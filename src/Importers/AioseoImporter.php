<?php
namespace Keystone\Importers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Importers\Contracts\ImporterInterface;

/**
 * Imports metadata from All in One SEO (AIOSEO).
 *
 * AIOSEO v4+ stores data in custom tables (aioseo_posts / aioseo_terms) and
 * duplicates some meta into postmeta for compatibility (one-way).
 *
 * Refs:
 * - AIOSEO stores in custom tables `aioseo_posts`/`aioseo_terms`. https://aioseo.com/docs/localizing-aioseo-data-via-the-translations-api/ :contentReference[oaicite:5]{index=5}
 * - They also mirror to postmeta for multilanguage compat (one-way). https://wordpress.org/support/topic/meta-description-update-query/ :contentReference[oaicite:6]{index=6}
 * - Common postmeta keys still seen: _aioseo_title, _aioseo_description, _aioseo_keywords. https://wordpress.org/support/topic/meta-description-update-query/ :contentReference[oaicite:7]{index=7}
 */
class AioseoImporter implements ImporterInterface {

	public function slug() { return 'aioseo'; }
	public function label() { return 'All in One SEO'; }

	public function detected() {
		return function_exists( 'aioseo' ) || defined( 'AIOSEO_VERSION' );
	}

	public function count() {
		global $wpdb;
		if ( $this->has_table( $wpdb->prefix . 'aioseo_posts' ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$wpdb->prefix}aioseo_posts" );
		}
		$sql = "
			SELECT COUNT(1)
			FROM {$wpdb->postmeta}
			WHERE meta_key IN ('_aioseo_title','_aioseo_description','_aioseo_canonical_url')
		";
		return (int) $wpdb->get_var( $sql );
	}

	public function import( $limit = 200, $offset = 0 ) {
		global $wpdb;

		$imported = 0; $skipped = 0;

		if ( $this->has_table( $wpdb->prefix . 'aioseo_posts' ) ) {
			$rows = $wpdb->get_results( $wpdb->prepare("
				SELECT post_id, title, description, canonical_url, robots_default, robots_noindex
				FROM {$wpdb->prefix}aioseo_posts
				ORDER BY post_id ASC
				LIMIT %d OFFSET %d
			", $limit, $offset ), ARRAY_A );

			foreach ( $rows as $row ) {
				$post_id   = (int) $row['post_id'];
				$title     = isset( $row['title'] ) ? $row['title'] : '';
				$desc      = isset( $row['description'] ) ? $row['description'] : '';
				$canonical = isset( $row['canonical_url'] ) ? $row['canonical_url'] : '';
				$noindex   = ! empty( $row['robots_noindex'] ) ? 1 : 0;

				$changed  = 0;
				$changed += $this->set_meta( $post_id, 'keystone_title', $title );
				$changed += $this->set_meta( $post_id, 'keystone_description', $desc );
				$changed += $this->set_meta( $post_id, 'keystone_canonical', $canonical );
				$changed += $this->set_meta( $post_id, 'keystone_noindex', $noindex );

				if ( $changed ) { $imported++; } else { $skipped++; }
			}
		} else {
			// Fallback to mirrored postmeta keys.
			$posts = $wpdb->get_results( $wpdb->prepare("
				SELECT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
				WHERE m.meta_key IN ('_aioseo_title','_aioseo_description','_aioseo_canonical_url')
				GROUP BY p.ID
				ORDER BY p.ID ASC
				LIMIT %d OFFSET %d
			", $limit, $offset ), ARRAY_A );

			foreach ( $posts as $r ) {
				$post_id   = (int) $r['ID'];
				$title     = get_post_meta( $post_id, '_aioseo_title', true );
				$desc      = get_post_meta( $post_id, '_aioseo_description', true );
				$canonical = get_post_meta( $post_id, '_aioseo_canonical_url', true );

				$changed  = 0;
				$changed += $this->set_meta( $post_id, 'keystone_title', $title );
				$changed += $this->set_meta( $post_id, 'keystone_description', $desc );
				$changed += $this->set_meta( $post_id, 'keystone_canonical', $canonical );

				if ( $changed ) { $imported++; } else { $skipped++; }
			}
		}

		return array( 'imported' => $imported, 'skipped' => $skipped );
	}
	
	protected function has_table( $table ) {
		global $wpdb;
		$val = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		// If table is missing, $val is null; compare safely.
		return is_string( $val ) && strcasecmp( $val, (string) $table ) === 0;
	}

	protected function set_meta( $post_id, $key, $value ) {
		$value = is_string( $value ) ? trim( $value ) : $value;
		if ( '' === $value || null === $value ) { return 0; }
		$mapped = apply_filters( 'keystone/import/map_meta_key', $key, $key );
		return update_post_meta( $post_id, $mapped, $value ) ? 1 : 0;
	}
}