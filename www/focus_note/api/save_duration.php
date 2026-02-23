<?php
/**
 * 質問型アクション 所要時間保存API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\FocusNote\Model\QuestionActionModel;
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

    $id = (int)($input['id'] ?? 0);
    $duration = (int)($input['duration_min'] ?? 0);
    if ($id <= 0 || $duration < 1) {
        throw new \Exception('id and duration_min (1以上) are required');
    }

    $model = new QuestionActionModel();
    $row = $model->find($id);
    if (!$row) {
        throw new \Exception('Not found');
    }

    $model->markDone($id, $duration);

    echo json_encode(['status' => 'success', 'message' => '記録しました'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
