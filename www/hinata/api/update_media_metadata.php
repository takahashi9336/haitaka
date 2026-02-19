<?php
/**
 * 動画メタデータ更新API（カテゴリ変更など）
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->updateMetadata();
