<?php
namespace Keystone\Sitemap;

use Keystone\Sitemap\Contracts\SitemapSourceInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Dynamic, provider-driven sitemaps:
 *  - /sitemap.xml (index)
 *  - /sitemap-{slug}-{page}.xml
 * Caches per page with transients (1 day).
 */
class SitemapController {

	protected $registry;

	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/** Add rewrite tags and rules. */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?keystone_sitemap=index', 'top' );
		add_rewrite_rule( '^sitemap-([a-z0-9\-]+)-([0-9]+)\.xml$', 'index.php?keystone_sitemap=$matches[1]&keystone_sitemap_page=$matches[2]', 'top' );
		add_rewrite_tag( '%keystone_sitemap%', '([^&]+)' );
		add_rewrite_tag( '%keystone_sitemap_page%', '([0-9]+)' );
	}

	/** Maybe render index or a page. */
	public function maybe_render() {
		$qs = get_query_var( 'keystone_sitemap' );
		if ( empty( $qs ) ) { return; }

		nocache_headers();
		header( 'Content-Type: application/xml; charset=utf-8' );

		if ( 'index' === $qs ) {
			echo $this->render_index(); // phpcs:ignore
			exit;
		}

		$slug = sanitize_key( $qs );
		$page = max( 1, absint( get_query_var( 'keystone_sitemap_page' ) ) );

		// Cache first.
		$cached = $this->registry->cache_get( $slug, $page );
		if ( $cached ) {
			echo $cached; // phpcs:ignore
			exit;
		}

		$source = $this->registry->get( $slug );
		if ( ! $source ) { status_header( 404 ); exit; }

		$xml = $source->render_page( $page );
		$this->registry->cache_set( $slug, $page, $xml );

		echo $xml; // phpcs:ignore
		exit;
	}

	/** Build master index combining all sources. */
	protected function render_index() {
		$entries = array();
		foreach ( $this->registry->all() as $src ) {
			$entries = array_merge( $entries, $src->index_entries() );
		}

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php foreach ( $entries as $e ) : ?>
	<sitemap><loc><?php echo esc_url( $e['loc'] ); ?></loc><lastmod><?php echo esc_html( $e['lastmod'] ); ?></lastmod></sitemap>
	<?php endforeach; ?>
</sitemapindex>
<?php
		return ob_get_clean();
	}
}