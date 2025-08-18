<?php
namespace Keystone\App\Services\IndexNow;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\App\Services\IndexNow\IndexNowService;

/**
 * Exposes IndexNow key at /{key}.txt using a dynamic route if we cannot write to ABSPATH.
 * - Adds rewrite for ^([a-f0-9]{32})\.txt$
 * - On match, outputs the key if it equals saved key.
 */
class KeyRoute {
	protected $svc;

	public function __construct( IndexNowService $svc ) {
		$this->svc = $svc;
	}

	public function add_rewrite_rules() {
		add_rewrite_rule( '^([a-f0-9]{32})\.txt$', 'index.php?keystone_ix_key=$matches[1]', 'top' );
		add_rewrite_tag( '%keystone_ix_key%', '([a-f0-9]{32})' );
	}

	public function maybe_render_key() {
		$q = (string) get_query_var( 'keystone_ix_key' );
		if ( ! $q ) { return; }

		$key = $this->svc->ensure_key();

		// Prefer static file when possible; otherwise dynamic.
		$file = trailingslashit( ABSPATH ) . $key . '.txt';
		if ( file_exists( $file ) ) {
			// Let the webserver serve it; but if we got here, just print it.
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo (string) file_get_contents( $file ); // phpcs:ignore
			exit;
		}

		// Dynamic fallback.
		if ( strtolower( $q ) === strtolower( $key ) ) {
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo $key; // phpcs:ignore
			exit;
		}

		status_header( 404 );
		exit;
	}
}