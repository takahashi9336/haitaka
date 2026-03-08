<?php
/**
 * 改善事項登録API（FAB からの登録用）
 * POST (application/json): screen_name, content, source_url(任意)
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Admin\Model\ImprovementItemModel;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin 権限が必要です']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$screenName = trim($input['screen_name'] ?? '');
$content = trim($input['content'] ?? '');
$sourceUrl = !empty($input['source_url']) ? trim($input['source_url']) : null;

if (!$content) {
    echo json_encode(['status' => 'error', 'message' => '改善事項は必須です']);
    exit;
}

if (!$screenName) {
    $screenName = '不明';
}

try {
    $model = new ImprovementItemModel();
    $model->createItem([
        'screen_name' => $screenName,
        'content' => $content,
        'status' => ImprovementItemModel::STATUS_PENDING,
        'source_url' => $sourceUrl,
    ]);
    echo json_encode(['status' => 'success']);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '保存に失敗しました']);
}
