<?php
namespace Keystone\Importers\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Importers\ImportManager;

/**
 * Simple admin UI to run imports on demand.
 */
class ImportPage {

	/** @var ImportManager */
	protected $manager;

	/** @var string */
	protected $cap = 'manage_options';

	public function __construct( ImportManager $manager ) {
		$this->manager = $manager;
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_keystone_run_import', array( $this, 'handle_run' ) );
	}

	public function register_menu() {
		add_submenu_page(
			'keystone-seo',
			__( 'Import from Other SEO Plugins', 'keystone-seo' ),
			__( 'Import', 'keystone-seo' ),
			$this->cap,
			'keystone-import',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( $this->cap ) ) { return; }
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import SEO Data', 'keystone-seo' ); ?></h1>
			<p><?php esc_html_e( 'Migrate titles, descriptions, canonicals, and index settings into Keystone.', 'keystone-seo' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plugin', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Detected', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Items', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Action', 'keystone-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $this->manager->all() as $imp ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $imp->label() ); ?></strong> <code><?php echo esc_html( $imp->slug() ); ?></code></td>
						<td><?php echo $imp->detected() ? '&#10003;' : '&mdash;'; ?></td>
						<td><?php echo esc_html( (string) $imp->count() ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="keystone_run_import" />
								<input type="hidden" name="slug" value="<?php echo esc_attr( $imp->slug() ); ?>" />
								<?php wp_nonce_field( 'keystone_run_import', '_ks_nonce' ); ?>
								<?php submit_button( __( 'Run Import (Batch 200)', 'keystone-seo' ), 'primary', 'submit', false ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function handle_run() {
		if ( ! current_user_can( $this->cap ) ) { wp_die( -1 ); }
		check_admin_referer( 'keystone_run_import', '_ks_nonce' );

		$slug  = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$res   = $this->manager->run( $slug, 200, 0 );
		$query = array(
			'page'      => 'keystone-import',
			'keystone'  => 'done',
			'slug'      => $slug,
			'i'         => (int) $res['imported'],
			's'         => (int) $res['skipped'],
		);
		wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
		exit;
	}
}