<?php
/**
 * 映画一括登録画面
 * 物理パス: haitaka/www/movie/import.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->import();
