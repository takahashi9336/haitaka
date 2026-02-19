<?php
/**
 * 動画カテゴリ新規作成API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->createMediaCategory();
