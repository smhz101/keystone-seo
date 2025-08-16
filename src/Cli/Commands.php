<?php
namespace Keystone\Cli;

use Keystone\Sitemap\SitemapProvider;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WP-CLI commands for Keystone.
 *
 * @since 0.1.0
 */
class Commands {
	/** @var SitemapProvider */
	protected $provider;

	public function __construct( SitemapProvider $provider ) {
		$this->provider = $provider;
	}

	/**
	 * Register CLI commands.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}
		\WP_CLI::add_command( 'keystone sitemap:render-index', array( $this, 'cmd_render_index' ) );
	}

	/**
	 * Output sitemap index XML to STDOUT (for debug).
	 *
	 * ## EXAMPLES
	 *     wp keystone sitemap:render-index
	 *
	 * @return void
	 */
	public function cmd_render_index() {
		\WP_CLI::line( $this->provider->render_index() );
	}
}