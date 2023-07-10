<?php
$urlbase = get_option('msg_ess_ws_base', 'http://svilmy.intranet.rai.it/api/raiplace/');
$urlbase = rtrim($urlbase, '/') . '/';
define('MSG_ESS_WS_BASE', $urlbase);

add_action( 'wp_ajax_user_info_notifiche', 'msg_ess_user_notifiche' );
add_action( 'wp_ajax_nopriv_user_info_notifiche', 'msg_ess_user_notifiche' );
add_action( 'wp_ajax_user_info_ess', 'msg_ess_user_ess' );
add_action( 'wp_ajax_nopriv_user_info_ess', 'msg_ess_user_ess' );
function msg_ess_user_notifiche() {
    $user = wp_get_current_user();
    $matr = $user->user_login;
    //$matr = substr($matr, 1); // deprecato: era matricola solo numerica...

    //TEST LOCALE su message
    if(strpos($_SERVER['HTTP_HOST'], "message-asp") || '1' == (get_option('msg_ess_debug'))){
        _test_rest_dev_message('notifiche');
    }
    // temp statico
    //$matr = '103650';
    $urlGetUserData = MSG_ESS_WS_BASE.'/getnotifiche?m='.$matr;
    return msg_rest_req($urlGetUserData);
}
function msg_ess_user_ess() {
    $user = wp_get_current_user();
    $matr = $user->user_login;
    //$matr = substr($matr, 1); // deprecato: era matricola solo numerica...
    //TEST LOCALE su message
    if(strpos($_SERVER['HTTP_HOST'], "message-asp") || '1' == (get_option('msg_ess_debug'))){
        _test_rest_dev_message('ess');
    }
    // temp statico
    //$matr = '103650';
    $urlGetUserData = MSG_ESS_WS_BASE.'/getinfomatr?m='.$matr;
    return msg_rest_req($urlGetUserData);

}



function msg_rest_req($url){
    $ws_user = get_option('muserdata_usr', 'srvapache1');
    $ws_pass = get_option('muserdata_pwd', 'apache11112015');
    $ws_key = get_option('muserdata_key', '33540117672688738685');
    // $ws_domain = 'ict.corp.rai.it';
    $curl = curl_init();

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
        //return new WP_Error( 'user_error', 'Errore dati utente', array( 'status' => 404 ) );
        header("Content-Type: application/json;charset=utf-8");
        echo '{ "error": "nodata" }';
        exit();
    } else {
        header("Content-Type: application/json;charset=utf-8");
        echo $response;
        exit();
    }
}

function _test_rest_dev_message($tipo='ess'){
    $user = wp_get_current_user();
    //sleep(7); // simulo il ritardo della risposta del webService
    //$matr = $user->user_login;
    $userinfo = '"userid":'.$user->ID.',';
    $rest = [];
    $rest['ess'] = '{'.$userinfo.'"cvResponse":{"success":true,"error":null,"CVperc":"72","url":"http://svilmy.intranet.rai.it/cv_online"},"ferieResponse":{"success":true,"error":null,"eccezioni":[{"codice":"FE","Spettanti":25.0,"Fruite":3.0,"Residue":28.0,"AnniPrec":6.0},{"codice":"PR","Spettanti":5,"Fruite":2,"Residue":3,"AnniPrec":0},{"codice":"PF","Spettanti":4.0,"Fruite":0.0,"Residue":4.0,"AnniPrec":0.0}],"url":"http://svilmy.intranet.rai.it/feriepermessi"},"cedResponse":{"DataCompetenza":"201710","DataCompetenza_Desc":"Ottobre 2017","DataContabilizzazione":"201710","DataContabilizzazione_Desc":"Ottobre 2017","success":true,"error":null,"url":"http://svilmy.intranet.rai.it/bustapaga"}}';
    $rest['notifiche'] = '{'.$userinfo.'"success":true,"error":null,"NotificheAPI":[{"tipo":"1","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 13/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta POH  per il 03/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 08/11/2017-09/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 03/11/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 13/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 27/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 26/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 24/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 17/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STRF per il 17/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta STR  per il 17/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta rifiutata: FE   del 02/10/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 02/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsStorno","descrittiva":"Inserimento storno PRM  per il 06/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta PRM  per il 06/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 05/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 04/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: STR  del 21/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: FE   del 22/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 03/10/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 25/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per il 26/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"InsStorno","descrittiva":"Inserimento storno FE   per 15/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: FE   del 15/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"InsRichiesta","descrittiva":"Inserimento richiesta FE   per 15/09/2017 da Rossi Mario          ","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 04/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: UMH  del 15/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 19/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: POH  del 18/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRP  del 14/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MR   del 03/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MR   del 03/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"1","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: L7G  del 10/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MR   del 10/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: L7G  del 27/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: L7G  del 27/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 01/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 22/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 25/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 23/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 24/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 25/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 27/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 01/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 04/09/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 28/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: MN   del 20/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 10/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: STR  del 10/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 18/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: PRQ  del 18/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: SEH  del 08/08/2017 - da Rossi Mario il 21/08/2017 14.46","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: ROH  del 08/08/2017 - da Rossi Mario il 21/08/2017 14.28","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"ApprovazioneEccezione","descrittiva":"Richiesta approvata: PR   del 09/08/2017 - da Rossi Mario il 21/08/2017 14.19","href":"http://svilmy.intranet.rai.it/notifiche"},{"tipo":"2","titolo":"RifiutoEccezione","descrittiva":"Richiesta cancellata: FE   del 11/08/2017","href":"http://svilmy.intranet.rai.it/notifiche"}]}';
    header("Content-Type: application/json;charset=utf-8");
    if($tipo == 'ess'){
        $obj = json_decode($rest['ess']);
    } else {
        $obj = json_decode($rest['notifiche']);
    }
    /*
    global $extimer;
    $currtime = microtime(true);
    $obj->starttime = $extimer;
    $obj->currtime = $currtime;
    $obj->extime = $currtime - $extimer;
    $obj->memory = memory_get_usage();
    //*/
    $obj_obj = json_encode($obj);
    print $obj_obj;
    exit();
}

/* OLD con REST API
//*** Chiamata Web Service REST  remoto
add_action( 'rest_api_init', function () {
    register_rest_route( 'raiplace_rest', '/get_user_data/ess', array(
        'methods' => 'GET',
        'callback' => 'msg_ess_user_ess',
    ));
    register_rest_route( 'raiplace_rest', '/get_user_data/notifiche', array(
        'methods' => 'GET',
        'callback' => 'msg_ess_user_notifiche',
    ));

} );
function msg_ess_user_notifiche( WP_REST_Request $request ) {
    $user = wp_get_current_user();
    $matr = $user->user_login;

    //TEST LOCALE su message
    if(strpos($_SERVER['HTTP_HOST'], "message-asp")){
        _test_rest_dev_message('notifiche');
    }

    // temp statico
    $matr = '103650';
    $urlGetUserData = MSG_ESS_WS_BASE.'/getnotifiche?m='.$matr;
    return msg_rest_req($urlGetUserData);
}
function msg_ess_user_ess( WP_REST_Request $request ) {
    $user = wp_get_current_user();
    $matr = $user->user_login;

    //TEST LOCALE su message
    if(strpos($_SERVER['HTTP_HOST'], "message-asp")){
        _test_rest_dev_message('ess');
    }
    // temp statico
    $matr = '103650';
    $urlGetUserData = MSG_ESS_WS_BASE.'/getinfomatr?m='.$matr;
    return msg_rest_req($urlGetUserData);

}*/