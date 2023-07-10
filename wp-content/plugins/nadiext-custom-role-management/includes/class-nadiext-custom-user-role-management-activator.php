<?php
/**
 * Fired during plugin activation
 *
 * @link       https://active-directory-wp.com
 * @since      1.0.0
 *
 * @package    NADIExt_BuddyPress_Attributes
 * @subpackage NADIExt_BuddyPress_Attributes/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    NADIExt_BuddyPress_SimpleAttributes
 * @subpackage NADIExt_BuddyPress_SimpleAttributes/includes
 * @author     NeosIT GmbH <info@neos-it.de>
 */
class NADIExt_Custom_User_Role_Management_Activator
{
	public static function activate()
	{
		if (!class_exists('NextADInt_Adi_Init')) {
			wp_die(__('Could not activate extension: Next ADI is not installed or activated'));
		}
	}
}