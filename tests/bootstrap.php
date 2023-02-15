<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Shortpixel_Image_Optimiser
 */
//define('WP_TESTS_SKIP_INSTALL', '1');
define('SHORTPIXEL_DEBUG', 4); // Note - debug logs will go into /tmp/
define('SHORTPIXEL_DEBUG_TARGET', '/tmp/wordpress/');
define('SHORTPIXEL_API_KEY', '77O4S9VttKCljaDha8fW');

define('UNIT_INSTALL', '/var/www/tools/wordpress-unit/wordpress-tests-lib/includes');

require_once( dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php');

$_tests_dir = UNIT_INSTALL; //getenv( 'WP_TESTS_DIR' );
/*$_tests_dir = false;

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib/includes';
}
 */
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';


/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require_once( dirname( dirname( __FILE__ ) ) . '/wp-shortpixel.php');
	//require_once( dirname( dirname( __FILE__ ) ) . '/wp-shortpixel-req.php');

	// Core Classes
	//require_once('SPIO_UnitTestCase.php');
	//$shortPixelPluginInstance = new WPShortPixel;

}
tests_add_filter( 'plugins_loaded', '_manually_load_plugin' );


// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
