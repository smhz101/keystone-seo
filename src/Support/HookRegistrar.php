<?php
namespace Keystone\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Centralized hook registration to avoid anonymous closures in hooks/filters.
 *
 * @since 0.1.0
 */
class HookRegistrar {
  /**
	 * Register a WordPress action.
	 *
	 * @param string $hook Hook name.
	 * @param object $object Instance.
	 * @param string $method Method name.
	 * @param int    $priority Priority.
	 * @param int    $args Accepted args.
	 * @return void
	 */
	public function action( $hook, $object, $method, $priority = 10, $args = 1 ) {
		add_action( $hook, array( $object, $method ), $priority, $args );
	}

	/**
	 * Register a WordPress filter.
	 *
	 * @param string $hook Hook name.
	 * @param object $object Instance.
	 * @param string $method Method name.
	 * @param int    $priority Priority.
	 * @param int    $args Accepted args.
	 * @return void
	 */
	public function filter( $hook, $object, $method, $priority = 10, $args = 1 ) {
		add_filter( $hook, array( $object, $method ), $priority, $args );
	}
}