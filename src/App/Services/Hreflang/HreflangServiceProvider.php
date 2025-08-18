<?php
namespace Keystone\App\Services\Hreflang;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Hreflang\HreflangController;
use Keystone\Hreflang\Adapters\WPMLAdapter;
use Keystone\Hreflang\Adapters\PolylangAdapter;

class HreflangServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'hreflang', new HreflangController() );
	}
	public function hooks( $hooks, $c ) {
		add_filter( 'keystone/hreflang/alternates', array( new WPMLAdapter(), 'filter' ), 10, 1 );
		add_filter( 'keystone/hreflang/alternates', array( new PolylangAdapter(), 'filter' ), 11, 1 );
		$hooks->action( 'wp_head', $c->make( 'hreflang' ), 'output', 3, 0 );
	}
}
