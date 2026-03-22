<?php
/**
 * 初参戦ライブガイド 楽曲管理画面 エントリポイント（管理者専用）
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\LiveGuideController;

$controller = new LiveGuideController();
$controller->admin();
