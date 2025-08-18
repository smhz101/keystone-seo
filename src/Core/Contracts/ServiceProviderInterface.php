<?php
namespace Keystone\Core\Contracts;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Contract for Keystone service providers.
 */
interface ServiceProviderInterface {
	/** Register container bindings/singletons. */
	public function register( $c );
	/** Attach WordPress hooks via a bus (no anonymous functions). */
	public function hooks( $hooks, $c );
}
