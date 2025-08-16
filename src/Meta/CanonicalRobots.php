<?php
namespace Keystone\Meta;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Canonical + robots meta tag output.
 *
 * Global defaults:
 *  - Noindex search, 404, feeds
 *  - Respect per-post noindex/canonical overrides
 *
 * @since 0.1.0
 */
class CanonicalRobots {
	protected $repo;

	/** @var array Plugin settings snapshot */
	protected $settings = array();

	public function __construct( MetaRepository $repo, $settings = array() ) {
		$this->repo     = $repo;
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/** Print canonical + robots in <head>. */
	public function output() {
		// Decide robots "index/noindex,follow".
		$robots = $this->determine_robots();

		if ( $robots ) {
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
		}

		// Canonical URL.
		$canonical = $this->determine_canonical();
		if ( $canonical ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		}
	}

	/** Compose robots directive string. */
	protected function determine_robots() {
		// Default follow unless stated otherwise.
		$index  = 'index';
		$follow = 'follow';

		// Global rules: search/404/feeds are noindex.
		if ( is_search() || is_404() || is_feed() ) {
			$index = 'noindex';
		}

		// Per-post overrides.
		if ( is_singular() ) {
			$meta = $this->repo->get( get_queried_object_id() );
			if ( ! empty( $meta['noindex'] ) ) {
				$index = 'noindex';
			}
		}

		/**
		 * Filter final robots string.
		 *
		 * @param string $robots Robots value, e.g. 'index,follow'.
		 */
		return apply_filters( 'keystone/meta/robots', "{$index},{$follow}" );
	}

	/** Resolve canonical URL (per-post > current permalink). */
	protected function determine_canonical() {
		$url = '';

		if ( is_singular() ) {
			$id   = get_queried_object_id();
			$meta = $this->repo->get( $id );
			if ( ! empty( $meta['canonical'] ) ) {
				$url = $meta['canonical'];
			} else {
				$url = get_permalink( $id );
			}
		} elseif ( is_home() || is_front_page() ) {
			$url = home_url( '/' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$url = get_term_link( get_queried_object() );
		} elseif ( is_author() ) {
			$url = get_author_posts_url( get_queried_object_id() );
		} elseif ( is_post_type_archive() ) {
			$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
		} elseif ( is_search() ) {
			// Canonicalize searched term.
			$url = get_search_link( get_search_query() );
		}

		/**
		 * Filter final canonical URL (empty string disables output).
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'keystone/meta/canonical', $url );
	}
}