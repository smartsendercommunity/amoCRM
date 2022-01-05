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
if ($input["timeEnd"] == NULL) {
    $result["state"] = false;
    $result["message"]["timeEnd"] = "timeEnd is missing";
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

// Подготовка данных задачи
if ($input["manager"] != NULL) {
    $amoManagers = json_decode(send_bearer($amo_url."/api/v4/users?limit=250", $access["token"]), true);
    if (is_array($amoManagers["_embedded"]["users"]) === true) {
        foreach ($amoManagers["_embedded"]["users"] as $oneManager) {
            if ($oneManager["email"] == $input["manager"]) {
                $taskData["responsible_user_id"] = $oneManager["id"];
                break;
            }
        }
    }
}
if ($input["userId"] != NULL) {
    if (file_exists('users.json') === true) {
        $users = json_decode(file_get_contents('users.json'), true);
    } else {
        $result["state"] = false;
        $result["message"]["userId"] = "user not found. Please, create user";
    }
    if ($users[$input["userId"]] != NULL) {
        $taskData["entity_id"] = $users[$input["userId"]];
        $taskData["entity_type"] = "contacts";
    } else {
        $result["state"] = false;
        $result["message"]["userId"] = "user not found. Please, create user";
    }
    if ($result["state"] === false) {
        http_response_code(422);
        echo json_encode($result);
        exit;
    }
} else if ($input["dealId"] != NULL) {
    $taskData["entity_id"] = $input["dealId"];
    $taskData["entity_type"] = "leads";
    settype($taskData["entity_id"], "int");
} else if ($input["contactId"] != NULL) {
    $taskData["entity_id"] = $input["contactId"];
    $taskData["entity_type"] = "contacts";
    settype($taskData["entity_id"], "int");
}
if ($input["complete"] == true) {
    $taskData["is_completed"] = true;
}
if ($input["type"] == "call") {
    $taskData["task_type_id"] = 1;
} else if ($input["type"] == "meet") {
    $taskData["task_type_id"] = 2;
}
$taskData["text"] = $input["text"];
$taskData["complete_till"] = strtotime($input["timeEnd"]);
if ($input["result"] != NULL) {
    $taskData["result"]["text"] = $input["result"];
}

// Создание задачи
$tasksData[] = $taskData;
$createTask = json_decode(send_bearer($amo_url."/api/v4/tasks", $access["token"], "POST", $tasksData), true);
if ($createTask["_embedded"]["tasks"][0]["id"] != NULL) {
    $result["task"] = $createTask["_embedded"]["tasks"][0];
    unset($result["task"]["_links"]);
} else {
    $result["error"] = $createTask;
}
$result["send"] = $taskData;
echo json_encode($result);




