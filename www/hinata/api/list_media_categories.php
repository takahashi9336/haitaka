<?php
/**
 * 動画カテゴリ一覧取得API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->listMediaCategories();
