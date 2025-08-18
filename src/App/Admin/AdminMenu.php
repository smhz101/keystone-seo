<?php
namespace Keystone\App\Admin;

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

	/** @var SettingsPage */
	protected $settings_page;

	public function __construct( Capabilities $caps, SettingsPage $settings_page ) {
		$this->caps          = $caps;
		$this->settings_page = $settings_page;
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
			array( $this->settings_page, 'render' ),
			'dashicons-shield-alt',
			58
		);
	}
}