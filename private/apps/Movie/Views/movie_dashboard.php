<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$totalHours = floor($totalRuntime / 60);
$totalMins = $totalRuntime % 60;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画ダッシュボード - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <style>
        :root { --mv-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .mv-theme-btn { background-color: var(--mv-theme); }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        .mv-theme-text { color: var(--mv-theme); }
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
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .gacha-card { transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .gacha-card.spinning { transform: rotateY(360deg) scale(0.9); }
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }

        @keyframes gachaPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.04); } }
        @keyframes gachaGlow { 0%,100% { box-shadow: 0 0 20px rgba(251,191,36,0.2); } 50% { box-shadow: 0 0 40px rgba(251,191,36,0.5); } }
        @keyframes gachaShine { 0% { left: -100%; } 100% { left: 200%; } }
        @keyframes gachaReveal { 0% { transform: rotateY(180deg) scale(0.7); opacity: 0; } 100% { transform: rotateY(0) scale(1); opacity: 1; } }
        .gacha-idle { animation: gachaPulse 2.5s ease-in-out infinite, gachaGlow 2.5s ease-in-out infinite; }
        .gacha-reveal { animation: gachaReveal 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        .gacha-box-shine::after {
            content: ''; position: absolute; top: 0; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            animation: gachaShine 3s ease-in-out infinite;
        }

        .rec-section { position: relative; }
        .rec-scroll-wrap { position: relative; overflow: hidden; }
        .rec-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .rec-scroll::-webkit-scrollbar { display: none; }
        .rec-card { flex: 0 0 140px; transition: transform 0.2s ease; }
        @media (min-width: 768px) { .rec-card { flex: 0 0 160px; } }
        .rec-card:hover { transform: translateY(-4px); }

        .rec-arrow {
            position: absolute; top: 50%; transform: translateY(-50%); z-index: 5;
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #475569; transition: all 0.2s;
        }
        .rec-arrow:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #1e293b; }
        .rec-arrow.left { left: -4px; }
        .rec-arrow.right { right: -4px; }
        .rec-arrow.hidden { display: none; }

        @keyframes skeletonPulse { 0%,100% { opacity: 0.4; } 50% { opacity: 0.7; } }
        .skeleton-card { animation: skeletonPulse 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-film text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">映画</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/movie/list.php?tab=watchlist" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-bookmark mr-1"></i><span class="hidden sm:inline">見たい</span>
                </a>
                <a href="/movie/list.php?tab=watched" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-check-circle mr-1"></i><span class="hidden sm:inline">見た</span>
                </a>
                <a href="/movie/import.php" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition px-3 py-2 rounded-lg hover:bg-slate-50">
                    <i class="fa-solid fa-file-import mr-1"></i><span class="hidden sm:inline">一括登録</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-6xl mx-auto">

                <!-- ① スタッツカード -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <a href="/movie/list.php?tab=watched" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:shadow-md hover:border-green-200 transition-all group block">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-green-50 text-green-500">
                                <i class="fa-solid fa-eye text-lg"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400">総鑑賞本数</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1.5">
                            <span class="text-3xl font-black text-slate-800"><?= $watchedCount ?></span>
                            <span class="text-sm font-bold text-slate-400 mb-0.5">本</span>
                        </div>
                    </a>

                    <a href="/movie/list.php?tab=watched&filter=this_month" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition-all group block">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50 text-blue-500">
                                <i class="fa-solid fa-calendar-check text-lg"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400">今月の鑑賞数</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1.5">
                            <span class="text-3xl font-black text-slate-800"><?= $thisMonthCount ?></span>
                            <span class="text-sm font-bold text-slate-400 mb-0.5">本</span>
                        </div>
                    </a>

                    <a href="/movie/list.php?tab=watchlist" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:shadow-md hover:border-amber-200 transition-all group block">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-50 text-amber-500">
                                <i class="fa-solid fa-bookmark text-lg"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400">見たいリスト</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1.5">
                            <span class="text-3xl font-black text-slate-800"><?= $watchlistCount ?></span>
                            <span class="text-sm font-bold text-slate-400 mb-0.5">本</span>
                        </div>
                    </a>

                    <div class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-purple-50 text-purple-500">
                                <i class="fa-solid fa-clock text-lg"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-400">総視聴時間</span>
                        </div>
                        <div class="flex items-end gap-1.5">
                            <span class="text-3xl font-black text-slate-800"><?= $totalHours ?></span>
                            <span class="text-sm font-bold text-slate-400 mb-0.5">時間</span>
                            <?php if ($totalMins > 0): ?>
                            <span class="text-lg font-black text-slate-500"><?= $totalMins ?></span>
                            <span class="text-xs font-bold text-slate-400 mb-0.5">分</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TMDB検索ボックス -->
                <?php if ($tmdbConfigured): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 mb-8">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-50 text-indigo-500">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </div>
                        <h2 class="text-sm font-bold text-slate-500">映画を探す</h2>
                    </div>
                    <div class="relative" id="dashSearchWrapper">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" id="dashSearchInput"
                                       placeholder="タイトルで検索..."
                                       class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] focus:border-transparent"
                                       onkeydown="if(event.key==='Enter') MovieSearch.search()"
                                       autocomplete="off">
                            </div>
                            <button onclick="MovieSearch.search()" class="px-4 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0">
                                検索
                            </button>
                        </div>
                        <div id="dashSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-slate-100 max-h-[60vh] overflow-y-auto z-50"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ② ガチャ -->
                <div class="mb-8">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 md:p-8 shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-gradient-to-bl from-white/5 to-transparent rounded-bl-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-dice text-amber-400 text-lg"></i>
                                <h2 class="text-white font-black text-lg tracking-tight">次に観る映画ガチャ</h2>
                                <span id="gachaLimit" class="ml-auto text-[10px] font-bold text-slate-500 bg-slate-700/80 px-2 py-0.5 rounded-full"></span>
                            </div>
                            <p class="text-slate-400 text-xs mb-6">見たいリストからランダムに1作品をピックアップ！<span class="text-amber-400/70">（1日1回）</span></p>

                            <?php if ($watchlistCount > 0): ?>
                            <!-- 未ガチャ状態 -->
                            <div id="gachaIdle" class="flex flex-col items-center py-4">
                                <button onclick="Gacha.spin()" class="gacha-idle gacha-box-shine relative w-40 h-40 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex flex-col items-center justify-center shadow-2xl cursor-pointer hover:scale-105 transition-transform overflow-hidden mb-5">
                                    <i class="fa-solid fa-dice text-5xl text-white/90 mb-2 drop-shadow"></i>
                                    <span class="text-white font-black text-sm tracking-wide drop-shadow">タップでガチャ</span>
                                </button>
                                <p class="text-slate-500 text-xs">見たいリスト <span class="text-amber-400 font-bold"><?= $watchlistCount ?></span> 本の中から運命の1本を...</p>
                            </div>

                            <!-- 結果表示 -->
                            <div id="gachaResult" class="hidden">
                                <div id="gachaArea" class="flex flex-col sm:flex-row items-center gap-6">
                                    <div id="gachaCard" class="gacha-card shrink-0">
                                        <div id="gachaPoster" class="w-36 h-[216px] bg-slate-700 rounded-xl flex items-center justify-center shadow-2xl ring-2 ring-white/20">
                                            <i class="fa-solid fa-film text-4xl text-slate-500"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 text-center sm:text-left">
                                        <h3 id="gachaTitle" class="text-white font-black text-xl md:text-2xl leading-tight mb-2"></h3>
                                        <div id="gachaMeta" class="flex items-center gap-3 justify-center sm:justify-start text-slate-400 text-sm mb-4"></div>
                                        <div id="gachaActions" class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                            <a id="gachaDetailLink" href="#" onclick="sessionStorage.setItem('mv_back_to','dashboard')" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition backdrop-blur-sm">
                                                <i class="fa-solid fa-info-circle mr-1.5"></i>詳細を見る
                                            </a>
                                            <button onclick="Gacha.markWatched()" class="px-4 py-2 bg-green-500/80 hover:bg-green-500 text-white text-sm font-bold rounded-lg transition">
                                                <i class="fa-solid fa-check mr-1.5"></i>見た！
                                            </button>
                                            <a id="gachaGoogleLink" href="#" target="_blank" rel="noopener" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition backdrop-blur-sm inline-flex items-center gap-1.5" title="Googleで検索">
                                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>検索
                                            </a>
                                        </div>
                                        <!-- もう1回ボタン（2回目がまだ残っているとき表示） -->
                                        <div id="gachaRetry" class="hidden mt-4 pt-3 border-t border-slate-700">
                                            <button onclick="Gacha.spin()" class="text-xs text-amber-400/80 hover:text-amber-400 transition flex items-center gap-1.5">
                                                <i class="fa-solid fa-rotate"></i>
                                                <span>どうしてももう1回だけ引く...</span>
                                            </button>
                                            <p class="text-[10px] text-slate-600 mt-1">※ 本日最後の1回です</p>
                                        </div>
                                        <!-- 上限到達 -->
                                        <div id="gachaExhausted" class="hidden mt-4 pt-3 border-t border-slate-700">
                                            <p class="text-[10px] text-slate-600"><i class="fa-solid fa-moon mr-1"></i>本日のガチャは終了しました。明日またお楽しみに！</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 上限到達（既にガチャ済みで再訪問） -->
                            <div id="gachaDone" class="hidden text-center py-6">
                                <div class="w-20 h-20 bg-slate-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-moon text-3xl text-slate-500"></i>
                                </div>
                                <p class="text-slate-400 text-sm font-bold mb-1">本日のガチャは終了</p>
                                <p class="text-slate-600 text-xs">明日また運命の1本を引きましょう</p>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fa-solid fa-box-open text-4xl text-slate-600 mb-3"></i>
                                <p class="text-slate-400 text-sm">見たいリストに映画を追加するとガチャが回せます</p>
                                <a href="/movie/list.php?tab=watchlist" class="inline-block mt-4 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition">
                                    <i class="fa-solid fa-plus mr-1.5"></i>映画を追加する
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- おすすめセクション -->
                <?php if ($tmdbConfigured): ?>

                <!-- 案A: あなたへのおすすめ -->
                <div id="recPersonalSection" class="mb-8 hidden rec-section">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-heart mv-theme-text"></i>
                        <h2 class="text-sm font-bold text-slate-700">あなたへのおすすめ</h2>
                        <span class="text-[10px] text-slate-400 ml-auto">高評価映画に基づくレコメンド</span>
                    </div>
                    <div class="rec-scroll-wrap">
                        <button class="rec-arrow left hidden" onclick="Rec.scroll('recPersonalCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="recPersonalLoading" class="rec-scroll">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="rec-card skeleton-card">
                                <div class="aspect-[2/3] bg-slate-200 rounded-xl mb-2"></div>
                                <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div id="recPersonalCards" class="rec-scroll hidden"></div>
                        <button class="rec-arrow right" onclick="Rec.scroll('recPersonalCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </div>

                <!-- 案B: 好みのジャンルから -->
                <div id="recGenreSection" class="mb-8 hidden rec-section">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-masks-theater mv-theme-text"></i>
                        <h2 class="text-sm font-bold text-slate-700">好みのジャンルから</h2>
                        <div id="recGenreTags" class="flex gap-1 ml-2"></div>
                    </div>
                    <div class="rec-scroll-wrap">
                        <button class="rec-arrow left hidden" onclick="Rec.scroll('recGenreCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="recGenreLoading" class="rec-scroll">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="rec-card skeleton-card">
                                <div class="aspect-[2/3] bg-slate-200 rounded-xl mb-2"></div>
                                <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div id="recGenreCards" class="rec-scroll hidden"></div>
                        <button class="rec-arrow right" onclick="Rec.scroll('recGenreCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </div>

                <!-- 案C: 今週のトレンド -->
                <div id="recTrendingSection" class="mb-8 rec-section">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-fire mv-theme-text"></i>
                        <h2 class="text-sm font-bold text-slate-700">今週のトレンド</h2>
                        <span class="text-[10px] text-slate-400 ml-auto">世界で話題の映画</span>
                    </div>
                    <div class="rec-scroll-wrap">
                        <button class="rec-arrow left hidden" onclick="Rec.scroll('recTrendingCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="recTrendingLoading" class="rec-scroll">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="rec-card skeleton-card">
                                <div class="aspect-[2/3] bg-slate-200 rounded-xl mb-2"></div>
                                <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div id="recTrendingCards" class="rec-scroll hidden"></div>
                        <button class="rec-arrow right" onclick="Rec.scroll('recTrendingCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </div>

                <?php endif; ?>

                <!-- ③ グラフエリア -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- 月別鑑賞本数 -->
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-chart-bar mv-theme-text"></i>
                            <h2 class="text-sm font-bold text-slate-700">月別鑑賞本数</h2>
                            <span class="text-[10px] text-slate-400 ml-auto">過去12ヶ月</span>
                        </div>
                        <div class="relative" style="height: 220px;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>

                    <!-- ジャンル分布 -->
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-chart-pie mv-theme-text"></i>
                            <h2 class="text-sm font-bold text-slate-700">ジャンル分布</h2>
                        </div>
                        <?php if (!empty($genreDistribution)): ?>
                        <div class="flex items-center gap-4">
                            <div class="relative shrink-0" style="width: 180px; height: 180px;">
                                <canvas id="genreChart"></canvas>
                            </div>
                            <div class="flex-1 min-w-0 space-y-1.5 max-h-[180px] overflow-y-auto" id="genreLegend">
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-10 text-slate-400">
                            <i class="fa-solid fa-chart-pie text-3xl mb-2"></i>
                            <p class="text-sm">鑑賞データがたまるとジャンル分布が表示されます</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 評価スコア分布 -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-star-half-stroke mv-theme-text"></i>
                        <h2 class="text-sm font-bold text-slate-700">評価スコア分布</h2>
                        <?php if ($ratedCount > 0): ?>
                        <span class="text-[10px] text-slate-400 ml-auto"><?= $ratedCount ?>本評価済み</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($ratedCount > 0): ?>
                    <div class="relative" style="height: 200px;">
                        <canvas id="ratingChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-star text-3xl mb-2"></i>
                        <p class="text-sm">映画に評価をつけるとスコア分布が表示されます</p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php require_once __DIR__ . '/_tmdb_attribution.php'; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php require_once __DIR__ . '/_movie_search_shared.php'; ?>
    <script>
        const themeColor = '<?= htmlspecialchars($themePrimaryHex) ?>';

        const MAX_SPINS = 2;

        const Gacha = {
            currentId: null,
            storageKey: 'mv_gacha',

            getState() {
                try {
                    const raw = localStorage.getItem(this.storageKey);
                    if (!raw) return null;
                    const s = JSON.parse(raw);
                    if (s.date !== new Date().toISOString().slice(0, 10)) return null;
                    return s;
                } catch { return null; }
            },

            saveState(spins, movie) {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    date: new Date().toISOString().slice(0, 10),
                    spins: spins,
                    movie: movie || null,
                }));
            },

            init() {
                const state = this.getState();
                const limitEl = document.getElementById('gachaLimit');

                if (!state || state.spins === 0) {
                    this.showIdle();
                    if (limitEl) limitEl.textContent = '残り 2/2 回';
                } else if (state.spins < MAX_SPINS) {
                    if (state.movie) {
                        this.showResult(state.movie, state.spins);
                    } else {
                        this.showIdle();
                    }
                    if (limitEl) limitEl.textContent = `残り ${MAX_SPINS - state.spins}/${MAX_SPINS} 回`;
                } else {
                    if (state.movie) {
                        this.showResult(state.movie, state.spins);
                    } else {
                        this.showDone();
                    }
                    if (limitEl) limitEl.textContent = '残り 0/2 回';
                }
            },

            showIdle() {
                const idle = document.getElementById('gachaIdle');
                const result = document.getElementById('gachaResult');
                const done = document.getElementById('gachaDone');
                if (idle) idle.classList.remove('hidden');
                if (result) result.classList.add('hidden');
                if (done) done.classList.add('hidden');
            },

            showResult(movie, spins) {
                const idle = document.getElementById('gachaIdle');
                const result = document.getElementById('gachaResult');
                const done = document.getElementById('gachaDone');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.remove('hidden');
                if (done) done.classList.add('hidden');

                if (movie) this.updateUI(movie);

                const retry = document.getElementById('gachaRetry');
                const exhausted = document.getElementById('gachaExhausted');
                if (spins < MAX_SPINS) {
                    if (retry) retry.classList.remove('hidden');
                    if (exhausted) exhausted.classList.add('hidden');
                } else {
                    if (retry) retry.classList.add('hidden');
                    if (exhausted) exhausted.classList.remove('hidden');
                }
            },

            showDone() {
                const idle = document.getElementById('gachaIdle');
                const result = document.getElementById('gachaResult');
                const done = document.getElementById('gachaDone');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.add('hidden');
                if (done) done.classList.remove('hidden');
            },

            async spin() {
                const state = this.getState();
                const currentSpins = state ? state.spins : 0;
                if (currentSpins >= MAX_SPINS) {
                    App.toast('本日のガチャは終了です');
                    return;
                }

                const idle = document.getElementById('gachaIdle');
                const result = document.getElementById('gachaResult');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.remove('hidden');

                const card = document.getElementById('gachaCard');
                if (card) card.classList.add('spinning');

                try {
                    const res = await fetch('/movie/api/gacha.php');
                    const json = await res.json();

                    setTimeout(() => {
                        if (card) card.classList.remove('spinning');

                        if (json.status === 'success' && json.data) {
                            const newSpins = currentSpins + 1;
                            const movieData = {
                                id: json.data.id,
                                title: json.data.title,
                                poster_path: json.data.poster_path || null,
                                release_date: json.data.release_date || null,
                                vote_average: json.data.vote_average || null,
                                runtime: json.data.runtime || null,
                            };
                            this.saveState(newSpins, movieData);

                            const posterEl = document.getElementById('gachaPoster');
                            if (posterEl) posterEl.closest('.gacha-card')?.classList.add('gacha-reveal');

                            this.showResult(movieData, newSpins);

                            const limitEl = document.getElementById('gachaLimit');
                            if (limitEl) limitEl.textContent = `残り ${MAX_SPINS - newSpins}/${MAX_SPINS} 回`;
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

            updateUI(m) {
                this.currentId = m.id;
                const title = document.getElementById('gachaTitle');
                const poster = document.getElementById('gachaPoster');
                const meta = document.getElementById('gachaMeta');
                const detailLink = document.getElementById('gachaDetailLink');

                if (title) title.textContent = m.title;
                if (detailLink) detailLink.href = '/movie/detail.php?id=' + m.id;
                const googleLink = document.getElementById('gachaGoogleLink');
                if (googleLink) googleLink.href = 'https://www.google.com/search?q=' + encodeURIComponent(m.title + ' 映画');

                if (poster) {
                    if (m.poster_path) {
                        if (poster.tagName === 'IMG') {
                            poster.src = 'https://image.tmdb.org/t/p/w342' + m.poster_path;
                            poster.alt = m.title;
                        } else {
                            poster.outerHTML = `<img id="gachaPoster" src="https://image.tmdb.org/t/p/w342${m.poster_path}" alt="${this.esc(m.title)}" class="w-36 h-[216px] object-cover rounded-xl shadow-2xl ring-2 ring-white/20">`;
                        }
                    } else {
                        if (poster.tagName !== 'IMG') return;
                        poster.outerHTML = `<div id="gachaPoster" class="w-36 h-[216px] bg-slate-700 rounded-xl flex items-center justify-center shadow-2xl ring-2 ring-white/20"><i class="fa-solid fa-film text-4xl text-slate-500"></i></div>`;
                    }
                }

                if (meta) {
                    let html = '';
                    if (m.release_date) html += `<span>${m.release_date.substring(0,4)}年</span>`;
                    if (m.vote_average && m.vote_average > 0) html += `<span class="text-amber-400"><i class="fa-solid fa-star text-xs"></i> ${Number(m.vote_average).toFixed(1)}</span>`;
                    if (m.runtime) html += `<span>${m.runtime}分</span>`;
                    meta.innerHTML = html;
                }
            },

            async markWatched() {
                if (!this.currentId) return;
                try {
                    const result = await App.post('/movie/api/update.php', {
                        id: this.currentId,
                        status: 'watched',
                    });
                    if (result.status === 'success') {
                        App.toast('見たリストに移動しました！');
                        const state = this.getState();
                        if (state) {
                            const refunded = Math.max(0, state.spins - 1);
                            this.saveState(refunded, null);
                        }
                        this.showIdle();
                        this.updateLimit();
                    } else {
                        App.toast(result.message || '更新に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            },

            updateLimit() {
                const state = this.getState();
                const spins = state ? state.spins : 0;
                const el = document.getElementById('gachaLimit');
                if (el) el.textContent = `残り ${MAX_SPINS - spins}/${MAX_SPINS} 回`;
            },

            esc(str) {
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        };

        Gacha.init();

        // --- Chart.js ---
        const chartFont = { family: "'Inter', 'Noto Sans JP', sans-serif" };
        Chart.defaults.font.family = chartFont.family;
        Chart.defaults.font.size = 11;

        // 月別鑑賞本数
        (() => {
            const data = <?= json_encode(array_values($monthlyWatchCounts)) ?>;
            const labels = <?= json_encode(array_map(fn($ym) => date('n月', strtotime($ym . '-01')), array_keys($monthlyWatchCounts))) ?>;
            const ctx = document.getElementById('monthlyChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: themeColor + '40',
                        borderColor: themeColor,
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

        // ジャンル分布
        (() => {
            const raw = <?= json_encode($genreDistribution, JSON_UNESCAPED_UNICODE) ?>;
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
                '#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6',
                '#ec4899', '#06b6d4', '#f97316', '#84cc16', '#94a3b8'
            ];

            const ctx = document.getElementById('genreChart');
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
            const legend = document.getElementById('genreLegend');
            if (legend) {
                legend.innerHTML = labels.map((l, i) => {
                    const pct = ((data[i] / total) * 100).toFixed(0);
                    return `<div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${palette[i]}"></span>
                        <span class="text-xs text-slate-600 truncate flex-1">${l}</span>
                        <span class="text-[11px] font-bold text-slate-500">${pct}%</span>
                    </div>`;
                }).join('');
            }
        })();

        // 評価スコア分布
        (() => {
            const dist = <?= json_encode(array_values($ratingDistribution)) ?>;
            if (dist.every(v => v === 0)) return;

            const ctx = document.getElementById('ratingChart');
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
                            const r = Math.round(251 * (1 - ratio) + 34 * ratio);
                            const g = Math.round(146 * (1 - ratio) + 197 * ratio);
                            const b = Math.round(60 * (1 - ratio) + 94 * ratio);
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

        // ─── おすすめ映画セクション ───
        const Rec = {
            esc(str) {
                const d = document.createElement('div');
                d.textContent = str ?? '';
                return d.innerHTML;
            },

            movieData: {},

            renderCard(m) {
                this.movieData[m.id] = m;
                MoviePreview.storeMovie(m);
                const year = m.release_date ? m.release_date.substring(0, 4) : '';
                const rating = m.vote_average ? Number(m.vote_average).toFixed(1) : '';
                const poster = m.poster_path
                    ? `<img src="https://image.tmdb.org/t/p/w342${m.poster_path}" alt="${this.esc(m.title)}" class="w-full h-full object-cover rounded-xl" loading="lazy">`
                    : `<div class="w-full h-full bg-slate-100 rounded-xl flex items-center justify-center"><i class="fa-solid fa-film text-3xl text-slate-300"></i></div>`;
                const registered = m._registered;
                const actionBtns = registered
                    ? `<div class="absolute top-1.5 right-1.5 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>`
                    : `<div class="absolute top-1.5 right-1.5 flex flex-col gap-1">
                        <button onclick="event.stopPropagation(); Rec.addToList(${m.id}, 'watchlist', this)" class="w-6 h-6 bg-white/90 hover:bg-amber-400 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たいリストに追加"><i class="fa-solid fa-bookmark"></i></button>
                        <button onclick="event.stopPropagation(); Rec.addToList(${m.id}, 'watched', this)" class="w-6 h-6 bg-white/90 hover:bg-green-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たリストに追加"><i class="fa-solid fa-eye"></i></button>
                    </div>`;

                return `<div class="rec-card cursor-pointer" onclick="MoviePreview.open(Rec.movieData[${m.id}])">
                    <div class="aspect-[2/3] relative overflow-hidden rounded-xl shadow-sm mb-2 group">
                        ${poster}
                        ${actionBtns}
                        ${rating ? `<div class="absolute bottom-1.5 left-1.5 bg-black/70 text-white text-[10px] font-bold px-1.5 py-0.5 rounded"><i class="fa-solid fa-star text-amber-400 mr-0.5"></i>${rating}</div>` : ''}
                    </div>
                    <h3 class="text-xs font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5">${this.esc(m.title)}</h3>
                    <p class="text-[10px] text-slate-400">${year}</p>
                </div>`;
            },

            async addToList(tmdbId, status, btnEl) {
                btnEl.disabled = true;
                const origHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                try {
                    const result = await App.post('/movie/api/add.php', { tmdb_id: tmdbId, status: status });
                    if (result.status === 'success') {
                        const wrap = btnEl.closest('.flex.flex-col') || btnEl;
                        wrap.outerHTML = '<div class="absolute top-1.5 right-1.5 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                        App.toast(status === 'watched' ? '見たリストに追加しました' : '見たいリストに追加しました');
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
                const cardW = el.querySelector('.rec-card')?.offsetWidth || 160;
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
                    const resp = await fetch('/movie/api/recommendations.php?type=' + type);
                    const json = await resp.json();

                    const loading = document.getElementById(loadingId);
                    const cards = document.getElementById(cardsId);
                    const section = document.getElementById(sectionId);

                    if (json.status === 'success' && json.data && json.data.length > 0) {
                        if (section) section.classList.remove('hidden');
                        if (cards) {
                            cards.innerHTML = json.data.map(m => this.renderCard(m)).join('');
                            cards.classList.remove('hidden');
                        }
                        if (loading) loading.classList.add('hidden');
                        if (onMeta) onMeta(json);
                        requestAnimationFrame(() => this.updateArrows(cardsId));
                    } else {
                        if (loading) loading.classList.add('hidden');
                    }
                } catch (e) {
                    console.error('Rec load error (' + type + '):', e);
                    const loading = document.getElementById(loadingId);
                    if (loading) loading.classList.add('hidden');
                }
            },

            init() {
                Promise.all([
                    this.loadSection('personal', 'recPersonalSection', 'recPersonalCards', 'recPersonalLoading'),
                    this.loadSection('genre', 'recGenreSection', 'recGenreCards', 'recGenreLoading', (json) => {
                        if (json.genres) {
                            const tagsEl = document.getElementById('recGenreTags');
                            if (tagsEl) {
                                tagsEl.innerHTML = json.genres.map(g =>
                                    `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${this.esc(g)}</span>`
                                ).join('');
                            }
                        }
                    }),
                    this.loadSection('trending', 'recTrendingSection', 'recTrendingCards', 'recTrendingLoading'),
                ]);
            }
        };

        Rec.init();

        MovieSearch.init({
            inputId: 'dashSearchInput',
            resultsId: 'dashSearchResults',
            wrapperId: 'dashSearchWrapper',
        });

    </script>
</body>
</html>
