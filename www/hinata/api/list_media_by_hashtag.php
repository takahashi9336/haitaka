<?php
/**
 * ハッシュタグでメディア一覧取得 API
 * GET: ?hashtag=七回目のひな誕祭
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

header('Content-Type: application/json; charset=UTF-8');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $hashtag = trim($_GET['hashtag'] ?? '');
    if ($hashtag === '') {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    $pdo = Database::connect();
    $stmt = $pdo->prepare("
        SELECT hmeta.id as meta_id, hmeta.category,
               ma.platform, ma.media_key, ma.sub_key, ma.media_type,
               ma.title, ma.thumbnail_url, ma.upload_date
        FROM hn_media_hashtags mh
        JOIN hn_media_metadata hmeta ON hmeta.id = mh.media_meta_id
        JOIN com_media_assets ma ON ma.id = hmeta.asset_id
        WHERE mh.hashtag = :tag
        ORDER BY COALESCE(ma.upload_date, ma.created_at) DESC
        LIMIT 100
    ");
    $stmt->execute(['tag' => $hashtag]);
    $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        if (empty($row['thumbnail_url']) && ($row['platform'] ?? '') === 'youtube' && !empty($row['media_key'])) {
            $row['thumbnail_url'] = 'https://img.youtube.com/vi/' . $row['media_key'] . '/mqdefault.jpg';
        }
    }
    unset($row);

    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
