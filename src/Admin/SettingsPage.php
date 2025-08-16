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
			'indexnow_enabled'   => false,
			'indexnow_key'       => '',
			'indexnow_endpoints' => array(),
			'org_name'       => get_bloginfo( 'name' ),
			'org_logo'       => '',
			'org_url'        => home_url( '/' ),
			'same_as'        => array(),
			'site_search_url'=> '',
			'title_template' => '%title% %sep% %sitename%',
			'desc_template'  => '%tagline%',
			'og_default_image' => '',
			'og_use_generator' => false,
			'og_bg'            => '',
			'og_fg'            => '',
			'sm_include_cpt'  => array(),
			'sm_include_tax'  => array(),
		);
		$settings = wp_parse_args( get_option( $this->option_key, array() ), $defaults );

		// Collect public CPTs and taxonomies.
		$cpts = get_post_types( array( 'public' => true ), 'objects' );
		$taxs = get_taxonomies( array( 'public' => true ), 'objects' );
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
						<th scope="row"><?php esc_html_e( 'Enable IndexNow', 'keystone-seo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="indexnow_enabled" value="1" <?php checked( ! empty( $settings['indexnow_enabled'] ) ); ?> />
								<?php esc_html_e( 'Ping search engines when content is published/updated/deleted', 'keystone-seo' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Key', 'keystone-seo' ); ?></th>
						<td>
							<input type="text" name="indexnow_key" value="<?php echo esc_attr( (string) $settings['indexnow_key'] ); ?>" class="regular-text" maxlength="64" />
							<p class="description">
								<?php esc_html_e( 'If empty, Keystone will generate one and try to write {key}.txt at the site root; otherwise a dynamic route will serve it.', 'keystone-seo' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Endpoints', 'keystone-seo' ); ?></th>
						<td>
							<textarea name="indexnow_endpoints" rows="3" class="large-text" placeholder="https://www.bing.com/indexnow"><?php
								echo esc_textarea( implode( "\n", (array) $settings['indexnow_endpoints'] ) );
							?></textarea>
							<p class="description"><?php esc_html_e( 'One URL per line. Default will be used if left empty.', 'keystone-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Key File Status', 'keystone-seo' ); ?></th>
						<td>
							<?php
							$key = isset( $settings['indexnow_key'] ) ? $settings['indexnow_key'] : '';
							if ( $key ) {
								$link = home_url( '/' . $key . '.txt' );
								echo '<code>' . esc_html( $link ) . '</code>';
							} else {
								esc_html_e( 'Key will be generated after saving.', 'keystone-seo' );
							}
							?>
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

				<h2 class="title"><?php esc_html_e( 'Social (Open Graph / Twitter)', 'keystone-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Social Image URL', 'keystone-seo' ); ?></th>
						<td>
							<input type="url" name="og_default_image" class="regular-text" value="<?php echo esc_attr( isset( $settings['og_default_image'] ) ? $settings['og_default_image'] : '' ); ?>">
							<p class="description"><?php esc_html_e( 'Used when no featured image is available.', 'keystone-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Dynamic OG Image', 'keystone-seo' ); ?></th>
						<td>
							<label><input type="checkbox" name="og_use_generator" value="1" <?php checked( ! empty( $settings['og_use_generator'] ) ); ?>> <?php esc_html_e( 'Generate simple OG image for posts', 'keystone-seo' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Generator Colors (optional)', 'keystone-seo' ); ?></th>
						<td>
							<input type="text" name="og_bg" value="<?php echo esc_attr( isset( $settings['og_bg'] ) ? $settings['og_bg'] : '' ); ?>" placeholder="#111827" class="small-text">
							<input type="text" name="og_fg" value="<?php echo esc_attr( isset( $settings['og_fg'] ) ? $settings['og_fg'] : '' ); ?>" placeholder="#FFFFFF" class="small-text">
							<p class="description"><?php esc_html_e( 'Hex colors. Leave empty for defaults.', 'keystone-seo' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Sitemaps', 'keystone-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Custom Post Types', 'keystone-seo' ); ?></th>
						<td>
							<?php foreach ( $cpts as $pt ) :
								if ( in_array( $pt->name, array( 'post', 'page' ), true ) ) { continue; } ?>
								<label style="display:inline-block;margin-right:16px;">
									<input type="checkbox" name="sm_include_cpt[]" value="<?php echo esc_attr( $pt->name ); ?>"
										<?php checked( in_array( $pt->name, (array) $settings['sm_include_cpt'], true ) ); ?> />
									<?php echo esc_html( $pt->labels->name . ' (' . $pt->name . ')' ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Posts and Pages are always included.', 'keystone-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Taxonomies', 'keystone-seo' ); ?></th>
						<td>
							<?php foreach ( $taxs as $tx ) :
								if ( in_array( $tx->name, array( 'category', 'post_tag' ), true ) ) { continue; } ?>
								<label style="display:inline-block;margin-right:16px;">
									<input type="checkbox" name="sm_include_tax[]" value="<?php echo esc_attr( $tx->name ); ?>"
										<?php checked( in_array( $tx->name, (array) $settings['sm_include_tax'], true ) ); ?> />
									<?php echo esc_html( $tx->labels->name . ' (' . $tx->name . ')' ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Categories & Tags are always included.', 'keystone-seo' ); ?></p>
						</td>
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

		$k  = isset( $_POST['indexnow_key'] ) ? sanitize_text_field( wp_unslash( $_POST['indexnow_key'] ) ) : '';
		$k  = strtolower( preg_replace( '/[^a-f0-9]/i', '', $k ) );
		if ( $k && strlen( $k ) !== 32 ) { $k = ''; } // enforce 32 hex

		$eps = array();
		if ( isset( $_POST['indexnow_endpoints'] ) ) { // phpcs:ignore
			$lines = explode( "\n", wp_unslash( $_POST['indexnow_endpoints'] ) ); // phpcs:ignore
			foreach ( $lines as $l ) {
				$l = trim( $l );
				if ( $l ) { $eps[] = esc_url_raw( $l ); }
			}
		}

		$data = array(
			'site_name'      		=> isset( $_POST['site_name'] ) ? sanitize_text_field( wp_unslash( $_POST['site_name'] ) ) : '',
			'indexnow_enabled'	=> isset( $_POST['indexnow_enabled'] ) ? (bool) absint( $_POST['indexnow_enabled'] ) : false,
			'indexnow_key'    	=> $k,
			'indexnow_endpoints'=> array_values( array_unique( array_filter( $eps ) ) ),
			'org_name'       		=> isset( $_POST['org_name'] ) ? sanitize_text_field( wp_unslash( $_POST['org_name'] ) ) : '',
			'org_logo'       		=> isset( $_POST['org_logo'] ) ? esc_url_raw( wp_unslash( $_POST['org_logo'] ) ) : '',
			'org_url'        		=> isset( $_POST['org_url'] ) ? esc_url_raw( wp_unslash( $_POST['org_url'] ) ) : '',
			'same_as'        		=> $same_as,
			'title_template' 		=> isset( $_POST['title_template'] ) ? sanitize_text_field( wp_unslash( $_POST['title_template'] ) ) : '',
			'desc_template'  		=> isset( $_POST['desc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['desc_template'] ) ) : '',
			'site_search_url'		=> isset( $_POST['site_search_url'] ) ? esc_url_raw( wp_unslash( $_POST['site_search_url'] ) ) : '',
			'og_default_image' 	=> isset( $_POST['og_default_image'] ) ? esc_url_raw( wp_unslash( $_POST['og_default_image'] ) ) : '',
			'og_use_generator' 	=> isset( $_POST['og_use_generator'] ) ? (bool) absint( $_POST['og_use_generator'] ) : false,
			'og_bg' 						=> isset( $_POST['og_bg'] ) ? sanitize_text_field( wp_unslash( $_POST['og_bg'] ) ) : '',
			'og_fg' 						=> isset( $_POST['og_fg'] ) ? sanitize_text_field( wp_unslash( $_POST['og_fg'] ) ) : ''
		);

		$data['sm_include_cpt'] = array();
		if ( isset( $_POST['sm_include_cpt'] ) ) { // phpcs:ignore
			foreach ( (array) $_POST['sm_include_cpt'] as $pt ) { // phpcs:ignore
				$data['sm_include_cpt'][] = sanitize_key( $pt );
			}
		}
		$data['sm_include_tax'] = array();
		if ( isset( $_POST['sm_include_tax'] ) ) { // phpcs:ignore
			foreach ( (array) $_POST['sm_include_tax'] as $tx ) { // phpcs:ignore
				$data['sm_include_tax'][] = sanitize_key( $tx );
			}
		}

		update_option( $this->option_key, $data );
		add_settings_error( 'keystone_seo', 'saved', __( 'Settings saved.', 'keystone-seo' ), 'updated' );
	}
}