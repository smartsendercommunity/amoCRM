<?php

// v1   10.11.2021
// Powered by M-Soft
// https://t.me/mufik

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
include ('config.php');
include ('functions.php');

$log["time"] = time();
$log["date"] = date("Y-m-d H:i:s");
$log["request"]["get"] = $_GET;
$log["request"]["post"] = $_POST;
$log["request"]["json"] = $input;

send_forward(json_encode($log), $log_url);

//-------------

// Получение (обновление) Access Token
if (file_exists("access.json") === true) {
    $accessJSON = file_get_contents("access.json");
    $access = json_decode ($accessJSON, true);
    if ($access["expired"] < (time()+100)) {
        $amo_send["client_id"] = $amo_id;
        $amo_send["client_secret"] = $amo_key;
        $amo_send["grant_type"] = 'refresh_token';
        $amo_send["refresh_token"] = $access["refresh"];
        $amo_send["redirect_uri"] = $amo_uri;
        $oauth = send_forward(json_encode($amo_send), $amo_url."/oauth2/access_token");
        $amo_access = json_decode($oauth, true);
        if ($amo_access["token_type"] == "Bearer") {
            $access["token"] = $amo_access["access_token"];
            $access["refresh"] = $amo_access["refresh_token"];
            $access["expired"] = time() + $amo_access["expires_in"];
            file_put_contents("access.json", json_encode($access));
        } else {
            $result["state"] = false;
            $result["message"] = "Authorization error. Please, delete is 'access.json' and update config data";
            echo json_encode($result);
            exit;
        }
    }
    $accountJSON = send_bearer($amo_url.'/api/v4/account', $access["token"]);
    $account_info = json_decode($accountJSON, true);
    $access["account"] = $account_info["name"];
    $access["id"] = $account_info["id"];
    $access["amo_id"] = $account_info["uuid"];
} else {
    $amo_send["client_id"] = $amo_id;
    $amo_send["client_secret"] = $amo_key;
    $amo_send["grant_type"] = 'authorization_code';
    $amo_send["code"] = $amo_code;
    $amo_send["redirect_uri"] = $amo_uri;
    $oauth = send_forward(json_encode($amo_send), $amo_url."/oauth2/access_token");
    $amo_access = json_decode($oauth, true);
    if ($amo_access["token_type"] == "Bearer") {
        $access["token"] = $amo_access["access_token"];
        $access["refresh"] = $amo_access["refresh_token"];
        $access["expired"] = time() + $amo_access["expires_in"];
        $accountJSON = send_bearer($amo_url.'/api/v4/account', $access["token"]);
        $account_info = json_decode($accountJSON, true);
        $access["account"] = $account_info["name"];
        $access["id"] = $account_info["id"];
        $access["amo_id"] = $account_info["uuid"];
        file_put_contents("access.json", json_encode($access));
    } else {
        $result["state"] = false;
        $result["message"] = "Authorization error. Please, delete is 'access.json' and update config data";
        echo json_encode($result);
        exit;
    }
}

// Создание дополнительного поля контакта - ssId
if ($access["ssId"] == NULL) {
    $createFields["type"] = "text";
    $createFields["name"] = "ssId";
    $createFields["is_api_only"] = true;
    $resultCreate = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields", $access["token"], "POST", $createFields), true);
    $access["ssId"] = $resultCreate["id"];
    file_put_contents("access.json", json_encode($access));
} else {
    $getFields = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields/".$access["ssId"], $access["token"]), true);
    if ($access["ssId"] != $getFields["id"]) {
        $createFields["type"] = "text";
        $createFields["name"] = "ssId";
        $createFields["is_api_only"] = true;
        $resultCreate = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields", $access["token"], "POST", $createFields), true);
        $access["ssId"] = $resultCreate["id"];
        file_put_contents("access.json", json_encode($access));
    }
}

if (stripos($_SERVER["PHP_SELF"], "connect") !== false) {
    if ($access["account"] != NULL) {
        $result["state"] = true;
        $result["message"] = $access["account"].": connect allready";
    } else {
        $result["state"] = false;
        $result["message"] = "Error connect. Please, delete is 'access.json' and update config data";
    }
    echo json_encode($result);
}








