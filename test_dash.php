<?php
session_start();
$_SESSION['role'] = 'patient';
$_SESSION['email'] = 'test@example.com';
$_SESSION['user_id'] = 1;
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require 'public/index.php';
