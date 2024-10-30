<?php
/**
 * Plugin Name:       MIYN App
 * Plugin URI:        https://wordpress.org/plugins/miyn-app
 * Description:       A plugin for MIYN App
 * Version:           1.3.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Netmow
 * Author URI:        https://netmow.com/
 * Text Domain:       miynapp
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Currently plugin version.
 * Start at version 1.3.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MIYNAPP_VERSION', '1.3.0' );


/**
 * The code that runs during plugin activation.
 * This action is documented in inc/class-miyn-app-activation.php
 */
function miynapp_activate_init() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/class-miyn-app-activation.php';
	Miynapp_activations_init::miynapp_activate();
}
register_activation_hook( __FILE__, 'miynapp_activate_init' );


/**
 * The code that runs during plugin deactivation.
 * This action is documented in inc/class-miyn-app-deactivator.php
 */
function miynapp_deactivation_init() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/class-miyn-app-deactivator.php';
	Miynapp_deactivator_init::miynapp_deactivate();
}
register_deactivation_hook( __FILE__, 'miynapp_deactivation_init' );


/**
 * The code that runs during plugin activation.
 * This action is documented in inc/class-miyn-core-init.php
 */
require_once plugin_dir_path( __FILE__ ) . 'inc/class-miyn-core-init.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.2.0
 */
function miynapp_run_init() {

	$plugin = new Miynapp_features_init();
	$plugin->miynapp_run();

}
miynapp_run_init();