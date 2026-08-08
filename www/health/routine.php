<?php
/**
 * Health: ルーティン表
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Health\Controller\HealthController;

(new HealthController())->routine();
