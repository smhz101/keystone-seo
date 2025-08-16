<?php
namespace Keystone\Schema\Providers;

use Keystone\Schema\Contracts\SchemaProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Organization node for the site/brand.
 * Settings keys used: org_name, org_logo, org_url, same_as (array).
 */
class OrganizationProvider implements SchemaProviderInterface {
	public function nodes( $context ) {
		$s = isset( $context['settings'] ) ? $context['settings'] : array();

		$name = isset( $s['org_name'] ) && $s['org_name'] ? $s['org_name'] : get_bloginfo( 'name' );
		$logo = isset( $s['org_logo'] ) ? esc_url_raw( $s['org_logo'] ) : '';
		$url  = isset( $s['org_url'] ) && $s['org_url'] ? esc_url_raw( $s['org_url'] ) : home_url( '/' );
		$same = isset( $s['same_as'] ) && is_array( $s['same_as'] ) ? array_values( array_filter( $s['same_as'] ) ) : array();

		$node = array(
			'@type' => 'Organization',
			'@id'   => trailingslashit( $url ) . '#organization',
			'name'  => $name,
			'url'   => $url,
		);

		if ( $logo ) {
			$node['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			);
		}
		if ( ! empty( $same ) ) {
			$node['sameAs'] = array_map( 'esc_url_raw', $same );
		}

		return array( $node );
	}
}
