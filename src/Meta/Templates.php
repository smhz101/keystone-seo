<?php
namespace Keystone\Meta;

use Keystone\Meta\Contracts\TokenProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Lightweight templating for SEO titles/descriptions.
 * Replaces %tokens% with provider values in priority order.
 */
class Templates {
	/** @var TokenProviderInterface[] */
	protected $providers = array();

	/**
	 * Register a token provider.
	 *
	 * @param TokenProviderInterface $provider Provider.
	 * @return void
	 */
	public function add_provider( TokenProviderInterface $provider ) {
		$this->providers[] = $provider;
	}

	/**
	 * Render a template with tokens.
	 *
	 * @param string $template e.g. "%title% %sep% %sitename%".
	 * @param array  $context  e.g. ['post_id' => 123].
	 * @return string
	 */
	public function render( $template, $context = array() ) {
		$map = array();
		foreach ( $this->providers as $p ) {
			$map = array_merge( $map, $p->tokens( $context ) );
		}
		return strtr( $template, $map );
	}
}