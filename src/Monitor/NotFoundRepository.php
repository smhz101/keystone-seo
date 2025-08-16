<?php
namespace Keystone\Monitor;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Tracks 404 hits for audit/crawl management.
 *
 * Table: wp_keystone_404s
 */
class NotFoundRepository {
	protected $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'keystone_404s';
	}

	/** Create/upgrade table */
	public function migrate() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$this->table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			path VARCHAR(255) NOT NULL,
			referrer TEXT NULL,
			hits BIGINT UNSIGNED NOT NULL DEFAULT 1,
			first_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_path (path),
			KEY idx_hits (hits),
			KEY idx_last_seen (last_seen)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/** Log a 404 hit */
	public function log( $path, $referrer ) {
		global $wpdb;
		$path = '/' . ltrim( (string) $path, '/' );
		$ref  = (string) $referrer;

		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table} WHERE path=%s", $path ) );
		if ( $existing ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$this->table} SET hits=hits+1, referrer=%s WHERE id=%d", $ref, $existing ) );
		} else {
			$wpdb->insert( $this->table, array( 'path' => $path, 'referrer' => $ref ), array( '%s', '%s' ) );
		}
	}

	/** Paginated list for admin table */
	public function all( $paged = 1, $per_page = 20 ) {
		global $wpdb;
		$paged    = max( 1, absint( $paged ) );
		$per_page = max( 1, absint( $per_page ) );
		$offset   = ( $paged - 1 ) * $per_page;

		$list  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY hits DESC, last_seen DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );

		return array( 'items' => $list, 'total' => $total );
	}

	/** Top N (for CLI) */
	public function top( $limit = 50 ) {
		global $wpdb;
		$limit = max( 1, absint( $limit ) );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY hits DESC, last_seen DESC LIMIT %d", $limit ), ARRAY_A );
	}

	/** Delete one row */
	public function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/** Bulk delete */
	public function bulk_delete( $ids ) {
		global $wpdb;
		$ids = array_map( 'absint', (array) $ids );
		if ( empty( $ids ) ) { return 0; }
		$in = '(' . implode( ',', $ids ) . ')';
		return (int) $wpdb->query( "DELETE FROM {$this->table} WHERE id IN {$in}" );
	}

	/** Clear table */
	public function clear() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$this->table}" );
	}
}