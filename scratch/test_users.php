<?php
require 'c:\xampp\htdocs\CitiLife-System\app\Core\Database.php';
require 'c:\xampp\htdocs\CitiLife-System\app\Models\UserModel.php';

$pdo = (new \App\Core\Database())->connect();
$m = new \App\Models\UserModel($pdo);
$users = $m->getAllStaffUsers();
foreach($users as $u) {
  echo $u['email'] . ' -> ' . $u['role'] . "\n";
}
