<?php
namespace Keystone\App\Services\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\App\Services\Schema\GraphBuilder;
use Keystone\App\Services\Schema\Providers\OrganizationProvider;
use Keystone\App\Services\Schema\Providers\WebSiteProvider;
use Keystone\App\Services\Schema\Providers\ArticleProvider;
use Keystone\App\Services\Schema\Providers\ProductProvider;
use Keystone\Integrations\WooCommerce\ProductDataProvider;

class SchemaServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'schema.graph', function ( $c ) {
			$g = new GraphBuilder( $c->make( 'settings' ) );
			$g->add_provider( new OrganizationProvider() );
			$g->add_provider( new WebSiteProvider() );
			$g->add_provider( new ArticleProvider() );
			$g->add_provider( new ProductProvider( new ProductDataProvider() ) );
			return $g;
		} );
	}
	public function hooks( $hooks, $c ) {
		$hooks->action( 'wp_head', $c->make( 'schema.graph' ), 'output', 2, 0 );
	}
}
