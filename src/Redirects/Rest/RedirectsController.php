<?php
namespace Keystone\Redirects\Rest;

use WP_REST_Server;
use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;
use Keystone\Redirects\RedirectRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * REST endpoints for redirects.
 * GET /keystone/v1/redirects
 * POST /keystone/v1/redirects
 * PUT /keystone/v1/redirects/{id}
 * DELETE /keystone/v1/redirects/{id}
 */
class RedirectsController {

	protected $namespace = 'keystone/v1';
	protected $caps;
	protected $nonce;
	protected $repo;

	public function __construct( Capabilities $caps, Nonce $nonce, RedirectRepository $repo ) {
	$this->caps = $caps; $this->nonce = $nonce; $this->repo = $repo;
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/redirects', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list' ),
				'permission_callback' => array( $this, 'can' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create' ),
				'permission_callback' => array( $this, 'can_with_nonce' ),
			),
		) );

		register_rest_route( $this->namespace, '/redirects/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => array( $this, 'can_with_nonce' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => array( $this, 'can_with_nonce' ),
			),
		) );
	}

	public function can() {
		return $this->caps->can_manage_settings();
	}
	public function can_with_nonce() {
		return $this->caps->can_manage_settings() && $this->nonce->verify_rest();
	}

	public function list( $request ) {
		$paged    = absint( $request->get_param( 'page' ) ) ?: 1;
		$per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ) ?: 20 ) );
		return rest_ensure_response( $this->repo->all( $paged, $per_page ) );
	}

	public function create( $request ) {
		$data = $this->sanitize_body( (array) $request->get_json_params() );
		$id   = $this->repo->create( $data['source'], $data['target'], $data['status'], $data['regex'] );
		return rest_ensure_response( $this->repo->get( $id ) );
	}

	public function update( $request ) {
		$id   = absint( $request['id'] );
		$data = $this->sanitize_body( (array) $request->get_json_params() );
		$this->repo->update( $id, $data );
		return rest_ensure_response( $this->repo->get( $id ) );
	}

	public function delete( $request ) {
		$id = absint( $request['id'] );
		$this->repo->delete( $id );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	protected function sanitize_body( $raw ) {
		return array(
			'source' => isset( $raw['source'] ) ? sanitize_text_field( $raw['source'] ) : '',
			'target' => isset( $raw['target'] ) ? esc_url_raw( $raw['target'] ) : '',
			'status' => isset( $raw['status'] ) ? absint( $raw['status'] ) : 301,
			'regex'  => ! empty( $raw['regex'] ) ? 1 : 0,
		);
	}
}
