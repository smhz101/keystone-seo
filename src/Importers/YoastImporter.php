<?php
namespace Keystone\Importers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Importers\Contracts\ImporterInterface;

/**
 * Imports metadata from Yoast SEO post meta.
 *
 * Sources (meta keys):
 * - _yoast_wpseo_title
 * - _yoast_wpseo_metadesc
 * - _yoast_wpseo_canonical
 * - _yoast_wpseo_meta-robots-noindex (1=noindex, 2=index)
 *
 * Refs:
 * - Yoast meta keys incl. robots noindex mapping. https://gofishdigital.com/blog/bulk-update-title-meta/ :contentReference[oaicite:0]{index=0}
 * - Canonical key context. https://webmasters.stackexchange.com/questions/138641/import-canonical-url-for-yoast-seo-using-database :contentReference[oaicite:1]{index=1}
 */
class YoastImporter implements ImporterInterface {

	public function slug() { return 'yoast'; }
	public function label() { return 'Yoast SEO'; }

	public function detected() {
		return defined( 'WPSEO_VERSION' ) || function_exists( 'YoastSEO' );
	}

	public function count() {
		global $wpdb;
		$sql = "
			SELECT COUNT(1)
			FROM {$wpdb->postmeta}
			WHERE meta_key IN ('_yoast_wpseo_title','_yoast_wpseo_metadesc','_yoast_wpseo_canonical','_yoast_wpseo_meta-robots-noindex')
		";
		return (int) $wpdb->get_var( $sql );
	}

	public function import( $limit = 200, $offset = 0 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare("
			SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			WHERE m.meta_key IN ('_yoast_wpseo_title','_yoast_wpseo_metadesc','_yoast_wpseo_canonical','_yoast_wpseo_meta-robots-noindex')
			GROUP BY p.ID
			ORDER BY p.ID ASC
			LIMIT %d OFFSET %d
		", $limit, $offset ), ARRAY_A );

		$imported = 0; $skipped = 0;

		foreach ( $rows as $r ) {
			$post_id = (int) $r['ID'];

			$title     = get_post_meta( $post_id, '_yoast_wpseo_title', true );
			$desc      = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
			$canonical = get_post_meta( $post_id, '_yoast_wpseo_canonical', true );
			$noindex   = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
			$noindex   = (string) $noindex === '1' ? 1 : 0; // 1=noindex, 2=index/empty

			$changed = 0;
			$changed += $this->set_meta( $post_id, 'keystone_title', $title );
			$changed += $this->set_meta( $post_id, 'keystone_description', $desc );
			$changed += $this->set_meta( $post_id, 'keystone_canonical', $canonical );
			$changed += $this->set_meta( $post_id, 'keystone_noindex', $noindex );

			if ( $changed ) { $imported++; } else { $skipped++; }
		}

		return array( 'imported' => $imported, 'skipped' => $skipped );
	}

	/**
	 * Wrapper to persist Keystone meta safely.
	 *
	 * @param int    $post_id
	 * @param string $key
	 * @param mixed  $value
	 * @return int 1 if updated, 0 otherwise
	 */
	protected function set_meta( $post_id, $key, $value ) {
		$value = is_string( $value ) ? trim( $value ) : $value;
		if ( '' === $value || null === $value ) { return 0; }
		/**
		 * Allow mapping Keystone keys to different storage.
		 *
		 * @param string $mapped_key
		 * @param string $key
		 */
		$mapped = apply_filters( 'keystone/import/map_meta_key', $key, $key );
		return update_post_meta( $post_id, $mapped, $value ) ? 1 : 0;
	}
}