<?php
/**
 * ????????API
 * POST: { event_id }
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\SetlistModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '???????']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['event_id'] ?? 0);
    if ($eventId === 0) throw new \Exception('event_id ?????');

    $model = new SetlistModel();
    $attended = $model->toggleAttendance($eventId);

    echo json_encode([
        'status' => 'success',
        'attended' => $attended,
        'message' => $attended ? '???????????' : '???????????'
    ]);
} catch (\Exception $e) {
    Logger::errorWithContext('toggle_attendance: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
