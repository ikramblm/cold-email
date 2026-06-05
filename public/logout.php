<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::logout();
header('Location: ' . APP_URL . '/login.php');
exit;
