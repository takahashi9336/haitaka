<?php
/**
 * ストリーミングURL一括保存API（管理者専用）
 * POST: { songs: [{ song_id, apple_music_url?, spotify_url? }, ...] }
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
    $songs = $input['songs'] ?? [];
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
        if ($songId === 0) continue;

        $song = $songModel->find($songId);
        if (!$song) continue;

        $songModel->update($songId, [
            'apple_music_url' => trim($row['apple_music_url'] ?? '') ?: null,
            'spotify_url'     => trim($row['spotify_url'] ?? '') ?: null,
            'update_user'     => $updateUser,
        ]);
        $updated++;
    }

    $pdo->commit();
    Logger::info("hn_songs bulk_streaming_url update count={$updated} by={$updateUser}");
    echo json_encode(['status' => 'success', 'message' => "{$updated}曲のストリーミングURLを保存しました", 'updated' => $updated]);
} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
