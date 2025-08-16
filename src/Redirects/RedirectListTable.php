<?php
namespace Keystone\Redirects;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin list table for redirects.
 */
class RedirectListTable extends \WP_List_Table {
	protected $repo;

	public function __construct( RedirectRepository $repo ) {
		parent::__construct( array(
			'singular' => 'keystone_redirect',
			'plural'   => 'keystone_redirects',
			'ajax'     => false,
		) );
		$this->repo = $repo;
	}

	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'source'  => __( 'Source', 'keystone-seo' ),
			'target'  => __( 'Target', 'keystone-seo' ),
			'status'  => __( 'Status', 'keystone-seo' ),
			'regex'   => __( 'Regex', 'keystone-seo' ),
			'hits'    => __( 'Hits', 'keystone-seo' ),
			'updated' => __( 'Updated', 'keystone-seo' ),
		);
	}

	public function column_cb( $item ) {
		echo '<input type="checkbox" name="ids[]" value="' . esc_attr( $item['id'] ) . '" />';
	}

	public function column_source( $item ) {
		$edit = add_query_arg( array( 'edit' => $item['id'] ) );
		$actions = array(
			'edit'   => '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'keystone-seo' ) . '</a>',
			'delete' => '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'delete' => $item['id'] ) ), 'keystone_redirects' ) ) . '">' . esc_html__( 'Delete', 'keystone-seo' ) . '</a>',
		);
		echo '<strong>' . esc_html( $item['source'] ) . '</strong> ' . $this->row_actions( $actions );
	}

	public function column_target( $item ) {
		echo '<a href="' . esc_url( $item['target'] ) . '" target="_blank" rel="noopener">' . esc_html( $item['target'] ) . '</a>';
	}

	public function column_status( $item ) {
		echo esc_html( $item['status'] );
	}

	public function column_regex( $item ) {
		echo $item['regex'] ? '&#10003;' : '&mdash;';
	}

	public function column_hits( $item ) {
		echo esc_html( (string) $item['hits'] );
	}

	public function column_updated( $item ) {
		echo esc_html( $item['updated_at'] );
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
