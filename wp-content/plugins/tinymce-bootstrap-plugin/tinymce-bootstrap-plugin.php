<?php
/*
Plugin Name: TinyMce Bootstrap Plugin
Plugin URI: http://codecanyon.net/item/tinymce-bootstrap-plugin/10086522
Description: TinyMce Bootstrap Plugin adds Bootstrap toolbar in tinyMce, giving access to visual editors to insert Bootstrap elements into your WP content.
Version: 2.3.5
Author: Gilles Migliori
Author URI: http://codecanyon.net/user/migli
*/

// plugin version
add_option('tbp_version', '2.3.5');

add_action('admin_init', 'tbp_admin_init');
add_action('admin_menu', 'tbp_admin_menu');
add_action('admin_enqueue_scripts', 'tbp_admin_scripts');

// default media path when activating plugin

register_activation_hook(__FILE__, 'set_up_default_options');

function set_up_default_options()
{
    $upload_dir = wp_upload_dir();
    add_option('bootstrap_media_path', $upload_dir['baseurl']);
    add_option('type', 'buttongroup');
    add_option('dropdown_text', '');
    add_option('bootstrap_icon_font', 'glyphicon');
}

function tbp_admin_init()
{
    global $elements;
    // register tbp elements array
    for ($i=0; $i < count($elements); $i++) {
        register_setting('tbp_options', 'tbp_element_choice_' . $elements[$i]);
    }
    register_setting('tbp_options', 'tinymce_custom_background');
    register_setting('tbp_options', 'tinymce_background_color');
    register_setting('tbp_options', 'use_frontend_css');
    register_setting('tbp_options', 'bootstrap_css_path');
    register_setting('tbp_options', 'custom_bootstrap_css_path');
    register_setting('tbp_options', 'bootstrap_media_path');
    register_setting('tbp_options', 'type');
    register_setting('tbp_options', 'dropdown_text');
    register_setting('tbp_options', 'bootstrap_icon_font');
}

// create custom plugin settings menu
function tbp_admin_menu()
{
    //create new top-level menu
    $page = add_menu_page('TinyMce Bootstrap Plugin Settings', 'TinyMce Bootstrap Settings', 'administrator', __FILE__, 'tbp_settings_page', 'dashicons-editor-bold');
}

// add bootstrap icon font to admin panel

function tbp_admin_scripts($hook)
{

    $screen = get_current_screen();
    // echo '$screen->base = ' . $screen->base;
    if (preg_match('`tinymce-bootstrap-plugin`', $hook)) {
        wp_deregister_style( 'editor-buttons' );
        // tbp admin page
        // css
        wp_register_style('TbpAdminStylesheet', plugins_url('assets/css/admin-page.css', __FILE__));
        wp_register_style('TbpColorpickerStylesheet', plugins_url('assets/css/colorpicker.min.css', __FILE__));
        wp_enqueue_style('TbpAdminStylesheet');
        wp_enqueue_style('TbpColorpickerStylesheet');
        // js
        wp_enqueue_script('admin_page_colorpicker', plugin_dir_url(__FILE__) . 'assets/js/colorpicker.min.js', 'jquery');
        wp_enqueue_script('admin_page_script', plugin_dir_url(__FILE__) . 'assets/js/wp-admin.js', 'jquery');
    } elseif ($screen->base == 'post') {
        // plugin stylesheets on pages with editor
        wp_register_style('TbpEditorStylesheet', plugins_url('assets/css/editor.css', __FILE__));
        wp_register_style('TbpColorpickerStylesheet', plugins_url('assets/css/colorpicker.min.css', __FILE__));
        wp_enqueue_style('TbpEditorStylesheet');
        wp_enqueue_style('TbpColorpickerStylesheet');
    } else {
        return;
    }
}

$elements = array('btn', 'icon', 'image', 'table', 'template', 'breadcrumb', 'pagination', 'pager', 'label', 'badge', 'alert', 'panel', 'snippet');
$frontend_css_files = array();

// get the selected Bootstrap css in option list
function get_selected($url)
{
    $bootstrap_css_path = get_option('bootstrap_css_path');
    if ($url == $bootstrap_css_path) {
        return ' selected';
    } else {
        return false;
    }
}

// get the selected Bootstrap icon font
function get_selected_icon_font($font)
{
    $bootstrap_icon_font = get_option('bootstrap_icon_font');
    if ($font == $bootstrap_icon_font) {
        return ' selected';
    } else {
        return false;
    }
}

// get custom Bootstrap css path from plugin options | theme default css
function get_custom_path()
{
    $custom_path = get_option('custom_bootstrap_css_path');
    if (!empty($custom_path)) {
        return $custom_path;
    } else {
        return get_stylesheet_uri();
    }
}

function tbp_settings_page()
{
    global $elements;
    ?>
    <div class="wrap">
    <h2>TinyMce Bootstrap Plugin Settings</h2>
    <h3>Check the elements to display in TinyMce Bootstrap toolbar</h3>
    <form method="post" action="options.php">
        <?php settings_fields('tbp_options'); ?>
        <?php do_settings_sections('tbp_options'); ?>
        <table class="form-table mce-bs-icon-btn">
            <tr valign="top">
                <th scope="row">check all</th>
                <td><input type="checkbox" name="checkall" value="checked" /></td>
                <td>&nbsp;</td>
            </tr>
    <?php
        echo '<tr valign="top">' . " \n";
        for ($i=0; $i < count($elements); $i++) {
            $option_name = 'tbp_element_choice_' . $elements[$i];
            $icon = '<i class="mce-i-icon-' . $elements[$i] . '"></i>';
            $checked = esc_attr(get_option($option_name));
            echo '    <th scope="row">' . $icon . $elements[$i] . '</th>' . " \n";
            echo '    <td><input type="checkbox" name="' . $option_name . '" value="checked"' . $checked . ' /></td>' . " \n";
            if ((round($i / 2) - ($i / 2)) != 0) {
                echo '</tr>' . " \n";
                echo '<tr valign="top">' . " \n";
            } elseif ($i == 12) {
                echo '<td>&nbsp;</td>' . " \n";
                echo '</tr>' . " \n";
            }
        }
    ?>
        </table>
        <h3>Choose witch Bootstrap css to use in Wordpress Admin Editor + Frontend Template</h3>
        <?php
            $checked = esc_attr(get_option('use_frontend_css'));
        ?>
        <p class="help" style="position:relative;padding-left: 40px;"><span class="dashicons dashicons-info" style="position:absolute;top:0;left: -0;"></span>Check "use Wordpress Theme css in Wordpress Admin Editor" if your active theme includes Bootstrap css.<br>Otherwise, choose any Bootstrap css in the list ; it will be included in admin editor and in frontend pages.<br>The Default Bootstrap CSS, Darkly, Flatly &amp; Slate themes are included in this plugin.</p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Use Wordpress Theme css <br>in Admin Editor : </th>
                <td><input type="checkbox" name="use_frontend_css" value="checked"<?php echo $checked; ?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Bootstrap CSS to add to Wordpress Theme <br>&amp; Admin Editor : </th>
                <td>
                    <select name="bootstrap_css_path">
    <?php
        $options = array(
            array(
                'url' => '',
                'text' => 'None - Bootstrap is already included in my Theme css'
           ),
            array(
                'url' => plugins_url('assets/css/bootstrap.min.css', __FILE__),
                'text' => 'default Bootstrap CSS'
           ),
            array(
                'url' => plugins_url('assets/themes/darkly/bootstrap.min.css', __FILE__),
                'text' => 'Bootstrap Darkly Theme'
           ),
            array(
                'url' => plugins_url('assets/themes/flatly/bootstrap.min.css', __FILE__),
                'text' => 'Bootstrap Flatly Theme'
           ),
            array(
                'url' => plugins_url('assets/themes/slate/bootstrap.min.css', __FILE__),
                'text' => 'Bootstrap Slate Theme'
           ),
            array(
                'url' => 'custom',
                'text' => 'Custom'
           )
       );
        for ($i=0; $i < count($options); $i++) {
            $url      = $options[$i]['url'];
            $text     = $options[$i]['text'];
            $selected = get_selected($url);
            echo '<option value="' . $url . '"' . $selected . '>' . $text . '</option>' . " \n";
        }
    ?>
                    </select>
                    <div id="custom-editor-input-wrapper">

                        <p><label><strong>Custom Bootstrap CSS URL for Wordpress : </strong></label><br><input type="text" size="80" name="custom_bootstrap_css_path" value="<?php echo get_custom_path(); ?>"></p>
                        <p><span class="dashicons dashicons-info"></span> Your theme CSS path is <code><?php echo get_bloginfo('template_url') . '/style.css'; ?></code></p>
                    </div>
                </td>
            </tr>
            <?php
            $checked = esc_attr(get_option('tinymce_custom_background'));
        ?>
            <tr valign="top">
                <th scope="row">Custom Background color <br>in Admin Editor : </th>
                <td>
                    <input type="checkbox" name="tinymce_custom_background" value="checked"<?php echo $checked; ?> />
                    <div id="custom-editor-background-wrapper">
                        <p><label><strong>Pick a color : </strong></label><input type="text" size="80" name="tinymce_background_color" id="colorpicker" value="<?php echo get_option('tinymce_background_color'); ?>"></p>
                    </div>
                </td>
            </tr>
            <?php
            $buttongroup_checked = '';
            $dropdownMenu_checked = '';
            if(get_option('type') == 'dropdownMenu') {
                $dropdownMenu_checked = ' checked';
            } else {
                $buttongroup_checked = ' checked';
            }
        ?>
            <tr valign="top">
                <th scope="row">Display : </th>
                <td>
                    <p><label for="buttongroup"><input type="radio" name="type" value="buttongroup"<?php echo $buttongroup_checked; ?> /> Toolbar with Bootstrap icons</label></p>
                    <p><label for="dropdownMenu"><input type="radio" name="type" value="dropdownMenu"<?php echo $dropdownMenu_checked; ?> /> Dropdown menu</label></p>
                    <div id="dropdown-menu-wrapper">
                        <p><label><strong>Label for Dropdown menu : </strong></label><input type="text" size="80" name="dropdown_text" value="<?php echo get_option('dropdown_text'); ?>"></p>
                    </div>
                </td>
            </tr>
            <?php
            $selected = esc_attr(get_option('bootstrap_icon_font'));
        ?>
            <tr valign="top">
                <th scope="row">Icon Font : </th>
                <td>
                <label>Icon Font : </label>
                <select name="bootstrap_icon_font">
                    <option value="glyphicon"<?php echo get_selected_icon_font('glyphicon'); ?>>glyphicon</option>
                    <option value="ionicon"<?php echo get_selected_icon_font('ionicon'); ?>>ionicon</option>
                    <option value="fontawesome"<?php echo get_selected_icon_font('fontawesome'); ?>>fontawesome</option>
                    <option value="weathericon"<?php echo get_selected_icon_font('weathericon'); ?>>weathericon</option>
                    <option value="mapicon"<?php echo get_selected_icon_font('mapicon'); ?>>mapicon</option>
                    <option value="octicon"<?php echo get_selected_icon_font('octicon'); ?>>octicon</option>
                    <option value="typicon"<?php echo get_selected_icon_font('typicon'); ?>>typicon</option>
                    <option value="elusiveicon"<?php echo get_selected_icon_font('elusiveicon'); ?>>elusiveicon</option>
                    <option value="materialdesign"<?php echo get_selected_icon_font('materialdesign'); ?>>materialdesign</option>
                </select>
                </td>
            </tr>
        </table>
		<?php
            $upload_dir = wp_upload_dir();
        ?>
		<p class="help" style="position:relative;padding-left: 40px;"><span class="dashicons dashicons-info" style="position:absolute;top:0;left: -0;"></span>Default wordpress media path is <?php echo $upload_dir['baseurl']; ?></p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Wordpress Media path : </th>
                <td>
                    <input type="text" name="bootstrap_media_path" value="<?php echo get_option('bootstrap_media_path'); ?>" />
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>

    </form>
    </div>
<?php
}

/* =============================================
    End admin settings
============================================= */

//Include Bootstrap CSS in Frontend Template
$bootstrap_css_path = get_option('bootstrap_css_path');
if ($bootstrap_css_path !== get_bloginfo('template_url') . '/css/default.css') {
    //CUSTOM Matteo: non serve: è incluso in alto da Porto;
    //questo simpatico plugin lo include in fondo al body così sovrascrive tutto...
    //add_action('wp_head', 'include_bootstrap_css_in_frontend');
}

function include_bootstrap_css_in_frontend()
{
    $bootstrap_css_path = get_option('bootstrap_css_path');
    if ($bootstrap_css_path == 'custom') {
        $bootstrap_css_path = get_option('custom_bootstrap_css_path');
    }
    wp_register_style('TbpBootstrapFrontend', $bootstrap_css_path);
    wp_enqueue_style('TbpBootstrapFrontend');
}

//Register Buttons in TinyMce
function register_tinymce_bootstrap_button($buttons)
{
    // exit('register_tinymce_bootstrap_button');
    array_push($buttons, "|", "bootstrap");

    return $buttons;
}

function add_tinymce_bootstrap_plugin($plugin_array)
{
    // exit('add_tinymce_bootstrap_plugin');
    $plugin_array['tinymce_bootstrap_plugin'] = plugins_url('assets/plugin.min.js', __FILE__);

    return $plugin_array;
}

function add_tinymce_bootstrap_buttons()
{
    if (!current_user_can('edit_posts') && ! current_user_can('edit_pages')) {
        return;
    } elseif (get_user_option('rich_editing') == 'true') {
        add_filter('mce_external_plugins', 'add_tinymce_bootstrap_plugin');
        add_filter('mce_buttons', 'register_tinymce_bootstrap_button');
    }

}
add_action('init', 'add_tinymce_bootstrap_buttons');

// activate checked elements in tinyMce, include Bootstrap CSS & Frontend CSS in TinyMCE
add_filter('tiny_mce_before_init', 'set_tinymce_bootstrap_config');
// set_tinymce_bootstrap_config();
function set_tinymce_bootstrap_config($tinyMceConfig)
{
    //Include Bootstrap CSS in TinyMce
    $bootstrap_css_path        = get_option('bootstrap_css_path');
    $use_frontend_css          = get_option('use_frontend_css');
    $tinymce_custom_background = get_option('tinymce_custom_background');
    $type                      = get_option('type');
    $dropdown_text             = get_option('dropdown_text');
    $bootstrap_icon_font       = get_option('bootstrap_icon_font');

    if ($bootstrap_css_path == 'custom') {
        $bootstrap_css_path = get_option('custom_bootstrap_css_path');
    }
    //Include Frontend CSS in TinyMce
    if ($use_frontend_css == 'checked') {
        $theme_dir = get_bloginfo('template_url');
        $frontend_css_files = implode(', ', get_frontend_css_files($theme_dir));
    } else {
        $frontend_css_files = "";
    }
    // set background color for tinyMce
    if ($tinymce_custom_background == 'checked') {
        $tinymce_background_color = get_option('tinymce_background_color');
    } else {
        $tinymce_background_color = '';
    }
    // set Media path
    $bootstrap_media_path = get_option('bootstrap_media_path');

    $tinyMceConfig['bootstrapConfig'] = "{
        'bootstrapElements': {
            'btn': " . get_element_value('btn') . ",
            'icon': " . get_element_value('icon') . ",
            'image': " . get_element_value('image') . ",
            'table': " . get_element_value('table') . ",
            'template': " . get_element_value('template') . ",
            'breadcrumb': " . get_element_value('breadcrumb') . ",
            'pagination': " . get_element_value('pagination') . ",
            'pager': " . get_element_value('pager') . ",
            'label': " . get_element_value('label') . ",
            'badge': " . get_element_value('badge') . ",
            'alert': " . get_element_value('alert') . ",
            'panel': " . get_element_value('panel') . ",
            'snippet': " . get_element_value('snippet') . "
        },
        'bootstrapCssPath': '" . $bootstrap_css_path . "',
        'frontEndCssFiles': '" . $frontend_css_files . "',
        'tinymceBackgroundColor': '" . $tinymce_background_color . "',
        'bootstrapMediaPath': '" . $bootstrap_media_path . "',
        'type': '" . $type . "',
        'dropdownText': '" . $dropdown_text . "',
        'bootstrapIconFont': '" . $bootstrap_icon_font . "'
    }";

    return $tinyMceConfig;
}

function get_element_value($element)
{
    $option_name = 'tbp_element_choice_' . $element;
    $checked = esc_attr(get_option($option_name));
    if ($checked == 'checked') {
        return 'true';
    } else {
        return 'false';
    }
}

function get_frontend_css_files($theme_dir)
{
    global $frontend_css_files;
    $path = parse_url($theme_dir, PHP_URL_PATH);
    $directory = new RecursiveDirectoryIterator(
        $_SERVER['DOCUMENT_ROOT'] . $path,
        RecursiveDirectoryIterator::KEY_AS_FILENAME |
        RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
    );
    $files = new RegexIterator(
        new RecursiveIteratorIterator($directory),
        '#.css$#',
        RegexIterator::MATCH,
        RegexIterator::USE_KEY
    );
    foreach ($files as $file) { // http://wordpress/wp-content/themes/bitter-sweet/\css\bootstrap.min.css
        $find = array($_SERVER['DOCUMENT_ROOT'] . $path, '\\');
        $replace = array($theme_dir, '/');
        $frontend_css_files[] = str_replace($find, $replace, $file->getPathname());
    }
    // var_dump($frontend_css_files);
    return $frontend_css_files;
}
