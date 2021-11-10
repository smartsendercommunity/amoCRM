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

include('connect.php');

if ($input["url"] == NULL) {
    $result["state"] = false;
    $result["message"]["url"] = "url is missing";
    http_response_code(422);
    echo json_encode($result);
    exit;
}

if (stripos($input["url"], $amo_url) !== false) {
    if ($input["type"] == NULL || $input["type"] == "GET") {
        echo send_bearer($input["url"], $access["token"]);
    } else {
        echo send_bearer($input["url"], $access["token"], $input["type"], $input["data"]);
    }
} else {
    if ($input["type"] == NULL || $input["type"] == "GET") {
        echo send_bearer($amo_url.$input["url"], $access["token"]);
    } else {
        echo send_bearer($amo_url.$input["url"], $access["token"], $input["type"], $input["data"]);
    }
}




