<?php
namespace Keystone\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Simple logger wrapper.
 *
 * @since 0.1.0
 */
class Logger {
	/**
	 * Log a message to error_log with context.
	 *
	 * @param string $level debug|info|warning|error.
	 * @param string $message Message.
	 * @param array  $context Extra data.
	 * @return void
	 */
	public function log( $level, $message, $context = array() ) {
		$prefix = '[Keystone SEO][' . strtoupper( $level ) . '] ';
		$line   = $prefix . $message;
		if ( ! empty( $context ) ) {
			$line .= ' ' . wp_json_encode( $context );
		}
		error_log( $line ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}