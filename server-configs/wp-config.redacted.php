<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */
// define('FORCE_SSL_ADMIN', true);
define('WP_CACHE', true);
define( 'WPCACHEHOME', "/var/www/html/wp-content/plugins/wp-super-cache/" );
// $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
// $_SERVER['HTTPS']='on';
// $_SERVER['HTTP_HOST']='easycare.dnow.hk';

$_SERVER['HTTPS'] = 'on';
define('FORCE_SSL_LOGIN', true);
define('FORCE_SSL_ADMIN', true);

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'angelcare_wordpress_dev' );

/** Database username */
define( 'DB_USER', 'doctornow' );

/** Database password */
define( 'DB_PASSWORD', 'REDACTED' );

/** Database hostname */
define( 'DB_HOST', 'rm-3nsd2db8f226efy18.mysql.rds.aliyuncs.com' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'REDACTED' );
define( 'SECURE_AUTH_KEY',  'REDACTED' );
define( 'LOGGED_IN_KEY',    'REDACTED' );
define( 'NONCE_KEY',        'REDACTED' );
define( 'AUTH_SALT',        'REDACTED' );
define( 'SECURE_AUTH_SALT', 'REDACTED' );
define( 'LOGGED_IN_SALT',   'REDACTED' );
define( 'NONCE_SALT',       'REDACTED' );


define('STRATUSX_DELETE_IMAGE_API_KEY', 'IsbXSbbCFo[ba2iFjHb^!;YN<7vzgX[UsUeIev{kG+CRe');
define('STRATUSX_QWEN_API_KEY', 'sk-2e73644b7f3a454686bd7c2122150c82');
define('STRATUSX_CRON_API_KEY', '9JKAN3DAJ7KA0J5SKHQW23JKNZMKLAQO');
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



define( 'WP_REDIS_HOST', 'angelcare-redis' );
define( 'WP_REDIS_PORT', '6379' );
define( 'WP_REDIS_DATABASE', '3' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
