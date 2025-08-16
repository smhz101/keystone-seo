<?php
namespace Keystone\Schema\Providers;

use Keystone\Schema\Contracts\SchemaProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Basic Article schema for posts.
 */
class ArticleProvider implements SchemaProviderInterface {
	public function nodes( $context ) {
		if ( empty( $context['is_singular'] ) || 'post' !== get_post_type( $context['post_id'] ) ) {
			return array();
		}

		$id    = $context['post_id'];
		$url   = get_permalink( $id );
		$title = get_the_title( $id );
		$img   = get_the_post_thumbnail_url( $id, 'full' );

		$node = array(
			'@type'        => 'Article',
			'@id'          => trailingslashit( $url ) . '#article',
			'mainEntityOfPage' => $url,
			'headline'     => $title,
			'datePublished'=> get_the_date( 'c', $id ),
			'dateModified' => get_post_modified_time( 'c', true, $id ),
			'author'       => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ),
			),
		);

		if ( $img ) {
			$node['image'] = array( $img );
		}

		return array( $node );
	}
}
