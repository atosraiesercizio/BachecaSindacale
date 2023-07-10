function getExtension(filename) {
    return filename.split('.').pop().toLowerCase();
}

function isImage(file) {
    switch (getExtension(file)) {
        //if .jpg/.gif/.png do something
        case 'jpg':
        case 'gif':
        case 'png':
        case 'jpeg':
        case 'bmp':
        case 'svg':
            return true;
            break;

    }
    return false;
}

//new RegExp('/regex'+DATA-FROM-INPUT+'', 'i');
jQuery(function () {
    'use strict';

    function init_file_field(i, file) {
        var current_file = file;
        var url = settings.ajaxurl;
        var nonce = settings.nonce;
        var $current_form = jQuery(current_file).closest('form');
        var $current_file = jQuery(current_file, $current_form);
        var id = $current_file.attr('id');
        var validation = ($current_file.attr('data-wpt-validate')) ? $current_file.attr('data-wpt-validate') : '[]';
        var obj_validation = jQuery.parseJSON(validation);

        for (var element in obj_validation) {
            if (element === 'extension') {
                for (var sub_element in obj_validation[element]) {
                    if (sub_element === 'args') {
                        var validation_args = obj_validation[element][sub_element][0];
                        //validation_args = validation_args.split('|').join(',');
                    }
                    if (sub_element === 'message') {
                        var validation_message = obj_validation[element][sub_element];
                    }
                }
            }
        }

        var current_post_id = jQuery("input[name='_cred_cred_prefix_post_id']", $current_form).val();
        var current_form_id = jQuery("input[name='_cred_cred_prefix_form_id']", $current_form).val();

        jQuery(file).fileupload({
            url: url + '?id=' + current_post_id + '&formid=' + current_form_id + '&nonce=' + nonce,
            dataType: 'json',
            cache: false,
            maxChunkSize: 0,
            drop: function (e, data) {
                return false
            },
            dragover: function (e) {
                return false
            },
            formData: {id: current_post_id, formid: current_form_id},
            //acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
            done: function (e, data) {
                //progress bar hiding
                var $file_selector = jQuery('#' + id, $current_form);
                var wpt_id = $file_selector.siblings(".meter").attr("id");
                var $current_field_id = jQuery('#' + wpt_id, $current_form);
                var $progress_bar_selector = jQuery('#' + wpt_id + ' .progress-bar', $current_form);

                $current_field_id.show();
                $progress_bar_selector.css(
                    {'width': '0%'}
                );
                $current_field_id.hide();

                if (data._response.result.error && data._response.result.error !== '') {
                    alert(data._response.result.error);
                }
                if (data.result.files) {
                    jQuery.each(data.result.files, function (index, file) {

                        var wpt_id = id.replace("_file", "");

                        var hidden_id = wpt_id + '_hidden';
                        var element_number = 0;
                        if (id.toLowerCase().indexOf("wpt-form-el") >= 0) {
                            element_number = id.replace(/[^0-9]/g, '');
                            var new_element_number = element_number - 1;
                            hidden_id = "wpt-form-el" + new_element_number;
                        }

                        var is_repetitive = $file_selector.parent().parent().hasClass("js-wpt-repetitive");
                        if (is_repetitive) {
                            var element_name = wpt_id.replace(element_number, "[" + element_number + "]");
                            jQuery('input[name="' + element_name + '"]#' + wpt_id, $current_form).val(file);
                        } else {
                            jQuery('input[name=' + wpt_id + ']#' + wpt_id, $current_form).val(file);
                        }

                        //hidden text set
                        var $file_hidden_selector = jQuery('#' + hidden_id, $current_form);
                        $file_hidden_selector.val(file);
                        $file_hidden_selector.prop('disabled', false);
                        //file field disabled and hided
                        $file_selector.hide();
                        $file_selector.prop('disabled', true);

                        //remove restore button
                        $file_selector.siblings(".js-wpt-credfile-undo").hide();

                        var preview_span = $file_selector.siblings(".js-wpt-credfile-preview");
                        var $preview_span_selector = jQuery(preview_span, $current_form);
                        jQuery('.wpt-form-error', preview_span.parent()).remove();

                        //add image/file uploaded and button to delete
                        if (isImage(file) && data.result.previews) {
                            var preview = data.result.previews[index];
                            var attachid = data.result.attaches[index];

                            //console.log(preview_span);
                            if (typeof preview_span !== undefined) {
                                $preview_span_selector.find('.js-wpt-uploaded-file').remove();
                                if ($preview_span_selector.find("img").length > 0 &&
                                    $preview_span_selector.find("input").length > 0) {
                                    $preview_span_selector.find("img").attr("src", preview);
                                    $preview_span_selector.find("input").attr("rel", preview);
                                } else {
                                    //append new image and delete button to the span
                                    jQuery("<img id='loaded_" + wpt_id + "' src='" + preview + "'>", $current_form).prependTo(preview_span);
                                }

                                if ($file_hidden_selector.attr('name') == '_featured_image') {
                                    var _featured_image_name = $file_hidden_selector.attr('name');
                                    if (jQuery("#attachid_" + _featured_image_name, $current_form).lenght > 0) {
                                        jQuery("#attachid_" + _featured_image_name, $current_form).attr("value", attachid);
                                    } else {
                                        jQuery("<input id='attachid_" + _featured_image_name + "' name='attachid_" + _featured_image_name + "' type='hidden' value='" + attachid + "'>", $current_form).appendTo(preview_span.parent());
                                    }
                                }
                            }

                        } else {
                            $preview_span_selector.find('.js-wpt-uploaded-file').remove();
                            $preview_span_selector.find('img').remove();
                            jQuery("<a class='js-wpt-uploaded-file' id='loaded_" + wpt_id + "' href='" + file + "' target='_blank'>" + file + "</a>", $current_form).prependTo(preview_span);
                        }
                        if (typeof preview_span !== undefined) {
                            $preview_span_selector.show();
                        }

                        wptCredfile.init('body');
                    });
                    credfile_fu_init();
                }
            },
            add: function (e, data) {
                if (validation_args) {
                    var uploadErrors = [];
                    var acceptFileTypes = new RegExp('/regex' + validation_args + '', 'i'); //^image\/(gif|jpe?g|png)$/i;
                    if (data.originalFiles[0]['type'].length && !acceptFileTypes.test(data.originalFiles[0]['type'])) {
                        uploadErrors.push(validation_message);
                    }
                    if (data.originalFiles[0]['size'].length && data.originalFiles[0]['size'] > 5000000) {
                        uploadErrors.push(settings.too_big_file_alert_text);
                    }
                    if (uploadErrors.length > 0) {
                        alert(uploadErrors.join("\n"));
                    } else {
                        data.submit();
                    }
                } else {
                    data.submit();
                }

            },
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                var $file_selector = jQuery('#' + id, $current_form);
                var wpt_id = $file_selector.siblings(".meter").attr("id");
                var $current_field_id = jQuery('#' + wpt_id, $current_form);
                $current_field_id.show();
                jQuery('#' + wpt_id + ' .progress-bar', $current_form).css(
                    {'width': progress + '%'}
                );
            },
            fail: function (e, data) {
                var wpt_id = id.replace("_file", "");
                var $current_field_id = jQuery('#' + wpt_id, $current_form);
                $current_field_id.hide();
                jQuery('#progress_' + wpt_id + ' .progress-bar', $current_form).css(
                    {'width': '0%'}
                );

                alert("Upload Failed !");
            }
        }).prop('disabled', !jQuery.support.fileInput)
            .parent().addClass(jQuery.support.fileInput ? undefined : 'disabled');

        jQuery(document).bind('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }

    function credfile_fu_init() {
        jQuery('input[type="file"]:visible').each(init_file_field);

        jQuery(document).off('click', '.js-wpt-credfile-delete, .js-wpt-credfile-undo', null);
        jQuery(document).on('click', '.js-wpt-credfile-delete, .js-wpt-credfile-undo', function (e) {
            jQuery('input[type="file"]:visible').each(init_file_field);
        });

        //AddRepetitive add event
        wptCallbacks.addRepetitive.add(function () {
            jQuery('input[type="file"]:visible').each(init_file_field);
        });

        //AddRepetitive remove event
        wptCallbacks.addRepetitive.remove(function () {
            //console.log("TODO: delete file related before removing")
        });
    }

    //Fix the not visible field under false conditional
    jQuery(document).off('click', 'input[type="file"]', null);
    jQuery(document).on('click', 'input[type="file"]', function () {
        credfile_fu_init();
    });

    credfile_fu_init();
});