<?php
namespace Keystone\Schema\Providers;

use Keystone\Schema\Contracts\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Outputs Article (for posts) or WebPage (for pages) on singular views.
 * https://developers.google.com/search/docs/appearance/structured-data/article
 */
class ArticleProvider implements ProviderInterface {

	public function nodes() {
		if ( ! is_singular() ) { return array(); }

		$post_id = get_queried_object_id();
		$perma   = get_permalink( $post_id );
		$site    = home_url( '/' );
		$org_id  = trailingslashit( $site ) . '#organization';
		$web_id  = trailingslashit( $site ) . '#website';

		$is_post = ( 'post' === get_post_type( $post_id ) );
		$type    = $is_post ? 'Article' : 'WebPage';
		$id      = trailingslashit( $perma ) . '#main';

		$img = get_the_post_thumbnail_url( $post_id, 'full' );
		$img_obj = $img ? array( '@type' => 'ImageObject', 'url' => $img ) : null;

		$node = array(
			'@type'            => $type,
			'@id'              => $id,
			'mainEntityOfPage' => $perma,
			'headline'         => get_the_title( $post_id ),
			'datePublished'    => get_post_time( 'c', true, $post_id ),
			'dateModified'     => get_post_modified_time( 'c', true, $post_id ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
			),
			'publisher'        => array( '@id' => $org_id ),
			'isPartOf'         => array( '@id' => $web_id ),
		);

		if ( $img_obj ) { $node['image'] = $img_obj; }

		/** Allow addons to enrich the node (e.g., articleBody, keywords). */
		$node = apply_filters( 'keystone/schema/article', $node, $post_id );

		return array( $node );
	}
}