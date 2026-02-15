<?php
/**
 * 楽曲トップ（リリース一覧・全曲一覧）エントリ
 * 物理パス: haitaka/www/hinata/songs.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\SongController;

$controller = new SongController();
$controller->index();
