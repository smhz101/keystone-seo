<?php
namespace Keystone\Meta;

use Keystone\Meta\Contracts\TokenProviderInterface;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Generates SEO titles/descriptions/social tags.
 * Now supports tokenized templates via Templates engine.
 */
class MetaService {

	/** @var Templates */
	protected $templates;

	/** @var array */
	protected $settings = array();

	public function __construct( $settings = array(), Templates $templates = null ) {
		$this->settings  = is_array( $settings ) ? $settings : array();
		$this->templates = $templates ?: new Templates();
	}

	/**
	 * Allow adding token providers from bootstrap.
	 *
	 * @param TokenProviderInterface $provider Provider.
	 * @return void
	 */
	public function add_provider( TokenProviderInterface $provider ) {
		$this->templates->add_provider( $provider );
	}

	/**
	 * Filter: document title parts.
	 *
	 * @param array $parts WP title parts.
	 * @return array
	 */
	public function filter_document_title( $parts ) {
		// Title Template: default "%title% %sep% %sitename%"
		$template = isset( $this->settings['title_template'] ) && $this->settings['title_template']
			? $this->settings['title_template']
			: '%title% %sep% %sitename%';

		$post_id = is_singular() ? get_queried_object_id() : 0;
		$title   = $this->templates->render( $template, array( 'post_id' => $post_id ) );

		$parts['title'] = $title;

		if ( ! empty( $this->settings['site_name'] ) ) {
			$parts['site'] = $this->settings['site_name'];
		}
		return $parts;
	}

	/**
	 * Action: print meta tags in head (description + OG/Twitter).
	 *
	 * @return void
	 */
	public function output_head_tags() {
		// Description Template: default = tagline (fallback).
		$template = isset( $this->settings['desc_template'] ) && $this->settings['desc_template']
			? $this->settings['desc_template']
			: '%tagline%';

		$post_id = is_singular() ? get_queried_object_id() : 0;
		$title   = wp_get_document_title();
		$desc    = $this->templates->render( $template, array( 'post_id' => $post_id ) );

		echo "\n" . '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( is_singular() ? 'article' : 'website' ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
}