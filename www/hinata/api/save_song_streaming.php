<?php
/**
 * 楽曲のストリーミングURL保存API（管理者専用）
 * POST: { song_id, apple_music_url?, spotify_url? }
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Model\SongModel;
use Core\Auth;
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
    $songId = (int)($input['song_id'] ?? 0);
    if ($songId === 0) {
        throw new \Exception('song_id が必要です');
    }

    $songModel = new SongModel();
    $song = $songModel->find($songId);
    if (!$song) {
        throw new \Exception('楽曲が見つかりません');
    }

    $songModel->update($songId, [
        'apple_music_url' => trim($input['apple_music_url'] ?? '') ?: null,
        'spotify_url'     => trim($input['spotify_url'] ?? '') ?: null,
        'update_user'     => $_SESSION['user']['id_name'] ?? '',
    ]);

    Logger::info("hn_songs streaming_url update id={$songId} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
    echo json_encode(['status' => 'success', 'message' => 'ストリーミングURLを保存しました']);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
