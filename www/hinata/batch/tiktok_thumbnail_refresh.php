<?php
/**
 * TikTok サムネイル再取得バッチ
 *
 * 既存TikTok動画のoEmbed APIからサムネイルを取得し、
 * サーバに保存して thumbnail_url を更新する。
 * （TikTok CDN のホットリンク対策を回避するため）
 *
 * GET ?limit=50 / CLI: php tiktok_thumbnail_refresh.php [limit]
 * 1回の実行で指定件数まで処理
 */
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Logger;
use App\Hinata\Controller\MediaController;

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

try {
    $controller = new MediaController();
    $stats = $controller->refreshTikTokThumbnails($limit);

    $output = [
        'status' => 'success',
        'stats'  => $stats,
    ];

    if ($isCli) {
        echo date('Y-m-d H:i:s') . " TikTok thumbnail refresh completed.\n";
        echo '  Checked: ' . $stats['checked'] . ', Saved: ' . $stats['saved'] . "\n";
        echo '  Skipped: ' . $stats['skipped'] . ', Error: ' . $stats['error'] . "\n";
    } else {
        echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} catch (\Throwable $e) {
    Logger::errorWithContext('tiktok_thumbnail_refresh: ' . $e->getMessage(), $e);
    if ($isCli) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit(1);
}
