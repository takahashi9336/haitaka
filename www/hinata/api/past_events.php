<?php
/**
 * 過去イベント取得 API（無限スクロール用）
 * GET: ?before=YYYY-MM-DD&limit=20&offset=0&category=1
 * 物理パス: haitaka/www/hinata/api/past_events.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\EventModel;
use Core\Auth;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

try {
    $before   = $_GET['before'] ?? date('Y-m-d');
    $limit    = max(1, min(50, (int)($_GET['limit'] ?? 20)));
    $offset   = max(0, (int)($_GET['offset'] ?? 0));
    $category = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;

    $model  = new EventModel();
    $events = $model->getPastEvents($before, $limit, $offset, $category);

    echo json_encode([
        'status' => 'success',
        'data'   => $events,
        'has_more' => count($events) === $limit,
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
