<?php
/**
 * Health: 食材ストック一覧 API
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
    $model = new KitchenStockModel();
    $items = $model->getAllItems();
    echo json_encode(['status' => 'success', 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}

