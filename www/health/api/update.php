<?php
/**
 * Health: 食材ストック更新 API
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
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'id が不正です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $patch = [];
    if (array_key_exists('name', $input)) {
        $name = trim((string)$input['name']);
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => '食材名を入力してください'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $patch['name'] = $name;
    }
    if (array_key_exists('qty', $input)) {
        $patch['qty'] = ($input['qty'] === null || $input['qty'] === '') ? null : (string)$input['qty'];
    }
    if (array_key_exists('purchased_date', $input)) {
        $patch['purchased_date'] = ($input['purchased_date'] === null || $input['purchased_date'] === '') ? null : (string)$input['purchased_date'];
    }
    if (array_key_exists('is_frozen', $input)) {
        $patch['is_frozen'] = !empty($input['is_frozen']) ? 1 : 0;
    }
    if (array_key_exists('item_group', $input)) {
        $itemGroup = trim((string)$input['item_group']);
        if ($itemGroup === '') {
            $itemGroup = 'food';
        }
        if (!in_array($itemGroup, $allowedGroups, true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'item_group が不正です'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $patch['item_group'] = $itemGroup;
    }

    if (empty($patch)) {
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $model = new KitchenStockModel();
    $ok = $model->update($id, $patch);
    if (!$ok) {
        throw new \Exception('update failed');
    }
    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}

