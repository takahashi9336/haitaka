<?php
/**
 * リリース内楽曲順序保存API（管理者専用）
 * POST: { release_id, songs: [{ song_id, track_number }, ...] }
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Model\SongModel;
use Core\Auth;
use Core\Database;
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
    $releaseId = (int)($input['release_id'] ?? 0);
    $songs = $input['songs'] ?? [];

    if ($releaseId === 0) {
        throw new \Exception('release_id が必要です');
    }
    if (!is_array($songs) || count($songs) === 0) {
        throw new \Exception('保存対象の楽曲がありません');
    }

    $pdo = Database::connect();
    $pdo->beginTransaction();

    $songModel = new SongModel();
    $updated = 0;
    $updateUser = $_SESSION['user']['id_name'] ?? '';

    foreach ($songs as $row) {
        $songId = (int)($row['song_id'] ?? 0);
        $trackNumber = isset($row['track_number']) ? (int)$row['track_number'] : null;
        if ($songId === 0) continue;

        $song = $songModel->find($songId);
        if (!$song || (int)($song['release_id'] ?? 0) !== $releaseId) {
            continue; // 対象リリースの楽曲でなければスキップ
        }

        $songModel->update($songId, [
            'track_number' => $trackNumber > 0 ? $trackNumber : null,
            'update_user'  => $updateUser,
        ]);
        $updated++;
    }

    $pdo->commit();
    Logger::info("hn_songs track_order update release_id={$releaseId} count={$updated} by={$updateUser}");
    echo json_encode(['status' => 'success', 'message' => "{$updated}曲の順序を保存しました", 'updated' => $updated]);
} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
