<?php
/**
 * イベント会場住所から座標を取得して保存する API
 * - hn_events.event_place_address -> latitude/longitude/place_id を更新
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;
use App\Hinata\Model\EventModel;
use Core\Maps\GeocodeService;
use Core\Maps\SqlUsageLimiter;

$auth = new Auth();
if (
    !$auth->check()
    || !in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)
) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['id'] ?? 0);
    $address = trim((string)($input['event_place_address'] ?? ''));
    if ($eventId <= 0) {
        throw new \Exception('イベントIDが不正です');
    }
    if ($address === '') {
        throw new \Exception('会場住所（Maps用）を入力してください');
    }

    Database::connect(); // .env を $_ENV に読み込む
    $geo = (new GeocodeService(new SqlUsageLimiter()))->geocode($address);
    if ($geo === null) {
        throw new \Exception('取得できませんでした（APIキー/制限/住所を確認してください）');
    }

    $eventModel = new EventModel();
    $eventModel->update($eventId, [
        'latitude' => $geo['latitude'] ?? null,
        'longitude' => $geo['longitude'] ?? null,
        'place_id' => $geo['place_id'] ?? null,
        // formatted_address は保存しない（ユーザー入力を優先）
        'update_user' => $_SESSION['user']['id_name'] ?? '',
    ]);

    echo json_encode([
        'status' => 'success',
        'geo' => $geo,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

