var credFrontEndViewModel = {
    /**
     * @description JSON object that holds AJAX messages to be sent when forms are fully initialized to prevent getting responses before observable bindings are made.
     */
    messagesQueue: {},
    /**
     * @description Array of CRED forms present in the currnet page
     */
    credForms: [],
    /**
     * @description a list of CRED form IDs that's ready for third-party actions
     */
    readyCREDForms: [],
    /**
     * @description Updates CRED forms auto-draft post ID
     */

    updateFormsPostID: function () {

        this.getAllForms();

        for (var single_form in this.credForms) {
            single_form = this.credForms[single_form];
            var form_data = this.extractFormData(single_form);
            this.assignDynamicObservableID(form_data);
        }
    },
    /**
     * @description Returns all CRED forms in document
     */
    getAllForms: function () {
        var document_forms = jQuery('.cred-form, .cred-user-form', document);

        for (var form_index in document_forms) {
            if (isNaN(form_index)) {
                break;
            }
            this.credForms.push(document_forms[form_index]);
        }
        return this.credForms;

    },
    /**
     * @description Assigns an observable binding ID to each CRED form to be updated dynamically when observable value changes.
     * @param form_data JSON object returned from extractFormData method
     */
    assignDynamicObservableID: function (form_data) {
        if (form_data.post_id_node !== undefined && form_data.post_id_node !== null) {
            form_data.binding_property_name = "post_id_observable_" + this.uniqueID() + this.uniqueID();

            this[form_data.context_id][form_data.binding_property_name] = ko.observable(form_data.post_id);
            this[form_data.context_id][form_data.binding_property_name + "_submit"] = ko.computed(function () {
                return (this[form_data.context_id][form_data.binding_property_name]() === undefined);
            }, this);

            jQuery(form_data.post_id_node).attr('data-bind', 'value: ' + form_data.binding_property_name);
            jQuery(form_data.form_submit_node).attr('disabled', 'disabled');

            var cred_check_id_ajax_data = {
                action: 'check_post_id',
                post_id: form_data.post_id,
                form_id: form_data.form_id,
                binding_property_name: form_data.binding_property_name,
                form_index: form_data.binding_property_name,
                form_context_id: form_data.context_id
            };

            this.messagesQueue[form_data.binding_property_name] = cred_check_id_ajax_data;
        }
    },
    /**
     * @description Returns a JSON object with useful form information including form_id, auto-draft post_id, the hidden input where auto-draft post_id is saved, and the submit button for the form.
     * @param form HTML form node
     */
    extractFormData: function (form) {
        //Setup a knockout context for each form
        var form_context_binding_id = 'cred_form_context_' + this.uniqueID();
        this[form_context_binding_id] = {};

        jQuery(form).attr('data-bind', 'with: ' + form_context_binding_id);

        return {
            form_id: (jQuery(form).children("input[name='_cred_cred_prefix_form_id']") ? jQuery(form).children("input[name='_cred_cred_prefix_form_id']").val() : null),
            post_id: (jQuery(form).children("input[name='_cred_cred_prefix_post_id']") ? jQuery(form).children("input[name='_cred_cred_prefix_post_id']").val() : null),
            post_id_node: jQuery(form).children("input[name='_cred_cred_prefix_post_id']"),
            form_submit_node: jQuery(form).children('.wpt-form-submit'),
            context_id: form_context_binding_id
        };
    },
    /**
     * @description Returns a uniqueID
     */
    uniqueID: function () {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    },
    /**
     * @description Sends out all messages in the messagesQueue via AJAX
     */
    initQueue: function () {
        var queue_keys = Object.keys(this.messagesQueue);
        if (queue_keys.length > 0) {
            for (var key in queue_keys) {
                var message = this.messagesQueue[queue_keys[key]];
                jQuery.post(cred_frontend_settings.ajaxurl, message, function (callback_data) {
                    if (callback_data != "" && callback_data != 0) {
                        try {
                            var callback_data = JSON.parse(callback_data);
                            credFrontEndViewModel[callback_data.form_context_id][callback_data.observable_id](callback_data.pid);
                        } catch (err) {
                            console.error('CRED: Error parsing callback data for `check_post_id` ');
                        }
                    }
                });
            }
        }
    },
    /**
     * @description Looks up all CRED file buttons and assigns event listeners for undo, delete and upload actions.
     */
    initCREDFile: function () {
        jQuery('.js-wpt-credfile-delete, .js-wpt-credfile-undo').on('click', function (e) {
            e.preventDefault();

            var that = jQuery(this),

                credfile_action = that.data('action'),
                credfile_container = that.closest('.wpt-repctl');


            if (credfile_container.length < 1) {
                credfile_container = that.closest('.js-wpt-field-items');
            }

            var that_delete_button = jQuery('.js-wpt-credfile-delete', credfile_container),

                that_undo_button = jQuery('.js-wpt-credfile-undo', credfile_container),
                that_hidden_input = jQuery('.js-wpv-credfile-hidden', credfile_container),
                that_file_input = jQuery('.js-wpt-credfile-upload-file', credfile_container),
                that_preview = jQuery('.js-wpt-credfile-preview', credfile_container),
                that_existing_value = that_hidden_input.val();


            var myid = that_hidden_input.attr('name');
            if (credfile_action == 'delete') {
                that_file_input.val('').prop('disabled', false).prop('alt', '').prop('res', '').show();
                that_hidden_input.val('').prop('disabled', true);
                that_preview.hide();

                if (that_existing_value != '') {
                    //that_undo_button.show();
                } else {
                    that_undo_button.hide();
                }

                if (myid == '_featured_image') {
                    jQuery('input[name="attachid_' + myid + '"').val('');
                } else {
                    if (that.closest('.js-wpt-repetitive').length > 0) {
                    } else {
                        jQuery('#' + myid).prop('disabled', false);
                    }
                }
                if (jQuery('.js-wpt-credfile-preview > img', credfile_container).length > 0) {
                    jQuery('.js-wpt-credfile-preview > img', credfile_container).remove();
                }
                that_file_input.trigger('change');
            } else if (credfile_action == 'undo') {
                that_file_input.prop('disabled', true).hide();
                that_hidden_input.prop('disabled', false);
                that_file_input.trigger('change');
                that_preview.show();
                //that_delete_button.show();
                that_undo_button.hide();
                if (myid == '_featured_image')
                    jQuery('#attachid_' + myid).val(jQuery("input[name='_cred_cred_prefix_post_id']").val());
                else {
                    if (that.closest('.js-wpt-repetitive').length > 0) {
                    } else
                        jQuery('#' + myid).prop('disabled', false);
                }
            }
        });

        jQuery('.js-wpt-credfile-upload-file').on('change', function (e) {
            e.preventDefault();
            var that = jQuery(this),
                credfile_container = that.closest('.wpt-repctl');
            if (credfile_container.length < 1) {
                credfile_container = that.closest('.js-wpt-field-items');
            }
            var that_delete_button = jQuery('.js-wpt-credfile-delete', credfile_container),
                that_undo_button = jQuery('.js-wpt-credfile-undo', credfile_container),
                that_hidden_input = jQuery('.js-wpv-credfile-hidden', credfile_container),
                that_preview = jQuery('.js-wpt-credfile-preview', credfile_container),
                that_existing_value = that_hidden_input.val();

            if (that_existing_value === '' || that_existing_value != that.val()) {
                that_undo_button.hide();
            }
        });
    },
    /**
     * @description Adds IDs for both labels and inputs for accessibility support
     * @since 1.8.6
     */
    addAccessibilityIDs: function () {
        var $cred_form_labels = jQuery('.cred-form .form-group label');
        for (var form_label_index in $cred_form_labels) {
            if (isNaN(form_label_index)) {
                break;
            }

            var $form_label = jQuery($cred_form_labels[form_label_index]);
            var accessibility_id = this.uniqueID();

            $input_array = [];

            $input_array.push($form_label.parent().find(':input:not(:button, :hidden)'));
            $input_array.push($form_label.parent().find('select')[0]);
            $input_array.push($form_label.parent().find('textarea')[0]);

            if ($input_array.length > 0) {
                for (var input in $input_array) {
                    if ($input_array[input] !== undefined) {
                        $input_array[input] = jQuery($input_array[input]);
                        if ($input_array[input].attr('id') !== undefined && $input_array[input].attr('id') !== null && $input_array[input].attr('id') != "") {
                            $form_label.attr('for', $input_array[input].attr('id'));
                        } else {
                            $input_array[input].attr('id', accessibility_id);
                            $form_label.attr('for', accessibility_id);
                        }
                    }
                }
            }
        }
    },
    /**
     * @description Cleans all CRED form nodes and apply the bindings
     * @since 1.9
     */
    applyViewModelBindings: function() {
        //Clear initialised CRED forms
        this.readyCREDForms = [];

        for (var cred_form in this.credForms) {
            ko.cleanNode(this.credForms[cred_form]);
            ko.applyBindings(this, this.credForms[cred_form]);

            var cred_form_id = jQuery(this.credForms[cred_form]).attr('id');
            this.readyCREDForms.push(cred_form_id);

            jQuery('.js-wpt-validate', '#' + cred_form_id).removeClass('js-wpt-validate');
            jQuery(document).trigger('cred_form_ready', {
                form_id: cred_form_id
            });
        }
    },
    /**
     * @description Disables file inputs while the form is in preview mode
     * @since 1.9
     */
    activatePreviewMode: function() {
        //disable media buttons in preview mode
        if(window.hasOwnProperty('cred_form_preview_mode') && window.cred_form_preview_mode == true) {
            jQuery('#insert-media-button').prop('disabled', true);
            jQuery('.insert-media').prop('disabled', true);
            jQuery('.cred-form input[type="file"]').attr('onclick', 'return false');
            jQuery('.cred-user-form input[type="file"]').attr('onclick', 'return false');

            jQuery(document).on('toolset_repetitive_field_added', function() {
                jQuery('input[type="file"]', $parent).attr('onclick', 'return false');
                jQuery('input[type="file"]', $parent).attr('onclick', 'return false');
            });

        }
    }
};


(function () {
    //Add observable IDs and prepare messages queue
    credFrontEndViewModel.updateFormsPostID();

    //Apply bindings and init ajax requests to update forms
    setTimeout(function () {
        var isPreview = jQuery('#lbl_preview').length > 0;
        if (!isPreview) {
            credFrontEndViewModel.applyViewModelBindings();
        }
        credFrontEndViewModel.initCREDFile();
    }, 200);

    setTimeout(function () {
        credFrontEndViewModel.initQueue();
    }, 300);


    jQuery(document).ready(function () {
        credFrontEndViewModel.addAccessibilityIDs();
        credFrontEndViewModel.activatePreviewMode();
    });

    jQuery(document).on('cred_form_ajax_completed', function () {
        if (jQuery.fn.iris) {
            wptColorpicker.init('body');
        }

        //Reset initialised validation forms
        if(window.hasOwnProperty('initialisedCREDForms')) {
            initialisedCREDForms = [];
        }

        //Reapply bindings for the form
        credFrontEndViewModel.applyViewModelBindings();
        credFrontEndViewModel.activatePreviewMode();
    });

    jQuery(document).on('cred_form_ready', function(evt, form_data) {
        var $form = jQuery("#" + form_data.form_id);

        //uncheck generic checkboxes
        jQuery('input[type="checkbox"][cred_generic="1"]').each(function(index, checkbox){
            if(jQuery(checkbox).attr('default_checked') != 1) {
                jQuery(checkbox).prop('checked', false);
            } else {
                jQuery(checkbox).prop('checked', true);
            }
        });

        //Queue after conditional and validation init
        setTimeout(function() {
            jQuery('.form-submit', $form).attr('disabled', false);
        }, 4);

        if(typeof(wptColorpicker) !== 'undefined') {
            wptColorpicker.init($form);
        }
    });

})();