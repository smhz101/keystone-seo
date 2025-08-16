<?php
namespace Keystone\Schema;

use Keystone\Schema\Contracts\SchemaProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Collects schema nodes from providers and prints a single JSON-LD script.
 */
class GraphBuilder {
	/** @var SchemaProviderInterface[] */
	protected $providers = array();

	/** @var array */
	protected $settings = array();

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Register a provider.
	 *
	 * @param SchemaProviderInterface $p Provider.
	 * @return void
	 */
	public function add_provider( SchemaProviderInterface $p ) {
		$this->providers[] = $p;
	}

	/**
	 * Action: output consolidated JSON-LD in <head>.
	 *
	 * @return void
	 */
	public function output() {
		$context = array(
			'post_id'     => is_singular() ? get_queried_object_id() : 0,
			'is_singular' => is_singular(),
			'is_home'     => is_front_page(),
			'settings'    => $this->settings,
		);

		$graph = array();
		foreach ( $this->providers as $p ) {
			$nodes = $p->nodes( $context );
			if ( ! empty( $nodes ) ) {
				$graph = array_merge( $graph, $nodes );
			}
		}

		if ( empty( $graph ) ) {
			return;
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $payload ) . '</script>' . "\n";
	}
}
