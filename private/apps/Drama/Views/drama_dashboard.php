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
        .rec-scroll-wrap { position: relative; }
        .rec-scroll { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.25rem; scroll-behavior: smooth; }
        .rec-card { width: 140px; flex-shrink: 0; }
        .rec-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 9999px; background: rgba(15,23,42,0.75); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(15,23,42,0.4); z-index: 10; }
        .rec-arrow.left { left: -12px; }
        .rec-arrow.right { right: -12px; }
        .skeleton-card { animation: dr-rec-pulse 1.5s ease-in-out infinite; }
        @keyframes dr-rec-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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

                <!-- ドラマガチャ -->
                <div class="mb-6 md:mb-8">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-4 md:p-6 lg:p-8 shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-gradient-to-bl from-white/5 to-transparent rounded-bl-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-dice text-amber-400 text-lg"></i>
                                <h2 class="text-white font-black text-lg tracking-tight">次に観るドラマガチャ</h2>
                                <span id="drGachaLimit" class="ml-auto text-[10px] font-bold text-slate-500 bg-slate-700/80 px-2 py-0.5 rounded-full"></span>
                            </div>
                            <p class="text-slate-400 text-xs mb-4 md:mb-6">「見たい」ドラマからランダムに1作品をピックアップ！<span class="text-amber-400/70">（1日2回まで）</span></p>

                            <?php if ($wannaWatchCount > 0): ?>
                            <!-- 未ガチャ状態 -->
                            <div id="drGachaIdle" class="flex flex-col items-center py-4">
                                <button onclick="DrGacha.spin()" class="gacha-idle gacha-box-shine relative w-32 h-32 md:w-40 md:h-40 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex flex-col items-center justify-center shadow-2xl cursor-pointer hover:scale-105 transition-transform overflow-hidden mb-4 md:mb-5">
                                    <i class="fa-solid fa-dice text-4xl md:text-5xl text-white/90 mb-1.5 md:mb-2 drop-shadow"></i>
                                    <span class="text-white font-black text-xs md:text-sm tracking-wide drop-shadow">タップでガチャ</span>
                                </button>
                                <p class="text-slate-500 text-xs">見たいドラマ <span class="text-amber-400 font-bold"><?= (int)($wannaWatchCount ?? 0) ?></span> 本の中から運命の1本を...</p>
                            </div>

                            <!-- 結果表示 -->
                            <div id="drGachaResult" class="hidden">
                                <div id="drGachaArea" class="flex flex-col sm:flex-row items-center gap-6">
                                    <div id="drGachaCard" class="gacha-card shrink-0">
                                        <div id="drGachaPoster" class="w-36 h-[216px] bg-slate-700 rounded-xl flex items-center justify-center shadow-2xl ring-2 ring-white/20">
                                            <i class="fa-solid fa-clapperboard text-4xl text-slate-500"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 text-center sm:text-left">
                                        <h3 id="drGachaTitle" class="text-white font-black text-xl md:text-2xl leading-tight mb-2"></h3>
                                        <div id="drGachaMeta" class="flex items-center gap-3 justify-center sm:justify-start text-slate-400 text-sm mb-4"></div>
                                        <div id="drGachaActions" class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                            <a id="drGachaDetailLink" href="#" onclick="sessionStorage.setItem('dr_back_to','dashboard')" class="px-4 py-2 bg-white/10 hover:bg白/20 text-white text-sm font-bold rounded-lg transition backdrop-blur-sm">
                                                <i class="fa-solid fa-info-circle mr-1.5"></i>詳細を見る
                                            </a>
                                            <button onclick="DrGacha.markWatched()" class="px-4 py-2 bg-green-500/80 hover:bg-green-500 text-white text-sm font-bold rounded-lg transition">
                                                <i class="fa-solid fa-check mr-1.5"></i>見た！
                                            </button>
                                            <a id="drGachaGoogleLink" href="#" target="_blank" rel="noopener" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition backdrop-blur-sm inline-flex items-center gap-1.5" title="Googleで検索">
                                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>検索
                                            </a>
                                        </div>
                                        <div id="drGachaRetry" class="hidden mt-4 pt-3 border-t border-slate-700">
                                            <button onclick="DrGacha.spin()" class="text-xs text-amber-400/80 hover:text-amber-400 transition flex items-center gap-1.5">
                                                <i class="fa-solid fa-rotate"></i>
                                                <span>どうしてももう1回だけ引く...</span>
                                            </button>
                                            <p class="text-[10px] text-slate-600 mt-1">※ 本日最後の1回です</p>
                                        </div>
                                        <div id="drGachaExhausted" class="hidden mt-4 pt-3 border-t border-slate-700">
                                            <p class="text-[10px] text-slate-600"><i class="fa-solid fa-moon mr-1"></i>本日のガチャは終了しました。明日またお楽しみに！</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="drGachaDone" class="hidden text-center py-6">
                                <div class="w-20 h-20 bg-slate-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-moon text-3xl text-slate-500"></i>
                                </div>
                                <p class="text-slate-400 text-sm font-bold mb-1">本日のガチャは終了</p>
                                <p class="text-slate-600 text-xs">明日また運命の1本を引きましょう</p>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fa-solid fa-box-open text-4xl text-slate-600 mb-3"></i>
                                <p class="text-slate-400 text-sm">「見たい」にドラマを追加するとガチャが回せます</p>
                                <a href="/drama/list.php?tab=wanna_watch" class="inline-block mt-4 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition">
                                    <i class="fa-solid fa-plus mr-1.5"></i>ドラマを追加する
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- おすすめドラマ（TMDB） -->
                <?php if ($tmdbConfigured): ?>
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <i class="fa-solid fa-wand-magic-sparkles dr-theme-text"></i>
                        <h2 class="text-sm font-bold text-slate-700">おすすめドラマ</h2>
                        <span class="text-[10px] text-slate-400 ml-auto">視聴履歴とTMDBトレンドに基づく提案</span>
                    </div>

                    <div class="flex items-center gap-2 mb-4 text-[11px]">
                        <button type="button" data-dr-rec-tab="personal" class="dr-rec-tab px-3 py-1.5 rounded-full bg-slate-800 text-white font-bold shadow-sm">パーソナル</button>
                        <button type="button" data-dr-rec-tab="genre" class="dr-rec-tab px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 font-bold">ジャンル</button>
                        <button type="button" data-dr-rec-tab="trending" class="dr-rec-tab px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 font-bold">トレンド</button>
                        <span class="text-[10px] text-slate-400 ml-auto">※ 映画と同様に TMDB のデータを利用しています</span>
                    </div>

                    <!-- パーソナル -->
                    <div id="drRecPersonalSection" class="mb-6 dr-rec-section">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-heart dr-theme-text"></i>
                            <h3 class="text-xs font-bold text-slate-700">あなたへのおすすめ</h3>
                            <span class="text-[10px] text-slate-400 ml-auto">高評価ドラマに基づくレコメンド</span>
                        </div>
                        <div class="rec-scroll-wrap">
                            <button class="rec-arrow left hidden" onclick="DrRec.scroll('drRecPersonalCards', -1)"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                            <div id="drRecPersonalLoading" class="rec-scroll">
                                <?php for ($i = 0; $i < 6; $i++): ?>
                                <div class="rec-card skeleton-card">
                                    <div class="aspect-[2/3] bg-slate-200 rounded-xl mb-2"></div>
                                    <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                    <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <div id="drRecPersonalCards" class="rec-scroll hidden"></div>
                            <button class="rec-arrow right" onclick="DrRec.scroll('drRecPersonalCards', 1)"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                        </div>
                    </div>

                    <!-- ジャンル -->
                    <div id="drRecGenreSection" class="mb-6 dr-rec-section hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-masks-theater dr-theme-text"></i>
                            <h3 class="text-xs font-bold text-slate-700">好みのジャンルから</h3>
                            <div id="drRecGenreTags" class="flex gap-1 ml-1"></div>
                        </div>
                        <div class="rec-scroll-wrap">
                            <button class="rec-arrow left hidden" onclick="DrRec.scroll('drRecGenreCards', -1)"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                            <div id="drRecGenreLoading" class="rec-scroll">
                                <?php for ($i = 0; $i < 6; $i++): ?>
                                <div class="rec-card skeleton-card">
                                    <div class="aspect-[2/3] bg-slate-200 rounded-xl mb-2"></div>
                                    <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                    <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <div id="drRecGenreCards" class="rec-scroll hidden"></div>
                            <button class="rec-arrow right" onclick="DrRec.scroll('drRecGenreCards', 1)"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                        </div>
                    </div>

                    <!-- トレンド -->
                    <div id="drRecTrendingSection" class="mb-2 dr-rec-section hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-fire dr-theme-text"></i>
                            <h3 class="text-xs font-bold text-slate-700">今週のトレンド</h3>
                            <span class="text-[10px] text-slate-400 ml-auto">世界で話題のドラマ</span>
                        </div>
                        <div class="rec-scroll-wrap">
                            <button class="rec-arrow left hidden" onclick="DrRec.scroll('drRecTrendingCards', -1)"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                            <div id="drRecTrendingLoading" class="rec-scroll">
                                <?php for ($i = 0; $i < 6; $i++): ?>
                                <div class="rec-card skeleton-card">
                                    <div class="aspect-[2/3] bg-slate-200 rounded-xl mb-2"></div>
                                    <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                    <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <div id="drRecTrendingCards" class="rec-scroll hidden"></div>
                            <button class="rec-arrow right" onclick="DrRec.scroll('drRecTrendingCards', 1)"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                        </div>
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
                <p class="text-[10px] text-slate-400 mt-2">本ドラマ機能では、映画と同様に外部サービス TMDB の作品データを利用しています。</p>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../Movie/Views/_tmdb_attribution.php'; ?>
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

        const DrGacha = {
            currentId: null,
            storageKey: 'dr_gacha',
            getState() {
                try {
                    const raw = localStorage.getItem(this.storageKey);
                    if (!raw) return null;
                    const s = JSON.parse(raw);
                    if (s.date !== new Date().toISOString().slice(0, 10)) return null;
                    return s;
                } catch { return null; }
            },
            saveState(spins, item) {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    date: new Date().toISOString().slice(0, 10),
                    spins: spins,
                    item: item || null,
                }));
            },
            init() {
                const MAX_SPINS = 2;
                const state = this.getState();
                const limitEl = document.getElementById('drGachaLimit');
                const spins = state ? state.spins : 0;
                if (!state || spins === 0) {
                    this.showIdle();
                    if (limitEl) limitEl.textContent = '残り 2/2 回';
                } else if (spins < MAX_SPINS) {
                    if (state.item) {
                        this.showResult(state.item, spins);
                    } else {
                        this.showIdle();
                    }
                    if (limitEl) limitEl.textContent = `残り ${MAX_SPINS - spins}/${MAX_SPINS} 回`;
                } else {
                    if (state.item) {
                        this.showResult(state.item, spins);
                    } else {
                        this.showDone();
                    }
                    if (limitEl) limitEl.textContent = '残り 0/2 回';
                }
            },
            showIdle() {
                const idle = document.getElementById('drGachaIdle');
                const result = document.getElementById('drGachaResult');
                const done = document.getElementById('drGachaDone');
                if (idle) idle.classList.remove('hidden');
                if (result) result.classList.add('hidden');
                if (done) done.classList.add('hidden');
            },
            showResult(item, spins) {
                const idle = document.getElementById('drGachaIdle');
                const result = document.getElementById('drGachaResult');
                const done = document.getElementById('drGachaDone');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.remove('hidden');
                if (done) done.classList.add('hidden');
                if (item) this.updateUI(item);
                const retry = document.getElementById('drGachaRetry');
                const exhausted = document.getElementById('drGachaExhausted');
                const MAX_SPINS = 2;
                if (spins < MAX_SPINS) {
                    if (retry) retry.classList.remove('hidden');
                    if (exhausted) exhausted.classList.add('hidden');
                } else {
                    if (retry) retry.classList.add('hidden');
                    if (exhausted) exhausted.classList.remove('hidden');
                }
            },
            showDone() {
                const idle = document.getElementById('drGachaIdle');
                const result = document.getElementById('drGachaResult');
                const done = document.getElementById('drGachaDone');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.add('hidden');
                if (done) done.classList.remove('hidden');
            },
            async spin() {
                const MAX_SPINS = 2;
                const state = this.getState();
                const currentSpins = state ? state.spins : 0;
                if (currentSpins >= MAX_SPINS) {
                    App.toast('本日のガチャは終了です');
                    return;
                }
                const idle = document.getElementById('drGachaIdle');
                const result = document.getElementById('drGachaResult');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.remove('hidden');
                const card = document.getElementById('drGachaCard');
                if (card) card.classList.add('spinning');
                try {
                    const res = await fetch('/drama/api/gacha.php');
                    const json = await res.json();
                    setTimeout(() => {
                        if (card) card.classList.remove('spinning');
                        if (json.status === 'success' && json.data) {
                            const newSpins = currentSpins + 1;
                            const d = json.data;
                            const item = {
                                id: d.id,
                                title: d.title,
                                name: d.title,
                                poster_path: d.poster_path || null,
                                first_air_date: d.first_air_date || null,
                                vote_average: d.vote_average || null,
                                runtime_avg: d.runtime_avg || null,
                            };
                            this.saveState(newSpins, item);
                            const limitEl = document.getElementById('drGachaLimit');
                            if (limitEl) limitEl.textContent = `残り ${MAX_SPINS - newSpins}/${MAX_SPINS} 回`;
                            this.showResult(item, newSpins);
                        } else if (json.status === 'empty') {
                            App.toast('見たいリストが空です');
                        }
                    }, 700);
                } catch (e) {
                    if (card) card.classList.remove('spinning');
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            },
            updateUI(item) {
                this.currentId = item.id;
                const titleEl = document.getElementById('drGachaTitle');
                const poster = document.getElementById('drGachaPoster');
                const meta = document.getElementById('drGachaMeta');
                const detailLink = document.getElementById('drGachaDetailLink');
                if (titleEl) titleEl.textContent = item.title || '';
                if (detailLink) detailLink.href = '/drama/detail.php?id=' + item.id;
                const googleLink = document.getElementById('drGachaGoogleLink');
                if (googleLink) googleLink.href = 'https://www.google.com/search?q=' + encodeURIComponent((item.title || '') + ' ドラマ');
                if (poster) {
                    if (item.poster_path) {
                        poster.innerHTML = '';
                        const img = document.createElement('img');
                        img.src = 'https://image.tmdb.org/t/p/w342' + item.poster_path;
                        img.alt = item.title || '';
                        img.className = 'w-36 h-[216px] object-cover rounded-xl shadow-2xl ring-2 ring-white/20';
                        poster.replaceWith(img);
                        img.id = 'drGachaPoster';
                    } else {
                        if (!poster.querySelector('i')) {
                            poster.innerHTML = '<i class="fa-solid fa-clapperboard text-4xl text-slate-500"></i>';
                        }
                    }
                }
                if (meta) {
                    let html = '';
                    if (item.first_air_date) html += '<span>' + item.first_air_date.substring(0, 4) + '年</span>';
                    if (item.vote_average && item.vote_average > 0) html += '<span class="text-amber-400"><i class="fa-solid fa-star text-xs"></i> ' + Number(item.vote_average).toFixed(1) + '</span>';
                    if (item.runtime_avg) html += '<span>' + item.runtime_avg + '分</span>';
                    meta.innerHTML = html;
                }
            },
            async markWatched() {
                if (!this.currentId) return;
                try {
                    const result = await App.post('/drama/api/update.php', {
                        id: this.currentId,
                        status: 'watched',
                    });
                    if (result.status === 'success') {
                        App.toast('「見た」に移動しました！');
                        const state = this.getState();
                        if (state) {
                            const refunded = Math.max(0, state.spins - 1);
                            this.saveState(refunded, null);
                        }
                        this.showIdle();
                        const limitEl = document.getElementById('drGachaLimit');
                        const s = this.getState();
                        const spins = s ? s.spins : 0;
                        if (limitEl) limitEl.textContent = `残り ${2 - spins}/2 回`;
                    } else {
                        App.toast(result.message || '更新に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            }
        };

        DrGacha.init();

        const DrRec = {
            esc(str) {
                const d = document.createElement('div');
                d.textContent = str || '';
                return d.innerHTML;
            },
            data: {},
            renderCard(s) {
                const id = s.id;
                this.data[id] = s;
                const name = s.name || s.title || '';
                const year = (s.first_air_date || '').substring(0, 4);
                const rating = s.vote_average ? Number(s.vote_average).toFixed(1) : '';
                const imgPath = s.poster_path || s.backdrop_path || '';
                const poster = imgPath
                    ? `<img src="https://image.tmdb.org/t/p/w342${imgPath}" alt="${this.esc(name)}" class="w-full h-full object-cover rounded-xl" loading="lazy">`
                    : `<div class="w-full h-full bg-slate-100 rounded-xl flex items-center justify-center"><i class="fa-solid fa-clapperboard text-2xl text-slate-300"></i></div>`;
                const registered = s._registered;
                let actionBtns;
                if (registered) {
                    actionBtns = '<div class="absolute top-1.5 right-1.5 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                } else {
                    actionBtns = `<div class="absolute top-1.5 right-1.5 flex flex-col gap-1">
                        <button onclick="event.stopPropagation(); DrRec.addToList(${id}, 'wanna_watch', this)" class="w-6 h-6 bg-white/90 hover:bg-amber-400 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たいリストに追加"><i class="fa-solid fa-bookmark"></i></button>
                        <button onclick="event.stopPropagation(); DrRec.addToList(${id}, 'watching', this)" class="w-6 h-6 bg-white/90 hover:bg-sky-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見てるに追加"><i class="fa-solid fa-play"></i></button>
                        <button onclick="event.stopPropagation(); DrRec.addToList(${id}, 'watched', this)" class="w-6 h-6 bg-white/90 hover:bg-green-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たに追加"><i class="fa-solid fa-check"></i></button>
                    </div>`;
                }
                return `<div class="rec-card cursor-pointer" onclick="if (typeof DramaPreview !== 'undefined' && DramaPreview) { DramaPreview.open(DrRec.data[${id}]); }">
                    <div class="aspect-[2/3] relative overflow-hidden rounded-xl shadow-sm mb-2">
                        ${poster}
                        ${actionBtns}
                        ${rating ? `<div class="absolute bottom-1.5 left-1.5 bg-black/70 text-white text-[10px] font-bold px-1.5 py-0.5 rounded"><i class="fa-solid fa-star text-amber-400 mr-0.5"></i>${rating}</div>` : ''}
                    </div>
                    <h3 class="text-xs font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5">${this.esc(name)}</h3>
                    <p class="text-[10px] text-slate-400">${year ? this.esc(year + '年') : ''}</p>
                </div>`;
            },
            async addToList(tmdbId, status, btnEl) {
                btnEl.disabled = true;
                const origHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                try {
                    const result = await App.post('/drama/api/add.php', { tmdb_id: tmdbId, status: status });
                    if (result.status === 'success') {
                        const wrap = btnEl.closest('.flex.flex-col') || btnEl;
                        wrap.outerHTML = '<div class="absolute top-1.5 right-1.5 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                        const msgMap = { wanna_watch: '見たいリストに追加しました', watching: '見てるに追加しました', watched: '見たに追加しました' };
                        App.toast(msgMap[status] || '追加しました');
                    } else {
                        App.toast(result.message || '追加に失敗しました');
                        btnEl.disabled = false;
                        btnEl.innerHTML = origHtml;
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                    btnEl.disabled = false;
                    btnEl.innerHTML = origHtml;
                }
            },
            scroll(cardsId, dir) {
                const el = document.getElementById(cardsId);
                if (!el) return;
                const card = el.querySelector('.rec-card');
                const cardW = card ? card.offsetWidth : 160;
                const amount = (cardW + 12) * 3;
                el.scrollBy({ left: dir * amount, behavior: 'smooth' });
            },
            updateArrows(cardsId) {
                const el = document.getElementById(cardsId);
                if (!el) return;
                const wrap = el.closest('.rec-scroll-wrap');
                if (!wrap) return;
                const leftBtn = wrap.querySelector('.rec-arrow.left');
                const rightBtn = wrap.querySelector('.rec-arrow.right');
                if (!leftBtn || !rightBtn) return;
                const update = () => {
                    const atStart = el.scrollLeft <= 4;
                    const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
                    leftBtn.classList.toggle('hidden', atStart);
                    rightBtn.classList.toggle('hidden', atEnd);
                };
                el.addEventListener('scroll', update, { passive: true });
                update();
            },
            async loadSection(type, sectionId, cardsId, loadingId, onMeta) {
                try {
                    const resp = await fetch('/drama/api/recommendations.php?type=' + encodeURIComponent(type));
                    const json = await resp.json();
                    const loading = document.getElementById(loadingId);
                    const cards = document.getElementById(cardsId);
                    const section = document.getElementById(sectionId);
                    if (json.status === 'success' && json.data && json.data.length > 0) {
                        if (section) section.classList.remove('hidden');
                        if (cards) {
                            cards.innerHTML = json.data.map(s => this.renderCard(s)).join('');
                            cards.classList.remove('hidden');
                        }
                        if (loading) loading.classList.add('hidden');
                        if (onMeta) onMeta(json);
                        requestAnimationFrame(() => this.updateArrows(cardsId));
                    } else {
                        if (loading) loading.classList.add('hidden');
                    }
                } catch (e) {
                    console.error('DrRec load error (' + type + '):', e);
                    const loading = document.getElementById(loadingId);
                    if (loading) loading.classList.add('hidden');
                }
            },
            init() {
                const tabs = document.querySelectorAll('.dr-rec-tab');
                const sections = {
                    personal: document.getElementById('drRecPersonalSection'),
                    genre: document.getElementById('drRecGenreSection'),
                    trending: document.getElementById('drRecTrendingSection'),
                };
                const activateTab = (key) => {
                    tabs.forEach(btn => {
                        const t = btn.getAttribute('data-dr-rec-tab');
                        if (t === key) {
                            btn.classList.remove('bg-slate-100', 'text-slate-600');
                            btn.classList.add('bg-slate-800', 'text-white');
                        } else {
                            btn.classList.add('bg-slate-100', 'text-slate-600');
                            btn.classList.remove('bg-slate-800', 'text-white');
                        }
                    });
                    Object.keys(sections).forEach(k => {
                        if (sections[k]) {
                            sections[k].classList.toggle('hidden', k !== key);
                        }
                    });
                };
                tabs.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const key = btn.getAttribute('data-dr-rec-tab');
                        activateTab(key);
                    });
                });
                activateTab('personal');

                Promise.all([
                    this.loadSection('personal', 'drRecPersonalSection', 'drRecPersonalCards', 'drRecPersonalLoading'),
                    this.loadSection('genre', 'drRecGenreSection', 'drRecGenreCards', 'drRecGenreLoading', (json) => {
                        if (json.genres) {
                            const tagsEl = document.getElementById('drRecGenreTags');
                            if (tagsEl) {
                                tagsEl.innerHTML = json.genres.map(g =>
                                    `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${this.esc(g)}</span>`
                                ).join('');
                            }
                        }
                    }),
                    this.loadSection('trending', 'drRecTrendingSection', 'drRecTrendingCards', 'drRecTrendingLoading'),
                ]);
            }
        };

        DrRec.init();

        DramaSearch.init({
            inputId: 'drDashSearchInput',
            resultsId: 'drDashSearchResults',
            wrapperId: 'drDashSearchWrapper'
        });
    </script>
</body>
</html>

