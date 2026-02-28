<?php
/**
 * ミーグリ レポ（チャット形式）画面
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\MeetGreetController;

$controller = new MeetGreetController();
$controller->reportPage();
