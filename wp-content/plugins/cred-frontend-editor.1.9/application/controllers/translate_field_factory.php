<?php

/**
 * Class that translates field during cred shortcodes elaboration.
 *
 * @since unknown
 */
class CRED_Translate_Field_Factory {

	public $_formBuilder;
	public $_formHelper;

	public function __construct( $formBuilder, $formHelper ) {
		$this->_formBuilder = $formBuilder;
		$this->_formHelper = $formHelper;
	}

	/**
	 * cred_translate_option
	 *
	 * @param type $option
	 * @param type $key
	 * @param type $form
	 * @param type $field
	 *
	 * @return type
	 */
	private function _cred_translate_option( $option, $key, $form, $field ) {
		if ( ! isset( $option['title'] ) ) {
			return $option;
		}
		$original = $option['title'];
		$option['title'] = cred_translate(
			$field['slug'] . " " . $option['title'], $option['title'], 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID
		);
		if ( $original == $option['title'] ) {
			// Try translating with types context
			$option['title'] = cred_translate(
				'field ' . $field['id'] . ' option ' . $key . ' title', $option['title'], 'plugin Types' );
		}

		return $option;
	}

	/**
	 * cred_translate_form
	 *
	 * @staticvar array $_count_
	 *
	 * @param type $name
	 * @param type $field
	 *
	 * @return type
	 */
	public function cred_translate_form_name( $name, &$field ) {
		// allow multiple submit buttons
		static $_count_ = array(
			'submit' => 0,
		);

		$count = ( $field['type'] == 'form_submit' ) ? '_' . ( $_count_['submit']++ ) : "";
		$f = "";

		if ( $field['type'] == 'taxonomy_hierarchical' || $field['type'] == 'taxonomy_plain' ) {
			$f = "_" . $field['name'];
		} else {
			if ( isset( $field['master_taxonomy'] ) && isset( $field['type'] ) ) {
				$f = "_" . $field['master_taxonomy'] . "_" . $field['type'];
			} else {
				if ( isset( $field['id'] ) ) {
					$f = "_" . $field['id'];
				} else {

				}
			}
		}

		return array( "cred_form_" . CRED_StaticClass::$out['prg_id'] . $f . $count );
	}

	/**
	 * get_field_object
	 *
	 * @global type $post
	 * @global type $post
	 * @staticvar array $_count_
	 * @staticvar boolean $wpExtensions
	 *
	 * @param string $name
	 * @param type $field
	 * @param type $additional_options
	 *
	 * @return type
	 */
	public function cred_translate_field( $name, &$field, $additional_options = array() ) {
		static $_count_ = array(
			'submit' => 0,
		);

		static $wpExtensions = false;
		// get refs here
		$globals = CRED_StaticClass::$_staticGlobal;
		if ( false === $wpExtensions ) {
			$wpMimes = $globals['MIMES'];
			$wpExtensions = implode( ',', array_keys( $wpMimes ) );
		}

		// get refs here
		$form = $this->_formBuilder->_formData;
		$postData = $this->_formBuilder->_postData;
		$zebraForm = $this->_formBuilder->_zebraForm;

		// extend additional_options with defaults
		extract( array_merge(
			array(
				'preset_value' => null,
				'placeholder' => null,
				'value_escape' => false,
				'make_readonly' => false,
				'is_tax' => false,
				'max_width' => null,
				'max_height' => null,
				'single_select' => false,
				'generic_type' => null,
				'urlparam' => '',
			), $additional_options
		) );

		$type = 'text';

		$attributes = array();


		if ( isset( $class ) ) {
			$attributes['class'] = $class;
		}

		$types_default_value = "";
		$value = '';
		$field_name = $name;

		$field["name"] = cred_translate( $field["name"], $field["name"], $form->getForm()->post_type . "-" . $form->getForm()->post_title . "-" . $form->getForm()->ID );

		if ( isset( $field['data']['user_default_value'] ) && ! empty( $field['data']['user_default_value'] ) ) {
			$types_default_value = $field['data']['user_default_value'];
		}

		// if not taxonomy field
		if ( ! $is_tax ) {

			if ( isset( $placeholder ) && ! empty( $placeholder ) && is_string( $placeholder ) ) {
				// use translated value by WPML if exists
				$placeholder = cred_translate(
					'Value: ' . $placeholder, $placeholder, 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID
				);
				$additional_options['placeholder'] = $placeholder;
			}

			$can_accept_post_data = ! ( CRED_StaticClass::$_reset_file_values && CRED_Form_Base::$_self_updated_form );

			//Urlparam shortcode attribute
			if ( is_string( $urlparam ) && ! empty( $urlparam ) && isset( $_GET[ $urlparam ] ) ) {
				// use translated value by WPML if exists
				$field_configuration = urldecode( $_GET[ $urlparam ] );

				//Value shortcode attribute
			} elseif ( isset( $preset_value ) && ! empty( $preset_value ) > 0 ) {
				// use translated value by WPML if exists, only for strings
				// For numeric values, just pass it
				if (
					! empty( $preset_value ) && is_string( $preset_value )
				) {
					$field_configuration = cred_translate(
						'Value: ' . $preset_value, $preset_value, 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID
					);

					$additional_options['preset_value'] = $placeholder;
				} else {
					if ( is_numeric( $preset_value ) ) {
						$field_configuration = $preset_value;
					}
				}

				//DB post data
				//This function is called every time we need to render a form field
				//because the flow create/edit post form is the same we need to remind
				//some cases we cannot get postData values like AJAX create form after valid submition
			} elseif ( $can_accept_post_data && ! isset( $_POST[ $field_name ] ) && $postData && isset( $postData->fields[ $field_name ] ) ) {
				if ( is_array( $postData->fields[ $field_name ] ) && count( $postData->fields[ $field_name ] ) > 1 ) {
					if ( isset( $field['data']['repetitive'] ) &&
						$field['data']['repetitive'] == 1
					) {
						$field_configuration = $postData->fields[ $field_name ];
					}
				} else {
					$field_configuration = $postData->fields[ $field_name ][0];
					//checkboxes needs to be different from from db
					if ( $field['type'] == 'checkboxes' ) {
						if ( isset( $postData->fields[ $field_name ] ) &&
							isset( $postData->fields[ $field_name ][0] ) && is_array( $postData->fields[ $field_name ][0] )
						) {
							$save_empty = ( isset( $field['data']['save_empty'] ) && $field['data']['save_empty'] == 'yes' );
							$field_configuration = array();
							foreach ( $postData->fields[ $field_name ][0] as $key => $value ) {
								if ( $save_empty && $value == 0 ) {
									continue;
								}
								$field_configuration[] = $key;
							}
						}
					}
				}

				//$_POST data
			} elseif ( $_POST && isset( $_POST ) && isset( $_POST[ $field_name ] ) ) {
				$field_configuration = stripslashes_deep( $_POST[ $field_name ] );

				//Types default value
			} elseif ( ! empty( $types_default_value ) ) {
				$field_configuration = $types_default_value;
			} else {
				if ( ! isset( $preset_value ) ) {
					$field_configuration = null;
				}
			}

			// save a map between options / actual values for these types to be used later
			if ( in_array( $field['type'], array( 'checkboxes', 'radio', 'select', 'multiselect' ) ) ) {
				$tmp = array();
				foreach ( $field['data']['options'] as $optionKey => $optionData ) {
					if ( $optionKey !== 'default' && is_array( $optionData ) ) {
						$tmp[ $optionKey ] = ( 'checkboxes' == $field['type'] ) ? @$optionData['set_value'] : $optionData['value'];
					}
				}
				CRED_StaticClass::$out['field_values_map'][ $field['slug'] ] = $tmp;
				unset( $tmp );
				unset( $optionKey );
				unset( $optionData );
			}

			if ( isset( $field_configuration ) ) {
				$value = $field_configuration;
			}

			switch ( $field['type'] ) {
				case 'form_messages' :
					$type = 'messages';
					break;

				case 'form_submit':
					$type = 'submit';

					if ( isset( $preset_value ) &&
						! empty( $preset_value ) &&
						is_string( $preset_value )
					) {
						// use translated value by WPML if exists
						$field_configuration = cred_translate(
							'Value: ' . $preset_value, $preset_value, 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID
						);
						$value = $field_configuration;

						$additional_options['preset_value'] = $placeholder;
					}

					// allow multiple submit buttons
					$name .= '_' . ++$_count_['submit'];
					break;

				case 'recaptcha':
					$type = 'recaptcha';
					$value = '';
					$attributes = array(
						'error_message' => $this->_formHelper->getLocalisedMessage( 'enter_valid_captcha' ),
						'show_link' => $this->_formHelper->getLocalisedMessage( 'show_captcha' ),
						'no_keys' => __( 'Enter your ReCaptcha keys at the CRED Settings page in order for ReCaptcha API to work', 'wp-cred' ),
					);
					if ( false !== $globals['RECAPTCHA'] ) {
						$attributes['public_key'] = $globals['RECAPTCHA']['public_key'];
						$attributes['private_key'] = $globals['RECAPTCHA']['private_key'];
					}
					if ( 1 == CRED_StaticClass::$out['count'] ) {
						$attributes['open'] = true;
					}

					// used to load additional js script
					CRED_StaticClass::$out['has_recaptcha'] = true;
					break;

				case 'audio':
				case 'video':
				case 'file':
					$type = 'cred' . $field['type'];

					global $post;
					if ( isset( $post ) ) {
						$attachments = get_children(
							array(
								'post_parent' => $post->ID,
								//'post_mime_type' => 'image',
								'post_type' => 'attachment',
							)
						);
					}
					if ( isset( $attachments ) ) {
						foreach ( $attachments as $attachment_post_id => $attachment ) {
							$file_url = $attachment->guid;
							if ( is_array( $value ) ) {
								foreach ( $value as $n => &$single_value ) {
									if ( ( isset( $single_value ) && ! empty( $single_value ) ) && basename( $file_url ) == basename( $single_value ) ) {
										$single_value = $file_url;
										break;
									}
								}
							} else {
								if ( ( isset( $value ) && ! empty( $value ) ) && basename( $file_url ) == basename( $value ) ) {
									$value = $file_url;
								}
							}
						}
					}
					break;

				case 'image':
					$type = 'cred' . $field['type'];

					// show previous post featured image thumbnail
					if (
						$can_accept_post_data &&
						! isset( $_POST['_featured_image'] ) &&
						'_featured_image' == $name
					) {
						$value = '';
						if ( isset( $postData->extra['featured_img_html'] ) ) {
							$attributes['display_featured_html'] = $value = $postData->extra['featured_img_html'];
						}
					}

					global $post;
					if ( isset( $post ) ) {
						$attachments = get_children(
							array(
								'post_parent' => $post->ID,
								//'post_mime_type' => 'image',
								'post_type' => 'attachment',
							)
						);
					}

					if ( isset( $attachments ) ) {
						$attributes['preview_thumbnail_url'] = array();
						foreach ( $attachments as $attachment_post_id => $attachment ) {
							//guid will help use to mantain the correct order when are repetitive images
							$full_image_url = $attachment->guid;
							$url_image_preview_thumbnail_array = wp_get_attachment_image_src( $attachment->ID );
							$url_image_preview_thumbnail = isset( $url_image_preview_thumbnail_array[0] ) ? $url_image_preview_thumbnail_array[0] : $full_image_url;
							if ( is_array( $value ) ) {
								foreach ( $value as $n => &$single_value ) {
									if (
										isset( $single_value ) &&
										! empty( $single_value ) &&
										basename( $full_image_url ) == basename( $single_value )
									) {
										$single_value = $full_image_url;
										$hash_value = md5( $single_value );
										$attributes['preview_thumbnail_url'][ $hash_value ] = $url_image_preview_thumbnail;
										break;
									}
								}
							} else {
								if (
									isset( $value ) &&
									! empty( $value ) &&
									basename( $full_image_url ) === basename( $value )
								) {
									$value = $full_image_url;
									$hash_value = md5( $value );
									$attributes['preview_thumbnail_url'][ $hash_value ] = $url_image_preview_thumbnail;
								}
							}
						}
					}
					break;

				case 'date':
					$type = 'date';
					if ( ! function_exists( 'adodb_mktime' ) ) {
						require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';
					}
					$value = array();
					$format = get_option( 'date_format', '' );
					if ( empty( $format ) ) {
						$format = $zebraForm->getDateFormat();
						$format .= " h:i:s";
					}

					$attributes = array_merge( $additional_options, array(
						'format' => $format,
						'readonly_element' => false,
						'repetitive' => isset( $field['data']['repetitive'] ) ? $field['data']['repetitive'] : 0,

					) );

					if (
						isset( $field_configuration ) &&
						! empty( $field_configuration )
					) {
						if ( is_array( $field_configuration ) ) {
							foreach ( $field_configuration as $dv ) {
								if ( isset( $dv['datepicker'] ) ) {
									$value[] = array( 'timestamp' => $dv['datepicker'] );
								} else {
									$value[] = array( 'timestamp' => $dv );
								}
							}
						} else {
							$value['timestamp'] = $field_configuration;
						}
					}
					break;

				case 'select':
				case 'multiselect':
					$type = 'select';
					$value = array();
					$titles = array();
					$attributes = array();
					$default = array();

					if ( $field['type'] == 'multiselect' ) {
						$attributes['multiple'] = 'multiple';
					}

					$attributes['options'] = array();

					foreach ( $field['data']['options'] as $key => $option ) {
						$index = $key;
						if ( 'default' === $key && $option != 'no-default' ) {
							$default[] = $option;
						} else {
							if ( is_admin() ) {
								if ( isset( $option['title'] ) ) {
									cred_translate_register_string( 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID, $field['slug'] . " " . $option['title'], $option['title'], false );
								}
							}
							if ( isset( $option['title'] ) ) {
								$option = $this->_cred_translate_option( $option, $key, $form, $field );
								$attributes['options'][ $index ] = $option['title'];

								$is_field_configuration_set = isset( $field_configuration ) && ! empty( $field_configuration );
								$is_field_checked = ( $is_field_configuration_set && $field_configuration == $option['value'] );
								$is_a_field_option_checked = ( $is_field_configuration_set && ( is_array( $field_configuration ) && ( array_key_exists( $option['value'], $field_configuration ) || in_array( $option['value'], $field_configuration ) ) ) );

								if ( $is_field_checked || $is_a_field_option_checked ) {

									if ( 'select' == $field['type'] ) {
										$titles[] = $key;
										$value = $option['value'];
									} else {
										$value = $field_configuration;
									}
								}

								if ( isset( $option['dummy'] ) && $option['dummy'] ) {
									$attributes['dummy'] = $key;
								}
							}
						}
					}

					if ( $field['type'] == 'multiselect' ) {
						if ( empty( $value ) && ! empty( $default ) ) {
							$value = $default;
						}
					} else {
						if ( empty( $titles ) && ! empty( $default[0] ) ) {
							$titles = isset( $field['data']['options'][ $default[0] ]['value'] ) ? $field['data']['options'][ $default[0] ]['value'] : "";
						}
						$attributes['actual_value'] = isset( $field_configuration ) && ! empty( $field_configuration ) ? $field_configuration : $titles;
					}
					if ( isset( CRED_StaticClass::$out['field_values_map'][ $field['slug'] ] ) ) {
						$attributes['actual_options'] = CRED_StaticClass::$out['field_values_map'][ $field['slug'] ];
					}
					break;

				case 'radio':
					$type = 'radios';
					$value = array();
					$titles = array();
					$attributes = array();

					$default = isset( $field['data']['options']['default'] ) ? $field['data']['options']['default'] : "";
					if ( isset( $field['data']['options']['default'] ) ) {
						unset( $field['data']['options']['default'] );
					}

					$set_default = false;
					foreach ( $field['data']['options'] as $key => &$option ) {
						if ( isset( $option['value'] ) ) {
							$option['value'] = str_replace( "\\", "", $option['value'] );

						}

						if ( ! $set_default && $key == $default ) {
							$set_default = true;
							$default = $option['value'];
						}

						$index = $key;

						if ( is_admin() ) {
							//register strings on form save
							cred_translate_register_string( 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID, $field['slug'] . " " . $option['title'], $option['title'], false );
						}
						$option = $this->_cred_translate_option( $option, $key, $form, $field );

						$titles[ $index ] = $option['title'];

						if ( isset( $field_configuration ) && $field_configuration == $option['value'] ) {
							$attributes = isset( $option['value'] ) ? $option['value'] : $key;
							$value = isset( $option['value'] ) ? $option['value'] : $key;
						}
					}

					if ( ! isset( $field_configuration ) && ! empty( $default ) ) {
						$attributes = $default;
					}
					$def = $attributes;
					$attributes = array( 'default' => $def );
					$attributes['actual_titles'] = $titles;

					if ( isset( CRED_StaticClass::$out['field_values_map'][ $field['slug'] ] ) ) {
						$attributes['actual_values'] = CRED_StaticClass::$out['field_values_map'][ $field['slug'] ];
					}

					foreach ( $attributes['actual_values'] as $k => &$option ) {
						$option = str_replace( "\\", "", $option );
					}
					break;

				case 'checkboxes':

					if ( ! empty( $field_configuration ) && ! is_array( $field_configuration ) ) {
						$field_configuration = array( $field_configuration );
					}

					$type = 'checkboxes';
					$save_empty = isset( $field['data']['save_empty'] ) ? $field['data']['save_empty'] : false;
					$value = array();
					if ( isset( $field_configuration ) && ! empty( $field_configuration ) ) {
						if ( ! is_array( $field_configuration ) ) {
							if ( isset( $field['data']['options'] ) && ! empty( $field['data']['options'] ) ) {
								foreach ( $field['data']['options'] as $option_key => $option_value ) {
									if ( $option_value['set_value'] == $field_configuration ) {
										$field_configuration = array( $option_key => $field_configuration );
									}
								}
							}
						} else {
							if ( count( array_filter( array_keys( $field_configuration ), 'is_string' ) ) > 0 ) {
								$new_data_value = array();
								if ( isset( $field['data']['options'] ) && ! empty( $field['data']['options'] ) ) {
									foreach ( $field['data']['options'] as $option_key => $option_value ) {
										if ( in_array( $option_value['set_value'], $field_configuration ) ) {
											$new_data_value[ $option_key ] = $option_value['set_value'];
										}
									}
								}
								$field_configuration = $new_data_value;
								unset( $new_data_value );
							}
						}
						foreach ( $field_configuration as $config_key => $config_value ) {
							if ( $save_empty || $field['cred_generic'] == 1 ) {
								$value[ $config_key ] = $config_value;
							} else {
								$value[ $config_key ] = 1;
							}
						}
					}

					$titles = array();
					$attributes = array();

					if ( isset( $field_configuration ) && ! is_array( $field_configuration ) ) {
						$field_configuration = array( $field_configuration );
					}

					foreach ( $field['data']['options'] as $key => $option ) {
						if ( is_admin() ) {
							//register strings on form save
							cred_translate_register_string( 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID, $field['slug'] . " " . $option['title'], $option['title'], false );
						}
						$option = $this->_cred_translate_option( $option, $key, $form, $field );
						$index = $key;
						$titles[ $index ] = $option['title'];
						if ( empty( $value ) ) {
							if ( isset( $field_configuration ) && ! empty( $field_configuration ) && isset( $field_configuration[ $index ] ) ) {
								$value[ $index ] = $field_configuration[ $index ];
							} else {
								$value[ $index ] = 0;
							}
						}
						if ( isset( $option['checked'] ) && $option['checked'] && ! isset( $field_configuration ) ) {
							$attributes[] = $index;
						} elseif ( isset( $field_configuration ) && isset( $field_configuration[ $index ] ) /* && in_array($index,$field_configuration) */ ) {
							if (
							! ( isset( $field['data']['save_empty'] ) && 'yes' == $field['data']['save_empty'] && ( 0 === $field_configuration[ $index ] || '0' === $field_configuration[ $index ] ) )
							) {
								$attributes[] = $index;
							}
						}
					}
					$def = $attributes;

					$attributes = array( 'default' => $def );
					$attributes['actual_titles'] = $titles;
					if ( isset( CRED_StaticClass::$out['field_values_map'][ $field['slug'] ] ) ) {
						$attributes['actual_values'] = CRED_StaticClass::$out['field_values_map'][ $field['slug'] ];
					}
					break;

				case 'checkbox':
					$save_empty = isset( $field['data']['save_empty'] ) ? $field['data']['save_empty'] : false;
					//If save empty and $_POST is set but checkbox is not set data value 0

					if ( isset( $field_configuration ) &&
						$field_configuration == 1 &&
						$save_empty == 'no' &&
						isset( $_POST ) && ! empty( $_POST ) && ! isset( $_POST[ $field_name ] )
					) {
						$field_configuration = 0;
					}

					$type = 'checkbox';

					$value = $field['data']['set_value'];
					$attributes = array();

					if ( isset( $field_configuration ) && $field_configuration == $value ) {
						$attributes = array( 'checked' => 'checked' );
					}

					if ( is_admin() ) {
						//register strings on form save
						cred_translate_register_string( 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID, $field['slug'], $field['name'], false );
					}
					$field['name'] = cred_translate( $field['slug'], $field['name'], 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID );
					break;

				case 'textarea':
					$type = 'textarea';
					break;

				case 'wysiwyg':
					$type = 'wysiwyg';
					$attributes = array( 'disable_xss_filters' => true );
					if ( 'post_content' == $name &&
						isset( $form->fields['form_settings']->form['has_media_button'] ) &&
						$form->fields['form_settings']->form['has_media_button']
					) {
						$attributes['has_media_button'] = true;
					}
					break;

				case 'integer':
					$type = 'integer';
					break;

				case 'numeric':
					$type = 'numeric';
					break;

				case 'phone':
					$type = 'phone';
					break;

				case 'embed':
				case 'url':
					$type = 'url';
					break;

				case 'email':
					$type = 'email';
					break;

				case 'colorpicker':
					$type = 'colorpicker';
					break;

				case 'textfield':
					$type = 'textfield';
					break;

				case 'password':
					$type = 'password';
					break;

				case 'hidden':
					$type = 'hidden';
					break;
				case 'skype':
					$type = 'skype';
					//if for some reason i receive data_value as array but it is not repetitive i need to get as not array of array
					//if (isset($field['data']['repetitive']) && $field['data']['repetitive'] == 1)
					// Note that generic skype fields are not repetitive and $field_configuration is a string...
					if ( isset( $field['cred_generic'] ) && $field['cred_generic'] ) {
						$field_configuration = array(
							'skypename' => isset( $field_configuration ) ? $field_configuration : '',
							'style' => '',
						);

					} else {
						if ( isset( $field_configuration ) ) {
							if ( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] == 0 && isset( $field_configuration[0] ) ) {
								$field_configuration = $field_configuration[0];
							}

							if ( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] == 1 && ! isset( $field_configuration[0] ) ) {
								$field_configuration = array( $field_configuration );
							}
						}
					}

					if ( isset( $field_configuration ) &&
						( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] == 0 )
					) {
						$value = $field_configuration;
					} elseif ( isset( $field_configuration ) && is_string( $field_configuration ) ) {
						$field_configuration = array( 'skypename' => $field_configuration, 'style' => '' );
						$value = $field_configuration;
					} else {
						$value = array( 'skypename' => '', 'style' => '' );
						$field_configuration = $value;
					}

					$attributes = array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'edit_skype_text' => $this->_formHelper->getLocalisedMessage( 'edit_skype_button' ),
						'value' => isset( $field_configuration[0]['skypename'] ) ? $field_configuration[0]['skypename'] : $field_configuration['skypename'],
						'_nonce' => wp_create_nonce( 'insert_skype_button' ),
					);
					break;

				// everything else defaults to a simple text field
				default:
					$type = 'textfield';
					break;
			}

			$attributes = array_merge( $attributes, $additional_options );

			if ( isset( $attributes['make_readonly'] ) && ! empty( $attributes['make_readonly'] ) ) {
				unset( $attributes['make_readonly'] );
				if ( ! is_array( $attributes ) ) {
					$attributes = array();
				}
				$attributes['readonly'] = 'readonly';
			}

			// repetitive field (special care)
			if ( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] ) {
				$value = isset( $postData->fields[ $field_name ] ) ? $postData->fields[ $field_name ] : isset( $value ) ? $value : array();
				$objs = $zebraForm->add( $type, $name, $value, $attributes, $field );
			} else {
				$objs = $zebraForm->add( $type, $name, $value, $attributes, $field );
			}

		} else {
			// taxonomy field or auxilliary taxonomy field (eg popular terms etc..)
			if ( ! array_key_exists( 'master_taxonomy', $field ) ) { // taxonomy field
				if ( $field['hierarchical'] ) {
					if ( in_array( $preset_value, array( 'checkbox', 'select' ) ) ) {
						$tax_display = $preset_value;
					} else {
						$tax_display = 'checkbox';
					}
				}

				if ( $postData && isset( $postData->taxonomies[ $field_name ] ) ) {
					if ( ! $field['hierarchical'] ) {
						$field_configuration = array(
							'terms' => $postData->taxonomies[ $field_name ]['terms'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'remove_text' => $this->_formHelper->getLocalisedMessage( 'remove_taxonomy' ),
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'auto_suggest' => true,
							'show_popular_text' => $this->_formHelper->getLocalisedMessage( 'show_popular' ),
							'hide_popular_text' => $this->_formHelper->getLocalisedMessage( 'hide_popular' ),
							'show_popular' => $show_popular,
						);
					} else {
						$field_configuration = array(
							'terms' => $postData->taxonomies[ $field_name ]['terms'],
							'all' => $field['all'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'add_new_text' => $this->_formHelper->getLocalisedMessage( 'add_new_taxonomy' ),
							'parent_text' => __( '-- Parent --', 'wp-cred' ),
							'type' => $tax_display,
							'single_select' => $single_select,
						);
					}
				} elseif ( $_POST && isset( $_POST ) ) {

					if ( ! $field['hierarchical'] ) {
						$field_configuration = array(
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'remove_text' => $this->_formHelper->getLocalisedMessage( 'remove_taxonomy' ),
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'auto_suggest' => true,
							'show_popular_text' => $this->_formHelper->getLocalisedMessage( 'show_popular' ),
							'hide_popular_text' => $this->_formHelper->getLocalisedMessage( 'hide_popular' ),
							'show_popular' => $show_popular,
						);
					} else {
						$field_configuration = array(
							'all' => $field['all'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'add_new_text' => $this->_formHelper->getLocalisedMessage( 'add_new_taxonomy' ),
							'parent_text' => __( '-- Parent --', 'wp-cred' ),
							'type' => $tax_display,
							'single_select' => $single_select,
						);
					}
				} else {

					if ( ! $field['hierarchical'] ) {
						$field_configuration = array(
							//'terms'=>array(),
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'remove_text' => $this->_formHelper->getLocalisedMessage( 'remove_taxonomy' ),
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'auto_suggest' => true,
							'show_popular_text' => $this->_formHelper->getLocalisedMessage( 'show_popular' ),
							'hide_popular_text' => $this->_formHelper->getLocalisedMessage( 'hide_popular' ),
							'show_popular' => $show_popular,
						);
					} else {
						$field_configuration = array(
							'all' => $field['all'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'add_new_text' => $this->_formHelper->getLocalisedMessage( 'add_new_taxonomy' ),
							'parent_text' => __( '-- Parent --', 'wp-cred' ),
							'type' => $tax_display,
							'single_select' => $single_select,
						);
					}
				}

				$field_configuration['class'] = isset( $additional_options['class'] ) ? $additional_options['class'] : "";
				$field_configuration['output'] = isset( $additional_options['output'] ) ? $additional_options['output'] : "";

				// if not hierarchical taxonomy
				if ( ! $field['hierarchical'] ) {
					$objs = /* & */
						$zebraForm->add( 'taxonomy', $name, $value, $field_configuration );
				} else {
					$objs = /* & */
						$zebraForm->add( 'taxonomyhierarchical', $name, $value, $field_configuration );
				}

				// register this taxonomy field for later use by auxilliary taxonomy fields
				CRED_StaticClass::$out['taxonomy_map']['taxonomy'][ $field_name ] = &$objs;
				// if a taxonomy auxiliary field exists attached to this taxonomy, add this taxonomy id to it
				if ( isset( CRED_StaticClass::$out['taxonomy_map']['aux'][ $field_name ] ) ) {
					CRED_StaticClass::$out['taxonomy_map']['aux'][ $field_name ]->set_attributes( array( 'master_taxonomy_id' => $objs->attributes['id'] ) );
				}
			} else { // taxonomy auxilliary field (eg most popular etc..)
				if ( isset( $preset_value ) ) // use translated value by WPML if exists
				{
					$field_configuration = cred_translate(
						'Value: ' . $preset_value, $preset_value, 'cred-form-' . $form->form->post_title . '-' . $form->form->ID
					);
				} else {
					$field_configuration = null;
				}
			}
		}

		return $objs;
	}

}