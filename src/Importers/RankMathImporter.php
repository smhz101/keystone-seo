<?php
namespace Keystone\Importers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Importers\Contracts\ImporterInterface;

/**
 * Imports metadata from Rank Math post meta.
 *
 * Known keys:
 * - rank_math_title
 * - rank_math_description
 * - rank_math_canonical_url
 * - (robots may be stored in rank_math_robots or via settings UI)
 *
 * Refs:
 * - Official support confirming title/description keys. https://wordpress.org/support/topic/meta-key-names-postmeta-for-title-description-etc/ :contentReference[oaicite:2]{index=2}
 * - Canonical key used by importers/tools. https://www.smackcoders.com/documentation/wp-ultimate-csv-importer-pro/rankmath-data-import-and-export :contentReference[oaicite:3]{index=3}
 * - How to noindex in Rank Math (behavior). https://rankmath.com/kb/how-to-noindex-urls/ :contentReference[oaicite:4]{index=4}
 */
class RankMathImporter implements ImporterInterface {

	public function slug() { return 'rankmath'; }
	public function label() { return 'Rank Math'; }

	public function detected() {
		return defined( 'RANK_MATH_VERSION' ) || function_exists( 'rank_math' );
	}

	public function count() {
		global $wpdb;
		$sql = "
			SELECT COUNT(1)
			FROM {$wpdb->postmeta}
			WHERE meta_key IN ('rank_math_title','rank_math_description','rank_math_canonical_url','rank_math_robots')
		";
		return (int) $wpdb->get_var( $sql );
	}

	public function import( $limit = 200, $offset = 0 ) {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare("
			SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			WHERE m.meta_key IN ('rank_math_title','rank_math_description','rank_math_canonical_url','rank_math_robots')
			GROUP BY p.ID
			ORDER BY p.ID ASC
			LIMIT %d OFFSET %d
		", $limit, $offset ), ARRAY_A );

		$imported = 0; $skipped = 0;

		foreach ( $rows as $r ) {
			$post_id   = (int) $r['ID'];
			$title     = get_post_meta( $post_id, 'rank_math_title', true );
			$desc      = get_post_meta( $post_id, 'rank_math_description', true );
			$canonical = get_post_meta( $post_id, 'rank_math_canonical_url', true );

			// Robots storage varies; try to infer boolean noindex.
			$robots = get_post_meta( $post_id, 'rank_math_robots', true );
			$noindex = 0;
			if ( is_array( $robots ) ) {
				$noindex = in_array( 'noindex', $robots, true ) ? 1 : 0;
			} elseif ( is_string( $robots ) ) {
				$noindex = ( false !== stripos( $robots, 'noindex' ) ) ? 1 : 0;
			}

			$changed  = 0;
			$changed += $this->set_meta( $post_id, 'keystone_title', $title );
			$changed += $this->set_meta( $post_id, 'keystone_description', $desc );
			$changed += $this->set_meta( $post_id, 'keystone_canonical', $canonical );
			$changed += $this->set_meta( $post_id, 'keystone_noindex', $noindex );

			if ( $changed ) { $imported++; } else { $skipped++; }
		}

		return array( 'imported' => $imported, 'skipped' => $skipped );
	}

	protected function set_meta( $post_id, $key, $value ) {
		$value = is_string( $value ) ? trim( $value ) : $value;
		if ( '' === $value || null === $value ) { return 0; }
		$mapped = apply_filters( 'keystone/import/map_meta_key', $key, $key );
		return update_post_meta( $post_id, $mapped, $value ) ? 1 : 0;
	}
}