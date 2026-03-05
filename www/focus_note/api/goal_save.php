<?php
/**
 * 目標（WOOP）保存 API
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
    $model = new GoalModel();

    if ($goalId > 0) {
        $goal = $model->find($goalId);
        if (!$goal) {
            throw new \Exception('Goal not found');
        }
        $model->updateGoal($goalId, [
            'wish' => $input['wish'] ?? '',
            'outcome' => $input['outcome'] ?? '',
            'obstacle' => $input['obstacle'] ?? '',
            'plan' => $input['plan'] ?? '',
            'being' => $input['being'] ?? '',
        ]);
        echo json_encode(['status' => 'success', 'goal_id' => $goalId], JSON_UNESCAPED_UNICODE);
    } else {
        $newId = $model->createGoal([
            'wish' => $input['wish'] ?? '',
            'outcome' => $input['outcome'] ?? '',
            'obstacle' => $input['obstacle'] ?? '',
            'plan' => $input['plan'] ?? '',
            'being' => $input['being'] ?? '',
        ]);
        echo json_encode(['status' => 'success', 'goal_id' => $newId], JSON_UNESCAPED_UNICODE);
    }
} catch (\Exception $e) {
    Logger::errorWithContext('goal_save: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
