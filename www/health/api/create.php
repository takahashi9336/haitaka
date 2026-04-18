<?php
/**
 * Health: 食材ストック作成 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Health\Model\KitchenStockModel;

$allowedGroups = ['food', 'seasoning', 'other'];

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
        echo json_encode(['status' => 'error', 'message' => '食材名を入力してください'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $itemGroup = isset($input['item_group']) ? trim((string)$input['item_group']) : '';
    if ($itemGroup === '') {
        $itemGroup = 'food';
    }
    if (!in_array($itemGroup, $allowedGroups, true)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'item_group が不正です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = [
        'name' => $name,
        'item_group' => $itemGroup,
        'qty' => isset($input['qty']) && $input['qty'] !== '' ? (string)$input['qty'] : null,
        'purchased_date' => isset($input['purchased_date']) && $input['purchased_date'] !== '' ? (string)$input['purchased_date'] : null,
        'is_frozen' => !empty($input['is_frozen']) ? 1 : 0,
    ];

    $model = new KitchenStockModel();
    $ok = $model->create($data);
    if (!$ok) {
        throw new \Exception('create failed');
    }
    echo json_encode(['status' => 'success', 'id' => (int)$model->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}

