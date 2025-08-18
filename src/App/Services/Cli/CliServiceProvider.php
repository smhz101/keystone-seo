<?php
namespace Keystone\App\Services\Cli;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Core\Providers\ServiceProvider;
use Keystone\Cli\Commands;

class CliServiceProvider extends ServiceProvider {
	public function register( $c ) {
		$c->bind( 'cli.commands', function ( $c ) {
			return new Commands(
				$c->make( 'sitemap.registry' ),
				$c->make( 'redirect.repo' ),
				$c->make( 'nf.repo' ),
				$c->make( 'indexnow' )
			);
		} );
	}
	public function hooks( $hooks, $c ) {
		if ( class_exists( '\WP_CLI' ) ) {
			$c->make( 'cli.commands' )->register();
			// If needed: $c->make('cli.commands')->register_import_subcommands($c->make('import.manager'));
		}
	}
}
