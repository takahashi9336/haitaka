<?php
/**
 * ??????????API???????
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\FocusNote\Model\QuestionActionModel;
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

    $items = $input['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    $model = new QuestionActionModel();
    $userName = $_SESSION['user']['id_name'] ?? '?';

    foreach ($items as $item) {
        $pickId = (int)($item['pick_id'] ?? 0);
        if ($pickId <= 0) continue;

        $time = trim((string)($item['time'] ?? ''));
        $place = trim((string)($item['place'] ?? ''));
        $taskContent = trim((string)($item['task_content'] ?? ''));
        $questionText = $userName . '??' . ($time ?: '[??]') . '?' . ($place ?: '[??]') . '?' . $taskContent . '?????';

        $model->upsertForPick($pickId, [
            'scheduled_time' => $time,
            'place' => $place,
            'question_text' => $questionText,
        ]);
    }

    echo json_encode(['status' => 'success', 'message' => '??????'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('save_question_actions: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
