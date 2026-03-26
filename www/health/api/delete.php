<?php
/**
 * Health: 食材ストック削除 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Health\Model\KitchenStockModel;

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
        echo json_encode(['status' => 'error', 'message' => 'id が不正です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $model = new KitchenStockModel();
    $ok = $model->delete($id);
    if (!$ok) {
        throw new \Exception('delete failed');
    }
    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}

