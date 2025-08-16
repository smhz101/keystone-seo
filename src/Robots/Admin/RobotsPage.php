<?php
namespace Keystone\Robots\Admin;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin UI for robots.txt rules managed by Keystone.
 * Settings keys: robots_custom (textarea lines), robots_extra_sitemaps (textarea), robots_block_media (bool)
 */
class RobotsPage {
	protected $caps;
	protected $nonce;
	protected $option_key = 'keystone_seo_settings';

	public function __construct( Capabilities $caps, Nonce $nonce ) {
		$this->caps  = $caps;
		$this->nonce = $nonce;
	}

	public function add_menu() {
		add_submenu_page(
			'keystone-seo',
			__( 'Robots.txt', 'keystone-seo' ),
			__( 'Robots.txt', 'keystone-seo' ),
			$this->caps->manage_settings_cap(),
			'keystone-robots',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! $this->caps->can_manage_settings() ) {
			wp_die( esc_html__( 'Access denied.', 'keystone-seo' ) );
		}

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['keystone_save_robots'] ) && $this->nonce->verify_admin_post() ) { // phpcs:ignore
			$this->save();
		}

		$defaults = array(
			'robots_custom'        => array(),
			'robots_extra_sitemaps'=> array(),
			'robots_block_media'   => false,
		);
		$s = wp_parse_args( get_option( $this->option_key, array() ), $defaults );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Robots.txt Manager', 'keystone-seo' ); ?></h1>
			<form method="post">
				<?php $this->nonce->field(); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Custom Rules (one per line)', 'keystone-seo' ); ?></th>
						<td>
							<textarea name="robots_custom" rows="8" class="large-text" placeholder="User-agent: *&#10;Disallow: /private/"><?php echo esc_textarea( implode( "\n", (array) $s['robots_custom'] ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Lines are output as-is into robots.txt after the default allow/sitemap lines.', 'keystone-seo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Extra Sitemaps (one URL per line)', 'keystone-seo' ); ?></th>
						<td>
							<textarea name="robots_extra_sitemaps" rows="4" class="large-text" placeholder="<?php echo esc_attr( home_url( '/sitemap.xml' ) ); ?>"><?php echo esc_textarea( implode( "\n", (array) $s['robots_extra_sitemaps'] ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Block Media Query Strings', 'keystone-seo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="robots_block_media" value="1" <?php checked( (bool) $s['robots_block_media'] ); ?>>
								<?php esc_html_e( 'Add rules to reduce crawling of image/style/script query-string URLs', 'keystone-seo' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Robots Settings', 'keystone-seo' ), 'primary', 'keystone_save_robots' ); ?>
			</form>
		</div>
		<?php
	}

	protected function save() {
		$custom = array();
		if ( isset( $_POST['robots_custom'] ) ) { // phpcs:ignore
			$lines = explode( "\n", wp_unslash( $_POST['robots_custom'] ) ); // phpcs:ignore
			foreach ( $lines as $l ) {
				$l = trim( $l );
				if ( $l ) { $custom[] = sanitize_text_field( $l ); }
			}
		}

		$sitemaps = array();
		if ( isset( $_POST['robots_extra_sitemaps'] ) ) { // phpcs:ignore
			$lines = explode( "\n", wp_unslash( $_POST['robots_extra_sitemaps'] ) ); // phpcs:ignore
			foreach ( $lines as $l ) {
				$l = trim( $l );
				if ( $l ) { $sitemaps[] = esc_url_raw( $l ); }
			}
		}

		$settings = get_option( $this->option_key, array() );
		$settings['robots_custom']         = $custom;
		$settings['robots_extra_sitemaps'] = $sitemaps;
		$settings['robots_block_media']    = isset( $_POST['robots_block_media'] ) ? (bool) absint( $_POST['robots_block_media'] ) : false; // phpcs:ignore

		update_option( $this->option_key, $settings );
		add_settings_error( 'keystone_robots', 'saved', __( 'Robots settings saved.', 'keystone-seo' ), 'updated' );
	}
}