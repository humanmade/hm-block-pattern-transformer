<?php
/**
 * PHPUnit Bootstrap
 *
 * Loads WordPress test framework for integration testing with wp-env.
 *
 * @package HM\Rehydrator\Tests
 */

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Set PHPUnit Polyfills path for WordPress test suite.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

// Determine WordPress test directory.
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wp_tests_dir ) {
	// Default location in wp-env.
	$wp_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	// Try alternative locations.
	$wp_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	$wp_tests_dir = '/var/www/html/wp-tests-lib';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test library. Set WP_TESTS_DIR environment variable.\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/plugin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $wp_tests_dir . '/includes/bootstrap.php';
