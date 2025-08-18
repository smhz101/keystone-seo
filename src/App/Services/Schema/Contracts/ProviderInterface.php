<?php
namespace Keystone\App\Services\Schema\Contracts;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Contract for schema providers that contribute nodes to the JSON-LD graph.
 *
 * Providers SHOULD:
 * - Return an array of associative arrays (each a JSON-LD node).
 * - Include stable @id values so nodes can be merged/deduped.
 */
interface ProviderInterface {
	/**
	 * Return JSON-LD nodes to add to the graph for the current request.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function nodes();
}