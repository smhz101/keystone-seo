<?php
namespace Keystone\Monitor;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'convert_to_screen' ) ) {
	if ( file_exists( ABSPATH . 'wp-admin/includes/screen.php' ) ) {
		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}
}
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin list table for 404 logs.
 */
class NotFoundListTable extends \WP_List_Table {
	protected $repo;

	public function __construct( NotFoundRepository $repo ) {
		parent::__construct( array(
			'singular' => 'keystone_nf',
			'plural'   => 'keystone_nfs',
			'ajax'     => false,
		) );
		$this->repo = $repo;
	}

	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'path'      => __( 'Path', 'keystone-seo' ),
			'referrer'  => __( 'Referrer', 'keystone-seo' ),
			'hits'      => __( 'Hits', 'keystone-seo' ),
			'first'     => __( 'First Seen', 'keystone-seo' ),
			'last'      => __( 'Last Seen', 'keystone-seo' ),
		);
	}

	public function column_cb( $item ) {
		echo '<input type="checkbox" name="ids[]" value="' . esc_attr( $item['id'] ) . '" />';
	}

	public function column_path( $item ) {
		$actions = array(
			'delete' => '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'delete' => $item['id'] ) ), 'keystone_nf' ) ) . '">' . esc_html__( 'Delete', 'keystone-seo' ) . '</a>',
		);
		echo '<code>' . esc_html( $item['path'] ) . '</code> ' . $this->row_actions( $actions );
	}

	public function column_referrer( $item ) {
		if ( empty( $item['referrer'] ) ) {
			echo '&mdash;';
			return;
		}
		echo '<a href="' . esc_url( $item['referrer'] ) . '" target="_blank" rel="noopener">' . esc_html( $item['referrer'] ) . '</a>';
	}

	public function column_hits( $item ) {
		echo esc_html( (string) $item['hits'] );
	}

	public function column_first( $item ) {
		echo esc_html( $item['first_seen'] );
	}

	public function column_last( $item ) {
		echo esc_html( $item['last_seen'] );
	}

	public function get_bulk_actions() {
		return array( 'delete' => __( 'Delete', 'keystone-seo' ) );
	}

	public function prepare_items() {
		$per_page = 20;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore
		$result   = $this->repo->all( $paged, $per_page );

		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items           = $result['items'];

		$this->set_pagination_args( array(
			'total_items' => $result['total'],
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $result['total'] / $per_page ),
		) );
	}
}