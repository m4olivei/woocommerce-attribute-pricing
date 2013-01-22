<?php
// Load the WordPress test environment.
//
// See for more details:
// http://stackoverflow.com/questions/9138215/unit-testing-wordpress-plugins
// https://github.com/nb/wordpress-tests
// http://codex.wordpress.org/Automated_Testing
//
// The path to the WordPress test environment.
$path = getenv('WORDPRESS_TEST_ENVIRONMENT');

// The path to the plugin to test.
$plugin_path = dirname(dirname(__FILE__));
$plugin_file = join('/', array($plugin_path, 'includes/wc-ap-price-functions.php'));

if (file_exists($path)) {
  require_once($path);
  include_once($plugin_file);
} else {
  exit("Couldn't find path to wp-test-suite/includes/bootstrap.php at".$path."\n");
}