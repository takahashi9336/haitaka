<?php
/**
 * ブログスクレイピング バッチエンドポイント
 *
 * モード1 (デフォルト): 全体の最新記事を取得
 *   GET ?pages=2
 *
 * モード2: 全メンバーを順番にスクレイプ (各メンバー pages ページずつ)
 *   GET ?mode=members&pages=2
 *
 * 管理者認証必須。CLI からも実行可能。
 *   CLI: php blog_scrape.php [pages] [mode]
 *   例:  php blog_scrape.php 2 members
 */
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Model\BlogScraper;
use App\Hinata\Model\BlogModel;

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

$pages = max(1, min(10, (int)($isCli ? ($argv[1] ?? 1) : ($_GET['pages'] ?? 1))));
$mode  = $isCli ? ($argv[2] ?? 'latest') : ($_GET['mode'] ?? 'latest');

$scraper = new BlogScraper();
$model   = new BlogModel();
$nameMap = $model->getMemberNameMap();

$stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'no_member' => 0, 'members_done' => 0];

if ($mode === 'members') {
    // DB の official_blog_ct から ct マップを取得
    $ctMap = $model->getCtToMemberIdMap(); // [ct => member_id]

    // DB に ct が無い場合、公式サイトから自動取得して補完
    if (empty($ctMap)) {
        $discoveredCts = $scraper->discoverMemberCts(); // [正規化名 => ct]
        // nameMap は [正規化名 => member_id]
        foreach ($discoveredCts as $normName => $ct) {
            if (isset($nameMap[$normName])) {
                $ctMap[$ct] = $nameMap[$normName];
            }
        }
    }

    $total = count($ctMap);
    foreach ($ctMap as $ct => $memberId) {
        $articles = $scraper->scrapeMember($ct, $pages);
        foreach ($articles as $art) {
            $art['member_id'] = $memberId;
            $result = $model->upsertArticle($art);
            $stats[$result]++;
        }
        $stats['members_done']++;

        if ($isCli) {
            echo '  [' . $stats['members_done'] . '/' . $total . '] ct=' . $ct . ' => ' . count($articles) . " articles\n";
        }

        usleep(500000);
    }
} else {
    $articles = $scraper->scrapeLatest($pages);

    foreach ($articles as $art) {
        $memberName = str_replace([' ', '　'], '', $art['member_name'] ?? '');
        $memberId = $nameMap[$memberName] ?? null;
        if ($memberId === null) {
            $stats['no_member']++;
        }
        $art['member_id'] = $memberId;
        $result = $model->upsertArticle($art);
        $stats[$result]++;
    }
}

$output = [
    'status'  => 'success',
    'mode'    => $mode,
    'pages'   => $pages,
    'stats'   => $stats,
    'errors'  => $scraper->getErrors(),
];

if ($isCli) {
    echo date('Y-m-d H:i:s') . " Blog scrape completed (mode={$mode})." . PHP_EOL;
    echo '  Inserted: ' . $stats['inserted'] . ', Updated: ' . $stats['updated'] . PHP_EOL;
    if ($mode === 'members') {
        echo '  Members processed: ' . $stats['members_done'] . PHP_EOL;
    }
    if ($stats['no_member'] > 0) {
        echo '  No member match: ' . $stats['no_member'] . PHP_EOL;
    }
    if (!empty($output['errors'])) {
        echo '  Errors: ' . implode(', ', $output['errors']) . PHP_EOL;
    }
} else {
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
