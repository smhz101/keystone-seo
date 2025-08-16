<?php
namespace Keystone\Monitor\Admin;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;
use Keystone\Monitor\NotFoundRepository;
use Keystone\Monitor\NotFoundListTable;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin UI for 404 monitor.
 */
class NotFoundPage {
	protected $caps;
	protected $nonce;
	protected $repo;
	protected $hook_suffix = '';

	public function __construct( Capabilities $caps, Nonce $nonce, NotFoundRepository $repo ) {
		$this->caps  = $caps;
		$this->nonce = $nonce;
		$this->repo  = $repo;
	}

	public function add_menu() {
		$this->hook_suffix = add_submenu_page(
			'keystone-seo',
			__( '404 Monitor', 'keystone-seo' ),
			__( '404 Monitor', 'keystone-seo' ),
			$this->caps->manage_settings_cap(),
			'keystone-404',
			array( $this, 'render' )
		);
		add_action( 'load-' . $this->hook_suffix, array( $this, 'on_load' ) );
	}

	public function on_load() {
		$this->handle_actions();
	}

	protected function handle_actions() {
		if ( ! $this->caps->can_manage_settings() ) { return; }

		// Single delete.
		if ( isset( $_GET['delete'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'keystone_nf' ) ) { // phpcs:ignore
			$this->repo->delete( absint( $_GET['delete'] ) ); // phpcs:ignore
			add_settings_error( 'keystone_nf', 'deleted', __( 'Row deleted.', 'keystone-seo' ), 'updated' );
		}

		// Bulk delete.
		if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && $this->nonce->verify_admin_post() ) { // phpcs:ignore
			$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array(); // phpcs:ignore
			$cnt = $this->repo->bulk_delete( $ids );
			add_settings_error( 'keystone_nf', 'bulk_deleted', sprintf( esc_html__( 'Deleted %d records.', 'keystone-seo' ), $cnt ), 'updated' );
		}

		// Clear all.
		if ( isset( $_POST['keystone_nf_clear'] ) && $this->nonce->verify_admin_post() ) { // phpcs:ignore
			$this->repo->clear();
			add_settings_error( 'keystone_nf', 'cleared', __( 'Cleared 404 log.', 'keystone-seo' ), 'updated' );
		}
	}

	public function render() {
		if ( ! $this->caps->can_manage_settings() ) { wp_die( esc_html__( 'Access denied.', 'keystone-seo' ) ); }

		$table = new NotFoundListTable( $this->repo );
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '404 Monitor', 'keystone-seo' ); ?></h1>

			<form method="post" style="margin-bottom:12px;">
				<?php $this->nonce->field(); ?>
				<?php submit_button( __( 'Clear Log', 'keystone-seo' ), 'secondary', 'keystone_nf_clear', false ); ?>
			</form>

			<form method="post">
				<?php $this->nonce->field(); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}