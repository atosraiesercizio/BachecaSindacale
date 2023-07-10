<?php
/*
Plugin Name: Next Active Directory Integration: Custom User Role Management
Plugin URI: https://www.active-directory-wp.com
Description: Set roles for specific users which excludes them from NADIS role equivalent mapping. Therefore you can configure roles for specific users locally
Version: 1.0.3

Author: NeosIT GmbH
Author URI: http://www.neos-it.de/
License: Commercial
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

define('NADIEXT_CUSTOM_USER_ROLE_MANAGEMENT', 'nadiext_custom_user_role_management');
define('NADIEXT_CUSTOM_USER_ROLE_MANAGEMENT_PREFIX', NADIEXT_CUSTOM_USER_ROLE_MANAGEMENT . '_');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_nadiext_custom_user_role_management()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-nadiext-custom-user-role-management-activator.php';
	NADIExt_Custom_User_Role_Management_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_nadiext_custom_user_role_management()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-nadiext-custom-user-role-management-deactivator.php';
	NADIExt_Custom_User_Role_Management_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_nadiext_custom_user_role_management');
register_deactivation_hook(__FILE__, 'deactivate_nadiext_custom_user_role_management');
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-nadiext-custom-user-role-management.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_nadiext_custom_user_role_management()
{
	if (!class_exists('NextADInt_Adi_Init')) {
		// fail silently if NADI has not been loaded
		return;
	}

	$plugin = new NADIExt_Custom_User_Role_Management();
	$plugin->run();
}

add_action('plugins_loaded', 'run_nadiext_custom_user_role_management');
