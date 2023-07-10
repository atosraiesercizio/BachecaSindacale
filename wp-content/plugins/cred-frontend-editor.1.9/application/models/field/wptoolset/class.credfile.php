<?php

require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.textfield.php';

class WPToolset_Field_Credfile extends WPToolset_Field_Textfield {

    public $disable_progress_bar;

    public static function get_image_sizes($size = '') {

        global $_wp_additional_image_sizes;

        $sizes = array();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        // Create the full array with sizes and crop info
        foreach ( $get_intermediate_image_sizes as $_size ) {
            if ( in_array( $_size, array('thumbnail', 'medium', 'large') ) ) {
                $sizes[$_size]['width'] = get_option( $_size . '_size_w' );
                $sizes[$_size]['height'] = get_option( $_size . '_size_h' );
                $sizes[$_size]['crop'] = (bool) get_option( $_size . '_crop' );
            } elseif ( isset( $_wp_additional_image_sizes[$_size] ) ) {
                $sizes[$_size] = array(
                    'width' => $_wp_additional_image_sizes[$_size]['width'],
                    'height' => $_wp_additional_image_sizes[$_size]['height'],
                    'crop' => $_wp_additional_image_sizes[$_size]['crop']
                );
            }
        }

        // Get only 1 size if found
        if ( $size ) {
            if ( isset( $sizes[$size] ) ) {
                return $sizes[$size];
            } else {
                return false;
            }
        }

        return $sizes;
    }

    /**
     * Determine if the file upload progress bar should be displayed on the front-end.
     *
     * @return bool
     * @since 1.9
     */
    private function is_progress_bar_disabled() {

        /**
         * cred_file_upload_disable_progress_bar
         *
         * Allows for overriding the decision whether the file upload progress bar should be displayed
         *
         * @param bool $disable True to disable, false to enable.
         * @since unknown
         */
        $is_disabled = (bool) apply_filters(
                        'cred_file_upload_disable_progress_bar', version_compare( CRED_FE_VERSION, '1.3.6.2', '<=' )
        );

        return $is_disabled;
    }

    public function init() {

        $this->disable_progress_bar = $this->is_progress_bar_disabled();

        $asset_manager = CRED_Asset_Manager::get_instance();
        $asset_manager->enqueue_file_upload_assets( $this->disable_progress_bar );

        wp_localize_script(
                CRED_Asset_Manager::AJAX_FILE_UPLOADER, 'settings', array(
            'media_settings' => self::get_image_sizes( 'thumbnail' ),
            'ajaxurl' => sprintf( '%s/application/submit.php', untrailingslashit( CRED_ABSURL ) ),
            'delete_confirm_text' => __( 'Are you sure to delete this file ?', 'wp-cred' ),
            'delete_alert_text' => __( 'Generic Error in deleting file', 'wp-cred' ),
            'delete_text' => __( 'delete', 'wp-cred' ),
            'too_big_file_alert_text' => __( 'File is too big', 'wp-cred' ),
            'nonce' => wp_create_nonce( 'ajax_nonce' )
                )
        );
    }

    public static function registerScripts() {
        
    }

    public static function registerStyles() {
        
    }

    public function enqueueScripts() {
        
    }

    public function enqueueStyles() {
        
    }

    public function metaform() {
        $value = $this->getValue();
        $name = $this->getName();

        if ( isset( $this->_data['title'] ) ) {
            $title = $this->_data['title'];
        } else {
            $title = $name;
        }

        $id = $this->_data['id'];
        $unique_id = str_replace( array('[', ']'), '', $this->_data['name'] );

        $preview_span_input_showhide = '';
        $button_extra_classnames = '';

        $has_image = false;
        $is_empty = false;

        if ( empty( $value ) ) {
            $value = ''; // NOTE we need to set it to an empty string because sometimes it is NULL on repeating fields
            $is_empty = true;
            $preview_span_input_showhide = ' style="display:none"';
        }

        if ( !$is_empty ) {
            $pathinfo = pathinfo( $value );
            // TODO we should check against the allowed mime types, not file extensions
            if ( ($this->_data['type'] == 'credimage' || $this->_data['type'] == 'credfile') &&
                    isset( $pathinfo['extension'] ) && in_array( strtolower( $pathinfo['extension'] ), array('png', 'gif', 'jpg', 'jpeg', 'bmp', 'tif') ) ) {
                $has_image = true;
            }
        }

        $preview_file = ''; //WPTOOLSET_FORMS_RELPATH . '/images/icon-attachment32.png';
        $attr_hidden = array(
            'id' => $unique_id . "_hidden",
            'class' => 'js-wpv-credfile-hidden',
            'data-wpt-type' => 'file'
        );

        $attributes = $this->getAttr();
	    $preview_images = isset($attributes['preview_thumbnail_url']) ? $attributes['preview_thumbnail_url'] : "";

        $output = (isset( $attributes['output'] )) ? $attributes['output'] : "";        
        $shortcode_class = array_key_exists( 'class', $attributes ) ? $attributes['class'] : "";

        $attr_file = array(
            'id' => $unique_id . "_file",
            'class' => "js-wpt-credfile-upload-file wpt-credfile-upload-file {$shortcode_class}",
            'alt' => $value,
            'res' => $value
        );

	    if ( ! $is_empty ) {
		    $image_hash = md5( $value );
		    $preview_file = isset( $preview_images[ $image_hash ] ) ? $preview_images[ $image_hash ] : $value;
		    $attr_file['style'] = 'display:none';
	    } else {
		    $attr_hidden['disabled'] = 'disabled';
	    }

        $form = array();

        $form[] = array(
            '#type' => 'markup',
            '#markup' => '<input type="button" style="display:none" data-action="undo" class="js-wpt-credfile-undo wpt-credfile-undo' . $button_extra_classnames . '" value="' . esc_attr( __( 'Restore Previous Value', 'wp-cred' ) ) . '" />',
        );

        //Attachment id for _featured_image if exists
        //if it does not exists file_upload.js will handle it after file is uploaded
        if ( $name == '_featured_image' ) {
            global $post;
            if ( is_object( $post ) && property_exists( $post, 'ID' ) ) {
                $post_id = $post->ID;
                $post_thumbnail_id = get_post_thumbnail_id( $post_id );
                if ( !empty( $post_thumbnail_id ) ) {
                    // here we can use $id because referred to _feature_image only and it is unique
                    $form[] = array(
                        '#type' => 'markup',
                        '#markup' => "<input id='attachid_" . $id . "' name='attachid_" . $name . "' type='hidden' value='" . esc_attr( $post_thumbnail_id ) . "'>"
                    );
                }
            }
        }

        $form[] = array(
            '#type' => 'hidden',
            '#name' => $name,
            '#value' => $value,
            '#attributes' => $attr_hidden,
        );
        $form[] = array(
            '#type' => 'file',
            '#name' => $name,
            '#value' => $value,
            '#title' => $title,
            '#before' => '',
            '#after' => '',
            '#attributes' => $attr_file,
            '#validate' => $this->getValidationData(),
            '#repetitive' => $this->isRepetitive(),
        );

        if ( !$this->disable_progress_bar ) {
            //Progress Bar
            $form[] = array(
                '#type' => 'markup',
                '#markup' => '<div id="progress_' . $unique_id . '" class="meter" style="display:none;"><span class = "progress-bar" style="width:0;"></span></div>',
            );
        }

        if ( $output == 'bootstrap' ) {
            $delete_span_button = '<span role="button" data-action="delete" class="dashicons-before dashicons-no js-wpt-credfile-delete wpt-credfile-delete" title="' . esc_attr( __( 'delete', 'wp-cred' ) ) . '"></span>';
        } else {
            $delete_span_button = '<input type="button" data-action="delete" class="js-wpt-credfile-delete wpt-credfile-delete' . $button_extra_classnames . '" value="' . esc_attr( __( 'delete', 'wp-cred' ) ) . '" style="width:100%;margin-top:2px;margin-bottom:2px;" />';
        }

        $span_container = '<span class="js-wpt-credfile-preview wpt-credfile-preview" ' . $preview_span_input_showhide . '>%s %s</span>';
        if ( $has_image ) {
            $preview_image = '<img id="' . $unique_id . '_image" src="' . $preview_file . '" title="' . $preview_file . '" alt="' . $preview_file . '" data-pin-nopin="true"/>';
            $form[] = array(
                '#type' => 'markup',
                '#markup' => sprintf( $span_container, $preview_image, $delete_span_button ),
            );
        } else {
            $form[] = array(
                '#type' => 'markup',
                '#markup' => sprintf( $span_container, $preview_file, $delete_span_button ),
            );
        }
        return $form;
    }

}
