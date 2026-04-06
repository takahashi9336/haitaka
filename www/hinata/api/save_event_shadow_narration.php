<?php
/**
 * 影ナレ保存API（イベント単位）
 * POST: { event_id, member_ids: [int...], memo? }
 *
 * 編集権限: admin / hinata_admin のみ（Auth::isHinataAdmin()）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\EventShadowNarrationModel;
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
    if ($eventId === 0) throw new \Exception('event_id が不正です');

    $memberIds = $input['member_ids'] ?? [];
    if (!is_array($memberIds)) $memberIds = [];
    $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
    $memberIds = array_values(array_filter($memberIds, fn($v) => $v > 0));

    $memo = isset($input['memo']) && trim((string)$input['memo']) !== '' ? trim((string)$input['memo']) : null;

    $model = new EventShadowNarrationModel();
    $model->saveForEvent($eventId, $memberIds, $memo);

    Logger::info("hn_event_shadow_narrations save event_id={$eventId} members=" . count($memberIds) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
    echo json_encode(['status' => 'success', 'message' => '保存しました']);
} catch (\Exception $e) {
    Logger::errorWithContext('save_event_shadow_narration: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

