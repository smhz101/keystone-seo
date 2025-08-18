<?php
namespace Keystone\Core\Providers;

use Keystone\Core\Contracts\ServiceProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Base class providers can extend (optional).
 */
abstract class ServiceProvider implements ServiceProviderInterface {

	public function register( $c ) { /* no-op */ }

	public function hooks( $hooks, $c ) { /* no-op */ }
}
