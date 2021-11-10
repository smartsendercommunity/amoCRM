<?php

/////////////////////////////////////////////////
////////////  F U N K T I O N S  /////////////////
/////////////////////////////////////////////////

{
function send_forward($inputJSON, $link){
	
$request = 'POST';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function send_sha1_signature($request, $inputJSON, $link, $amojo_key){
	
$signature = hash_hmac('sha1', $inputJSON, $amojo_key);	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Cache-Control: no-cache', 'Content-Type: application/json', 'X-Signature: '.$signature)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function send_bearer($url, $token, $type = "GET", $param = []){
	
		
$descriptor = curl_init($url);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('User-Agent: M-Soft Integration', 'Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function send_request($url, $header, $type = 'GET', $param = []) {
    $descriptor = curl_init($url);
    
    if ($type != "GET") {
        curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
        $header[] = 'Content-Type: application/json';
    }
    $header[] = 'User-Agent: Soft-M(https://api.soft-m.ml)';
    
    curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($descriptor, CURLOPT_HTTPHEADER, $header); 
    curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
    
    $itog = curl_exec($descriptor);
    //$itog["code"] = curl_getinfo($descriptor, CURLINFO_RESPONSE_CODE);
    curl_close($descriptor);
    return $itog;
    
}
}

/////////////////////////////////////////////////
/////////////////////////////////////////////////