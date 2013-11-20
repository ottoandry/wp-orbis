<?php
/*
Plugin Name: Orbis
Plugin URI: http://pronamic.eu/wordpress/orbis/
Description: This plugin creates an intranet environment in WordPress

Version: 1.0.0
Requires at least: 3.0

Author: Pronamic
Author URI: http://pronamic.eu/

License: GPL
*/

require_once 'includes/functions.php';
require_once 'includes/persons.php';
require_once 'includes/companies.php';
require_once 'includes/projects.php';
require_once 'includes/log.php';
require_once 'includes/flot.php';
require_once 'includes/scheme.php';
require_once 'includes/shortcodes.php';
require_once 'admin/includes/upgrade.php';

function orbis_bootstrap() {
	// Classes
	require_once 'classes/orbis-plugin.php';
	require_once 'classes/orbis-core-admin.php';
	require_once 'classes/orbis-core-plugin.php';
	require_once 'classes/orbis-api.php';

	// Initialize
	global $orbis_plugin;

	$orbis_plugin = new Orbis_Core_Plugin( __FILE__ );
}

add_action( 'orbis_bootstrap', 'orbis_bootstrap', 1 );

// Bootstrap
do_action( 'orbis_bootstrap' );
