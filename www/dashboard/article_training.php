<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

$auth = new Auth();
if (!$auth->check()) {
    header('Location: /login.php');
    exit;
}
$user = $_SESSION['user'];

/**
 * 記事トレーニング用 URL を検証・正規化する（http/https のみ、最大500文字）。
 *
 * @return array{ok:bool, url:string, error:string}
 */
function dashboard_normalize_article_training_url(string $raw): array
{
    $u = trim($raw);
    if ($u === '') {
        return ['ok' => false, 'url' => '', 'error' => ''];
    }
    if (!preg_match('#\Ahttps?://#i', $u)) {
        $u = 'https://' . ltrim($u, '/');
    }
    if (mb_strlen($u) > 500) {
        return ['ok' => false, 'url' => $u, 'error' => '記事URLは500文字以内にしてください。'];
    }
    $p = parse_url($u);
    if ($p === false || empty($p['scheme']) || empty($p['host'])) {
        return ['ok' => false, 'url' => $u, 'error' => '有効な記事URLを入力してください。'];
    }
    if (!in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
        return ['ok' => false, 'url' => $u, 'error' => '記事URLは http:// または https:// で始まる形式にしてください。'];
    }
    return ['ok' => true, 'url' => $u, 'error' => ''];
}

$rawUrl = isset($_GET['url']) ? trim((string) $_GET['url']) : '';
$articleTitleInput = isset($_GET['title']) ? trim((string) $_GET['title']) : '';
if (mb_strlen($articleTitleInput) > 500) {
    $articleTitleInput = mb_substr($articleTitleInput, 0, 500);
}

$articleUrl = '';
$articleTitle = '';
$hasArticle = false;
$setupError = '';
$prefillUrl = '';
$prefillTitle = '';

if ($rawUrl !== '') {
    $norm = dashboard_normalize_article_training_url($rawUrl);
    if ($norm['ok']) {
        $articleUrl = $norm['url'];
        $articleTitle = $articleTitleInput;
        $hasArticle = true;
    } else {
        $setupError = $norm['error'];
        $prefillUrl = $rawUrl;
        $prefillTitle = $articleTitleInput;
    }
}

$existing = [];
if ($hasArticle) {
    $pdo = Database::connect();
    $stmt = $pdo->prepare('SELECT * FROM dashboard_article_training WHERE user_id = ? AND article_url = ? LIMIT 1');
    $stmt->execute([$user['id'], $articleUrl]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$appKey = 'dashboard';
require_once __DIR__ . '/../../private/components/theme_from_session.php';

if ($hasArticle && !empty($existing['article_title'])) {
    $articleTitle = $existing['article_title'];
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>記事トレーニング - ダッシュボード</title>
    <?php require_once __DIR__ . '/../../private/components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter">記事トレーニング</h1>
                    <p class="text-xs text-slate-400 truncate">
                        <?php if ($hasArticle): ?>
                            「ほめポイント」と「ツッコミポイント」を3つずつ書き出してみましょう。
                        <?php else: ?>
                            記事のURLを入力してトレーニングを始められます。
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-3xl mx-auto space-y-6">

                <?php if (!$hasArticle): ?>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <p class="text-[11px] font-bold tracking-wider text-slate-500"><i class="fa-solid fa-link mr-1"></i>トレーニングする記事</p>
                        <div class="flex items-center gap-1 shrink-0" title="ニュースサイト">
                            <a href="https://news.yahoo.co.jp/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 text-[#6001d2] hover:bg-slate-50 active:scale-95 transition" aria-label="Yahoo!ニュース（新しいタブ）">
                                <i class="fa-brands fa-yahoo text-[11px]"></i>
                            </a>
                            <a href="https://news.google.com/home?hl=ja&gl=JP&ceid=JP%3Aja" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-blue-600 active:scale-95 transition" aria-label="Google ニュース（新しいタブ）">
                                <i class="fa-brands fa-google text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    <?php if ($setupError !== ''): ?>
                        <p class="text-xs text-red-600 mb-3 font-medium"><?= htmlspecialchars($setupError) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-slate-500 mb-4">読みたい記事のURLを貼り付けてください。タイトルは任意です（空の場合はURLが表示名になります）。</p>
                    <form method="get" action="/dashboard/article_training.php" class="space-y-4">
                        <div>
                            <label for="setup_url" class="text-[11px] font-medium text-slate-500 mb-1 block">記事URL <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="url"
                                id="setup_url"
                                required
                                maxlength="500"
                                inputmode="url"
                                autocomplete="off"
                                placeholder="https://example.com/article（スキーム省略可）"
                                value="<?= htmlspecialchars($prefillUrl) ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent"
                            >
                        </div>
                        <div>
                            <label for="setup_title" class="text-[11px] font-medium text-slate-500 mb-1 block">記事タイトル（任意）</label>
                            <input
                                type="text"
                                name="title"
                                id="setup_title"
                                maxlength="500"
                                placeholder="一覧や履歴に表示する名前"
                                value="<?= htmlspecialchars($prefillTitle) ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent"
                            >
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-sm hover:shadow-md active:scale-[0.98] transition">
                                <i class="fa-solid fa-play"></i>
                                この記事でトレーニング
                            </button>
                            <a href="/dashboard/article_training_history.php" class="text-xs text-slate-500 hover:text-slate-700">
                                <i class="fa-solid fa-clock-rotate-left mr-1"></i>履歴へ
                            </a>
                            <a href="/" class="text-xs text-slate-500 hover:text-slate-700">
                                <i class="fa-solid fa-chevron-left mr-1"></i>ダッシュボード
                            </a>
                        </div>
                    </form>
                </section>

                <?php else: ?>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <p class="text-[11px] font-bold tracking-wider text-slate-500 mb-2"><i class="fa-solid fa-newspaper mr-1"></i>トレーニング対象の記事</p>
                    <a href="<?= htmlspecialchars($articleUrl) ?>" target="_blank" rel="noopener noreferrer" class="block group">
                        <p class="text-sm font-bold text-slate-800 group-hover:text-slate-900">
                            <?= $articleTitle !== '' ? htmlspecialchars($articleTitle) : 'タイトル未取得の記事' ?>
                        </p>
                        <p class="text-[11px] text-slate-400 break-all mt-1"><?= htmlspecialchars($articleUrl) ?></p>
                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 mt-2">
                            記事を開いてざっと読んでみましょう
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </span>
                    </a>
                    <p class="mt-4 pt-4 border-t border-slate-100">
                        <a href="/dashboard/article_training.php" class="text-xs font-medium text-slate-500 hover:text-slate-800">
                            <i class="fa-solid fa-rotate mr-1"></i>別の記事のURLを入力する
                        </a>
                    </p>
                </section>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <p class="text-[11px] font-bold tracking-wider text-slate-600 mb-0.5"><i class="fa-solid fa-hourglass-start mr-1"></i>タイマー</p>
                            <p class="text-xs text-slate-500">設定した時間でカウントダウンします。終了時に通知します（設定はブラウザに保存されます）。</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0" title="ニュースサイト">
                            <a href="https://news.yahoo.co.jp/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 text-[#6001d2] hover:bg-slate-50 active:scale-95 transition" aria-label="Yahoo!ニュース（新しいタブ）">
                                <i class="fa-brands fa-yahoo text-[11px]"></i>
                            </a>
                            <a href="https://news.google.com/home?hl=ja&gl=JP&ceid=JP%3Aja" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-blue-600 active:scale-95 transition" aria-label="Google ニュース（新しいタブ）">
                                <i class="fa-brands fa-google text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="text-[11px] font-medium text-slate-500 shrink-0" for="trainingTimerDuration">時間</label>
                        <select id="trainingTimerDuration" class="text-sm px-2 py-1.5 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent">
                            <option value="180">3分</option>
                            <option value="300" selected>5分</option>
                            <option value="480">8分</option>
                            <option value="600">10分</option>
                            <option value="900">15分</option>
                            <option value="1200">20分</option>
                        </select>
                        <button type="button" id="trainingTimerStart" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 active:scale-[0.98] transition">
                            <i class="fa-solid fa-play text-[10px]"></i>
                            開始
                        </button>
                        <button type="button" id="trainingTimerReset" disabled class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:pointer-events-none active:scale-[0.98] transition">
                            <i class="fa-solid fa-rotate-left text-[10px]"></i>
                            リセット
                        </button>
                        <span id="trainingTimerDisplay" class="font-mono text-2xl font-black text-slate-800 tabular-nums tracking-tight w-full text-center sm:w-auto sm:ml-auto sm:text-right mt-1 sm:mt-0">5:00</span>
                    </div>
                </section>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <p class="text-[11px] font-bold tracking-wider text-emerald-600 mb-1"><i class="fa-solid fa-thumbs-up mr-1"></i>ほめたいところ</p>
                    <p class="text-xs text-slate-500 mb-3">「ここが面白い」「ここが新しい」「この切り口が好き」など、素直にほめポイントを3つ書き出してみてください。</p>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php $key = 'praise_' . $i; $value = $existing[$key] ?? ''; ?>
                        <div class="mb-3">
                            <label class="text-[11px] font-medium text-slate-500 mb-1 block">ほめポイント <?= $i ?></label>
                            <textarea
                                id="praise_<?= $i ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none min-h-[56px]"
                                maxlength="500"
                            ><?= htmlspecialchars($value) ?></textarea>
                        </div>
                    <?php endfor; ?>
                </section>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <p class="text-[11px] font-bold tracking-wider text-sky-600 mb-1"><i class="fa-solid fa-comment-dots mr-1"></i>ツッコミたいところ</p>
                    <p class="text-xs text-slate-500 mb-3">「ここは本当？」「別の見方もありそう」「ここをもう少し詳しく知りたい」など、ツッコミポイントを3つ書き出してみてください。</p>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php $key = 'tsukkomi_' . $i; $value = $existing[$key] ?? ''; ?>
                        <div class="mb-3">
                            <label class="text-[11px] font-medium text-slate-500 mb-1 block">ツッコミポイント <?= $i ?></label>
                            <textarea
                                id="tsukkomi_<?= $i ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent resize-none min-h-[56px]"
                                maxlength="500"
                            ><?= htmlspecialchars($value) ?></textarea>
                        </div>
                    <?php endfor; ?>
                </section>

                <section class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                        <a href="/dashboard/article_training_history.php" class="inline-flex items-center text-slate-500 hover:text-slate-700">
                            <i class="fa-solid fa-clock-rotate-left mr-1"></i>履歴
                        </a>
                        <a href="/" class="inline-flex items-center text-slate-500 hover:text-slate-700">
                            <i class="fa-solid fa-chevron-left mr-1"></i>ダッシュボードに戻る
                        </a>
                    </div>
                    <button
                        type="button"
                        id="saveBtn"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-sm hover:shadow-md active:scale-[0.98] transition"
                        onclick="ArticleTraining.save()"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        保存する
                    </button>
                </section>

                <?php endif; ?>

            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php if ($hasArticle): ?>
    <script>
        const ArticleTraining = {
            async save() {
                const payload = {
                    article_url: <?= json_encode($articleUrl, JSON_UNESCAPED_UNICODE) ?>,
                    article_title: <?= json_encode($articleTitle, JSON_UNESCAPED_UNICODE) ?>,
                    praise_1: document.getElementById('praise_1').value.trim(),
                    praise_2: document.getElementById('praise_2').value.trim(),
                    praise_3: document.getElementById('praise_3').value.trim(),
                    tsukkomi_1: document.getElementById('tsukkomi_1').value.trim(),
                    tsukkomi_2: document.getElementById('tsukkomi_2').value.trim(),
                    tsukkomi_3: document.getElementById('tsukkomi_3').value.trim(),
                };

                const nonEmptyCount =
                    (payload.praise_1 ? 1 : 0) +
                    (payload.praise_2 ? 1 : 0) +
                    (payload.praise_3 ? 1 : 0) +
                    (payload.tsukkomi_1 ? 1 : 0) +
                    (payload.tsukkomi_2 ? 1 : 0) +
                    (payload.tsukkomi_3 ? 1 : 0);

                if (nonEmptyCount === 0) {
                    App.toast('少なくとも1つはコメントを書いてみましょう');
                    return;
                }

                try {
                    const res = await App.post('/dashboard/api/save_article_training.php', payload);
                    if (res && res.status === 'success') {
                        App.toast('トレーニング内容を保存しました');
                    } else {
                        App.toast(res && res.message ? res.message : '保存に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('保存中にエラーが発生しました');
                }
            }
        };

        const ArticleTrainingTimer = (function () {
            const LS_KEY = 'dashboard_article_training_timer_duration_sec';
            let intervalId = null;
            let running = false;
            let remaining = 0;

            function el(id) {
                return document.getElementById(id);
            }

            function selectedSeconds() {
                const sel = el('trainingTimerDuration');
                if (!sel) return 300;
                const v = parseInt(sel.value, 10);
                return Number.isFinite(v) && v > 0 ? v : 300;
            }

            function format(sec) {
                const s = Math.max(0, sec);
                const m = Math.floor(s / 60);
                const r = s % 60;
                return m + ':' + (r < 10 ? '0' : '') + r;
            }

            function updateDisplay() {
                const d = el('trainingTimerDisplay');
                if (d) d.textContent = format(remaining);
            }

            function restoreFromSelect() {
                remaining = selectedSeconds();
                updateDisplay();
            }

            function applyIdleUi() {
                const sel = el('trainingTimerDuration');
                const resetBtn = el('trainingTimerReset');
                if (sel) sel.disabled = false;
                if (resetBtn) resetBtn.disabled = true;
            }

            function finish(isReset) {
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
                running = false;
                applyIdleUi();
                restoreFromSelect();
                if (!isReset && typeof App !== 'undefined' && App.toast) {
                    App.toast('タイムアップしました');
                }
            }

            return {
                init() {
                    const sel = el('trainingTimerDuration');
                    const startBtn = el('trainingTimerStart');
                    const resetBtn = el('trainingTimerReset');
                    if (!sel || !startBtn || !resetBtn) return;

                    try {
                        const saved = localStorage.getItem(LS_KEY);
                        if (saved && [...sel.options].some(function (o) { return o.value === saved; })) {
                            sel.value = saved;
                        }
                    } catch (e) { /* ignore */ }

                    sel.addEventListener('change', function () {
                        if (running) return;
                        try {
                            localStorage.setItem(LS_KEY, sel.value);
                        } catch (e) { /* ignore */ }
                        restoreFromSelect();
                    });

                    startBtn.addEventListener('click', function () {
                        if (running) return;
                        running = true;
                        remaining = selectedSeconds();
                        sel.disabled = true;
                        resetBtn.disabled = false;
                        updateDisplay();
                        intervalId = setInterval(function () {
                            remaining -= 1;
                            updateDisplay();
                            if (remaining <= 0) {
                                finish(false);
                            }
                        }, 1000);
                    });

                    resetBtn.addEventListener('click', function () {
                        if (!running && intervalId === null) return;
                        finish(true);
                    });

                    applyIdleUi();
                    restoreFromSelect();
                }
            };
        })();

        ArticleTrainingTimer.init();
    </script>
    <?php endif; ?>
</body>
</html>
