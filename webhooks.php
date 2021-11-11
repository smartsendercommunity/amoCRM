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
if ($_POST["account"]["id"] != $access["id"]) {
    $result["state"] = false;
    $result["message"]["account"] = "this account not connected";
    echo json_encode($result);
    exit;
}
if ($_POST["leads"] == NULL) {
    $result["state"] = false;
    $result["leads"] = "Only leads are supported";
    echo json_encode($result);
    exit;
}

// Получение данных о сделке/контакте
foreach ($_POST["leads"] as $leads) {
    $dealId = $leads[0]["id"];
    break;
}
$dealData = json_decode(send_bearer($amo_url."/api/v4/leads/".$dealId."?with=contacts", $access["token"]), true);
if ($dealData["_embedded"]["contacts"][0]["id"] != NULL) {
    $contactData = json_decode(send_bearer($amo_url."/api/v4/contacts/".$dealData["_embedded"]["contacts"][0]["id"], $access["token"]), true);
} else {
    sleep(10);
    $dealData = json_decode(send_bearer($amo_url."/api/v4/leads/".$dealId."?with=contacts", $access["token"]), true);
    if ($dealData["_embedded"]["contacts"][0]["id"] != NULL) {
        $contactData = json_decode(send_bearer($amo_url."/api/v4/contacts/".$dealData["_embedded"]["contacts"][0]["id"], $access["token"]), true);
    }
}
if ($contactData == NULL) {
    $result["state"] = false;
    $result["message"]["contact"] = "no contacts in leads";
    echo json_encode ($result);
    exit;
} else {
    if (is_array($contactData["custom_fields_values"]) === true) {
        foreach ($contactData["custom_fields_values"] as $contactFields) {
            if ($contactFields["field_id"] == $access["ssId"]) {
                $userId = $contactFields["values"][0]["value"];
                break;
            }
        }
    }
}
if ($userId == NULL || $userId == "") {
    $result["state"] = false;
    $result["message"]["contant"] = "this contact is not Smart Sender";
    echo json_encode($result);
    exit;
}

// Подготовка данных и отправка в Smart Sender
if (is_array($_GET["addTags"]) === true) {
    foreach ($_GET["addTags"] as $addTags) {
        $tagsData = json_decode(send_bearer("https://api.smartsender.com/v1/tags?page=1&limitation=20&term=".$addTags, $ss_token), true);
        if (is_array($tagsData["collection"]) === true) {
            foreach ($tagsData["collection"] as $tagsSS) {
                if ($tagsSS["name"] == $addTags) {
                    $result["addTags"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/tags/".$tagsSS["id"], $ss_token, "POST"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["delTags"]) === true) {
    foreach ($_GET["delTags"] as $delTags) {
        $tagsData = json_decode(send_bearer("https://api.smartsender.com/v1/tags?page=1&limitation=20&term=".$delTags, $ss_token), true);
        if (is_array($tagsData["collection"]) === true) {
            foreach ($tagsData["collection"] as $tagsSS) {
                if ($tagsSS["name"] == $delTags) {
                    $result["delTags"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/tags/".$tagsSS["id"], $ss_token, "DELETE"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["addFunnels"]) === true) {
    foreach ($_GET["addFunnels"] as $addFunnels) {
        $funnelsData = json_decode(send_bearer("https://api.smartsender.com/v1/funnels?page=1&limitation=20&term=".$addFunnels, $ss_token), true);
        if (is_array($funnelsData["collection"]) === true) {
            foreach ($funnelsData["collection"] as $funnelsSS) {
                if ($funnelsSS["name"] == $addFunnels) {
                    $result["addFunnels"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/funnels/".$funnelsSS["serviceKey"], $ss_token, "POST"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["delFunnels"]) === true) {
    foreach ($_GET["delFunnels"] as $delFunnels) {
        $funnelsData = json_decode(send_bearer("https://api.smartsender.com/v1/funnels?page=1&limitation=20&term=".$delFunnels, $ss_token), true);
        if (is_array($funnelsData["collection"]) === true) {
            foreach ($funnelsData["collection"] as $funnelsSS) {
                if ($funnelsSS["name"] == $delFunnels) {
                    $result["delFunnels"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/funnels/".$funnelsSS["serviceKey"], $ss_token, "DELETE"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["triggers"]) === true) {
    foreach ($_GET["triggers"] as $triggers) {
        $result["triggers"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/fire?name=".$triggers, $ss_token, "POST"), true);
    }
}
if (is_array($_GET["variables"]) === true) {
    // Получаем реальные имена полей...
    $contactFieldsData = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields", $access["token"]), true);
    if (is_array($contactFieldsData["_embedded"]["custom_fields"]) === true) {
        foreach ($contactFieldsData["_embedded"]["custom_fields"] as $oneContactFieldsData) {
            $contactFieldsName[$oneContactFieldsData["id"]] = $oneContactFieldsData["name"];
        }
    }
    $dealFieldsData = json_decode(send_bearer($amo_url."/api/v4/leads/custom_fields", $access["token"]), true);
    if (is_array($dealFieldsData["_embedded"]["custom_fields"]) === true) {
        foreach ($dealFieldsData["_embedded"]["custom_fields"] as $oneDealFieldsData) {
            $dealFieldsName[$oneDealFieldsData["id"]] = $oneDealFieldsData["name"];
        }
    }
    // Генерируем полный список полей контакта/сделки (При совпадении названия полей, приоритет за полем из сделки) P.S. Для смены приоритета, поменяйте следующие две конструкции "foreach" местами
    if (is_array($contactData["custom_fields_values"]) === true) {
        foreach ($contactData["custom_fields_values"] as $contactFields) {
            $variables[$contactFieldsName[$contactFields["field_id"]]] = $contactFields["values"][0]["value"];
        }
    }
    if (is_array($dealData["custom_fields_values"]) === true) {
        foreach ($dealData["custom_fields_values"] as $dealFields) {
            $variables[$dealFieldsName[$dealFields["field_id"]]] = $dealFields["values"][0]["value"];
        }
    }
    // Отбор данных для передачи в Smart Sender (незаполненные поля в amoCRM будут очищены в Smart Sender)
    foreach ($_GET["variables"] as $varKey => $varValue) {
        $sendVar["values"][$varKey] = $variables[$varValue];
    }
    $updateUser = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId, $ss_token, "PUT", $sendVar), true);
    $result["send"] = $sendVar;
    $result["update"] = $updateUser;
}
send_forward(json_encode($result), $log_url);












