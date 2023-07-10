<?php
/*
Plugin Name: Message/RaiPlace: Tools vari
Description: Custom plugin for RAI
Author: Message SpA
Author URI: http://www.messagegroup.eu/
Version: 1.0
Text Domain: msg-tools
*/
if (!defined('ABSPATH')) exit;

// per il servizio ajax standalone
session_start();

define('MSG_TOOLS_PLUG_PATH', dirname(__FILE__));
define('MSG_TOOLS_PLUG_URL', plugin_dir_url(__FILE__));
define('MSG_TOOLS_ASSET_VERS', '1.0');

/*** customizzazioni VISUAL COMPOSER ***/
if (function_exists('vc_add_param')) {
    include MSG_TOOLS_PLUG_PATH . "/msg-visualcomposer.php";
}

add_action('init', 'msg_tools_init');
function msg_tools_init(){
    global $wp_rewrite;

    $user = wp_get_current_user();
    if(!empty($user->ID)){
	    $_SESSION['rai_user_matricola'] = $user->user_login;
    } else {
	    $_SESSION['rai_user_matricola'] = '';
    }
    // prefisso slug per le pagine
    $ppre = array("it" => "pagine", "en" => "pages");
    $wp_rewrite->page_structure = $ppre['it'] . '/%pagename%';
    $wp_rewrite->page_structure = $ppre['it'] . '/%pagename%';
    //flush_rewrite_rules();

    /*** People Search ***/
    include MSG_TOOLS_PLUG_PATH . "/msg-people.php";

    /*** ZIP Basket (zippa allegati multipli) ***/
    include MSG_TOOLS_PLUG_PATH . "/msg-zipbasket.php";

    /*** Views (e Types) ***/
    include MSG_TOOLS_PLUG_PATH . "/msg-views.php";

    /*** Shortcodes (generici) ***/
    include MSG_TOOLS_PLUG_PATH . "/msg-shortcodes.php";

    /*** ESS (Rai ESS WS proxy e renderizzatore per Home Page) ***/
    include MSG_TOOLS_PLUG_PATH . "/msg-ess.php";



}

//Aggiungo i js e css ad hoc
add_action('wp_enqueue_scripts', 'msg_tools_scripts');
function msg_tools_scripts(){
    wp_enqueue_script('msg_tools_script', MSG_TOOLS_PLUG_URL . '/js/msg_tools.js', array('jquery'), MSG_TOOLS_ASSET_VERS, true);
    wp_localize_script('msg_tools_script', 'msg_tools', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
	    'ajaxurlalt' => '/wp-content/plugins/msg-tools/rai_ajax_bridge.php'
    ));
}

/*** ADMIN/SETTINGS ***/

add_action('admin_menu', 'msgtools_admin_menu');
function msgtools_admin_menu()
{
    add_options_page('Message Tools', 'RaiPlace Tools: parametri', 'manage_options', 'msgtools_settings', 'msgtools_admin_page');
}

function msgtools_admin_page()
{
    include(MSG_TOOLS_PLUG_PATH . '/msg-tools-settings.php');
}


function msg_getActiveMenusByCT($type, &$menu_args){
    //$rows = get_option('msgtools_types_menus');
    $opt = get_option("msgtools_types_menus", "");
    $opt = str_replace(array("\r\n", "\r", "\n"), "|", $opt);
    $rows = explode("|", $opt);
    foreach ($rows as $row) {
        $vals = explode(':', $row);
        if (!empty($vals[0]) && $vals[0] == $type) {
            if (!empty($vals[1])) {
                $menu_args['root_id'] = intval($vals[1]);
            }
            if (!empty($vals[2])) {
                $menu_args['current_id'] = intval($vals[2]);
            }
            break;
        }
    }

}

/********** MENU                    **********/
add_filter('wp_nav_menu_objects', 'raiplace_nav_menu_objects_sub_menu', 10, 2);
function raiplace_nav_menu_objects_sub_menu($sorted_menu_items, $args)
{
    if (isset($args->sub_menu)) {
        $root_id = 0;
        if (isset($args->root_id)) {
            $root_id = intval($args->root_id);
        } else {
            foreach ($sorted_menu_items as $menu_item) {
                if ($menu_item->current) {
                    $root_id = ($menu_item->menu_item_parent) ? $menu_item->menu_item_parent : $menu_item->ID;
                    break;
                }
            }
        }

        if (!isset($args->direct_parent)) {
            $prev_root_id = $root_id;
            while ($prev_root_id != 0) {
                foreach ($sorted_menu_items as $menu_item) {
                    if ($menu_item->ID == $prev_root_id) {
                        $prev_root_id = $menu_item->menu_item_parent;
                        if ($prev_root_id != 0) $root_id = $menu_item->menu_item_parent;
                        break;
                    }
                }
            }
        }
        $menu_item_parents = array();
        foreach ($sorted_menu_items as $key => $item) {
            if ($item->nolink) {
                $item->url = '';
            }
            if ($item->ID == $root_id) $menu_item_parents[] = $item->ID;
            if (in_array($item->menu_item_parent, $menu_item_parents)) {
                $menu_item_parents[] = $item->ID;
            } else if (!(isset($args->show_parent) && in_array($item->ID, $menu_item_parents))) {
                unset($sorted_menu_items[$key]);
            }
        }
        return $sorted_menu_items;
    } else {
        return $sorted_menu_items;
    }
}

add_filter('nav_menu_css_class', 'raiplace_nav_menu_sub_menu_class', 10, 3);
function raiplace_nav_menu_sub_menu_class($classes, $item, $args = array())
{
    $arr = (array)$args;
    if(empty($arr)){
        $args = new stdClass();
    }
    if ($item->nolink) {
        $classes[] = 'nolink';
    }
    if (($item->current || $item->current_item_ancestor) && in_array('menu-item-has-children', $classes)) {
        $classes[] = 'opened';
    }
    if (isset($args->current_id) && $args->current_id == $item->ID) {
        $classes[] = 'current-menu-item special-active';
    }
    if (isset($args->root_id) && $args->root_id == $item->ID) {
        $classes[] = 'active';
    }

    return ($classes);
}

/* funzioni views */
/* views filter
 * risolve il problema degli shortcode esterni non processati
 * (di Visual Composer, nella fattispecie)
 * durante le richieste AJAX
 */
add_filter('wpv_filter_post_excerpt', 'rai_fix_excerpt_shortcodes');
function rai_fix_excerpt_shortcodes($excerpt)
{
    if (class_exists('WPBMap') && method_exists('WPBMap', 'addAllMappedShortcodes')) {
        WPBMap::addAllMappedShortcodes();
    }
    return $excerpt;
}


add_action('admin_enqueue_scripts', 'msg_tools_admin_scripts');
function msg_tools_admin_scripts()
{
    wp_enqueue_script('msg_tools_admin_script', MSG_TOOLS_PLUG_URL . 'js/msg_tools_admin.js', array('jquery'), MSG_TOOLS_ASSET_VERS, true);
    wp_enqueue_style('msg_tools_admin_css', MSG_TOOLS_PLUG_URL . 'css/msg_tools_admin.css', array(), MSG_TOOLS_ASSET_VERS);
}

/*** AVATAR ***/
/*** per prendere l'immagine da un server di RAI ***/
function msg_user_img($matrnum)
{
    $matrnum = substr($matrnum, 1);
    if (substr($_SERVER['HTTP_HOST'], -7) == '.rai.it') { //solo su rai (l'immagine è un "src" client side, non ho modo di controllare in altro modo)
        if (!is_numeric($matrnum)) { // utenti NON pXXXXX (p.e.: elot2b1) non hanno la foto
            $matrnum = '000000';
        }
        // il "calcolo" di ck (un codice di controllo) è stato stabilito da RAI
        $ck = $matrnum . date('Ynj');
        $ck = array_sum(str_split($ck));
        $ck = str_pad($ck, 3, "0", STR_PAD_LEFT);
        //@TODO: da configurazione...?

        //$av_url = 'hrapp.servizi.rai.it';
        $av_url = get_option('msgtools_avatar_url', 'http://hrapp.servizi.rai.it/viewFace/view.aspx');
        $av_w = get_option('msgtools_avatar_w', '220');
        $av_h = get_option('msgtools_avatar_h', '220');
        /*if (defined('RAI_AVATAR_DOMAIN')) {// deprecato
            $av_domain = RAI_AVATAR_DOMAIN;
        }*/
        //sqr=1&cre=1 fissi, passati da RAI il 2018/03
        $img_avat = '<img src="' . $av_url . '?sqr=1&cre=1&m=' . $matrnum . '&w=' . $av_w . '&h=' . $av_h . '&face=1&ck=' . $ck . '" class="rai-avatar">';
    } else {
        $img_avat = '<img src="/wp-content/themes/porto-child/images/avatar.png" class="rai-avatar">';
    }
    return $img_avat;
}

add_filter('get_avatar', 'msg_custom_avatar', 1, 5);

function msg_custom_avatar($avatar, $id_or_email, $size, $default, $alt){
    $user = false;
    if (is_numeric($id_or_email)) {
        $id = (int)$id_or_email;
        $user = get_user_by('id', $id);
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $id = (int)$id_or_email->user_id;
            $user = get_user_by('id', $id);
        }
    } else {
        $user = get_user_by('email', $id_or_email);
    }
    if($user && is_object($user)) {
        $avatar = msg_user_img($user->user_login);
    }
    return $avatar;
}

/*** Web Service REST per inserire scelta tema ***/
function msg_rest_usertheme(WP_REST_Request $request){
    $params = $request->get_params();
    $matr = !empty($params['matricola']) ? preg_replace("/[^a-zA-Z0-9]+/", "", $params['matricola']) : '';
    $tema = filter_var($params['tema'], FILTER_VALIDATE_INT);
    $tema = $tema > 0 && $tema < 5 ? $tema : false;
    $user = get_user_by('login', $matr);
    $chiave = $request->get_header('keystring');
    $chiave_ok = "59881203878321920";
    if ($chiave != $chiave_ok) {
        return new WP_Error('key_error', 'Accesso non autorizzato', array('status' => 403));
    } elseif (!$user->ID) {
        return new WP_Error('no_user', 'Utente inesistente', array('status' => 404));
    } elseif (!$tema) {
        return new WP_Error('no_theme', 'Tema non valido', array('status' => 404));
    } else {
        update_user_meta($user->ID, 'rai_variazione_tema', $tema);
        //var_dump($request);
        return ["esito" => "ok"];
    }
}

add_action('rest_api_init', function () {
    register_rest_route('raiplace_rest', '/user_theme', array(
        'methods' => 'POST',
        'callback' => 'msg_rest_usertheme',
    ));
    register_rest_route('raiplace_rest', '/user_theme/(?P<matricola>[a-zA-Z0-9]+)/(?P<tema>\d+)', array(
        'methods' => 'POST',
        'callback' => 'msg_rest_usertheme',
    ));
});
