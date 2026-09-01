<?php
// Legacy view redirected to dashboard
header("Location: /" . (defined('PROJECT_DIR') ? PROJECT_DIR : 'Citilife-System') . "/dashboard");
exit;