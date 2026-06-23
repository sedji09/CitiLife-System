<?php
require 'config/database.php';
require 'vendor/autoload.php';
$f = new FeedbackModel($pdo);
$f->ensureSchema();
echo 'Schema created successfully';
