<?php
/**
 * リリース別アーティスト写真保存API（管理者専用）
 * POST: { release_id, members: [{ member_id, image_url }, ...] }
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\ReleaseController;

$controller = new ReleaseController();
$controller->saveReleaseMemberImages();
