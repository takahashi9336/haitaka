<?php
/**
 * セットリスト保存API（管理者専用）
 * POST: { event_id, items: [{ song_id, sort_order, encore?, memo? }] }
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Model\SetlistModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['event_id'] ?? 0);
    if ($eventId === 0) throw new \Exception('event_id が必要です');

    $items = $input['items'] ?? [];
    $model = new SetlistModel();
    $model->saveForEvent($eventId, $items);

    Logger::info("hn_setlists save event_id={$eventId} count=" . count($items) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
    echo json_encode(['status' => 'success', 'message' => 'セットリストを保存しました']);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
