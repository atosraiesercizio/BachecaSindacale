<?php

include_once('../../includes/class-nadiext-custom-user-role-management-modifier.php');
require_once '../../vendor/autoload.php';

/**
 * Created by PhpStorm.
 * User: dme
 * Date: 06.02.2017
 * Time: 13:59
 */
class Ut_NADIExt_Custom_User_Role_Management_ModifierTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Logger
	 */
	private $logger;
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

	private $currentBlogId = 1;

	public function setUp()
	{
		$this->isManagedByPeMetakey = $this->isManagedByPeMetakey . $this->currentBlogId;
		$this->userCustomRolesMetakey = $this->userCustomRolesMetakey . $this->currentBlogId;
		$this->cleanExistingRolesMetakey = $this->cleanExistingRolesMetakey . $this->currentBlogId;
		\WP_Mock::setUp();
	}

	public function tearDown()
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NADIExt_Custom_User_Role_Management_Modifier | PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		\WP_Mock::wpFunction( 'get_current_blog_id', array(
			'return' => 1,
		) );

		return $this->getMockBuilder('NADIExt_Custom_User_Role_Management_Modifier')
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function modifyUserRoles_withUserNotManagedByThisPlugin_returnWordPressRoles() {

		if (!class_exists('NADIExt_Custom_User_Role_Management_Modifier') && !interface_exists('NADIExt_Custom_User_Role_Management_Modifier')) {
			echo "You create a new class/interface 'NADIExt_Custom_User_Role_Management_Modifier'. Be careful.";
		}

		$sut = $this->sut(array('findCustomRolesForUser', 'findUsersManagedByThisPlugin'));

		$wpUser = new stdClass();
		$wpUser->ID = 1;

		$wpUser2 = new stdClass();
		$wpUser2->ID = 2;

		$wordpressRoles = array(0 => 'administrator');

		$sut->expects($this->once())
			->method('findUsersManagedByThisPlugin')
			->willReturn(array($wpUser));

		$roles = $sut->modifyUserRoles($wordpressRoles, true, $wpUser2, array());

		$this->assertEquals($roles, $wordpressRoles);
	}

	/**
	 * @test
	 *
	 * cleanExistingRoles = false
	 * customRoles = empty array
	 *
	 */
	public function modifyUserRoles_withUserManagedByThisPlugin_returnWordPressRoles() {

		if (!class_exists('NADIExt_Custom_User_Role_Management_Modifier') && !interface_exists('NADIExt_Custom_User_Role_Management_Modifier')) {
			echo "You create a new class/interface 'NADIExt_Custom_User_Role_Management_Modifier'. Be careful.";
		}

		$sut = $this->sut(array('findCustomRolesForUser', 'findUsersManagedByThisPlugin'));

		$wordpressRoles = array(0 => 'administrator');

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = "wpUser1";
		$wpUser->roles = $wordpressRoles;

		$wpUser2 = new stdClass();
		$wpUser2->ID = 2;



		$sut->expects($this->once())
			->method('findUsersManagedByThisPlugin')
			->willReturn(array($wpUser));

		$sut->expects($this->once())
			->method('findCustomRolesForUser')
			->willReturn(array());

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $this->cleanExistingRolesMetakey, true),
			'return' => false,
		) );

		$roles = $sut->modifyUserRoles($wordpressRoles, true, $wpUser, array());

		$this->assertEquals($roles, $wordpressRoles);
	}

	/**
	 * @test
	 *
	 * cleanExistingRoles = true
	 * customRoles = empty array
	 *
	 */
	public function modifyUserRoles_withUserManagedByThisPlugin_cleanRoles_returnWordPressRoles() {

		if (!class_exists('NADIExt_Custom_User_Role_Management_Modifier') && !interface_exists('NADIExt_Custom_User_Role_Management_Modifier')) {
			echo "You create a new class/interface 'NADIExt_Custom_User_Role_Management_Modifier'. Be careful.";
		}

		$sut = $this->sut(array('findCustomRolesForUser', 'findUsersManagedByThisPlugin'));

		$wordpressRoles = array(0 => 'administrator');

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = "wpUser1";
		$wpUser->roles = $wordpressRoles;

		$wpUser2 = new stdClass();
		$wpUser2->ID = 2;



		$sut->expects($this->once())
			->method('findUsersManagedByThisPlugin')
			->willReturn(array($wpUser));

		$sut->expects($this->once())
			->method('findCustomRolesForUser')
			->willReturn(array('editor'));

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $this->cleanExistingRolesMetakey, true),
			'return' => true,
		) );

		$roles = $sut->modifyUserRoles($wordpressRoles, true, $wpUser, array());
		$expectedRoles =  array('editor');

		$this->assertEquals($roles, $expectedRoles);
	}

	/**
	 * @test
	 *
	 * cleanExistingRoles = true
	 * customRoles = array
	 *
	 */
	public function modifyUserRoles_withUserManagedByThisPlugin_keepRoles_addRoleEquivRoles_addCustomRoles_returnWordPressRoles() {

		if (!class_exists('NADIExt_Custom_User_Role_Management_Modifier') && !interface_exists('NADIExt_Custom_User_Role_Management_Modifier')) {
			echo "You create a new class/interface 'NADIExt_Custom_User_Role_Management_Modifier'. Be careful.";
		}

		$sut = $this->sut(array('findCustomRolesForUser', 'findUsersManagedByThisPlugin'));

		$wordpressRoles = array(0 => 'administrator');

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = "wpUser1";
		$wpUser->roles = array('subscriber');

		$wpUser2 = new stdClass();
		$wpUser2->ID = 2;



		$sut->expects($this->once())
			->method('findUsersManagedByThisPlugin')
			->willReturn(array($wpUser));

		$sut->expects($this->once())
			->method('findCustomRolesForUser')
			->willReturn(array('editor'));

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $this->cleanExistingRolesMetakey, true),
			'return' => false,
		) );

		$roles = $sut->modifyUserRoles($wordpressRoles, true, $wpUser, array());
		$expectedRoles =  array('administrator', 'editor', 'subscriber');

		$this->assertEquals($roles, $expectedRoles);
	}

	/**
	 * @test //TODO react on print message to validate condition check
	 *
	 */
	public function deleteMetakey_withKeyDeletedSuccessfully_logSucessfullMessage() {
		$sut = $this->sut(null);

		$metakey = 'test';
		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = "wpUser1";

		\WP_Mock::wpFunction( 'delete_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $metakey),
			'return' => true,
		) );

		$sut->deleteMetakey($metakey, $wpUser);
	}

	/**
	 * @test //TODO react on print message to validate condition check
	 *
	 */
	public function deleteMetakey_withKeyDeletedFailed_logFailedMessage() {
		$sut = $this->sut(null);

		$metakey = 'test';
		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = "wpUser1";

		\WP_Mock::wpFunction( 'delete_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $metakey),
			'return' => false,
		) );

		$sut->deleteMetakey($metakey, $wpUser);
	}

	/**
	 * @test
	 */
	public function getMetaValue_returnUserMeta() {

		$sut = $this->sut(null);

		$metakey = 'test';
		$wpUser = new stdClass();
		$wpUser->ID = 1;

		$value = 'value';

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $metakey, true),
			'return' => $value,
		) );

		$actual = $sut->getMetaValue($metakey, $wpUser);

		$this->assertEquals($value, $actual);

	}

	/**
	 * @test
	 */
	public function getMetaValue_returnFalse() {

		$sut = $this->sut(null);

		$metakey = 'test';
		$wpUser = new stdClass();
		$wpUser->ID = 1;

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $metakey, true),
			'return' => false,
		) );

		$actual = $sut->getMetaValue($metakey, $wpUser);

		$this->assertEquals(false, $actual);

	}

	/**
	 * @test
	 */
	public function splitRolesString()
	{
		$sut = $this->sut(null);
		$roleString = 'administrator;editor;subscriber';
		$expected = array('administrator', 'editor', 'subscriber');

		$actual = $sut->splitRolesString($roleString);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function cleanExistingRolesForUser_returnTrue() {
		$sut = $this->sut(null);

		$metakey = 'next_ad_int_pe_crm_clean_existing_roles_1';
		$wpUser = new stdClass();
		$wpUser->ID = 1;

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $metakey, true),
			'return' => true,
		) );

		$actual = $sut->getCleanExistingRolesForUser($wpUser);

		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function cleanExistingRolesForUser_returnFalse() {
		$sut = $this->sut(null);

		$metakey = 'next_ad_int_pe_crm_clean_existing_roles_1';
		$wpUser = new stdClass();
		$wpUser->ID = 1;

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array( $wpUser->ID, $metakey, true),
			'return' => false,
		) );

		$actual = $sut->getCleanExistingRolesForUser($wpUser);

		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function findUserManagedByThisPlugin_returnUser() {

		$sut = $this->sut(null);
		$wpUser = new stdClass();
		$users = array($wpUser);

		\WP_Mock::wpFunction( 'get_users', array(
			'times' => 1,
			'args' => array(array('meta_key' => 'next_ad_int_pe_crm_is_managed_by_pe_1')),
			'return' => array($wpUser)
		));

		$actual = $sut->findUsersManagedByThisPlugin();

		$this->assertEquals($users, $actual);

	}

	/**
	 * @test
	 */
	public function findUserManagedByThisPlugin_returnNull() {

		$sut = $this->sut(null);

		\WP_Mock::wpFunction( 'get_users', array(
			'times' => 1,
			'args' => array(array('meta_key' => 'next_ad_int_pe_crm_is_managed_by_pe_1')),
			'return' => null
		));

		$actual = $sut->findUsersManagedByThisPlugin();

		$this->assertEquals(null, $actual);

	}


	/**
	 * @test
	 */
	public function addErrorToCollector_pushError() {

		$sut = $this->sut(null);

		$username = 'admin';
		$msg = 'Something wen´t wrong!';

		$expectedError = new stdClass();
		$expectedError->user = $username;
		$expectedError->msg = $msg;

		$expectedErrorCollector = array($expectedError);


		$sut->addErrorToCollector($username, $msg);

		$actual = $sut->getErrorCollector();

		$this->assertEquals($expectedErrorCollector, $actual);

	}

	/**
	 * @test
	 */
	public function getErrorToCollector_withResetErrorCollector_returnEmptyArray() {

		$sut = $this->sut(null);

		$username = 'admin';
		$msg = 'Something wen´t wrong!';

		$expectedError = new stdClass();
		$expectedError->user = $username;
		$expectedError->msg = $msg;

		$expectedErrorCollector = array($expectedError);

		$sut->addErrorToCollector($username, $msg);

		$actual = $sut->getErrorCollector();

		$this->assertEquals($expectedErrorCollector, $actual);

		$sut->resetErrorCollector();

		$actual = $sut->getErrorCollector();

		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function findCustomRolesForUser_returnRolesAsStringForFrontend() {
		$sut = $this->sut(array('splitRolesString'));

		$wpUser = new stdClass();
		$wpUser->ID = 1;

		$sut->expects($this->never())
			->method('splitRolesString');

		$expectedRoles = 'administrator;editor;contributor';

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, 'next_ad_int_pe_crm_user_custom_roles_1', true),
			'return' => $expectedRoles
		));

		$actualRoles = $sut->findCustomRolesForUser($wpUser, false);

		$this->assertEquals($expectedRoles, $actualRoles);

	}

	/**
	 * @test
	 */
	public function findCustomRolesForUser_returnRolesAsArrayForBackend() {

		$sut = $this->sut(array('splitRolesString'));

		$wpUser = new stdClass();
		$wpUser->ID = 1;

		$rolesString = 'administrator;editor;contributor';
		$expectedRoles = array('administrator', 'editor', 'contributor');

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, 'next_ad_int_pe_crm_user_custom_roles_1', true),
			'return' => $rolesString
		));

		$sut->expects($this->once())
			->method('splitRolesString')
			->with($rolesString)
			->willReturn($expectedRoles);

		$actualRoles = $sut->findCustomRolesForUser($wpUser, true);

		$this->assertEquals($expectedRoles, $actualRoles);
	}

	/**
	 * @test
	 */
	public function loadSettings_returnJsonString() {

		$sut = $this->sut(array('findUsersManagedByThisPlugin', 'findCustomRolesForUser', 'getCleanExistingRolesForUser'));

		$user1 = new stdClass();
		$user1->ID = 1;
		$user1->data = new stdClass();
		$user1->data->user_login = 'user1';

		$user2 = new stdClass();
		$user2->ID = 2;
		$user2->data = new stdClass();
		$user2->data->user_login = 'user2';

		$users = array($user1, $user2);

		$sut->expects($this->once())
			->method('findUsersManagedByThisPlugin')
			->willReturn($users);

		$sut->expects($this->exactly(2))
			->method('findCustomRolesForUser')
			->withConsecutive(
				array($user1, false),
				array($user2, false)
			)
			->willReturnOnConsecutiveCalls(
				'administrator;subscriber',
				'administrator;editor'
			);

		$sut->expects($this->exactly(2))
			->method('getCleanExistingRolesForUser')
			->withConsecutive(
				array($user1),
				array($user2)
			)
			->willReturnOnConsecutiveCalls(
				false,
				false
			);


		$sut->loadSettings();
	}

	/**
	 * @test
	 */
	public function createOrUpdateMetakey_withUpdateUserMetaSuccessful() {
		$sut = $this->sut(null);

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = '$wpUser1';

		$metaKey = 'nadiExt_metakey';

		\WP_Mock::wpFunction( 'update_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, $metaKey, 123),
			'return' => true
		));

		$sut->createOrUpdateMetakey('something', $wpUser, 'nadiExt_metakey', 123);
	}

	/**
	 * @test
	 */
	public function createOrUpdateMetakey_withUpdateUserMetaFailed() {
		$sut = $this->sut(null);

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = '$wpUser1';

		$metaKey = 'nadiExt_metakey';

		\WP_Mock::wpFunction( 'update_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, $metaKey, 123),
			'return' => false
		));

		$sut->createOrUpdateMetakey('something', $wpUser, 'nadiExt_metakey', 123);
	}

	/**
	 * @test
	 */
	public function createOrUpdateMetakey_withUpdateValueAlreadyUpToDate() {
		$sut = $this->sut(null);

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = '$wpUser1';

		$metaKey = 'nadiExt_metakey';

		\WP_Mock::wpFunction( 'update_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, $metaKey, 'something'),
			'return' => false
		));

		$sut->createOrUpdateMetakey('something', $wpUser, 'nadiExt_metakey', 'something');
	}

	/**
	 * @test
	 */
	public function createOrUpdateMetakey_withCreateUserMetaSuccessful() {
		$sut = $this->sut(null);

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = '$wpUser1';

		$metaKey = 'nadiExt_metakey';

		\WP_Mock::wpFunction( 'update_user_meta', array(
			'times' => 0,
			'args' => array($wpUser->ID, $metaKey, 123),
			'return' => false
		));

		\WP_Mock::wpFunction( 'add_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, $metaKey, 123),
			'return' => true
		));

		$sut->createOrUpdateMetakey('', $wpUser, 'nadiExt_metakey', 123);
	}

	/**
	 * @test
	 */
	public function createOrUpdateMetakey_withCreateUserMetaFailed() {
		$sut = $this->sut(null);

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = '$wpUser1';

		$metaKey = 'nadiExt_metakey';

		\WP_Mock::wpFunction( 'update_user_meta', array(
			'times' => 0,
			'args' => array($wpUser->ID, $metaKey, 123),
			'return' => false
		));

		\WP_Mock::wpFunction( 'add_user_meta', array(
			'times' => 1,
			'args' => array($wpUser->ID, $metaKey, 123),
			'return' => false
		));

		$sut->createOrUpdateMetakey('', $wpUser, 'nadiExt_metakey', 123);
	}

	/**
	 * @test
	 */
	public function saveSettings_withUsersToSaveAndToDelete_returnJsonString() {

		$sut = $this->sut(array('setCleanExistingRolesForAllUsers', 'addErrorToCollector', 'getMetaValue', 'createOrUpdateMetakey', 'deleteMetakey', 'getErrorCollector', 'resetErrorCollector'));

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = 'user1';

		$wpUser2 = new stdClass();
		$wpUser2->ID = 1;
		$wpUser2->data = new stdClass();
		$wpUser2->data->user_login = 'user2';

		$cleanExistingRolesForAllUsers = 1;

		$data = array();
		$data['saveUsers'][0] = array('username' => 'user1', 'roles' => 'administrator;editor;subscriber', 'cleanExistingRoles' => 0);
		$data['deleteUsers'][0] = array('username' => 'user2', 'roles' => 'administrator;editor;subscriber', 'cleanExistingRoles' => 0);

		$_POST['data'] = $data;
		$_POST['data']['cleanExistingRolesForAllUsers'] = $cleanExistingRolesForAllUsers;

		\WP_Mock::wpFunction( 'get_user_by', array(
			'times' => 1,
			'args' => array('login', $data['saveUsers'][0]['username']),
			'return' => $wpUser
		));

		\WP_Mock::wpFunction( 'get_user_by', array(
			'times' => 1,
			'args' => array('login', $data['deleteUsers'][0]['username']),
			'return' => $wpUser2
		));

		$sut->expects($this->exactly(3))
			->method('getMetaValue')
			->withConsecutive(
				array($this->isManagedByPeMetakey, $wpUser),
				array($this->userCustomRolesMetakey, $wpUser),
				array($this->cleanExistingRolesMetakey, $wpUser)
			)
			->willReturnOnConsecutiveCalls(
				1,
				'administrator;editor;subscriber',
				0
			);

		$sut->expects($this->exactly(3))
			->method('createOrUpdateMetakey')
			->withConsecutive(
				array(1, $wpUser, $this->isManagedByPeMetakey, 1),
				array('administrator;editor;subscriber', $wpUser, $this->userCustomRolesMetakey, 'administrator;editor;subscriber'),
				array(0, $wpUser, $this->cleanExistingRolesMetakey, 0)
			);

		$sut->expects($this->exactly(3))
			->method('deleteMetakey')
			->withConsecutive(
				array($this->isManagedByPeMetakey, $wpUser2),
				array($this->userCustomRolesMetakey, $wpUser2),
				array($this->cleanExistingRolesMetakey, $wpUser2)
			)
			->willReturnOnConsecutiveCalls(
				'administrator;subscriber',
				'administrator;editor',
				'administrator;editor'
			);

		$sut->expects($this->once())
			->method('getErrorCollector')
			->willReturn(array());

		$sut->expects($this->never())
			->method('resetErrorCollector');

		$sut->saveSettings();

	}

	/**
	 * @test
	 */
	public function saveSettings_withErrorOccured_returnJsonString() {

		$sut = $this->sut(array('setCleanExistingRolesForAllUsers', 'addErrorToCollector', 'getMetaValue', 'createOrUpdateMetakey', 'deleteMetakey', 'getErrorCollector', 'resetErrorCollector'));

		$wpUser = new stdClass();
		$wpUser->ID = 1;
		$wpUser->data = new stdClass();
		$wpUser->data->user_login = 'wpuser1';

		$wpUser2 = new stdClass();
		$wpUser2->ID = 1;
		$wpUser2->data = new stdClass();
		$wpUser2->data->user_login = 'wpuser2';

		$cleanExistingRolesForAllUsers = 1;

		$data = array();
		$data['saveUsers'][0] = array('username' => 'wpuser1', 'roles' => 'administrator;editor;subscriber', 'cleanExistingRoles' => 0);
		$data['deleteUsers'][0] = array('username' => 'wpuser2', 'roles' => 'administrator;editor;subscriber', 'cleanExistingRoles' => 0);

		$_POST['data'] = $data;
		$_POST['data']['cleanExistingRolesForAllUsers'] = $cleanExistingRolesForAllUsers;

		\WP_Mock::wpFunction( 'get_user_by', array(
			'times' => 1,
			'args' => array('login', $data['saveUsers'][0]['username']),
			'return' => null
		));

		\WP_Mock::wpFunction( 'get_user_by', array(
			'times' => 1,
			'args' => array('login', $data['deleteUsers'][0]['username']),
			'return' => $wpUser2
		));

		$sut->expects($this->once())
			->method('addErrorToCollector')
			->with('wpuser1', 'Could not find WordPress User with user_login: wpuser1');

		$sut->expects($this->never())
			->method('getMetaValue');

		$sut->expects($this->never())
			->method('createOrUpdateMetakey');

		$sut->expects($this->exactly(3))
			->method('deleteMetakey')
			->withConsecutive(
				array($this->isManagedByPeMetakey, $wpUser2),
				array($this->userCustomRolesMetakey, $wpUser2),
				array($this->cleanExistingRolesMetakey, $wpUser2)
			);

		$sut->expects($this->exactly(2))
			->method('getErrorCollector')
			->willReturn(array('error1', 'error2'));

		$sut->expects($this->once())
			->method('resetErrorCollector');

		$sut->saveSettings();

	}

}
