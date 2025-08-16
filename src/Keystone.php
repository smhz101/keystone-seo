<?php
namespace Keystone;

use Keystone\Support\Container;
use Keystone\Support\HookRegistrar;
use Keystone\Support\Paths;
use Keystone\Support\Logger;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;

use Keystone\Admin\AdminMenu;
use Keystone\Admin\SettingsPage;

use Keystone\Http\Rest\SettingsController;

use Keystone\Meta\MetaService;
use Keystone\Meta\Templates;
use Keystone\Meta\Providers\PostTokenProvider;

use Keystone\Robots\RobotsController;

use Keystone\Sitemap\SitemapProvider;
use Keystone\Sitemap\SitemapController;

use Keystone\Redirects\RedirectRepository;
use Keystone\Redirects\RedirectManager;
use Keystone\Redirects\Admin\RedirectsPage;
use Keystone\Redirects\Rest\RedirectsController as RedirectsRest;

use Keystone\IndexNow\IndexNowService;

use Keystone\Schema\GraphBuilder;
use Keystone\Schema\Providers\OrganizationProvider;
use Keystone\Schema\Providers\WebSiteProvider;
use Keystone\Schema\Providers\ArticleProvider;
use Keystone\Schema\Providers\ProductProvider;

use Keystone\Integrations\WooCommerce\ProductDataProvider;

use Keystone\Hreflang\HreflangController;
use Keystone\Hreflang\Adapters\WPMLAdapter;
use Keystone\Hreflang\Adapters\PolylangAdapter;

use Keystone\Monitor\NotFoundRepository;
use Keystone\Monitor\NotFoundMonitor;

use Keystone\Cli\Commands;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Keystone {
	protected $c;
	protected $hooks;

	public function __construct() {
		$this->c     = new Container();
		$this->hooks = new HookRegistrar();

		$this->c->bind( 'paths', new Paths() );
		$this->c->bind( 'logger', new Logger() );
		$this->c->bind( 'caps', new Capabilities() );
		$this->c->bind( 'nonce', new Nonce() );
		$this->c->bind( 'settings', function () { return get_option( 'keystone_seo_settings', array() ); } );

		// Meta.
		$this->c->bind( 'meta.templates', new Templates() );
		$this->c->bind( 'meta', function ( $c ) {
			$svc = new MetaService( $c->make( 'settings' ), $c->make( 'meta.templates' ) );
			$svc->add_provider( new PostTokenProvider( '—' ) );
			return $svc;
		} );

		// Robots.
		$this->c->bind( 'robots', new RobotsController() );

		// Sitemaps.
		$this->c->bind( 'sitemap.provider', new SitemapProvider() );
		$this->c->bind( 'sitemap.controller', function ( $c ) { return new SitemapController( $c->make( 'sitemap.provider' ) ); } );

		// Redirects.
		$this->c->bind( 'redirect.repo', new RedirectRepository() );
		$this->c->bind( 'redirect.manager', function ( $c ) { return new RedirectManager( $c->make( 'redirect.repo' ) ); } );
		$this->c->bind( 'redirects.page', function ( $c ) { return new RedirectsPage( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'redirect.repo' ) ); } );
		$this->c->bind( 'redirects.rest', function ( $c ) { return new RedirectsRest( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'redirect.repo' ) ); } );

		// IndexNow.
		$this->c->bind( 'indexnow', function ( $c ) { return new IndexNowService( $c->make( 'settings' ) ); } );

		// Schema.
		$this->c->bind( 'schema.graph', function ( $c ) {
			$g = new GraphBuilder( $c->make( 'settings' ) );
			$g->add_provider( new OrganizationProvider() );
			$g->add_provider( new WebSiteProvider() );
			$g->add_provider( new ArticleProvider() );
			$g->add_provider( new ProductProvider( new ProductDataProvider() ) );
			return $g;
		} );

		// Hreflang + adapters.
		$this->c->bind( 'hreflang', new HreflangController() );
		add_filter( 'keystone/hreflang/alternates', array( new WPMLAdapter(), 'filter' ), 10, 1 );
		add_filter( 'keystone/hreflang/alternates', array( new PolylangAdapter(), 'filter' ), 11, 1 );

		// 404 Monitor.
		$this->c->bind( 'nf.repo', new NotFoundRepository() );
		$this->c->bind( 'nf.monitor', function ( $c ) { return new NotFoundMonitor( $c->make( 'nf.repo' ) ); } );

		// Admin.
		$this->c->bind( 'admin.settings', function ( $c ) { return new SettingsPage( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );
		$this->c->bind( 'admin.menu', function ( $c ) { return new AdminMenu( $c->make( 'caps' ), $c->make( 'admin.settings' ) ); } );

		// REST.
		$this->c->bind( 'rest.settings', function ( $c ) { return new SettingsController( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );

		// CLI.
		$this->c->bind( 'cli.commands', function ( $c ) { return new Commands( $c->make( 'sitemap.provider' ), $c->make( 'redirect.repo' ) ); } );
	}

	public function activate() {
		$this->c->make( 'redirect.repo' )->migrate();
		$this->c->make( 'nf.repo' )->migrate();
		$this->c->make( 'sitemap.controller' )->add_rewrite_rules();
	}

	public function run() {
		// Admin menus.
		$this->hooks->action( 'admin_menu', $this->c->make( 'admin.menu' ), 'register_menu' );
		$this->hooks->action( 'admin_menu', $this->c->make( 'redirects.page' ), 'add_menu' );

		// REST.
		$this->hooks->action( 'rest_api_init', $this->c->make( 'rest.settings' ), 'register_routes' );
		$this->hooks->action( 'rest_api_init', $this->c->make( 'redirects.rest' ), 'register_routes' );

		// Meta + Schema + Hreflang.
		$this->hooks->filter( 'document_title_parts', $this->c->make( 'meta' ), 'filter_document_title', 10, 1 );
		$this->hooks->action( 'wp_head', $this->c->make( 'meta' ), 'output_head_tags', 1, 0 );
		$this->hooks->action( 'wp_head', $this->c->make( 'schema.graph' ), 'output', 2, 0 );
		$this->hooks->action( 'wp_head', $this->c->make( 'hreflang' ), 'output', 3, 0 );

		// Robots + Sitemaps.
		$this->hooks->filter( 'robots_txt', $this->c->make( 'robots' ), 'filter_robots', 10, 2 );
		$this->hooks->action( 'init', $this->c->make( 'sitemap.controller' ), 'add_rewrite_rules' );
		$this->hooks->action( 'template_redirect', $this->c->make( 'sitemap.controller' ), 'maybe_render' );

		// Redirects + 404 monitor.
		$this->hooks->action( 'template_redirect', $this->c->make( 'redirect.manager' ), 'maybe_redirect', 0, 0 );
		$this->hooks->action( 'template_redirect', $this->c->make( 'nf.monitor' ), 'maybe_log', 9999, 0 );

		// IndexNow.
		$this->hooks->action( 'transition_post_status', $this->c->make( 'indexnow' ), 'on_transition_post_status', 10, 3 );
		$this->hooks->action( 'deleted_post', $this->c->make( 'indexnow' ), 'on_deleted_post', 10, 1 );

		// CLI.
		$this->c->make( 'cli.commands' )->register();
	}
}