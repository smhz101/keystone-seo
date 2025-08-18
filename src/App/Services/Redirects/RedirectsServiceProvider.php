<?php
namespace Keystone\App\Services\Redirects;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Redirects\RedirectRepository;
use Keystone\Redirects\RedirectManager;

class RedirectsServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'redirect.repo', new RedirectRepository() );
		$c->bind( 'redirect.manager', function ( $c ) { return new RedirectManager( $c->make( 'redirect.repo' ) ); } );
	}
	public function hooks( $hooks, $c ) {
		$hooks->action( 'template_redirect', $c->make( 'redirect.manager' ), 'maybe_redirect', 0, 0 );
	}
}
