<?php
/**
 * リリース詳細取得API（管理者用・編集時のデータ取得）
 * GET: ?id=1
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\ReleaseController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません']);
    exit;
}

$controller = new ReleaseController();
$controller->detail();
