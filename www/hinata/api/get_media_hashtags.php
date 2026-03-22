<?php
/**
 * メディアに付与済みのハッシュタグ取得 API
 * GET: ?meta_id=123
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

header('Content-Type: application/json; charset=UTF-8');

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $metaId = (int)($_GET['meta_id'] ?? 0);
    if ($metaId === 0) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    $pdo = Database::connect();
    $stmt = $pdo->prepare("SELECT hashtag FROM hn_media_hashtags WHERE media_meta_id = ? ORDER BY hashtag ASC");
    $stmt->execute([$metaId]);
    $tags = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'hashtag');

    echo json_encode(['status' => 'success', 'data' => $tags], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
