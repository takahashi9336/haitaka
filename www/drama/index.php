<?php

use App\Drama\Controller\DramaController;

require_once __DIR__ . '/../../private/bootstrap.php';

$controller = new DramaController();
$controller->dashboard();

