<?php
namespace Keystone\Admin;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Settings page with:
 * - General (site name override)
 * - IndexNow (enable + key)
 * - Organization (name, logo, url, social)
 * - Meta Templates (title/description)
 */
class SettingsPage {
	protected $caps;
	protected $nonce;
	protected $option_key = 'keystone_seo_settings';

	public function __construct( Capabilities $caps, Nonce $nonce ) {
		$this->caps  = $caps;
		$this->nonce = $nonce;
	}

	public function render() {
		if ( ! $this->caps->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'keystone-seo' ) );
		}
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['keystone_save_settings'] ) ) { // phpcs:ignore
			$this->handle_post();
		}

		$defaults = array(
			'site_name'      => get_bloginfo( 'name' ),
			'indexnow'       => false,
			'indexnowKey'    => '',
			'org_name'       => get_bloginfo( 'name' ),
			'org_logo'       => '',
			'org_url'        => home_url( '/' ),
			'same_as'        => array(),
			'site_search_url'=> '',
			'title_template' => '%title% %sep% %sitename%',
			'desc_template'  => '%tagline%',
		);
		$settings = wp_parse_args( get_option( $this->option_key, array() ), $defaults );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post">
				<?php $this->nonce->field(); ?>

				<h2 class="title"><?php esc_html_e( 'General', 'keystone-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="site_name"><?php esc_html_e( 'Site Name (override)', 'keystone-seo' ); ?></label></th>
						<td><input type="text" id="site_name" name="site_name" class="regular-text" value="<?php echo esc_attr( $settings['site_name'] ); ?>"></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'IndexNow', 'keystone-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'IndexNow', 'keystone-seo' ); ?></th>
						<td>
							<label><input type="checkbox" name="indexnow" value="1" <?php checked( (bool) $settings['indexnow'] ); ?>> <?php esc_html_e( 'Enable IndexNow Pings', 'keystone-seo' ); ?></label><br>
							<input type="text" name="indexnowKey" class="regular-text" placeholder="<?php esc_attr_e( 'IndexNow Key', 'keystone-seo' ); ?>" value="<?php echo esc_attr( $settings['indexnowKey'] ); ?>">
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Organization', 'keystone-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="org_name"><?php esc_html_e( 'Organization Name', 'keystone-seo' ); ?></label></th>
						<td><input type="text" id="org_name" name="org_name" class="regular-text" value="<?php echo esc_attr( $settings['org_name'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="org_logo"><?php esc_html_e( 'Logo URL', 'keystone-seo' ); ?></label></th>
						<td><input type="url" id="org_logo" name="org_logo" class="regular-text" value="<?php echo esc_attr( $settings['org_logo'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="org_url"><?php esc_html_e( 'Organization URL', 'keystone-seo' ); ?></label></th>
						<td><input type="url" id="org_url" name="org_url" class="regular-text" value="<?php echo esc_attr( $settings['org_url'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Social Profiles (one per line)', 'keystone-seo' ); ?></th>
						<td>
							<textarea name="same_as" rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", (array) $settings['same_as'] ) ); ?></textarea>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Meta Templates', 'keystone-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="title_template"><?php esc_html_e( 'Title Template', 'keystone-seo' ); ?></label></th>
						<td><input type="text" id="title_template" name="title_template" class="regular-text" value="<?php echo esc_attr( $settings['title_template'] ); ?>">
							<p class="description"><?php echo esc_html__( 'Use tokens like %title%, %sitename%, %tagline%, %category%, %date%, %sep%', 'keystone-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="desc_template"><?php esc_html_e( 'Description Template', 'keystone-seo' ); ?></label></th>
						<td><input type="text" id="desc_template" name="desc_template" class="regular-text" value="<?php echo esc_attr( $settings['desc_template'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="site_search_url"><?php esc_html_e( 'Site Search URL (optional)', 'keystone-seo' ); ?></label></th>
						<td><input type="url" id="site_search_url" name="site_search_url" class="regular-text" placeholder="<?php echo esc_attr( add_query_arg( 's', '{search_term_string}', home_url( '/' ) ) ); ?>" value="<?php echo esc_attr( $settings['site_search_url'] ); ?>"></td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'keystone-seo' ), 'primary', 'keystone_save_settings' ); ?>
			</form>
		</div>
		<?php
	}

	protected function handle_post() {
		if ( ! $this->caps->can_manage_settings() || ! $this->nonce->verify_admin_post() ) {
			wp_die( esc_html__( 'Invalid request.', 'keystone-seo' ) );
		}

		$same_as = array();
		if ( isset( $_POST['same_as'] ) ) { // phpcs:ignore
			$lines = explode( "\n", wp_unslash( $_POST['same_as'] ) ); // phpcs:ignore
			foreach ( $lines as $l ) {
				$l = trim( $l );
				if ( $l ) {
					$same_as[] = esc_url_raw( $l );
				}
			}
		}

		$data = array(
			'site_name'      => isset( $_POST['site_name'] ) ? sanitize_text_field( wp_unslash( $_POST['site_name'] ) ) : '',
			'indexnow'       => isset( $_POST['indexnow'] ) ? (bool) absint( $_POST['indexnow'] ) : false,
			'indexnowKey'    => isset( $_POST['indexnowKey'] ) ? sanitize_text_field( wp_unslash( $_POST['indexnowKey'] ) ) : '',
			'org_name'       => isset( $_POST['org_name'] ) ? sanitize_text_field( wp_unslash( $_POST['org_name'] ) ) : '',
			'org_logo'       => isset( $_POST['org_logo'] ) ? esc_url_raw( wp_unslash( $_POST['org_logo'] ) ) : '',
			'org_url'        => isset( $_POST['org_url'] ) ? esc_url_raw( wp_unslash( $_POST['org_url'] ) ) : '',
			'same_as'        => $same_as,
			'title_template' => isset( $_POST['title_template'] ) ? sanitize_text_field( wp_unslash( $_POST['title_template'] ) ) : '',
			'desc_template'  => isset( $_POST['desc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['desc_template'] ) ) : '',
			'site_search_url'=> isset( $_POST['site_search_url'] ) ? esc_url_raw( wp_unslash( $_POST['site_search_url'] ) ) : '',
		);

		update_option( $this->option_key, $data );
		add_settings_error( 'keystone_seo', 'saved', __( 'Settings saved.', 'keystone-seo' ), 'updated' );
	}
}