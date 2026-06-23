<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Models/UserModel.php';
$userModel = new UserModel($pdo);
$user = $userModel->getUserById(62);
var_dump($user);
