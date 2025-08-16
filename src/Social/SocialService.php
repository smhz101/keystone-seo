<?php
namespace Keystone\Social;

use Keystone\Meta\MetaRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Outputs Open Graph + Twitter Cards.
 * Priority: per-post meta > featured image > default image > generated OG
 */
class SocialService {
	/** @var array */
	protected $settings = array();

	/** @var MetaRepository */
	protected $repo;

	public function __construct( $settings = array(), MetaRepository $repo = null ) {
		$this->settings = is_array( $settings ) ? $settings : array();
		$this->repo     = $repo ?: new MetaRepository();
	}

	/** Hook: wp_head */
	public function output() {
		$title = wp_get_document_title();
		$desc  = $this->resolve_description();
		$url   = $this->resolve_url();
		$type  = is_singular() ? ( 'product' === get_post_type() ? 'product' : 'article' ) : 'website';
		$image = $this->resolve_image();

		echo "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}

		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		if ( $image ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		}
	}

	/** Resolve description: per-post > site tagline */
	protected function resolve_description() {
		if ( is_singular() ) {
			$meta = $this->repo->get( get_queried_object_id() );
			if ( ! empty( $meta['description'] ) ) {
				return $meta['description'];
			}
		}
		return (string) get_bloginfo( 'description', 'display' );
	}

	/** Resolve canonical URL or current */
	protected function resolve_url() {
		if ( is_singular() ) {
			$meta = $this->repo->get( get_queried_object_id() );
			if ( ! empty( $meta['canonical'] ) ) {
				return $meta['canonical'];
			}
			return get_permalink( get_queried_object_id() );
		}
		return (string) home_url( add_query_arg( null, null ) );
	}

	/** Resolve image with graceful fallbacks */
	protected function resolve_image() {
		// 1) Featured image on singular.
		if ( is_singular() ) {
			$img = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
			if ( $img ) { return $img; }
		}

		// 2) Default social image from settings.
		if ( ! empty( $this->settings['og_default_image'] ) ) {
			return esc_url_raw( $this->settings['og_default_image'] );
		}

		// 3) Generated OG image (if enabled).
		if ( ! empty( $this->settings['og_use_generator'] ) && is_singular() ) {
			$id  = get_queried_object_id();
			$svg = add_query_arg( array( 'keystone_og' => 1, 'post' => $id ), home_url( '/' ) );
			// We generate PNG but expose stable URL via pretty rewrite too (see ImageGenerator).
			return esc_url_raw( $svg );
		}

		// 4) Nothing.
		return '';
	}
}