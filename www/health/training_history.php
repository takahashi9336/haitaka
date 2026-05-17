<?php
/**
 * Health: トレーニング実施履歴
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Health\Controller\HealthController;

(new HealthController())->trainingHistory();
