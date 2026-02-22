<?php
/**
 * ライブ参戦トグルAPI
 * POST: { event_id }
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Model\SetlistModel;
use Core\Auth;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['event_id'] ?? 0);
    if ($eventId === 0) throw new \Exception('event_id が必要です');

    $model = new SetlistModel();
    $attended = $model->toggleAttendance($eventId);

    echo json_encode([
        'status' => 'success',
        'attended' => $attended,
        'message' => $attended ? '参戦記録を追加しました' : '参戦記録を解除しました'
    ]);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
