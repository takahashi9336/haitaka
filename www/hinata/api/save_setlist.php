<?php
/**
 * ????????API???????
 * POST: { event_id, items: [{ entry_type, sort_order, song_id?, encore?, label?, block_kind?, center_member_id?, center_member_ids?, memo? }] }
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\SetlistModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '????????']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['event_id'] ?? 0);
    if ($eventId === 0) throw new \Exception('event_id ?????');

    $items = $input['items'] ?? [];
    $model = new SetlistModel();
    $model->saveForEvent($eventId, $items);

    Logger::info("hn_setlists save event_id={$eventId} count=" . count($items) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
    echo json_encode(['status' => 'success', 'message' => '?????????????']);
} catch (\Exception $e) {
    Logger::errorWithContext('save_setlist: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
