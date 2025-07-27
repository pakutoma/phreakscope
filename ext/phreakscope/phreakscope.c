#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_phreakscope.h"
#include "zend_exceptions.h"

#include <unistd.h>
#include <string.h>

/* Module globals */
ZEND_DECLARE_MODULE_GLOBALS(phreakscope)

/* Thread-local storage for signal safety */
static __thread int in_signal_handler = 0;

/* VarInt encoding helper */
static size_t encode_varint(uint8_t *buf, uint32_t value) {
    size_t i = 0;
    while (value >= 0x80) {
        buf[i++] = (value & 0x7F) | 0x80;
        value >>= 7;
    }
    buf[i++] = value & 0x7F;
    return i;
}

/* Hash function for location dictionary */
static uint32_t hash_location_key(const char *key, size_t len) {
    uint32_t hash = 5381;
    size_t i;
    for (i = 0; i < len; i++) {
        hash = ((hash << 5) + hash) + key[i];
    }
    return hash % PHREAKSCOPE_LOCATION_HASH_SIZE;
}

/* Find or create location in dictionary */
static uint32_t get_location_id(const char *key, size_t key_len) {
    uint32_t hash = hash_location_key(key, key_len);
    phreakscope_location *loc = PHREAKSCOPE_G(locations)[hash];
    
    /* Search in hash chain */
    while (loc) {
        if (loc->key_len == key_len && memcmp(loc->key, key, key_len) == 0) {
            return loc->id;
        }
        loc = loc->next;
    }
    
    /* Not found, create new location */
    pthread_mutex_lock(&PHREAKSCOPE_G(mutex));
    
    /* Double-check after acquiring lock */
    loc = PHREAKSCOPE_G(locations)[hash];
    while (loc) {
        if (loc->key_len == key_len && memcmp(loc->key, key, key_len) == 0) {
            pthread_mutex_unlock(&PHREAKSCOPE_G(mutex));
            return loc->id;
        }
        loc = loc->next;
    }
    
    /* Allocate new location */
    loc = (phreakscope_location *)malloc(sizeof(phreakscope_location));
    if (!loc) {
        pthread_mutex_unlock(&PHREAKSCOPE_G(mutex));
        return 0;
    }
    
    loc->id = ++PHREAKSCOPE_G(location_count);
    loc->key = (char *)malloc(key_len + 1);
    if (!loc->key) {
        free(loc);
        pthread_mutex_unlock(&PHREAKSCOPE_G(mutex));
        return 0;
    }
    
    memcpy(loc->key, key, key_len);
    loc->key[key_len] = '\0';
    loc->key_len = key_len;
    
    /* Insert into hash chain */
    loc->next = PHREAKSCOPE_G(locations)[hash];
    PHREAKSCOPE_G(locations)[hash] = loc;
    
    pthread_mutex_unlock(&PHREAKSCOPE_G(mutex));
    return loc->id;
}

/* Sampling thread function */
static void *phreakscope_sampling_thread(void *arg) {
    char location_buf[512];
    uint8_t sample_buf[1024];
    size_t sample_size;
    
#ifdef ZTS
    void ***tsrm_ls = (void ***)arg;
#endif
    
    while (PHREAKSCOPE_G(enabled)) {
        zend_execute_data *execute_data;
        int depth = 0;
        size_t buf_pos = 0;
        
        /* Get current execution context */
#ifdef ZTS
        execute_data = TSRMG_BULK(executor_globals_id, zend_executor_globals *)->current_execute_data;
#else
        execute_data = EG(current_execute_data);
#endif
        
        /* Walk the stack */
        while (execute_data && depth < PHREAKSCOPE_G(max_depth)) {
            zend_function *func = execute_data->func;
            
            if (func) {
                uint32_t location_id = 0;
                
                if (func->type == ZEND_USER_FUNCTION) {
                    /* User code: use file:line format */
                    if (execute_data->opline && func->op_array.filename) {
                        snprintf(location_buf, sizeof(location_buf), "%s:%d",
                                ZSTR_VAL(func->op_array.filename),
                                execute_data->opline->lineno);
                        location_id = get_location_id(location_buf, strlen(location_buf));
                    }
                } else if (func->common.function_name) {
                    /* Internal function: use function name */
                    const char *fname = ZSTR_VAL(func->common.function_name);
                    location_id = get_location_id(fname, strlen(fname));
                }
                
                if (location_id > 0) {
                    buf_pos += encode_varint(sample_buf + buf_pos, location_id);
                    depth++;
                }
            }
            
            execute_data = execute_data->prev_execute_data;
        }
        
        /* Store sample if we got any locations */
        if (buf_pos > 0) {
            pthread_mutex_lock(&PHREAKSCOPE_G(mutex));
            
            /* Get current write position */
            size_t write_pos = PHREAKSCOPE_G(sample_write_pos);
            phreakscope_sample *sample = &PHREAKSCOPE_G(samples)[write_pos];
            
            /* Ensure capacity */
            if (sample->capacity < buf_pos) {
                uint8_t *new_data = (uint8_t *)realloc(sample->data, buf_pos);
                if (new_data) {
                    sample->data = new_data;
                    sample->capacity = buf_pos;
                }
            }
            
            /* Copy sample data */
            if (sample->data && sample->capacity >= buf_pos) {
                memcpy(sample->data, sample_buf, buf_pos);
                sample->size = buf_pos;
                
                /* Advance write position */
                PHREAKSCOPE_G(sample_write_pos) = (write_pos + 1) % PHREAKSCOPE_G(sample_capacity);
                PHREAKSCOPE_G(total_samples)++;
                
                /* Check for overflow */
                if (PHREAKSCOPE_G(sample_write_pos) == PHREAKSCOPE_G(sample_read_pos)) {
                    /* Buffer full, drop oldest sample */
                    PHREAKSCOPE_G(sample_read_pos) = (PHREAKSCOPE_G(sample_read_pos) + 1) % PHREAKSCOPE_G(sample_capacity);
                    PHREAKSCOPE_G(dropped_samples)++;
                }
            }
            
            pthread_mutex_unlock(&PHREAKSCOPE_G(mutex));
        }
        
        /* Sleep for the specified interval */
        usleep(PHREAKSCOPE_G(interval_usec));
    }
    
    return NULL;
}

/* PHP Functions */
PHP_FUNCTION(phreakscope_start)
{
    if (PHREAKSCOPE_G(enabled)) {
        RETURN_FALSE;
    }
    
    PHREAKSCOPE_G(enabled) = 1;
    
    /* Create sampling thread */
#ifdef ZTS
    if (pthread_create(&PHREAKSCOPE_G(thread_id), NULL, phreakscope_sampling_thread, (void *)tsrm_get_ls_cache())) {
#else
    if (pthread_create(&PHREAKSCOPE_G(thread_id), NULL, phreakscope_sampling_thread, NULL)) {
#endif
        PHREAKSCOPE_G(enabled) = 0;
        php_error_docref(NULL, E_WARNING, "Failed to create sampling thread");
        RETURN_FALSE;
    }
    
    RETURN_TRUE;
}

PHP_FUNCTION(phreakscope_stop)
{
    if (!PHREAKSCOPE_G(enabled)) {
        RETURN_FALSE;
    }
    
    /* Signal thread to stop */
    PHREAKSCOPE_G(enabled) = 0;
    
    /* Wait for thread to finish */
    pthread_join(PHREAKSCOPE_G(thread_id), NULL);
    
    RETURN_TRUE;
}

PHP_FUNCTION(phreakscope_dump_raw)
{
    if (PHREAKSCOPE_G(enabled)) {
        php_error_docref(NULL, E_WARNING, "Cannot dump while profiling is active");
        RETURN_FALSE;
    }
    
    /* Calculate total size needed */
    size_t total_size = 0;
    size_t i;
    
    /* Size for location dictionary */
    total_size += sizeof(uint32_t); /* location count */
    for (i = 0; i < PHREAKSCOPE_LOCATION_HASH_SIZE; i++) {
        phreakscope_location *loc = PHREAKSCOPE_G(locations)[i];
        while (loc) {
            total_size += sizeof(uint32_t); /* id */
            total_size += sizeof(uint32_t); /* key length */
            total_size += loc->key_len;     /* key data */
            loc = loc->next;
        }
    }
    
    /* Size for samples */
    total_size += sizeof(uint64_t); /* sample count */
    size_t read_pos = PHREAKSCOPE_G(sample_read_pos);
    size_t write_pos = PHREAKSCOPE_G(sample_write_pos);
    
    while (read_pos != write_pos) {
        phreakscope_sample *sample = &PHREAKSCOPE_G(samples)[read_pos];
        total_size += sizeof(uint32_t); /* sample size */
        total_size += sample->size;     /* sample data */
        read_pos = (read_pos + 1) % PHREAKSCOPE_G(sample_capacity);
    }
    
    /* Allocate output buffer */
    zend_string *result = zend_string_alloc(total_size, 0);
    char *ptr = ZSTR_VAL(result);
    
    /* Write location dictionary */
    *(uint32_t *)ptr = PHREAKSCOPE_G(location_count);
    ptr += sizeof(uint32_t);
    
    for (i = 0; i < PHREAKSCOPE_LOCATION_HASH_SIZE; i++) {
        phreakscope_location *loc = PHREAKSCOPE_G(locations)[i];
        while (loc) {
            *(uint32_t *)ptr = loc->id;
            ptr += sizeof(uint32_t);
            
            *(uint32_t *)ptr = loc->key_len;
            ptr += sizeof(uint32_t);
            
            memcpy(ptr, loc->key, loc->key_len);
            ptr += loc->key_len;
            
            loc = loc->next;
        }
    }
    
    /* Write samples */
    uint64_t sample_count = 0;
    char *sample_count_ptr = ptr;
    ptr += sizeof(uint64_t);
    
    read_pos = PHREAKSCOPE_G(sample_read_pos);
    while (read_pos != write_pos) {
        phreakscope_sample *sample = &PHREAKSCOPE_G(samples)[read_pos];
        
        *(uint32_t *)ptr = sample->size;
        ptr += sizeof(uint32_t);
        
        memcpy(ptr, sample->data, sample->size);
        ptr += sample->size;
        
        sample_count++;
        read_pos = (read_pos + 1) % PHREAKSCOPE_G(sample_capacity);
    }
    
    /* Update sample count */
    *(uint64_t *)sample_count_ptr = sample_count;
    
    ZSTR_LEN(result) = ptr - ZSTR_VAL(result);
    RETURN_STR(result);
}

/* Module functions */
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("phreakscope.interval_usec", "10000", PHP_INI_SYSTEM, OnUpdateLong, interval_usec, zend_phreakscope_globals, phreakscope_globals)
    STD_PHP_INI_ENTRY("phreakscope.buffer_bytes", "1048576", PHP_INI_SYSTEM, OnUpdateLong, buffer_bytes, zend_phreakscope_globals, phreakscope_globals)
    STD_PHP_INI_ENTRY("phreakscope.max_depth", "64", PHP_INI_SYSTEM, OnUpdateLong, max_depth, zend_phreakscope_globals, phreakscope_globals)
PHP_INI_END()

static void php_phreakscope_init_globals(zend_phreakscope_globals *phreakscope_globals)
{
    phreakscope_globals->enabled = 0;
    phreakscope_globals->interval_usec = PHREAKSCOPE_DEFAULT_INTERVAL_USEC;
    phreakscope_globals->buffer_bytes = PHREAKSCOPE_DEFAULT_BUFFER_BYTES;
    phreakscope_globals->max_depth = PHREAKSCOPE_DEFAULT_MAX_DEPTH;
    phreakscope_globals->location_count = 0;
    phreakscope_globals->total_samples = 0;
    phreakscope_globals->dropped_samples = 0;
}

PHP_MINIT_FUNCTION(phreakscope)
{
    ZEND_INIT_MODULE_GLOBALS(phreakscope, php_phreakscope_init_globals, NULL);
    REGISTER_INI_ENTRIES();
    
    return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(phreakscope)
{
    UNREGISTER_INI_ENTRIES();
    return SUCCESS;
}

PHP_RINIT_FUNCTION(phreakscope)
{
    /* Initialize mutex */
    pthread_mutex_init(&PHREAKSCOPE_G(mutex), NULL);
    
    /* Allocate location hash table */
    PHREAKSCOPE_G(locations) = (phreakscope_location **)calloc(PHREAKSCOPE_LOCATION_HASH_SIZE, sizeof(phreakscope_location *));
    
    /* Calculate sample buffer capacity */
    size_t sample_capacity = PHREAKSCOPE_G(buffer_bytes) / sizeof(phreakscope_sample);
    if (sample_capacity < 100) {
        sample_capacity = 100;
    }
    
    PHREAKSCOPE_G(sample_capacity) = sample_capacity;
    PHREAKSCOPE_G(samples) = (phreakscope_sample *)calloc(sample_capacity, sizeof(phreakscope_sample));
    PHREAKSCOPE_G(sample_read_pos) = 0;
    PHREAKSCOPE_G(sample_write_pos) = 0;
    
    return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(phreakscope)
{
    size_t i;
    
    /* Stop profiling if active */
    if (PHREAKSCOPE_G(enabled)) {
        PHREAKSCOPE_G(enabled) = 0;
        pthread_join(PHREAKSCOPE_G(thread_id), NULL);
    }
    
    /* Free location dictionary */
    if (PHREAKSCOPE_G(locations)) {
        for (i = 0; i < PHREAKSCOPE_LOCATION_HASH_SIZE; i++) {
            phreakscope_location *loc = PHREAKSCOPE_G(locations)[i];
            while (loc) {
                phreakscope_location *next = loc->next;
                free(loc->key);
                free(loc);
                loc = next;
            }
        }
        free(PHREAKSCOPE_G(locations));
    }
    
    /* Free sample buffer */
    if (PHREAKSCOPE_G(samples)) {
        for (i = 0; i < PHREAKSCOPE_G(sample_capacity); i++) {
            if (PHREAKSCOPE_G(samples)[i].data) {
                free(PHREAKSCOPE_G(samples)[i].data);
            }
        }
        free(PHREAKSCOPE_G(samples));
    }
    
    /* Destroy mutex */
    pthread_mutex_destroy(&PHREAKSCOPE_G(mutex));
    
    return SUCCESS;
}

PHP_MINFO_FUNCTION(phreakscope)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "phreakscope support", "enabled");
    php_info_print_table_row(2, "Version", PHP_PHREAKSCOPE_VERSION);
    php_info_print_table_row(2, "Sampling interval", "configurable via phreakscope.interval_usec");
    php_info_print_table_row(2, "Buffer size", "configurable via phreakscope.buffer_bytes");
    php_info_print_table_end();
    
    DISPLAY_INI_ENTRIES();
}

/* Function entries */
const zend_function_entry phreakscope_functions[] = {
    PHP_FE(phreakscope_start, NULL)
    PHP_FE(phreakscope_stop, NULL)
    PHP_FE(phreakscope_dump_raw, NULL)
    PHP_FE_END
};

/* Module entry */
zend_module_entry phreakscope_module_entry = {
    STANDARD_MODULE_HEADER,
    "phreakscope",
    phreakscope_functions,
    PHP_MINIT(phreakscope),
    PHP_MSHUTDOWN(phreakscope),
    PHP_RINIT(phreakscope),
    PHP_RSHUTDOWN(phreakscope),
    PHP_MINFO(phreakscope),
    PHP_PHREAKSCOPE_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PHREAKSCOPE
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(phreakscope)
#endif