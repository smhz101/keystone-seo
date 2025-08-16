<?php
namespace Keystone\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Safe wrapper around WooCommerce to read product info without fataling when WC is absent.
 */
class ProductDataProvider {

	/** Whether current request is a single product page (if WooCommerce exists). */
	public function is_product_context() {
		return function_exists( 'is_product' ) && is_product();
	}

	/**
	 * Extract product info for schema.
	 *
	 * @return array<string,mixed> Empty array if not available.
	 */
	public function info() {
		if ( ! function_exists( 'wc_get_product' ) || ! $this->is_product_context() ) {
			return array();
		}
		$id = get_queried_object_id();
		$wc = wc_get_product( $id );
		if ( ! $wc ) { return array(); }

		$images = array();
		$main = get_the_post_thumbnail_url( $id, 'full' );
		if ( $main ) { $images[] = $main; }
		$gallery = $wc->get_gallery_image_ids();
		if ( is_array( $gallery ) ) {
			foreach ( $gallery as $img_id ) {
				$url = wp_get_attachment_image_url( $img_id, 'full' );
				if ( $url ) { $images[] = $url; }
			}
		}

		$stock = method_exists( $wc, 'get_stock_status' ) ? $wc->get_stock_status() : '';
		$availability = $this->availability_uri( $stock );

		$brand = '';
		// Common attribute keys for brand: 'pa_brand', 'brand'.
		$brand_keys = array( 'pa_brand', 'brand' );
		foreach ( $brand_keys as $k ) {
			$val = $wc->get_attribute( $k );
			if ( $val ) { $brand = is_array( $val ) ? implode( ', ', $val ) : (string) $val; break; }
		}

		return array(
			'id'          => $id,
			'name'        => $wc->get_name(),
			'description' => wp_strip_all_tags( $wc->get_short_description() ?: $wc->get_description() ),
			'sku'         => (string) $wc->get_sku(),
			'price'       => $wc->get_price(),
			'currency'    => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'availability'=> $availability,
			'brand'       => $brand,
			'images'      => array_values( array_unique( $images ) ),
		);
	}

	protected function availability_uri( $status ) {
		$map = array(
			'instock'    => 'https://schema.org/InStock',
			'outofstock' => 'https://schema.org/OutOfStock',
			'onbackorder'=> 'https://schema.org/PreOrder',
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : 'https://schema.org/Discontinued';
	}
}