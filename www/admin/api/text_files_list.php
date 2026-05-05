<?php
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Admin\Model\TextFileAdminStorage;

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin 権限が必要です'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $storage = new TextFileAdminStorage();
    $items = $storage->list();
    // content は返さない
    foreach ($items as &$it) {
        unset($it['content']);
    }
    echo json_encode(['status' => 'success', 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '取得に失敗しました'], JSON_UNESCAPED_UNICODE);
}

