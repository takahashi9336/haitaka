<?php
/**
 * 動画⇔楽曲メンバー紐付け補助用：指定動画に紐づく楽曲メンバー一覧取得API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->getSongMembersForMedia();

