<?php
/**
 * YouTube 新着動画 自動取り込みバッチ
 *
 * プリセット2チャンネルの最新動画を取得し、未登録分を DB に自動登録。
 * タイトル+説明文からメンバー名を検出し自動紐づけ。
 *
 * GET (管理者) / CLI 対応
 */
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use Core\Database;
use Core\MediaAssetModel;
use App\Hinata\Model\YouTubeApiClient;
use App\Hinata\Model\NewsModel;

if (!$isCli) {
    $auth = new Auth();
    $auth->requireLogin();
    if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => '権限がありません']);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

$yt = new YouTubeApiClient();
if (!$yt->isConfigured()) {
    $msg = 'YouTube API key not configured';
    if ($isCli) { echo $msg . PHP_EOL; exit(1); }
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

$assetModel = new MediaAssetModel();
$pdo = Database::connect();

$newsModel = new NewsModel();
$nameMap = $newsModel->getMemberNameMap();

$stats = ['channels' => 0, 'fetched' => 0, 'new' => 0, 'existing' => 0, 'members_linked' => 0];
$errors = [];

$categoryMap = [
    'short' => 'SoloPV',
    'live'  => 'Live',
    'video' => 'Variety',
];

foreach (YouTubeApiClient::PRESET_CHANNELS as $channelId => $channelName) {
    $stats['channels']++;
    if ($isCli) echo "[{$channelName}] Fetching..." . PHP_EOL;

    $playlistId = $yt->getUploadsPlaylistId($channelId);
    if (!$playlistId) {
        $errors[] = "Failed to get playlist for {$channelName}";
        continue;
    }

    $result = $yt->getPlaylistItems($playlistId, 25);
    if (!$result || empty($result['videos'])) {
        $errors[] = "No videos found for {$channelName}";
        continue;
    }

    foreach ($result['videos'] as $video) {
        $stats['fetched']++;
        $videoId = $video['video_id'];

        $uploadDate = null;
        if (!empty($video['published_at'])) {
            $uploadDate = date('Y-m-d H:i:s', strtotime($video['published_at']));
        }

        $assetId = $assetModel->findOrCreateAsset(
            'youtube',
            $videoId,
            null,
            $video['title'],
            $video['thumbnail_url'] ?? null,
            $uploadDate,
            $video['description'] ?? null,
            $video['media_type'] ?? null
        );

        if (!$assetId) continue;

        $category = $categoryMap[$video['media_type'] ?? 'video'] ?? 'Variety';
        $metaId = $assetModel->findOrCreateMetadata($assetId, $category);

        if (!$metaId) continue;

        // 新規登録かどうかの判定: metaId が今回作成されたか
        $isNew = false;
        $checkStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM hn_media_members WHERE media_meta_id = ?"
        );
        $checkStmt->execute([$metaId]);
        $existingMembers = (int)$checkStmt->fetchColumn();

        // まだメンバー紐づけがない場合のみ自動検出
        if ($existingMembers === 0) {
            $isNew = true;
            $text = $video['title'] . ' ' . ($video['description'] ?? '');
            $normalizedText = str_replace([' ', '　'], '', $text);

            $memberIds = [];
            foreach ($nameMap as $name => $memberId) {
                if (mb_strpos($normalizedText, $name) !== false) {
                    $memberIds[] = $memberId;
                }
            }
            $memberIds = array_unique($memberIds);

            if (!empty($memberIds)) {
                $insertStmt = $pdo->prepare(
                    "INSERT IGNORE INTO hn_media_members (media_meta_id, member_id) VALUES (?, ?)"
                );
                foreach ($memberIds as $mid) {
                    $insertStmt->execute([$metaId, $mid]);
                }
                $stats['members_linked']++;
            }
        }

        if ($isNew) {
            $stats['new']++;
        } else {
            $stats['existing']++;
        }
    }

    usleep(500000);
}

$output = [
    'status' => 'success',
    'stats'  => $stats,
    'errors' => $errors,
];

if ($isCli) {
    echo date('Y-m-d H:i:s') . ' YouTube import completed.' . PHP_EOL;
    echo '  Channels: ' . $stats['channels'] . ', Fetched: ' . $stats['fetched'] . PHP_EOL;
    echo '  New: ' . $stats['new'] . ', Existing: ' . $stats['existing'] . PHP_EOL;
    echo '  Members linked: ' . $stats['members_linked'] . PHP_EOL;
    if (!empty($errors)) {
        echo '  Errors: ' . implode(', ', $errors) . PHP_EOL;
    }
} else {
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
