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
			UNIQUE KEY uniq_path (path)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

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

	public function top( $limit = 50 ) {
		global $wpdb;
		$limit = max( 1, absint( $limit ) );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY hits DESC, last_seen DESC LIMIT %d", $limit ), ARRAY_A );
	}
}