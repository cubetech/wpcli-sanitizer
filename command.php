<?php

use Cubetech\WPCli\Sanitizer;

if (! class_exists('WP_CLI')) {
	return;
}

require_once(__DIR__ . '/src/Sanitizer.php');

/**
 * Registers the sanitize command to the WP-Cli
 *
 * @when before_wp_load
 */
WP_CLI::add_command('media ct-sanitize', Sanitizer::class);