<?php

/**
 * Base Class for CRED Post and User Forms
 */
abstract class CRED_Form_Base implements ICRED_Form_Base {

    protected $_form_id;
	protected $_form_count;
	protected $_post_id;
	protected $_preview;

	/**
	 * @var false|string [CRED_FORMS_CUSTOM_POST_NAME|CRED_USER_FORMS_CUSTOM_POST_NAME]
	 */
	protected $_type_form;
    //post data
	/**
	 * @var object post data
	 */
    public $_postData;
	/**
	 * @var CRED_Form_Data
	 */
    public $_formData;
	/**
	 * @var CRED_Form_Rendering
	 */
    public $_zebraForm;
	/**
	 * @var null|object
	 */
    public $_shortcodeParser;
	/**
	 * @var CRED_Form_Builder_Helper
	 */
    public $_formHelper;
	/**
	 * @var string
	 */
    public $_content;
	/**
	 * @var string
	 */
	protected $_post_type;
	/**
	 * @var bool|void
	 */
    protected $_disable_progress_bar;
	/**
	 * @var bool
	 */
    public static $_self_updated_form = false;

	/**
	 * CRED_Form_Base constructor.
	 *
	 * @param int $_form_id
	 * @param int|bool $_post_id
	 * @param int $_form_count
	 * @param bool $_preview
	 */
    public function __construct($_form_id, $_post_id = false, $_form_count = 0, $_preview = false) {
        $this->_form_id = $_form_id;
        $this->_post_id = $_post_id;
        $this->_form_count = $_form_count;
        $this->_preview = $_preview;

        $this->_type_form = get_post_type( $_form_id );

        $this->_formData = new CRED_Form_Data( $this->_form_id, $this->_type_form, $this->_preview );

        // shortcodes parsed by custom shortcode parser
        $this->_shortcodeParser = CRED_Loader::get( 'CLASS/Shortcode_Parser' );
        // various functions performed by custom form helper
        require_once CRED_ABSPATH . '/library/toolset/cred/embedded/classes/Form_Builder_Helper.php';
        $this->_formHelper = new CRED_Form_Builder_Helper( $this ); //CRED_Loader::get('CLASS/Form_Helper', $this);

        $this->_disable_progress_bar = version_compare( CRED_FE_VERSION, '1.3.6.2', '<=' );
        $this->_disable_progress_bar = apply_filters( 'cred_file_upload_disable_progress_bar', $this->_disable_progress_bar );
    }

	/**
	 * @return int
	 */
	public function get_form_id() {
		return $this->_form_id;
	}

	/**
	 * @param int $form_id
	 */
	public function set_form_id( $form_id ) {
		$this->_form_id = $form_id;
	}

	/**
	 * @return int
	 */
	public function get_form_count() {
		return $this->_form_count;
	}

	/**
	 * @param int $form_count
	 */
	public function set_form_count( $form_count ) {
		$this->_form_count = $form_count;
	}

	/**
	 * @return bool|int
	 */
	public function get_post_id() {
		return $this->_post_id;
	}

	/**
	 * @param bool|int $post_id
	 */
	public function set_post_id( $post_id ) {
		$this->_post_id = $post_id;
	}

	/**
	 * @return bool
	 */
	public function is_preview() {
		return $this->_preview;
	}

	/**
	 * @param bool $preview
	 */
	public function set_preview( $preview ) {
		$this->_preview = $preview;
	}

	/**
	 * @return false|string
	 */
	public function get_type_form() {
		return $this->_type_form;
	}

	/**
	 * @param false|string $type_form
	 */
	public function set_type_form( $type_form ) {
		$this->_type_form = $type_form;
	}

    /**
     * @global int $post
     * @global WP_User $authordata
     * @return boolean
     */
    public function print_form() {
        add_filter( 'wp_revisions_to_keep', 'cred__return_zero', 10, 2 );

        $bypass_form = apply_filters( 'cred_bypass_process_form_' . $this->_form_id, false, $this->_form_id, $this->_post_id, $this->_preview );
        $bypass_form = apply_filters( 'cred_bypass_process_form', $bypass_form, $this->_form_id, $this->_post_id, $this->_preview );

        if ( is_wp_error( $this->_formData ) ) {
            return false;
        }

        $formHelper = $this->_formHelper;

        $form = $this->_formData;
        $form_fields = $form->getFields();
        $_form_type = $form_fields['form_settings']->form['type'];
        $_post_type = $form_fields['form_settings']->post['post_type'];

        $this->set_authordata();

        $result = $this->create_new_post( $this->_form_id, $_form_type, $this->_post_id, $_post_type );
        if ( is_wp_error( $result ) ) {
            return false;
        }

        // check if user has access to this form
        if ( !$this->_preview &&
                !$this->check_form_access( $_form_type, $this->_form_id, $this->_postData, $formHelper ) ) {
            return $formHelper->error();
        }

        // set allowed file types
        CRED_StaticClass::$_staticGlobal['MIMES'] = $formHelper->getAllowedMimeTypes();

        // get custom post fields
        $fields_settings = $formHelper->getFieldSettings( $_post_type );

        // strip any unneeded parsms from current uri
        $actionUri = $formHelper->currentURI( array(
            '_tt' => time()       // add time get bypass cache
                ), array(
            '_success', // remove previous success get if set
            '_success_message'   // remove previous success get if set
                ) );

        $prg_form_id = $this->createPrgID( $this->_form_id, $this->_form_count );
        $my_form_id = $this->createFormID( $this->_form_id, $this->_form_count );

        $zebraForm = new CRED_Form_Rendering( $this->_form_id, $my_form_id, $_form_type, $this->_post_id, $actionUri, $this->_preview );
        $this->_zebraForm = $zebraForm;
        $this->_zebraForm->setFormHelper( $formHelper );
        $this->_zebraForm->setLanguage( CRED_StaticClass::$_staticGlobal['LOCALES'] );

        if ( $formHelper->isError( $this->_zebraForm ) ) {
            return $this->_zebraForm;
        }

        // all fine here
        $this->_post_type = $_post_type;
        $this->_content = $form->getForm()->post_content;

        CRED_StaticClass::$out['fields'] = $fields_settings;
        CRED_StaticClass::$out['count'] = $this->_form_count;
        CRED_StaticClass::$out['prg_id'] = $prg_form_id;

        //####################################################################################//

        $zebraForm->_formData = $form;

        $fields = $form->getFields();
        $zebraForm->extra_parameters = $form_fields['extra'];

        $form_id = $this->_form_id;
        $form_type = $fields['form_settings']->form['type'];

	    $form_use_ajax = ( isset( $fields['form_settings']->form['use_ajax'] ) && $fields['form_settings']->form['use_ajax'] == 1 ) ? true : false;
	    $is_ajax = $this->is_cred_ajax( $form_use_ajax );

        $prg_id = CRED_StaticClass::$out['prg_id'];
        $form_count = CRED_StaticClass::$out['count'];
	    $form_name = $this->createFormID( $form_id, $form_count );

        $post_type = $fields['form_settings']->post['post_type'];
        // show display message from previous submit of same create form (P-R-G pattern)
        if (
                !$zebraForm->preview && /* 'edit'!=$form_type && (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation'!=$form_type) && */
                isset( $_GET['_success_message'] ) &&
                $_GET['_success_message'] == $prg_id &&
                'message' == $form_fields['form_settings']->form['action']
        ) {
            $zebraForm->is_submit_success = true;
            return $formHelper->display_message( $form );
        } else {
            $zebraForm->is_submit_success = $this->isSubmitted();
        }

        // no message to display if not submitted
        $message = false;

        $current_form = array(
            'id' => $form_id,
            'post_type' => $post_type,
            'form_type' => $form_type,
            'form_html_id' => '#' . $form_name
        );

        CRED_StaticClass::$_current_post_title = $form->getForm()->post_title;
        CRED_StaticClass::$_current_form_id = $form_id;

        /**
         * fix dates
         */
        $this->adodb_date_fix_date_and_time();

        $mime_types = wp_get_mime_types();
        CRED_StaticClass::$_allowed_mime_types = array_merge( $mime_types, array('xml' => 'text/xml') );
        CRED_StaticClass::$_allowed_mime_types = apply_filters( 'upload_mimes', CRED_StaticClass::$_allowed_mime_types );

        /**
         * sanitize input data
         */
        if ( !array_key_exists( 'post_fields', CRED_StaticClass::$out['fields'] ) ) {
            CRED_StaticClass::$out['fields']['post_fields'] = array();
        }

        /**
         * fixed Server side error messages should appear next to the field with the problem
         */
        $formHelper->checkFilePost( $zebraForm, CRED_StaticClass::$out['fields']['post_fields'] );
        if ( isset( CRED_StaticClass::$out['fields']['post_fields'] ) && isset( CRED_StaticClass::$out['form_fields_info'] ) ) {
            $formHelper->checkFilesType( CRED_StaticClass::$out['fields']['post_fields'], CRED_StaticClass::$out['form_fields_info'], $zebraForm, $error_files );
        }

        CRED_StaticClass::$_reset_file_values = ($is_ajax && $form_type == 'new' && $form_fields['form_settings']->form['action'] == 'form' && self::$_self_updated_form);

        $cloned = false;
        if ( isset( $_POST ) && !empty( $_POST ) ) {
            $cloned = true;
            $temp_post = $_POST;
        }

	    if ( ! self::$_self_updated_form ) {
		    CRED_Frontend_Preserve_Taxonomy_Input::initialize();
	    } else {
		    CRED_Frontend_Preserve_Taxonomy_Input::get_instance()->remove_filters();
	    }

        $this->try_to_reset_submit_post_fields();

        $this->build_form();

	    if ( $cloned ) {
		    $_POST = $temp_post;
	    }

        $num_errors = 0;
        $validate = (self::$_self_updated_form) ? true : $this->validate_form( $error_files );

        if ( $form_use_ajax ) {
	        $bypass_form = self::$_self_updated_form;
        }

	    if (  ! empty( $_POST )
	          && array_key_exists( CRED_StaticClass::PREFIX . 'form_id', $_POST )
	          && $_POST[ CRED_StaticClass::PREFIX . 'form_id' ] != $form_id
	    ) {
		    $output = $this->render_form();
		    $cred_response = new CRED_Generic_Response( $num_errors > 0 ? CRED_GENERIC_RESPONSE_RESULT_KO : CRED_GENERIC_RESPONSE_RESULT_OK, $output, $is_ajax, $current_form, $formHelper );

		    return $cred_response->show();
	    }

	    if ( ! $bypass_form
		    && $validate
	    ) {
		    if ( ! $zebraForm->preview ) {
                // save post data
                $bypass_save_form_data = apply_filters( 'cred_bypass_save_data_' . $form_id, false, $form_id, $this->_post_id, $current_form );
                $bypass_save_form_data = apply_filters( 'cred_bypass_save_data', $bypass_save_form_data, $form_id, $this->_post_id, $current_form );

                if ( !$bypass_save_form_data ) {
                    $model = CRED_Loader::get( 'MODEL/Forms' );
                    $attachedData = $model->getAttachedData( $this->_post_id );
                    $post_id = $this->save_form( $this->_post_id );
                }

                if ( is_wp_error( $post_id ) ) {
                    $num_errors++;
                    $zebraForm->add_field_message( $post_id->get_error_message(), 'Post Name' );
                } else {
                    $result = $this->check_redirection( $post_id, $form_id, $form, $fields, $current_form, $formHelper, $is_ajax, $attachedData );
                    if ( $result != false ) {
                        return $result;
                    } else {
                        $this->add_field_messages_by_files($zebraForm, $formHelper);
                    }
                }
            } else {
                $zebraForm->add_field_message( __( 'Preview Form submitted', 'wp-cred' ) );
            }
        } else if ( $this->isSubmitted() ) {
        	//Reset form_count in case of failed validation
		    CRED_StaticClass::$_staticGlobal['COUNT'] = 0;
		    $this->_form_count = 1;
			$this->set_submitted_form_messages($form_id, $form_name, $num_errors, $zebraForm, $formHelper);
        }

        if (
                (
                isset( $_GET['_success'] )
                && $_GET['_success'] == $prg_id
                )
                || (
                $is_ajax
                && self::$_self_updated_form
                )
        ) {
	        if ( isset( $_GET['_target'] )
		        && is_numeric( $_GET['_target'] )
	        ) {
		        $post_id = $_GET['_target'];
	        }

            $saved_message = $formHelper->getLocalisedMessage( 'post_saved' );

	        if ( isset( $post_id )
		        && is_int( $post_id )
	        ) {
		        // add success message from previous submit of same any form (P-R-G pattern)
		        $saved_message = apply_filters( 'cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $this->_preview );
		        $saved_message = apply_filters( 'cred_data_saved_message', $saved_message, $form_id, $post_id, $this->_preview );
	        }

            //$zebraForm->add_form_message('data-saved', $saved_message);
            $zebraForm->add_success_message( $saved_message );
        }

	    if ( $validate
		    && $is_ajax
		    && ! self::$_self_updated_form
	    ) {
		    self::$_self_updated_form = true;

		    $this->print_form();
	    } else {

		    $messages = $zebraForm->getFieldsSuccessMessages( ($is_ajax ? $form_name : "") );
		    $messages .= $zebraForm->getFieldsErrorMessages();
		    $js_messages = $zebraForm->getFieldsErrorMessagesJs();

		    if ( false !== $message ) {
			    $output = $message;
		    } else {
			    $output = $this->render_form( $messages, $js_messages );
		    }
		    $cred_response = new CRED_Generic_Response( $num_errors > 0 ? CRED_GENERIC_RESPONSE_RESULT_KO : CRED_GENERIC_RESPONSE_RESULT_OK, $output, $is_ajax, $current_form, $formHelper );

		    return $cred_response->show();
	    }
    }

	/**
	 * @param $form_uses_ajax
	 *
	 * @return bool
	 */
    private function is_cred_ajax($form_uses_ajax) {
	    $is_ajax = (cred_is_ajax_call() && $form_uses_ajax);

	    //Fixing when CRED Form is called by external plugins using ajax
	    if ( $is_ajax
		    && ! $this->isSubmitted()
		    && isset( $_REQUEST['action'] )
		    && in_array( $_REQUEST['action'], Toolset_Utils::get_ajax_actions_array_to_exclude_on_frontend() )
	    ) {
		    $is_ajax = false;
	    }

	    return $is_ajax;
    }

	/**
	 * Function used to reset $_POST during a AJAX CRED Form submition elaboration
	 */
    private function try_to_reset_submit_post_fields() {
	    if ( CRED_StaticClass::$_reset_file_values ) {

	    	//Reset post fields
		    foreach ( CRED_StaticClass::$out['fields']['post_fields'] as $field_key => $field_value ) {
			    $field_name = isset( $field_value['plugin_type_prefix'] ) ? $field_value['plugin_type_prefix'] . $field_key : $field_key;
			    if ( isset( $_POST[$field_name] ) ) {
				    unset( $_POST[$field_name] ); // = array();
			    }
		    }

		    if( isset (CRED_StaticClass::$out['fields']['user_fields']) ) {
			    //Reset user fields
			    foreach ( CRED_StaticClass::$out['fields']['user_fields'] as $field_key => $field_value ) {
				    $field_name = isset( $field_value['plugin_type_prefix'] ) ? $field_value['plugin_type_prefix'] . $field_key : $field_key;
				    if ( isset( $_POST[$field_name] ) ) {
					    unset( $_POST[$field_name] ); // = array();
				    }
			    }
		    }

		    if ( isset( $_POST['_featured_image'] ) ) {
			    unset( $_POST['_featured_image'] );
		    }
		    if ( isset( $_POST['attachid__featured_image'] ) ) {
			    unset( $_POST['attachid__featured_image'] );
		    }
		    foreach ( CRED_StaticClass::$out['fields']['taxonomies'] as $field_key => $field_value ) {
			    if ( isset( $_POST[$field_key] ) ) {
				    unset( $_POST[$field_key] ); // = array();
			    }
		    }

		    /**
		     * According to $_reset_file_values we need to force reseting taxonomy/taxonomyhierarchical
		     */
		    add_filter('toolset_filter_taxonomyhierarchical_terms', array('CRED_StaticClass', 'cred_empty_array'), 10, 0);
		    add_filter('toolset_filter_taxonomy_terms', array('CRED_StaticClass', 'cred_empty_array'), 10, 0);
	    }
    }

	/**
	 * Add field messages from $_FILES
	 *
	 * @param $zebraForm
	 * @param $formHelper
	 */
    private function add_field_messages_by_files($zebraForm, $formHelper) {
	    if ( isset( $_FILES ) && count( $_FILES ) > 0 ) {
		    // TODO check if this wp_list_pluck works with repetitive files... maybe in_array( array(1), $errors_on_files ) does the trick...
		    $errors_on_files = $food_names = wp_list_pluck( $_FILES, 'error' );
		    if ( in_array( 1, $errors_on_files ) || in_array( 2, $errors_on_files ) ) {
			    $zebraForm->add_field_message( $formHelper->getLocalisedMessage( 'no_data_submitted' ) );
		    } else {
			    $zebraForm->add_field_message( $formHelper->getLocalisedMessage( 'post_not_saved' ) );
		    }
	    } else {
		    // else just show the form again
		    $zebraForm->add_field_message( $formHelper->getLocalisedMessage( 'post_not_saved' ) );
	    }
    }

	/**
	 * Set field messages on submitted form
	 *
	 * @param $form_id
	 * @param $zebraForm
	 * @param $formHelper
	 */
    private function set_submitted_form_messages($form_id, $form_name, &$num_errors, $zebraForm, $formHelper) {
	    $top_messages = isset( $zebraForm->top_messages[$form_name] ) ? $zebraForm->top_messages[$form_name] : array();
	    $num_errors = count( $top_messages );
	    if ( empty( $_POST ) ) {
		    $num_errors++;
		    $not_saved_message = $formHelper->getLocalisedMessage( 'no_data_submitted' );
	    } else {
		    if ( count( $top_messages ) == 1 ) {
			    $temporary_messages = str_replace( "<br />%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage( 'post_not_saved_singular' ) );
			    $not_saved_message = $temporary_messages . "<br />%PROBLEMS_UL_LIST";
		    } else {
			    $temporary_messages = str_replace( "<br />%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage( 'post_not_saved_plural' ) );
			    $not_saved_message = $temporary_messages . "<br />%PROBLEMS_UL_LIST";
		    }

		    $error_list = '<ul>';
		    foreach ( $top_messages as $id_field => $text ) {
			    $error_list .= '<li>' . $text . '</li>';
		    }
		    $error_list .= '</ul>';
		    $not_saved_message = str_replace( array('%PROBLEMS_UL_LIST', '%NN'), array($error_list, count( $top_messages )), $not_saved_message );
	    }
	    $not_saved_message = apply_filters( 'cred_data_not_saved_message_' . $form_id, $not_saved_message, $form_id, $this->_post_id, $this->_preview );
	    $not_saved_message = apply_filters( 'cred_data_not_saved_message', $not_saved_message, $form_id, $this->_post_id, $this->_preview );

	    $zebraForm->add_field_message( $not_saved_message );
    }

	/**
	 * @param $post_id
	 * @param $form_id
	 * @param $form
	 * @param $fields
	 * @param $thisform
	 * @param $formHelper
	 * @param $is_ajax
	 * @param $attachedData
	 *
	 * @return mixed
	 */
	abstract function check_redirection($post_id, $form_id, $form, $fields, $thisform, $formHelper, $is_ajax, $attachedData);

    /**
     * @global int $post
     * @global WP_User $authordata
     */
    public function set_authordata() {
        global $post, $authordata;
        if ( is_int( $this->_post_id ) && $this->_post_id > 0 ) {
            if ( !isset( $post->ID ) || (isset( $post->ID ) && $post->ID != $this->_post_id) ) {
                $post = get_post( $this->_post_id );
                // As we modify the global $post, we need to also set the global $authordata and set the Toolset post relationships
                // This will bring compatibility with third party plugins and with shortcodes getting related posts data
                $authordata = new WP_User( $post->post_author );
                do_action( 'toolset_action_record_post_relationship_belongs', $post );
            }
        }
    }

    /**
     * build_form
     */
    public function build_form() {
    }

	/**
	 * @param string $messages
	 * @param string $js_messages
	 *
	 * @return mixed
	 */
    public function render_form($messages = "", $js_messages = "") {
        $shortcodeParser = $this->_shortcodeParser;
        $zebraForm = $this->_zebraForm;

        $shortcodeParser->remove_all_shortcodes();

        $zebraForm->render();
        // post content area might contain shortcodes, so return them raw by replacing with a dummy placeholder
        //By Gen, we use placeholder <!CRED_ERROR_MESSAGE!> in content for errors

        $this->_content = str_replace( CRED_StaticClass::FORM_TAG . '_' . $zebraForm->form_properties['name'] . '%', $zebraForm->_form_content, $this->_content ) . $js_messages;
        $this->_content = str_replace( '<!CRED_ERROR_MESSAGE!>', $messages, $this->_content );
        // parse old shortcode first (with dashes)
        $shortcodeParser->add_shortcode( 'cred-post-parent', array(&$this, 'cred_parent') );
        $this->_content = $shortcodeParser->do_shortcode( $this->_content );
        $shortcodeParser->remove_shortcode( 'cred-post-parent', array(&$this, 'cred_parent') );
        // parse new shortcode (with underscores)
        $shortcodeParser->add_shortcode( 'cred_post_parent', array(&$this, 'cred_parent') );
        $this->_content = $shortcodeParser->do_shortcode( $this->_content );
        $shortcodeParser->remove_shortcode( 'cred_post_parent', array(&$this, 'cred_parent') );

        return $this->_content;
    }

    /**
     * @param string $_form_type
     * @return boolean
     */
    public function create_new_post($_form_type, $form_type, $post_id, $post_type) {
        return $post_id;
    }

    public function save_form($post_id = null, $post_type = "") {
        return $post_id;
    }

	/**
	 * getFieldSettings important function that fill $out with all post fields in order to render forms
	 *
	 * @staticvar type $fields
	 * @staticvar type $_post_type
	 *
	 * @param $post_type
	 *
	 * @return null
	 */
    public function getFieldSettings($post_type) {
        static $fields = null;
        static $_post_type = null;
        if ( null === $fields || $_post_type != $post_type ) {
            $_post_type = $post_type;
            if ( $post_type == 'user' ) {
                $ffm = CRED_Loader::get( 'MODEL/UserFields' );
                $fields = $ffm->getFields( false, '', '', true, array($this, 'getLocalisedMessage') );
            } else {
                $ffm = CRED_Loader::get( 'MODEL/Fields' );
                $fields = $ffm->getFields( $post_type, true, array($this, 'getLocalisedMessage') );
            }

            // in CRED 1.1 post_fields and custom_fields are different keys, merge them together to keep consistency

            if ( array_key_exists( 'post_fields', $fields ) ) {
                $fields['_post_fields'] = $fields['post_fields'];
            }
            if (
                    array_key_exists( 'custom_fields', $fields ) && is_array( $fields['custom_fields'] )
            ) {
                if ( isset( $fields['post_fields'] ) && is_array( $fields['post_fields'] ) ) {
                    $fields['post_fields'] = array_merge( $fields['post_fields'], $fields['custom_fields'] );
                } else {
                    $fields['post_fields'] = $fields['custom_fields'];
                }
            }
        }
        return $fields;
    }

	/**
	 * @param $id
	 * @param $count
	 *
	 * @return string
	 */
    public function createFormID($id, $count) {
	    return str_replace( '-', '_', CRED_StaticClass::$_current_prefix ) . $this->createPrgID( $id, $count );
    }

	/**
	 * @param $id
	 * @param $count
	 *
	 * @return string
	 */
    public function createPrgID($id, $count) {
        return $id . '_' . $count;
    }

	/**
	 * @param array $replace_get
	 * @param array $remove_get
	 *
	 * @return array|mixed|string
	 */
    public function currentURI($replace_get = array(), $remove_get = array()) {
        $request_uri = $_SERVER["REQUEST_URI"];
        if ( !empty( $replace_get ) ) {
            $request_uri = explode( '?', $request_uri, 2 );
            $request_uri = $request_uri[0];

            parse_str( $_SERVER['QUERY_STRING'], $get_params );
            if ( empty( $get_params ) )
                $get_params = array();

            foreach ( $replace_get as $key => $value ) {
                $get_params[$key] = $value;
            }
            if ( !empty( $remove_get ) ) {
                foreach ( $get_params as $key => $value ) {
                    if ( isset( $remove_get[$key] ) )
                        unset( $get_params[$key] );
                }
            }
            if ( !empty( $get_params ) )
                $request_uri.='?' . http_build_query( $get_params, '', '&' );
        }
        return $request_uri;
    }

	/**
	 * @param $error_files
	 *
	 * @return bool
	 */
    public function validate_form($error_files) {
        $form_validator = new CRED_Validator_Form( $this, $error_files );
        return $form_validator->validate();
    }

	/**
	 * @param $post_id
	 * @param null $attachedData
	 */
    public function notify($post_id, $attachedData = null) {
        $form = &$this->_formData;
        $_fields = $form->getFields();

        // init notification manager if needed
        if (
                isset( $_fields['notification']->enable ) &&
                $_fields['notification']->enable &&
                !empty( $_fields['notification']->notifications )
        ) {
            // add extra plceholder codes
            add_filter( 'cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 5, 3 );
            add_filter( 'cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 5, 3 );

            CRED_Loader::load( 'CLASS/Notification_Manager' );
            if ( $form->getForm()->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME )
                CRED_Notification_Manager::set_user_fields();
            // add the post to notification management
            CRED_Notification_Manager::add( $post_id, $form->getForm()->ID, $_fields['notification']->notifications );
            // send any notifications now if needed
            CRED_Notification_Manager::triggerNotifications( $post_id, array(
                'event' => 'form_submit',
                'form_id' => $form->getForm()->ID,
                'notification' => $_fields['notification']
                    ), $attachedData );

            // remove extra plceholder codes
            remove_filter( 'cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 5, 3 );
            remove_filter( 'cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 5, 3 );
        }
    }

	/**
	 * @param $lang
	 *
	 * @return null|string
	 */
    public function wpml_save_post_lang($lang) {
        global $sitepress;
        if ( isset( $sitepress ) ) {
            if ( empty( $_POST['icl_post_language'] ) ) {
                if ( isset( $_GET['lang'] ) ) {
                    $lang = $_GET['lang'];
                } else {
                    $lang = $sitepress->get_current_language();
                }
            }
        }
        return $lang;
    }

	/**
	 * @param $clauses
	 *
	 * @return mixed
	 */
    public function terms_clauses($clauses) {
        global $sitepress;
        if ( isset( $sitepress ) ) {
            if ( isset( $_GET['source_lang'] ) ) {
                $src_lang = $_GET['source_lang'];
            } else {
                $src_lang = $sitepress->get_current_language();
            }
            if ( isset( $_GET['lang'] ) ) {
                $lang = sanitize_text_field( $_GET['lang'] );
            } else {
                $lang = $src_lang;
            }
            $clauses['where'] = str_replace( "icl_t.language_code = '" . $src_lang . "'", "icl_t.language_code = '" . $lang . "'", $clauses['where'] );
        }
        return $clauses;
    }

    /**
     * @return bool
     */
    public function isSubmitted() {
        return $this->_zebraForm->isSubmitted();
    }

    /**
     * Fix date and time using adodb date
     */
    private function adodb_date_fix_date_and_time() {
        if ( isset( $_POST ) && !empty( $_POST ) ) {
	        foreach ( $_POST as $name => &$value ) {
		        if ( $name == CRED_StaticClass::NONCE ) {
			        continue;
		        }
		        if ( is_array( $value ) && isset( $value['datepicker'] ) ) {
			        if ( ! function_exists( 'adodb_date' ) ) {
				        require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';
			        }
			        $date_format = get_option( 'date_format' );
			        $date = $value['datepicker'];
			        $value['datetime'] = adodb_date( "Y-m-d", $date );
			        $value['hour'] = isset( $value['hour'] ) ? $value['hour'] : "00";
			        $value['minute'] = isset( $value['minute'] ) ? $value['minute'] : "00";
			        $value['timestamp'] = strtotime( $value['datetime'] . " " . $value['hour'] . ":" . $value['minute'] . ":00" );
		        }
	        }
        }
    }

	/**
	 * @param $codes
	 * @param $form_id
	 * @param $post_id
	 *
	 * @return mixed
	 */
    public function extraSubjectNotificationCodes($codes, $form_id, $post_id) {
        $form = $this->_formData;
        if ( $form_id == $form->getForm()->ID ) {
            //$codes['%%POST_PARENT_TITLE%%'] = $this->cred_parent(array('get' => 'title'));
            $codes['%%POST_PARENT_TITLE%%'] = $this->cred_parent_for_notification( $post_id, array('get' => 'title') );
        }
        return $codes;
    }

	/**
	 * @param $codes
	 * @param $form_id
	 * @param $post_id
	 *
	 * @return mixed
	 */
    public function extraBodyNotificationCodes($codes, $form_id, $post_id) {
        $form = $this->_formData;
        if ( $form_id == $form->getForm()->ID ) {
            $codes['%%FORM_DATA%%'] = isset( CRED_StaticClass::$out['notification_data'] ) ? CRED_StaticClass::$out['notification_data'] : '';
            //$codes['%%POST_PARENT_TITLE%%'] = $this->cred_parent(array('get' => 'title'));
            //$codes['%%POST_PARENT_LINK%%'] = $this->cred_parent(array('get' => 'url'));
            $codes['%%POST_PARENT_TITLE%%'] = $this->cred_parent_for_notification( $post_id, array('get' => 'title') );
            $codes['%%POST_PARENT_LINK%%'] = $this->cred_parent_for_notification( $post_id, array('get' => 'url') );
        }
        return $codes;
    }

	/**
	 * @param $post_id
	 * @param $atts
	 *
	 * @return false|null|string
	 */
    public function cred_parent_for_notification($post_id, $atts) {
        extract( shortcode_atts( array(
            'post_type' => null,
            'get' => 'title'
                        ), $atts ) );

        $post_type = get_post_type( $post_id );
        $parent_id = null;
        foreach ( CRED_StaticClass::$out['fields']['parents'] as $k => $v ) {
            if ( isset( $_REQUEST[$k] ) ) {
                $parent_id = $_REQUEST[$k];
                break;
            }
        }

        if ( $parent_id !== null ) {
            switch ($get) {
                case 'title':
                    return get_the_title( $parent_id );
                case 'url':
                    return get_permalink( $parent_id );
                case 'id':
                    return $parent_id;
                default:
                    return '';
            }
        }
        return '';
    }

    /**
     * CRED-Shortcode: cred_parent
     *
     * Description: Render data relating to pre-selected parent of the post the form will manipulate
     *
     * Parameters:
     * 'post_type' => [optional] Define a specifc parent type
     * 'get' => Which information to render (title, url)
     *
     * Example usage:
     *
     *
     * [cred_parent get="url"]
     *
     * Link:
     *
     *
     * Note:
     *  'post_type'> necessary if there are multiple parent types
     *
     * */
    public function cred_parent($atts) {
        extract( shortcode_atts( array(
            'post_type' => null,
            'get' => 'title'
                        ), $atts ) );

        $parent_id = null;
        if ( $post_type ) {
            if ( isset( CRED_StaticClass::$out['fields']['parents']['_wpcf_belongs_' . $post_type . '_id'] ) && isset( $_GET['parent_' . $post_type . '_id'] ) ) {
                $parent_id = intval( $_GET['parent_' . $post_type . '_id'] );
            }
        } else {
            if ( isset( CRED_StaticClass::$out['fields']['parents'] ) ) {
                foreach ( CRED_StaticClass::$out['fields']['parents'] as $key => $parentdata ) {
                    if ( isset( $_GET['parent_' . $parentdata['data']['post_type'] . '_id'] ) ) {
                        $parent_id = intval( $_GET['parent_' . $parentdata['data']['post_type'] . '_id'] );
                        break;
                    } else {
                        global $post;
                        if ( isset( $post ) && !empty( $post ) ) {
                            $parent_id = get_post_meta( $post->ID, $key, true );
                            break;
                        } else {
                            if ( isset( $_GET['_id'] ) ) {
                                $parent_id = get_post_meta( intval( $_GET['_id'] ), $key, true );
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ( $parent_id !== null ) {
            switch ($get) {
                case 'title':
                    return get_the_title( $parent_id );
                case 'url':
                    return get_permalink( $parent_id );
                case 'id':
                    return $parent_id;
                default:
                    return '';
            }
        } else {
            switch ($get) {
                case 'title':
                    return _( 'Previous Page' );
                case 'url':
                    $back = $_SERVER['HTTP_REFERER'];
                    return (isset( $back ) && !empty( $back )) ? $back : '';
                case 'id':
                    return '';
                default:
                    return '';
            }
        }
        return '';
    }

}
