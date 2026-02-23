<?php
/**
 * ウィークリーページ保存API（障害コントラスト・フィックス）
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\FocusNote\Model\WeeklyPageModel;
use Core\Auth;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = new Auth();
    $auth->requireLogin();

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        throw new \Exception('Invalid JSON');
    }

    $pageId = (int)($input['page_id'] ?? 0);
    if ($pageId <= 0) {
        throw new \Exception('page_id is required');
    }

    $model = new WeeklyPageModel();
    $page = $model->find($pageId);
    if (!$page) {
        throw new \Exception('Page not found');
    }

    $model->savePage($pageId, [
        'obstacle_contrast' => $input['obstacle_contrast'] ?? '',
        'obstacle_fix' => $input['obstacle_fix'] ?? '',
    ]);

    echo json_encode(['status' => 'success', 'message' => '保存しました'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
