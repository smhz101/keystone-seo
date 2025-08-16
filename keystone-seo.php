<?php
/**
 * Plugin Name: Keystone SEO
 * Description: Developer-first, modular SEO suite for WordPress. Free & powerful.
 * Version: 1.8.0
 * Author: Keystone
 * Text Domain: keystone-seo
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constants (used across plugin)
 */
define( 'KEYSTONE_SEO_FILE', __FILE__ );
define( 'KEYSTONE_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'KEYSTONE_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'KEYSTONE_SEO_VERSION', '1.8.0' );

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

/**
 * Activation: DB tables, rewrite rules, caps scaffolding.
 */
function keystone_seo_activate() {
	if ( class_exists( Keystone::class ) ) {
		$instance = new Keystone();
		$instance->activate();
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'keystone_seo_activate' );

/**
 * Deactivation: flush rewrites only (keep data).
 */
function keystone_seo_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'keystone_seo_deactivate' );

/**
 * Bootstrap runtime.
 */
add_action( 'plugins_loaded', 'keystone_seo_boot' );
function keystone_seo_boot() {
	if ( ! class_exists( Keystone::class ) ) {
		return;
	}
	$plugin = new Keystone();
	$plugin->run();
}