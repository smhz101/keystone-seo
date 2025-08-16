<?php
/**
 * Plugin Name: Keystone SEO
 * Description: Modern, modular, developer-first SEO for WordPress.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.2
 * Author: Keystone
 * License: GPLv2 or later
 * Text Domain: keystone-seo
 */

defined('ABSPATH') || exit;

if (version_compare(PHP_VERSION, '8.1', '<')) {
  // Fail fast for unsupported PHP.
  if (is_admin()) {
    add_action('admin_notices', static function () {
      echo '<div class="notice notice-error"><p>Keystone SEO requires PHP 8.1+.</p></div>';
    });
  }
  return;
}

// Composer autoload (optional in .org build; required if using Composer).
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
  require_once $autoload;
}