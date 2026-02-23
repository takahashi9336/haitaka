<?php
/**
 * 映画ダッシュボード（トップ）
 * 物理パス: haitaka/www/movie/index.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->dashboard();
