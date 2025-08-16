<?php
namespace Keystone\Breadcrumbs;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Renders accessible breadcrumbs + Schema.org BreadcrumbList JSON-LD.
 * Usage:
 *  - Shortcode: [keystone_breadcrumbs]
 *  - Template tag: keystone_breadcrumbs();
 *
 * @since 0.1.0
 */
class BreadcrumbsService {
	/** Build the breadcrumb trail as array of ['label','url'] items. */
	public function trail() {
		$items = array();

		$items[] = array( 'label' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) );

		if ( is_home() || is_front_page() ) {
			return $items;
		}

		if ( is_singular( 'post' ) ) {
			$cat = get_the_category();
			if ( $cat ) {
				$primary = $cat[0];
				$items[] = array( 'label' => $primary->name, 'url' => get_category_link( $primary ) );
			}
			$items[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
		} elseif ( is_page() ) {
			$parents = array_reverse( get_post_ancestors( get_queried_object_id() ) );
			foreach ( $parents as $pid ) {
				$items[] = array( 'label' => get_the_title( $pid ), 'url' => get_permalink( $pid ) );
			}
			$items[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term    = get_queried_object();
			$items[] = array( 'label' => single_term_title( '', false ), 'url' => get_term_link( $term ) );
		} elseif ( is_search() ) {
			$items[] = array( 'label' => sprintf( esc_html__( 'Search: %s', 'keystone-seo' ), get_search_query() ), 'url' => '' );
		} elseif ( is_author() ) {
			$items[] = array( 'label' => get_the_author(), 'url' => '' );
		} elseif ( is_post_type_archive() ) {
			$pt      = get_query_var( 'post_type' );
			$items[] = array( 'label' => post_type_archive_title( '', false ), 'url' => get_post_type_archive_link( $pt ) );
		} elseif ( is_404() ) {
			$items[] = array( 'label' => esc_html__( 'Not Found', 'keystone-seo' ), 'url' => '' );
		}

		/** Let developers alter the trail. */
		return apply_filters( 'keystone/breadcrumbs/trail', $items );
	}

	/** Return full HTML + JSON-LD. */
	public function render() {
		$items = $this->trail();

		// HTML (accessible nav).
		ob_start();
		?>
<nav class="keystone-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'keystone-seo' ); ?>">
	<ol>
		<?php foreach ( $items as $i => $it ) : ?>
			<li>
				<?php if ( ! empty( $it['url'] ) && $i < count( $items ) - 1 ) : ?>
					<a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['label'] ); ?></a>
				<?php else : ?>
					<span aria-current="page"><?php echo esc_html( $it['label'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
		<?php
		$html = ob_get_clean();

		// JSON-LD graph.
		$list = array();
		foreach ( $items as $idx => $it ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $idx + 1,
				'name'     => $it['label'],
				'item'     => ! empty( $it['url'] ) ? $it['url'] : null,
			);
		}
		$payload = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		);

		$script = '<script type="application/ld+json">' . wp_json_encode( $payload ) . '</script>';

		/**
		 * Filter the final rendered HTML (breadcrumbs markup + JSON-LD).
		 *
		 * @param string $html
		 */
		return (string) apply_filters( 'keystone/breadcrumbs/html', $html . "\n" . $script );
	}
}