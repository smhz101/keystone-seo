<?php
namespace Keystone\App\Services\Schema\Providers;

use Keystone\App\Services\Schema\Contracts\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Outputs WebSite with SearchAction (sitelinks search box).
 * https://developers.google.com/search/docs/appearance/structured-data/sitelinks-searchbox
 */
class WebSiteProvider implements ProviderInterface {

	public function nodes() {
		$site_url = home_url( '/' );
		$web_id   = trailingslashit( $site_url ) . '#website';
		$org_id   = trailingslashit( $site_url ) . '#organization';

		$node = array(
			'@type'    => 'WebSite',
			'@id'      => $web_id,
			'url'      => $site_url,
			'name'     => get_bloginfo( 'name' ),
			'publisher'=> array( '@id' => $org_id ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => add_query_arg( 's', '{search_term_string}', home_url( '/' ) ),
				'query-input' => 'required name=search_term_string',
			),
		);

		return array( $node );
	}
}