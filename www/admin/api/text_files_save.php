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

try {
    $ext = strtolower(trim((string)($input['ext'] ?? '')));
    $content = $input['content'] ?? '';
    if ($ext === 'html' && isset($input['content_b64']) && is_string($input['content_b64']) && $input['content_b64'] !== '') {
        $decoded = base64_decode($input['content_b64'], true);
        if ($decoded === false) {
            echo json_encode(['status' => 'error', 'message' => '本文の形式が不正です'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $content = $decoded;
    }
    $storage = new TextFileAdminStorage();
    $res = $storage->save([
        'id' => $input['id'] ?? '',
        'title' => $input['title'] ?? '',
        'ext' => $ext,
        'content' => $content,
    ], (int)($_SESSION['user']['id'] ?? 0));

    echo json_encode(['status' => 'success', 'id' => $res['id']], JSON_UNESCAPED_UNICODE);
} catch (\InvalidArgumentException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '保存に失敗しました'], JSON_UNESCAPED_UNICODE);
}

