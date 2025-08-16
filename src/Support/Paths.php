<?php
namespace Keystone\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Paths helper.
 *
 * @since 0.1.0
 */
class Paths {
	public function base_dir() { return KEYSTONE_SEO_DIR; }
	public function base_url() { return KEYSTONE_SEO_URL; }
	public function views_dir() { return KEYSTONE_SEO_DIR . 'views/'; }
	public function assets_url() { return KEYSTONE_SEO_URL . 'assets/'; }
}