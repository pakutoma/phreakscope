PHP_ARG_ENABLE(phreakscope, whether to enable phreakscope support,
[  --enable-phreakscope    Enable phreakscope support])

if test "$PHP_PHREAKSCOPE" != "no"; then
  AC_CHECK_HEADER(pthread.h,, [AC_MSG_ERROR([pthread.h header not found])])
  AC_CHECK_LIB(pthread, pthread_create,, [AC_MSG_ERROR([pthread library not found])])
  
  PHP_ADD_LIBRARY(pthread, 1, PHREAKSCOPE_SHARED_LIBADD)
  PHP_SUBST(PHREAKSCOPE_SHARED_LIBADD)
  
  PHP_NEW_EXTENSION(phreakscope, phreakscope.c, $ext_shared)
fi