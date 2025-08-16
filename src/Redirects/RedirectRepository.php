<?php
namespace Keystone\Redirects;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Data access for redirects: CRUD, migration, queries.
 *
 * Table: wp_keystone_redirects
 */
class RedirectRepository {

	protected $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'keystone_redirects';
	}

	/** Create/upgrade table */
	public function migrate() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$this->table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source VARCHAR(255) NOT NULL,
			target VARCHAR(255) NOT NULL,
			status SMALLINT UNSIGNED NOT NULL DEFAULT 301,
			regex TINYINT(1) NOT NULL DEFAULT 0,
			hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_source (source),
			KEY idx_regex (regex),
			KEY idx_hits (hits)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/** Create */
	public function create( $source, $target, $status = 301, $regex = 0 ) {
		global $wpdb;
		$source = '/' . ltrim( (string) $source, '/' );
		$target = (string) $target;
		$wpdb->insert(
			$this->table,
			array(
				'source' => $source,
				'target' => $target,
				'status' => absint( $status ),
				'regex'  => absint( $regex ),
			),
			array( '%s', '%s', '%d', '%d' )
		);
		return (int) $wpdb->insert_id;
	}

	/** Update */
	public function update( $id, $data ) {
		global $wpdb;
		$fields = array();
		$types  = array();

		if ( isset( $data['source'] ) ) { $fields['source'] = '/' . ltrim( (string) $data['source'], '/' ); $types[] = '%s'; }
		if ( isset( $data['target'] ) ) { $fields['target'] = (string) $data['target']; $types[] = '%s'; }
		if ( isset( $data['status'] ) ) { $fields['status'] = absint( $data['status'] ); $types[] = '%d'; }
		if ( isset( $data['regex'] ) )  { $fields['regex']  = absint( $data['regex'] );  $types[] = '%d'; }

		if ( empty( $fields ) ) { return false; }

		return (bool) $wpdb->update( $this->table, $fields, array( 'id' => absint( $id ) ), $types, array( '%d' ) );
	}

	/** Delete */
	public function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/** Get single */
	public function get( $id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id=%d", absint( $id ) );
		return $wpdb->get_row( $sql, ARRAY_A );
	}

	/** List with pagination */
	public function all( $paged = 1, $per_page = 20 ) {
		global $wpdb;
		$paged    = max( 1, absint( $paged ) );
		$per_page = max( 1, absint( $per_page ) );
		$offset   = ( $paged - 1 ) * $per_page;

		$sql  = $wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY updated_at DESC LIMIT %d OFFSET %d", $per_page, $offset );
		$list = $wpdb->get_results( $sql, ARRAY_A );

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );

		return array( 'items' => $list, 'total' => $total );
	}

	/** Find by path, exact then regex */
	public function find_by_path( $path ) {
		global $wpdb;
		$path  = (string) $path;
		$sql   = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE regex=0 AND source=%s LIMIT 1", $path );
		$exact = $wpdb->get_row( $sql, ARRAY_A );
		if ( $exact ) { return $exact; }

		$rules = $wpdb->get_results( "SELECT * FROM {$this->table} WHERE regex=1", ARRAY_A );
		foreach ( $rules as $rule ) {
			$pattern = '#' . str_replace( '#', '\#', $rule['source'] ) . '#';
			if ( preg_match( $pattern, $path ) ) { return $rule; }
		}
		return null;
	}

	/** Increment hits counter */
	public function bump_hits( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table} SET hits = hits + 1 WHERE id=%d", absint( $id ) ) );
	}

	/** Bulk delete */
	public function bulk_delete( $ids ) {
		global $wpdb;
		$ids = array_map( 'absint', (array) $ids );
		if ( empty( $ids ) ) { return 0; }
		$in = '(' . implode( ',', $ids ) . ')';
		return (int) $wpdb->query( "DELETE FROM {$this->table} WHERE id IN {$in}" );
	}

	/** CSV import: source,target,status,regex */
	public function import_csv( $path ) {
		if ( ! file_exists( $path ) || ! is_readable( $path ) ) { return 0; }
		$fh = fopen( $path, 'r' );
		$cnt = 0;
		if ( $fh ) {
			while ( ( $row = fgetcsv( $fh ) ) !== false ) {
				if ( count( $row ) < 2 ) { continue; }
				$src = '/' . ltrim( sanitize_text_field( $row[0] ), '/' );
				$tgt = esc_url_raw( $row[1] );
				$st  = isset( $row[2] ) ? absint( $row[2] ) : 301;
				$rx  = isset( $row[3] ) ? absint( $row[3] ) : 0;
				try {
					$this->create( $src, $tgt, $st, $rx );
					$cnt++;
				} catch ( \Throwable $e ) { /* ignore dupes */ }
			}
			fclose( $fh );
		}
		return $cnt;
	}

	/** Export all as array for CSV */
	public function export_all() {
		global $wpdb;
		return $wpdb->get_results( "SELECT source,target,status,regex,hits,updated_at FROM {$this->table} ORDER BY source ASC", ARRAY_A );
	}
}