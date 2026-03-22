<?php
/**
 * 初参戦ライブガイド 候補曲保存 API
 * POST: { event_id: int, items: [{ song_id: int, likelihood: string }, ...] }
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\EventGuideSongModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json; charset=UTF-8');

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $eventId = (int)($input['event_id'] ?? 0);
    if ($eventId === 0) {
        throw new \Exception('event_id が指定されていません');
    }
    $items = $input['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    $model = new EventGuideSongModel();
    $model->saveForEvent($eventId, $items);

    Logger::info("hn_event_guide_songs save event_id={$eventId} count=" . count($items) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));

    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('save_event_guide_songs: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
