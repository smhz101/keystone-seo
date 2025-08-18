<?php
namespace Keystone\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\App\Services\Sitemap\Registry as SitemapRegistry;
use Keystone\App\Services\Sitemap\SitemapController;
use Keystone\App\Services\Sitemap\Providers\PostTypeSource;
use Keystone\App\Services\Sitemap\Providers\TaxonomySource;
use Keystone\App\Services\Sitemap\CacheInvalidator;

class SitemapServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'sitemap.registry', new SitemapRegistry() );
		$c->bind( 'sitemap.controller', function ( $c ) { return new SitemapController( $c->make( 'sitemap.registry' ) ); } );
		$c->bind( 'sitemap.invalidator', new CacheInvalidator() );

		// Defaults
		$c->make( 'sitemap.registry' )->add( new PostTypeSource( 'post' ) );
		$c->make( 'sitemap.registry' )->add( new PostTypeSource( 'page' ) );
		$c->make( 'sitemap.registry' )->add( new TaxonomySource( 'category' ) );
		$c->make( 'sitemap.registry' )->add( new TaxonomySource( 'post_tag' ) );

		// From settings
		$st = $c->make( 'settings' );
		if ( ! empty( $st['sm_include_cpt'] ) ) {
			foreach ( (array) $st['sm_include_cpt'] as $pt ) {
				$c->make( 'sitemap.registry' )->add( new PostTypeSource( $pt ) );
			}
		}
		if ( ! empty( $st['sm_include_tax'] ) ) {
			foreach ( (array) $st['sm_include_tax'] as $tx ) {
				$c->make( 'sitemap.registry' )->add( new TaxonomySource( $tx ) );
			}
		}
	}
	public function hooks( $hooks, $c ) {
		$hooks->action( 'init',              $c->make( 'sitemap.controller' ), 'add_rewrite_rules' );
		$hooks->action( 'template_redirect', $c->make( 'sitemap.controller' ), 'maybe_render' );
		$hooks->action( 'save_post',    $c->make( 'sitemap.invalidator' ), 'on_save_post',    10, 1 );
		$hooks->action( 'deleted_post', $c->make( 'sitemap.invalidator' ), 'on_deleted_post', 10, 1 );
		$hooks->action( 'edited_term',  $c->make( 'sitemap.invalidator' ), 'on_edited_term',  10, 3 );
	}
}