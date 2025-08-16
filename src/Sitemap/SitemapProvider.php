<?php
namespace Keystone\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Builds XML for the main sitemap index and basic post-type sitemaps.
 *
 * @since 0.1.0
 */
class SitemapProvider {
	/**
	 * Render sitemap index XML.
	 *
	 * @return string
	 */
	public function render_index() {
		$items = array();

		// Posts sitemap.
		$items[] = array(
			'loc'     => home_url( '/sitemap-posts.xml' ),
			'lastmod' => esc_html( gmdate( 'c' ) ),
		);

		// Pages sitemap.
		$items[] = array(
			'loc'     => home_url( '/sitemap-pages.xml' ),
			'lastmod' => esc_html( gmdate( 'c' ) ),
		);

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
		<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			<?php foreach ( $items as $it ) : ?>
				<sitemap>
					<loc><?php echo esc_url( $it['loc'] ); ?></loc>
					<lastmod><?php echo esc_html( $it['lastmod'] ); ?></lastmod>
				</sitemap>
			<?php endforeach; ?>
		</sitemapindex>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a simple URL set for a given post type.
	 *
	 * @param string $post_type Post type.
	 * @return string
	 */
	public function render_urlset_for( $post_type ) {
		$q = new \WP_Query( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 1000,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		) );

		$urls = array();
		foreach ( $q->posts as $post_id ) {
			$urls[] = array(
				'loc'     => get_permalink( $post_id ),
				'lastmod' => get_post_modified_time( 'c', true, $post_id ),
			);
		}

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
		<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			<?php foreach ( $urls as $u ) : ?>
				<url>
					<loc><?php echo esc_url( $u['loc'] ); ?></loc>
					<lastmod><?php echo esc_html( $u['lastmod'] ); ?></lastmod>
				</url>
			<?php endforeach; ?>
		</urlset>
		<?php
		return ob_get_clean();
	}
}