<?php
$appKey = 'drama';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ドラマ検索 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --dr-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .dr-theme-btn { background-color: var(--dr-theme); }
        .dr-theme-btn:hover { filter: brightness(1.08); }
        .dr-theme-text { color: var(--dr-theme); }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/drama/" class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?> hover:brightness-110 transition"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-clapperboard text-sm"></i>
                </a>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ドラマ検索</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-4 md:px-10 py-6">
            <div class="max-w-4xl mx-auto">
                <?php if (!$tmdbConfigured): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-800">
                    <p class="font-bold"><i class="fa-solid fa-exclamation-triangle mr-2"></i>TMDB APIキーが未設定です</p>
                    <p class="text-sm mt-2">.env に TMDB_API_KEY を設定してください。</p>
                </div>
                <?php else: ?>

                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 mb-4">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <div class="flex-1 relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" id="drSearchPageInput"
                                   value="<?= htmlspecialchars($query ?? '') ?>"
                                   placeholder="ドラマタイトルで検索..."
                                   class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)] focus:border-transparent"
                                   onkeydown="if(event.key==='Enter') DrSearchPage.search()">
                        </div>
                        <button onclick="DrSearchPage.search()" class="px-4 py-2.5 dr-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0">
                            検索
                        </button>
                    </div>
                </div>

                <div id="drSearchPageResults" class="bg-white rounded-xl border border-slate-100 shadow-sm">
                    <div id="drSearchPageInner" class="divide-y divide-slate-100"></div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php if ($tmdbConfigured): ?>
    <?php require_once __DIR__ . '/_drama_search_shared.php'; ?>
    <script>
        const DrSearchPage = {
            async search() {
                const q = document.getElementById('drSearchPageInput').value.trim();
                if (!q) return;
                const container = document.getElementById('drSearchPageInner');
                container.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-slate-300"></i></div>';
                history.replaceState(null, '', '?q=' + encodeURIComponent(q));
                try {
                    const res = await fetch('/drama/api/search.php?q=' + encodeURIComponent(q) + '&page=1');
                    const json = await res.json();
                    if (json.status !== 'success') {
                        container.innerHTML = `<div class="text-center py-8 text-red-500 text-sm">${(json.message || '検索に失敗しました')}</div>`;
                        return;
                    }
                    const results = json.data || {};
                    const list = results.results || [];
                    if (!list.length) {
                        container.innerHTML = '<div class="text-center py-8 text-slate-400 text-sm">TMDBで見つかりませんでした</div>';
                        return;
                    }
                    list.forEach(s => DramaPreview.store(s));
                    container.innerHTML = list.map(s => DramaSearch.renderResult(s)).join('');
                } catch (e) {
                    console.error(e);
                    container.innerHTML = '<div class="text-center py-8 text-red-500 text-sm">検索中にエラーが発生しました</div>';
                }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const initial = '<?= trim($query ?? '') ?>';
            if (initial) {
                DrSearchPage.search();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>

