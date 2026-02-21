<?php
/**
 * 映画一括編集画面
 * 物理パス: haitaka/www/movie/bulk_edit.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Movie\Controller\MovieController;

$controller = new MovieController();
$controller->bulkEdit();
