<?php
/**
 * PHPUnit Bootstrap
 *
 * Sets up Brain\Monkey for WordPress function mocking and loads plugin files.
 *
 * @package HM\Block_Pattern_Transformer\Tests
 */

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define ABSPATH for plugin.php.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Load plugin.
require_once dirname( __DIR__ ) . '/plugin.php';
