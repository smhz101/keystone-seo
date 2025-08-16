<?php
namespace Keystone\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SitemapController {

	protected $provider;

	public function __construct( SitemapProvider $provider ) {
		$this->provider = $provider;
	}

	/**
	 * Add rewrites for sitemaps.
	 *
	 * @return void
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?keystone_sitemap=index', 'top' );
		add_rewrite_rule( '^sitemap-(posts|pages)-([0-9]+)\.xml$', 'index.php?keystone_sitemap=$matches[1]&keystone_sitemap_page=$matches[2]', 'top' );
		add_rewrite_tag( '%keystone_sitemap%', '([^&]+)' );
		add_rewrite_tag( '%keystone_sitemap_page%', '([0-9]+)' );
	}
	
	/**
	 * Template redirect handler to output XML.
	 *
	 * @return void
	 */
	public function maybe_render() {
		$type = get_query_var( 'keystone_sitemap' );
		if ( empty( $type ) ) { return; }

		nocache_headers();
		header( 'Content-Type: application/xml; charset=utf-8' );

		if ( 'index' === $type ) {
			echo $this->provider->render_index(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		$page = (int) get_query_var( 'keystone_sitemap_page' );
		$pt   = ( 'posts' === $type ) ? 'post' : 'page';

		echo $this->provider->render_urlset_for_page( $pt, $page ?: 1 ); // phpcs:ignore
		exit;
	}
}