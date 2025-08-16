<?php
namespace Keystone\Hreflang\Adapters;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Injects WPML alternates into keystone/hreflang/alternates.
 * Only active if WPML functions exist.
 */
class WPMLAdapter {
	public function filter( $alts ) {
		if ( ! function_exists( 'wpml_get_active_languages' ) || ! function_exists( 'icl_object_id' ) ) {
			return $alts;
		}
		if ( ! is_singular() ) { return $alts; }

		$post_id = get_queried_object_id();
		$langs   = wpml_get_active_languages();

		if ( empty( $langs ) ) { return $alts; }

		foreach ( $langs as $code => $info ) {
			$tr_id = icl_object_id( $post_id, get_post_type( $post_id ), false, $code );
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