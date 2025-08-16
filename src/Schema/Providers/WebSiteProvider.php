<?php
namespace Keystone\Schema\Providers;

use Keystone\Schema\Contracts\SchemaProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WebSite node (+ SearchAction).
 * Settings keys used: site_search_url (e.g., /?s={search_term_string})
 */
class WebSiteProvider implements SchemaProviderInterface {
	public function nodes( $context ) {
		$home = home_url( '/' );

		$search_url = isset( $context['settings']['site_search_url'] ) && $context['settings']['site_search_url']
			? esc_url_raw( $context['settings']['site_search_url'] )
			: add_query_arg( 's', '{search_term_string}', home_url( '/' ) );

		$site = array(
			'@type' => 'WebSite',
			'@id'   => trailingslashit( $home ) . '#website',
			'url'   => $home,
			'name'  => get_bloginfo( 'name' ),
			'potentialAction' => array(
				array(
					'@type'       => 'SearchAction',
					'target'      => $search_url,
					'query-input' => 'required name=search_term_string',
				),
			),
		);

		return array( $site );
	}
}
