<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://github.com/mtuszynski
 * @since             1.0.0
 * @package           Magazine_Subscription
 *
 * @wordpress-plugin
 * Plugin Name:       Magazine Subscription
 * Plugin URI:        https://https://github.com/mtuszynski/wp-magazine-subscription
 * Description:       Plugin enabling subscription management for a magazine. PDF delivery, subscription assignment to user accounts, and administration.
 * Version:           1.0.0
 * Author:            MirT
 * Author URI:        https://https://github.com/mtuszynski/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       magazine-subscription
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MAGAZINE_SUBSCRIPTION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-magazine-subscription-activator.php
 */
function activate_magazine_subscription() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-magazine-subscription-activator.php';
	Magazine_Subscription_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-magazine-subscription-deactivator.php
 */
function deactivate_magazine_subscription() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-magazine-subscription-deactivator.php';
	Magazine_Subscription_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_magazine_subscription' );
register_deactivation_hook( __FILE__, 'deactivate_magazine_subscription' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-magazine-subscription.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_magazine_subscription() {

	$plugin = new Magazine_Subscription();
	$plugin->run();

}
run_magazine_subscription();
