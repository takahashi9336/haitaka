<?php
/**
 * 初参戦ライブガイド API
 * GET: ?event_id=X
 * イベントの候補曲（確度別）と紐づく動画、コラボURL、ハッシュタグ付きメディアを返す
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\EventModel;
use App\Hinata\Model\EventGuideSongModel;
use App\Hinata\Model\SongModel;
use Core\Auth;
use Core\Database;
use Core\Logger;

header('Content-Type: application/json; charset=UTF-8');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $eventId = (int)($_GET['event_id'] ?? 0);
    if ($eventId === 0) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'event' => null,
                'songs_by_likelihood' => ['certain' => [], 'high' => [], 'possible' => []],
                'collaboration_urls' => [],
                'hashtag_media' => [],
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $eventModel = new EventModel();
    $event = $eventModel->find($eventId);
    if (!$event) {
        echo json_encode(['status' => 'error', 'message' => 'イベントが見つかりません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $guideModel = new EventGuideSongModel();
    $songsByLikelihood = $guideModel->getByEventIdGroupedByLikelihood($eventId);

    $songModel = new SongModel();
    foreach (['certain', 'high', 'possible'] as $lik) {
        foreach ($songsByLikelihood[$lik] as &$song) {
            // 初参戦ガイドでは MV とコール動画（Call）のみ表示
            $song['videos'] = $songModel->getMediaLinksBySongId((int)$song['song_id'], ['MV', 'Call']);
        }
        unset($song);
    }

    $collaborationUrls = [];
    if (!empty($event['collaboration_urls'])) {
        $decoded = json_decode($event['collaboration_urls'], true);
        if (is_array($decoded)) {
            $collaborationUrls = array_values(array_filter(array_map('trim', $decoded)));
        }
    }

    $hashtagMedia = [];
    $hashtag = trim($event['event_hashtag'] ?? '');
    if ($hashtag !== '') {
        $pdo = Database::connect();
        // タイトルまたは説明にハッシュタグが含まれる動画を取得（#あり・なし両方にマッチ）
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $hashtag);
        $pattern = '%' . $escaped . '%';
        $stmt = $pdo->prepare("
            SELECT hmeta.id as meta_id, hmeta.category,
                   ma.platform, ma.media_key, ma.sub_key, ma.media_type,
                   ma.title, ma.thumbnail_url, ma.upload_date
            FROM hn_media_metadata hmeta
            JOIN com_media_assets ma ON ma.id = hmeta.asset_id
            WHERE (ma.title LIKE :pat1 OR (ma.description IS NOT NULL AND ma.description LIKE :pat2))
            ORDER BY COALESCE(ma.upload_date, ma.created_at) DESC
            LIMIT 50
        ");
        $stmt->execute(['pat1' => $pattern, 'pat2' => $pattern]);
        $hashtagMedia = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($hashtagMedia as &$row) {
            if (empty($row['thumbnail_url']) && ($row['platform'] ?? '') === 'youtube' && !empty($row['media_key'])) {
                $row['thumbnail_url'] = 'https://img.youtube.com/vi/' . $row['media_key'] . '/mqdefault.jpg';
            }
        }
        unset($row);
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'event' => [
                'id' => (int)$event['id'],
                'event_name' => $event['event_name'],
                'event_date' => $event['event_date'],
                'event_place' => $event['event_place'] ?? '',
                'event_url' => $event['event_url'] ?? '',
                'event_hashtag' => $event['event_hashtag'] ?? '',
            ],
            'songs_by_likelihood' => $songsByLikelihood,
            'collaboration_urls' => $collaborationUrls,
            'hashtag_media' => $hashtagMedia,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    Logger::errorWithContext('get_live_guide: ' . $e->getMessage(), $e);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
