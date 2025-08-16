<?php
namespace Keystone\Security;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Capability map for Keystone actions.
 *
 * @since 0.1.0
 */
class Capabilities {
	/**
	 * Returns the capability required for managing settings.
	 *
	 * @return string
	 */
	public function manage_settings_cap() {
		return 'manage_options';
	}

	/**
	 * Check if current user can manage Keystone settings.
	 *
	 * @return bool
	 */
	public function can_manage_settings() {
		return current_user_can( $this->manage_settings_cap() );
	}
}