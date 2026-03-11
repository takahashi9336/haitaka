<?php
$appKey = 'dashboard';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$animeTheme = getThemeVarsForApp('anime');
if (empty($animeTheme['themePrimaryHex'])) {
    $animeTheme['themePrimaryHex'] = '#0ea5e9';
    $animeTheme['cardIconBg'] = 'bg-sky-50';
    $animeTheme['cardIconText'] = 'text-sky-500';
    $animeTheme['cardBorder'] = 'border-slate-100';
}
$themeHex = $animeTheme['themePrimaryHex'] ?? '#0ea5e9';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アニメ - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <style>
        :root { --anime-theme: <?= htmlspecialchars($themeHex) ?>; }
        .anime-theme-btn { background-color: var(--anime-theme); }
        .anime-theme-btn:hover { filter: brightness(1.08); }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-sky-500 text-white shadow-lg">
                    <i class="fa-solid fa-tv text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">アニメ</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/anime/list.php?tab=watching" class="text-xs font-bold text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-play mr-1"></i><span class="hidden sm:inline">見てる</span>
                </a>
                <a href="/anime/list.php?tab=wanna_watch" class="text-xs font-bold text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-bookmark mr-1"></i><span class="hidden sm:inline">見たい</span>
                </a>
                <a href="/anime/list.php?tab=watched" class="text-xs font-bold text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-check-circle mr-1"></i><span class="hidden sm:inline">見た</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-3 py-4 sm:px-4 md:p-6 lg:p-10">
            <div class="max-w-6xl mx-auto">

                <?php if ($oauthError): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                    <?= htmlspecialchars($oauthError) ?>
                </div>
                <?php endif; ?>

                <?php if (!$oauthConfigured): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-800">
                    <p class="font-bold"><i class="fa-solid fa-exclamation-triangle mr-2"></i>Annict OAuth が未設定です</p>
                    <p class="text-sm mt-2">.env に ANNICT_CLIENT_ID, ANNICT_CLIENT_SECRET, ANNICT_REDIRECT_URI を設定してください。</p>
                </div>
                <?php else: ?>

                <!-- スタッツカード -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4 mb-6 md:mb-8">
                    <a href="/anime/list.php?tab=watched" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 transition-all block">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-green-50 text-green-500">
                                <i class="fa-solid fa-eye text-sm"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">見た</span>
                        </div>
                        <span class="text-2xl font-black text-slate-800"><?= $stats['watched'] ?></span>
                    </a>
                    <a href="/anime/list.php?tab=watching" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 transition-all block">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-500">
                                <i class="fa-solid fa-play text-sm"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">見てる</span>
                        </div>
                        <span class="text-2xl font-black text-slate-800"><?= $stats['watching'] ?></span>
                    </a>
                    <a href="/anime/list.php?tab=wanna_watch" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 transition-all block">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-500">
                                <i class="fa-solid fa-bookmark text-sm"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">見たい</span>
                        </div>
                        <span class="text-2xl font-black text-slate-800"><?= $stats['wanna_watch'] ?></span>
                    </a>
                    <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 text-slate-500">
                                <i class="fa-solid fa-pause text-sm"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">中断</span>
                        </div>
                        <span class="text-2xl font-black text-slate-800"><?= $stats['on_hold'] ?></span>
                    </div>
                    <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 text-slate-500">
                                <i class="fa-solid fa-stop text-sm"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">中止</span>
                        </div>
                        <span class="text-2xl font-black text-slate-800"><?= $stats['stop_watching'] ?></span>
                    </div>
                </div>

                <!-- アニメを探す -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 md:p-5 mb-6 md:mb-8">
                    <div class="flex items-center gap-2 mb-2 md:mb-3">
                        <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg flex items-center justify-center bg-sky-50 text-sky-500">
                            <i class="fa-solid fa-magnifying-glass text-xs md:text-sm"></i>
                        </div>
                        <h2 class="text-sm font-bold text-slate-500">アニメを探す</h2>
                    </div>
                    <div class="relative" id="dashSearchWrapper">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <div class="flex-1 relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" id="dashSearchInput"
                                       placeholder="タイトルで検索..."
                                       class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--anime-theme)] focus:border-transparent"
                                       onkeydown="if(event.key==='Enter') AnimeSearch.search()"
                                       autocomplete="off">
                            </div>
                            <button onclick="AnimeSearch.search()" class="px-4 py-2.5 anime-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0">
                                検索
                            </button>
                        </div>
                        <div id="dashSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-slate-100 max-h-[60vh] overflow-y-auto z-50"></div>
                    </div>
                </div>

                <!-- 今期アニメ -->
                <?php if (!empty($thisSeasonWorks)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-calendar text-sky-500 mr-2"></i>今期のアニメ</h2>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach (array_slice($thisSeasonWorks, 0, 12) as $w): ?>
                        <a href="/anime/detail.php?id=<?= (int)($w['id'] ?? 0) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 transition text-sm">
                            <?php if (!empty($w['images']['recommended_url'])): ?>
                            <img src="<?= htmlspecialchars($w['images']['recommended_url']) ?>" alt="" class="w-10 h-14 object-cover rounded">
                            <?php else: ?>
                            <div class="w-10 h-14 bg-slate-200 rounded flex items-center justify-center"><i class="fa-solid fa-tv text-slate-400 text-xs"></i></div>
                            <?php endif; ?>
                            <span class="font-bold text-slate-800 truncate max-w-[140px]"><?= htmlspecialchars($w['title'] ?? '') ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 視聴中 -->
                <?php if (!empty($watchingWorks)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-play text-sky-500 mr-2"></i>視聴中</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach (array_slice($watchingWorks, 0, 12) as $w): ?>
                        <a href="/anime/detail.php?id=<?= (int)($w['id'] ?? 0) ?>" class="block group">
                            <div class="aspect-[2/3] rounded-lg overflow-hidden bg-slate-100 mb-2">
                                <?php if (!empty($w['images']['recommended_url'])): ?>
                                <img src="<?= htmlspecialchars($w['images']['recommended_url']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                                <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center"><i class="fa-solid fa-tv text-3xl text-slate-300"></i></div>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs font-bold text-slate-700 truncate"><?= htmlspecialchars($w['title'] ?? '') ?></p>
                            <?php if (isset($w['episodes_count']) && $w['episodes_count'] > 0): ?>
                            <p class="text-[10px] text-slate-400"><?= $w['episodes_count'] ?>話</p>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!--  media分布・ステータス分布 -->
                <?php if (!empty($mediaDistribution) || !empty($stats)): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <?php if (!empty($mediaDistribution)): ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-chart-pie text-sky-500 mr-2"></i>媒体分布</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($mediaDistribution as $media => $cnt): ?>
                            <span class="px-2 py-1 rounded bg-sky-50 text-sky-700 text-xs font-bold"><?= htmlspecialchars($media) ?>: <?= $cnt ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php
                    $statusLabels = ['watching' => '見てる', 'watched' => '見た', 'wanna_watch' => '見たい', 'on_hold' => '中断', 'stop_watching' => '中止'];
                    $hasAnyStatus = array_sum($stats) > 0;
                    ?>
                    <?php if ($hasAnyStatus): ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-chart-pie text-sky-500 mr-2"></i>ステータス分布</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($statusLabels as $key => $label): ?>
                            <?php if (($stats[$key] ?? 0) > 0): ?>
                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs font-bold"><?= htmlspecialchars($label) ?>: <?= $stats[$key] ?></span>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (empty($works)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center text-slate-500">
                    <i class="fa-solid fa-tv text-4xl mb-3 text-slate-300"></i>
                    <p class="font-bold">まだ作品が登録されていません</p>
                    <p class="text-sm mt-1">検索から「見たい」「見てる」「見た」を追加すると、ここに表示されます</p>
                </div>
                <?php endif; ?>

                <p class="text-[10px] text-slate-400 mt-4">アニメ作品データ提供: <a href="https://annict.com" target="_blank" rel="noopener" class="underline">Annict</a></p>

                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php require_once __DIR__ . '/_anime_search_shared.php'; ?>
    <script>
        AnimeSearch.init({
            inputId: 'dashSearchInput',
            resultsId: 'dashSearchResults',
            wrapperId: 'dashSearchWrapper'
        });
    </script>
</body>
</html>
