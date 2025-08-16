<?php
namespace Keystone\Meta\Providers;

use Keystone\Meta\Contracts\TokenProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Token provider for common post/page tokens.
 *
 * Available tokens:
 *  - %title%       : post/page title or site title on non-singular.
 *  - %sitename%    : blog name.
 *  - %tagline%     : blog description.
 *  - %category%    : first category name (posts).
 *  - %sep%         : separator (defaults to "—").
 *  - %date%        : post date (Y-m-d).
 */
class PostTokenProvider implements TokenProviderInterface {
	/** @var string */
	protected $sep;

	public function __construct( $sep = '—' ) {
		$this->sep = $sep;
	}

	/**
	 * @param array $context Expecting optional ['post_id' => int].
	 * @return array<string,string>
	 */
	public function tokens( $context ) {
		$post_id  = isset( $context['post_id'] ) ? absint( $context['post_id'] ) : 0;
		$is_singular = $post_id ? true : is_singular();

		$title = $is_singular ? get_the_title( $post_id ?: get_queried_object_id() ) : get_bloginfo( 'name' );

		$cat = '';
		if ( $post_id ) {
			$terms = get_the_terms( $post_id, 'category' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$cat = $terms[0]->name;
			}
		}

		$date = $post_id ? get_the_date( 'Y-m-d', $post_id ) : '';

		return array(
			'%title%'    => (string) $title,
			'%sitename%' => (string) get_bloginfo( 'name' ),
			'%tagline%'  => (string) get_bloginfo( 'description', 'display' ),
			'%category%' => (string) $cat,
			'%date%'     => (string) $date,
			'%sep%'      => (string) $this->sep,
		);
	}
}