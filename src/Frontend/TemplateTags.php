<?php
namespace Keystone\Frontend;

use Keystone\Breadcrumbs\BreadcrumbsService;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Public template helpers and shortcode registration.
 *
 * @since 0.1.0
 */
class TemplateTags {
	protected $breadcrumbs;

	public function __construct( BreadcrumbsService $breadcrumbs ) {
		$this->breadcrumbs = $breadcrumbs;
	}

	/** Register shortcode for breadcrumbs. */
	public function register_shortcodes() {
		add_shortcode( 'keystone_breadcrumbs', array( $this, 'shortcode_breadcrumbs' ) );
	}

	/** Shortcode callback. */
	public function shortcode_breadcrumbs() {
		return $this->breadcrumbs->render();
	}

	/**
	 * Optional global template tag for themes:
	 *   if ( function_exists('keystone_breadcrumbs') ) { keystone_breadcrumbs(); }
	 */
	public function register_template_tag() {
		if ( ! function_exists( '\keystone_breadcrumbs' ) ) {
			function keystone_breadcrumbs() { echo do_shortcode( '[keystone_breadcrumbs]' ); } // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}