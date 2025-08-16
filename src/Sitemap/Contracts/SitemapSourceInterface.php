<?php
namespace Keystone\Sitemap\Contracts;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Contract for any sitemap data source (posts, taxonomies, etc).
 * Each source exposes:
 *  - a unique $slug used in URLs: /sitemap-{$slug}-{$page}.xml
 *  - index entries (how many pages exist)
 *  - renderer for a given page (1-based)
 */
interface SitemapSourceInterface {
	/** Return unique, url-safe slug, e.g., "posts", "pages", "products", "tax-category". */
	public function slug();

	/**
	 * Return an array of index entries for this source.
	 * Each item: ['loc' => url, 'lastmod' => ISO8601]
	 */
	public function index_entries();

	/**
	 * Render an XML <urlset> string for the given page (1-based).
	 * Must return complete XML (including XML header + <urlset>).
	 *
	 * @param int $page Page number (1..N).
	 * @return string
	 */
	public function render_page( $page );
}