<?php
namespace Keystone\Hreflang\Adapters;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Injects Polylang alternates into keystone/hreflang/alternates.
 */
class PolylangAdapter {
	public function filter( $alts ) {
		if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_get_post' ) ) {
			return $alts;
		}
		if ( ! is_singular() ) { return $alts; }

		$post_id = get_queried_object_id();
		$langs   = pll_languages_list();

		foreach ( (array) $langs as $code ) {
			$tr_id = pll_get_post( $post_id, $code );
			if ( $tr_id ) {
				$alts[] = array(
					'hreflang' => strtolower( substr( $code, 0, 2 ) ),
					'href'     => get_permalink( $tr_id ),
				);
			}
		}
		return $alts;
	}
}
