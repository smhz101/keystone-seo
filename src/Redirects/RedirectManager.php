<?php
namespace Keystone\Redirects;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Applies redirect rules during request lifecycle.
 *
 * @since 0.1.0
 */
class RedirectManager {
	/** @var RedirectRepository */
	protected $repo;

	public function __construct( RedirectRepository $repo ) {
		$this->repo = $repo;
	}

	/**
	 * Early hook to capture request and apply redirects.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = parse_url( $uri, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			$path = '/';
		}

		$rule = $this->repo->find_by_path( $path );
		if ( ! $rule ) {
			return;
		}

		$this->repo->bump_hits( (int) $rule['id'] );

		$status = (int) $rule['status'];
		$target = $rule['target'];

		// Expand backreferences if regex.
		if ( (int) $rule['regex'] === 1 ) {
			$pattern = '#' . str_replace( '#', '\#', $rule['source'] ) . '#';
			$target  = preg_replace( $pattern, $target, $path );
		}

		wp_safe_redirect( $target, $status );
		exit;
	}
}