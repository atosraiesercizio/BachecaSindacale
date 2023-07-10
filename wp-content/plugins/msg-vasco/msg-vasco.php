<?php
/*
 Plugin Name: Message/RaiPlace: interazione con Vasco
 Description: Cura il colloquio con il WS di Vasco per l'iscrizione al servizio di One Time Password via SMS
 Author: Message s.r.l.
 Author URI: http://www.messagegroup.it/
 Version: 1.0
 Text Domain: msg_ua
 */

if (!defined('ABSPATH')) exit;
define('MVASCO_PLUG_PATH', dirname(__FILE__));
define('MVASCO_PLUG_URL', plugin_dir_url(__FILE__));
define('MVASCO_ASSET_VERS', '1.0');
define('MVASCO_PAGE_SLUG', 'rai_vasco_iscrizione');

define('MVASCO_VASCO_URL', get_option('mvasco_url'));
define('MVASCO_VASCO_USR', get_option('mvasco_usr'));
define('MVASCO_VASCO_PWD', get_option('mvasco_pwd'));

$csvpath = get_option('mvasco_rsa_path');
$csvpath = ABSPATH . ltrim($csvpath, "\/");
define('MVASCO_VASCO_CSV_PATH', $csvpath);


/*** INIT ***/
add_action('init', 'mvasco_init');
function mvasco_init()
{
	//global $wp_rewrite;
	$mvascodebug = false;
	if (current_user_can('administrator') && isset($_GET['vascodebug'])) {
		$mvascodebug = true;
	}
	define('MVASCO_DEBUG', $mvascodebug);

}

/* attivazione: creazione pagine (se non esistono) */
register_activation_hook(__FILE__, 'mvasco_activation');
function mvasco_activation()
{
	$wpdb = $GLOBALS['wpdb'];
	$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '%s' AND post_type = 'page' ", MVASCO_PAGE_SLUG));
	if (empty($id)) {
		wp_insert_post(array(
			'post_type' => 'page',
			'post_title' => 'VASCO : Gestione iscrizione al servizio',
			'post_name' => MVASCO_PAGE_SLUG,
			'post_status' => 'publish'
		));
	}
}

add_filter('the_content', 'mvasco_content');
/**
 * inserisce l'html della pagina di Vasco
 * (chiamata dal filtro 'the_content')
 * @param $content
 * @return string
 */
function mvasco_content($content)
{
	if (is_page(MVASCO_PAGE_SLUG)) {
		$msg_formato_tel = 'Scrivi il numero del cellulare. Per i numeri italiani italiano il prefisso internazionale (+39) è facoltativo, mentre per i numeri <b>esteri</b> il prefisso internazionale è <b>obbligatorio</b> (p.e. +33 per la Francia)';
		$content = '';
		// controllo utenti RSA
		$current_user = wp_get_current_user();
		if (MVASCO_DEBUG) {
			if (!empty($_GET['forzamatricola'])) {
				$current_user_tmp = get_user_by('login', $_GET['forzamatricola']);
			}
			if (!empty($current_user_tmp->ID)) {
				$current_user = $current_user_tmp;
				$content .= '<script>mvasco_debug_url = "/wp-admin/admin-ajax.php?vascodebug=1&forzamatricola=' . $_GET['forzamatricola'] . '";</script>';
			} else {
				$content .= '<script>mvasco_debug_url = "/wp-admin/admin-ajax.php?vascodebug=1";</script>';
			}
		}
		if (file_exists(MVASCO_VASCO_CSV_PATH)) {
			$row = 1;
			$header = true;
			$utenti_no_vasco = array();
			if (($handle = fopen(MVASCO_VASCO_CSV_PATH, "r")) !== FALSE) {
				if (MVASCO_DEBUG) {
					$content .= '<pre>DEBUG: file csv aperto in lettura</pre>';
				}
				while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
					$num = count($data);
					if ($num == 8) {
						if ($header) {
							$header = false;
							continue;
						}
						if (strtolower($data[0]) == strtolower($current_user->user_login)) {
							$content .= 'Siamo spiacenti, ma non puoi effettuare la registrazione al servizio perché risulti <b>già in possesso di SecureID/RSA</b>.
							<br>Se non ricordi le credenziali di accesso o non possiedi SecureID o Digipass e per ogni altro problema contatta il 2550.';//.'<br>';
							//$content .= 'perché utilizzi già altri sistemi per l\'accesso da fuori rete (<em>chiave RSA</em>.)';
							//$content = '<div id="mvasco_wrapper"><div class="vc_row row_large_padd">' . $content . '</div></div>';
							fclose($handle);
							return $content;
						}
					}
				}
				fclose($handle);
			}

		}

		wp_enqueue_style('mvasco_css', MVASCO_PLUG_URL . '/css/msg-vasco.css', NULL, MVASCO_ASSET_VERS);
		wp_enqueue_script('mvasco_script', MVASCO_PLUG_URL . '/js/msg-vasco.js', array('jquery'), MVASCO_ASSET_VERS, true);
		wp_localize_script('mvasco_script', 'mvasco', array(
			'ajaxurl' => admin_url('admin-ajax.php')
		));
		global $post;
		$content .= '<hr><div id="mvasco_wrapper"><div class="vc_row row_large_padd"><div id="vasco_check"><h2>Aspetta, sto controllando lo stato dell\'iscrizione al servizio...</h2><br><br><div id="vasco_wait"></div></div>';
		//$stato_utente = get_user_meta($current_user->ID, 'mvasco_iscritto', true);
		$content .= '
            <div id="vasco_no_virtual" class="hidden">Siamo spiacenti, ma non puoi effettuare la registrazione al servizio perché risulti già in <b>possesso di una Digipass/Vasco</b> di tipo Hardware o Software.
            <br>Se non ricordi le credenziali di accesso o non possiedi SecureID o Digipass e per ogni altro problema contatta il 2550.</div>
            <div id="vasco_error" class="hidden">Siamo spiacenti, ma si è verificato un problema di connessione col server che gestisce le DigiPass.<br>Riprova più tardi</div>
            <div id="vasco_after_check" class="hidden">
            <h2 class="show_vasco_ok">Il servizio è ATTIVO per la tua utenza</h2>
			<div class="show_vasco_ok">Il numero di telefono attualmente registrato è <span class="big user_cell"></span>.<hr></div>
			<h2 class="show_vasco_ko">Non hai attivato il servizio</h2> 
			<div class="show_vasco_ko">Inserisci il tuo numero di cellulare e iscriviti al servizio</div>
            <div class="row">
                <div class="form-group col-sm-6">
                    <h3 class="show_vasco_ok">Modifica il tuo numero di telefono</h3>
                    <div class="form-inline">
                        <div class="form-group">
                            <input type="text" id="mvasco_cell" class="user_cell" placeholder="Solo cifre (con eventuale + iniziale)">
							<button class="btn btn-default show_vasco_ko" id="mvasco_attiva" disabled>
								<span class="ti-check"></span> Attiva il servizio
							</button>
                            <button class="btn btn-default show_vasco_ok" id="mvasco_modifica">
                                <span class="ti-pencil"></span> <em>Modifica</em>
                            </button>
                        </div>
                    </div>
                    <div class="small">' . $msg_formato_tel . '</div>
                </div>
                <div class="form-group col-sm-6 show_vasco_ok vasco_disattiva">
                    <h3>Disattiva il servizio</h3>
                    <button class="btn btn-primary" id="mvasco_disattiva">
                        <span class="ti-trash"></span> Disattiva il servizio
                    </button>
                </div>
            </div></div>';

		$content .= '</div></div>';
	}
	return $content;
}

/*** AJAX ***/
add_action('wp_ajax_nopriv_mvasco_save', 'mvasco_save');
add_action('wp_ajax_mvasco_save', 'mvasco_save');
function mvasco_save(){
	$vasco_debug = false;
	$response = array(
		'esito' => 'ko'
	);
	$current_user = wp_get_current_user();
	if (current_user_can('administrator') && isset($_GET['vascodebug'])) {
		$vasco_debug = true;
		$current_user_tmp = get_user_by('login', $_GET['forzamatricola']);
		if (!empty($current_user_tmp->ID)) {
			$current_user = $current_user_tmp;
		}
	}
	$meta = [
		'iscritto' => true,
		'telefono' => validaTelefono($_POST['telefono'])
	];
	switch ($_POST['sub_action']) {
		case 'check':
			$resp = richiestaVascoSOAP('userInfo', ['user' => $current_user->user_login]);
			if (!empty($resp['success'])) {
				$meta['iscritto'] = $resp['attrs']['USERFLD_HAS_DP'] == 'Assigned';
				$meta['telefono'] = !empty($resp['attrs']['USERFLD_MOBILE']) ? $resp['attrs']['USERFLD_MOBILE'] : '';
				$meta['tipolicenza'] = 'NON';
				if (!empty($resp['attrs']['USERFLD_ASSIGNED_DIGIPASS'])) {
					$meta['tipolicenza'] = strtoupper(substr($resp['attrs']['USERFLD_ASSIGNED_DIGIPASS'], 0, 3));
				}
				$response = array(
					'esito' => 'ok',
					'iscritto' => $meta['iscritto'],
					'telefono' => validaTelefono($meta['telefono']),
					'tipolicenza' => $meta['tipolicenza']
				);
				update_user_meta($current_user->ID, 'mvasco_iscritto', $meta);
			} else {
				$response['reason'] = $resp['error_code'];
			}
			break;
		case 'attiva':
			$respWrite = richiestaVascoSOAP('userWritePhone', ['user' => $current_user->user_login, 'phone' => $meta['telefono']]);
			$resp = richiestaVascoSOAP('digipassAssignUser', ['user' => $current_user->user_login]);
			if (!empty($resp['success'])) {
				$meta['digipass_code'] = $resp['attrs']['DIGIPASSFLD_SERNO'];
				update_user_meta($current_user->ID, 'mvasco_iscritto', $meta);
				$response = array(
					'esito' => 'ok',
					'digipass_code' => $resp['attrs']['DIGIPASSFLD_SERNO'],
					'telefono' => $meta['telefono']
				);
			} else {
				$response['reason'] = $resp['error_code'];
			}
			break;
		case 'disattiva':
			$resp = richiestaVascoSOAP('digipassUnassignUser', ['user' => $current_user->user_login]);
			if (!empty($resp['success'])) {
				$meta['iscritto'] = false;
				unset($meta['digipass_code']);
				update_user_meta($current_user->ID, 'mvasco_iscritto', $meta);
				//@TODO: azioni VASCO eliminazione nuovo utente...
				//... o, se si può disabilitare:
				//$meta['iscritto'] = false;
				//update_user_meta($current_user->ID, 'mvasco_iscritto', $meta);
				$response = array(
					'esito' => 'ok',
					'telefono' => $meta['telefono']
				);
			} else {
				$response['reason'] = $resp['error_code'];
			}
			break;
		case 'modifica':
			$resp = richiestaVascoSOAP('userWritePhone', ['user' => $current_user->user_login, 'phone' => $meta['telefono']]);
			if (!empty($resp['success'])) {
				update_user_meta($current_user->ID, 'mvasco_iscritto', $meta);
				//@TODO: azioni VASCO modifica utente esistente
				$response = array(
					'esito' => 'ok',
					'telefono' => $meta['telefono']
				);
			} else {
				$response['reason'] = $resp['error_code'];
			}

			break;

		default:
			break;
	}
	$logout = richiestaVascoSOAP('adminLogout');
	if ($vasco_debug) {
		global $vasco_sess_id;
		$response['debug'] = $resp;
		$response['debug']['vasco_sess_id'] = $vasco_sess_id;
		$response['debug_logout'] = $logout;

	}

	wp_send_json($response);
}

/*** ADMIN/SETTINGS ***/

add_action('admin_menu', 'mvasco_admin_menu');
function mvasco_admin_menu()
{
	add_options_page('Message Vasco', 'VASCO: parametri', 'manage_options', 'mvasco_settings', 'mvasco_admin_page');
}

function mvasco_admin_page()
{
	include(MVASCO_PLUG_PATH . '/msg-vasco-settings.php');
}

/******************************************************
 *
 * COLLOQUIO CON VASCO (WebService)
 *
 *****************************************************/

//$local_cert = "vasco_cert.pem"; //non usato
global $vasco_sess_id;
$vasco_sess_id = '';

//var_dump(richiestaVascoSOAP('userInfo', ['user' => 'gceriani']));
//var_dump(richiestaVascoSOAP('digipassAssignUser', ['user' => 'gceriani']));
//var_dump(richiestaVascoSOAP('digipassUnassignUser', ['user' => 'gceriani']));
//var_dump(richiestaVascoSOAP('userInfo', ['user' => 'gceriani']));
//var_dump(richiestaVascoSOAP('digipassUnassign', ['digipass_sn' => 'VDP4453548']));
/**
 * Richiesta SOAP a Vasco
 * @param string $action_id
 * @param array $params
 * @return array
 */

function richiestaVascoSOAP($action_id = '', $params)
{
	$wsURL = MVASCO_VASCO_URL;
	$return = ['success' => false, 'error_code' => 'NO_ACTION'];
	if ($action_id != 'adminLogin') {
		$vasco_sess_id = vascoGetValidSession();
	}
	switch ($action_id) {
		case 'userInfo':
		case 'userWritePhone':
			$tipoResponse = 'userExecuteResponse';
			break;
		case 'digipassAssignUser':
			$tipoResponse = 'digipassExecuteResponse';
			break;
		case 'digipassUnassign': // non dovrebbe essere usata direttamente, ma chiamata ricorsivamente da 'digipassUnassignUser'
			$tipoResponse = 'digipassExecuteResponse';
			break;
		case 'digipassUnassignUser':
			$tipoResponse = 'userExecuteResponse';
			break;
		case 'adminLogout':
			$tipoResponse = 'logoffResponse';
			break;
		default: // 'adminLogin'
			$tipoResponse = 'logonResponse';
			break;

	}

	if ($action_id == 'digipassUnassignUser') {
		$userInfo = richiestaVascoSOAP('userInfo', $params);
		if (!empty($userInfo['success'])) {
			if ($userInfo['attrs']['USERFLD_HAS_DP'] == 'Unassigned') { // non aveva licenze assegnate...
				$return = ['success' => true, 'reason' => 'USER_ALREADY_UNASSIGNED'];
			} else { // ha almeno una licenza
				$tipolicenza = strtoupper(substr($userInfo['attrs']['USERFLD_ASSIGNED_DIGIPASS'], 0, 3));
				if ($tipolicenza == "ESP" || $tipolicenza == "VDP") { // eliminiamo solo le DIGIPASS Virtuali, unico tipo rimovibile da questo contesto
					// l'utente su RAI dovrebbe avere una sola DIGIPASS, ma per sicurezza... (su Vasco è possibile assegnare più licenze, qui divise da virgola)
					$digipasses = explode(",", $userInfo['attrs']['USERFLD_ASSIGNED_DIGIPASS']);
					$digierrors = array();
					foreach ($digipasses as $digipass) {
						$okUnassign = richiestaVascoSOAP('digipassUnassign', ['digipass_sn' => $digipass]);
						if (empty($okUnassign['success'])) {
							$digierrors[] = $digipass;
						}
					}
					if (count($digierrors)) {
						$return = ['success' => false, 'error_code' => 'ERROR_UNASSIGN_CODES', 'digipass_error_sn' => $digierrors];
					} else {
						$return = ['success' => true, 'reason' => 'USER_UNASSIGNED'];
					}
				} else {
					$return = ['success' => true, 'reason' => 'USER_WITH_NONVIRTUAL_DIGIPASS'];
				}

			}
		}
	} else {
		if ($action_id == 'digipassAssignUser') {
			$userInfo = richiestaVascoSOAP('userInfo', $params);
			if (!empty($userInfo['success']) && $userInfo['attrs']['USERFLD_HAS_DP'] == 'Assigned') {
				return ['success' => false, 'reason' => 'USER_ALREADY_ASSIGNED'];
			}
		}

		$xmlToPost = getXml2Vasco($action_id, $params);
		if ($xmlToPost) {
			$headers = array(
				"Content-type: text/xml;charset=\"utf-8\"",
				"Accept: text/xml",
				"Cache-Control: no-cache",
				"Pragma: no-cache",
				"Content-length: " . strlen($xmlToPost),
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $wsURL);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$verbose = fopen('php://temp', 'w+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlToPost);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$response = curl_exec($ch);
			if ($response === FALSE) {
				$error_details = "CURL error (#" . curl_errno($ch) . "): " . htmlspecialchars(curl_error($ch), ENT_QUOTES);
				rewind($verbose);
				$verboseLog = stream_get_contents($verbose);
				$error_details_verb = "<pre>" . htmlspecialchars($verboseLog, ENT_QUOTES) . "</pre>";
				$return = ['success' => false, 'error_code' => 'NO_VASCO', 'error_details' => $error_details, 'error_details_verb' => $error_details_verb];

			} else {
				$respXML = str_ireplace(['soap-env:', 'soap:', 'admin-types:', 'auth-types:'], '', $response);
				$respXML = simplexml_load_string($respXML);
				$respxmlold = new SimpleXMLElement($response);
				$respxmlold->preserveWhiteSpace = false;
				$respxmlold->formatOutput = true;
				//header('Content-Type: text/xml');
				//print($respxmlold->asXML());
				//print "<h2>Flusso $az - step ".($i+1)."</h2>";
				$xmlsResults = $respXML->Body->{$tipoResponse}->results;

				if (empty($xmlsResults)) { // la risposta di Vasco è malformata/ingestibile
					$return = ['success' => false, 'error_code' => 'VASCO_RESP_ERROR', 'error_response_details' => $respXML, 'richiesta' => $xmlToPost];
				} elseif ($xmlsResults->resultCodes->returnCode != 0) {
					$return = ['success' => false, 'error_code' => (string)$xmlsResults->resultCodes->statusCodeEnum];
				} else { //tutto OK
					$respAttrs = [];
					foreach ($xmlsResults->resultAttribute->attributes as $attr) {
						$respAttrs[(string)$attr->attributeID] = (string)$attr->value;
					}
					$return = ['success' => true, 'attrs' => $respAttrs];
				}

			}
			curl_close($ch);
		}
	}
	return $return;

}

/**
 * ritorna la variabile globale $vasco_sess_id (se presente, altrimenti effettua la login per ottenerla)
 * @return string
 */
function vascoGetValidSession()
{
	global $vasco_sess_id;
	if (empty($vasco_sess_id)) {
		$vascoResp = richiestaVascoSOAP('adminLogin', ['user' => MVASCO_VASCO_USR, 'pass' => MVASCO_VASCO_PWD]);
		if (!empty($vascoResp['success'])) {
			$vasco_sess_id = $vascoResp['attrs']['CREDFLD_SESSION_ID'];
		}
	}
	return $vasco_sess_id;
}

/**
 * Restituisce la giusta stringa XML per il POST, con le dovute sostituzioni
 * @param string $action_id
 * @param array $prm
 * @return string
 */
function getXml2Vasco($action_id = 'adminLogin', $prm = array())
{
	if ($action_id != 'adminLogin') {
		$vasco_sess_id = vascoGetValidSession();
	}
	$return = '';
	switch ($action_id) {
		case 'adminLogin':
			if (isset($prm['user']) && isset($prm['pass'])) {
				$return = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:adm="http://www.vasco.com/IdentikeyServer/IdentikeyTypes/Administration" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				   <soapenv:Header/>
				   <soapenv:Body>
					  <adm:logon>
						 <attributeSet>
							<!--Zero or more repetitions:-->
							<attributes>
							   <value xsi:type="xsd:dateTime">2016-11-25T16:37:04Z</value>
							   <attributeID>CREDFLD_LAST_LOGON_TIME</attributeID>
							</attributes>
							<attributes>
							   <value xsi:type="xsd:string">' . $prm['user'] . '</value>
							   <attributeID>CREDFLD_USERID</attributeID>
							</attributes>
							<attributes>
							   <value xsi:type="xsd:unsignedInt">0</value>
							   <attributeID>CREDFLD_PASSWORD_FORMAT</attributeID>
							</attributes>
							<attributes>
							   <value xsi:type="xsd:string">' . $prm['pass'] . '</value>
							   <attributeID>CREDFLD_PASSWORD</attributeID>
							</attributes>            
						 </attributeSet>
					  </adm:logon>
				   </soapenv:Body>
				</soapenv:Envelope>';
			}
			break;
		case 'userInfo':
			if (isset($prm['user'])) {
				$return = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:adm="http://www.vasco.com/IdentikeyServer/IdentikeyTypes/Administration" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				   <soapenv:Header/>
				   <soapenv:Body>
					  <adm:userExecute>
						 <sessionID>' . $vasco_sess_id . '</sessionID>
						 <cmd>USERCMD_VIEW</cmd>
						 <attributeSet>
							<!--Zero or more repetitions:-->
							<attributes>
							   <value xsi:type="xsd:string">users</value>
							   <attributeID>USERFLD_DOMAIN</attributeID>
							</attributes>
							 <attributes>
							   <value xsi:type="xsd:string">' . $prm['user'] . '</value>
							   <attributeID>USERFLD_USERID</attributeID>
							</attributes>
						 </attributeSet>
						 <!--Optional:-->
						 <adminDomainInfoList>
							<!--Zero or more repetitions:-->
							<adminDomains>
							   <adminDomain>master</adminDomain>
							</adminDomains>
						 </adminDomainInfoList>
					  </adm:userExecute>
				   </soapenv:Body>
				</soapenv:Envelope>';
			}
			break;
		case 'userWritePhone':
			if (isset($prm['user'])) {
				$return = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:adm="http://www.vasco.com/IdentikeyServer/IdentikeyTypes/Administration" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				   <soapenv:Header/>
				   <soapenv:Body>
					  <adm:userExecute>
						 <sessionID>' . $vasco_sess_id . '</sessionID>
						 <cmd>USERCMD_UPDATE</cmd>
						 <attributeSet>
							<attributes>
							   <value xsi:type="xsd:string">' . $prm['user'] . '</value>
							   <attributeID>USERFLD_USERID</attributeID>
							</attributes>
							<attributes>
							   <value xsi:type="xsd:string">users</value>
							   <attributeID>USERFLD_DOMAIN</attributeID>
							</attributes>
							<attributes>
							   <value xsi:type="xsd:string">' . $prm['phone'] . '</value>
							   <attributeID>USERFLD_MOBILE</attributeID>
							</attributes>
						 </attributeSet>
						 <!--Optional:-->
						 <adminDomainInfoList>
							<adminDomains>
							   <adminDomain>master</adminDomain>
							</adminDomains>
						 </adminDomainInfoList>
					  </adm:userExecute>
				   </soapenv:Body>
				</soapenv:Envelope>';
			}
			break;
		case 'digipassAssignUser':
			if (isset($prm['user'])) {
				$return = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:adm="http://www.vasco.com/IdentikeyServer/IdentikeyTypes/Administration" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				   <soapenv:Header/>
				   <soapenv:Body>
					  <adm:digipassExecute>
						 <sessionID>' . $vasco_sess_id . '</sessionID>
						 <cmd>DIGIPASSCMD_ASSIGN</cmd>
						 <attributeSet>
							<!--Zero or more repetitions:-->
							<attributes>
							   <value  xsi:type="xsd:string">' . $prm['user'] . '</value>
							   <attributeID>DIGIPASSFLD_ASSIGNED_USERID</attributeID>
							</attributes>
							<attributes>
							   <value  xsi:type="xsd:string">users</value>
							   <attributeID>DIGIPASSFLD_DOMAIN</attributeID>
							</attributes>
							<attributes>
							   <value  xsi:type="xsd:int">0</value>
							   <attributeID>DIGIPASSFLD_GRACE_PERIOD_DAYS</attributeID>
							</attributes>
							<attributes>
                                <value  xsi:type="xsd:string">VIR10</value>
                                <attributeID>DIGIPASSFLD_DPTYPE</attributeID>
                            </attributes>
						 </attributeSet>
					  </adm:digipassExecute>
				   </soapenv:Body>
				</soapenv:Envelope>';
			}
			break;
		case 'digipassUnassign':
			if (isset($prm['digipass_sn'])) {
				$return = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:adm="http://www.vasco.com/IdentikeyServer/IdentikeyTypes/Administration"  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				   <soapenv:Header/>
				   <soapenv:Body>
					  <adm:digipassExecute>
						 <sessionID>' . $vasco_sess_id . '</sessionID>
						 <cmd>DIGIPASSCMD_UNASSIGN</cmd>
						 <attributeSet>
							<!--Zero or more repetitions:-->
							<attributes>
							   <value  xsi:type="xsd:string">' . $prm['digipass_sn'] . '</value>
							   <attributeID>DIGIPASSFLD_SERNO</attributeID>
							</attributes>
						 </attributeSet>
					  </adm:digipassExecute>
				   </soapenv:Body>
				</soapenv:Envelope>';
			}
			break;
		case 'adminLogout':
			$return = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:adm="http://www.vasco.com/IdentikeyServer/IdentikeyTypes/Administration" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
			   <soapenv:Header/>
			   <soapenv:Body>
				  <adm:logoff>
					 <attributeSet>
						<!--Zero or more repetitions:-->
						<attributes>
						   <!--Optional:-->
						   <value xsi:type="xsd:string">' . $vasco_sess_id . '</value>
						   <attributeID>CREDFLD_SESSION_ID</attributeID>
						</attributes>
					 </attributeSet>
				  </adm:logoff>
			   </soapenv:Body>
			</soapenv:Envelope>';
			break;
		default:
			break;
	}
	return $return;
}

/**
 * funzione di servizio per pulizia dei numeri di telefono
 * ritorna una stringa +NNNNN... o NNNNN...
 * @param $str
 * @return string
 */
function validaTelefono($str){
	$prima = preg_replace("/[^0-9\+]/", "", substr($str, 0, 1));
	$resto = preg_replace("/[^0-9]/", "", substr($str, 1));
	return $prima.$resto;
}