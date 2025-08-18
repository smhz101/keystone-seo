<?php
namespace Keystone\App\Services\Schema\Providers;

use Keystone\App\Services\Schema\Contracts\ProviderInterface;
use Keystone\Integrations\WooCommerce\ProductDataProvider;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WooCommerce Product schema on single product pages.
 * https://developers.google.com/search/docs/appearance/structured-data/product
 */
class ProductProvider implements ProviderInterface {

	protected $data;

	public function __construct( ProductDataProvider $data ) {
		$this->data = $data;
	}

	public function nodes() {
		if ( ! $this->data->is_product_context() ) { return array(); }

		$p = $this->data->info();
		if ( empty( $p ) ) { return array(); }

		$perma = get_permalink( $p['id'] );
		$id    = trailingslashit( $perma ) . '#product';

		$offer = array(
			'@type'         => 'Offer',
			'price'         => isset( $p['price'] ) ? (string) $p['price'] : '',
			'priceCurrency' => isset( $p['currency'] ) ? (string) $p['currency'] : '',
			'availability'  => isset( $p['availability'] ) ? (string) $p['availability'] : '',
			'url'           => $perma,
		);

		$node = array(
			'@type'        => 'Product',
			'@id'          => $id,
			'name'         => $p['name'],
			'description'  => $p['description'],
			'sku'          => $p['sku'],
			'url'          => $perma,
			'image'        => $p['images'], // array of URLs
			'offers'       => $offer,
		);

		if ( ! empty( $p['brand'] ) ) {
			$node['brand'] = array( '@type' => 'Brand', 'name' => $p['brand'] );
		}

		$node = apply_filters( 'keystone/schema/product', $node, $p );

		return array( $node );
	}
}