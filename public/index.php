<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/' . currentUser()['dashboard']);
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
