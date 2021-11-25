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
}
if ($input["fullName"] == NULL && $input["firstName"] == NULL && $input["lastName"] == NULL) {
    $result["state"] = false;
    $result["message"]["name"] = "one of the three fields (fullName, firstName, lastName) is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Подготовка данных контакта
if (file_exists('users.json') === true) {
    $users = json_decode(file_get_contents('users.json'), true);
}
if ($users[$input["userId"]] != NULL) {
    $userAmoId = $users[$input["userId"]];
}
if ($input["fullName"] != NULL) {
    $userData["name"] = $input["fullName"];
}
if ($input["firstName"] != NULL) {
    $userData["first_name"] = $input["firstName"];
}
if ($input["lastName"] != NULL) {
    $userData["last_name"] = $input["lastName"];
}
if ($input["manager"] != NULL) {
    $amoManagers = json_decode(send_bearer($amo_url."/api/v4/users?limit=250", $access["token"]), true);
    if (is_array($amoManagers["_embedded"]["users"]) === true) {
        foreach ($amoManagers["_embedded"]["users"] as $oneManager) {
            if ($oneManager["email"] == $input["manager"]) {
                $userData["responsible_user_id"] = $oneManager["id"];
                break;
            }
        }
    }
}
if (is_array($input["fields"]) === true) {
    $amoContactFields = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields?limit=250", $access["token"]), true);
    if (is_array($amoContactFields["_embedded"]["custom_fields"]) === true) {
        foreach ($amoContactFields["_embedded"]["custom_fields"] as $oneContactFields) {
            $contactFields[$oneContactFields["name"]] = $oneContactFields["id"];
            $contactFieldsType[$oneContactFields["name"]] = $oneContactFields["type"];
        }
    }
    foreach ($input["fields"] as $fieldsKey => $fieldsValue) {
        if ($contactFields[$fieldsKey] != NULL) {
            $customFields["field_id"] = $contactFields[$fieldsKey];
            if ($contactFieldsType[$fieldsKey] == "numeric") {
                $customFields["values"][0]["value"] = str_replace(" ", "", str_replace(",", ".", $fieldsValue));
                settype($customFields["values"][0]["value"], "float");
            } else if ($contactFieldsType[$fieldsKey] == "date" || $contactFieldsType[$fieldsKey] == "date_time" || $contactFieldsType[$fieldsKey] == "birthday") {
                $customFields["values"][0]["value"] = strtotime($fieldsValue);
            } else if ($contactFieldsType[$fieldsKey] == "multiselect" && is_array($fieldsValue)) {
                foreach ($fieldsValue as $oneFieldsValue) {
                    $customFields["values"][]["value"] = $oneFieldsValue;
                }
            } else {
                $customFields["values"][0]["value"] = $fieldsValue;
            }
            $userData["custom_fields_values"][] = $customFields;
            unset ($customFields);
        }
    }
}
$customFields["field_id"] = $access["ssId"];
$customFields["values"][0]["value"] = $input["userId"];
$userData["custom_fields_values"][] = $customFields;
unset ($customFields);
if (is_array($input["tags"]) === true) {
    foreach ($input["tags"] as $oneTag) {
        $userData["_embedded"]["tags"][]["name"] = $oneTag;
    }
}

// Обновление/создание контакта в amoCRM
if ($userAmoId != NULL) {
    $updateContact = json_decode(send_bearer($amo_url."/api/v4/contacts/".$userAmoId, $access["token"], "PATCH", $userData), true);
    $result["update"] = $updateContact;
    $result["send"] = $userData;
} else {
    $usersData[] = $userData;
    $createContact = json_decode(send_bearer($amo_url."/api/v4/contacts", $access["token"], "POST", $usersData), true);
    if ($createContact["_embedded"]["contacts"][0]["id"] != NULL) {
        $users[$input["userId"]] = $createContact["_embedded"]["contacts"][0]["id"];
        file_put_contents("users.json", json_encode($users));
    }
    $result["create"] = $createContact;
    $result["send"] = $usersData;
}

echo json_encode($result);




