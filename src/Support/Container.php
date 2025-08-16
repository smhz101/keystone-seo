<?php
namespace Keystone\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Very small service container.
 *
 * @since 0.1.0
 */
class Container {
	/** @var array<string, callable|object> */
	protected $bindings = array();

	/** @var array<string, object> */
	protected $instances = array();

	/**
	 * Bind a service by key.
	 *
	 * @param string          $key Service key.
	 * @param callable|object $concrete Factory or instance.
	 * @return void
	 */
	public function bind( $key, $concrete ) {
		$this->bindings[ $key ] = $concrete;
	}

	/**
	 * Resolve a service by key.
	 *
	 * @param string $key Service key.
	 * @return object
	 */
	public function make( $key ) {
		if ( isset( $this->instances[ $key ] ) ) {
			return $this->instances[ $key ];
		}
		$concrete = isset( $this->bindings[ $key ] ) ? $this->bindings[ $key ] : null;
		if ( is_object( $concrete ) && ! is_callable( $concrete ) ) {
			$this->instances[ $key ] = $concrete;
			return $concrete;
		}
		if ( is_callable( $concrete ) ) {
			$obj = call_user_func( $concrete, $this );
			$this->instances[ $key ] = $obj;
			return $obj;
		}
		throw new \RuntimeException( 'Service not bound: ' . $key );
	}
}