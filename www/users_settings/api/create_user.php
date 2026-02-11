<?php
require_once __DIR__ . '/../../../private/vendor/autoload.php';
use App\Settings\Controller\SettingsController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new SettingsController())->createUser();
}