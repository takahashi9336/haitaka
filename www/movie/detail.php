<?php
/**
 * 映画リスト 詳細画面
 * 物理パス: haitaka/www/movie/detail.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->detail();
