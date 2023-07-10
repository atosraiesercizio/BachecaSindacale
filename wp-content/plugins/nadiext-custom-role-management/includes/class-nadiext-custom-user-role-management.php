<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://active-directory-wp.com
 * @since      1.0.0
 *
 * @package    NADIExt_BuddyPress_Attributes
 * @subpackage NADIExt_BuddyPress_Attributes/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    NADIExt_NADIExt_Custom_User_Role_Management
 * @subpackage NADIExt_Custom_User_Role_Management/includes
 * @author     NeosIT GmbH <info@neos-it.de>
 */
class NADIExt_Custom_User_Role_Management
{
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Plugin_Name_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;
	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * @var NADIExt_Custom_User_Role_Management_Modifier
	 */
	private $_modifier;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		$this->plugin_name = 'nadiext_custom_user_role_management';
		$this->version = '1.0.0';
	}

	public function run()
	{
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_internal_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-nadiext-custom-user-role-management-admin.php';

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-nadiext-custom-user-role-management-modifier.php';
		$this->_modifier = new NADIExt_Custom_User_Role_Management_Modifier();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new NADIExt_Custom_User_Role_Management_Admin($this->get_plugin_name(), $this->get_version(), $this->_modifier);
		add_action('admin_menu', array($plugin_admin, 'registerMenu'), 11 /* insert entry after default menu entries (10) */);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
	}

	/**
	 * Internal hooks executed by other plug-ins
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_internal_hooks()
	{
		add_filter('next_ad_int_sync_ad2wp_clean_existing_roles', array($this->_modifier, 'cleanExistingRolesForAllUsers'), 10, 4);
		add_filter('next_ad_int_sync_ad2wp_filter_roles', array($this->_modifier, 'modifyUserRoles'), 10, 4);
		add_action('wp_ajax_next_ad_int_custom_user_role_management_load_settings', array($this->_modifier, 'loadSettings')); //TODO Refactor Method Name
		add_action('wp_ajax_next_ad_int_custom_user_role_management_save_settings', array($this->_modifier, 'saveSettings')); //TODO Refactor Method Name
	}


	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
