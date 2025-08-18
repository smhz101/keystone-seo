<?php
namespace Keystone\App\Services\IndexNow;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\App\Services\IndexNow\KeyRoute;
use Keystone\App\Services\IndexNow\IndexNowService;

class IndexNowServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'indexnow', function ( $c ) {
			return new IndexNowService( $c->make( 'settings' ) );
		} );
		$c->bind( 'indexnow.keyroute', function ( $c ) {
			return new KeyRoute( $c->make( 'indexnow' ) );
		} );
	}

	public function hooks( $hooks, $c ) {
		$hooks->action( 'transition_post_status', $c->make( 'indexnow' ), 'on_transition_post_status', 10, 3 );
		$hooks->action( 'deleted_post',          $c->make( 'indexnow' ), 'on_deleted_post', 10, 1 );
		$hooks->action( 'init',              $c->make( 'indexnow.keyroute' ), 'add_rewrite_rules', 10, 0 );
		$hooks->action( 'template_redirect', $c->make( 'indexnow.keyroute' ), 'maybe_render_key', 0, 0 );
	}
}