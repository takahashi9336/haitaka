<?php
/**
 * 動画設定管理画面 エントリポイント（管理者専用）
 * 物理パス: haitaka/www/hinata/media_settings_admin.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->mediaAdmin('settings');
