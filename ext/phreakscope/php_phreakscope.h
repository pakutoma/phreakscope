#ifndef PHP_PHREAKSCOPE_H
#define PHP_PHREAKSCOPE_H

#include "php.h"
#include <pthread.h>
#include <stdint.h>

extern zend_module_entry phreakscope_module_entry;
#define phpext_phreakscope_ptr &phreakscope_module_entry

#define PHP_PHREAKSCOPE_VERSION "1.0.0"

#ifdef PHP_WIN32
#	define PHP_PHREAKSCOPE_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#	define PHP_PHREAKSCOPE_API __attribute__ ((visibility("default")))
#else
#	define PHP_PHREAKSCOPE_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

/* Location dictionary entry */
typedef struct _phreakscope_location {
    uint32_t id;
    char *key;
    size_t key_len;
    struct _phreakscope_location *next;
} phreakscope_location;

/* Sample entry with variable-length encoding */
typedef struct _phreakscope_sample {
    uint8_t *data;
    size_t size;
    size_t capacity;
} phreakscope_sample;

/* Global state */
ZEND_BEGIN_MODULE_GLOBALS(phreakscope)
    zend_bool enabled;
    pthread_t thread_id;
    pthread_mutex_t mutex;
    
    /* Configuration */
    long interval_usec;
    long buffer_bytes;
    long max_depth;
    
    /* Location dictionary */
    phreakscope_location **locations;
    uint32_t location_count;
    uint32_t location_capacity;
    
    /* Sample buffer (ring buffer) */
    phreakscope_sample *samples;
    size_t sample_write_pos;
    size_t sample_read_pos;
    size_t sample_capacity;
    
    /* Statistics */
    uint64_t total_samples;
    uint64_t dropped_samples;
ZEND_END_MODULE_GLOBALS(phreakscope)

#define PHREAKSCOPE_G(v) ZEND_MODULE_GLOBALS_ACCESSOR(phreakscope, v)

/* Default configuration */
#define PHREAKSCOPE_DEFAULT_INTERVAL_USEC 10000    /* 100Hz = 10ms */
#define PHREAKSCOPE_DEFAULT_BUFFER_BYTES  1048576   /* 1MB */
#define PHREAKSCOPE_DEFAULT_MAX_DEPTH     64

/* Hash table size for location dictionary */
#define PHREAKSCOPE_LOCATION_HASH_SIZE    4096

/* Function declarations */
PHP_MINIT_FUNCTION(phreakscope);
PHP_MSHUTDOWN_FUNCTION(phreakscope);
PHP_RINIT_FUNCTION(phreakscope);
PHP_RSHUTDOWN_FUNCTION(phreakscope);
PHP_MINFO_FUNCTION(phreakscope);

PHP_FUNCTION(phreakscope_start);
PHP_FUNCTION(phreakscope_stop);
PHP_FUNCTION(phreakscope_dump_raw);

#endif /* PHP_PHREAKSCOPE_H */