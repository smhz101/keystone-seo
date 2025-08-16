<?php
namespace Keystone\Cli;

use Keystone\Sitemap\Registry as SitemapRegistry;
use Keystone\Redirects\RedirectRepository;
use Keystone\Monitor\NotFoundRepository;
use Keystone\IndexNow\IndexNowService;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WP-CLI commands for Keystone.
 *
 * @command keystone
 */
class Commands {

	/** @var SitemapRegistry */
	protected $registry;

	/** @var RedirectRepository|null */
	protected $redirects;

	/** @var NotFoundRepository|null */
	protected $nf;

	/** @var IndexNowService|null */
	protected $indexnow;

	/**
	 * Inject services for CLI.
	 *
	 * @param SitemapRegistry        $registry   Sitemap sources registry.
	 * @param RedirectRepository|null $redirects Redirects repository.
	 * @param NotFoundRepository|null $nf        404 monitor repository.
	 */
	public function __construct( SitemapRegistry $registry, RedirectRepository $redirects = null, NotFoundRepository $nf = null ) {
		$this->registry  = $registry;
		$this->redirects = $redirects;
		$this->nf        = $nf;
		$this->indexnow  = $indexnow;
	}

	/** Register WP-CLI commands. */
	public function register() {
		if ( ! class_exists( '\WP_CLI' ) ) { return; }

		\WP_CLI::add_command( 'keystone sitemap:render-index', array( $this, 'cmd_render_index' ) );
		\WP_CLI::add_command( 'keystone redirects:import', array( $this, 'cmd_redirects_import' ) );
		\WP_CLI::add_command( 'keystone redirects:export', array( $this, 'cmd_redirects_export' ) );
		\WP_CLI::add_command( 'keystone 404:top', array( $this, 'cmd_nf_top' ) );
		\WP_CLI::add_command( 'keystone 404:clear', array( $this, 'cmd_nf_clear' ) );
		\WP_CLI::add_command( 'keystone indexnow:ping', array( $this, 'cmd_indexnow_ping' ) );
	}

	/**
	 * Print the combined sitemap index XML to STDOUT.
	 * Usage: wp keystone sitemap:render-index > sitemap.xml
	 */
	public function cmd_render_index() {
		$entries = array();
		foreach ( $this->registry->all() as $source ) {
			$entries = array_merge( $entries, (array) $source->index_entries() );
		}

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ( $entries as $e ) : ?>
	<sitemap>
		<loc><?php echo esc_url( $e['loc'] ); ?></loc>
		<lastmod><?php echo esc_html( $e['lastmod'] ); ?></lastmod>
	</sitemap>
<?php endforeach; ?>
</sitemapindex>
<?php
		$xml = ob_get_clean();
		\WP_CLI::line( $xml );
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

	/** --url=<absolute_url> */
	public function cmd_indexnow_ping( $args, $assoc ) {
		if ( ! $this->indexnow ) { \WP_CLI::error( 'IndexNowService not bound.' ); }
		$u = isset( $assoc['url'] ) ? (string) $assoc['url'] : '';
		if ( ! $u ) { \WP_CLI::error( 'Provide --url=https://example.com/path' ); }
		$this->indexnow->ping_url( $u );
		\WP_CLI::success( 'Pinged IndexNow endpoints.' );
	}
}