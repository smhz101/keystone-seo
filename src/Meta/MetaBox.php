<?php
namespace Keystone\Meta;

use Keystone\Security\Capabilities;
use Keystone\Security\Nonce;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Adds the "Keystone SEO" meta box to post types and saves values.
 *
 * @since 0.1.0
 */
class MetaBox {
	protected $caps;
	protected $nonce;
	protected $repo;

	/** @var string[] post types to attach to (filterable). */
	protected $post_types = array( 'post', 'page' );

	public function __construct( Capabilities $caps, Nonce $nonce, MetaRepository $repo ) {
		$this->caps  = $caps;
		$this->nonce = $nonce;
		$this->repo  = $repo;
	}

	/** Register box. */
	public function add_meta_boxes() {
		$types = apply_filters( 'keystone/meta/post_types', $this->post_types );
		foreach ( (array) $types as $pt ) {
			add_meta_box(
				'keystone-seo-box',
				__( 'Keystone SEO', 'keystone-seo' ),
				array( $this, 'render' ),
				$pt,
				'side',
				'high'
			);
		}
	}

	/** Render controls (kept minimal for speed). */
	public function render( $post ) {
		if ( ! $this->caps->can_manage_settings() ) { echo esc_html__( 'Insufficient permissions.', 'keystone-seo' ); return; }
		// Dedicated nonce for meta box.
		wp_nonce_field( 'keystone_seo_metabox', '_keystone_seo_metabox' );

		$meta = $this->repo->get( $post->ID );
		?>
		<p>
			<label for="kseo_title"><strong><?php esc_html_e( 'SEO Title', 'keystone-seo' ); ?></strong></label><br/>
			<input type="text" id="kseo_title" name="kseo_title" class="widefat" maxlength="150" value="<?php echo esc_attr( $meta['title'] ); ?>" />
			<small><?php esc_html_e( 'Overrides template. Tokens not supported here.', 'keystone-seo' ); ?></small>
		</p>
		<p>
			<label for="kseo_desc"><strong><?php esc_html_e( 'Meta Description', 'keystone-seo' ); ?></strong></label><br/>
			<textarea id="kseo_desc" name="kseo_desc" class="widefat" rows="3" maxlength="300"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
		</p>
		<p>
			<label for="kseo_canonical"><strong><?php esc_html_e( 'Canonical URL', 'keystone-seo' ); ?></strong></label><br/>
			<input type="url" id="kseo_canonical" name="kseo_canonical" class="widefat" value="<?php echo esc_attr( $meta['canonical'] ); ?>" />
		</p>
		<p>
			<label><input type="checkbox" name="kseo_noindex" value="1" <?php checked( $meta['noindex'], true ); ?> />
			<?php esc_html_e( 'Noindex this content', 'keystone-seo' ); ?></label>
		</p>
		<?php
	}

	/** Save handler with capability + nonce + sanitize. */
	public function save_post( $post_id ) {
		// Bail on autosave/cron/revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( wp_is_post_revision( $post_id ) ) { return; }

		if ( ! $this->caps->can_manage_settings() ) { return; }
		if ( ! isset( $_POST['_keystone_seo_metabox'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_keystone_seo_metabox'] ) ), 'keystone_seo_metabox' ) ) { // phpcs:ignore
			return;
		}

		$data = array(
			'title'       => isset( $_POST['kseo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kseo_title'] ) ) : '',
			'description' => isset( $_POST['kseo_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kseo_desc'] ) ) : '',
			'canonical'   => isset( $_POST['kseo_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['kseo_canonical'] ) ) : '',
			'noindex'     => isset( $_POST['kseo_noindex'] ) ? (bool) absint( $_POST['kseo_noindex'] ) : false,
		);

		$this->repo->save( $post_id, $data );
	}
}