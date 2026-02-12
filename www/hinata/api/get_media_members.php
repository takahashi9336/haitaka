<?php
/**
 * 紐付け管理用：動画に紐づくメンバー一覧取得API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->getMediaMembers();
