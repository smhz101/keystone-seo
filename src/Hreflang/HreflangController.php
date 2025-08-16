<?php
namespace Keystone\Hreflang;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Minimal hreflang output.
 * - Core-only fallback: prints only current locale (x-default) and current page URL.
 * - Exposes filter 'keystone/hreflang/alternates' for WPML/Polylang adapters to inject alternates.
 */
class HreflangController {
	/**
	 * Print <link rel="alternate" hreflang=".."> tags in head.
	 *
	 * @return void
	 */
	public function output() {
		$current = array(
			'hreflang' => get_locale() ? strtolower( substr( get_locale(), 0, 2 ) ) : 'en',
			'href'     => is_singular() ? get_permalink() : home_url( add_query_arg( null, null ) ),
		);

		$alts = apply_filters( 'keystone/hreflang/alternates', array( $current ) );

		// Always add x-default pointing to homepage.
		$alts[] = array( 'hreflang' => 'x-default', 'href' => home_url( '/' ) );

		$printed = array();
		foreach ( $alts as $a ) {
			$code = isset( $a['hreflang'] ) ? $a['hreflang'] : '';
			$href = isset( $a['href'] ) ? $a['href'] : '';
			$key  = $code . '|' . $href;
			if ( $code && $href && ! isset( $printed[ $key ] ) ) {
				echo '<link rel="alternate" hreflang="' . esc_attr( $code ) . '" href="' . esc_url( $href ) . '">' . "\n";
				$printed[ $key ] = true;
			}
		}
	}
}