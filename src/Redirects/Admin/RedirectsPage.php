<?php
namespace Keystone\Redirects\Admin;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;
use Keystone\Redirects\RedirectRepository;
use Keystone\Redirects\RedirectListTable;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin UI for managing redirects.
 */
class RedirectsPage {
	protected $caps;
	protected $nonce;
	protected $repo;
	protected $table;

	public function __construct( Capabilities $caps, Nonce $nonce, RedirectRepository $repo ) {
		$this->caps  = $caps;
		$this->nonce = $nonce;
		$this->repo  = $repo;
		$this->table = new RedirectListTable( $repo );
	}

	public function add_menu() {
		add_submenu_page(
			'keystone-seo',
			__( 'Redirects', 'keystone-seo' ),
			__( 'Redirects', 'keystone-seo' ),
			$this->caps->manage_settings_cap(),
			'keystone-redirects',
			array( $this, 'render' )
		);
	}

	protected function handle_actions() {
		if ( ! $this->caps->can_manage_settings() ) { return; }

		// Single delete via GET
		if ( isset( $_GET['delete'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'keystone_redirects' ) ) { // phpcs:ignore
			$this->repo->delete( absint( $_GET['delete'] ) ); // phpcs:ignore
			add_settings_error( 'keystone_redirects', 'deleted', __( 'Redirect deleted.', 'keystone-seo' ), 'updated' );
		}

		// Bulk delete via POST
		if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && $this->nonce->verify_admin_post() ) { // phpcs:ignore
			$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array(); // phpcs:ignore
			$cnt = $this->repo->bulk_delete( $ids );
			add_settings_error( 'keystone_redirects', 'bulk_deleted', sprintf( esc_html__( 'Deleted %d redirects.', 'keystone-seo' ), $cnt ), 'updated' );
		}

		// Create/update
		if ( isset( $_POST['keystone_save_redirect'] ) && $this->nonce->verify_admin_post() ) { // phpcs:ignore
			$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
			$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : ''; // phpcs:ignore
			$target = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : ''; // phpcs:ignore
			$status = isset( $_POST['status'] ) ? absint( $_POST['status'] ) : 301; // phpcs:ignore
			$regex  = isset( $_POST['regex'] )  ? 1 : 0; // phpcs:ignore

			if ( $id ) {
				$this->repo->update( $id, compact( 'source', 'target', 'status', 'regex' ) );
				add_settings_error( 'keystone_redirects', 'updated', __( 'Redirect updated.', 'keystone-seo' ), 'updated' );
			} else {
				$this->repo->create( $source, $target, $status, $regex );
				add_settings_error( 'keystone_redirects', 'created', __( 'Redirect created.', 'keystone-seo' ), 'updated' );
			}
		}
	}

	public function render() {
		if ( ! $this->caps->can_manage_settings() ) { wp_die( esc_html__( 'Access denied.', 'keystone-seo' ) ); }

		$this->handle_actions();

		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore
		$item    = $edit_id ? $this->repo->get( $edit_id ) : array(
			'id'     => 0,
			'source' => '',
			'target' => '',
			'status' => 301,
			'regex'  => 0,
		);

		$this->table->prepare_items();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Redirects', 'keystone-seo' ); ?></h1>

			<h2><?php echo $edit_id ? esc_html__( 'Edit Redirect', 'keystone-seo' ) : esc_html__( 'Add New Redirect', 'keystone-seo' ); ?></h2>
			<form method="post">
				<?php $this->nonce->field(); ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
				<table class="form-table">
					<tr>
						<th><label for="source"><?php esc_html_e( 'Source Path', 'keystone-seo' ); ?></label></th>
						<td><input type="text" id="source" name="source" class="regular-text" placeholder="/old-path" value="<?php echo esc_attr( $item['source'] ); ?>" required></td>
					</tr>
					<tr>
						<th><label for="target"><?php esc_html_e( 'Target URL', 'keystone-seo' ); ?></label></th>
						<td><input type="url" id="target" name="target" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" value="<?php echo esc_attr( $item['target'] ); ?>" required></td>
					</tr>
					<tr>
						<th><label for="status"><?php esc_html_e( 'HTTP Status', 'keystone-seo' ); ?></label></th>
						<td>
							<select id="status" name="status">
								<?php foreach ( array( 301, 302, 307, 410 ) as $st ) : ?>
									<option value="<?php echo esc_attr( (string) $st ); ?>" <?php selected( (int) $item['status'], $st ); ?>><?php echo esc_html( (string) $st ); ?></option>
								<?php endforeach; ?>
							</select>
							<label style="margin-left:10px;"><input type="checkbox" name="regex" value="1" <?php checked( (int) $item['regex'], 1 ); ?>> <?php esc_html_e( 'Regex', 'keystone-seo' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button( $edit_id ? __( 'Update Redirect', 'keystone-seo' ) : __( 'Add Redirect', 'keystone-seo' ), 'primary', 'keystone_save_redirect' ); ?>
			</form>

			<hr>

			<form method="post">
				<?php $this->nonce->field(); ?>
				<?php $this->table->display(); ?>
			</form>
		</div>
		<?php
	}
}