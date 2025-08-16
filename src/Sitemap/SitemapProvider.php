<?php
namespace Keystone\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Builds index + chunked post/page sitemaps.
 */
class SitemapProvider {
	protected $per_page = 2000;

	public function render_index() {
		$maps = array();

		foreach ( array( 'post', 'page' ) as $type ) {
			$total = (int) wp_count_posts( $type )->publish;
			$pages = max( 1, (int) ceil( $total / $this->per_page ) );

			for ( $i = 1; $i <= $pages; $i++ ) {
				$slug = $type . 's-' . $i;
				$maps[] = array(
					'loc'     => home_url( '/sitemap-' . $slug . '.xml' ),
					'lastmod' => esc_html( gmdate( 'c' ) ),
				);
			}
		}

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php foreach ( $maps as $m ) : ?>
	<sitemap><loc><?php echo esc_url( $m['loc'] ); ?></loc><lastmod><?php echo esc_html( $m['lastmod'] ); ?></lastmod></sitemap>
	<?php endforeach; ?>
</sitemapindex>
<?php
		return ob_get_clean();
	}

	public function render_urlset_for_page( $post_type, $page ) {
		$page     = max( 1, absint( $page ) );
		$offset   = ( $page - 1 ) * $this->per_page;

		$q = new \WP_Query( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $this->per_page,
			'offset'         => $offset,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		) );

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php foreach ( $q->posts as $post_id ) : ?>
	<url>
		<loc><?php echo esc_url( get_permalink( $post_id ) ); ?></loc>
		<lastmod><?php echo esc_html( get_post_modified_time( 'c', true, $post_id ) ); ?></lastmod>
	</url>
	<?php endforeach; ?>
</urlset>
<?php
		return ob_get_clean();
	}
}