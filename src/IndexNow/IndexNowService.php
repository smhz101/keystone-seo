<?php
namespace Keystone\IndexNow;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * IndexNow pings on publish/update/delete (opt-in).
 * Uses the gateway endpoint https://api.indexnow.org/indexnow
 * Payload: { host, key, urlList[] } per spec.
 */
class IndexNowService {
	/** @var array */
	protected $settings;

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Whether IndexNow is enabled and key present.
	 *
	 * @return bool
	 */
	protected function enabled() {
		return ! empty( $this->settings['indexnow'] ) && ! empty( $this->settings['indexnowKey'] );
	}

	/**
	 * Handle post transitions; ping on publish/updates of public posts.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param \WP_Post $post      Post object.
	 * @return void
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( ! $this->enabled() ) { return; }
		if ( 'publish' !== $new_status ) { return; }
		if ( 'revision' === $post->post_type ) { return; }
		if ( 'auto-draft' === $old_status && 'publish' === $new_status ) { /* first publish OK */ }

		if ( 'publish' === $new_status ) {
			$url = get_permalink( $post );
			if ( $url ) {
				$this->ping_urls( array( $url ) );
			}
		}
	}

	/**
	 * Ping on delete (send homepage to signal re-crawl).
	 *
	 * @param int $post_id Deleted post ID.
	 * @return void
	 */
	public function on_deleted_post( $post_id ) {
		if ( ! $this->enabled() ) { return; }
		$this->ping_urls( array( home_url( '/' ) ) );
	}

	/**
	 * Send IndexNow request (fire-and-forget with small timeout).
	 *
	 * @param string[] $urls URLs to ping.
	 * @return void
	 */
	public function ping_urls( $urls ) {
		$urls = array_values( array_filter( array_map( 'esc_url_raw', (array) $urls ) ) );
		if ( empty( $urls ) ) { return; }

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$key  = sanitize_text_field( $this->settings['indexnowKey'] );

		$body = array(
			'host'     => $host,
			'key'      => $key,
			'urlList'  => $urls,
		);

		wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'timeout' => 2,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);
	}
}