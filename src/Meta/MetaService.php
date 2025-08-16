<?php
namespace Keystone\Meta;

use Keystone\Meta\Contracts\TokenProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Generates SEO titles/descriptions and social tags.
 *
 * Hooks:
 * - filter 'document_title_parts'
 * - action 'wp_head' to print OG/Twitter tags
 *
 * @since 0.1.0
 */
class MetaService {
	/** @var TokenProviderInterface[] */
	protected $providers = array();

	/** @var array */
	protected $settings = array();

	public function __construct( $settings = array() ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

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
	 * Filter: document title parts.
	 *
	 * @param array $parts WP title parts.
	 * @return array
	 */
	public function filter_document_title( $parts ) {
		// Example: prepend site name override if set.
		if ( ! empty( $this->settings['site_name'] ) ) {
			$parts['site'] = $this->settings['site_name'];
		}
		return $parts;
	}

	/**
	 * Action: print meta tags in head.
	 *
	 * @return void
	 */
	public function output_head_tags() {
		$title = wp_get_document_title();
		$desc  = get_bloginfo( 'description', 'display' );

		echo "\n" . '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		// Open Graph
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		// Twitter
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}  
}