<?php
/**
 * メディア一括保存API
 * 物理パス: haitaka/www/hinata/api/save_media_bulk.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\MediaController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

// 管理者権限チェック
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません']);
    exit;
}

$controller = new MediaController();
$controller->bulkSave();
