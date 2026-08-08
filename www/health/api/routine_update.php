<?php
/**
 * Health: ルーティン更新 API（メタ情報のみ）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Health\Model\RoutineModel;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!is_array($input)) {
        throw new \Exception('Invalid input');
    }

    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'IDが不正です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $patch = [];
    if (isset($input['name'])) {
        $name = trim((string)$input['name']);
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'ルーティン名を入力してください'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $patch['name'] = $name;
    }
    if (isset($input['type']) && in_array($input['type'], ['平日', '休日'], true)) {
        $patch['type'] = $input['type'];
    }
    if (isset($input['theme']) && in_array($input['theme'], ['morning', 'night'], true)) {
        $patch['theme'] = $input['theme'];
    }

    if (empty($patch)) {
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $model = new RoutineModel();
    $model->update($id, $patch);
    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}
