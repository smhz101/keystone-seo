<?php
namespace Keystone\Core\Config;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Lightweight config holder with WP filters override.
 */
class Config {
	protected $data = array();

	public function __construct( $defaults = array() ) {
		$this->data = is_array( $defaults ) ? $defaults : array();
	}

	public function get( $key, $default = null ) {
		$val = isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
		/**
		 * Permit runtime overrides.
		 * Example: apply_filters('keystone/config/site_name', 'My Site')
		 */
		return apply_filters( "keystone/config/{$key}", $val );
	}

	public function set( $key, $value ) {
		$this->data[ $key ] = $value;
	}
}
