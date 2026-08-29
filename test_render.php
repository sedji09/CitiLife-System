<?php
session_start();
define('PROJECT_DIR', 'CitiLife-System');
$_SESSION['role']='patient';
$_SESSION['user_id']=1;
$_SESSION['email']='test@example.com';
require 'config/database.php';
require 'app/Models/UserModel.php';
require 'app/Models/PatientModel.php';
require 'app/Models/CaseModel.php';
ob_start();
require 'views/pages/patient/dashboard.view.php';
$out = ob_get_clean();
if(empty($out)) echo 'empty'; else echo 'success';