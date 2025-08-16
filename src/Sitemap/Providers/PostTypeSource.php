<?php
namespace Keystone\Sitemap\Providers;

use Keystone\Sitemap\Contracts\SitemapSourceInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Chunked sitemap for a given post type (published only) + images.
 * - Per page: 2000 URLs (safe for most engines).
 * - Includes <image:image> for featured image (if present).
 */
class PostTypeSource implements SitemapSourceInterface {
	protected $post_type;
	protected $per_page = 2000;

	public function __construct( $post_type ) {
		$this->post_type = (string) $post_type;
	}

	public function slug() {
		return ( 'post' === $this->post_type ) ? 'posts'
			: ( 'page' === $this->post_type ? 'pages' : 'cpt-' . sanitize_title( $this->post_type ) );
	}

	public function index_entries() {
		$total = 0;
		$count = wp_count_posts( $this->post_type );
		if ( is_object( $count ) && isset( $count->publish ) ) {
			$total = (int) $count->publish;
		}
		$pages = max( 1, (int) ceil( $total / $this->per_page ) );

		$out = array();
		for ( $i = 1; $i <= $pages; $i++ ) {
			$out[] = array(
				'loc'     => home_url( '/sitemap-' . $this->slug() . '-' . $i . '.xml' ),
				'lastmod' => gmdate( 'c' ),
			);
		}
		return $out;
	}

	public function render_page( $page ) {
		$page   = max( 1, absint( $page ) );
		$offset = ( $page - 1 ) * $this->per_page;

		$q = new \WP_Query( array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $this->per_page,
			'offset'         => $offset,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
	<?php foreach ( $q->posts as $post_id ) :
		$loc     = get_permalink( $post_id );
		$lastmod = get_post_modified_time( 'c', true, $post_id );
		$img     = get_the_post_thumbnail_url( $post_id, 'full' );
		?>
	<url>
		<loc><?php echo esc_url( $loc ); ?></loc>
		<lastmod><?php echo esc_html( $lastmod ); ?></lastmod>
		<?php if ( $img ) : ?>
		<image:image><image:loc><?php echo esc_url( $img ); ?></image:loc></image:image>
		<?php endif; ?>
	</url>
	<?php endforeach; ?>
</urlset>
<?php
		return ob_get_clean();
	}
}