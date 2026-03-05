<?php
/**
 * If-Then ルール一括保存 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\FocusNote\Model\GoalModel;
use App\FocusNote\Model\IfThenRuleModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = new Auth();
    $auth->requireLogin();

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        throw new \Exception('Invalid JSON');
    }

    $goalId = (int) ($input['goal_id'] ?? 0);
    if ($goalId <= 0) {
        throw new \Exception('goal_id is required');
    }

    $goalModel = new GoalModel();
    $goal = $goalModel->find($goalId);
    if (!$goal) {
        throw new \Exception('Goal not found');
    }

    $items = $input['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    $ruleModel = new IfThenRuleModel();
    $ruleModel->replaceForGoal($goalId, $items);

    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('if_then_rules_save: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
