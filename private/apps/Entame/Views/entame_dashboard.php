<?php
$appKey = 'entame';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$totalHours = floor(($totalRuntime ?? 0) / 60);
$totalMins = ($totalRuntime ?? 0) % 60;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エンタメダッシュボード - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --ent-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .ent-theme-text { color: var(--ent-theme); }
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .user-info { display: none; }
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
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-masks-theater text-sm"></i>
            </div>
            <h1 class="font-black text-slate-700 text-xl tracking-tighter">エンタメ</h1>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="/movie/index.php" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                <i class="fa-solid fa-film mr-1"></i><span class="hidden sm:inline">映画</span>
            </a>
            <a href="/drama/index.php" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                <i class="fa-solid fa-clapperboard mr-1"></i><span class="hidden sm:inline">ドラマ</span>
            </a>
            <a href="/anime/index.php" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                <i class="fa-solid fa-tv mr-1"></i><span class="hidden sm:inline">アニメ</span>
            </a>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-10">
        <div class="max-w-6xl mx-auto">

            <!-- トータルサマリ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6 md:mb-8">
                <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                    <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-500">
                            <i class="fa-solid fa-eye text-sm md:text-lg"></i>
                        </div>
                        <span class="text-[11px] md:text-xs font-bold text-slate-400">見た（合計）</span>
                    </div>
                    <div class="flex items-end gap-1">
                        <span class="text-2xl md:text-3xl font-black text-slate-800"><?= (int)($totalWatchedCount ?? 0) ?></span>
                        <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                    <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-amber-50 text-amber-500">
                            <i class="fa-solid fa-bookmark text-sm md:text-lg"></i>
                        </div>
                        <span class="text-[11px] md:text-xs font-bold text-slate-400">見たい（合計）</span>
                    </div>
                    <div class="flex items-end gap-1">
                        <span class="text-2xl md:text-3xl font-black text-slate-800"><?= (int)($totalWatchlistCount ?? 0) ?></span>
                        <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                    <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-sky-50 text-sky-500">
                            <i class="fa-solid fa-play text-sm md:text-lg"></i>
                        </div>
                        <span class="text-[11px] md:text-xs font-bold text-slate-400">見てる（ドラマ＋アニメ）</span>
                    </div>
                    <div class="flex items-end gap-1">
                        <span class="text-2xl md:text-3xl font-black text-slate-800">
                            <?= (int)(($entameStats['drama']['watching'] ?? 0) + ($entameStats['anime']['watching'] ?? 0)) ?>
                        </span>
                        <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5">
                    <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-purple-50 text-purple-500">
                            <i class="fa-solid fa-clock text-sm md:text-lg"></i>
                        </div>
                        <span class="text-[11px] md:text-xs font-bold text-slate-400">総視聴時間（映画＋ドラマ）</span>
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

            <!-- メディア別カード -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
                <!-- 映画 -->
                <a href="/movie/index.php" class="block bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-indigo-200 transition-all group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-indigo-50 text-indigo-500">
                            <i class="fa-solid fa-film"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400">映画</p>
                            <h2 class="text-sm font-black text-slate-800">Movie</h2>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <div class="flex items-end gap-3">
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見た</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['movie']['watched'] ?? 0) ?>本</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見たい</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['movie']['watchlist'] ?? 0) ?>本</p>
                        </div>
                    </div>
                </a>

                <!-- ドラマ -->
                <a href="/drama/index.php" class="block bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-violet-200 transition-all group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-violet-50 text-violet-500">
                            <i class="fa-solid fa-clapperboard"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400">ドラマ</p>
                            <h2 class="text-sm font-black text-slate-800">Drama</h2>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <div class="flex items-end gap-3 flex-wrap">
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見た</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['drama']['watched'] ?? 0) ?>本</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見てる</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['drama']['watching'] ?? 0) ?>本</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見たい</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['drama']['wanna_watch'] ?? 0) ?>本</p>
                        </div>
                    </div>
                </a>

                <!-- アニメ -->
                <a href="/anime/index.php" class="block bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-sky-200 transition-all group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-sky-50 text-sky-500">
                            <i class="fa-solid fa-tv"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400">アニメ</p>
                            <h2 class="text-sm font-black text-slate-800">Anime</h2>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <div class="flex items-end gap-3 flex-wrap">
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見た</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['anime']['watched'] ?? 0) ?>本</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見てる</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['anime']['watching'] ?? 0) ?>本</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 mb-0.5">見たい</p>
                            <p class="text-xl font-black text-slate-800"><?= (int)($entameStats['anime']['wanna_watch'] ?? 0) ?>本</p>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</main>

<script src="/assets/js/core.js?v=2"></script>
</body>
</html>

