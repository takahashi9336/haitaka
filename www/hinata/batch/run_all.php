<?php
/**
 * Hinata バッチ一括ランナー (cron 用)
 *
 * 毎回実行 (3時間ごと):
 *   - ブログ最新取得 (latest, 1ページ)
 *   - ニュース (当月+翌月)
 *   - スケジュール (当月+翌月)
 *   - YouTube 新着取り込み
 *
 * 週1回 (日曜 9:00):
 *   - YouTube メタデータ更新 (50件)
 *   - ブログ全メンバー補充 (mode=members, 1ページ)
 *
 * cron 式: 0 0,9,12,15,18,21 * * *
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$startTime = microtime(true);
$batchDir = __DIR__;

echo str_repeat('=', 60) . PHP_EOL;
echo date('Y-m-d H:i:s') . ' Hinata batch runner started' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

$isSundayMorning = (date('w') === '0' && date('H') === '09');

// --- 毎回実行 ---

runBatch('Blog (latest)', $batchDir . '/blog_scrape.php', ['1', 'latest']);
runBatch('News', $batchDir . '/news_scrape.php', ['2']);
runBatch('Schedule', $batchDir . '/schedule_scrape.php', ['2']);
runBatch('YouTube import', $batchDir . '/youtube_import.php', []);

// --- 週次 (日曜 9:00 のみ) ---

if ($isSundayMorning) {
    echo PHP_EOL . '--- Weekly tasks (Sunday 09:00) ---' . PHP_EOL;
    runBatch('YouTube refresh', $batchDir . '/youtube_refresh.php', ['50']);
    runBatch('Blog (all members)', $batchDir . '/blog_scrape.php', ['1', 'members']);
}

$elapsed = round(microtime(true) - $startTime, 1);
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo date('Y-m-d H:i:s') . " All done ({$elapsed}s)" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

/**
 * 個別バッチを子プロセスとして実行
 */
function runBatch(string $label, string $scriptPath, array $args = []): void
{
    echo PHP_EOL . ">> {$label}" . PHP_EOL;

    if (!file_exists($scriptPath)) {
        echo "   SKIP: {$scriptPath} not found" . PHP_EOL;
        return;
    }

    $phpBin = PHP_BINARY ?: '/usr/local/bin/php';
    $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($scriptPath);
    foreach ($args as $arg) {
        $cmd .= ' ' . escapeshellarg($arg);
    }
    $cmd .= ' 2>&1';

    $t = microtime(true);
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    $dt = round(microtime(true) - $t, 1);

    foreach ($output as $line) {
        echo "   {$line}" . PHP_EOL;
    }

    if ($exitCode !== 0) {
        echo "   WARNING: exit code {$exitCode}" . PHP_EOL;
    }
    echo "   ({$dt}s)" . PHP_EOL;
}
