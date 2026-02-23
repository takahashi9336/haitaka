<?php
/**
 * マンスリーページ保存API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\FocusNote\Model\MonthlyPageModel;
use App\FocusNote\Model\DailyTaskModel;
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

    $monthlyModel = new MonthlyPageModel();
    $page = $monthlyModel->find($pageId);
    if (!$page) {
        throw new \Exception('Page not found');
    }

    $monthlyModel->savePage($pageId, [
        'target' => $input['target'] ?? '',
        'importance_check' => $input['importance_check'] ?? '',
        'concrete_imaging' => $input['concrete_imaging'] ?? '',
        'reverse_planning' => $input['reverse_planning'] ?? '',
    ]);

    $dailyTaskModel = new DailyTaskModel();
    $contents = $input['daily_tasks'] ?? [];
    if (is_array($contents)) {
        $dailyTaskModel->replaceTasks($pageId, $contents);
    }

    echo json_encode(['status' => 'success', 'message' => '保存しました'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
