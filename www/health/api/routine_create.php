<?php
/**
 * Health: ルーティン作成 API
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

    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'ルーティン名を入力してください'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $type  = in_array($input['type'] ?? '', ['平日', '休日'], true) ? $input['type'] : '平日';
    $theme = in_array($input['theme'] ?? '', ['morning', 'night'], true) ? $input['theme'] : 'morning';

    $model = new RoutineModel();
    $data = [
        'name'       => $name,
        'type'       => $type,
        'theme'      => $theme,
        'sort_order' => $model->nextSortOrder(),
    ];
    $ok = $model->create($data);
    if (!$ok) {
        throw new \Exception('create failed');
    }
    echo json_encode(['status' => 'success', 'id' => (int)$model->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}
