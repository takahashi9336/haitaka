<?php
/**
 * Health: トレーニングメニュー
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Health\Controller\HealthController;

(new HealthController())->trainingMenu();
