<?php
namespace Keystone\Admin;

use Keystone\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Registers admin menu & pages.
 *
 * @since 0.1.0
 */
class AdminMenu {
  /** @var Capabilities */
	protected $caps;

  public function __construct( Capabilities $caps ) {
		$this->caps = $caps;
	}

	/**
	 * Add menu pages.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			'Keystone SEO',
			'Keystone SEO',
			$this->caps->manage_settings_cap(),
			'keystone-seo',
			'render_page',
			'dashicons-shield-alt',
			58
		);
	}
}