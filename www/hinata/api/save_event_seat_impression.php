<?php
/**
 * イベントの座席・感想保存API
 * POST: { event_id, seat_info, impression }
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

    if ($eventId === 0) {
        throw new \Exception('event_id が不正です');
    }

    $seatInfo = isset($input['seat_info']) ? trim($input['seat_info']) : '';
    $impression = isset($input['impression']) ? trim($input['impression']) : '';

    $model = new EventModel();
    $model->saveUserSeatImpression($eventId, $seatInfo ?: null, $impression ?: null);

    Logger::info("hn_user_events_status seat/impression save event_id={$eventId} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));

    echo json_encode([
        'status' => 'success',
    ]);
} catch (\Exception $e) {
    Logger::errorWithContext('save_event_seat_impression: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
