<?php
require 'vendor/autoload.php';
$m = new PHPMailer\PHPMailer\PHPMailer();
$m->isSMTP();
$m->Host = 'smtp.gmail.com';
$m->Port = 587;
$m->SMTPSecure = 'tls';
$m->SMTPAuth = true;
$m->Username = 'citilifediagnosticcenter26@gmail.com';
$m->Password = 'vimk temr ldcc menb';
$m->SMTPDebug = 2;
if ($m->smtpConnect()) {
    echo "SUCCESS";
} else {
    echo "FAILED";
}
