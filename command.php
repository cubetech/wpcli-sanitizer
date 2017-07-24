<?php

use Cubetech\WPCli\Sanitizer;

if (! class_exists('WP_CLI')) {
	return;
}
require_once('vendor/autoload.php');

/**
 * Registers the sanitize command to the WP-Cli
 *
 * @when before_wp_load
 */
WP_CLI::add_command('sanitize', Sanitizer::class);
