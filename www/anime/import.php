<?php
/**
 * アニメ一括登録画面
 * 物理パス: haitaka/www/anime/import.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Anime\Controller\AnimeController;

$controller = new AnimeController();
$controller->import();

