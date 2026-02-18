<?php
/**
 * 動画・楽曲紐付け保存API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->saveMediaSongLink();
