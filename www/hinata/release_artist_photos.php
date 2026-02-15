<?php
/**
 * リリース別アーティスト写真登録画面 エントリ（管理者専用）
 * 物理パス: haitaka/www/hinata/release_artist_photos.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\ReleaseController;

$controller = new ReleaseController();
$controller->artistPhotos();
