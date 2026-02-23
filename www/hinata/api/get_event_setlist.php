<?php
/**
 * ????????????API
 * GET: ?event_id=X
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
    $eventId = (int)($_GET['event_id'] ?? 0);
    if ($eventId === 0) throw new \Exception('event_id ?????');

    $model = new SetlistModel();
    $setlist = $model->getByEventId($eventId);
    $attended = $model->isAttended($eventId);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'setlist' => $setlist,
            'attended' => $attended,
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('get_event_setlist: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
