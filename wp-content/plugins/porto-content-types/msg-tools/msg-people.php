<?php
define('MPEOPLE_WS_URL', get_option('mpeople_url', 'http://sviladweb.servizi.rai.it/ADWeb.asmx')); //"http://sviladweb.servizi.rai.it/ADWeb.asmx"
define('MPEOPLE_WS_USER', get_option('mpeople_usr', 'srvapache1'));
define('MPEOPLE_WS_PASS', get_option('mpeople_pwd', 'apache11112015'));

/*** SHORTCODE per la form di ricerca persone ***/
add_shortcode('msg_people', 'msg_people_search_sc');
function msg_people_search_sc($atts){ // attributi non usati per ora
    wp_enqueue_style('msg_people_search', MSG_TOOLS_PLUG_URL . 'css/msg_people_search.css', array(), MSG_TOOLS_ASSET_VERS);

    wp_enqueue_script('msg_people_search', MSG_TOOLS_PLUG_URL . 'js/msg_people_search.js', array('jquery'), MSG_TOOLS_ASSET_VERS, true);
    wp_localize_script( 'msg_people_search', 'msg_people',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234
        )
    );

    $out = '<div class="form-inline vc_row row_large_padd" id="people_search">';
    $out .= '<div class="form-group"><label>Cognome</label><input id="cognome" name="cognome" type="text"><div class="small">Cognome intero o parte iniziale (minimo 3 lettere)</div></div>';
    $out .= '<div class="form-group"><button type="submit" class="btn btn-default" disabled style="margin: 4px 0 0 4px">Cerca</button"></div>';
    $out .= '</div>';
    $out .= '<div id="people_serp" class="vc_row row_large_padd" style="padding-top:0"></div>';
    // faccio una post vuota iniziale perchè ogni tanto al primo colpo manca l'autentica NTLM...
    // ...lo so, lo so!
    $wsPeopleRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Body></soap12:Body>
            </soap12:Envelope>';
    $wsPeopleResp = postSoapReq(MPEOPLE_WS_URL, $wsPeopleRequest, MPEOPLE_WS_USER, MPEOPLE_WS_PASS);
    return $out;
}

/*** azioni AJAX per la ricerca ***/
add_action( 'wp_ajax_msg_people_search', 'msg_people_ajax_search' );
add_action( 'wp_ajax_nopriv_msg_people_search', 'msg_people_ajax_search' );
function msg_people_ajax_search(){
    $resp = array('esito' => 'ko');
    if(isset($_POST['cognome'])){

        $cognome = $_POST['cognome'];

        //busta SOAP presa da http://sviladweb.servizi.rai.it/ADWeb.asmx?op=GetpropertyUserByName
        $wsPeopleRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Body>
                <GetpropertyUserByName xmlns="http://adweb.servizi.rai.it/">
                  <LastName>'.$cognome.'</LastName>
                  <FirstName></FirstName>
                  <contest></contest>
                </GetpropertyUserByName>
              </soap12:Body>
            </soap12:Envelope>';

        $wsPeopleResp = postSoapReq(MPEOPLE_WS_URL, $wsPeopleRequest, MPEOPLE_WS_USER, MPEOPLE_WS_PASS);
        $respXML = str_ireplace(['soap:', 'soap:'], '', $wsPeopleResp); //porkaround per evitare di caricare il namespace (da una URL che a oggi non esiste...)
        $respXML = simplexml_load_string($respXML);
        $utenti = array();
        if(!empty($respXML->Body->GetpropertyUserByNameResponse->GetpropertyUserByNameResult->ElencoUtenti->Utente)){

            foreach($respXML->Body->GetpropertyUserByNameResponse->GetpropertyUserByNameResult->ElencoUtenti->Utente as $user){
                $utenti[] = array(
                    'nome' => (string)$user->Nome, // o $user->Nome->__toString()
                    'cognome' => (string)$user->Cognome,
                    'matricola' => (string)$user->Matricola,
                    'img' => msg_user_img((string)$user->Matricola),
                    'email' => (string)$user->Email,
                    'struttura' => (string)$user->Struttura,
                );
                //print "<div>Nome Cognome: {$user->Nome} {$user->Cognome}</div>";
            }
        } else {
            /* $utenti[] = array(
                 'nome' => "Matteo", // o $user->Nome->__toString()
                 'cognome' => "Boria",
                 'matricola' => "abc123",
                 'img' => msg_user_img('aaa'),
                 'email' => "m.boria@messagegroup.it",
                 'struttura' => "Message/Avanade",
             );*/
        }

        if($utenti){
            $resp['esito'] = 'ok';
            $resp['utenti'] = $utenti;
        }
    }
    wp_send_json($resp);
}

/*** funzioni di servizio ***/
/***
 *  SOAP
 * non uso la classe standard di php SoapClient (nè altre consolidate come nuSoap)
 * perchè non riesco a far passare l'autentica NTLM (la macchina da cui parto è RHEL, ma
 * il WS richiede un autentica Windows)
 * ho risolto facendo richieste "grezze" con CURL
 */

function postSoapReq($url, $request = '', $username = false , $password = false ) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/soap+xml', 'SOAPAction: ""'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if($request){
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    if( $username && $password ) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    }

    $buffer = curl_exec($ch);
    curl_close($ch);

    return $buffer;
}
