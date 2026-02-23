<?php
/**
 * 推し設定ページ
 * 物理パス: haitaka/www/hinata/oshi_settings.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\OshiController;

$controller = new OshiController();
$controller->settings();
