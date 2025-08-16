<?php
namespace Keystone\Admin;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Renders the Settings screen and handles POST.
 *
 * @since 0.1.0
 */
class SettingsPage {
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
	 * Render admin settings UI.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->caps->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'keystone-seo' ) );
		}

		// Handle POST save.
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['keystone_save_settings'] ) ) { // phpcs:ignore
			$this->handle_post();
		}

		$settings = get_option( $this->option_key, array(
			'site_name'   => get_bloginfo( 'name' ),
			'indexnow'    => false,
			'indexnowKey' => '',
		) );
    ?>
		
    <div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post">
				<?php $this->nonce->field(); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="site_name"><?php esc_html_e( 'Site Name (override)', 'keystone-seo' ); ?></label></th>
						<td><input type="text" id="site_name" name="site_name" class="regular-text" value="<?php echo esc_attr( $settings['site_name'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'IndexNow', 'keystone-seo' ); ?></th>
						<td>
							<label><input type="checkbox" name="indexnow" value="1" <?php checked( (bool) $settings['indexnow'] ); ?>> <?php esc_html_e( 'Enable IndexNow Pings', 'keystone-seo' ); ?></label><br>
							<input type="text" name="indexnowKey" class="regular-text" placeholder="<?php esc_attr_e( 'IndexNow Key', 'keystone-seo' ); ?>" value="<?php echo esc_attr( $settings['indexnowKey'] ); ?>">
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'keystone-seo' ), 'primary', 'keystone_save_settings' ); ?>
			</form>
		</div>

		<?php
  }
  
	/**
	 * Handle settings POST with sanitization + nonce.
	 *
	 * @return void
	 */
	protected function handle_post() {
		if ( ! $this->caps->can_manage_settings() || ! $this->nonce->verify_admin_post() ) {
			wp_die( esc_html__( 'Invalid request.', 'keystone-seo' ) );
		}

		$data = array(
			'site_name'   => isset( $_POST['site_name'] ) ? sanitize_text_field( wp_unslash( $_POST['site_name'] ) ) : '',
			'indexnow'    => isset( $_POST['indexnow'] ) ? (bool) absint( $_POST['indexnow'] ) : false,
			'indexnowKey' => isset( $_POST['indexnowKey'] ) ? sanitize_text_field( wp_unslash( $_POST['indexnowKey'] ) ) : '',
		);

		update_option( $this->option_key, $data );
		add_settings_error( 'keystone_seo', 'saved', __( 'Settings saved.', 'keystone-seo' ), 'updated' );
	}
}