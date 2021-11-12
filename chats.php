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

// Проверка входящего запроса
if ($_GET["scope"] != $access["scope_id"]) {
    $result["state"] = false;
    $result["message"]["scope"] = "scope is not valid";
    echo json_encode($result);
    exit;
}
$userId = $input["conversation_id"];
$sendMessage["watermark"] = 1;
if ($input["type"] == "text") {
    $sendMessage["type"] = "text";
    $sendMessage["content"] = $input["text"];
} else if ($input["type"] == "picture") {
    $sendMessage["type"] = "picture";
    $sendMessage["media"] = $input["media"];
    if ($input["text"] != NULL) {
        $sendMessage2["watermark"] = 2;
        $sendMessage2["type"] = "text";
        $sendMessage2["content"] = $input["text"];
    }
} else if ($input["type"] == "file") {
    $sendMessage["type"] = "file";
    $sendMessage["media"] = $input["media"];
    if ($input["text"] != "") {
        $sendMessage2["watermark"] = 2;
        $sendMessage2["type"] = "text";
        $sendMessage2["content"] = $input["text"];
    }
}

$Send = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/send", $ss_token, "POST", $sendMessage), true);
$result["send1"] = $Send;
if ($sendMessage["type"] != "text" && $Send["error"]["code"] != NULL) {
    $sendMessage2["content"] = "Файл доступен по ссылке: ".$sendMessage["media"]."\n\n".$sendMessage2["content"];
    $sendMessage2["watermark"] = 2;
    $sendMessage2["type"] = "text";
}
if ($sendMessage2["watermark"] == 2) {
    $Send2 = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/send", $ss_token, "POST", $sendMessage2), true);
    $result["send2"] = $Send2;
}
send_forward(json_encode($result), $log_url);













