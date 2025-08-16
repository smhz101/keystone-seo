<?php
namespace Keystone\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Lightweight data access for Woo products without hard dependency.
 */
class ProductDataProvider {
	/**
	 * Check WooCommerce availability.
	 *
	 * @return bool
	 */
	public function available() {
		return class_exists( '\WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Get product data for schema.
	 *
	 * @param int $post_id Product post ID.
	 * @return array<string,mixed>
	 */
	public function product_data( $post_id ) {
		$p = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
		if ( ! $p ) { return array(); }

		$price = $p->get_price();
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'woocommerce_currency', 'USD' );

		return array(
			'name'        => $p->get_name(),
			'price'       => $price,
			'currency'    => $currency,
			'sku'         => $p->get_sku(),
			'in_stock'    => $p->is_in_stock(),
			'rating'      => method_exists( $p, 'get_average_rating' ) ? (float) $p->get_average_rating() : null,
			'reviewCount' => method_exists( $p, 'get_review_count' ) ? (int) $p->get_review_count() : null,
			'brand'       => get_post_meta( $post_id, '_brand', true ), // can be adapted later
			'gtin'        => get_post_meta( $post_id, '_gtin', true ),  // can be mapped later
			'image'       => get_the_post_thumbnail_url( $post_id, 'full' ),
			'url'         => get_permalink( $post_id ),
		);
	}
}
