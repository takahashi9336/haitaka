<?php
/**
 * ニューススクレイピング バッチエンドポイント
 * GET ?months=2  (デフォルト: 当月+翌月)
 * 管理者認証必須。CLI からも実行可能。
 */
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Model\NewsScraper;
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

$monthCount = max(1, min(6, (int)($isCli ? ($argv[1] ?? 2) : ($_GET['months'] ?? 2))));
$pastMonths = max(0, min(12, (int)($isCli ? ($argv[2] ?? 0) : ($_GET['past'] ?? 0))));

$scraper = new NewsScraper();
$model   = new NewsModel();
$nameMap = $model->getMemberNameMap();

$stats = ['inserted' => 0, 'updated' => 0, 'members_linked' => 0];
$allErrors = [];

$offsets = [];
for ($i = $pastMonths; $i > 0; $i--) $offsets[] = -$i;
for ($i = 0; $i < $monthCount; $i++) $offsets[] = $i;

foreach ($offsets as $offset) {
    $ym = date('Ym', strtotime("{$offset} months"));
    $items = $scraper->scrapeMonth($ym);

    foreach ($items as $item) {
        $result = $model->upsertNews($item);
        $stats[$result] = ($stats[$result] ?? 0) + 1;

        $newsId = $model->getIdByCode($item['article_code']);
        if ($newsId) {
            $memberIds = $model->detectMembers($item['title'], $nameMap);
            $model->setMembers($newsId, $memberIds);
            if (!empty($memberIds)) {
                $stats['members_linked']++;
            }
        }
    }

    usleep(500000);
}

$allErrors = array_merge($allErrors, $scraper->getErrors());

$output = [
    'status' => 'success',
    'stats'  => $stats,
    'errors' => $allErrors,
];

if ($isCli) {
    echo date('Y-m-d H:i:s') . ' News scrape completed.' . PHP_EOL;
    echo '  Inserted: ' . ($stats['inserted'] ?? 0) . ', Updated: ' . ($stats['updated'] ?? 0) . PHP_EOL;
    echo '  Members linked: ' . $stats['members_linked'] . PHP_EOL;
    if (!empty($allErrors)) {
        echo '  Errors: ' . implode(', ', $allErrors) . PHP_EOL;
    }
} else {
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
