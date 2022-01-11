<?php

// Данные интеграции с amoCRM
$amo_key = "";
$amo_id = "";
$amo_code = "";
$amo_url = "";
$amo_uri = "https://exemple.com";
$amojo_channel = "";
$amojo_secret = "";
$ss_token = "";

// Сервысные данные
$dir = dirname($_SERVER["PHP_SELF"]);
$url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $dir;
$url = explode("?", $url);
$url = $url[0];
