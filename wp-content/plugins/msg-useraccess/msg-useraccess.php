<?php
/*
 Plugin Name: Message/RaiPlace: User Access
 Description: Custom plugin for RAI
 Author: Message s.r.l.
 Author URI: http://www.messagegroup.it/
 Version: 1.0
 Text Domain: msg_ua
 */
global $msg_ua;
if (!class_exists('Msg_UA')) {
	class Msg_UA {
		private $usermeta = 'next_ad_int_memberof';
		private $groupsfield = 'gruppi_con_accesso';
		protected $plugin_path;
		protected $p_slug = 'msg_ua';
		private $capab_bypass = 'edit_others_posts'; //capability comune editors e admins; per mostrare solo agli admin usa: 'promote_users' 
		private $current_user_can_bypass = false;
		public $current_user_groups = false;
		
		public function __construct() {
			//install
			register_activation_hook($this->plugin_path, array($this, 'install'));
			register_deactivation_hook($this->plugin_path, array($this, 'uninstall'));
			
			//vars
			$this->plugin_path = dirname(__FILE__);

			//various
			load_plugin_textdomain($this->p_slug, FALSE, $this->plugin_path . '/languages/');

			//actions
			add_action('init', array($this, 'init'));
			add_action('edit_form_after_title', array($this, 'edit_form_after_title')); //aggiunge campo in editing
			add_action('save_post', array($this, 'onSavePost')); //salva il campo in editing
			add_action('pre_get_posts', array($this, 'pre_get_posts')); //esclude in ricerche, archivi, etc.
			
			//filters
			add_filter( 'wp_get_nav_menu_items', array($this, 'wp_get_nav_menu_items'), null, 3 ); //esclude dai menu
			add_filter('template_include', array($this, 'template_include')); //se è il caso, carica la template "non permessa" nei single
			add_filter( 'wpv_filter_query', array($this, 'wpv_filter_query'), 999); //filtro viste (views)
			
			//shortcodes
			//add_shortcode('msg_ua_shortcode', array($this, 'scMethod1'));
		}
		
		public function init(){
			$this->current_user_groups = $this->getUserGroups();
			//$this->current_user_can_bypass = current_user_can($this->capab_bypass);
		}
		public function install() {

		}

		public function uninstall() {

		}

		public function scMethod1() {

		}
		/**
		 * da omonima WP action, per mostrare il campo del custom field in wp-admin
		 * @return void
		 */
		public function edit_form_after_title() {
			global $post;
			$value = get_post_meta($post->ID, $this->groupsfield, true);
			echo('<div style="padding:10px 0; margin-top:15px; border-top:1px solid #ddd;">
				<label>Accesso Gruppi AD</label>
				<input style="width:100%;" type="text" value="'.$value.'" name="'.$this->groupsfield.'" />
				<div style="font-size:11px;">Inserisci il gruppo ADs (multipli separ. da virgole)</div>
			</div>');

		}
		/**
		 * da omonima WP action, per salvare il custom field
		 * @param int $postid ID del post (passato da WP)
		 * @return void
		 */
		public function onSavePost($postid) {
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
			if (!current_user_can('edit_page', $postid)) return false;
			if (isset($_POST[$this->groupsfield]) && $_POST[$this->groupsfield]) {
				update_post_meta($postid, $this->groupsfield, $_POST[$this->groupsfield]);
			} else {
				delete_post_meta($postid, $this->groupsfield);
			}
		}
		
		/**
		 * Ritorna i gruppi AD di un utente
		 * @param int $user ID dell'utente, se non passato usa l'utente corrente
		 * @return array I gruppi AD come array 
		 */
		private function getUserGroups($user_ID = false){
			if(!$user_ID){
				$current = true;
				global $user_ID;
			}
			if(($current && !$this->current_user_groups) || !$current){
				$groups = get_user_meta($user_ID, $this->usermeta, true);
				$groups = '#'.$groups;
				$search = array(" ", "\t");
				$groups = str_replace($search, '', $groups);
				$search = array("\r\n", "\r", "\n");
				$groups = str_replace($search, '#', $groups);
				$groups = explode("#CN=", $groups);
				foreach ($groups as $k => $group) {
					$groups[$k] = strtok($group, ',');
				}
				array_shift($groups); //cancelliamo il primo vuoto
			} else {
				$groups = $this->current_user_groups;
			}
			return $groups;
		}
		/**
		 * Ritorna i gruppi AD di un post/page/cpt
		 * @param object $post il post, se non passato usa il $post globale
		 * @return array I gruppi AD come array 
		 */
		private function getPostGroups($post = false){
			if(!$post){ global $post; }
			$gruppiPost = $post ? get_post_meta($post->ID, $this->groupsfield, true) : array();
			$gruppiPost = str_replace(array(' ', ';'), array('', ','), $gruppiPost);
			$gruppiPost = $gruppiPost ? explode(',', $gruppiPost) : array();
			return $gruppiPost;
		}
		/**
		 * Controlla l'accesso di un utente a un post/page/cpt
		 * @param object $pst il post da controllare
		 * @param object $usr oggetto utente, se non passato usa l'utente corrente
		 * @return boolean se l'utente può accedere al post (fa parte dei gruppi impostati nel post) 
		 */
		public function checkSectionPermission($post = false, $user_ID = false) {
			if($this->current_user_can_bypass){
				$can = true;
			} else {
				if(!$user_ID){ global $user_ID; }
				if(!$post){ global $post; }
				//prendo (come array) i gruppi settati per il post
				$gruppiPost = $this->getPostGroups($post);
				if(count($gruppiPost)){ //ci sono gruppi settati nel post
					//prendo (come array) i gruppi dell'utente
					$gruppiUser = $this->getUserGroups();
					$gruppiInters = array_intersect($gruppiPost, $gruppiUser);
					//l'utente può accedere se c'è un'intersezione fra gli array
					$can = count($gruppiInters) > 0;
				} else { //senza gruppi nel post, l'accesso è universale
					$can = true;
				}
			}
			return($can);
		}
		/** CONTROLLO VOCI MENU
		 * da omonimo filtro WP, per controllare le voci di menu
		 * @params mixed (1-3) da chiamata di add_filter di wp
		 * @return array le voci di menu
		 */
		public function wp_get_nav_menu_items($items, $menu, $args) {
			foreach ( $items as $key => $item ) {
				if($item->type == "post_type"){
					$can = $this->checkSectionPermission(get_post($item->object_id));
					if(!$can){ unset($items[$key]); }
				}
			}
			return $items;
		}
		/** CONTROLLO ACCESSO SINGOLO POST
		 * da omonimo filtro WP, per l'accesso al singolo post/page/cpt
		 * @param string $single_template path verso la template appropriata
		 * @return string il (nuovo) path verso la template
		 */
		public function template_include($single_template) {
			//global $post;
			if(is_single()){
				if (!$this->checkSectionPermission()) {
					$single_template_file = "single-no-access.php";
					$single_template_plugin = $this->plugin_path . '/templates/'.$single_template_file;
					$single_template = locate_template($single_template_file) ? locate_template($single_template_file) : $single_template_plugin;
					//$single_template = $single_template_plugin;
				}
			}
			return $single_template;
		}
		
		/** CONTROLLO SEARCH
		 * da omonima action WP, per l'esclusione dalla search/archivi/etc
		 * @param object $query l'oggetto $query di WP (passato per referenza)
		 * @return object la (nuova) $query
		 */
		public function pre_get_posts($query) {
			if($query->is_search && !is_admin() && !$this->current_user_can_bypass){
				$mq = $query->get('meta_query');
				$mq = $this->modify_meta_query($mq);
				$query->set('meta_query', $mq);
			}
			return $query;
		}
		/** CONTROLLO VIEWS
		 * da omonimo filtro WP/wp-views, per l'esclusione dalle viste (views),
		 * v. https://wp-types.com/documentation/user-guides/views-filters/wpv_filter_query/
		 * @param mixed (1-3) da chiamata di add_filter di wp
		 * @return object i (nuovi) $query_args
		 */
		public function wpv_filter_query($query_vars){
			if(!$this->current_user_can_bypass){
				$mq = isset($query_vars['meta_query']) ? $query_vars['meta_query'] : false;
				$mq = $this->modify_meta_query($mq);
				$query_vars['meta_query'] = $mq;
			}
			return $query_vars;
		}
		
		/**
		 * Modifica l'array meta_query (anche per le viste, vedi filtro wpv_filter_query)
		 * @param array $mq la meta_query (con formato standard WP)
		 * @return array la (nuova) meta_query
		 */
		private function modify_meta_query($mq){
			$meta_cond_add = null;
			$user_groups = $this->getUserGroups();
			$meta_cond_add = array(
				'relation' => 'OR',
				array('key' => $this->groupsfield, 'compare' => 'NOT EXISTS'),
				//array('key' => $this->groupsfield, 'compare' => '=', 'value' => 'impossibilechesiaquesto')
			);
			foreach($user_groups as $ug){
				$meta_cond_add[] = array('key' => $this->groupsfield, 'value' => $ug, 'compare' => 'LIKE');
			}
			if($mq && count($user_groups)){
				$qmeta_query = array(
					'relation' => 'AND',
					$mq,
					$meta_cond_add
				);
			} elseif($mq){
				$qmeta_query = $mq;
			} else {
				$qmeta_query = $meta_cond_add;
			}
			//$qmeta_query = $mq;
			return $qmeta_query;
		}
		/**
		 * Scrive i gruppi per un post (servizio)
		 * @return null 
		 */
		public function printPostGroups(){
			var_dump($this->checkSectionPermission());
		}

	}

}
$msg_ua = new Msg_UA();
