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

$input = json_decode(file_get_contents('php://input'), true);
include ('config.php');
include ('functions.php');

//-------------

// Получение (обновление) Access Token
if (file_exists("access.json") === true) {
    $accessJSON = file_get_contents("access.json");
    $access = json_decode ($accessJSON, true);
    if ($access["expired"] < (time()+100)) {
        $amo_send["client_id"] = $amo_id;
        $amo_send["client_secret"] = $amo_key;
        $amo_send["grant_type"] = 'refresh_token';
        $amo_send["refresh_token"] = $access["refresh"];
        $amo_send["redirect_uri"] = $amo_uri;
        $oauth = send_forward(json_encode($amo_send), $amo_url."/oauth2/access_token");
        $amo_access = json_decode($oauth, true);
        if ($amo_access["token_type"] == "Bearer") {
            $access["token"] = $amo_access["access_token"];
            $access["refresh"] = $amo_access["refresh_token"];
            $access["expired"] = time() + $amo_access["expires_in"];
            file_put_contents("access.json", json_encode($access));
        } else {
            $result["state"] = false;
            $result["message"] = "Authorization error. Please, delete is 'access.json' and update config data";
            echo json_encode($result);
            exit;
        }
    }
    $accountJSON = send_bearer($amo_url.'/api/v4/account?with=amojo_id', $access["token"]);
    $account_info = json_decode($accountJSON, true);
    $access["account"] = $account_info["name"];
    $access["id"] = $account_info["id"];
    $access["amojo_id"] = $account_info["amojo_id"];
    file_put_contents("access.json", json_encode($access));
} else {
    $amo_send["client_id"] = $amo_id;
    $amo_send["client_secret"] = $amo_key;
    $amo_send["grant_type"] = 'authorization_code';
    $amo_send["code"] = $amo_code;
    $amo_send["redirect_uri"] = $amo_uri;
    $oauth = send_forward(json_encode($amo_send), $amo_url."/oauth2/access_token");
    $amo_access = json_decode($oauth, true);
    if ($amo_access["token_type"] == "Bearer") {
        $access["token"] = $amo_access["access_token"];
        $access["refresh"] = $amo_access["refresh_token"];
        $access["expired"] = time() + $amo_access["expires_in"];
        $accountJSON = send_bearer($amo_url.'/api/v4/account?with=amojo_id', $access["token"]);
        $account_info = json_decode($accountJSON, true);
        $access["account"] = $account_info["name"];
        $access["id"] = $account_info["id"];
        $access["amojo_id"] = $account_info["amojo_id"];
        file_put_contents("access.json", json_encode($access));
    } else {
        $result["state"] = false;
        $result["message"] = "Authorization error. Please, delete is 'access.json' and update config data";
        echo json_encode($result);
        exit;
    }
}

// Создание дополнительного поля контакта - ssId
if ($access["ssId"] == NULL) {
    $createFields["type"] = "text";
    $createFields["name"] = "ssId";
    $createFields["is_api_only"] = true;
    $resultCreate = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields", $access["token"], "POST", $createFields), true);
    $access["ssId"] = $resultCreate["id"];
    file_put_contents("access.json", json_encode($access));
} else {
    $getFields = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields/".$access["ssId"], $access["token"]), true);
    if ($getFields["status"] == "404") {
        $createFields["type"] = "text";
        $createFields["name"] = "ssId";
        $createFields["is_api_only"] = true;
        $resultCreate = json_decode(send_bearer($amo_url."/api/v4/contacts/custom_fields", $access["token"], "POST", $createFields), true);
        $access["ssId"] = $resultCreate["id"];
        file_put_contents("access.json", json_encode($access));
    }
}



if (stripos($_SERVER["PHP_SELF"], "connect") !== false) {
    if ($access["account"] != NULL) {
        $result["state"] = true;
        $result["message"] = $access["account"].": connect allready";
    } else {
        $result["state"] = false;
        $result["message"] = "Error connect. Please, delete is 'access.json' and update config data";
    }
    echo json_encode($result);

    echo PHP_EOL.PHP_EOL;
    if ($access["scope_id"] == NULL) {
        if ($access["amojo_id"] == NULL || $amojo_channel == NULL) {
            echo "  Чтобы получить доступ к чатам в amoCRM отправте в поддержку следующее сообщение:".PHP_EOL.PHP_EOL;
            echo "
    Здраствуйте. Предоставте пожалуйста доступ к чатам AMOJO через мою интеграцию. 
    https://www.amocrm.ru/developers/content/chats/chat-capabilities#chats-cap-channel-register
        
    1. Smart Sender
    2. ".$url."/chats.php?scope=:scope_id
    3. Аккаунт: ".$access["id"]."
    4. Не включать
    5. (Укажите свою почту)
    6. ".$url."/logo.svg
    7. ".$amo_id."
    8. Для личного использования";
        } else {
            $amojo_send["account_id"] = $access["amojo_id"];
            $amojo_send["title"] = "Smart Sender";
            $amojo_send["hook_api_version"] = "v1";
            $connect = json_decode(send_sha1_signature("POST", json_encode($amojo_send), "https://amojo.amocrm.ru/v2/origin/custom/".$amojo_channel."/connect", $amojo_secret), true);
            if ($connect["scope_id"] != NULL) {
                $access["scope_id"] = $connect["scope_id"];
                file_put_contents("access.json", json_encode($access));
                echo "  Доступ к чатам amojo успешно получен. Теперь Вы можете передавать сообщения пользователей в amoCRM";
            } else {
                echo "  Ошибка получения доступа к чатам amojo. Проверте данные amojo в файле config.php";
            }
        }
    } else {
        echo "  Доступ к чатам в amoCRM имеется";
    }
}









