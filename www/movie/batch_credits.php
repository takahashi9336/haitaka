<?php
/**
 * 映画：出演者（俳優/監督/脚本）をTMDBから取得してDB登録する一時バッチ
 * 物理パス: haitaka/www/movie/batch_credits.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Logger;
use App\Movie\Model\TmdbApiClient;
use App\Movie\Service\MovieCreditsService;

$auth = new Auth();
if (!$auth->check()) { header('Location: /login.php'); exit; }

$tmdb = new TmdbApiClient();
if (!$tmdb->isConfigured()) { die('TMDB API not configured'); }

$pdo = \Core\Database::connect();
$userId = (int)($_SESSION['user']['id'] ?? 0);

function getStatusFilePath(int $userId): string {
    $dir = __DIR__ . '/../../private/cache/batch';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir . '/movie_credits_status_' . $userId . '.json';
}

function readStatus(int $userId): array {
    $path = getStatusFilePath($userId);
    if (!file_exists($path)) {
        return [
            'status' => 'idle',
            'processed' => 0,
            'total' => 0,
            'errors' => 0,
            'started_at' => null,
            'updated_at' => null,
            'last_title' => null,
        ];
    }
    $raw = @file_get_contents($path);
    $json = $raw ? json_decode($raw, true) : null;
    return is_array($json) ? $json : [
        'status' => 'unknown',
        'processed' => 0,
        'total' => 0,
        'errors' => 0,
        'started_at' => null,
        'updated_at' => null,
        'last_title' => null,
    ];
}

function writeStatus(int $userId, array $data): void {
    $path = getStatusFilePath($userId);
    $data['updated_at'] = date('Y-m-d H:i:s');
    @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// 進捗確認（JSON）
if (isset($_GET['status']) && isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(readStatus($userId), JSON_UNESCAPED_UNICODE);
    exit;
}

function countRemaining(\PDO $pdo): int {
    $stmt = $pdo->query("
        SELECT COUNT(*) AS cnt
        FROM mv_movies m
        WHERE m.tmdb_id IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM mv_movie_credits c WHERE c.movie_id = m.id)
    ");
    return (int)($stmt->fetchColumn() ?: 0);
}

function fetchNextBatch(\PDO $pdo, int $afterMovieId, int $batchSize): array {
    $stmt = $pdo->prepare("
        SELECT m.id, m.tmdb_id, m.title
        FROM mv_movies m
        WHERE m.tmdb_id IS NOT NULL
          AND m.id > :after_id
          AND NOT EXISTS (SELECT 1 FROM mv_movie_credits c WHERE c.movie_id = m.id)
        ORDER BY m.id
        LIMIT :lim
    ");
    $stmt->bindValue('after_id', $afterMovieId, \PDO::PARAM_INT);
    $stmt->bindValue('lim', $batchSize, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

$totalRemaining = countRemaining($pdo);

if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $batchSize = 3;
        $cursor = (int)($_GET['cursor'] ?? 0); // 最後に処理した mv_movies.id（0は先頭）
        $slice = fetchNextBatch($pdo, $cursor, $batchSize);
        $results = [];

        // 初回のキック時にステータスを初期化
        if ($cursor === 0) {
            writeStatus($userId, [
                'status' => 'running',
                'processed' => 0,
                'total' => $totalRemaining,
                'errors' => 0,
                'started_at' => date('Y-m-d H:i:s'),
                'last_title' => null,
                'cursor' => 0,
            ]);
        }
        $st = readStatus($userId);
        $processedSoFar = (int)($st['processed'] ?? 0);
        $errorsSoFar = (int)($st['errors'] ?? 0);
        $startedAt = $st['started_at'] ?? date('Y-m-d H:i:s');
        $newCursor = (int)($st['cursor'] ?? 0);

        foreach ($slice as $mv) {
            $tmdbId = (int)($mv['tmdb_id'] ?? 0);
            if ($tmdbId <= 0) continue;
            $newCursor = (int)($mv['id'] ?? 0);

            $detail = $tmdb->getMovieDetail($tmdbId);
            if (!$detail) {
                $results[] = [
                    'id' => (int)$mv['id'],
                    'title' => $mv['title'],
                    'error' => 'TMDB取得に失敗しました',
                ];
                $processedSoFar++;
                $errorsSoFar++;
                writeStatus($userId, [
                    'status' => 'running',
                    'processed' => $processedSoFar,
                    'total' => (int)($st['total'] ?? $totalRemaining),
                    'errors' => $errorsSoFar,
                    'started_at' => $startedAt,
                    'last_title' => $mv['title'] ?? null,
                    'cursor' => $newCursor,
                ]);
                usleep(600000);
                continue;
            }

            $info = MovieCreditsService::saveFromTmdbDetail($pdo, (int)$mv['id'], $tmdbId, $detail);
            $results[] = [
                'id' => (int)$mv['id'],
                'title' => $mv['title'],
                'saved' => (int)($info['saved'] ?? 0),
                'cast' => $info['cast'] ?? [],
                'directors' => $info['directors'] ?? [],
                'writers' => $info['writers'] ?? [],
            ];
            $processedSoFar++;
            if (!($info['ok'] ?? false)) {
                $errorsSoFar++;
            }
            writeStatus($userId, [
                'status' => 'running',
                'processed' => $processedSoFar,
                'total' => (int)($st['total'] ?? $totalRemaining),
                'errors' => $errorsSoFar,
                'started_at' => $startedAt,
                'last_title' => $mv['title'] ?? null,
                'cursor' => $newCursor,
            ]);

            // レート制限対策（ジッター付き）
            usleep(450000 + random_int(0, 250000));
        }

        // slice が空なら、現時点で未投入が残っていない（=done）
        $done = empty($slice);
        if ($done) {
            writeStatus($userId, [
                'status' => 'done',
                'processed' => $processedSoFar,
                'total' => (int)($st['total'] ?? $totalRemaining),
                'errors' => $errorsSoFar,
                'started_at' => $startedAt,
                'last_title' => $st['last_title'] ?? null,
                'cursor' => $newCursor,
                'remaining' => countRemaining($pdo),
            ]);
        }

        echo json_encode([
            'processed' => $results,
            'done' => $done,
            'next_cursor' => $newCursor,
            'total' => (int)($st['total'] ?? $totalRemaining),
            'remaining' => countRemaining($pdo),
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        Logger::errorWithContext('batch_credits: ' . $e->getMessage(), $e);
        $st = readStatus($userId);
        writeStatus($userId, [
            'status' => 'error',
            'processed' => (int)($st['processed'] ?? 0),
            'total' => (int)($st['total'] ?? $totalRemaining),
            'errors' => (int)($st['errors'] ?? 0) + 1,
            'started_at' => $st['started_at'] ?? null,
            'last_title' => $st['last_title'] ?? null,
            'cursor' => (int)($st['cursor'] ?? 0),
            'remaining' => countRemaining($pdo),
        ]);
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画：出演者情報投入バッチ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-8 max-w-2xl w-full">
        <h1 class="text-xl font-black text-slate-800 mb-2">
            <i class="fa-solid fa-users mr-2 text-violet-500"></i>映画：出演者情報投入バッチ
        </h1>
        <p class="text-sm text-slate-500 mb-2">
            対象: credits 未投入の映画（TMDB連携済み） / 保存: cast上位<?= \App\Movie\Service\MovieCreditsService::SAVE_CAST_LIMIT ?>、監督上位<?= \App\Movie\Service\MovieCreditsService::SAVE_DIRECTOR_LIMIT ?>、脚本上位<?= \App\Movie\Service\MovieCreditsService::SAVE_WRITER_LIMIT ?>
        </p>
        <p class="text-sm text-slate-500 mb-1">対象件数: <strong id="totalCount"><?= (int)$totalRemaining ?></strong> 件</p>
        <p class="text-xs text-slate-400 mb-6">
            進捗確認: <a class="underline hover:text-slate-600" href="?status=1">このURL</a>
            / 自動開始: <a class="underline hover:text-slate-600" href="?autostart=1">このURL</a>
        </p>

        <?php if ($total === 0): ?>
        <div class="text-center py-8 text-green-600 font-bold">
            <i class="fa-solid fa-check-circle text-3xl mb-2 block"></i>
            すべて投入済みです
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

        <div class="bg-slate-50 rounded-xl p-4 max-h-72 overflow-y-auto text-xs text-slate-700 space-y-2 mb-4 font-mono" id="log"></div>

        <button id="startBtn" onclick="startBatch()" class="w-full py-3 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl transition">
            <i class="fa-solid fa-play mr-2"></i>開始
        </button>

        <div id="doneMsg" class="hidden text-center py-4 text-green-600 font-bold">
            <i class="fa-solid fa-check-circle text-2xl mb-2 block"></i>
            完了しました
        </div>
        <?php endif; ?>

        <a href="/movie/" class="block text-center text-sm text-slate-400 hover:text-slate-600 mt-4 transition">
            ← 映画へ戻る
        </a>
    </div>

    <script>
    const total = <?= (int)$totalRemaining ?>;
    let processed = 0;
    const autostart = <?= isset($_GET['autostart']) ? 'true' : 'false' ?>;
    const statusMode = <?= isset($_GET['status']) ? 'true' : 'false' ?>;
    let nextCursor = 0;

    function esc(s) {
        return (s ?? '').toString().replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    }

    function addLog(html) {
        const log = document.getElementById('log');
        const div = document.createElement('div');
        div.innerHTML = html;
        log.appendChild(div);
        log.scrollTop = log.scrollHeight;
    }

    function updateProgress() {
        const pct = total > 0 ? Math.round((processed / total) * 100) : 100;
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('progressText').textContent = `${processed} / ${total}`;
    }

    async function startBatch() {
        const btn = document.getElementById('startBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>処理中...';
        await processBatch(0);
    }

    async function processBatch(cursor) {
        try {
            const res = await fetch(`?api=1&cursor=${cursor}`);
            const json = await res.json();
            (json.processed || []).forEach(r => {
                processed++;
                if (r.error) {
                    addLog(`<span class="text-red-600">×</span> ${esc(r.title)}: ${esc(r.error)}`);
                    return;
                }
                const cast = (r.cast || []).join(', ');
                const directors = (r.directors || []).join(', ');
                const writers = (r.writers || []).join(', ');
                addLog(`<span class="text-emerald-600">✓</span> ${esc(r.title)}<br>` +
                    `<span class="text-slate-500"> cast:</span> ${esc(cast)}<br>` +
                    `<span class="text-slate-500"> director:</span> ${esc(directors)}<br>` +
                    `<span class="text-slate-500"> writer:</span> ${esc(writers)}`);
            });
            updateProgress();
            if (!json.done) {
                nextCursor = json.next_cursor || cursor;
                await processBatch(nextCursor);
            } else {
                document.getElementById('startBtn')?.classList.add('hidden');
                document.getElementById('doneMsg')?.classList.remove('hidden');
                if (typeof json.remaining !== 'undefined') {
                    addLog(`<span class="text-slate-500">remaining:</span> ${esc(json.remaining)}`);
                }
            }
        } catch (e) {
            addLog(`<span class="text-red-600">×</span> エラー: ${esc(e.message)}`);
            const btn = document.getElementById('startBtn');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-play mr-2"></i>再開';
        }
    }

    async function pollStatus() {
        try {
            const res = await fetch(`?status=1&json=1`);
            const st = await res.json();
            const p = parseInt(st.processed || 0, 10);
            const t = parseInt(st.total || 0, 10);
            const pct = t > 0 ? Math.round((p / t) * 100) : 0;
            const bar = document.getElementById('progressBar');
            const txt = document.getElementById('progressText');
            if (bar) bar.style.width = pct + '%';
            if (txt) txt.textContent = `${p} / ${t || total}`;
            const log = document.getElementById('log');
            if (log) {
                const last = st.last_title ? ` 最終: ${st.last_title}` : '';
                const rem = (typeof st.remaining !== 'undefined') ? ` / remaining: ${esc(st.remaining)}` : '';
                log.innerHTML = `<div>status: ${esc(st.status || 'unknown')} / errors: ${esc(st.errors ?? 0)}${rem} / updated: ${esc(st.updated_at ?? '')}${esc(last)}</div>` + log.innerHTML;
            }
            if ((st.status === 'done' || st.status === 'error') && document.getElementById('startBtn')) {
                document.getElementById('startBtn')?.classList.add('hidden');
                document.getElementById('doneMsg')?.classList.remove('hidden');
            }
        } catch (e) {
            // ignore
        } finally {
            setTimeout(pollStatus, 3000);
        }
    }

    async function init() {
        if (statusMode) {
            pollStatus();
            return;
        }
        if (!autostart) return;

        // autostart=1 は「キックしつつ進捗を見る」用。
        // ただし実行中にリロードした場合、最初からキックし直さず進捗表示に切り替える。
        try {
            const res = await fetch(`?status=1&json=1`);
            const st = await res.json();
            if (st && (st.status === 'running')) {
                pollStatus();
                return;
            }
        } catch (e) {
            // ignore: fallback to start
        }
        startBatch();
    }

    init();
    </script>
</body>
</html>

