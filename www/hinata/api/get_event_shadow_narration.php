<?php
/**
 * 影ナレ取得API（イベント単位）
 * GET: ?event_id=X
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\EventShadowNarrationModel;
use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です']);
    exit;
}

try {
    $eventId = (int)($_GET['event_id'] ?? 0);
    if ($eventId === 0) throw new \Exception('event_id が不正です');

    $model = new EventShadowNarrationModel();
    $row = $model->getByEventId($eventId);
    $memberIds = $row['member_ids'] ?? [];

    $members = [];
    if (!empty($memberIds)) {
        $memberModel = new MemberModel();
        $all = $memberModel->getAllWithColors();
        $byId = [];
        foreach ($all as $m) {
            $byId[(int)$m['id']] = $m;
        }
        foreach ($memberIds as $mid) {
            $m = $byId[(int)$mid] ?? null;
            if ($m) {
                $members[] = ['id' => (int)$m['id'], 'name' => (string)$m['name']];
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'event_id' => $eventId,
            'member_ids' => $memberIds,
            'members' => $members,
            'memo' => $row['memo'] ?? null,
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('get_event_shadow_narration: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

