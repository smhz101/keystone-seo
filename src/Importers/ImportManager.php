<?php
namespace Keystone\Importers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Importers\Contracts\ImporterInterface;

/**
 * Central registry and runner for all importers.
 */
class ImportManager {

	/** @var array<string, ImporterInterface> */
	protected $importers = array();

	/**
	 * Register an importer instance.
	 *
	 * @param ImporterInterface $importer
	 * @return void
	 */
	public function register( ImporterInterface $importer ) {
		$this->importers[ $importer->slug() ] = $importer;
	}

	/**
	 * Get a single importer.
	 *
	 * @param string $slug
	 * @return ImporterInterface|null
	 */
	public function get( $slug ) {
		return isset( $this->importers[ $slug ] ) ? $this->importers[ $slug ] : null;
	}

	/**
	 * All importers.
	 *
	 * @return ImporterInterface[]
	 */
	public function all() {
		return array_values( $this->importers );
	}

	/**
	 * Run importer by slug.
	 *
	 * @param string $slug
	 * @param int    $limit
	 * @param int    $offset
	 * @return array
	 */
	public function run( $slug, $limit = 200, $offset = 0 ) {
		$imp = $this->get( $slug );
		if ( ! $imp ) {
			return array( 'imported' => 0, 'skipped' => 0 );
		}
		return $imp->import( $limit, $offset );
	}
}