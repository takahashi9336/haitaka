<?php
/**
 * ミーグリ予定・レポ画面
 * 物理パス: haitaka/www/hinata/meetgreet.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MeetGreetController;

$controller = new MeetGreetController();
$controller->index();
