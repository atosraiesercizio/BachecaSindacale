<?php

/**
 * Class CRED_Form_Builder_Base
 */
class CRED_Form_Builder_Base {

	var $_post_to_create;

	/**
	 * CRED_Form_Builder_Base constructor.
	 */
	public function __construct() {
		// parse cred form output
		add_action( 'wp_loaded', array( $this, 'init' ), 10 );
		// load front end form assets
		add_action( 'wp_head', array( __CLASS__, 'loadFrontendAssets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'unloadFrontendAssets' ) );
	}

	/**
	 * init
	 */
	public function init() {
		if ( ! is_admin() ) {
			if ( array_key_exists( CRED_StaticClass::PREFIX . 'form_id', $_POST ) &&
				array_key_exists( CRED_StaticClass::PREFIX . 'form_count', $_POST )
			) {
				$form_id = intval( $_POST[ CRED_StaticClass::PREFIX . 'form_id' ] );
				$form_count = intval( $_POST[ CRED_StaticClass::PREFIX . 'form_count' ] );
				$post_id = ( array_key_exists( CRED_StaticClass::PREFIX . 'post_id', $_POST ) ) ? intval( $_POST[ CRED_StaticClass::PREFIX . 'post_id' ] ) : false;
				$preview = ( array_key_exists( CRED_StaticClass::PREFIX . 'form_preview_content', $_POST ) ) ? true : false;

				return $this->get_form( $form_id, $post_id, $form_count, $preview );
			}
		}
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_Post|CRED_Form_User
	 */
	protected function get_cred_form_object( $form_id, $post_id, $form_count, $preview ) {
		$type_form = get_post_type( $form_id );
		switch ( $type_form ) {
			case CRED_USER_FORMS_CUSTOM_POST_NAME:
				$form = $this->get_user_form( $form_id, $post_id, $form_count, $preview );
				break;
			default:
			case CRED_FORMS_CUSTOM_POST_NAME:
				$form = $this->get_post_form( $form_id, $post_id, $form_count, $preview );
				break;

		}

		CRED_StaticClass::initVars();

		return $form;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_Post
	 */
	private function get_post_form( $form_id, $post_id, $form_count, $preview ) {
		$form = new CRED_Form_Post( $form_id, $post_id, $form_count, $preview );
		if ( isset( $form->_post_id ) ) {
			$parent_post = get_post( $form->_post_id );
		}
		if (
			$form->_formData->hasHideComments() ||
			( isset( $parent_post ) && $parent_post->comment_status == 'closed' )
		) {
			CRED_Form_Builder_Helper::hideComments();
		}

		return $form;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_User
	 */
	private function get_user_form( $form_id, $post_id, $form_count, $preview ) {
		$form = new CRED_Form_User( $form_id, $post_id, $form_count, $preview );
		CRED_Form_Builder_Helper::hideComments();

		return $form;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return bool
	 */
	public function get_form( $form_id, $post_id = false, $form_count = -1, $preview = false ) {

		if ( $form_count == -1 ) {
			$form_count = CRED_StaticClass::$_staticGlobal['COUNT'];
		}

		global $post;
		CRED_StaticClass::$_cred_container_id = ( isset( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) ) ? intval( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) : ( isset( $post ) ? $post->ID : "" );

		//Security Check
		if ( isset( CRED_StaticClass::$_cred_container_id ) && ! empty( CRED_StaticClass::$_cred_container_id ) ) {
			if ( ! is_numeric( CRED_StaticClass::$_cred_container_id ) ) {
				wp_die( 'Invalid data' );
			}
		}

		$form = $this->get_cred_form_object( $form_id, $post_id, $form_count, $preview );
		$type_form = $form->get_type_form();
		$output = $form->print_form();

		CRED_StaticClass::$_staticGlobal['COUNT']++;

		if ( ! is_wp_error( $output ) ) {
			$html_form_id = get_cred_html_form_id( $type_form, $form_id, $form_count );

			/**
			 * cred_after_rendering_form_{$form_id}
			 *
			 *  This action is fired after rendering a CRED form (but before actual printing of the output).
			 *
			 * @param string $html_form_id ID of the main form element
			 * @param int $form_id CRED form id.
			 * @param string $type_form Post type of the form.
			 * @param int $form_count Number of forms rendered so far.
			 *
			 * @since 1.9
			 */
			do_action( 'cred_after_rendering_form_' . $form_id, $html_form_id, $form_id, $type_form, $form_count );

			return $output;
		}
	}

	// load frontend assets on init
	public static function loadFrontendAssets() {
	}

	// unload frontend assets if no form rendered on page
	public static function unloadFrontendAssets() {
		//Print custom js/css on front-end
		$custom_js_cache = wp_cache_get( 'cred_custom_js_cache' );
		if ( false !== $custom_js_cache ) {
			echo "\n<script type='text/javascript' class='custom-js'>\n";
			echo html_entity_decode( $custom_js_cache, ENT_QUOTES ) . "\n";
			echo "</script>\n";
		}

		$custom_css_cache = wp_cache_get( 'cred_custom_css_cache' );
		if ( false !== $custom_css_cache ) {
			echo "\n<style type='text/css'>\n";
			echo $custom_css_cache . "\n";
			echo "</style>\n";
		}
	}

}