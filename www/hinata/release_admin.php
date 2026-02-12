<?php
/**
 * リリース管理画面 エントリポイント（管理者専用）
 * 物理パス: haitaka/www/hinata/release_admin.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\ReleaseController;

$controller = new ReleaseController();
$controller->admin();
