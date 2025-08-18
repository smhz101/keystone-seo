<?php
namespace Keystone\Core\Foundation;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Keystone\Support\Container;
use Keystone\Core\Support\HookBus;
use Keystone\Core\Contracts\ServiceProviderInterface;

/**
 * Core application orchestrating container, providers, and hook bus.
 */
class Application {
	protected $c;
	protected $hooks;

	/** @var ServiceProviderInterface[] */
	protected $providers = array();

	public function __construct( Container|null $c = null, HookBus|null $hooks = null ) {
		$this->c     = $c ?: new Container();
		$this->hooks = $hooks ?: new HookBus();
	}

	public function container() { return $this->c; }
	public function hooks()     { return $this->hooks; }

	/** 
	 * Add a provider instance. 
	 */
	public function add( $provider ) { $this->providers[] = $provider; }

	/** 
	 * Register all providers (bindings only).
	 */
	public function register() { foreach ( $this->providers as $p ) { $p->register( $this->c ); } }


	/** 
	 * Attach all hooks from providers.
	 */
	public function boot() { foreach ( $this->providers as $p ) { $p->hooks( $this->hooks, $this->c ); } }
}