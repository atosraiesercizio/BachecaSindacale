<?php
/**
 * Internal class for synchronizing
 *
 * @link       https://active-directory-wp.com
 * @since      1.0.0
 *
 * @package    NADIExt_NADIExt_Custom_User_Role_Management
 * @subpackage NADIExt_NADIExt_Custom_User_Role_Management/includes
 */
class NADIExt_Custom_User_Role_Management_Modifier
{

	/**
	 * User is Managed by this Plugin Metakey
	 */
	private $isManagedByPeMetakey = 'next_ad_int_pe_crm_is_managed_by_pe_';

	/**
	 * User Custom Roles Metakey
	 */
	private $userCustomRolesMetakey = 'next_ad_int_pe_crm_user_custom_roles_';

	/**
	 * Clear Existing Roles Metakey
	 */
	private $cleanExistingRolesMetakey = 'next_ad_int_pe_crm_clean_existing_roles_';


	/* @var Logger $logger */
	private $logger;

	/* @var */
	private $errorCollector = array();

	/* @var */
	private $currentBlogId;

	/**
	 * NADIExt_Custom_User_Role_Management_Modifier constructor.
	 */
	public function __construct()
	{
		$this->logger = Logger::getLogger(__CLASS__);
		$this->currentBlogId = get_current_blog_id();
		$this->isManagedByPeMetakey = $this->isManagedByPeMetakey . $this->currentBlogId;
		$this->userCustomRolesMetakey = $this->userCustomRolesMetakey . $this->currentBlogId;
		$this->cleanExistingRolesMetakey = $this->cleanExistingRolesMetakey . $this->currentBlogId;
	}

	/**
	 * Role manipulation logic
	 * Case 1: Deletes existing roles and assignes roles definded by this plugin
	 * Case 2: Merges existing roles ( including locally assigned roles and NADI REQM roles ) with roles defined for the user by this plugin
	 *
	 * @param $wordPressRoles
	 * @param $cleanExistingRoles
	 * @param $wpUser
	 * @param $roleMapping
	 * @return array
	 */
	public function modifyUserRoles($wordPressRoles, $cleanExistingRoles, $wpUser, $roleMapping) {

		$usersManagedByThisPlugin = $this->findUsersManagedByThisPlugin();

		foreach ($usersManagedByThisPlugin as $userManagedByThisPlugin) {
			if ($userManagedByThisPlugin == $wpUser) {
				$this->logger->info("WordPress User: " . $wpUser->data->user_login . " is managed my the Next Active Directory Integration Custom Role Management Premium Extension.");

				$cleanExistingRolesForSpecificUser = get_user_meta($wpUser->ID, $this->cleanExistingRolesMetakey, true);
				$customRoles = $this->findCustomRolesForUser($wpUser);

				if ($cleanExistingRoles && $cleanExistingRolesForSpecificUser) {
					$this->logger->info("Clearing existing WordPress roles for WordPress User: " . $wpUser->data->user_login);
					$this->logger->info("Adding Custom WordPress Roles for WordPress User: " . $wpUser->data->user_login);

					$wordPressRoles = $customRoles;
				} else {
					$this->logger->info("Clearing existing WordPress roles for WordPress User: " . $wpUser->data->user_login . " disabled. Assigning NADI Role Equivalent Mapping Roles, custom roles and locally assigned WordPress Roles.");
					$wordPressRoles = array_merge($wordPressRoles, $customRoles);
					$wordPressRoles = array_merge($wordPressRoles, $wpUser->roles);
					$wordPressRoles = array_unique($wordPressRoles);

				}

			}
		}

		return $wordPressRoles;
	}


	/**
	 * Save handler for all plugin settings including the user configurations
	 *
	 * @param object $data
	 * @return string JSON
	 */
	public function saveSettings() {

		$data = $_POST['data'];

		// Persist Data for users to be saved

		foreach ($data["saveUsers"] as $user) {

			$lowercaseLogin = strtolower($user["username"]);

			// Get wpUser
			$wpUser = get_user_by('login', $lowercaseLogin);

			if ($wpUser == null) {
				$userNotFoundMsg = "Could not find WordPress User with user_login: " . $lowercaseLogin;
				$this->logger->info($userNotFoundMsg);
				$this->addErrorToCollector($lowercaseLogin, $userNotFoundMsg);
				continue;
			}

			// check if usermeta exists if not create

			$metaValueManagedBy = $this->getMetaValue($this->isManagedByPeMetakey, $wpUser);
			$metaValueCustomRoles = $this->getMetaValue($this->userCustomRolesMetakey, $wpUser);
			$metaValueCleanExistingRoles = $this->getMetaValue($this->cleanExistingRolesMetakey, $wpUser);

			$this->createOrUpdateMetakey($metaValueManagedBy, $wpUser, $this->isManagedByPeMetakey, 1);

			$this->createOrUpdateMetakey($metaValueCustomRoles, $wpUser, $this->userCustomRolesMetakey, $user["roles"]);

			$this->createOrUpdateMetakey($metaValueCleanExistingRoles, $wpUser, $this->cleanExistingRolesMetakey, $user["cleanExistingRoles"]);

		}

		foreach ($data["deleteUsers"] as $user) {

			$lowercaseLogin = strtolower($user["username"]);

			// Get wpUser
			$wpUser = get_user_by('login', $lowercaseLogin);

			$this->deleteMetakey($this->isManagedByPeMetakey, $wpUser);
			$this->deleteMetakey($this->userCustomRolesMetakey, $wpUser);
			$this->deleteMetakey($this->cleanExistingRolesMetakey, $wpUser);

		}

		$hasErrors = sizeof($this->getErrorCollector()) > 0;
		$response = new stdClass();

		if (!$hasErrors) {
			$response->status = "success";
			echo json_encode($response);
			return;
		}

		$response->status = "error";
		$response->errorCollector = $this->getErrorCollector();

		echo json_encode($response);

		// Resett errorCollector
		$this->resetErrorCollector();

		return;
	}

	/**
	 * Creates or updates a meta attribute for a specific user by a specific key and sets or updates the value
	 *
	 * @param bool $metakeyExists
	 * @param WP_USER $wpUser
	 * @param string $metaKey
	 * @param mixed $value
	 */
	public function createOrUpdateMetakey($metakeyExists, $wpUser, $metaKey, $value) {
		// if user_meta_key doesnt exist create it, else update value
		if($metakeyExists != "") {
			$metakeyUpdateSuccessfull = update_user_meta($wpUser->ID, $metaKey, $value);

			// Dirty workaround to fix update = false because dbValue = valueToPersist
			if ($metakeyExists == $value) {
				$metakeyUpdateSuccessfull = true;
			}

			if($metakeyUpdateSuccessfull) {
				$this->logger->info("Set value of '" . $metaKey . "' to '" . $value . "' for user " . $wpUser->data->user_login);
			} else {
				$this->logger->info("Failed Updating value of " . $metaKey . " for user " . $wpUser->data->user_login);
			}

		} else {
			$metakeyCreateSuccessfull = add_user_meta($wpUser->ID, $metaKey, $value);

			if($metakeyCreateSuccessfull) {
				$this->logger->info("Created '" . $metaKey . "' with " . $value . " for user " . $wpUser->data->user_login);
			} else {
				$this->logger->info("Failed creating " . $metaKey . " for user " . $wpUser->data->user_login);
			}
		}
	}

	/**
	 * Deletes a meta attribute for a specific user by a specific key
	 *
	 * @param $metaKey
	 * @param $wpUser
	 */
	public function deleteMetakey($metaKey, $wpUser) {
		$metaKeyDeleteSuccessfull = delete_user_meta($wpUser->ID, $metaKey);

		if($metaKeyDeleteSuccessfull) {
			$this->logger->info("Successfully deleted'" . $metaKey . "' for user " . $wpUser->data->user_login);
		} else {
			$this->logger->info("Failed deleting " . $metaKey . " for user " . $wpUser->data->user_login);
		}
	}

	/**
	 * Returns meta value for a specific users by a specific key
	 *
	 * @param string $metakey
	 * @param WP_USER $wpUser
	 * @return mixed
	 */
	public function getMetaValue($metakey, $wpUser) {
		$metaValue = get_user_meta($wpUser->ID, $metakey, true);

		return $metaValue;
	}

	/**
	 * Load handler
	 * Loads settings and users and passes them to the frontend via JSON string
	 */
	public function loadSettings() {
		// load all users managed by this plugin by metakey
		$users = $this->findUsersManagedByThisPlugin();
		$userData = array();

		foreach ($users as $key => $wpUser) {
			$userData[$key] = new stdClass();
			$userData[$key]->username = $wpUser->data->user_login;
			$userData[$key]->roles = $this->findCustomRolesForUser($wpUser, false);
			$userData[$key]->cleanExistingRoles = $this->getCleanExistingRolesForUser($wpUser);
		}

		$data = ["userData" => $userData];

		// send data to frontend
		 echo json_encode($data);
	}

	/**
	 * Returns users managed by this plugin by checking for user meta attributes
	 *
	 * @return mixed
	 */
	public function findUsersManagedByThisPlugin() {
		$users = get_users(array(
			'meta_key'     => $this->isManagedByPeMetakey,
		));

		return $users;
	}

	/**
	 * Finds roles for specific user configured in the plugin settings
	 *
	 * @param $wpUser
	 * @param bool $convertForWordPress
	 * @return array
	 */
	public function findCustomRolesForUser($wpUser, $convertForWordPress = true) {

		// To apply roles to WordPress users we have to convert the frontend roles string to a format WordPress can handle.
		if ($convertForWordPress) {
			$rolesString = get_user_meta($wpUser->ID, $this->userCustomRolesMetakey, true);

			$roles = $this->splitRolesString($rolesString);

			return $roles;
		}

		// Here we are just returning the WordPress roles string for a specific user shown in the frontend
		$roles = get_user_meta($wpUser->ID, $this->userCustomRolesMetakey, true);

		return $roles;
	}

	/**
	 * Checks if the existing roles for a specific user managed by this plugin should be cleaned
	 *
	 * @param $wpUser
	 * @return mixed
	 */
	public function getCleanExistingRolesForUser($wpUser) {

		$cleanExistingRoles = get_user_meta($wpUser->ID, $this->cleanExistingRolesMetakey, true);

		return $cleanExistingRoles;
	}

	/**
	 * Converting the roles string from the frontend into an array to make it assignable to WordPress users
	 *
	 * @param $rolesString
	 * @return array
	 */
	public function splitRolesString($rolesString) {
		$roles = explode(';', strtolower($rolesString));

		return $roles;
	}

	//TODO Rücksprache mit @CKL NADI Default true;
	/**
	 * Globally clean existing roles for all users to mirror NADIs default role management behavior
	 *
	 * @param $cleanExistingRolesForAllUsers
	 * @param $wordPressRoles
	 * @param $wpUser
	 * @param $roleMapping
	 * @return mixed
	 */
	public function cleanExistingRolesForAllUsers($cleanExistingRolesForAllUsers, $wordPressRoles, $wpUser, $roleMapping) {

		$cleanExistingRolesPluginValue = $this->getCleanExistingRolesForUser($wpUser);

		if ($cleanExistingRolesPluginValue != "") {
			return $cleanExistingRolesPluginValue;
		}

		return $cleanExistingRolesForAllUsers;
	}


	/**
	 * Adds a new error to the error collector
	 * @param $username
	 * @param $msg
	 */
	public function addErrorToCollector($username, $msg) {

		$error = new stdClass();
		$error->user = $username;
		$error->msg = $msg;

		array_push($this->errorCollector, $error);
	}

	/**
	 * Retrieves ErrorCollector
	 *
	 * @return array
	 */
	public function getErrorCollector() {
		return $this->errorCollector;
	}

	/**
	 * Resetting the error collector
	 */
	public function resetErrorCollector() {
		$this->errorCollector = array();
	}

}