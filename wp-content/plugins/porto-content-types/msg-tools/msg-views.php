<?php
add_shortcode('vista_a2', 'msg_view_vistaA2');

function msg_view_vistaA2($atts){
    //[wpv-view name="a2-pagina-archivio" cached="off" content_type="news" tax_name="tipo-di-news"]
    $cached = 'on'; // per l'uso con l'ide che non becca la variabile generata da shortcode_atts()
    $cachedstring = '';
    $atts = shortcode_atts(array(
        'name' => 'a2-pagina-foto',
        'content_type' => 'news',
        'tax_name' => "tipo-di-news",
        'cached' => "on",
    ), $atts);
    if($cached == "off"){
        $cachedstring = 'cached = "off"';
    }
    if(isset($_GET['archivio'])){
        $nome_vista_archivio = $atts['content_type'] == 'evento' ? 'a2-pagina-archivio-eventi' : 'a2-pagina-archivio';
        return do_shortcode('[wpv-view name="'.$nome_vista_archivio.'" '.$cachedstring.' content_type="'.$atts['content_type'].'" tax_name="'.$atts['tax_name'].'"]');
    } else {
        if($atts['content_type'] == 'evento' && $atts['name'] == 'a2-pagina-foto'){
            $nome_vista = 'a2-pagina-foto-eventi';
        } elseif($atts['content_type'] == 'job-postings') {
            $nome_vista = 'a2-pagina-no-foto-job-postings';
        } else {
            $nome_vista = $atts['name'];
        }
        $vshortcode = '[wpv-view name="'.$nome_vista.'" '.$cachedstring.' content_type="'.$atts['content_type'].'" tax_name="'.$atts['tax_name'].'"]';
        return do_shortcode($vshortcode);
    }
}
add_filter( 'wpv_filter_query', 'msg_views_filter_query', 101, 3);
function msg_views_filter_query($query_args, $views_settings, $view_id) {
    $user = wp_get_current_user();
    global $WP_Views;
    $view_shortcode_attributes = $WP_Views->view_shortcode_attributes;
    $viste_a2_names = array(
        'a2-pagina', 'a2-pagina-archivio', 'a2-pagina-archivio-eventi',
        'a2-pagina-foto', 'a2-pagina-no-foto', 'a2-pagina-foto-eventi', 'a2-pagina-no-foto-job-postings'
        //'a3-blocco-foto', 'a3-blocco-no-foto'
    );
    $nome_vista = $views_settings['view_slug'];
    if(in_array($nome_vista, $viste_a2_names)){ // viste A2 (ordinamento dinamico, basato sui filtri
        add_filter( 'posts_clauses', '_a2_filter_query', 20, 2 );
        $tipo = isset($view_shortcode_attributes[0]['content_type']) ? $view_shortcode_attributes[0]['content_type'] : 'news';

        if(!empty($_POST['search']['dps_general']) || !empty($_GET['sortby'])){
            $sortfield = '';
            if(!empty($_POST['search']['dps_general'])){
                foreach($_POST['search']['dps_general'] as $field){
                    if($field['name'] == 'sortby'){
                        if($field['value'] == 'wpcf-data-pubblicazione' && $tipo == 'evento'){
                            $sortfield = 'wpcf-data-evento';
                        } else {
                            $sortfield = $field['value'];
                        }
                        break;
                    }
                }

            } else {
                $sortfield = $_GET['sortby'];
            }

            if($sortfield){
                $query_args["meta_key"] = $sortfield;
                //$query_args["order"] = 'DESC';
                if($sortfield == 'views'){
                    foreach($query_args["meta_query"] as $i=>$mq_el){
                        if(is_array($mq_el) && key_exists('key', $mq_el)){

                        }
                    }
                    //$query_args["meta_query"] = array();
                    $query_args["meta_query"]['relation'] = 'AND';
                    $query_args["meta_query"]['data_pubb'] = array(
                        'key' => $tipo == 'evento' ? 'wpcf-data-evento' : 'wpcf-data-pubblicazione',
                        'compare' => 'EXISTS',
                        'type' => 'NUMERIC'
                    );
                    $query_args["meta_query"]['num_views'] = array(
                        'key' => 'views',
                        'compare' => 'EXISTS',
                        'type' => 'NUMERIC'

                    );
                    $query_args["orderby"] = array();
                    $query_args["orderby"]['num_views'] = "DESC";
                    //$query_args["orderby"]['data_pubb'] = $tipo == 'evento' ? "ASC" : "DESC";
	                $query_args["orderby"]['data_pubb'] = "DESC";
                    unset($query_args["meta_key"]);
                    unset($query_args["order"]);
                } else {
                    $query_args["order"] = $tipo == 'evento' ? "ASC" : "DESC";
                }
            }

        } else {
	        $query_args["meta_query"]['data_pubb'] = array(
		        'key' => $tipo == 'evento' ? 'wpcf-data-evento' : 'wpcf-data-pubblicazione',
		        'compare' => 'EXISTS',
		        'type' => 'NUMERIC'
	        );
            $query_args["orderby"] = 'data_pubb';
            $query_args["order"] = ($tipo == 'evento' && $nome_vista != 'a2-pagina-archivio-eventi') ? 'ASC' : 'DESC';

	        //$query_args["order"] = 'DESC';
        }
        //$query_args['meta_type']
    } elseif( $view_id == 0){ // tramite view id; deprecato 


    }
    // VISTE A3 (blocchi)
	$viste_a3_names = array(
		'a3-blocco-no-foto', 'a3-blocco-foto'
	);
	if(in_array($nome_vista, $viste_a3_names)) { // viste A3 (ordinamento dinamico)
		add_filter('posts_clauses', '_a3_filter_query', 20, 2);
	}

    //forzo il tipo di contenuto se fissato da attributo
    if(isset($view_shortcode_attributes[0]['content_type'])){
        $query_args['post_type'] = array($view_shortcode_attributes[0]['content_type']);
    }
    return $query_args;
}


/**
 * chiamata dal filtro 'wpv_filter_query' (tramite filtro 'posts_clauses') per le viste A2 (pagine di aggregazione)
 * @param $clauses
 * @param $query_object
 * @return mixed
 */
function _a2_filter_query( $clauses, $query_object ){
    global $leader_id, $wpdb;
	$now = current_time( 'timestamp' );
    remove_filter( 'posts_clauses', '_a2_filter_query', 20 );
    //var_dump($query_object);
    if(!empty($query_object->query_vars['post__not_in'])){
        $clauses['limits'] = "LIMIT 0, 15";
    }
    $seltype = $query_object->query['post_type'][0];

    $cust_tables = [
        'news' => 'raiposts_news',
        'evento' => 'raiposts_eventi',
        'comunicazione-int' => 'raiposts_comunicazioni',
        'job-postings' => 'raiposts_jobposting'
    ];
    $cust_table = $cust_tables[$seltype];
    if(array_key_exists($seltype, $cust_tables)) {
        $clauses['join'] = $clauses['join']." LEFT JOIN $cust_table AS rpp ON (wp_posts.ID = rpp.post_id)";
        $where = $clauses['where']." AND wp_posts.post_status = 'publish'";
        $where .= " AND wp_posts.post_type = '$seltype'";
        if($seltype == "job-postings"){ // eccezione criteri job-postings
	        $where .= " AND ((rpp.visibile_dal = 0 OR rpp.visibile_dal <= $now))";
            $where .= " AND ((rpp.al = 0 OR rpp.al >= $now))";
        } elseif($seltype == "evento" && !isset($_GET['archivio'])){
	        $where .= " AND (
	            (rpp.data_evento >= $now)
	            OR
	            (rpp.data_fine_evento >= $now)
	        )";
        }
        //$clauses['orderby'] = "rpp.data_pubblicazione DESC";
        $clauses['where'] = $where;
    }

    //var_dump($clauses);
    return $clauses;

}

/**
 * chiamata dal filtro 'wpv_filter_query'  (tramite filtro 'posts_clauses') per le viste A3 (blocchi)
 * @param $clauses
 * @param $query_object
 * @return mixed
 */
function _a3_filter_query( $clauses, $query_object ){
	global $leader_id, $wpdb;
	$now = current_time( 'timestamp' );
	remove_filter( 'posts_clauses', '_a3_filter_query', 20 );
	$seltype = $query_object->query['post_type'][0];

	$cust_tables = [
		'news' => 'raiposts_news',
		'evento' => 'raiposts_eventi',
		'comunicazione-int' => 'raiposts_comunicazioni',
		'job-postings' => 'raiposts_jobposting'
	];
	$cust_table = $cust_tables[$seltype];
	if($seltype == 'job-postings') {
		$clauses['join'] = $clauses['join']." LEFT JOIN $cust_table AS rpp ON (wp_posts.ID = rpp.post_id)";
		$where = $clauses['where']." AND wp_posts.post_status = 'publish'";
		$where .= " AND wp_posts.post_type = '$seltype'";
		$where .= " AND ((rpp.visibile_dal = 0 OR rpp.visibile_dal <= $now))";
		$where .= " AND ((rpp.al = 0 OR rpp.al >= $now))";
		//$clauses['orderby'] = "rpp.data_pubblicazione DESC";
		$clauses['where'] = $where;
	} elseif($seltype == "evento"){
		$clauses['join'] = $clauses['join']." LEFT JOIN $cust_table AS rpp ON (wp_posts.ID = rpp.post_id)";
		$where = $clauses['where']." AND wp_posts.post_status = 'publish'";
		$where .= " AND wp_posts.post_type = '$seltype'";
		$where .= " AND (rpp.data_evento >= $now OR rpp.data_fine_evento >= $now)";
		$clauses['where'] = $where;
		$clauses['orderby'] = "rpp.data_evento ASC";
	}

	return $clauses;

}


/*** SHORTCODE per Filtri di Ordinamento (A2) ***/
add_shortcode('a2_sort_filters', 'msg_view_a2sorting');
function msg_view_a2sorting($atts){
    $atts = shortcode_atts(array(
        'param' => 'sortby',
    ), $atts);
    $param_name = 'sortby';//$atts['param'];

    $params_vals = array(
        'wpcf-data-pubblicazione' => 'Data',
        'views' => 'Popolarità'
    );
    $out = '';
    foreach($params_vals as $pval => $label){
        $checked = '';
        $classSel = '';
        if(isset($_GET['sortby']) && $_GET['sortby'] == $pval){
            $checked = ' checked="checked"';
            $classSel = ' selected';
        } elseif(!isset($_GET['sortby']) && $pval == 'wpcf-data-pubblicazione') {
            $checked = ' checked="checked"';
            $classSel = ' selected';
        }
        $out .= '<div class="radio'.$classSel.'">
            <label for="'.$param_name.'-'.$pval.'">
                <input id="'.$param_name.'-'.$pval.'" class="js-wpv-filter-trigger" name="'.$param_name.'" value="'.$pval.'"'.$checked.' type="radio">
                '.$label.'
            </label>
            </div>';
    }
    return $out;
}

/*
 * Setto subito il campo 'views' durante la creazione di un post.
 * Il campo serve per l'ordinamento in base alla "popolarità"/visualizzazioni pagina, "views" appunto
 * senza il campo (che normalmente viene creato ed eventualmente incrementato durante la visualizzazione) il post scompare dalla vista
 * (il nome del campo trae in inganno, ma non c'entra con Toolset, è un custom field gestito dal plugin wp-postviews) serve per
 */
if(function_exists('postviews_menu')){
    add_action( 'save_post', 'msg_views_set_postview' );
    function msg_views_set_postview($post_id){
        if ( wp_is_post_revision( $post_id ) ){
            return;
        }
        if ( !$post_views = get_post_meta($post_id, 'views', true ) ) {
            $post_views = 0;
        }
        update_post_meta($post_id, 'views', ( $post_views + 1 ) );
    }
}

/*** TEMP ***/
add_shortcode('msg-control-post-taxonomy','msg_control_post_tax');
function msg_control_post_tax($atts){
    $atts = shortcode_atts(array(
        'tax_name' => '',
        'tax_param' => ''
    ), $atts);
    global $WP_Views;
    $view_sc_attrs = $WP_Views->view_shortcode_attributes;
    $ret = '<h1>'.$view_sc_attrs[0]['tax_name'].'</h1>';
    $ret .=  do_shortcode('[wpv-control-post-taxonomy taxonomy="'.$view_sc_attrs[0]['tax_name'].'" type="radios" default_label="Tutte" url_param="tax-tipo"]');
    //$ret .= do_shortcode('[wpv-control-post-taxonomy taxonomy="'.$atts['tax_name'].'" type="radios" default_label="Tutte" url_param="tax-tipo"]');
    return $ret;
}

/*** SHORTCODE ***/
add_shortcode('msg_page_link', 'msg_sc_page_link');
function msg_sc_page_link($atts){

    $atts = shortcode_atts(array(
        'page_id' => 0,
        'testo' => 'Vai',
        'querystring' => ''
    ), $atts);
    $qs = '';
    if($atts['querystring']){ // && strpos('|', $atts['querystring'])){
        $qs = '?'.$atts['querystring'];
    }
    $url = get_page_link(intval($atts['page_id'])).$qs;
    return '<a href="'.$url.'" class="ico-link btn btn-default">'.$atts['testo'].'</a>';
}

/*** SERVIZIO ***/
function view_today(){
	return time();
}
/* SHORTCODE: print additional block (from post) in a view loop */
/*add_shortcode( 'custom_block_in_loop', 'msgtools_custom_block_in_loop' );
function msgtools_custom_block_in_loop($atts, $content) {
    extract(shortcode_atts(array(
        'index' => false,
    ), $atts));
    $out = "";
    $index = intval($index);
    global $WP_Views;
    $view_shortcode_attributes = $WP_Views->view_shortcode_attributes;
    if($index !== false && $view_shortcode_attributes[0]['custom_blocks']){
        $custbs = explode("#", $view_shortcode_attributes[0]['custom_blocks']);
        foreach($custbs as $custb){
            $custb = explode("=", $custb);
            if(intval($custb[0]) == $index+1){
                $out = '<div class="col-xs-12 col-sm-6 col-md-4 item">';
                $out .= do_shortcode('[block_page id="'.intval($custb[1]).'" columns_desktop="4" in_page="true"]');
                $out .= '</div>';
                break;
            }
        }
    }
    return $out;
}*/
