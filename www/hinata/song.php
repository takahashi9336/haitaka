<?php
/**
 * 楽曲個別紹介 エントリ
 * 物理パス: haitaka/www/hinata/song.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\SongController;

$controller = new SongController();
$controller->detail();
