<?php
namespace Keystone\Importers\Contracts;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Contract for all third-party metadata importers.
 */
interface ImporterInterface {
	/**
	 * Human identifier, e.g. 'yoast', 'rankmath', 'aioseo'.
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * Human label for UI.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Quick detection if plugin's data is present on this site.
	 *
	 * @return bool
	 */
	public function detected();

	/**
	 * Count posts that have importable data.
	 *
	 * @return int
	 */
	public function count();

	/**
	 * Import a batch of items; returns [ imported => int, skipped => int ].
	 *
	 * @param int $limit  Batch size (e.g., 100).
	 * @param int $offset Offset for pagination.
	 * @return array
	 */
	public function import( $limit = 100, $offset = 0 );
}