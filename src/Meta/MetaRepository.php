<?php
namespace Keystone\Meta;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Repository for per-post Keystone SEO meta.
 * Keys:
 *  - _keystone_title
 *  - _keystone_description
 *  - _keystone_canonical
 *  - _keystone_noindex (bool '1'|'0')
 *
 * @since 0.1.0
 */
class MetaRepository {
	/** Get all meta as normalized array. */
	public function get( $post_id ) {
		return array(
			'title'       => (string) get_post_meta( $post_id, '_keystone_title', true ),
			'description' => (string) get_post_meta( $post_id, '_keystone_description', true ),
			'canonical'   => (string) get_post_meta( $post_id, '_keystone_canonical', true ),
			'noindex'     => (bool)   get_post_meta( $post_id, '_keystone_noindex', true ),
		);
	}

	/** Persist a subset of fields (sanitized beforehand). */
	public function save( $post_id, $data ) {
		if ( isset( $data['title'] ) ) {
			update_post_meta( $post_id, '_keystone_title', (string) $data['title'] );
		}
		if ( isset( $data['description'] ) ) {
			update_post_meta( $post_id, '_keystone_description', (string) $data['description'] );
		}
		if ( isset( $data['canonical'] ) ) {
			update_post_meta( $post_id, '_keystone_canonical', (string) $data['canonical'] );
		}
		if ( isset( $data['noindex'] ) ) {
			update_post_meta( $post_id, '_keystone_noindex', $data['noindex'] ? '1' : '0' );
		}
	}
}