<?php
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Model\MeetGreetModel;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $date = $input['date'] ?? '';
    $eventId = (int)($input['event_id'] ?? 0);

    if (!$date || !$eventId) {
        throw new \Exception('日付とイベントIDが必要です');
    }

    $model = new MeetGreetModel();
    $updated = $model->linkSlotsToEvent($date, $eventId);

    echo json_encode(['status' => 'success', 'updated' => $updated]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
