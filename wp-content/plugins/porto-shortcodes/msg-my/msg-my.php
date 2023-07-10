<?php
/*
Plugin Name: Message/Raiplace: MY links
Description: Custom plugin for RAI
Author: Message SpA
Author URI: http://www.messagegroup.eu/
Version: 1.0
Text Domain: msg-my
*/

if ( !defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'msg_my_init' );

function msg_my_init(){
	define('MMY_PLUGURL', plugin_dir_url(__FILE__));
	define('MMY_PLUGPATH', plugin_dir_path(__FILE__));

	define('MMY_CURR_USER_ID', get_current_user_id());
	define('MMY_KEY_APPS', 'msg_my_apps');
	define('MMY_KEY_LINKS', 'msg_my_links');
	define('MMY_APPICON_URL', MMY_PLUGURL . 'img/app-logo-default.png');

	define('MMY_NO_APPS_TXT', 'Non hai Servizi e utilità');
	define('MMY_NO_LINKS_TXT', 'Non hai Preferiti');
	define('MMY_TXT_ADD', 'Aggiungi ai preferiti');
	define('MMY_TXT_RMV', 'Rimuovi dai preferiti');

	if(!defined('MMY_PAGE_SLUG_APPS')) {
		define('MMY_PAGE_SLUG_APPS', 'my_apps_lista');
		define('MMY_PAGE_SLUG_LINKS', 'my_links_lista');
	}
}

//if(MMY_CURR_USER_ID != 0){ // non fare nulla per gli anonimi
add_action('wp_enqueue_scripts', 'msg_my_registerjscss');
function msg_my_registerjscss() {
	wp_register_script( 'msg-my', MMY_PLUGURL.'js/msg-my.js', array('jquery'), '1.0', false );
	wp_localize_script( 'msg-my', 'msg_my_vals', array(
		'nolinks' => __(MMY_NO_LINKS_TXT, 'msgtools'),
		'noapps' => __(MMY_NO_APPS_TXT, 'msgtools'),
		'txt_add' => MMY_TXT_ADD,
		'txt_rmv' => MMY_TXT_RMV,
		'ajaxurl' => admin_url( 'admin-ajax.php' )
	));
	wp_enqueue_script( 'msg-my');
}

/*** AJAX ***/
//mmy_hidden_sidebar
add_action( 'wp_ajax_nopriv_my_sidebar_list', 'msg_my_sidebar_list' );
add_action( 'wp_ajax_my_sidebar_list', 'msg_my_sidebar_list' );
function msg_my_sidebar_list(){
	$response = array(
		'esito' => 'ko'
	);
	$lista = mmy_hidden_sidebar(false);
	$hp_block_app = msg_my_sc_myLinksHome(['tipo' => 'app']);
	$hp_block_lnk = msg_my_sc_myLinksHome(['tipo' => 'link']);
	if($lista){
		$response = array(
			'esito' => 'ok',
			'html' => $lista,
			'hp_link' => $hp_block_lnk,
			'hp_app' => $hp_block_app
		);
	}
	wp_send_json($response);
}

add_action( 'wp_ajax_my_save', 'msg_my_ajax_save' );
add_action( 'wp_ajax_nopriv_my_save', 'msg_my_ajax_save' );
/**
 * action che crea una "route" AJAX (wp_ajax_[action] e wp_ajax_nopriv_[action]
 * per inserimento e cancellazione preferiti sui My dell'utente
 */
function msg_my_ajax_save(){
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$response = ['esito' => 'ko'];
	if($id){
		$key = $_POST['tipo'] == "app" ? MMY_KEY_APPS : MMY_KEY_LINKS;
		$all_links = get_user_meta(MMY_CURR_USER_ID, $key, true);
		if(!$all_links || !$all_links[0]){
			$all_links = array();
		}
		if($_POST['sub_action'] == 'add'){		//add
			if(!in_array_r($id, $all_links)){
				$new_link = array(
					'id' => $id,
					'inserito' => time()
				);
				$all_links[] = $new_link;

			}
		} else { 								// remove
			if(in_array_r($id, $all_links)){
				foreach($all_links as $k => $link){
					if($link['id'] == $id){
						array_splice($all_links, $k, 1);
						break;
					}
				}

			}
		}

		update_user_meta(MMY_CURR_USER_ID, $key, $all_links);
		$response = $response = ['esito' => 'ok'];
	}
	wp_send_json($response);
}

/*
 * MY LINKS
 * (in RaiPlace 3 le "Apps" sono solo links con una certa categoria)
 *
 * */

add_shortcode('my_add', 'msg_my_addbtn');
/**
 * Shortcode per il bottone di aggiunta ai "My"
 * @param $atts  (attributi shortcode: tipo = link|app; id = post id (facoltativo); nascondi_testo = bool (bottone son o senza label))
 * @return string
 */
function msg_my_addbtn($atts){
	$opt = shortcode_atts( array(
		'tipo' => '',
		'id' => 0,
		'nascondi_testo' => false
	), $atts );

	$id = intval($opt['id']);
	$tipo = $opt['tipo'];
	$nascondi_testo = (bool)$opt['nascondi_testo'];
	if(!$id && (is_page(MMY_PAGE_SLUG_LINKS) || is_page(MMY_PAGE_SLUG_APPS))){
		return '';
	}
	if(!$id){
		global $post;
		$id = $post->ID;
		$tipo = $post->post_type == "app" ? "app" : "link";
	}
	if(!$tipo){
		$tipo = get_post_type($id);
	}
	if($id){
		$tipo = $tipo == 'app' ? 'app' : 'link'; //restringo il tipo: app|link
		$tipokey = $tipo == 'app' ? MMY_KEY_APPS  : MMY_KEY_LINKS;
		$curr_my = get_user_meta(MMY_CURR_USER_ID, $tipokey, true);
		$class_remove = '';
		$azione = 'add';
		$title = MMY_TXT_ADD;
		if(in_array_r($id, $curr_my)){
			$class_remove = ' ico-bookmark_full';
			$azione = 'remove';
			$title = MMY_TXT_RMV;
		}
		return '<a class="bookmark-toggle'.($nascondi_testo ? ' no-text' : '').'" data-tipo="'.$tipo.'" data-azione="'.$azione.'" data-id="'.$id.'" title="'.$title.'"><span class="ico-bookmark'.$class_remove.'"><em>'.($nascondi_testo ? '' : $title).'</em></span></a>';
	} else {
		return '';
	}

}


add_shortcode('my_links_home', 'msg_my_sc_myLinksHome');
/**
 * Shortcode per fasce My Links e My Apps in Home
 * @param $atts (attributo shortcode: tipo = link|app)
 * @return string
 */
function msg_my_sc_myLinksHome($atts){
	$hp = $add_btn = ''; //solo per l'IDE, che si incazza per le variabili create da extract
	$opts = shortcode_atts( array(
		'tipo' => 'link'
	), $atts );
	$tipo = $opts['tipo'] == 'app' ? 'app' : 'link';
	$links = _get_my_links($tipo);
	$out = '<div class="row mmy-hp-list '.$tipo.'">';
	for($i=0; $i<4; $i++){
		$out .= '<div class="col-xs-12 col-sm-6 col-md-3">';
		if(isset($links[$i])){
			$img = $ico_link = '';
			$tag = $links[$i]['type'];
			$target = '';
			if($tipo == 'app'){
				$img = wp_get_attachment_image_url( get_post_thumbnail_id($links[$i]['id'], 'thumbnail' ));
				$terms = wp_get_post_terms( $links[$i]['id'], 'tipo-di-app');
				$target = get_post_meta($links[$i]['id'], 'wpcf-app-target-internal', true) ? '' : ' target="_blank"';
				if(!empty($terms)){
					$tag = $terms[0]->name;
				}

			}
			if($img){
				$img = '<div class="app-icon"><img src="'.$img.'"></div>';
			} else {
				$ico_link = '<a href="'.$links[$i]['url'].'" class="link-ico">
					<span class="ico-link"></span>
				</a>';
			};
			$out .= '
			<div class="item">
				'.$img.$ico_link.'
				<div class="desc">
					'.($tipo == 'link' ? '' : '<div class="tag small">'.$tag.'</div>').'
					<a href="'.$links[$i]['url'].'"'.$target.'>'.$links[$i]['title'].'</a>
					'.($tipo == 'app' ? '' : '<div class="tag small">'.$tag.'</div>').'
					'.do_shortcode('[my_add id="'.$links[$i]['id'].'" tipo="'.$tipo.'"]').'
				</div>
			</div>';
		} else {
			$tooltip_cont = 'Per aggiungere un preferito puoi cliccare sull\'icona del segnalibro (<span class=\'ico-bookmark\'></span>) della pagina di Rai Place che ti interessa maggiormente.';
			if($tipo == 'app'){
				$tooltip_cont = 'Per aggiungere un preferito puoi cliccare sull\'icona del segnalibro (<span class=\'ico-bookmark\'></span>) di ogni servizio e utilità che utilizzi più frequentemente.';
			}
			$out .= '<div class="item no-cont"><div class="no-content"><a href="#" data-toggle="popover" title="Aggiungi preferiti" data-html="true" data-content="'.$tooltip_cont.'" data-placement="top" data-trigger="hover">+</a></div></div>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * ritorna i due tipi di my links come array di array
 * @param string $tipo
 * @return array
 */
function _get_my_links($tipo = 'app'){
	$tipokey = $tipo == 'app' ? MMY_KEY_APPS : MMY_KEY_LINKS;
	$myall = (array)get_user_meta(MMY_CURR_USER_ID, $tipokey, true);

	$links = array();
	$myall = array_reverse($myall);
	foreach($myall as $k => $link){
		$links[$k] = array();
		$id = $link['id'];
		$links[$k]['id'] = $id;
		$links[$k]['title'] = get_the_title($id);
		$links[$k]['url']  = get_page_link($id);
		$type = get_post_type($id);
		$type = get_post_type_object($type);
		$links[$k]['type'] =  $type->labels->singular_name;
	}
	return ($links);
}
/****** PAGINE CON LISTE LINKS/APPS **********/
/* attivazione: creazione pagine (se non esistono) */
function msg_my_activation() {
	if(!defined('MMY_PAGE_SLUG_APPS')){
		define('MMY_PAGE_SLUG_APPS', 'my_apps_lista');
		define('MMY_PAGE_SLUG_LINKS', 'my_links_lista');
	}

	$wpdb = $GLOBALS['wpdb'];
	$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '%s' AND post_type = 'page' ", MMY_PAGE_SLUG_APPS) );
	if (empty($id)) {
		wp_insert_post (array(
			'post_type' =>'page',
			'post_title' => 'I miei Servizi e utlità',
			'post_name' => MMY_PAGE_SLUG_APPS,
			'post_status' => 'publish'
		));
	}

	$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '%s' AND post_type = 'page' ", MMY_PAGE_SLUG_LINKS) );
	if (empty($id)) {
		wp_insert_post (array(
			'post_type' =>'page',
			'post_title' => 'I miei Preferiti',
			'post_name' => MMY_PAGE_SLUG_LINKS,
			'post_status' => 'publish'
		));
	}
}
register_activation_hook(__FILE__, 'msg_my_activation');


add_filter( 'body_class', 'msg_my_custom_class' );
/**
 * aggiunge una classe nelle due pagine con le liste
 * @param $classes
 * @return array
 */
function msg_my_custom_class( $classes ) {
	if (is_page(MMY_PAGE_SLUG_LINKS) || is_page(MMY_PAGE_SLUG_APPS)) {
		$classes[] = 'mmy_links_page';
	}
	return $classes;
}

add_filter('the_content', 'msg_my_content_list' );
/**
 * intercetta le URL delle 2 pagine e mostra nel $content solo quanto generato qui da php
 * @param $content
 * @return string
 */
function msg_my_content_list( $content ) {
	if (is_page(MMY_PAGE_SLUG_LINKS) || is_page(MMY_PAGE_SLUG_APPS)) {
		global $post;
		$tipo = is_page(MMY_PAGE_SLUG_APPS) ? 'app' : 'link';
		$links = _get_my_links($tipo);
		$content = '<div id="my_list"><div class="vc_row row_large_padd a2elenco">';
		if(empty($links)){
			$content .= '<div>Non hai nessun preferito di tipo <em>'.$tipo.'</em>.</div>';
		}
		foreach ($links as $k => $link){
			$id = $link['id'];
			$toggle = do_shortcode('[my_add id="'.$id.'" tipo="'.$tipo.'"]');
			$content .= '<div class="item">'.$toggle.'<div  class="titolo"><a href="'.$link['url'].'">'.$link['title'].'</a></div><div class="tag">'.$link['type'].'</div></div>';
		}
		$content .= '</div></div>';
	}
	return $content;
}


/**
 * ritorna l'html della SIDEBAR a scomparsa (legata all'icona nella barra dell'header)
 * @param bool $wrapper
 * @param bool $raw
 * @return array|string
 */
function mmy_hidden_sidebar($wrapper = true, $raw = false){
	$content = '';
	if($wrapper) { $content .= '<div id="mmy_hidden_sidebar">'; }
	$tipi = array('link' => 'I miei Preferiti', 'app' => 'I miei Servizi e utilità');
	$tipiNomi = array('link' => 'Preferiti', 'app' => 'Servizi e utilità');
	$items = [];
	foreach($tipi as $tipo => $tipoTit){
		$links = _get_my_links($tipo);
		$items[$tipo] = $links;
		$content .= '<div class="my_mini_list '.$tipo.'"><div class="title">'.$tipoTit.'</div><div class="lista">';
		$link_more = '';

		foreach($links as $k => $link){
			$content .= '<div><a href="'.$link['url'].'">'.$link['title'].'</a></div>';
			if($k == 3){
				break;
			}
		}
		if(count($links)>4){
			$urlmore = $tipo == 'app' ? mmy_apps_page_url() : mmy_links_page_url();
			$link_more = '<div class="more"><a href="'.$urlmore.'" class="ti-plus">Vedi tutto</a></div>';
		}
		if(!count($links)){
			$content .= '<div class="no-result">Non hai '.$tipiNomi[$tipo].'</div>';
		}
		$content .= $link_more;
		$content .= '</div></div>';
	}
	if($wrapper) { $content .= '</div>'; }
	if($raw){
		return $items;
	} else {
		return $content;
	}

}

/* per ottenere info sulle URL globalmente, anche da altri moduli o dal tema */
function mmy_apps_page_url(){
	return get_permalink( get_id_by_slug(MMY_PAGE_SLUG_APPS));
}
function mmy_links_page_url(){
	return get_permalink( get_id_by_slug(MMY_PAGE_SLUG_LINKS));
}

/* TOOLS */
// in_array multidimensionale
if(!function_exists('in_array_r')){
	function in_array_r($needle, $haystack, $strict = false) {
		if(is_array($haystack)) {
			foreach ($haystack as $item) {
				if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
					return true;
				}
			}
		}
		return false;
	}
}
if(!function_exists('get_id_by_slug')) {
	function get_id_by_slug($page_slug, $post_type = 'page')
	{
		global $wpdb;
		$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s AND post_status = 'publish'", $page_slug, $post_type));
		if ($id) {
			return $id;
		} else {
			return null;
		}

	}
}
if(!function_exists('var_dumpa')){
	function var_dumpa($v){
		print '<pre>';
		var_dump($v);
		print('</pre>');
	}
}
