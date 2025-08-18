<?php
namespace Keystone\Core\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Consistent hook registration without anonymous callbacks.
 */
class HookBus {
	public function action( $hook, $obj, $method, $priority = 10, $args = 1 ) {
		add_action( $hook, array( $obj, $method ), $priority, $args );
	}
	public function filter( $hook, $obj, $method, $priority = 10, $args = 1 ) {
		add_filter( $hook, array( $obj, $method ), $priority, $args );
	}
}
