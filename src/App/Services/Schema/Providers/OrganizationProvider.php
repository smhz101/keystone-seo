<?php
namespace Keystone\App\Services\Schema\Providers;

use Keystone\App\Services\Schema\Contracts\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Outputs Organization (or LocalBusiness if you extend later via filters).
 * Uses settings if provided; falls back to blog name and site icon.
 */
class OrganizationProvider implements ProviderInterface {

	public function nodes() {
		$site_url = home_url( '/' );
		$org_id   = trailingslashit( $site_url ) . '#organization';

		$logo = '';
		$icon_id = get_option( 'site_icon' );
		if ( $icon_id ) {
			$logo = wp_get_attachment_image_url( $icon_id, 'full' );
		}

		$node = array(
			'@type' => 'Organization',
			'@id'   => $org_id,
			'name'  => get_bloginfo( 'name' ),
		);

		if ( $logo ) {
			$node['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			);
		}

		/**
		 * Allow extensions to enrich Organization (same @id).
		 * Example: address, sameAs, contactPoint, etc.
		 */
		$node = apply_filters( 'keystone/schema/organization', $node );

		return array( $node );
	}
}