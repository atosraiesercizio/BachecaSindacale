<?php


$ws_user = 'srvapache1';
$ws_pass = 'apache11112015';
$ws_domain = 'ict.corp.rai.it';
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://svilmy.intranet.rai.it/api/raiplace/getinfomatr?m=103650",
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
    "keystring: 33540117672688738685",
  ),
));
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
curl_setopt($curl, CURLOPT_USERPWD, "srvapache1:apache11112015");

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
	header("Content-Type: application/json;charset=utf-8");
	echo $response;
}