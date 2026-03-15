<?php
/**
 * ドラマ一括登録画面
 * 物理パス: haitaka/www/drama/import.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Drama\Controller\DramaController;

$controller = new DramaController();
$controller->import();

