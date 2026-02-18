<?php
/**
 * 動画・楽曲紐付け用：指定動画に紐づく楽曲を1件取得
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->getMediaLinkedSong();
