<?php
namespace Keystone\App\Services\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Simple invalidator: clears all keystone sitemap transients on content changes.
 * Trade-off: conservative but robust (keeps code small).
 */
class CacheInvalidator {

	public function on_save_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) { return; }
		$this->purge();
	}

	public function on_deleted_post( $post_id ) {
		$this->purge();
	}

	public function on_edited_term( $term_id, $tt_id, $taxonomy ) { // phpcs:ignore
		$this->purge();
	}

	/** Remove transients with prefix 'kseo_sm_' */
	protected function purge() {
		global $wpdb;
		// Works for all object caches using options table fallback.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_kseo_sm_%' OR option_name LIKE '_transient_timeout_kseo_sm_%'" ); // phpcs:ignore
	}
}