<?php
/**
 * イベント応募締め切り一括保存 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Model\EventApplicationModel;

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $eventId = (int)($input['event_id'] ?? 0);
    $rows = $input['rows'] ?? [];

    if ($eventId <= 0) {
        throw new \Exception('イベントを選択してください');
    }

    $normalized = [];
    foreach ($rows as $r) {
        $dl = $r['application_deadline'] ?? '';
        if (empty($dl)) continue;
        $normalized[] = [
            'round_name'          => trim($r['round_name'] ?? ''),
            'application_start'   => !empty($r['application_start']) ? str_replace('T', ' ', substr($r['application_start'], 0, 16)) . ':00' : null,
            'application_deadline'=> str_replace('T', ' ', substr($dl, 0, 16)) . ':00',
            'announcement_date'   => !empty($r['announcement_date']) ? str_replace('T', ' ', substr($r['announcement_date'], 0, 16)) . ':00' : null,
            'application_url'     => trim($r['application_url'] ?? '') ?: null,
        ];
    }

    $model = new EventApplicationModel();
    $model->replaceForEvent($eventId, $normalized);
    echo json_encode(['status' => 'success']);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
