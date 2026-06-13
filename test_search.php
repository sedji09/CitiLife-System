<?php
$_GET['action'] = 'search_staff';
$_GET['q'] = 'bongabon';
session_start();
$_SESSION['user_id'] = 62;
require 'app/Api/messages.php';
