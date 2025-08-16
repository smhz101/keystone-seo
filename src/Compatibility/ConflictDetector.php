<?php
namespace Keystone\Compatibility;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Detects presence of major SEO plugins and optionally suppresses their head output
 * to avoid duplicate tags. We DO NOT use anonymous callbacks in hooks.
 *
 * Yoast suppression:
 * - remove_action 'wpseo_head' via Front_End_Integration instance. https://developer.yoast.com/customization/yoast-seo/disabling-yoast-seo/ :contentReference[oaicite:8]{index=8}
 *
 * Rank Math suppression:
 * - remove_all_actions 'rank_math/head' within wp_head. https://rankmath.com/kb/how-to-disable-all-generated-seo-tags/ :contentReference[oaicite:9]{index=9}
 *
 * AIOSEO suppression:
 * - use 'aioseo_disable' true. https://aioseo.com/docs/aioseo_disable/ :contentReference[oaicite:10]{index=10}
 */
class ConflictDetector {

	/** @var string */
	protected $option_key = 'keystone_compat_suppress_plugins';

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'init', array( $this, 'maybe_suppress' ), 5 );
	}

	/**
	 * Show admin notice if another SEO plugin appears active.
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$conflicts = $this->active_conflicts();
		if ( empty( $conflicts ) ) { return; }

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Keystone SEO detected other SEO plugins. To avoid duplicate meta tags, please deactivate them or enable suppression in Keystone settings.', 'keystone-seo' );
		echo '</p></div>';
	}

	/**
	 * If opted-in (option true), suppress third-party head output.
	 */
	public function maybe_suppress() {
		$enabled = (bool) apply_filters( 'keystone/compat/suppress_plugins', (bool) get_option( $this->option_key, false ) );

		if ( ! $enabled ) {
			return;
		}

		// Yoast
		if ( defined( 'WPSEO_VERSION' ) && function_exists( 'YoastSEO' ) ) {
			// Front-end integration class is resolved by container.
			$yoast = \YoastSEO()->classes->get( \Yoast\WP\SEO\Integrations\Front_End_Integration::class );
			remove_action( 'wpseo_head', array( $yoast, 'present_head' ), -9999 ); // per Yoast docs.
		}

		// Rank Math
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			// Remove Rank Math SEO tags early while in head.
			add_action( 'wp_head', array( $this, 'rm_disable_head' ), 1 );
		}

		// AIOSEO
		if ( function_exists( 'aioseo' ) || defined( 'AIOSEO_VERSION' ) ) {
			add_filter( 'aioseo_disable', array( $this, 'aioseo_disable_all' ), 10, 1 );
		}
	}

	/**
	 * Separate public method targets to avoid closures in hooks.
	 */
	public function rm_disable_head() {
		if ( did_action( 'rank_math/head' ) === 0 ) {
			remove_all_actions( 'rank_math/head' );
		}
	}

	/**
	 * Filter callback to disable all AIOSEO output.
	 *
	 * @param bool $disabled
	 * @return bool
	 */
	public function aioseo_disable_all( $disabled ) {
		return true;
	}

	/**
	 * Returns array of active conflicting SEO plugins.
	 *
	 * @return string[]
	 */
	protected function active_conflicts() {
		$names = array();

		if ( defined( 'WPSEO_VERSION' ) || function_exists( 'YoastSEO' ) ) {
			$names[] = 'Yoast SEO';
		}
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			$names[] = 'Rank Math';
		}
		if ( function_exists( 'aioseo' ) || defined( 'AIOSEO_VERSION' ) ) {
			$names[] = 'All in One SEO';
		}
		return $names;
	}
}