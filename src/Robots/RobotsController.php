<?php
namespace Keystone\Robots;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Controls robots.txt output via 'robots_txt' filter.
 *
 * @since 0.1.0
 */
class RobotsController {
	/**
	 * Filter robots.txt content.
	 *
	 * @param string $output Existing robots content.
	 * @param bool   $public Blog public flag.
	 * @return string
	 */
	public function filter_robots( $output, $public ) {
		$lines = array();

		// Respect site visibility setting.
		if ( ! $public ) {
			$lines[] = 'User-agent: *';
			$lines[] = 'Disallow: /';
			return implode( "\n", $lines ) . "\n";
		}

		// Default: allow all, add sitemap hint.
		$lines[] = 'User-agent: *';
		$lines[] = 'Allow: /';

		$sitemap_url = home_url( '/sitemap.xml' );
		$lines[]     = 'Sitemap: ' . esc_url_raw( $sitemap_url );

		return implode( "\n", $lines ) . "\n";
	}
}