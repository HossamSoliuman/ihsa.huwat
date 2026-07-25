<?php
require_once __DIR__ . '/../config/config.php';
logoutUser();
header('Location: ' . BASE_URL . '/login.php');
exit;
