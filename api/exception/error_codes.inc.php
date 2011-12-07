<?php
/**
 * error_codes.inc.php
 * 
 * This file is where all error codes are defined.
 * All error code are named after the class and function they occur in.
 */

/**
 * Error number category defines.
 */
define( 'ARGUMENT_BEARTOOTH_BASE_ERRNO',   105000 );
define( 'DATABASE_BEARTOOTH_BASE_ERRNO',   205000 );
define( 'LDAP_BEARTOOTH_BASE_ERRNO',       305000 );
define( 'NOTICE_BEARTOOTH_BASE_ERRNO',     405000 );
define( 'PERMISSION_BEARTOOTH_BASE_ERRNO', 505000 );
define( 'RUNTIME_BEARTOOTH_BASE_ERRNO',    605000 );
define( 'SYSTEM_BEARTOOTH_BASE_ERRNO',     705000 );
define( 'TEMPLATE_BEARTOOTH_BASE_ERRNO',   805000 );
define( 'VOIP_BEARTOOTH_BASE_ERRNO',       905000 );

/**
 * "argument" error codes
 */

/**
 * "database" error codes
 * 
 * Since database errors already have codes this list is likely to stay empty.
 */

/**
 * "ldap" error codes
 * 
 * Since ldap errors already have codes this list is likely to stay empty.
 */

/**
 * "notice" error codes
 */

/**
 * "permission" error codes
 */

/**
 * "runtime" error codes
 */

/**
 * "system" error codes
 * 
 * Since system errors already have codes this list is likely to stay empty.
 * Note the following PHP error codes:
 *      1: error,
 *      2: warning,
 *      4: parse,
 *      8: notice,
 *     16: core error,
 *     32: core warning,
 *     64: compile error,
 *    128: compile warning,
 *    256: user error,
 *    512: user warning,
 *   1024: user notice
 */

/**
 * "template" error codes
 * 
 * Since template errors already have codes this list is likely to stay empty.
 */

/**
 * "voip" error codes
 */

?>
