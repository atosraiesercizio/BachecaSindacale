<?php
/**
 * Plugin Uninstall Procedure
 */

// Make sure that we are uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$option_name = array();
$elements = array('btn', 'icon', 'table', 'template', 'breadcrumb', 'pagination', 'pager', 'label', 'badge', 'alert', 'panel', 'snippet');
for ($i=0; $i < count($elements); $i++) {
    $option_name[] = 'tbp_element_choice_' . $elements[$i];
}
$option_name[] = 'tbp_version';
$option_name[] = 'tinymce_custom_background';
$option_name[] = 'tinymce_background_color';
$option_name[] = 'use_frontend_css';
$option_name[] = 'bootstrap_css_path';
$option_name[] = 'custom_bootstrap_css_path';

if (!is_multisite()) {
    foreach ($option_name as $option_name) {
        delete_option($option_name);
    }
    remove_menu_page('tbp_settings_page');
} else {
    global $wpdb;
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    $original_blog_id = get_current_blog_id();

    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        foreach ($option_name as $option_name) {
            delete_option($option_name);
        }
        remove_menu_page('tbp_settings_page');

        // OR
        // delete_site_option($option_name);
    }

    switch_to_blog($original_blog_id);
}
