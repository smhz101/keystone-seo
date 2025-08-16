<?php
/**
 * Plugin Name: Keystone SEO
 * Description: Developer-first, modular SEO suite for WordPress. Free & powerful.
 * Version: 0.1.0
 * Author: Keystone
 * Text Domain: keystone-seo
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constants
 */
define( 'KEYSTONE_SEO_FILE', __FILE__ );
define( 'KEYSTONE_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'KEYSTONE_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'KEYSTONE_SEO_VERSION', '0.1.0' );

// Composer autoload (optional but supported).
$autoload = KEYSTONE_SEO_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} else {
	// Fallback simple autoloader for PSR-4 namespace "Keystone\" -> /src/
	spl_autoload_register(
		function ( $class ) {
			if ( 0 !== strpos( $class, 'Keystone\\' ) ) {
				return;
			}
			$path = KEYSTONE_SEO_DIR . 'src/' . str_replace( array( 'Keystone\\', '\\' ), array( '', '/' ), $class ) . '.php';
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	);
}

use Keystone\Keystone;