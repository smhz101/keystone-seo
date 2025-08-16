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

use Keystone\Robots\RobotsController;

use Keystone\Sitemap\SitemapProvider;
use Keystone\Sitemap\SitemapController;

use Keystone\Redirects\RedirectRepository;
use Keystone\Redirects\RedirectManager;

use Keystone\Cli\Commands;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Keystone SEO main orchestrator.
 *
 * @since 0.1.0
 */
class Keystone {
	/** @var Container */
	protected $c;

	/** @var HookRegistrar */
	protected $hooks;

	public function __construct() {
		$this->c     = new Container();
		$this->hooks = new HookRegistrar();

		// Bind core services.
		$this->c->bind( 'paths', new Paths() );
		$this->c->bind( 'logger', new Logger() );
		$this->c->bind( 'caps', new Capabilities() );
		$this->c->bind( 'nonce', new Nonce() );

		// Settings snapshot.
		$this->c->bind( 'settings', function () {
			return get_option( 'keystone_seo_settings', array() );
		} );

		// Feature: Meta.
		$this->c->bind( 'meta', function ( $c ) {
			return new MetaService( $c->make( 'settings' ) );
		} );

		// Feature: Robots.
		$this->c->bind( 'robots', new RobotsController() );

		// Feature: Sitemap.
		$this->c->bind( 'sitemap.provider', new SitemapProvider() );
		$this->c->bind( 'sitemap.controller', function ( $c ) {
			return new SitemapController( $c->make( 'sitemap.provider' ) );
		} );

		// Feature: Redirects.
		$this->c->bind( 'redirect.repo', new RedirectRepository() );
		$this->c->bind( 'redirect.manager', function ( $c ) {
			return new RedirectManager( $c->make( 'redirect.repo' ) );
		} );

		// Admin UI.
		$this->c->bind( 'admin.settings', function ( $c ) {
			return new SettingsPage( $c->make( 'caps' ), $c->make( 'nonce' ) );
		} );
		$this->c->bind( 'admin.menu', function ( $c ) {
			return new AdminMenu( $c->make( 'caps' ), $c->make( 'admin.settings' ) );
		} );

		// REST.
		$this->c->bind( 'rest.settings', function ( $c ) {
			return new SettingsController( $c->make( 'caps' ), $c->make( 'nonce' ) );
		} );

		// CLI.
		$this->c->bind( 'cli.commands', function ( $c ) {
			return new Commands( $c->make( 'sitemap.provider' ) );
		} );
	}

	/**
	 * Activation tasks (DB tables, rewrites).
	 *
	 * @return void
	 */
	public function activate() {
		$this->c->make( 'redirect.repo' )->migrate();
		// Add rewrite rules once to ensure saved structure.
		$this->c->make( 'sitemap.controller' )->add_rewrite_rules();
	}

	/**
	 * Wire everything on runtime.
	 *
	 * @return void
	 */
	public function run() {
		// Admin menu.
		$this->hooks->action( 'admin_menu', $this->c->make( 'admin.menu' ), 'register_menu' );

		// REST routes.
		$this->hooks->action( 'rest_api_init', $this->c->make( 'rest.settings' ), 'register_routes' );

		// Meta.
		$this->hooks->filter( 'document_title_parts', $this->c->make( 'meta' ), 'filter_document_title', 10, 1 );
		$this->hooks->action( 'wp_head', $this->c->make( 'meta' ), 'output_head_tags', 1, 0 );

		// Robots.txt.
		$this->hooks->filter( 'robots_txt', $this->c->make( 'robots' ), 'filter_robots', 10, 2 );

		// Sitemaps.
		$this->hooks->action( 'init', $this->c->make( 'sitemap.controller' ), 'add_rewrite_rules' );
		$this->hooks->action( 'template_redirect', $this->c->make( 'sitemap.controller' ), 'maybe_render' );

		// Redirects.
		$this->hooks->action( 'template_redirect', $this->c->make( 'redirect.manager' ), 'maybe_redirect', 0, 0 );

		// CLI.
		$this->c->make( 'cli.commands' )->register();
	}
}