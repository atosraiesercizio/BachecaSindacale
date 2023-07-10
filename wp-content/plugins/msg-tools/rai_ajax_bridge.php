<?php
global $extimer;
$extimer = microtime(true);
global $conn_data, $matricola;

session_start();
if(empty($_SESSION['rai_user_matricola'])){
    _exitJson(['error' => 'no valid user data']);
}
$matricola = $_SESSION['rai_user_matricola'];
preg_match('/^(.+)\/wp-content\/plugins\/.*/', dirname(__FILE__), $path);
$rootdir = !empty($path[1]) ? $path[1] : $_SERVER['DOCUMENT_ROOT'];

// lettura (senza inclusione) di wp-config.php
$array = file($rootdir."/wp-config.php");
$dbArray = [];
$x = array('DB_NAME','DB_USER','DB_PASSWORD','DB_HOST');
foreach ( $array as $line ) {
    $matches=array();
    if (preg_match('/DEFINE\(\'(.*?)\',\s*\'(.*)\'\);/i', $line, $matches)) {
        $name=$matches[1];
        $value=$matches[2];
        if(in_array( $name, $x ))$dbArray[$name]= $value;
    }
}
//require $rootdir."/wp-config.php";
$dbportstring = '';
if(strpos($dbArray['DB_HOST'],":")){
    $db_full_host = explode(":", $dbArray['DB_HOST']);
    $dbArray['DB_HOST'] = $db_full_host[0];
    $dbportstring = ';port='.$db_full_host[1];
}
// connessione al DB
$conn = new PDO("mysql:host=".$dbArray['DB_HOST']."$dbportstring;dbname=".$dbArray['DB_NAME']."", $dbArray['DB_USER'], $dbArray['DB_PASSWORD']);

$conn_data = [
    'ws_base' => 'msg_ess_ws_base',
    'ws_usr' => 'muserdata_usr',
    'ws_pwd' => 'muserdata_pwd',
    'ws_key' => 'muserdata_key'
];
$allok = true;
foreach($conn_data as $k => $option){
    $qry = $conn->query("SELECT option_value FROM wp_options WHERE option_name = '$option'")->fetch();
    if($qry['option_value']){
        $conn_data[$k] = $qry['option_value'];
    } else {
        _exitJson(['error' => 'no connection data']);
    }
}
define('MSG_ESS_WS_BASE', rtrim($conn_data['ws_base'], "/"));

$qry = $conn->query("SELECT option_value FROM wp_options WHERE option_name = 'msg_ess_debug'")->fetch();
define('MSG_ESS_DEBUG', strpos($_SERVER['HTTP_HOST'], "message-asp") || !empty($qry['option_value']));
/**
 *
 */
$valid_types = ['ess', 'notifiche', 'postievento'];
if(isset($_REQUEST['tipo']) && in_array($_REQUEST['tipo'], $valid_types)){
    $curr_type = $_REQUEST['tipo'];
	if(isset($_REQUEST['matricolaforced_secret'])){
		//solo per effettuare dei test, dato che la nostra utenza NON ha dati...
		//$matricola = $_GET['matricolaforced_secret'];
	}
    switch($curr_type){
        case 'ess':
            $ws_path = '/getinfomatr?m='.$matricola;
            break;
        case 'notifiche':
            $ws_path = '/getnotifiche?m='.$matricola;
            break;
	    case 'postievento':
		    $ws_path = '/getdispoev?idev='.intval($_REQUEST['idev']);
		    break;

    }
    //TEST LOCALE su message
    if(MSG_ESS_DEBUG){
        _test_rest_dev_message($curr_type);
    } else {
	    msg_rest_req(MSG_ESS_WS_BASE.$ws_path);

    }
}




/**
 * chiama il Web Service e passa l'output alla scrittura finale
 * @param $url
 */
function msg_rest_req($url){
    global $conn_data, $extimer;
    $ws_user = $conn_data['ws_usr'];
    $ws_pass = $conn_data['ws_pwd'];
    $ws_key = $conn_data['ws_key'];
    // $ws_domain = 'ict.corp.rai.it';
    $curl = curl_init();
    $conntime = microtime(true);
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPAUTH => CURLAUTH_NTLM,
        CURLOPT_USERPWD => "$ws_user:$ws_pass",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "keystring: $ws_key",
        ),
    ));
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
    curl_setopt($curl, CURLOPT_USERPWD, "$ws_user:$ws_pass");

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        $esito = ["error" => "nodata", "reason" => $err];
    } else {
        $esito = json_decode($response);
        //$esito->matricola = $matricola;
        //$esito->ws_url = $url;
    }
    $conntime = microtime(true)-$conntime;
    $extimer = $extimer + $conntime;
    _exitJson($esito);

}

function _test_rest_dev_message($tipo='ess'){
    global $matricola;
    $userinfo = '"userid":"'.$matricola.'",';
    $rest = [];
    $rest['ess'] = '{'.$userinfo.'"cvResponse":{"success":true,"error":null,"CVperc":"72","url":"http://svilmy.intranet.rai.it/cv_online"},"ferieResponse":{"success":true,"error":null,"eccezioni":[{"codice":"FE","Spettanti":25.0,"Fruite":3.0,"Residue":28.0,"AnniPrec":6.0},{"codice":"PR","Spettanti":5,"Fruite":2,"Residue":3,"AnniPrec":0},{"codice":"PF","Spettanti":4.0,"Fruite":0.0,"Residue":4.0,"AnniPrec":0.0}],"url":"http://svilmy.intranet.rai.it/feriepermessi"},"cedResponse":{"DataCompetenza":"201710","DataCompetenza_Desc":"Ottobre 2017","DataContabilizzazione":"201710","DataContabilizzazione_Desc":"Ottobre 2017","success":true,"error":null,"url":"http://svilmy.intranet.rai.it/bustapaga"}}';
    $rest['notifiche'] = '{'.$userinfo.'"success":true,"error":null,"NotificheAPI":[{"tipo":"1","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 13/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta POH  per il 03/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 08/11/2017-09/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 03/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 13/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 27/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 26/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 24/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 17/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 17/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STR  per il 17/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta rifiutata: FE   del 02/10/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 02/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsStorno","descrittiva":"Inserimento storno PRM  per il 06/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta PRM  per il 06/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 05/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 04/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: STR  del 21/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: FE   del 22/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 03/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 25/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 26/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"InsStorno","descrittiva":"Inserimento storno FE   per 15/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: FE   del 15/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per 15/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 04/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: UMH  del 15/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 18/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRP  del 14/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MR   del 03/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MR   del 03/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: L7G  del 10/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MR   del 10/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: L7G  del 27/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: L7G  del 27/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 01/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 22/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 25/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 23/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 24/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 25/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 27/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 01/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 04/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 28/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MN   del 20/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 10/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 10/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 18/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 18/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: SEH  del 08/08/2017 - da Rossi Mario il 21/08/2017 14.46","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: ROH  del 08/08/2017 - da Rossi Mario il 21/08/2017 14.28","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: PR   del 09/08/2017 - da Rossi Mario il 21/08/2017 14.19","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: FE   del 11/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"}]}';
    $rest['postievento'] = '{'.$userinfo.'"success":true,"error":null,"disponibili":'.rand(3, 20).'}';
	$esito = json_decode($rest[$tipo], true);
    _exitJson($esito);
}

/**
 * scrive il json e chiude lo script
 * @param $var
 */
function _exitJson($var){
    global $extimer;
    $currtime = microtime(true);
    $timetotal = $currtime - $extimer;
    header("Content-Type: application/json;charset=utf-8");
    /*
    if(gettype($var) == "array"){
        $var['extime'] = $timetotal;
        $var['memory'] = memory_get_usage();
    } else {
        $var->extime = $timetotal;
        $var->memory = memory_get_usage();
    }
    //*/
    $var_obj = json_encode($var);
    echo $var_obj;
    exit();
}