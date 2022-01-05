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
    if ($input["contactId"] == NULL) {
        if ($input["dealId"] == NULL) {
            $result["state"] = false;
            $result["message"]["userId"] = "userId or dealId is missing";
        }
    } else {
        $userId = $input["contactId"];
    }
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
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

$getDealFields = json_decode(send_bearer($amo_url."/api/v4/leads/custom_fields", $access["token"]), true);
if (is_array($getDealFields["_embedded"]["custom_fields"]) === true) {
    foreach ($getDealFields["_embedded"]["custom_fields"] as $oneDealField) {
        $dealField[$oneDealField["id"]] = $oneDealField["name"];
    }
}

if ($userId != NULL) {
    $getContacts = json_decode(send_bearer($amo_url."/api/v4/contacts/".$userId."?with=leads", $access["token"]), true);
    if (is_array($getContacts["_embedded"]["leads"]) === true) {
        foreach ($getContacts["_embedded"]["leads"] as $oneDeal) {
            $getDeals = json_decode(send_bearer($amo_url."/api/v4/leads/".$oneDeal["id"], $access["token"]), true);
            if (is_array($getDeals["custom_fields_values"]) === true) {
                foreach ($getDeals["custom_fields_values"] as $oneDealVariables) {
                    $getDeals["variables"][$dealField[$oneDealVariables["field_id"]]] = $oneDealVariables["values"][0]["value"];
                }
            }
            $getDeals["tags"] = $getDeals["_embedded"]["tags"];
            unset($getDeals["_embedded"]);
            unset($getDeals["custom_fields_values"]);
            unset($getDeals["_links"]);
            $result["deals"][] = $getDeals;
        }
    }
} else if ($input["dealId"] != NULL) {
    $getDeals = json_decode(send_bearer($amo_url."/api/v4/leads/".$input["dealId"], $access["token"]), true);
    if (is_array($getDeals["custom_fields_values"]) === true) {
        foreach ($getDeals["custom_fields_values"] as $oneDealVariables) {
            $getDeals["variables"][$dealField[$oneDealVariables["field_id"]]] = $oneDealVariables["values"][0]["value"];
        }
    }
    $getDeals["tags"] = $getDeals["_embedded"]["tags"];
    unset($getDeals["_embedded"]);
    unset($getDeals["custom_fields_values"]);
    unset($getDeals["_links"]);
    $result["deals"] = $getDeals;
}

echo json_encode($result);




