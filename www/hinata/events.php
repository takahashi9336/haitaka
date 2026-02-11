<?php
/**
 * 日向坂イベント一覧 (一般用) エントリ
 * 物理パス: haitaka/www/hinata/events.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\EventController;

$controller = new EventController();
$controller->index();