<?php
namespace Keystone;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Foundation\Application;
use Keystone\Support\Container;
use Keystone\Core\Support\HookBus;

// Providers we’ll register now
use Keystone\App\Services\Admin\AdminServiceProvider;
use Keystone\App\Services\IndexNow\IndexNowServiceProvider;
// Later: SitemapServiceProvider, SchemaServiceProvider, RestServiceProvider, AdminServiceProvider, etc.

class Keystone {
	protected $app;

	public function __construct() {
		$c = new Container();

		// Base singletons (you already have these)
		$c->bind( 'paths', new \Keystone\Support\Paths() );
		$c->bind( 'logger', new \Keystone\Support\Logger() );
		$c->bind( 'caps', new \Keystone\Security\Capabilities() );
		$c->bind( 'nonce', new \Keystone\Security\Nonce() );
		$c->bind( 'settings', function(){ return get_option( 'keystone_seo_settings', array() ); } );

		// Repos used by multiple modules
		$c->bind( 'redirect.repo', new \Keystone\Redirects\RedirectRepository() );
		$c->bind( 'nf.repo', new \Keystone\Monitor\NotFoundRepository() );

		$this->app = new Application( $c, new HookBus() );

		// Add providers (start with IndexNow; we’ll migrate others next)
		$this->app->add( new AdminServiceProvider() );
		$this->app->add( new IndexNowServiceProvider() );
	}

	public function activate() {
		$this->app->register();
		$this->app->container()->make( 'indexnow' )->maybe_write_key_file();
		update_option( 'keystone_flush_rewrites', 1 );
	}

	public function run() {
		$this->app->register();
		$this->app->boot();
		add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 999, 0 );
	}

	public function maybe_flush_rewrites() {
		if ( get_option( 'keystone_flush_rewrites' ) ) {
			flush_rewrite_rules();
			delete_option( 'keystone_flush_rewrites' );
		}
	}
}
