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
use Keystone\Robots\Admin\RobotsPage;

use Keystone\Sitemap\SitemapController;

use Keystone\Redirects\RedirectRepository;
use Keystone\Redirects\RedirectManager;
use Keystone\Redirects\Admin\RedirectsPage;
use Keystone\Redirects\Rest\RedirectsController as RedirectsRest;

use Keystone\IndexNow\IndexNowService;
use Keystone\IndexNow\KeyRoute;

use Keystone\Schema\GraphBuilder;
use Keystone\Schema\Providers\OrganizationProvider;
use Keystone\Schema\Providers\WebSiteProvider;
use Keystone\Schema\Providers\ArticleProvider;
use Keystone\Schema\Providers\ProductProvider;

use Keystone\Integrations\WooCommerce\ProductDataProvider;

use Keystone\Social\SocialService;
use Keystone\Social\ImageGenerator;

use Keystone\Meta\MetaRepository;
use Keystone\Meta\MetaBox;
use Keystone\Meta\CanonicalRobots;

use Keystone\Breadcrumbs\BreadcrumbsService;
use Keystone\Frontend\TemplateTags;

use Keystone\Hreflang\HreflangController;
use Keystone\Hreflang\Adapters\WPMLAdapter;
use Keystone\Hreflang\Adapters\PolylangAdapter;

use Keystone\Monitor\NotFoundRepository;
use Keystone\Monitor\NotFoundMonitor;
use Keystone\Monitor\Admin\NotFoundPage;

use Keystone\Sitemap\Registry as SitemapRegistry;
use Keystone\Sitemap\Providers\PostTypeSource;
use Keystone\Sitemap\Providers\TaxonomySource;
use Keystone\Sitemap\CacheInvalidator;

use Keystone\Importers\ImportManager;
use Keystone\Importers\YoastImporter;
use Keystone\Importers\RankMathImporter;
use Keystone\Importers\AioseoImporter;
use Keystone\Importers\Admin\ImportPage;
use Keystone\Compatibility\ConflictDetector;

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

		// Per-post meta.
		$this->c->bind( 'meta.repo', new MetaRepository() );
		$this->c->bind( 'meta.box', function ( $c ) {
			return new MetaBox( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'meta.repo' ) );
		} );

		// Canonical + robots.
		$this->c->bind( 'meta.canonical', function ( $c ) {
			return new CanonicalRobots( $c->make( 'meta.repo' ), $c->make( 'settings' ) );
		} );

		// Social tags
		$this->c->bind( 'social', function( $c ) {
			return new SocialService( $c->make( 'settings' ), $c->make( 'meta.repo' ) );
		} );

		// OG generator
		$this->c->bind( 'og.gen', function( $c ) {
			return new ImageGenerator( $c->make( 'settings' ) );
		} );

		// Breadcrumbs + template tags.
		$this->c->bind( 'breadcrumbs', new BreadcrumbsService() );
		$this->c->bind( 'frontend.tags', function( $c ){ return new TemplateTags( $c->make('breadcrumbs') ); } );

		// Robots.
		$this->c->bind( 'robots', function ( $c ) { return new RobotsController( $c->make( 'settings' ) ); } );
		$this->c->bind( 'robots.page', function ( $c ) { return new RobotsPage( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );

		// Sitemaps.
		$this->c->bind( 'sitemap.registry', new SitemapRegistry() );
		$this->c->bind( 'sitemap.controller', function ( $c ) {
			return new SitemapController( $c->make( 'sitemap.registry' ) );
		} );

		// Register default sources (Posts/Pages + Category/Tag)
		$this->c->make( 'sitemap.registry' )->add( new PostTypeSource( 'post' ) );
		$this->c->make( 'sitemap.registry' )->add( new PostTypeSource( 'page' ) );
		$this->c->make( 'sitemap.registry' )->add( new TaxonomySource( 'category' ) );
		$this->c->make( 'sitemap.registry' )->add( new TaxonomySource( 'post_tag' ) );

		// Register CPT/tax from settings
		$__ks_settings = $this->c->make( 'settings' );
		if ( ! empty( $__ks_settings['sm_include_cpt'] ) ) {
			foreach ( (array) $__ks_settings['sm_include_cpt'] as $pt ) {
				$this->c->make( 'sitemap.registry' )->add( new PostTypeSource( $pt ) );
			}
		}
		if ( ! empty( $__ks_settings['sm_include_tax'] ) ) {
			foreach ( (array) $__ks_settings['sm_include_tax'] as $tx ) {
				$this->c->make( 'sitemap.registry' )->add( new TaxonomySource( $tx ) );
			}
		}

		// Sitemap cache invalidator
		$this->c->bind( 'sitemap.invalidator', new CacheInvalidator() );

		// Redirects.
		$this->c->bind( 'redirect.repo', new RedirectRepository() );
		$this->c->bind( 'redirect.manager', function ( $c ) { return new RedirectManager( $c->make( 'redirect.repo' ) ); } );
		$this->c->bind( 'redirects.page', function ( $c ) { return new RedirectsPage( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'redirect.repo' ) ); } );
		$this->c->bind( 'redirects.rest', function ( $c ) { return new RedirectsRest( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'redirect.repo' ) ); } );

		// IndexNow.
		$this->c->bind( 'indexnow', function ( $c ) {
			return new IndexNowService( $c->make( 'settings' ) );
		} );
		$this->c->bind( 'indexnow.keyroute', function ( $c ) {
			return new KeyRoute( $c->make( 'indexnow' ) ); 
		} );

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
		$this->c->bind( 'nf.page', function ( $c ) { return new NotFoundPage( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'nf.repo' ) ); } );

		// Admin.
		$this->c->bind( 'admin.settings', function ( $c ) { return new SettingsPage( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );
		$this->c->bind( 'admin.menu', function ( $c ) { return new AdminMenu( $c->make( 'caps' ), $c->make( 'admin.settings' ) ); } );

		// REST.
		$this->c->bind( 'rest.settings', function ( $c ) { return new SettingsController( $c->make( 'caps' ), $c->make( 'nonce' ) ); } );

		// Importers
		$c->bind( 'import.manager', function( Container $c ) {
			$mgr = new ImportManager();
			$mgr->register( new YoastImporter() );
			$mgr->register( new RankMathImporter() );
			$mgr->register( new AioseoImporter() );
			return $mgr;
		} );

		$c->bind( 'import.page', function( Container $c ) {
			return new ImportPage( $c->make( 'import.manager' ) );
		} );

		// Conflicts
		$c->bind( 'compat.detector', function( Container $c ) {
			return new ConflictDetector();
		} );

		// CLI. 
		$this->c->bind( 'cli.commands', function ( $c ) {
			return new Commands( 
				$c->make( 'sitemap.registry' ), 
				$c->make( 'redirect.repo' ), 
				$c->make( 'nf.repo' ),
				$c->make( 'indexnow' )
			);
		} );
	}

	public function activate() {
		$this->c->make( 'redirect.repo' )->migrate();
		$this->c->make( 'nf.repo' )->migrate();
		$this->c->make( 'sitemap.controller' )->add_rewrite_rules();
	 	$this->c->make( 'og.gen' )->add_rewrite_rules();
		$this->c->make( 'indexnow' )->maybe_write_key_file();
		$this->c->make( 'indexnow.keyroute' )->add_rewrite_rules();

		update_option( 'keystone_flush_rewrites', 1 );
	}

	public function run() {
		// Admin menus.
		$this->hooks->action( 'admin_menu', $this->c->make( 'admin.menu' ), 'register_menu' );
		$this->hooks->action( 'admin_menu', $this->c->make( 'robots.page' ), 'add_menu' );
		$this->hooks->action( 'admin_menu', $this->c->make( 'redirects.page' ), 'add_menu' );
		$this->hooks->action( 'admin_menu', $this->c->make( 'nf.page' ), 'add_menu' );

		// REST.
		$this->hooks->action( 'rest_api_init', $this->c->make( 'rest.settings' ), 'register_routes' );
		$this->hooks->action( 'rest_api_init', $this->c->make( 'redirects.rest' ), 'register_routes' );

		// Meta + Schema + Hreflang.
		$this->hooks->filter( 'document_title_parts', $this->c->make( 'meta' ), 'filter_document_title', 10, 1 );
		$this->hooks->action( 'add_meta_boxes', $this->c->make( 'meta.box' ), 'add_meta_boxes' );
		$this->hooks->action( 'save_post', $this->c->make( 'meta.box' ), 'save_post', 10, 1 );

		// Canonical + robots.
		$this->hooks->action( 'wp_head', $this->c->make( 'meta.canonical' ), 'output', 0, 0 );

		// Social meta tags
		$this->hooks->action( 'wp_head', $this->c->make( 'social' ), 'output', 4, 0 );

		// OG dynamic image route
		$this->hooks->action( 'init', $this->c->make( 'og.gen' ), 'add_rewrite_rules' );
		$this->hooks->action( 'template_redirect', $this->c->make( 'og.gen' ), 'maybe_render', 0, 0 );

		// Shortcode + template tag.
		$this->hooks->action( 'init', $this->c->make( 'frontend.tags' ), 'register_shortcodes' );
		$this->hooks->action( 'init', $this->c->make( 'frontend.tags' ), 'register_template_tag' );

		$this->hooks->action( 'wp_head', $this->c->make( 'meta' ), 'output_head_tags', 1, 0 );
		$this->hooks->action( 'wp_head', $this->c->make( 'schema.graph' ), 'output', 2, 0 );
		$this->hooks->action( 'wp_head', $this->c->make( 'hreflang' ), 'output', 3, 0 );

		// Robots + Sitemaps.
		$this->hooks->filter( 'robots_txt', $this->c->make( 'robots' ), 'filter_robots', 10, 2 );
		$this->hooks->action( 'init', $this->c->make( 'sitemap.controller' ), 'add_rewrite_rules' );
		$this->hooks->action( 'template_redirect', $this->c->make( 'sitemap.controller' ), 'maybe_render' );

		// Invalidation hooks.
		$this->hooks->action( 'save_post',     $this->c->make( 'sitemap.invalidator' ), 'on_save_post',     10, 1 );
		$this->hooks->action( 'deleted_post',  $this->c->make( 'sitemap.invalidator' ), 'on_deleted_post',  10, 1 );
		$this->hooks->action( 'edited_term',   $this->c->make( 'sitemap.invalidator' ), 'on_edited_term',   10, 3 );

		// Redirects + 404 monitor.
		$this->hooks->action( 'template_redirect', $this->c->make( 'redirect.manager' ), 'maybe_redirect', 0, 0 );
		$this->hooks->action( 'template_redirect', $this->c->make( 'nf.monitor' ), 'maybe_log', 9999, 0 );

		// IndexNow hooks
		$this->hooks->action( 'transition_post_status', $this->c->make( 'indexnow' ), 'on_transition_post_status', 10, 3 );
		$this->hooks->action( 'deleted_post', $this->c->make( 'indexnow' ), 'on_deleted_post', 10, 1 );

		// IndexNow key route
		$this->hooks->action( 'init', $this->c->make( 'indexnow.keyroute' ), 'add_rewrite_rules' );
		$this->hooks->action( 'template_redirect', $this->c->make( 'indexnow.keyroute' ), 'maybe_render_key', 0, 0 );

		// Importers
		$this->container->make( 'import.page' ); 
		$this->container->make( 'compat.detector' );

		// CLI.
		$this->c->make( 'cli.commands' )->register();

		// Flush once right after rules are registered.
		$this->hooks->action( 'init', $this, 'maybe_flush_rewrites', 999, 0 );
	}

	/** 
	 * Flush permalinks once if activation requested it. 
	 */
	public function maybe_flush_rewrites() {
		if ( get_option( 'keystone_flush_rewrites' ) ) {
			flush_rewrite_rules();
			delete_option( 'keystone_flush_rewrites' );
		}
	}
}