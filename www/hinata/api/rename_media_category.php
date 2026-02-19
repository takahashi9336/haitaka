<?php
/**
 * 動画カテゴリ名称変更API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->renameMediaCategory();
