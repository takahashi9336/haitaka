<?php
/**
 * 既存 YouTube 動画 メタデータ更新バッチ
 *
 * DB 登録済みの YouTube 動画のタイトル・サムネ・説明文を再取得して最新化。
 * YouTube API 規約: 30日以内にキャッシュデータを更新する義務への対応。
 *
 * GET ?limit=50 / CLI: php youtube_refresh.php [limit]
 * 週1回の実行を想定。
 */
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use Core\Database;
use Core\MediaAssetModel;
use App\Hinata\Model\YouTubeApiClient;

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

$limit = max(1, min(200, (int)($isCli ? ($argv[1] ?? 50) : ($_GET['limit'] ?? 50))));

$yt = new YouTubeApiClient();
if (!$yt->isConfigured()) {
    $msg = 'YouTube API key not configured';
    if ($isCli) { echo $msg . PHP_EOL; exit(1); }
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

$pdo = Database::connect();
$assetModel = new MediaAssetModel();

// upload_date の古い順に取得 (長期間更新されていない動画を優先)
$stmt = $pdo->prepare(
    "SELECT id, media_key FROM com_media_assets
     WHERE platform = 'youtube'
     ORDER BY COALESCE(upload_date, created_at) ASC
     LIMIT :lim"
);
$stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
$stmt->execute();
$assets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (empty($assets)) {
    $msg = 'No YouTube assets to refresh';
    if ($isCli) { echo $msg . PHP_EOL; exit(0); }
    echo json_encode(['status' => 'success', 'message' => $msg]);
    exit;
}

$videoIds = array_column($assets, 'media_key');
$idToAssetId = [];
foreach ($assets as $a) {
    $idToAssetId[$a['media_key']] = (int)$a['id'];
}

if ($isCli) echo "Fetching details for " . count($videoIds) . " videos..." . PHP_EOL;

$details = $yt->getVideoDetails($videoIds);

$stats = ['checked' => count($videoIds), 'updated' => 0, 'deleted' => 0, 'api_returned' => count($details)];

foreach ($videoIds as $vid) {
    $assetId = $idToAssetId[$vid];

    if (!isset($details[$vid])) {
        // 動画が削除/非公開になっている可能性
        $stats['deleted']++;
        continue;
    }

    $d = $details[$vid];
    $updated = false;

    if (!empty($d['title'])) {
        $assetModel->updateAssetField($assetId, 'title', $d['title']);
        $updated = true;
    }
    if (!empty($d['thumbnail_url'])) {
        $assetModel->updateAssetField($assetId, 'thumbnail_url', $d['thumbnail_url']);
    }
    if (!empty($d['description'])) {
        $assetModel->updateAssetField($assetId, 'description', $d['description']);
    }
    if (!empty($d['media_type'])) {
        $assetModel->updateAssetField($assetId, 'media_type', $d['media_type']);
    }

    if ($updated) $stats['updated']++;
}

$output = [
    'status' => 'success',
    'stats'  => $stats,
];

if ($isCli) {
    echo date('Y-m-d H:i:s') . ' YouTube refresh completed.' . PHP_EOL;
    echo '  Checked: ' . $stats['checked'] . ', Updated: ' . $stats['updated'] . PHP_EOL;
    echo '  API returned: ' . $stats['api_returned'] . ', Possibly deleted: ' . $stats['deleted'] . PHP_EOL;
} else {
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
