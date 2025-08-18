<?php
namespace Keystone\App\Services\Importers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Importers\ImportManager;
use Keystone\Importers\YoastImporter;
use Keystone\Importers\RankMathImporter;
use Keystone\Importers\AioseoImporter;
use Keystone\Importers\Admin\ImportPage;

class ImportersServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'import.manager', function( $c ) {
			$mgr = new ImportManager();
			$mgr->register( new YoastImporter() );
			$mgr->register( new RankMathImporter() );
			$mgr->register( new AioseoImporter() );
			return $mgr;
		} );
		$c->bind( 'import.page', function( $c ) { return new ImportPage( $c->make( 'import.manager' ) ); } );
	}
	public function hooks( $hooks, $c ) {
		// Menu + AJAX are added inside ImportPage constructor; ensure it's instantiated
		$c->make( 'import.page' );
	}
}
