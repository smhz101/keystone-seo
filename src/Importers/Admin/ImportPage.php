<?php
namespace Keystone\Importers\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Importers\ImportManager;

/**
 * Admin UI to run imports on demand with AJAX batching.
 *
 * - Page: Tools under "Keystone SEO" menu.
 * - AJAX: action=keystone_import_batch (nonce: _ks_imp)
 * - No anonymous callbacks in hooks.
 */
class ImportPage {

	/** @var ImportManager */
	protected $manager;

	/** @var string */
	protected $cap = 'manage_options';

	/** @var string */
	protected $page_slug = 'keystone-import';

	public function __construct( ImportManager $manager ) {
		$this->manager = $manager;
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_keystone_run_import', array( $this, 'handle_run' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_keystone_import_batch', array( $this, 'ajax_import_batch' ) );
	}

	public function register_menu() {
		add_submenu_page(
			'keystone-seo',
			__( 'Import from Other SEO Plugins', 'keystone-seo' ),
			__( 'Import', 'keystone-seo' ),
			$this->cap,
			$this->page_slug,
			array( $this, 'render' )
		);
	}

	public function enqueue_assets( $hook ) {
		// Load only on our page.
		if ( 'keystone-seo_page_' . $this->page_slug !== $hook ) { return; }

		// jQuery is present in WP admin.
		wp_register_script( 'keystone-import-js', false, array( 'jquery' ), '1.0', true );
		$payload = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'keystone_import_batch' ),
		);
		wp_localize_script( 'keystone-import-js', 'KS_IMPORT', $payload );
		wp_enqueue_script( 'keystone-import-js' );

		$inline = <<<JS
jQuery(function($){
	function runBatch(\$row, slug, total, limit, offset, imported, skipped){
		\$row.find('.ks-status').text('Running…');
		$.post(KS_IMPORT.ajax_url,{
			action: 'keystone_import_batch',
			_wpnonce: KS_IMPORT.nonce,
			slug: slug,
			limit: limit,
			offset: offset
		}).done(function(resp){
			if(!resp || !resp.success){ 
				\$row.find('.ks-status').text('Error');
				return;
			}
			var d = resp.data || {};
			imported += parseInt(d.imported || 0,10);
			skipped  += parseInt(d.skipped || 0,10);
			offset   = parseInt(d.next_offset || 0,10);

			var done  = !!d.done;
			var pct   = total > 0 ? Math.min(100, Math.floor( (offset/total)*100 )) : 100;

			\$row.find('.ks-progress-bar').css('width', pct+'%').attr('aria-valuenow', pct);
			\$row.find('.ks-progress-text').text(pct+'%');
			\$row.find('.ks-imported').text(imported);
			\$row.find('.ks-skipped').text(skipped);

			if(done){
				\$row.find('.ks-status').text('Done');
				\$row.find('.ks-start').prop('disabled', false);
			}else{
				runBatch(\$row, slug, total, limit, offset, imported, skipped);
			}
		}).fail(function(){
			\$row.find('.ks-status').text('Error');
			\$row.find('.ks-start').prop('disabled', false);
		});
	}

	$('.ks-start').on('click', function(e){
		e.preventDefault();
		var \$row = $(this).closest('tr');
		var slug  = $(this).data('slug');
		var total = parseInt(\$row.data('count'),10) || 0;
		var limit = parseInt($('#ks-limit').val(),10) || 200;

		$(this).prop('disabled', true);
		\$row.find('.ks-progress-wrap').show();
		\$row.find('.ks-progress-bar').css('width','0%').attr('aria-valuenow',0);
		\$row.find('.ks-progress-text').text('0%');
		\$row.find('.ks-imported').text('0');
		\$row.find('.ks-skipped').text('0');

		runBatch(\$row, slug, total, limit, 0, 0, 0);
	});
});
JS;
		wp_add_inline_script( 'keystone-import-js', $inline, 'after' );

		// Minimal inline CSS for progress bar.
		$css = '.ks-progress-wrap{display:none;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:4px;overflow:hidden;width:240px;height:16px;position:relative}
.ks-progress-bar{background:#2271b1;height:100%;width:0%;transition:width .2s}
.ks-progress-text{position:absolute;left:50%;top:0;transform:translateX(-50%);font-size:11px;line-height:16px;color:#fff}
.ks-metrics{font-size:11px;color:#555}';
		wp_add_inline_style( 'wp-admin', $css );
	}

	public function render() {
		if ( ! current_user_can( $this->cap ) ) { return; }

		$importers = $this->manager->all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import SEO Data', 'keystone-seo' ); ?></h1>
			<p><?php esc_html_e( 'Migrate titles, descriptions, canonicals, and index flags into Keystone. Use AJAX runner for large sites.', 'keystone-seo' ); ?></p>

			<p>
				<label for="ks-limit"><strong><?php esc_html_e( 'Batch size', 'keystone-seo' ); ?>:</strong></label>
				<input type="number" id="ks-limit" min="50" step="50" value="200" />
				<span class="description"><?php esc_html_e( 'Higher = faster but heavier per request.', 'keystone-seo' ); ?></span>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plugin', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Detected', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Items', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Status', 'keystone-seo' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'keystone-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $importers as $imp ) :
					$det = $imp->detected();
					$cnt = (int) $imp->count();
					?>
					<tr data-count="<?php echo esc_attr( (string) $cnt ); ?>">
						<td><strong><?php echo esc_html( $imp->label() ); ?></strong> <code><?php echo esc_html( $imp->slug() ); ?></code></td>
						<td><?php echo $det ? '&#10003;' : '&mdash;'; ?></td>
						<td><?php echo esc_html( (string) $cnt ); ?></td>
						<td class="ks-status"><?php esc_html_e( 'Idle', 'keystone-seo' ); ?></td>
						<td>
							<div style="display:flex;align-items:center;gap:8px;">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="keystone_run_import" />
									<input type="hidden" name="slug" value="<?php echo esc_attr( $imp->slug() ); ?>" />
									<?php wp_nonce_field( 'keystone_run_import', '_ks_nonce' ); ?>
									<?php submit_button( __( 'Run (Single Batch 200)', 'keystone-seo' ), 'secondary', 'submit', false ); ?>
								</form>

								<button type="button" class="button button-primary ks-start" data-slug="<?php echo esc_attr( $imp->slug() ); ?>" <?php disabled( ! $det ); ?>>
									<?php esc_html_e( 'Run via AJAX', 'keystone-seo' ); ?>
								</button>

								<div class="ks-progress-wrap" aria-label="<?php esc_attr_e( 'Progress', 'keystone-seo' ); ?>">
									<div class="ks-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
									<span class="ks-progress-text">0%</span>
								</div>
								<span class="ks-metrics">
									<?php esc_html_e( 'Imported', 'keystone-seo' ); ?>: <span class="ks-imported">0</span>,
									<?php esc_html_e( 'Skipped', 'keystone-seo' ); ?>: <span class="ks-skipped">0</span>
								</span>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<input type="hidden" id="ks-imp-nonce" value="<?php echo esc_attr( wp_create_nonce( 'keystone_import_batch' ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Legacy single-batch submit (kept for parity).
	 */
	public function handle_run() {
		if ( ! current_user_can( $this->cap ) ) { wp_die( -1 ); }
		check_admin_referer( 'keystone_run_import', '_ks_nonce' );

		$slug  = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$res   = $this->manager->run( $slug, 200, 0 );
		$query = array(
			'page'      => $this->page_slug,
			'keystone'  => 'done',
			'slug'      => $slug,
			'i'         => (int) $res['imported'],
			's'         => (int) $res['skipped'],
		);
		wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * AJAX: run batch and return progress.
	 * POST: slug, limit, offset
	 * Resp: { imported, skipped, next_offset, done, total }
	 */
	public function ajax_import_batch() {
		if ( ! current_user_can( $this->cap ) ) { wp_send_json_error(); }
		check_ajax_referer( 'keystone_import_batch' );

		$slug   = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : ''; // phpcs:ignore
		$limit  = isset( $_POST['limit'] ) ? max( 50, absint( $_POST['limit'] ) ) : 200; // phpcs:ignore
		$offset = isset( $_POST['offset'] ) ? max( 0, absint( $_POST['offset'] ) ) : 0; // phpcs:ignore

		$imp = $this->manager->get( $slug );
		if ( ! $imp ) {
			wp_send_json_error( array( 'message' => 'Invalid importer.' ) );
		}

		$total = (int) $imp->count();

		$res = $imp->import( $limit, $offset );
		$next_offset = $offset + $limit;
		$done = ( $next_offset >= $total );

		wp_send_json_success( array(
			'imported'    => (int) $res['imported'],
			'skipped'     => (int) $res['skipped'],
			'next_offset' => $done ? $total : $next_offset,
			'done'        => $done,
			'total'       => $total,
		) );
	}
}