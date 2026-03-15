<?php
$appKey = 'drama';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$totalHours = floor(($totalRuntime ?? 0) / 60);
$totalMins = ($totalRuntime ?? 0) % 60;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ドラマダッシュボード - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
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
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-clapperboard text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ドラマ</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/drama/import.php" class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-xs sm:text-sm font-bold rounded-lg hover:bg-slate-50 transition" title="一括登録">
                    <i class="fa-solid fa-file-import"></i>
                    <span class="hidden sm:inline">一括登録</span>
                </a>
                <a href="/drama/list.php?tab=watching" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-play mr-1"></i><span class="hidden sm:inline">見てる</span>
                </a>
                <a href="/drama/list.php?tab=wanna_watch" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-bookmark mr-1"></i><span class="hidden sm:inline">見たい</span>
                </a>
                <a href="/drama/list.php?tab=watched" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-check-circle mr-1"></i><span class="hidden sm:inline">見た</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-10">
            <div class="max-w-6xl mx-auto">

                <!-- スタッツカード -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 mb-6 md:mb-8">
                    <a href="/drama/list.php?tab=watched" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 hover:shadow-md hover:border-emerald-200 transition-all group block">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-500">
                                <i class="fa-solid fa-eye text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">見た</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= (int)($watchedCount ?? 0) ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                        </div>
                    </a>

                    <a href="/drama/list.php?tab=watching" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 hover:shadow-md hover:border-sky-200 transition-all group block">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-sky-50 text-sky-500">
                                <i class="fa-solid fa-play text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">見てる</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= (int)($watchingCount ?? 0) ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                        </div>
                    </a>

                    <a href="/drama/list.php?tab=wanna_watch" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 hover:shadow-md hover:border-amber-200 transition-all group block">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-amber-50 text-amber-500">
                                <i class="fa-solid fa-bookmark text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">見たい</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= (int)($wannaWatchCount ?? 0) ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                        </div>
                    </a>

                    <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-purple-50 text-purple-500">
                                <i class="fa-solid fa-clock text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">総視聴時間</span>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= $totalHours ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">時間</span>
                            <?php if ($totalMins > 0): ?>
                            <span class="text-base md:text-lg font-black text-slate-500"><?= $totalMins ?></span>
                            <span class="text-[10px] md:text-xs font-bold text-slate-400 mb-0.5">分</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TMDB 検索ボックス -->
                <?php if ($tmdbConfigured): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 md:p-5 mb-6 md:mb-8">
                    <div class="flex items-center gap-2 mb-2 md:mb-3">
                        <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg flex items-center justify-center bg-violet-50 text-violet-500">
                            <i class="fa-solid fa-magnifying-glass text-xs md:text-sm"></i>
                        </div>
                        <h2 class="text-sm font-bold text-slate-500">ドラマを探す</h2>
                    </div>
                    <div class="relative" id="drDashSearchWrapper">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" id="drDashSearchInput"
                                       placeholder="タイトルで検索..."
                                       class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)] focus:border-transparent"
                                       onkeydown="if(event.key==='Enter') DramaSearch.search()"
                                       autocomplete="off">
                            </div>
                            <button onclick="DramaSearch.search()" class="px-4 py-2.5 dr-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0">
                                検索
                            </button>
                        </div>
                        <div id="drDashSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-slate-100 max-h-[60vh] overflow-y-auto z-50"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- グラフエリア -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-chart-bar dr-theme-text"></i>
                            <h2 class="text-sm font-bold text-slate-700">月別視聴完了数</h2>
                            <span class="text-[10px] text-slate-400 ml-auto">過去12ヶ月</span>
                        </div>
                        <div class="relative" style="height: 220px;">
                            <canvas id="drMonthlyChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-star-half-stroke dr-theme-text"></i>
                            <h2 class="text-sm font-bold text-slate-700">評価スコア分布</h2>
                            <?php if (!empty($ratedCount)): ?>
                            <span class="text-[10px] text-slate-400 ml-auto"><?= (int)$ratedCount ?>本評価済み / 平均 <?= htmlspecialchars($avgRating) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($ratedCount)): ?>
                        <div class="relative" style="height: 200px;">
                            <canvas id="drRatingChart"></canvas>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-10 text-slate-400">
                            <i class="fa-solid fa-star text-3xl mb-2"></i>
                            <p class="text-sm">ドラマに評価をつけるとスコア分布が表示されます</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ジャンル分布 -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-chart-pie dr-theme-text"></i>
                        <h2 class="text-sm font-bold text-slate-700">ジャンル分布</h2>
                    </div>
                    <?php if (!empty($genreDistribution)): ?>
                    <div class="flex items-center gap-4">
                        <div class="relative shrink-0" style="width: 180px; height: 180px;">
                            <canvas id="drGenreChart"></canvas>
                        </div>
                        <div class="flex-1 min-w-0 space-y-1.5 max-h-[180px] overflow-y-auto" id="drGenreLegend"></div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-chart-pie text-3xl mb-2"></i>
                        <p class="text-sm">視聴データがたまるとジャンル分布が表示されます</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php require_once __DIR__ . '/_drama_search_shared.php'; ?>
    <script>
        const drThemeColor = '<?= htmlspecialchars($themePrimaryHex) ?>';

        // Chart.js 共通設定
        Chart.defaults.font.family = "'Inter', 'Noto Sans JP', sans-serif";
        Chart.defaults.font.size = 11;

        // 月別視聴完了数
        (() => {
            const data = <?= json_encode(array_values($monthlyWatchCounts ?? [])) ?>;
            const labels = <?= json_encode(array_map(fn($ym) => date('n月', strtotime($ym . '-01')), array_keys($monthlyWatchCounts ?? []))) ?>;
            const ctx = document.getElementById('drMonthlyChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: drThemeColor + '40',
                        borderColor: drThemeColor,
                        borderWidth: 1.5,
                        borderRadius: 6,
                        maxBarThickness: 36,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: '#94a3b8' },
                            grid: { color: '#f1f5f9' },
                        },
                        x: {
                            ticks: { color: '#94a3b8' },
                            grid: { display: false },
                        }
                    }
                }
            });
        })();

        // 評価スコア分布
        (() => {
            const dist = <?= json_encode(array_values($ratingDistribution ?? [])) ?>;
            if (!dist || dist.every(v => v === 0)) return;

            const ctx = document.getElementById('drRatingChart');
            if (!ctx) return;

            const labels = Array.from({length: 10}, (_, i) => (i + 1).toString());
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dist,
                        backgroundColor: labels.map((_, i) => {
                            const ratio = (i + 1) / 10;
                            const r = Math.round(236 * (1 - ratio) + 109 * ratio);
                            const g = Math.round(181 * (1 - ratio) + 114 * ratio);
                            const b = Math.round(252 * (1 - ratio) + 219 * ratio);
                            return `rgb(${r},${g},${b})`;
                        }),
                        borderRadius: 6,
                        maxBarThickness: 48,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: '#94a3b8' },
                            grid: { color: '#f1f5f9' },
                        },
                        x: {
                            ticks: { color: '#94a3b8', callback: (v, i) => (i+1) + '点' },
                            grid: { display: false },
                        }
                    }
                }
            });
        })();

        // ジャンル分布
        (() => {
            const raw = <?= json_encode($genreDistribution ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const entries = Object.entries(raw);
            if (entries.length === 0) return;

            const top = entries.slice(0, 8);
            const otherCount = entries.slice(8).reduce((s, [, v]) => s + v, 0);
            const labels = top.map(([k]) => k);
            const data = top.map(([, v]) => v);
            if (otherCount > 0) {
                labels.push('その他');
                data.push(otherCount);
            }

            const palette = [
                '#8b5cf6', '#0ea5e9', '#10b981', '#f97316',
                '#ec4899', '#6366f1', '#f59e0b', '#22c55e', '#94a3b8'
            ];

            const ctx = document.getElementById('drGenreChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: palette.slice(0, data.length),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '55%',
                    plugins: {
                        legend: { display: false },
                    },
                }
            });

            const total = data.reduce((a, b) => a + b, 0);
            const legend = document.getElementById('drGenreLegend');
            if (legend) {
                legend.innerHTML = labels.map((l, i) => {
                    const pct = total > 0 ? ((data[i] / total) * 100).toFixed(0) : 0;
                    return `<div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${palette[i]}"></span>
                        <span class="text-xs text-slate-600 truncate flex-1">${l}</span>
                        <span class="text-[11px] font-bold text-slate-500">${pct}%</span>
                    </div>`;
                }).join('');
            }
        })();

        DramaSearch.init({
            inputId: 'drDashSearchInput',
            resultsId: 'drDashSearchResults',
            wrapperId: 'drDashSearchWrapper'
        });
    </script>
</body>
</html>

