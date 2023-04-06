<?php

// v1   10.11.2021
// Powered by Smart Sender
// https://smartsender.com

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
if ($input["contactId"] == NULL ){
    if ($input["userId"] == NULL) {
        $result["state"] = false;
        $result["message"]["userId"] = "userId is missing";
    } else {
        if (file_exists('users.json') === true) {
            $users = json_decode(file_get_contents('users.json'), true);
        }
        if ($users[$input["userId"]] != NULL) {
            $userId = $users[$input["userId"]];
        } else {
            $result["state"] = false;
            $result["message"]["userId"] = "user not found. Please, create user";
        }
    }
} else {
    $userId = $input["contactId"];
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

$getContacts = json_decode(send_bearer($amo_url."/api/v4/contacts/".$userId."?with=leads", $access["token"]), true);
$getContactFields = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields", $access["token"]), true);
if (is_array($getContactFields["_embedded"]["custom_fields"]) === true) {
    foreach ($getContactFields["_embedded"]["custom_fields"] as $oneContactField) {
        $contactField[$oneContactField["id"]] = $oneContactField["name"];
    }
}
if (is_array($getContacts["custom_fields_values"]) === true) {
    foreach ($getContacts["custom_fields_values"] as $oneContactVariables) {
        $getContacts["variables"][$contactField[$oneContactVariables["field_id"]]] = $oneContactVariables["values"][0]["value"];
    }
}
$getContacts["tags"] = $getContacts["_embedded"]["tags"];
$getContacts["deals"] = $getContacts["_embedded"]["leads"];
if (is_array($getContacts["deals"]) === true) {
    foreach ($getContacts["deals"] as &$deals) {
        unset($deals["_links"]);
    }
}
unset($getContacts["_embedded"]);
unset($getContacts["custom_fields_values"]);
unset($getContacts["_links"]);
$result["user"] = $getContacts;
echo json_encode($result);




