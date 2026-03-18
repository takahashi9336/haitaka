<?php

require_once __DIR__ . '/../../private/bootstrap.php';

use App\SenseLab\Controller\SenseLabController;

$controller = new SenseLabController();
$controller->index();

