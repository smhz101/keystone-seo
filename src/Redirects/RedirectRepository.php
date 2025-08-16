<?php
namespace Keystone\Redirects;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Data access layer for redirects (normalized table).
 *
 * @since 0.1.0
 */
class RedirectRepository {
	/** @var string */
	protected $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'keystone_redirects';
	}

	/**
	 * Create table if missing (called on activation).
	 *
	 * @return void
	 */
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
			INDEX source_idx (source),
			INDEX regex_idx (regex)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Find a matching redirect by path.
	 *
	 * @param string $path Request path (no domain, leading slash included).
	 * @return array|null
	 */
	public function find_by_path( $path ) {
		global $wpdb;

		// Exact match first.
		$sql   = $wpdb->prepare( "SELECT * FROM {$this->table} WHERE regex = 0 AND source = %s LIMIT 1", $path );
		$exact = $wpdb->get_row( $sql, ARRAY_A );
		if ( $exact ) {
			return $exact;
		}

		// Regex rules (basic).
		$rules = $wpdb->get_results( "SELECT * FROM {$this->table} WHERE regex = 1", ARRAY_A );
		foreach ( $rules as $rule ) {
			$pattern = '#' . str_replace( '#', '\#', $rule['source'] ) . '#';
			if ( preg_match( $pattern, $path ) ) {
				return $rule;
			}
		}
		return null;
	}

	/**
	 * Increment hits.
	 *
	 * @param int $id Rule ID.
	 * @return void
	 */
	public function bump_hits( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table} SET hits = hits + 1 WHERE id = %d", absint( $id ) ) );
	}
}