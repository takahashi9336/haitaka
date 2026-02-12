<?php
/**
 * 紐付け管理用：動画・メンバー紐付け保存API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->saveMediaMembers();
