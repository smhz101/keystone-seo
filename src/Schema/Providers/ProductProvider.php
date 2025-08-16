<?php
namespace Keystone\Schema\Providers;

use Keystone\Schema\Contracts\SchemaProviderInterface;
use Keystone\Integrations\WooCommerce\ProductDataProvider;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Product schema for WooCommerce single product pages.
 */
class ProductProvider implements SchemaProviderInterface {
	/** @var ProductDataProvider */
	protected $woo;

	public function __construct( ProductDataProvider $woo ) {
		$this->woo = $woo;
	}

	public function nodes( $context ) {
		if ( empty( $context['is_singular'] ) || 'product' !== get_post_type( $context['post_id'] ) ) {
			return array();
		}
		if ( ! $this->woo->available() ) {
			return array();
		}

		$d = $this->woo->product_data( $context['post_id'] );
		if ( empty( $d ) ) { return array(); }

		$offers = array(
			'@type'         => 'Offer',
			'price'         => (string) $d['price'],
			'priceCurrency' => $d['currency'],
			'url'           => $d['url'],
			'availability'  => $d['in_stock'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
		);

		$node = array(
			'@type' => 'Product',
			'@id'   => trailingslashit( $d['url'] ) . '#product',
			'name'  => $d['name'],
			'url'   => $d['url'],
			'image' => $d['image'] ? array( $d['image'] ) : array(),
			'sku'   => $d['sku'],
			'offers'=> $offers,
		);

		if ( ! empty( $d['brand'] ) ) {
			$node['brand'] = array( '@type' => 'Brand', 'name' => $d['brand'] );
		}
		if ( ! empty( $d['gtin'] ) ) {
			$node['gtin13'] = $d['gtin'];
		}
		if ( $d['rating'] ) {
			$node['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => (string) $d['rating'],
				'reviewCount' => (int) $d['reviewCount'],
			);
		}

		return array( $node );
	}
}