<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://active-directory-wp.com
 * @since      1.0.0
 *
 * @package    NADIExt_BuddyPress_SimpleAttributes
 * @subpackage NADIExt_BuddyPress_SimpleAttributes/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    NADIExt_NADIExt_Custom_User_Role_Management
 * @subpackage NADIExt_NADIExt_Custom_User_Role_Management/admin
 * @author     NeosIT GmbH <info@neos-it.de>
 */
class NADIExt_Custom_User_Role_Management_Admin {

	/**
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * @var NADIExt_Custom_User_Role_Management_Modifier
	 */
	private $_modifier;



	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 * @param NADIExt_Custom_User_Role_Management_Modifier	$modifier
	 */
	public function __construct($plugin_name, $version, NADIExt_Custom_User_Role_Management_Modifier $modifier) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->_modifier = $modifier;
	}

	/**
	 * Register the menu in the sub menu of Next ADI
	 */
	public function registerMenu() {
		add_submenu_page(
			NEXT_AD_INT_PREFIX . 'blog_options',
			"Next ADI Extension: Custom User Role Management",
			"NADI: Custom User Roles Management",
			"manage_options",
			"nadiext_custom_user_role_management",
			array($this, "viewMapping")
		);
	}

	/**
	 * This methodf is executed by WordPress after the menu link has been clicked or settings have been saved
	 */
	public function viewMapping() {

		wp_enqueue_script('next_ad_int_custom_user_role_management_angular_js', plugins_url('', __FILE__) . '/js/angular.min.js', array(), '1.0');
		wp_enqueue_script('next_ad_int_custom_user_role_management_notify_js', plugins_url('', __FILE__) . '/js/ng-notify.min.js', array(), '1.0');
		wp_enqueue_style('next_ad_int_custom_user_role_management_notify_css', plugins_url('', __FILE__) . '/css/ng-notify.min.css', array(), '1.0');
        wp_enqueue_style('next_ad_int_custom_user_role_management_css', plugins_url('', __FILE__) . '/css/nadipe-custom-role-management.css', array(), '1.0');
		wp_enqueue_style('next_ad_int_custom_user_role_management_bootstrap_min_css', plugins_url('', __FILE__) . '/css/bootstrap.min.css', array(), '1.0');
		wp_enqueue_script('next_ad_int_custom_user_role_management_app_js', plugins_url('', __FILE__) . '/js/app.js', array(), '1.0');

		// include partial
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/nadiext-custom-user-role-management-mapping.php';
	}
}