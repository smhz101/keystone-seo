<?php
namespace Keystone\App\Services\Social;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Social\SocialService;
use Keystone\Social\ImageGenerator;

class SocialServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'social', function( $c ) { return new SocialService( $c->make( 'settings' ), $c->make( 'meta.repo' ) ); } );
		$c->bind( 'og.gen', function( $c ) { return new ImageGenerator( $c->make( 'settings' ) ); } );
	}
	public function hooks( $hooks, $c ) {
		$hooks->action( 'wp_head', $c->make( 'social' ), 'output', 4, 0 );
		$hooks->action( 'init', $c->make( 'og.gen' ), 'add_rewrite_rules' );
		$hooks->action( 'template_redirect', $c->make( 'og.gen' ), 'maybe_render', 0, 0 );
	}
}
