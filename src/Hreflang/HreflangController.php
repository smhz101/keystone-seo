<?php
namespace Keystone\Hreflang;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Outputs <link rel="alternate" hreflang="..."> in <head>.
 * Adapters (WPML/Polylang) hook into 'keystone/hreflang/alternates'.
 */
class HreflangController {

	/** Collect alternates and echo link tags. */
	public function output() {
		if ( ! is_singular() && ! is_home() && ! is_front_page() ) { return; }

		$alts = apply_filters( 'keystone/hreflang/alternates', array() );
		if ( empty( $alts ) || ! is_array( $alts ) ) { return; }

		// Deduplicate by hreflang/href.
		$seen = array();
		$tags = array();

		foreach ( $alts as $alt ) {
			$hl = isset( $alt['hreflang'] ) ? strtolower( (string) $alt['hreflang'] ) : '';
			$hr = isset( $alt['href'] ) ? esc_url( $alt['href'] ) : '';
			if ( ! $hl || ! $hr ) { continue; }
			$key = $hl . '|' . $hr;
			if ( isset( $seen[ $key ] ) ) { continue; }
			$seen[ $key ] = true;
			$tags[] = '<link rel="alternate" hreflang="' . esc_attr( $hl ) . '" href="' . esc_url( $hr ) . '">';
		}

		if ( $tags ) {
			echo implode( "\n", $tags ) . "\n"; // phpcs:ignore
		}
	}
}