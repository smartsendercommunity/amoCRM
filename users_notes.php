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
if ($input["text"] == NULL) {
    $result["state"] = false;
    $result["message"]["text"] = "text is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Подготовка и отправка примечания
$note["note_type"] = "common";
$note["params"]["text"] = $input["text"];
$notes[] = $note;
if ($input["dealId"] != NULL) {
    $addNotes = json_decode(send_bearer($amo_url."/api/v4/leads/".$input["dealId"]."/notes", $access["token"], "POST", $notes), true);
} else {
    $addNotes = json_decode(send_bearer($amo_url."/api/v4/contacts/".$userId."/notes", $access["token"], "POST", $notes), true);
}

if ($addNotes["_embedded"]["notes"][0]["id"] != NULL) {
    $result["notes"] = $addNotes["_embedded"]["notes"][0];
    unset($result["notes"]["_links"]);
} else {
    $result = $addNotes;
}

echo json_encode($result);
