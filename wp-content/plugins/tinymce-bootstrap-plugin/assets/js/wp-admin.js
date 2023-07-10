var $ = jQuery.noConflict();
$(document).ready(function() {
    $('#custom-editor-input-wrapper').hide();
    $('select[name="bootstrap_css_path"]').on('change', function(event) {
        if($(this).val() == 'custom') {
            $('#custom-editor-input-wrapper').fadeIn(200);
        } else {
            $('#custom-editor-input-wrapper').fadeOut(200);
        }
    });
    $('select[name="bootstrap_css_path"]').trigger('change');

    $('#custom-editor-background-wrapper').hide();
    $('input[name="tinymce_custom_background"]').on('change', function(event) {
        if(this.checked === true) {
            $('#custom-editor-background-wrapper').fadeIn(200);
        } else {
            $('#custom-editor-background-wrapper').fadeOut(200);
        }
    });
    $('input[name="type"]').on('change', function(event) {
        var value = $('input[name="type"]:checked').val();
        if(value == 'dropdownMenu') {
            $('#dropdown-menu-wrapper').fadeIn(200);
        } else {
            $('#dropdown-menu-wrapper').fadeOut(200);
        }
    });
    $('input[name="type"]').trigger('change');

    $('input[name="checkall"]').on('change', function(event) {
        if(this.checked === true) {
            $('input[name^="tbp_element_choice_"]').prop('checked', true);
        } else {
            $('input[name^="tbp_element_choice_"]').prop('checked', false);
        }
    });

    $('#colorpicker').ColorPicker({
        onShow: function (colpkr) {
            $(colpkr).fadeIn(500);

            return false;
        },
        onHide: function (colpkr) {
            $(colpkr).fadeOut(500);

            return false;
        },
        onChange: function (hsb, hex, rgb) {
            color = '#' + hex;
            $('#colorpicker').val(color);
        }
    });
});
