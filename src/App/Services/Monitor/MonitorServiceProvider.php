<?php
namespace Keystone\App\Services\Monitor;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Monitor\NotFoundRepository;
use Keystone\Monitor\NotFoundMonitor;

class MonitorServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'nf.repo', new NotFoundRepository() );
		$c->bind( 'nf.monitor', function ( $c ) { return new NotFoundMonitor( $c->make( 'nf.repo' ) ); } );
	}
	public function hooks( $hooks, $c ) {
		$hooks->action( 'template_redirect', $c->make( 'nf.monitor' ), 'maybe_log', 9999, 0 );
	}
}
