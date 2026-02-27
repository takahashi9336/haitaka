<?php
/**
 * 映画検索結果ページ
 * 物理パス: haitaka/www/movie/search.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->searchPage();
