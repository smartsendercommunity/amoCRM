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

// Проверка наличия всех обезательных полей
if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
} else {
    if (file_exists('users.json') === true) {
        $users = json_decode(file_get_contents('users.json'), true);
    }
    if ($users[$input["userId"]] != NULL) {
        $userAmoId = $users[$input["userId"]];
    } else {
        $result["state"] = false;
        $result["message"]["userId"] = "user not found. Please, create user";
    }
}
if ($input["url"] == NULL) {
    $result["state"] = false;
    $result["message"]["name"] = "url is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Подготовка данных ссылки
$urlData["url"] = $input["url"];
$urlData["metadata"]["entity_type"] = "contacts";
$urlData["metadata"]["entity_id"] = $userAmoId;
$urlsData[] = $urlData;
echo send_bearer($amo_url."/api/v4/short_links", $access["token"], "POST", $urlsData);





