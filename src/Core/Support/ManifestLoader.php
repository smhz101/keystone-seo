<?php
namespace Keystone\Core\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Reads Vite's manifest.json to enqueue hashed assets in WP Admin.
 * See: https://vite.dev/guide/backend-integration
 */
class ManifestLoader {

	/** @var string Absolute path to manifest.json */
	protected $manifest;

	public function __construct( $manifest_path ) {
		$this->manifest = (string) $manifest_path;
	}

	/**
	 * Resolve entry to file paths (js/css).
	 *
	 * @param string $entry e.g. "admin-app/main.ts"
	 * @return array{js:string[],css:string[]}
	 */
	public function entry( $entry ) {
		if ( ! file_exists( $this->manifest ) ) {
			return array( 'js' => array(), 'css' => array() );
		}
		$json = json_decode( (string) file_get_contents( $this->manifest ), true ); // phpcs:ignore
		if ( ! is_array( $json ) || ! isset( $json[ $entry ] ) ) {
			return array( 'js' => array(), 'css' => array() );
		}
		$chunk = $json[ $entry ];
		$js    = isset( $chunk['file'] ) ? array( $chunk['file'] ) : array();
		$css   = isset( $chunk['css'] ) ? (array) $chunk['css'] : array();
		return array( 'js' => $js, 'css' => $css );
	}
}