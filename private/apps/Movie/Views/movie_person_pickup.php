<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$roleKind = $roleKind ?? 'cast';
$personId = (int)($personId ?? 0);
$personIds = $personIds ?? [];
$personName = (string)($personName ?? '');
$personNames = (string)($personNames ?? '');
$personNamesCsv = (string)($personNamesCsv ?? '');
$roleLabel = $roleKind === 'cast' ? '俳優' : ($roleKind === 'director' ? '監督' : '脚本');

$idsForRequest = !empty($personIds) ? array_values(array_unique(array_map('intval', $personIds))) : ($personId > 0 ? [$personId] : []);
$namesLabel = '';
if ($personNames !== '') {
    $namesLabel = $personNames;
} elseif ($personName !== '') {
    $namesLabel = $personName;
} elseif (!empty($idsForRequest)) {
    $namesLabel = 'ID=' . implode(',', $idsForRequest);
}

// APIへ「person_idsの順序に対応した名前CSV」を渡す（作品ごとの該当者名表示に使う）
$namesCsvForRequest = $personNamesCsv;
if ($namesCsvForRequest === '' && $namesLabel !== '') {
    // person_names は「 / 」区切りで来る想定
    $namesCsvForRequest = implode(',', array_values(array_filter(array_map('trim', explode(' / ', $namesLabel)), fn($s) => $s !== '')));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($namesLabel !== '' ? $namesLabel : '人物') ?>の未登録映画 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --mv-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .mv-theme-btn { background-color: var(--mv-theme); }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        .mv-theme-text { color: var(--mv-theme); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/movie/" class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?> hover:brightness-110 transition"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?> title="映画ダッシュボード">
                    <i class="fa-solid fa-film text-sm"></i>
                </a>
                <div class="min-w-0">
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">見てない映画を探す</h1>
                    <p class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($roleLabel) ?>: <?= htmlspecialchars($namesLabel) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="/movie/list.php?tab=watched&credit_role=<?= htmlspecialchars($roleKind) ?>&person_ids=<?= urlencode(implode(',', $idsForRequest)) ?>&person_names=<?= urlencode($namesLabel) ?>"
                   class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-list"></i>
                    <span class="hidden sm:inline">見たリスト</span>
                </a>
                <a href="/movie/" class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-gauge"></i>
                    <span class="hidden sm:inline">ダッシュボード</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto" id="mainScroll">
            <div class="max-w-6xl mx-auto px-6 md:px-12 py-6">
                <?php if (!$tmdbConfigured): ?>
                    <div class="text-center py-12 text-amber-600 bg-amber-50 rounded-xl mb-6">
                        <i class="fa-solid fa-triangle-exclamation text-2xl mb-2"></i>
                        <p class="text-sm">TMDB APIキーが設定されていません</p>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-4">
                    <p class="text-xs text-slate-500">
                        <span class="font-bold">基準</span>:
                        TMDBの人物クレジットから取得し、あなたの「見た/見たい」登録済みは除外、<span class="font-bold">人気順（popularity）</span>で上位を表示します。
                    </p>
                </div>

                <div id="pickupStatus" class="mb-4 text-sm text-slate-500"></div>
                <div id="pickupResults" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/_movie_search_shared.php'; ?>
    <script src="/assets/js/core.js"></script>
    <script>
        const roleKind = <?= json_encode($roleKind, JSON_UNESCAPED_UNICODE) ?>;
        const personIds = <?= json_encode($idsForRequest, JSON_UNESCAPED_UNICODE) ?>;
        const personNamesCsv = <?= json_encode($namesCsvForRequest, JSON_UNESCAPED_UNICODE) ?>;

        function _esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        function renderCard(m) {
            const poster = m.poster_path
                ? `<img src="https://image.tmdb.org/t/p/w342${m.poster_path}" alt="${_esc(m.title)}" class="w-full aspect-[2/3] object-cover rounded-xl" loading="lazy">`
                : `<div class="w-full aspect-[2/3] bg-slate-100 rounded-xl flex items-center justify-center"><i class="fa-solid fa-film text-slate-300 text-2xl"></i></div>`;
            const year = m.release_date ? String(m.release_date).substring(0,4) : '';
            const rating = m.vote_average ? Number(m.vote_average).toFixed(1) : '';
            const namesText = m.matched_person_names || '';

            return `
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="relative">
                        ${poster}
                        ${rating ? `<div class="absolute bottom-2 right-2 text-[11px] font-bold text-white bg-black/60 backdrop-blur-sm px-2 py-0.5 rounded-lg"><i class="fa-solid fa-star text-amber-400 text-[9px] mr-0.5"></i>${rating}</div>` : ''}
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-bold text-slate-800 line-clamp-2 leading-tight">${_esc(m.title)}</h3>
                        <p class="text-[11px] text-slate-400 mt-1">${year ? year + '年' : ''}</p>
                        ${namesText ? `<div class="mt-1"><span class="inline-flex items-center text-[10px] font-black bg-slate-50 text-slate-600 border border-slate-200 px-2 py-0.5 rounded-full max-w-full truncate">${_esc(namesText)}</span></div>` : ''}
                        <div class="flex gap-1.5 mt-2">
                            <button onclick="addMovie(${m.id}, 'watchlist', this)" class="flex-1 text-[11px] font-bold text-white py-1.5 rounded-lg mv-theme-btn transition"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい</button>
                            <button onclick="addMovie(${m.id}, 'watched', this)" class="flex-1 text-[11px] font-bold text-slate-500 border border-slate-200 py-1.5 rounded-lg hover:bg-slate-50 transition"><i class="fa-solid fa-check mr-0.5"></i>見た</button>
                        </div>
                    </div>
                </div>`;
        }

        async function addMovie(tmdbId, status, btn) {
            btn.disabled = true;
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            try {
                const result = await App.post('/movie/api/add.php', { tmdb_id: tmdbId, status: status });
                if (result.status === 'success') {
                    App.toast(result.message);
                    btn.closest('.bg-white')?.remove();
                } else {
                    App.toast(result.message || '追加に失敗しました');
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
            } catch (e) {
                console.error(e);
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        async function loadPickup() {
            const statusEl = document.getElementById('pickupStatus');
            const grid = document.getElementById('pickupResults');
            statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2 text-slate-300"></i>取得中...';
            grid.innerHTML = '';
            try {
                const res = await fetch(`/movie/api/person_pickup.php?role_kind=${encodeURIComponent(roleKind)}&person_ids=${encodeURIComponent(personIds.join(','))}&person_names_csv=${encodeURIComponent(personNamesCsv)}&limit=20`);
                const json = await res.json();
                if (json.status !== 'success') {
                    statusEl.textContent = json.message || '取得に失敗しました';
                    return;
                }
                const movies = json.data?.results || [];
                statusEl.innerHTML = `<span class="font-bold mv-theme-text">${movies.length}</span> 本（未登録のみ）`;
                grid.innerHTML = movies.map(renderCard).join('');
                if (movies.length === 0) {
                    grid.innerHTML = `<div class="col-span-full text-center py-16 text-slate-400">
                        <i class="fa-solid fa-face-smile-beam text-4xl mb-4"></i>
                        <p class="text-sm">未登録の候補が見つかりませんでした</p>
                    </div>`;
                }
            } catch (e) {
                console.error(e);
                statusEl.textContent = 'エラーが発生しました';
            }
        }
        loadPickup();
    </script>
</body>
</html>

