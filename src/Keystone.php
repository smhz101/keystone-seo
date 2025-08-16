<?php
namespace Keystone;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Keystone SEO main orchestrator.
 *
 * @since 0.1.0
 */
class Keystone {
  
  public function __construct() {
  }

  /**
	 * Activation tasks (DB tables, rewrites).
	 *
	 * @return void
	 */
	public function activate() {}

	/**
	 * Wire everything on runtime.
	 *
	 * @return void
	 */
	public function run() {
  }
}