<?php
namespace Keystone\Robots;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Controls robots.txt output via 'robots_txt' filter.
 * Reads additional rules from keystone settings.
 */
class RobotsController {
	/** @var array */
	protected $settings = array();

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Filter robots.txt content.
	 *
	 * @param string $output Existing robots content.
	 * @param bool   $public Blog public flag.
	 * @return string
	 */
	public function filter_robots( $output, $public ) {
		$lines = array();

		// Respect site visibility.
		if ( ! $public ) {
			$lines[] = 'User-agent: *';
			$lines[] = 'Disallow: /';
			return implode( "\n", $lines ) . "\n";
		}

		// Default allow rules + main sitemap.
		$lines[] = 'User-agent: *';
		$lines[] = 'Allow: /';
		$lines[] = 'Sitemap: ' . esc_url_raw( home_url( '/sitemap.xml' ) );

		// Optional extra sitemaps.
		if ( ! empty( $this->settings['robots_extra_sitemaps'] ) && is_array( $this->settings['robots_extra_sitemaps'] ) ) {
			foreach ( $this->settings['robots_extra_sitemaps'] as $sm ) {
				$lines[] = 'Sitemap: ' . esc_url_raw( $sm );
			}
		}

		// Optional media query-string throttling (soft guidance).
		if ( ! empty( $this->settings['robots_block_media'] ) ) {
			$lines[] = 'Disallow: /*.js$';
			$lines[] = 'Disallow: /*.css$';
			$lines[] = 'Disallow: /*?*';
		}

		// Custom user lines.
		if ( ! empty( $this->settings['robots_custom'] ) && is_array( $this->settings['robots_custom'] ) ) {
			foreach ( $this->settings['robots_custom'] as $line ) {
				$lines[] = sanitize_text_field( $line );
			}
		}

		/**
		 * Filter to allow developers to modify robots lines.
		 *
		 * @param string[] $lines Lines to be joined by "\n".
		 */
		$lines = apply_filters( 'keystone/robots/lines', $lines );

		return implode( "\n", $lines ) . "\n";
	}
}