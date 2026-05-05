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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = trim((string)($input['id'] ?? ''));
if ($id === '') {
    echo json_encode(['status' => 'error', 'message' => 'id は必須です'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $storage = new TextFileAdminStorage();
    $item = $storage->get($id);
    if (!$item) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => '見つかりません'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['status' => 'success', 'item' => $item], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '取得に失敗しました'], JSON_UNESCAPED_UNICODE);
}

