<?php
/**
 * 目標削除 API（紐づく action_goals, if_then_rules は CASCADE で削除）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\FocusNote\Model\GoalModel;
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

    $model = new GoalModel();
    $goal = $model->find($goalId);
    if (!$goal) {
        throw new \Exception('Goal not found');
    }

    $model->delete($goalId);

    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('goal_delete: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
