<?php
/**
 * 人物別：未登録映画ピックアップページ
 * 物理パス: haitaka/www/movie/pickup.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->personPickupPage();

