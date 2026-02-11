<?php
require_once __DIR__ . '/../../private/vendor/autoload.php';


use App\Settings\Controller\SettingsController;
$controller = new SettingsController();
$controller->index();