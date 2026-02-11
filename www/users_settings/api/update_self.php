<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Settings\Controller\SettingsController;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new SettingsController())->updateSelf();
}