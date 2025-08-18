<?php
namespace Keystone\App\Services\Sitemap\Providers;

use Keystone\App\Services\Sitemap\Contracts\SitemapSourceInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Chunked sitemap for a taxonomy (public terms).
 */
class TaxonomySource implements SitemapSourceInterface {
	protected $taxonomy;
	protected $per_page = 2000;

	public function __construct( $taxonomy ) {
		$this->taxonomy = (string) $taxonomy;
	}

	public function slug() {
		return 'tax-' . sanitize_title( $this->taxonomy );
	}

	public function index_entries() {
		$total = (int) wp_count_terms( $this->taxonomy, array( 'hide_empty' => true ) );
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

		$terms = get_terms( array(
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => true,
			'number'     => $this->per_page,
			'offset'     => $offset,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) ) { $terms = array(); }

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php foreach ( $terms as $t ) :
		$loc = get_term_link( $t );
		?>
	<url>
		<loc><?php echo esc_url( $loc ); ?></loc>
	</url>
	<?php endforeach; ?>
</urlset>
<?php
		return ob_get_clean();
	}
}