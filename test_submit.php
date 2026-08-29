<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['case_id'] = 1;
$_POST['amount'] = 100;
$_POST['payment_method'] = 'Cash';

session_start();
$_SESSION['patient_id'] = 1;
$_SESSION['user_id'] = 1;

require 'app/api/submit_payment.php';
