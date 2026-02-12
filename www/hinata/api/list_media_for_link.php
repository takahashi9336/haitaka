<?php
/**
 * 紐付け管理用：動画一覧取得API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->listMediaForLink();
