<?php
require_once __DIR__ . '/../../private/bootstrap.php';


use App\Settings\Controller\SettingsController;
$controller = new SettingsController();
$controller->index();