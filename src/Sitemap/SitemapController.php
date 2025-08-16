<?php
namespace Keystone\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Wires rewrite rules + serves sitemap XMLs.
 *
 * @since 0.1.0
 */
class SitemapController {
	/** @var SitemapProvider */
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
		add_rewrite_rule( '^sitemap-posts\.xml$', 'index.php?keystone_sitemap=posts', 'top' );
		add_rewrite_rule( '^sitemap-pages\.xml$', 'index.php?keystone_sitemap=pages', 'top' );
		add_rewrite_tag( '%keystone_sitemap%', '([^&]+)' );
	}

	/**
	 * Template redirect handler to output XML.
	 *
	 * @return void
	 */
	public function maybe_render() {
		$type = get_query_var( 'keystone_sitemap' );
		if ( empty( $type ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/xml; charset=utf-8' );

		if ( 'index' === $type ) {
			echo $this->provider->render_index(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		if ( 'posts' === $type ) {
			echo $this->provider->render_urlset_for( 'post' ); // phpcs:ignore
			exit;
		}

		if ( 'pages' === $type ) {
			echo $this->provider->render_urlset_for( 'page' ); // phpcs:ignore
			exit;
		}
	}
}