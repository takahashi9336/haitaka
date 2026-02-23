<?php
/**
 * ウィークリーで選んだタスク保存API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\FocusNote\Model\WeeklyPageModel;
use App\FocusNote\Model\WeeklyTaskPickModel;
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

    $weeklyModel = new WeeklyPageModel();
    $page = $weeklyModel->find($pageId);
    if (!$page) {
        throw new \Exception('Page not found');
    }

    $ids = $input['daily_task_ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    $ids = array_map('intval', array_filter($ids));

    if (count($ids) < 3 || count($ids) > 5) {
        throw new \Exception('3〜5つ選んでください');
    }

    $pickModel = new WeeklyTaskPickModel();
    $pickModel->replacePicks($pageId, $ids);

    echo json_encode(['status' => 'success', 'message' => '選択を保存しました'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
