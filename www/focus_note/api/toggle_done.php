<?php
/**
 * ???????? ?????API
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

    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        throw new \Exception('id is required');
    }

    $model = new QuestionActionModel();
    $row = $model->find($id);
    if (!$row) {
        throw new \Exception('Not found');
    }

    $model->toggleDone($id);
    $updated = $model->find($id);

    echo json_encode([
        'status' => 'success',
        'done' => (int)($updated['done'] ?? 0),
        'message' => ($updated['done'] ?? 0) ? '???????' : '?????????'
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('toggle_done: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
