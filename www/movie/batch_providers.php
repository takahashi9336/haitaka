<?php
/**
 * 配信サービス一括取得バッチ（一時利用、後で削除）
 * ブラウザでアクセスして実行。TMDB APIのレートリミット対策で1件ずつ間隔を空けて処理。
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use Core\Auth;
use App\Movie\Model\MovieModel;
use App\Movie\Model\TmdbApiClient;

$auth = new Auth();
if (!$auth->check()) { header('Location: /login.php'); exit; }

$tmdb = new TmdbApiClient();
if (!$tmdb->isConfigured()) { die('TMDB API not configured'); }

$movieModel = new MovieModel();
$pdo = \Core\Database::connect();

$stmt = $pdo->query("SELECT id, tmdb_id, title FROM mv_movies WHERE tmdb_id IS NOT NULL AND (watch_providers IS NULL OR watch_providers = '') ORDER BY id");
$movies = $stmt->fetchAll();
$total = count($movies);

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $offset = (int)($_GET['offset'] ?? 0);
    $batchSize = 5;
    $slice = array_slice($movies, $offset, $batchSize);
    $results = [];
    foreach ($slice as $mv) {
        $detail = $tmdb->getMovieDetail((int)$mv['tmdb_id']);
        $jp = $detail['watch/providers']['results']['JP'] ?? null;
        $movieModel->updateWatchProviders((int)$mv['id'], $jp);
        $providerNames = [];
        if ($jp && !empty($jp['flatrate'])) {
            foreach ($jp['flatrate'] as $p) $providerNames[] = $p['provider_name'] ?? '';
        }
        $results[] = [
            'id' => $mv['id'],
            'title' => $mv['title'],
            'providers' => $providerNames ?: null,
        ];
        usleep(300000);
    }
    echo json_encode([
        'processed' => $results,
        'done' => $offset + $batchSize >= $total,
        'next_offset' => $offset + $batchSize,
        'total' => $total,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>配信サービス一括取得</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-8 max-w-lg w-full">
        <h1 class="text-xl font-black text-slate-800 mb-2"><i class="fa-solid fa-tv mr-2 text-violet-500"></i>配信サービス一括取得</h1>
        <p class="text-sm text-slate-500 mb-6">未取得の映画: <strong id="totalCount"><?= $total ?></strong> 件</p>

        <?php if ($total === 0): ?>
        <div class="text-center py-8 text-green-600 font-bold">
            <i class="fa-solid fa-check-circle text-3xl mb-2 block"></i>
            すべての映画で配信情報取得済みです
        </div>
        <?php else: ?>
        <div id="progress" class="mb-4">
            <div class="flex justify-between text-xs text-slate-500 mb-1">
                <span>進捗</span>
                <span id="progressText">0 / <?= $total ?></span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                <div id="progressBar" class="h-full bg-violet-500 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <div id="log" class="bg-slate-50 rounded-xl p-4 max-h-60 overflow-y-auto text-xs text-slate-600 space-y-1 mb-4 font-mono"></div>

        <button id="startBtn" onclick="startBatch()" class="w-full py-3 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl transition">
            <i class="fa-solid fa-play mr-2"></i>実行開始
        </button>

        <div id="doneMsg" class="hidden text-center py-4 text-green-600 font-bold">
            <i class="fa-solid fa-check-circle text-2xl mb-2 block"></i>
            完了しました！
        </div>
        <?php endif; ?>

        <a href="/movie/" class="block text-center text-sm text-slate-400 hover:text-slate-600 mt-4 transition">← ダッシュボードに戻る</a>
    </div>

    <script>
    const total = <?= $total ?>;
    let processed = 0;

    async function startBatch() {
        document.getElementById('startBtn').disabled = true;
        document.getElementById('startBtn').innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>処理中...';
        await processBatch(0);
    }

    async function processBatch(offset) {
        try {
            const res = await fetch(`?api=1&offset=${offset}`);
            const json = await res.json();
            json.processed.forEach(m => {
                processed++;
                const prov = m.providers ? m.providers.join(', ') : '配信なし';
                addLog(`✓ ${m.title} → ${prov}`);
            });
            updateProgress();
            if (!json.done) {
                await processBatch(json.next_offset);
            } else {
                document.getElementById('startBtn').classList.add('hidden');
                document.getElementById('doneMsg').classList.remove('hidden');
            }
        } catch (e) {
            addLog(`✗ エラー: ${e.message}`);
            document.getElementById('startBtn').disabled = false;
            document.getElementById('startBtn').innerHTML = '<i class="fa-solid fa-play mr-2"></i>再開';
        }
    }

    function addLog(msg) {
        const log = document.getElementById('log');
        const line = document.createElement('div');
        line.textContent = msg;
        log.appendChild(line);
        log.scrollTop = log.scrollHeight;
    }

    function updateProgress() {
        const pct = Math.round((processed / total) * 100);
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('progressText').textContent = `${processed} / ${total}`;
    }
    </script>
</body>
</html>
