<?php
namespace Keystone\Meta;

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
}