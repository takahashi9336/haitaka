<?php
/**
 * メンバー一覧API（FABミーグリネタ登録フォーム用）
 * 返却: { members: [{ id, name, favorite_level, generation, ... }] }
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Model\MemberModel;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $memberModel = new MemberModel();
    $members = $memberModel->getActiveMembersWithColors();
    $list = [];
    foreach ($members as $m) {
        $list[] = [
            'id' => (int)$m['id'],
            'name' => $m['name'] ?? '',
            'favorite_level' => (int)($m['favorite_level'] ?? 0),
            'generation' => (int)($m['generation'] ?? 0),
        ];
    }
    echo json_encode(['status' => 'success', 'members' => $list], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
