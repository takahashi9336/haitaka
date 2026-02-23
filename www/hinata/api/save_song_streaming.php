<?php
/**
 * ??????????URL??API???????
 * POST: { song_id, apple_music_url?, spotify_url? }
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Hinata\Model\SongModel;
use Core\Auth;
use Core\Logger;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '????????']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $songId = (int)($input['song_id'] ?? 0);
    if ($songId === 0) {
        throw new \Exception('song_id ?????');
    }

    $songModel = new SongModel();
    $song = $songModel->find($songId);
    if (!$song) {
        throw new \Exception('??????????');
    }

    $songModel->update($songId, [
        'apple_music_url' => trim($input['apple_music_url'] ?? '') ?: null,
        'spotify_url'     => trim($input['spotify_url'] ?? '') ?: null,
        'update_user'     => $_SESSION['user']['id_name'] ?? '',
    ]);

    Logger::info("hn_songs streaming_url update id={$songId} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
    echo json_encode(['status' => 'success', 'message' => '???????URL???????']);
} catch (\Exception $e) {
    Logger::errorWithContext('save_song_streaming: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
