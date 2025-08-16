<?php
namespace Keystone\Schema\Contracts;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Contract for schema providers contributing nodes to the JSON-LD graph.
 */
interface SchemaProviderInterface {
	/**
	 * Return an array of one or more schema nodes (associative arrays) or empty array.
	 *
	 * @param array $context e.g., ['post_id'=>.., 'is_singular'=>..].
	 * @return array
	 */
	public function nodes( $context );
}
