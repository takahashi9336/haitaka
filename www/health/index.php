<?php
/**
 * Health Entry Point
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Health\Controller\HealthController;

(new HealthController())->portal();

