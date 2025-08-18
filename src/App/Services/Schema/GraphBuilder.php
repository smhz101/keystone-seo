<?php
namespace Keystone\App\Services\Schema;

use Keystone\App\Services\Schema\Contracts\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Collects schema nodes from registered providers and outputs a single JSON-LD graph.
 *
 * - Providers are added via add_provider().
 * - Nodes are merged by @id (later nodes overwrite earlier ones).
 * - Output hooked via wp_head by Keystone\Keystone wiring.
 */
class GraphBuilder {
	/** @var array */
	protected $settings = array();

	/** @var ProviderInterface[] */
	protected $providers = array();

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/** Register a provider instance. */
	public function add_provider( ProviderInterface $p ) {
		$this->providers[] = $p;
	}

	/** Aggregate providers and echo a single <script type="application/ld+json"> */
	public function output() {
		$nodes = array();

		foreach ( $this->providers as $p ) {
			$list = (array) $p->nodes();
			foreach ( $list as $node ) {
				if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
					continue;
				}
				$id = isset( $node['@id'] ) ? (string) $node['@id'] : '';
				if ( $id ) {
					$nodes[ $id ] = isset( $nodes[ $id ] )
						? array_merge( $nodes[ $id ], $node )
						: $node;
				} else {
					$nodes[] = $node;
				}
			}
		}

		// Permit filtering final nodes.
		$nodes = apply_filters( 'keystone/schema/nodes', array_values( $nodes ), $this->settings );

		if ( empty( $nodes ) ) { return; }

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $nodes ),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $payload ) . '</script>' . "\n"; // phpcs:ignore
	}
}