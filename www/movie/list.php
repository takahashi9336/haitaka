<?php
/**
 * 映画リスト 一覧画面
 * 物理パス: haitaka/www/movie/list.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->index();
