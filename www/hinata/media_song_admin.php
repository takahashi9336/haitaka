<?php
/**
 * 動画・楽曲紐付け管理画面 エントリポイント（管理者専用）
 * 物理パス: haitaka/www/hinata/media_song_admin.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->mediaSongAdmin();
