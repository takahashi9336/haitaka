<?php
require_once __DIR__ . '/../private/vendor/autoload.php';
$auth = new \Core\Auth();
$auth->logout();
header('Location: /login.php');
exit;
