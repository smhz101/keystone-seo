<?php
namespace Keystone\Cli;

use Keystone\Sitemap\SitemapProvider;
use Keystone\Redirects\RedirectRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Commands {
	protected $provider;
	protected $redirects;

	public function __construct( SitemapProvider $provider, RedirectRepository $redirects = null ) {
		$this->provider  = $provider;
		$this->redirects = $redirects;
	}

	public function register() {
		if ( ! class_exists( '\WP_CLI' ) ) { return; }

		\WP_CLI::add_command( 'keystone sitemap:render-index', array( $this, 'cmd_render_index' ) );
		\WP_CLI::add_command( 'keystone redirects:import', array( $this, 'cmd_redirects_import' ) );
		\WP_CLI::add_command( 'keystone redirects:export', array( $this, 'cmd_redirects_export' ) );
	}

	public function cmd_render_index() {
		\WP_CLI::line( $this->provider->render_index() );
	}

	/**
	 * --file=<path>
	 */
	public function cmd_redirects_import( $args, $assoc ) {
		if ( ! $this->redirects ) { \WP_CLI::error( 'RedirectRepository not bound.' ); }

		$file = isset( $assoc['file'] ) ? (string) $assoc['file'] : '';
		if ( ! $file ) { \WP_CLI::error( 'Provide --file=/path/to/redirects.csv' ); }

		$added = $this->redirects->import_csv( $file );
		\WP_CLI::success( "Imported {$added} redirects." );
	}

	public function cmd_redirects_export() {
		if ( ! $this->redirects ) { \WP_CLI::error( 'RedirectRepository not bound.' ); }

		$rows = $this->redirects->export_all();
		$fh   = fopen( 'php://output', 'w' );
		fputcsv( $fh, array( 'source', 'target', 'status', 'regex', 'hits', 'updated_at' ) );
		foreach ( $rows as $r ) { fputcsv( $fh, $r ); }
		fclose( $fh );
	}
}