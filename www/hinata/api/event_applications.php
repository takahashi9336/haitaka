<?php
/**
 * イベント応募締め切り一覧取得 API (GET)
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

$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
    echo json_encode(['applications' => []]);
    exit;
}

$model = new EventApplicationModel();
$applications = $model->getByEventId($eventId);
echo json_encode(['applications' => $applications]);
