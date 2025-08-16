<?php
namespace Keystone\Security;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Nonce utilities (for admin forms & REST).
 *
 * @since 0.1.0
 */
class Nonce {
	/** @var string */
	protected $action = 'keystone-seo';

	/**
	 * Create a nonce field (admin UI).
	 *
	 * @return void
	 */
	public function field() {
		wp_nonce_field( $this->action, '_keystone_nonce' );
	}

	/**
	 * Verify admin POST nonces.
	 *
	 * @return bool
	 */
	public function verify_admin_post() {
		return isset( $_POST['_keystone_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_keystone_nonce'] ) ), $this->action );
	}

	/**
	 * Verify REST X-WP-Nonce header or parameter.
	 *
	 * @return bool
	 */
	public function verify_rest() {
		$nonce = '';
		if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
		} elseif ( isset( $_REQUEST['_keystone_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_keystone_nonce'] ) ); // phpcs:ignore
		}
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}
}