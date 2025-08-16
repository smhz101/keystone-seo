<?php
namespace Keystone\Cli;

use Keystone\Sitemap\SitemapProvider;
use Keystone\Redirects\RedirectRepository;
use Keystone\Monitor\NotFoundRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WP-CLI commands for Keystone.
 */
class Commands {
	protected $provider;
	protected $redirects;
	protected $nf;

	public function __construct( SitemapProvider $provider, RedirectRepository $redirects = null, NotFoundRepository $nf = null ) {
		$this->provider  = $provider;
		$this->redirects = $redirects;
		$this->nf        = $nf;
	}

	public function register() {
		if ( ! class_exists( '\WP_CLI' ) ) { return; }

		\WP_CLI::add_command( 'keystone sitemap:render-index', array( $this, 'cmd_render_index' ) );
		\WP_CLI::add_command( 'keystone redirects:import', array( $this, 'cmd_redirects_import' ) );
		\WP_CLI::add_command( 'keystone redirects:export', array( $this, 'cmd_redirects_export' ) );
		\WP_CLI::add_command( 'keystone 404:top', array( $this, 'cmd_nf_top' ) );
		\WP_CLI::add_command( 'keystone 404:clear', array( $this, 'cmd_nf_clear' ) );
	}

	public function cmd_render_index() {
		\WP_CLI::line( $this->provider->render_index() );
	}

	/** --file=<path> */
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

	public function cmd_nf_top( $args, $assoc ) {
		if ( ! $this->nf ) { \WP_CLI::error( 'NotFoundRepository not bound.' ); }
		$limit = isset( $assoc['limit'] ) ? absint( $assoc['limit'] ) : 20;
		$rows  = $this->nf->top( $limit );
		foreach ( $rows as $r ) {
			\WP_CLI::line( sprintf( '%6d  %s  (ref: %s)', $r['hits'], $r['path'], $r['referrer'] ?: '-' ) );
		}
	}

	public function cmd_nf_clear() {
		if ( ! $this->nf ) { \WP_CLI::error( 'NotFoundRepository not bound.' ); }
		$this->nf->clear();
		\WP_CLI::success( 'Cleared 404 log.' );
	}
}