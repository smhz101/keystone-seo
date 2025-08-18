<?php
namespace Keystone\App\Services\Meta;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Meta\MetaService;
use Keystone\Meta\Templates;
use Keystone\Meta\Providers\PostTokenProvider;
use Keystone\Meta\MetaRepository;
use Keystone\Meta\MetaBox;
use Keystone\Meta\CanonicalRobots;

class MetaServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'meta.templates', new Templates() );
		$c->bind( 'meta', function ( $c ) {
			$svc = new MetaService( $c->make( 'settings' ), $c->make( 'meta.templates' ) );
			$svc->add_provider( new PostTokenProvider( '—' ) );
			return $svc;
		} );
		$c->bind( 'meta.repo', new MetaRepository() );
		$c->bind( 'meta.box', function ( $c ) { return new MetaBox( $c->make( 'caps' ), $c->make( 'nonce' ), $c->make( 'meta.repo' ) ); } );
		$c->bind( 'meta.canonical', function ( $c ) { return new CanonicalRobots( $c->make( 'meta.repo' ), $c->make( 'settings' ) ); } );
	}
	public function hooks( $hooks, $c ) {
		$hooks->filter( 'document_title_parts', $c->make( 'meta' ), 'filter_document_title', 10, 1 );
		$hooks->action( 'add_meta_boxes', $c->make( 'meta.box' ), 'add_meta_boxes' );
		$hooks->action( 'save_post', $c->make( 'meta.box' ), 'save_post', 10, 1 );
		$hooks->action( 'wp_head', $c->make( 'meta.canonical' ), 'output', 0, 0 );
		$hooks->action( 'wp_head', $c->make( 'meta' ), 'output_head_tags', 1, 0 );
	}
}
