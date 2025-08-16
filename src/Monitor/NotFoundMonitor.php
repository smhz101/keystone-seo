<?php
namespace Keystone\Monitor;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Hooks into template_redirect; records 404s.
 */
class NotFoundMonitor {
	protected $repo;

	public function __construct( NotFoundRepository $repo ) {
		$this->repo = $repo;
	}

	public function maybe_log() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) { return; }
		if ( ! is_404() ) { return; }

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = parse_url( $uri, PHP_URL_PATH );
		$ref  = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';

		if ( ! is_string( $path ) ) { $path = '/'; }
		$this->repo->log( $path, esc_url_raw( $ref ) );
	}
}