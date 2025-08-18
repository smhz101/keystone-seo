<?php
namespace Keystone\App\Services\IndexNow;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * IndexNow integration:
 * - Generates & exposes the key (file or dynamic route fallback).
 * - Pings engines on publish/update/delete.
 * - Rate-limited with transients to avoid abuse.
 *
 * Settings keys used:
 *  - indexnow_enabled (bool)
 *  - indexnow_key (hex string)
 *  - indexnow_endpoints (string[] URLs)
 */
class IndexNowService {
	protected $settings = array();

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/** Ensure a key exists; return it. */
	public function ensure_key() {
		$key = isset( $this->settings['indexnow_key'] ) ? (string) $this->settings['indexnow_key'] : '';
		if ( $key && preg_match( '/^[a-f0-9]{32}$/i', $key ) ) { return strtolower( $key ); }

		$key = strtolower( wp_generate_password( 32, false, false ) );
		$key = preg_replace( '/[^a-f0-9]/i', 'a', $key ); // sanitize to hex-like

		$opts = get_option( 'keystone_seo_settings', array() );
		$opts['indexnow_key'] = $key;
		update_option( 'keystone_seo_settings', $opts );

		$this->settings['indexnow_key'] = $key;
		return $key;
	}

	/** Try to write {key}.txt at ABSPATH. Returns absolute path or empty. */
	public function maybe_write_key_file() {
		$key = $this->ensure_key();
		$target = trailingslashit( ABSPATH ) . $key . '.txt';

		// Already present?
		if ( file_exists( $target ) ) { return $target; }

		// Attempt to write.
		$written = @file_put_contents( $target, $key ); // phpcs:ignore
		if ( false !== $written ) { return $target; }

		return ''; // fall back to dynamic route
	}

	/** Transition hook: ping on publish/update. */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( ! $this->is_enabled() ) { return; }
		if ( 'publish' !== $new_status ) { return; }

		$url = get_permalink( $post->ID );
		$this->ping_url( $url );
	}

	/** Deletion hook: ping removed URL (engines will recrawl). */
	public function on_deleted_post( $post_id ) {
		if ( ! $this->is_enabled() ) { return; }
		$url = get_permalink( $post_id );
		if ( $url ) { $this->ping_url( $url ); }
	}

	/** Public method: ping a URL to all configured endpoints (rate-limited). */
	public function ping_url( $url ) {
		$url = esc_url_raw( $url );
		if ( ! $url ) { return; }
		if ( ! $this->is_enabled() ) { return; }

		$key = $this->ensure_key();

		// Rate limit: 1 request per 5 seconds per endpoint.
		$endpoints = $this->endpoints();
		foreach ( $endpoints as $ep ) {
			$stamp_key = 'kseo_ix_rl_' . md5( $ep );
			if ( get_transient( $stamp_key ) ) { continue; }
			set_transient( $stamp_key, 1, 5 );

			$ping = add_query_arg( array(
				'url' => rawurlencode( $url ),
				'key' => $key,
			), $ep );

			// GET (simple). Engines also support POST batches; we keep it lean.
			$args = array(
				'timeout' => 5,
				'headers' => array( 'Accept' => 'application/json' ),
				'redirection' => 3,
			);
			wp_remote_get( $ping, $args );
		}
	}

	/** Helper: enabled flag */
	protected function is_enabled() {
		return ! empty( $this->settings['indexnow_enabled'] );
	}

	/** Helper: endpoints (defaults to Bing). */
	protected function endpoints() {
		$eps = isset( $this->settings['indexnow_endpoints'] ) && is_array( $this->settings['indexnow_endpoints'] )
			? $this->settings['indexnow_endpoints']
			: array();
		$eps = array_filter( array_map( 'esc_url_raw', $eps ) );
		if ( empty( $eps ) ) {
			$eps = array( 'https://www.bing.com/indexnow' );
		}
		return array_values( array_unique( $eps ) );
	}
}