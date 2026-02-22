<?php
/**
 * YouTube最新動画取得API（キャッシュ付き）
 * GET ?channel_id=XXXX
 * 物理パス: haitaka/www/hinata/api/youtube_latest.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Model\YouTubeApiClient;

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$channelId = $_GET['channel_id'] ?? '';
if (empty($channelId) || !isset(YouTubeApiClient::PRESET_CHANNELS[$channelId])) {
    echo json_encode(['status' => 'error', 'message' => '無効なチャンネルIDです']);
    exit;
}

$cacheDir = __DIR__ . '/../../../private/cache/hinata';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$wantCount = 15;
$cachePath = $cacheDir . '/yt_latest_' . substr(md5($channelId), 0, 12) . '.json';
$cacheTtl = 21600; // 6時間

if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheTtl) {
    $cached = @file_get_contents($cachePath);
    if ($cached !== false) {
        $data = json_decode($cached, true);
        if (is_array($data) && count($data) >= $wantCount) {
            echo json_encode(['status' => 'success', 'data' => $data, 'cached' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

$yt = new YouTubeApiClient();
if (!$yt->isConfigured()) {
    echo json_encode(['status' => 'error', 'message' => 'YouTube API未設定']);
    exit;
}

$playlistId = $yt->getUploadsPlaylistId($channelId);
if (!$playlistId) {
    echo json_encode(['status' => 'error', 'message' => 'チャンネル情報を取得できません']);
    exit;
}

$result = $yt->getPlaylistItems($playlistId, $wantCount);
if (!$result || empty($result['videos'])) {
    echo json_encode(['status' => 'error', 'message' => '動画を取得できません']);
    exit;
}

$videos = $result['videos'];
@file_put_contents($cachePath, json_encode($videos, JSON_UNESCAPED_UNICODE), LOCK_EX);

echo json_encode(['status' => 'success', 'data' => $videos, 'cached' => false], JSON_UNESCAPED_UNICODE);
