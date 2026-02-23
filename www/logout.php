<?php
require_once __DIR__ . '/../private/bootstrap.php';
$auth = new \Core\Auth();
$auth->logout();
header('Location: /login.php');
exit;
