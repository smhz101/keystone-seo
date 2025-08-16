<?php
namespace Keystone\Meta\Contracts;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Contract for token providers used in meta templating.
 *
 * @since 0.1.0
 */
interface TokenProviderInterface {
  /**
	 * Return map of token => value for a given context (post, term, etc.).
	 *
	 * @param array $context Context array (e.g., 'post_id', 'type').
	 * @return array<string,string>
	 */
	public function tokens( $context );
}