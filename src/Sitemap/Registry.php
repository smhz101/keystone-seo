<?php
namespace Keystone\Sitemap;

use Keystone\Sitemap\Contracts\SitemapSourceInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Registry of sitemap sources + small cache helpers.
 */
class Registry {
	/** @var SitemapSourceInterface[] */
	protected $sources = array();

	/** Register a source (later sources override same slug). */
	public function add( SitemapSourceInterface $source ) {
		$this->sources[ $source->slug() ] = $source;
	}

	/** @return SitemapSourceInterface[] */
	public function all() {
		return $this->sources;
	}

	/** @return SitemapSourceInterface|null */
	public function get( $slug ) {
		return isset( $this->sources[ $slug ] ) ? $this->sources[ $slug ] : null;
	}

	/** Small helper: cache key for a page. */
	public function key( $slug, $page ) {
		return 'kseo_sm_' . sanitize_key( $slug ) . '_' . absint( $page );
	}

	/** Get cached string, if any (1 day). */
	public function cache_get( $slug, $page ) {
		$key = $this->key( $slug, $page );
		return get_transient( $key ) ?: '';
	}

	/** Set cached string (1 day). */
	public function cache_set( $slug, $page, $xml ) {
		set_transient( $this->key( $slug, $page ), (string) $xml, DAY_IN_SECONDS );
	}
}