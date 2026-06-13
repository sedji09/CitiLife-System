<?php
require 'config/database.php';
session_start();
$_SESSION['user_id'] = 62;
session_write_close();
$cookie = 'PHPSESSID=' . session_id();
$url = 'http://localhost/CitiLife-System/app/Api/messages.php?action=search_staff&q=';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
