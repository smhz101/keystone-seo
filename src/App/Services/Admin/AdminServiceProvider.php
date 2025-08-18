<?php
namespace Keystone\App\Services\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\App\Admin\AdminMenu;
use Keystone\App\Admin\SettingsPage;
use Keystone\Robots\Admin\RobotsPage;
use Keystone\Redirects\Admin\RedirectsPage;
use Keystone\Monitor\Admin\NotFoundPage;

class AdminServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'admin.settings', function ( $c ) { return new SettingsPage( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );
		$c->bind( 'admin.menu',     function ( $c ) { return new AdminMenu( $c->make( 'caps' ), $c->make( 'admin.settings' ) ); } );
		$c->bind( 'robots.page',    function ( $c ) { return new RobotsPage( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );
		$c->bind( 'redirects.page', function ( $c ) { return new RedirectsPage( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'redirect.repo' ) ); } );
		$c->bind( 'nf.page',        function ( $c ) { return new NotFoundPage( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'nf.repo' ) ); } );
	}
	public function hooks( $hooks, $c ) {
		$hooks->action( 'admin_menu', $c->make( 'admin.menu' ), 'register_menu' );
		$hooks->action( 'admin_menu', $c->make( 'robots.page' ), 'add_menu' );
		$hooks->action( 'admin_menu', $c->make( 'redirects.page' ), 'add_menu' );
		$hooks->action( 'admin_menu', $c->make( 'nf.page' ), 'add_menu' );
	}
}
