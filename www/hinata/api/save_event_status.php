<?php
/**
 * イベント参戦ステータス保存API
 * POST: { event_id, status }
 * status: 1=参加, 2=不参加, 3=検討, 4=当選, 5=落選, 0=クリア
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\EventModel;
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
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['event_id'] ?? 0);
    $newStatus = (int)($input['status'] ?? 0);

    if ($eventId === 0) {
        throw new \Exception('event_id が不正です');
    }
    if ($newStatus < 0 || $newStatus > 5) {
        throw new \Exception('status が不正です');
    }

    $model = new EventModel();

    if ($newStatus === 0) {
        $model->deleteUserStatus($eventId);
    } else {
        $model->saveUserStatus($eventId, $newStatus);
    }

    Logger::info("hn_user_events_status save event_id={$eventId} status={$newStatus} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));

    echo json_encode([
        'status' => 'success',
        'new_status' => $newStatus,
    ]);
} catch (\Exception $e) {
    Logger::errorWithContext('save_event_status: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
