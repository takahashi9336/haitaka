<?php
/**
 * 楽曲の参加メンバー編集画面 エントリ（管理者専用）
 * 物理パス: haitaka/www/hinata/song_member_edit.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\SongController;

$controller = new SongController();
$controller->memberEdit();
