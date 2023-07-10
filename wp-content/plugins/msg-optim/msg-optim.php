<?php
/*
Plugin Name: Message/RaiPlace: Ottimizzazioni DB per queries complesse
Description: Custom plugin for RAI
Author: Message SpA
Author URI: http://www.messagegroup.eu/
Version: 1.0
Text Domain: msg-optim
*/
if (!defined('ABSPATH')) exit;

define('MSG_OPTIM_PLUG_PATH', dirname(__FILE__));

include MSG_OPTIM_PLUG_PATH . "/_bulk_temp_actions.php";


add_filter( 'wpv_filter_query', 'msg_optim_hp_filter_query', 101, 3);
function msg_optim_hp_filter_query($query_args, $views_settings, $view_id) {
    $user = wp_get_current_user();
    global $WP_Views;
    $view_shortcode_attributes = $WP_Views->view_shortcode_attributes;
    $viste_home_names = array(
        'hp-news', 'hp-comunicazioni-interne', 'hp-job-posting', 'hp-eventi'
    );
    //$viste_home_names = ['hp-news'];
    $nome_vista = $views_settings['view_slug'];

    if(in_array($nome_vista, $viste_home_names)){ // viste HOME (secondo criterio ordinamento)
        add_filter( 'posts_clauses', '_opt_filter_query', 20, 2 );
    }

    return $query_args;
}

function _opt_filter_query( $clauses, $query_object ){
    global $leader_id, $wpdb;
    remove_filter( 'posts_clauses', '_opt_filter_query', 20 );
    $seltype = $query_object->query['post_type'][0];
	$now = current_time( 'timestamp' );
    $cust_tables = [
        'news' => 'raiposts_news',
        'evento' => 'raiposts_eventi',
        'comunicazione-int' => 'raiposts_comunicazioni',
        'job-postings' => 'raiposts_jobposting'
    ];
    $cust_table = $cust_tables[$seltype];


    $clauses['join'] = " LEFT JOIN $cust_table AS rpp ON (wp_posts.ID = rpp.post_id)";

    $where = " AND wp_posts.post_status = 'publish'";
    $where .= " AND rpp.visibile_in_home_page = 1";
    $where .= " AND wp_posts.post_type = '$seltype'";



    if($seltype == "evento"){ // vista eventi
        $where .= " AND (
            (rpp.data_evento >= $now)
            OR
            (rpp.data_fine_evento >= $now)
        )";
        $clauses['orderby'] = 'rpp.priorita ASC, rpp.data_evento ASC';
    } else { // altre viste
        $where .= " AND (
            (rpp.visibile_dal <= $now AND rpp.al = 0)
            OR
            (rpp.visibile_dal <= $now AND rpp.al >= $now)
        )";
        //$orderPrior = $seltype == "job-postings" ? "" : "rpp.priorita ASC, ";
	    $orderPrior = "rpp.priorita ASC, ";
        $clauses['orderby'] = $orderPrior.'rpp.data_pubblicazione DESC';

    }
    $clauses['where'] = $where;

    return $clauses;
}

/*** INSTALLAZIONE/UPDATE TABELLE - START ***/
define('MSG_OPTIM_DB_VERSION', '1.0');

register_activation_hook( __FILE__, 'msg_optim_install' );
/**
 *
 */
function msg_optim_install() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE raiposts_comunicazioni (
            post_id bigint(20) unsigned NOT NULL,
            visibile_in_home_page tinyint(3) unsigned NOT NULL DEFAULT '0',
            priorita tinyint(3) unsigned NOT NULL DEFAULT '99',
            visibile_dal int(10) unsigned NOT NULL,
            al int(10) unsigned NOT NULL,
            emittente varchar(64) NOT NULL,
            data_pubblicazione int(10) unsigned NOT NULL,
            PRIMARY KEY (post_id),
            KEY visibile_in_home_page (visibile_in_home_page),
            KEY visibile_dal_al (visibile_dal,al),
            KEY priorita (priorita),
            CONSTRAINT raiposts_comunicazioni_ibfk_2 FOREIGN KEY (post_id) REFERENCES wp_posts (ID) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE raiposts_eventi (
            post_id bigint(20) unsigned NOT NULL,
            visibile_in_home_page tinyint(3) unsigned NOT NULL DEFAULT '0',
            priorita tinyint(3) unsigned NOT NULL DEFAULT '99',
            data_evento int(10) unsigned NOT NULL,
            data_fine_evento int(10) unsigned NOT NULL,
            emittente varchar(64) NOT NULL,
            data_pubblicazione int(10) unsigned NOT NULL,
            PRIMARY KEY (post_id),
            KEY visibile_in_home_page (visibile_in_home_page),
            KEY data_evento_data_fine_evento (data_evento,data_fine_evento),
            KEY priorita (priorita),
            CONSTRAINT raiposts_eventi_ibfk_2 FOREIGN KEY (post_id) REFERENCES wp_posts (ID) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE raiposts_jobposting (
            post_id bigint(20) unsigned NOT NULL,
            visibile_in_home_page tinyint(3) unsigned NOT NULL DEFAULT '0',
            priorita tinyint(3) unsigned NOT NULL DEFAULT '99',
            visibile_dal int(10) unsigned NOT NULL,
            al int(10) unsigned NOT NULL,
            emittente varchar(64) NOT NULL,
            data_pubblicazione int(10) unsigned NOT NULL,
            PRIMARY KEY (post_id),
            KEY visibile_in_home_page (visibile_in_home_page),
            KEY visibile_dal_al (visibile_dal,al),
            KEY priorita (priorita),
            CONSTRAINT raiposts_jobposting_ibfk_2 FOREIGN KEY (post_id) REFERENCES wp_posts (ID) ON DELETE CASCADE
        ) $charset_collate;
        
        CREATE TABLE raiposts_news (
            post_id bigint(20) unsigned NOT NULL,
            visibile_in_home_page tinyint(3) unsigned NOT NULL DEFAULT '0',
            priorita tinyint(3) unsigned NOT NULL DEFAULT '99',
            visibile_dal int(10) unsigned NOT NULL,
            al int(10) unsigned NOT NULL,
            emittente varchar(64) NOT NULL,
            data_pubblicazione int(10) unsigned NOT NULL,
            PRIMARY KEY (post_id),
            KEY visibile_in_home_page (visibile_in_home_page),
            KEY visibile_dal_al (visibile_dal,al),
            KEY priorita (priorita),
            CONSTRAINT raiposts_news_ibfk_4 FOREIGN KEY (post_id) REFERENCES wp_posts (ID) ON DELETE CASCADE
        ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $update = dbDelta( $sql );
    update_option( 'msg_optim_db_version', MSG_OPTIM_DB_VERSION );
}

add_action( 'plugins_loaded', 'msg_optim_db_check' );
function msg_optim_db_check(){
	if ( get_option( 'msg_optim_db_version' ) != MSG_OPTIM_DB_VERSION ) {
		//msg_optim_install();
		//...deprecato
	}
}
/*** INSTALLAZIONE TABELLE - END ***/

/** controllo e update vecchi post */
add_action( 'activated_plugin', 'msg_optim_activation_redirect' );
/**
 * hook per 'activated_plugin', fa un redirect sulla "pagina" REST tmp_rai_rest/update_old_posts, dove aggiorna tutti i vecchi post;
 * v. action rest_api_init -> rest_action_update_old_posts() in _bulk_temp_actions.php
 * @param $plugin
 */
function msg_optim_activation_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect('/wp-json/tmp_rai_rest/update_old_posts') );
    }
}

/*** AZIONE al salvataggio (insert/update) ***/
add_action('save_post','rai_save_post', 10, 3);
function rai_save_post($post_id, $post, $is_update){
    if (wp_is_post_revision( $post_id )){
        return;
    }
    $ctype = $post->post_type;
    $types_tables = [
        'evento' => 'raiposts_eventi',
        'news' => 'raiposts_news',
        'comunicazione-int' => 'raiposts_comunicazioni',
        'job-postings' => 'raiposts_jobposting',
    ];
    $post_from_full_save = isset($_POST['_wptoolset_checkbox']['wpcf-visibile-in-home-page']); // per escludere salvataggi veloci, castino, etc.
    $ok_type = array_key_exists($ctype, $types_tables);
    if( $ok_type && $post_from_full_save ){
        global $wpdb;
        $ctable = $types_tables[$ctype];
        if($ctype == 'evento'){
            $col_date_start = 'data_evento';
            $col_date_end = 'data_fine_evento';
            $val_date_start = $_POST['wpcf']['data-evento']['datepicker'];//$_POST['wpcf-data-evento'];
            $val_date_end = $_POST['wpcf']['data-fine-evento']['datepicker'];//$_POST['wpcf-data-fine-evento'];
        } else {

            $col_date_start = 'visibile_dal';
            $col_date_end = 'al';
            $val_date_start = $_POST['wpcf']['visibile-dal']['datepicker'];//$_POST['wpcf-visibile-dal'];
            $val_date_end = $_POST['wpcf']['al']['datepicker'];//$_POST['wpcf-al'];
        }
        $val_visibile = !empty($_POST['wpcf']['visibile-in-home-page']) ? 1 : 0;
        $val_priorita = $_POST['wpcf']['priorita'];
        $val_emittente = $_POST['wpcf']['emittente'];
        $val_pubblicazione = $_POST['wpcf']['data-pubblicazione']['datepicker'];
        if($is_update || 1){
            /* per qualche ragione WP passa TRUE per il terzo parametro anche durante l'inserimento di un nuovo POST,
             * quindi metto il controllo di esistenza dentro questo blocco, facendo un check diretto sul DB
             */
            $sql = "SELECT COUNT(*) AS EXST FROM $ctable WHERE post_id = '$post_id'";
            $riga = $wpdb->get_row($sql);
            $esiste = (bool)$riga->EXST;
            if($esiste){
                $wpdb->update(
                    $ctable,
                    [
                        'visibile_in_home_page' => $val_visibile,
                        'priorita' => $val_priorita,
                        'emittente' => $val_emittente,
                        'data_pubblicazione' => $val_pubblicazione,
                        $col_date_start => $val_date_start,
                        $col_date_end => $val_date_end
                    ],
                    [ 'post_id' => $post_id ]
                );
            } else {
                $wpdb->insert(
                    $ctable,
                    [
                        'post_id' => $post_id,
                        'visibile_in_home_page' => $val_visibile,
                        'priorita' => $val_priorita,
                        'emittente' => $val_emittente,
                        'data_pubblicazione' => $val_pubblicazione,
                        $col_date_start => $val_date_start,
                        $col_date_end => $val_date_end
                    ]
                );
            }

        } /*else { // qui non si entra più, v. sopra
            $wpdb->update(
                $ctable,
                [
                    'visibile_in_home_page' => $val_visibile,
                    'priorita' => $val_priorita,
                    'emittente' => $val_emittente,
                    'data_pubblicazione' => $val_pubblicazione,
                    $col_date_start => $val_date_start,
                    $col_date_end => $val_date_end
                ],
                [ 'post_id' => $post_id ]
            );
        }*/
    }
    return;
}