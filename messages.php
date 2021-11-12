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

// Проверка доступа к amojo
if ($access["scope_id"] == NULL) {
    $result["state"] = false;
    $result["message"]["amojo"] = "amojo is not connected";
    http_response_code(403);
    echo json_encode($result);
    exit;
}
if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
} else {
    if (file_exists('users.json') === true) {
        $users = json_decode(file_get_contents('users.json'), true);
    }
    if ($users[$input["userId"]] != NULL) {
        $user["amoid"] = $users[$input["userId"]];
    } else {
        $result["state"] = false;
        $result["message"]["userId"] = "user not found. Please, create user";
    }
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

$userData = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"], $ss_token), true);
if ($userData["error"] != NULL) {
    http_response_code($userData["error"]["code"]);
    echo $userDataJSON;
    exit;
}
$userMessage = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/messages?page=1&limitation=20", $ss_token), true);
if (is_array($userMessage["error"]) === true) {
    $sendMessage["payload"]["message"]["type"] = "text";
    $sendMessage["payload"]["message"]["text"] = "Невозможно прочитать сообщение. Проблемы в API Smart Sender, или сообщения у пользователя отсутствуют";
} else if ($userMessage["collection"][0]["content"]["type"] == "text") {
    $sendMessage["payload"]["message"]["type"] = "text";
    $sendMessage["payload"]["message"]["text"] = $userMessage["collection"][0]["content"]["resource"]["parameters"]["content"];
    $sendMessage["payload"]["message"]["keyboard"]["mode"] = "inline";
    $sendMessage["payload"]["message"]["keyboard"]["buttons"][][]["text"] = "inline";
} else {
    $sendMessage["payload"]["message"]["type"] = "text";
    $sendMessage["payload"]["message"]["text"] = "Невозможно прочитать сообщение. Сообщение у пользователя отсутствует или не является текстом";
}
$sendMessage["payload"]["msgid"] = mt_rand(10000000000000, 99999999999999);
settype ($sendMessage["payload"]["msgid"], "string");
$sendMessage["payload"]["silent"] = false;
$sendMessage["account_id"] = $access["amojo_id"];
$sendMessage["event_type"] = "new_message";
$sendMessage["payload"]["timestamp"] = time();
$sendMessage["payload"]["conversation_id"] = $input["userId"];
$sendMessage["payload"]["sender"]["id"] = $input["userId"];
$sendMessage["payload"]["sender"]["name"] = $userData["fullName"];
$sendMessage["payload"]["sender"]["avatar"] = $userData["photo"];


$createChat["conversation_id"] = $input["userId"];
$createChat["user"]["id"] = $input["userId"];
$createChat["user"]["ref_id"] = $user["amoid"];
$createChat["user"]["name"] = $userData["fullName"];
$createChat["user"]["avatar"] = $userData["photo"];

$sendCreateChat = json_decode(send_sha1_signature("POST", json_encode($createChat), "https://amojo.amocrm.ru/v2/origin/custom/".$access["scope_id"]."/chats", $amojo_secret), true);


$sendMessage["payload"]["sender"]["ref_id"] = $sendCreateChat["user"]["id"];

$addChat[0]["chat_id"] = $sendCreateChat["id"];
$addChat[0]["contact_id"] = $user["amoid"];
settype($addChat[0]["contact_id"], "int");
$syncChat = json_decode(send_bearer($amo_url."/api/v4/contacts/chats", $access["token"], "POST", $addChat), true);
$result["syncChat"] = $syncChat;
$result["addChat"] = $addChat;

$result["createChat"] = $sendCreateChat;

$SendMessage = json_decode(send_sha1_signature("POST", json_encode($sendMessage), "https://amojo.amocrm.ru/v2/origin/custom/".$access["scope_id"], $amojo_secret), true);
$result["sendMessage"] = $sendMessage;
$result["SendMessage"] = $SendMessage;

if ($SendMessage["new_message"]["msgid"] != NULL) {
    $otvet["state"] = true;
} else {
    $otvet["state"] = false;
}
echo json_encode($result);







