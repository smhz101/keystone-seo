<?php
namespace Keystone\Http\Rest;

use WP_REST_Server;
use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * REST controller for settings.
 *
 * @since 0.1.0
 */
class SettingsController {
	/** @var string */
	protected $namespace = 'keystone/v1';

	/** @var Capabilities */
	protected $caps;

	/** @var Nonce */
	protected $nonce;

	/** @var string */
	protected $option_key = 'keystone_seo_settings';

	public function __construct( Capabilities $caps, Nonce $nonce ) {
		$this->caps  = $caps;
		$this->nonce = $nonce;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'permissions_with_nonce' ),
				),
			)
		);
	}

	/**
	 * Permissions basic (cap only).
	 *
	 * @return bool
	 */
	public function permissions() {
		return $this->caps->can_manage_settings();
	}

	/**
	 * Permissions with REST nonce verification.
	 *
	 * @return bool
	 */
	public function permissions_with_nonce() {
		return $this->caps->can_manage_settings() && $this->nonce->verify_rest();
	}

	/**
	 * GET settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings() {
		$settings = get_option( $this->option_key, array() );
		return rest_ensure_response( $settings );
	}

	/**
	 * PUT/PATCH settings with sanitization.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$raw = (array) $request->get_json_params();

		$san = array();
		$san['site_name']   = isset( $raw['site_name'] ) ? sanitize_text_field( $raw['site_name'] ) : '';
		$san['indexnow']    = ! empty( $raw['indexnow'] );
		$san['indexnowKey'] = isset( $raw['indexnowKey'] ) ? sanitize_text_field( $raw['indexnowKey'] ) : '';

		update_option( $this->option_key, $san );

		return rest_ensure_response( array( 'saved' => true, 'settings' => $san ) );
	}
}