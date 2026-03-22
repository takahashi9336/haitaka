<?php
/**
 * メディアへのハッシュタグ付与 API
 * POST: { meta_id: int, hashtags: ["tag1", "tag2"] }
 * 指定メタの既存ハッシュタグを削除し、新しいリストで置換
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;
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
    $metaId = (int)($input['meta_id'] ?? 0);
    if ($metaId === 0) {
        throw new \Exception('meta_id が指定されていません');
    }
    $hashtags = $input['hashtags'] ?? [];
    if (!is_array($hashtags)) {
        $hashtags = [];
    }
    $hashtags = array_unique(array_values(array_filter(array_map(function ($t) {
        return trim(preg_replace('/^#/', '', (string)$t));
    }, $hashtags))));

    $pdo = Database::connect();
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM hn_media_hashtags WHERE media_meta_id = ?")->execute([$metaId]);
    if (!empty($hashtags)) {
        $stmt = $pdo->prepare("INSERT INTO hn_media_hashtags (media_meta_id, hashtag) VALUES (?, ?)");
        foreach ($hashtags as $tag) {
            if (strlen($tag) > 0 && strlen($tag) <= 100) {
                $stmt->execute([$metaId, $tag]);
            }
        }
    }
    $pdo->commit();

    Logger::info("hn_media_hashtags save meta_id={$metaId} tags=" . implode(',', $hashtags) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));

    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Logger::errorWithContext('save_media_hashtags: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
