<?php
/**
 * イベント系列マスタ削除 API（日向坂管理者のみ）
 * - B案: hn_events から参照がある場合は削除不可
 * 物理パス: haitaka/www/hinata/api/delete_event_series.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Controller\EventController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json; charset=utf-8', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$controller = new EventController();
$controller->deleteEventSeriesJson();

