<?php
/**
 * 日向坂イベント管理画面 エントリポイント
 * 物理パス: haitaka/www/hinata/event_admin.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\EventController;

$controller = new EventController();
$controller->admin();