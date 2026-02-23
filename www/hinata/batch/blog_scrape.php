<?php
/**
 * ?????????? ??????????
 *
 * ???1 (?????): ??????????
 *   GET ?pages=2
 *
 * ???2: ?????????????? (????? pages ?????)
 *   GET ?mode=members&pages=2
 *
 * ????????CLI ????????
 *   CLI: php blog_scrape.php [pages] [mode]
 *   ?:  php blog_scrape.php 2 members
 */
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Logger;
use App\Hinata\Model\BlogScraper;
use App\Hinata\Model\BlogModel;

if (!$isCli) {
    $auth = new Auth();
    $auth->requireLogin();
    if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => '????????']);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

$pages = max(1, min(10, (int)($isCli ? ($argv[1] ?? 1) : ($_GET['pages'] ?? 1))));
$mode  = $isCli ? ($argv[2] ?? 'latest') : ($_GET['mode'] ?? 'latest');

try {
$scraper = new BlogScraper();
$model   = new BlogModel();
$nameMap = $model->getMemberNameMap();

$stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'no_member' => 0, 'members_done' => 0];

if ($mode === 'members') {
    // DB ? official_blog_ct ?? ct ??????
    $ctMap = $model->getCtToMemberIdMap(); // [ct => member_id]

    // DB ? ct ?????????????????????
    if (empty($ctMap)) {
        $discoveredCts = $scraper->discoverMemberCts(); // [???? => ct]
        // nameMap ? [???? => member_id]
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
        $memberName = str_replace([' ', '?'], '', $art['member_name'] ?? '');
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
} catch (\Throwable $e) {
    Logger::errorWithContext('blog_scrape: ' . $e->getMessage(), $e);
    if ($isCli) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit(1);
}
