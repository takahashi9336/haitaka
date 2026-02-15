<?php
/**
 * 楽曲参加メンバー保存API（管理者専用）
 * 物理パス: haitaka/www/hinata/api/save_song_members.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\SongController;

$controller = new SongController();
$controller->saveMembers();
