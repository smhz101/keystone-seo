<?php
namespace Keystone\Social;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Minimal dynamic OG image generator using GD.
 * - Pretty URL: /keystone-og/{ID}.png  (rewrite)
 * - Fallback query: ?keystone_og=1&post={ID}
 * - Caches file under uploads/keystone-og/{ID}.png
 *
 * Settings used:
 *  - og_use_generator (bool)
 *  - og_bg (hex color, optional)  default #111827
 *  - og_fg (hex color, optional)  default #FFFFFF
 */
class ImageGenerator {
	/** @var array */
	protected $settings = array();

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/** Register rewrite for pretty image URL. */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^keystone-og/([0-9]+)\.png$', 'index.php?keystone_og=1&post=$matches[1]', 'top' );
		add_rewrite_tag( '%keystone_og%', '([0-1])' );
		add_rewrite_tag( '%post%', '([0-9]+)' );
	}

	/** On template_redirect, render if requested. */
	public function maybe_render() {
		$q = get_query_var( 'keystone_og' );
		$get_trigger = isset( $_GET['keystone_og'] ) ? absint( $_GET['keystone_og'] ) : 0; // phpcs:ignore
		if ( ! $q && ! $get_trigger ) { return; }

		if ( empty( $this->settings['og_use_generator'] ) ) { return; } // disabled

		$post_id = absint( get_query_var( 'post' ) ?: ( isset( $_GET['post'] ) ? $_GET['post'] : 0 ) ); // phpcs:ignore
		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) { status_header( 404 ); exit; }

		$path = $this->ensure_cached( $post_id );
		if ( ! $path || ! file_exists( $path ) ) { status_header( 500 ); exit; }

		// Emit the PNG with cache headers.
		nocache_headers();
		header( 'Content-Type: image/png' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'Cache-Control: public, max-age=86400' );
		readfile( $path ); // phpcs:ignore
		exit;
	}

	/** Ensure a cached image exists; return file path. */
	protected function ensure_cached( $post_id ) {
		$upload = wp_get_upload_dir();
		if ( empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) { return ''; }

		$dir = trailingslashit( $upload['basedir'] ) . 'keystone-og';
		if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }

		$file = trailingslashit( $dir ) . absint( $post_id ) . '.png';
		if ( file_exists( $file ) ) { return $file; }

		// Build simple image (1200x630).
		if ( ! function_exists( 'imagecreatetruecolor' ) ) { return ''; }

		$w = 1200; $h = 630;
		$im = imagecreatetruecolor( $w, $h );

		$bg_hex = ! empty( $this->settings['og_bg'] ) ? $this->settings['og_bg'] : '#111827';
		$fg_hex = ! empty( $this->settings['og_fg'] ) ? $this->settings['og_fg'] : '#FFFFFF';

		list( $r1, $g1, $b1 ) = $this->hex_to_rgb( $bg_hex );
		list( $r2, $g2, $b2 ) = $this->hex_to_rgb( $fg_hex );

		$bg = imagecolorallocate( $im, $r1, $g1, $b1 );
		$fg = imagecolorallocate( $im, $r2, $g2, $b2 );

		imagefilledrectangle( $im, 0, 0, $w, $h, $bg );

		// Text content: title + site name (use system font).
		$title = get_the_title( $post_id );
		$site  = get_bloginfo( 'name' );

		// Draw using built-in bitmap fonts (portable, no TTF dep).
		// Title (wrap naive).
		$text  = $this->wrap_text( $title, 40 ); // approx chars/line for font size 5
		$lines = explode( "\n", $text );

		$y = 200; // start
		foreach ( $lines as $line ) {
			$box_w = imagefontwidth( 5 ) * strlen( $line );
			$x = (int) ( ( $w - $box_w ) / 2 );
			imagestring( $im, 5, max( 20, $x ), $y, $line, $fg );
			$y += 28;
		}

		// Site name badge
		$badge = ' ' . $site . ' ';
		$badge_w = imagefontwidth( 4 ) * strlen( $badge );
		$bx = (int) ( ( $w - $badge_w ) / 2 );
		$by = $h - 80;
		imagestring( $im, 4, max( 20, $bx ), $by, $badge, $fg );

		// Save.
		imagepng( $im, $file );
		imagedestroy( $im );

		return $file;
	}

	protected function hex_to_rgb( $hex ) {
		$h = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $h ) ) {
			$r = hexdec( str_repeat( substr( $h, 0, 1 ), 2 ) );
			$g = hexdec( str_repeat( substr( $h, 1, 1 ), 2 ) );
			$b = hexdec( str_repeat( substr( $h, 2, 1 ), 2 ) );
			return array( $r, $g, $b );
		}
		$r = hexdec( substr( $h, 0, 2 ) );
		$g = hexdec( substr( $h, 2, 2 ) );
		$b = hexdec( substr( $h, 4, 2 ) );
		return array( $r, $g, $b );
	}

	/** Very naive word-wrap for built-in font metrics. */
	protected function wrap_text( $text, $max_per_line = 40 ) {
		$words = preg_split( '/\s+/', (string) $text );
		$line = ''; $out = array();
		foreach ( $words as $w ) {
			if ( strlen( $line . ' ' . $w ) > $max_per_line ) {
				$out[] = trim( $line );
				$line = $w;
			} else {
				$line .= ' ' . $w;
			}
		}
		if ( trim( $line ) !== '' ) { $out[] = trim( $line ); }
		return implode( "\n", $out );
	}
}