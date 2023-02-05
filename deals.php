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
        $dealData["_embedded"]["contacts"][0]["id"] = $users[$input["userId"]];
        settype($dealData["_embedded"]["contacts"][0]["id"], "int");
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

// Подготовка данных сделки
if ($input["name"] != NULL) {
    $dealData["name"] = $input["name"];
}
if ($input["price"] != NULL) {
    $dealData["price"] = str_replace(" ", "", str_replace(",", ".", $input["price"]));
    settype($dealData["price"], "int");
}
if ($input["pipeline"] != NULL) {
    $getPipelines = json_decode(send_bearer($amo_url."/api/v4/leads/pipelines", $access["token"]), true);
    if (is_array($getPipelines["_embedded"]["pipelines"]) === true) {
        foreach ($getPipelines["_embedded"]["pipelines"] as $onePipeline) {
            if ($onePipeline["name"] == $input["pipeline"]) {
                $dealData["pipeline_id"] = $onePipeline["id"];
                if ($input["status"] != NULL && is_array($onePipeline["_embedded"]["statuses"]) === true) {
                    foreach ($onePipeline["_embedded"]["statuses"] as $oneStatus) {
                        if ($oneStatus["name"] == $input["status"]) {
                            $dealData["status_id"] = $oneStatus["id"];
                            break 2;
                        }
                    }
                } else {
                    break;
                }
            }
        } 
    }
}
if ($input["manager"] != NULL) {
    $amoManagers = json_decode(send_bearer($amo_url."/api/v4/users?limit=250", $access["token"]), true);
    if (is_array($amoManagers["_embedded"]["users"]) === true) {
        foreach ($amoManagers["_embedded"]["users"] as $oneManager) {
            if ($oneManager["email"] == $input["manager"]) {
                $dealData["responsible_user_id"] = $oneManager["id"];
                break;
            }
        }
    }
}
if (is_array($input["fields"]) === true) {
    $amoDealFields = json_decode(send_bearer($amo_url."/api/v4/leads/custom_fields?limit=250", $access["token"]), true);
    if (is_array($amoDealFields["_embedded"]["custom_fields"]) === true) {
        foreach ($amoDealFields["_embedded"]["custom_fields"] as $oneDealFields) {
            $dealFields[$oneDealFields["name"]] = $oneDealFields["id"];
            $dealFieldsType[$oneDealFields["name"]] = $oneDealFields["type"];
        }
    }
    foreach ($input["fields"] as $fieldsKey => $fieldsValue) {
        if ($dealFields[$fieldsKey] != NULL) {
            $customFields["field_id"] = $dealFields[$fieldsKey];
            if ($dealFieldsType[$fieldsKey] == "numeric") {
                $customFields["values"][0]["value"] = str_replace(" ", "", str_replace(",", ".", $fieldsValue));
                settype($customFields["values"][0]["value"], "float");
            } else if ($dealFieldsType[$fieldsKey] == "date" || $dealFieldsType[$fieldsKey] == "date_time" || $dealFieldsType[$fieldsKey] == "birthday") {
                $customFields["values"][0]["value"] = strtotime($fieldsValue);
            } else if ($dealFieldsType[$fieldsKey] == "multiselect" && is_array($fieldsValue)) {
                foreach ($fieldsValue as $oneFieldsValue) {
                    $customFields["values"][]["value"] = $oneFieldsValue;
                }
            } else {
                $customFields["values"][0]["value"] = $fieldsValue;
            }
            $dealData["custom_fields_values"][] = $customFields;
            unset ($customFields);
        }
    }
}
if (is_array($input["tags"]) === true) {
    if ($input["clearTags"] !== true && $input["dealId"] != NULL) {
        $getDealData = json_decode(send_bearer($amo_url."/api/v4/leads/".$input["dealId"], $access["token"]), true);
        $dealData["_embedded"]["tags"] = $getDealData["_embedded"]["tags"];
        foreach ($dealData["_embedded"]["tags"] as &$tempTag) {
            unset($tempTag["color"];
        }
    }
    foreach ($input["tags"] as $oneTag) {
        $dealData["_embedded"]["tags"][]["name"] = $oneTag;
    }
}

// Создание/обновление сделки
if ($input["dealId"] != NULL) {
    $updateDeal = json_decode(send_bearer($amo_url."/api/v4/leads/".$input["dealId"], $access["token"], "PATCH", $dealData), true);
    $result["update"] = $updateDeal;
    $result["send"] = $dealData;
} else {
    $dealsData[] = $dealData;
    $createDeal = json_decode(send_bearer($amo_url."/api/v4/leads", $access["token"], "POST", $dealsData), true);
    $result["create"] = $createDeal;
    $result["send"] = $dealsData;
}

echo json_encode($result);



