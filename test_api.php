<?php
session_start();
$_SESSION['user_id'] = 1;
$_GET['action'] = 'search_staff';
$_GET['q'] = 'ma';
require 'app/Api/messages.php';
