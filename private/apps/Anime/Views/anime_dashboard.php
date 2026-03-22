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

// 友人の視聴（アニメのみ）
$friendsActivityItems = [];
$friendsActivityHasViewable = false;
try {
    $friendsActivityService = new \App\FriendsActivity\Service\FriendsActivityService();
    $friendsActivityHasViewable = $friendsActivityService->hasViewableUsers((int)($_SESSION['user']['id'] ?? 0));
    if ($friendsActivityHasViewable) {
        $friendsActivityItems = $friendsActivityService->getFriendsWatchedItems((int)($_SESSION['user']['id'] ?? 0), 12, 'anime');
    }
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('Friends activity (anime) fetch error', $e);
}
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
        .gacha-card { transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .gacha-card.spinning { transform: rotateY(360deg) scale(0.9); }
        @keyframes gachaPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.04); } }
        @keyframes gachaGlow { 0%,100% { box-shadow: 0 0 20px rgba(14,165,233,0.2); } 50% { box-shadow: 0 0 40px rgba(14,165,233,0.5); } }
        @keyframes gachaReveal { 0% { transform: rotateY(180deg) scale(0.7); opacity: 0; } 100% { transform: rotateY(0) scale(1); opacity: 1; } }
        .gacha-idle { animation: gachaPulse 2.5s ease-in-out infinite, gachaGlow 2.5s ease-in-out infinite; }
        .gacha-reveal { animation: gachaReveal 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        .rec-scroll-wrap { position: relative; overflow: hidden; }
        .rec-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 20px; padding-bottom: 12px; }
        .rec-scroll::-webkit-scrollbar { display: none; }
        .rec-card { flex: 0 0 190px; transition: transform 0.2s ease; }
        @media (min-width: 768px) { .rec-card { flex: 0 0 230px; } }
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
                <a href="/anime/import.php" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-xs sm:text-sm font-bold rounded-lg hover:bg-slate-50 transition" title="一括登録">
                    <i class="fa-solid fa-file-import"></i>
                    <span>一括登録</span>
                </a>
                <?php if ($oauthConfigured && $hasToken): ?>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full mr-1 hidden sm:inline-flex items-center gap-1"><i class="fa-solid fa-link text-[9px]"></i>Annict 連携済み</span>
                <button type="button" id="annictRevokeBtn" class="text-[10px] font-bold text-slate-400 hover:text-red-600 px-2 py-1 rounded hover:bg-slate-50 transition" title="Annict 連携を解除">連携解除</button>
                <?php endif; ?>
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
                <?php elseif (!$hasToken): ?>
                <!-- Annict 連携 CTA -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 md:p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-sky-50 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-link text-2xl text-sky-500"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800 mb-2">Annict と連携する</h2>
                    <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">Annict に登録済みの視聴状況を読み込み、検索で作品を追加できるようになります。</p>
                    <a href="<?= htmlspecialchars($authorizeUrl) ?>" class="inline-flex items-center gap-2 px-6 py-3 anime-theme-btn text-white font-bold rounded-xl transition hover:brightness-110">
                        <i class="fa-solid fa-right-to-bracket"></i>Annict で連携する
                    </a>
                </div>
                <?php else: ?>

                <!-- スタッツカード -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-3 md:gap-4 mb-6 md:mb-8">
                    <a href="/anime/list.php?tab=watched" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 hover:shadow-md hover:border-green-200 transition-all group block">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-green-50 text-green-500">
                                <i class="fa-solid fa-eye text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">見た</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= $stats['watched'] ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                        </div>
                    </a>
                    <a href="/anime/list.php?tab=watching" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 hover:shadow-md hover:border-blue-200 transition-all group block">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-blue-50 text-blue-500">
                                <i class="fa-solid fa-play text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">見てる</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= $stats['watching'] ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                        </div>
                    </a>
                    <a href="/anime/list.php?tab=wanna_watch" class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm p-3 md:p-5 hover:shadow-md hover:border-amber-200 transition-all group block">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-amber-50 text-amber-500">
                                <i class="fa-solid fa-bookmark text-sm md:text-lg"></i>
                            </div>
                            <span class="text-[11px] md:text-xs font-bold text-slate-400">見たい</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 ml-auto text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl md:text-3xl font-black text-slate-800"><?= $stats['wanna_watch'] ?></span>
                            <span class="text-xs md:text-sm font-bold text-slate-400 mb-0.5">作品</span>
                        </div>
                    </a>
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

                <!-- ガチャ -->
                <div class="mb-6 md:mb-8">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-4 md:p-6 lg:p-8 shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-gradient-to-bl from-white/5 to-transparent rounded-bl-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-dice text-sky-400 text-lg"></i>
                                <h2 class="text-white font-black text-lg tracking-tight">見たいリストガチャ</h2>
                            </div>
                            <p class="text-slate-400 text-xs mb-4 md:mb-6">見たいリストからランダムに1作品をピックアップ！</p>

                            <?php if (($stats['wanna_watch'] ?? 0) > 0): ?>
                            <div id="animeGachaIdle" class="flex flex-col items-center py-4">
                                <button type="button" onclick="AnimeGacha.spin()" class="gacha-idle relative w-32 h-32 md:w-40 md:h-40 bg-gradient-to-br from-sky-400 to-sky-600 rounded-2xl flex flex-col items-center justify-center shadow-2xl cursor-pointer hover:scale-105 transition-transform overflow-hidden mb-4 md:mb-5">
                                    <i class="fa-solid fa-dice text-4xl md:text-5xl text-white/90 mb-1.5 md:mb-2 drop-shadow"></i>
                                    <span class="text-white font-black text-xs md:text-sm tracking-wide drop-shadow">タップでガチャ</span>
                                </button>
                                <p class="text-slate-500 text-xs">見たいリスト <span class="text-sky-400 font-bold"><?= $stats['wanna_watch'] ?></span> 作品の中から運命の1本を...</p>
                            </div>
                            <div id="animeGachaResult" class="hidden">
                                <div class="flex flex-col sm:flex-row items-center gap-6">
                            <div id="animeGachaCard" class="gacha-card shrink-0">
                                <div id="animeGachaPoster" class="w-64 md:w-80 aspect-[16/9] bg-slate-700 rounded-xl flex items-center justify-center shadow-2xl ring-2 ring-white/20">
                                    <i class="fa-solid fa-tv text-4xl text-slate-500"></i>
                                </div>
                            </div>
                                    <div class="flex-1 text-center sm:text-left">
                                        <h3 id="animeGachaTitle" class="text-white font-black text-xl md:text-2xl leading-tight mb-2"></h3>
                                        <div id="animeGachaMeta" class="flex items-center gap-3 justify-center sm:justify-start text-slate-400 text-sm mb-4"></div>
                                        <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                            <a id="animeGachaDetailLink" href="#" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition backdrop-blur-sm">
                                                <i class="fa-solid fa-info-circle mr-1.5"></i>詳細を見る
                                            </a>
                                            <button type="button" onclick="AnimeGacha.spin()" class="px-4 py-2 bg-sky-500/80 hover:bg-sky-500 text-white text-sm font-bold rounded-lg transition">
                                                <i class="fa-solid fa-rotate mr-1.5"></i>もう一度引く
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fa-solid fa-box-open text-4xl text-slate-600 mb-3"></i>
                                <p class="text-slate-400 text-sm">見たいリストに作品を追加するとガチャが回せます</p>
                                <a href="/anime/list.php?tab=wanna_watch" class="inline-block mt-4 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-lg transition">
                                    <i class="fa-solid fa-plus mr-1.5"></i>作品を追加する
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 今期アニメ（Annict API から取得） -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-calendar text-sky-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">今期のアニメ</h2>
                        <span id="animeCurrentSeasonLabel" class="text-[10px] text-slate-400 ml-1"></span>
                        <a href="/anime/current_season.php" class="ml-auto text-[11px] font-bold text-sky-500 hover:text-sky-600 inline-flex items-center gap-1">
                            <span>もっと見る</span>
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    </div>
                    <div id="animeCurrentSeasonContainer" class="rec-scroll-wrap">
                        <div class="flex items-center justify-center py-6 text-slate-400 text-sm" id="animeCurrentSeasonLoading">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>読み込み中...
                        </div>
                        <div class="hidden" id="animeCurrentSeasonEmpty">
                            <p class="text-center text-slate-400 text-sm py-6"><i class="fa-solid fa-circle-info mr-1"></i>今期のアニメ情報を取得できませんでした。</p>
                        </div>
                        <button type="button" class="rec-arrow left hidden" onclick="AnimeCurrentSeason.scroll(-1)" aria-label="左にスクロール"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="animeCurrentSeasonScroll" class="rec-scroll hidden"></div>
                        <button type="button" class="rec-arrow right hidden" onclick="AnimeCurrentSeason.scroll(1)" aria-label="右にスクロール"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </div>

                <!-- 視聴中 -->
                <?php if (!empty($watchingWorks)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6 rec-section">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-700"><i class="fa-solid fa-play text-sky-500 mr-2"></i>視聴中</h2>
                        <a href="/anime/list.php?tab=watching" class="text-xs font-bold text-sky-500 hover:text-sky-600 transition">もっと見る <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                    </div>
                    <div class="rec-scroll-wrap" id="animeWatchingContainer">
                        <button type="button" class="rec-arrow left" onclick="document.getElementById('watchingScroll').scrollBy({left:-200,behavior:'smooth'})" aria-label="左にスクロール"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="watchingScroll" class="rec-scroll">
                            <?php foreach (array_slice($watchingWorks, 0, 12) as $w): ?>
                            <div class="rec-card block shrink-0 group cursor-pointer" data-anime-id="<?= (int)($w['id'] ?? 0) ?>">
                                <div class="aspect-[16/9] rounded-xl overflow-hidden bg-slate-100 mb-2">
                                    <?php if (!empty($w['images']['recommended_url'])): ?>
                                    <img src="<?= htmlspecialchars($w['images']['recommended_url']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy" referrerpolicy="no-referrer">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center"><i class="fa-solid fa-tv text-3xl text-slate-300"></i></div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs font-bold text-slate-700 truncate"><?= htmlspecialchars($w['title'] ?? '') ?></p>
                                <?php if (isset($w['episodes_count']) && $w['episodes_count'] > 0): ?>
                                <p class="text-[10px] text-slate-400"><?= $w['episodes_count'] ?>話</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="rec-arrow right" onclick="document.getElementById('watchingScroll').scrollBy({left:200,behavior:'smooth'})" aria-label="右にスクロール"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 友人が視聴したアニメ -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-700"><i class="fa-solid fa-user-group text-sky-500 mr-2"></i>友人が視聴したアニメ</h2>
                        <a href="/friends_activity.php?filter=anime" class="text-xs font-bold text-sky-500 hover:text-sky-600 transition">もっと見る <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                    </div>
                    <?php if (!$friendsActivityHasViewable): ?>
                    <p class="text-sm text-slate-500 py-4">友達またはグループに<strong>自分もメンバーとして</strong>参加すると、友人の視聴履歴がここに表示されます。管理画面で友達登録・グループへの追加を行ってください。</p>
                    <?php elseif (empty($friendsActivityItems)): ?>
                    <p class="text-sm text-slate-500 py-4">まだ視聴履歴はありません</p>
                    <?php else: ?>
                    <div class="rec-scroll-wrap" id="friendsAnimeContainer">
                        <button type="button" class="rec-arrow left" onclick="document.getElementById('friendsAnimeScroll').scrollBy({left:-200,behavior:'smooth'})" aria-label="左にスクロール"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="friendsAnimeScroll" class="rec-scroll">
                            <?php foreach ($friendsActivityItems as $fa): ?>
                            <?php $reg = !empty($fa['_registered']); ?>
                            <div class="rec-card shrink-0 group cursor-pointer relative" role="button" tabindex="0" onclick="AnimePreview.open(<?= htmlspecialchars(json_encode(['id' => $fa['item_id'], 'title' => $fa['title'], 'images' => ['recommended_url' => $fa['image_url'] ?? '']])) ?>)">
                                <div class="aspect-[16/9] rounded-xl overflow-hidden bg-slate-100 mb-2 relative">
                                    <?php if (!empty($fa['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($fa['image_url']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy" referrerpolicy="no-referrer">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center"><i class="fa-solid fa-tv text-3xl text-slate-300"></i></div>
                                    <?php endif; ?>
                                    <?php if ($reg): ?>
                                    <div class="absolute top-1.5 right-1.5 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>
                                    <?php else: ?>
                                    <div class="absolute top-1.5 right-1.5 flex flex-col gap-1" onclick="event.stopPropagation()">
                                        <button onclick="FriendsAnimeRec.addStatus(<?= (int)$fa['item_id'] ?>, 'wanna_watch', this)" class="w-6 h-6 bg-white/90 hover:bg-amber-400 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たいリストに追加"><i class="fa-solid fa-bookmark"></i></button>
                                        <button onclick="FriendsAnimeRec.addStatus(<?= (int)$fa['item_id'] ?>, 'watching', this)" class="w-6 h-6 bg-white/90 hover:bg-sky-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見てるに追加"><i class="fa-solid fa-play"></i></button>
                                        <button onclick="FriendsAnimeRec.addStatus(<?= (int)$fa['item_id'] ?>, 'watched', this)" class="w-6 h-6 bg-white/90 hover:bg-green-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たに追加"><i class="fa-solid fa-check"></i></button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs font-bold text-slate-700 truncate"><?= htmlspecialchars($fa['title']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($fa['id_name']) ?><?= !empty($fa['watched_date']) ? '・' . htmlspecialchars($fa['watched_date']) : '' ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="rec-arrow right" onclick="document.getElementById('friendsAnimeScroll').scrollBy({left:200,behavior:'smooth'})" aria-label="右にスクロール"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 媒体分布・ステータス分布・季節分布 -->
                <?php
                $statusLabels = ['watching' => '見てる', 'watched' => '見た', 'wanna_watch' => '見たい'];
                $hasAnyStatus = array_sum($stats) > 0;
                $hasCharts = !empty($mediaDistribution) || $hasAnyStatus || !empty($seasonDistribution);
                ?>
                <?php if ($hasCharts): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php if (!empty($mediaDistribution)): ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-chart-pie text-sky-500 mr-2"></i>媒体分布</h2>
                        <div class="flex items-center gap-4">
                            <div class="shrink-0 w-28 h-28"><canvas id="mediaChart"></canvas></div>
                            <div id="mediaLegend" class="flex-1 min-w-0 space-y-1"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasAnyStatus): ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-chart-pie text-sky-500 mr-2"></i>ステータス分布</h2>
                        <div class="flex items-center gap-4">
                            <div class="shrink-0 w-28 h-28"><canvas id="statusChart"></canvas></div>
                            <div id="statusLegend" class="flex-1 min-w-0 space-y-1"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($seasonDistribution)): ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-sm font-bold text-slate-700 mb-4"><i class="fa-solid fa-calendar text-sky-500 mr-2"></i>季節分布</h2>
                        <div class="flex items-center gap-4">
                            <div class="shrink-0 w-28 h-28"><canvas id="seasonChart"></canvas></div>
                            <div id="seasonLegend" class="flex-1 min-w-0 space-y-1"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (empty($works)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 md:p-12 text-center text-slate-500">
                    <i class="fa-solid fa-tv text-4xl mb-3 text-slate-300"></i>
                    <p class="font-bold">まだ作品が登録されていません</p>
                    <p class="text-sm mt-2 max-w-md mx-auto">上の検索バーでタイトルを入力し、「見たい」「見てる」「見た」を追加すると、ここに表示されます。</p>
                </div>
                <?php endif; ?>

                <p class="text-xs text-slate-400 mt-4">アニメ作品データ提供: <a href="https://annict.com" target="_blank" rel="noopener" class="underline hover:text-slate-600">Annict</a></p>

                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php require_once __DIR__ . '/_anime_search_shared.php'; ?>
    <script>
        const FriendsAnimeRec = {
            async addStatus(workId, kind, btnEl) {
                btnEl.disabled = true;
                const origHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                try {
                    const result = await App.post('/anime/api/set_status.php', { work_id: workId, kind: kind });
                    if (result.status === 'success') {
                        const wrap = btnEl.closest('.absolute');
                        if (wrap) wrap.outerHTML = '<div class="absolute top-1.5 right-1.5 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                        App.toast({ wanna_watch: '見たい', watching: '見てる', watched: '見た' }[kind] + 'に追加しました');
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
            }
        };
        const AnimeGacha = {
            async spin() {
                const idle = document.getElementById('animeGachaIdle');
                const result = document.getElementById('animeGachaResult');
                const card = document.getElementById('animeGachaCard');
                if (idle) idle.classList.add('hidden');
                if (result) result.classList.remove('hidden');
                if (card) card.classList.add('spinning');
                try {
                    const res = await fetch('/anime/api/gacha.php');
                    const json = await res.json();
                    setTimeout(() => {
                        if (card) card.classList.remove('spinning');
                        if (json.status === 'success' && json.data) {
                            const w = json.data;
                            const posterEl = document.getElementById('animeGachaPoster');
                            const imgUrl = w.images?.recommended_url || w.images?.facebook?.og_image_url || '';
                            if (posterEl) {
                                if (imgUrl) {
                                    posterEl.outerHTML = '<img id="animeGachaPoster" src="' +
                                        imgUrl.replace(/^http:\/\//i, 'https://') +
                                        '" alt="" class="w-64 md:w-80 aspect-[16/9] object-cover rounded-xl shadow-2xl ring-2 ring-white/20">';
                                }
                                document.getElementById('animeGachaCard')?.classList.add('gacha-reveal');
                            }
                            const titleEl = document.getElementById('animeGachaTitle');
                            if (titleEl) titleEl.textContent = w.title || '';
                            const metaEl = document.getElementById('animeGachaMeta');
                            if (metaEl) {
                                let html = '';
                                if (w.season_name_text || w.season_name) html += '<span>' + (w.season_name_text || w.season_name) + '</span>';
                                if (w.episodes_count) html += '<span>' + w.episodes_count + '話</span>';
                                metaEl.innerHTML = html;
                            }
                            const detailLink = document.getElementById('animeGachaDetailLink');
                            if (detailLink) detailLink.href = '/anime/detail.php?id=' + (w.id || 0);
                        } else if (json.status === 'empty') {
                            if (typeof App !== 'undefined' && App.toast) App.toast('見たいリストが空です');
                        }
                    }, 700);
                } catch (e) {
                    if (card) card.classList.remove('spinning');
                    if (typeof App !== 'undefined' && App.toast) App.toast('エラーが発生しました');
                }
            }
        };
        const AnimeCurrentSeason = {
            data: [],
            esc(str) {
                const d = document.createElement('div');
                d.textContent = str || '';
                return d.innerHTML;
            },
            renderCard(w) {
                const imgUrl = (w.images && (w.images.recommended_url || (w.images.facebook && w.images.facebook.og_image_url))) || '';
                const safeImg = imgUrl ? this.esc(imgUrl.replace(/^http:\/\//i, 'https://')) : '';
                const title = this.esc(w.title || '');
                const episodes = w.episodes_count || null;
                const seasonText = w.season_name_text || w.season_name || '';
                return '<div class="rec-card cursor-pointer" data-anime-id="' + (w.id || 0) + '">' +
                    '<div class="aspect-[16/9] rounded-xl overflow-hidden bg-slate-100 mb-2">' +
                    (safeImg ? '<img src="' + safeImg + '" alt="" class="w-full h-full object-cover" loading="lazy" referrerpolicy="no-referrer">' :
                        '<div class="w-full h-full flex items-center justify-center"><i class="fa-solid fa-tv text-2xl text-slate-300"></i></div>') +
                    '</div>' +
                    '<p class="text-[11px] font-bold text-slate-700 line-clamp-2 leading-snug">' + title + '</p>' +
                    (seasonText || episodes ? '<p class="text-[10px] text-slate-400 mt-0.5">' +
                        (seasonText ? this.esc(seasonText) : '') +
                        (seasonText && episodes ? '・' : '') +
                        (episodes ? episodes + '話' : '') +
                        '</p>' : '') +
                '</div>';
            },
            updateArrows() {
                const scroll = document.getElementById('animeCurrentSeasonScroll');
                if (!scroll) return;
                const left = document.querySelector('#animeCurrentSeasonContainer .rec-arrow.left');
                const right = document.querySelector('#animeCurrentSeasonContainer .rec-arrow.right');
                if (!left || !right) return;
                const atStart = scroll.scrollLeft <= 4;
                const atEnd = scroll.scrollLeft + scroll.clientWidth >= scroll.scrollWidth - 4;
                left.classList.toggle('hidden', atStart);
                right.classList.toggle('hidden', atEnd);
            },
            scroll(dir) {
                const el = document.getElementById('animeCurrentSeasonScroll');
                if (!el) return;
                const card = el.querySelector('.rec-card');
                const width = card ? card.offsetWidth : 140;
                const amount = (width + 12) * 3 * dir;
                el.scrollBy({ left: amount, behavior: 'smooth' });
            },
            open(id) {
                if (!this.data || !Array.isArray(this.data)) return;
                var targetId = Number(id);
                var w = null;
                for (var i = 0; i < this.data.length; i++) {
                    var curId = Number(this.data[i].id || 0);
                    if (curId === targetId) {
                        w = this.data[i];
                        break;
                    }
                }
                if (!w) return;
                if (window.AnimePreview && typeof AnimePreview.open === 'function') {
                    AnimePreview.open(w);
                } else {
                    // フォールバックとして詳細ページへ
                    window.location.href = '/anime/detail.php?id=' + (w.id || 0);
                }
            },
            async init() {
                const labelEl = document.getElementById('animeCurrentSeasonLabel');
                const loadingEl = document.getElementById('animeCurrentSeasonLoading');
                const emptyEl = document.getElementById('animeCurrentSeasonEmpty');
                const scrollEl = document.getElementById('animeCurrentSeasonScroll');
                try {
                    const res = await fetch('/anime/api/current_season.php');
                    const json = await res.json();
                    if (json.status === 'success' && Array.isArray(json.data) && json.data.length > 0) {
                        this.data = json.data;
                        if (labelEl && json.season) {
                            labelEl.textContent = json.season;
                        }
                        if (scrollEl) {
                            scrollEl.innerHTML = json.data.map(w => this.renderCard(w)).join('');
                            scrollEl.classList.remove('hidden');
                            scrollEl.addEventListener('scroll', () => this.updateArrows(), { passive: true });
                            scrollEl.addEventListener('click', (e) => {
                                const card = e.target.closest('.rec-card');
                                if (!card) return;
                                const id = card.getAttribute('data-anime-id');
                                AnimeCurrentSeason.open(id);
                            });
                            this.updateArrows();
                        }
                        if (loadingEl) loadingEl.classList.add('hidden');
                    } else {
                        if (loadingEl) loadingEl.classList.add('hidden');
                        if (emptyEl) emptyEl.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error(e);
                    if (loadingEl) loadingEl.classList.add('hidden');
                    if (emptyEl) emptyEl.classList.remove('hidden');
                }
            }
        };

        AnimeCurrentSeason.init();

        // 視聴中カード用モーダルフック
        (function() {
            const watchingData = <?= json_encode(array_values(array_slice($watchingWorks, 0, 12))) ?>;
            const map = {};
            for (let i = 0; i < watchingData.length; i++) {
                const w = watchingData[i];
                if (w && w.id) {
                    map[String(w.id)] = w;
                }
            }
            const scroll = document.getElementById('watchingScroll');
            if (!scroll) return;
            scroll.addEventListener('click', function(e) {
                const card = e.target.closest('.rec-card');
                if (!card) return;
                const id = card.getAttribute('data-anime-id');
                if (!id) return;
                const w = map[String(id)];
                if (w && window.AnimePreview && typeof AnimePreview.open === 'function') {
                    AnimePreview.open(w);
                } else {
                    window.location.href = '/anime/detail.php?id=' + encodeURIComponent(id);
                }
            });
        })();

        AnimeSearch.init({
            inputId: 'dashSearchInput',
            resultsId: 'dashSearchResults',
            wrapperId: 'dashSearchWrapper'
        });
        (function() {
            const revokeBtn = document.getElementById('annictRevokeBtn');
            if (revokeBtn) {
                revokeBtn.addEventListener('click', async function() {
                    if (!confirm('Annict 連携を解除しますか？ 検索や視聴状況の同期ができなくなります。')) return;
                    revokeBtn.disabled = true;
                    try {
                        const res = await App.post('/anime/api/revoke.php', {});
                        if (res.status === 'success') {
                            location.reload();
                        } else {
                            App.toast(res.message || '解除に失敗しました');
                            revokeBtn.disabled = false;
                        }
                    } catch (e) {
                        App.toast('エラーが発生しました');
                        revokeBtn.disabled = false;
                    }
                });
            }
        })();

        <?php if ($hasCharts ?? false): ?>
        (function() {
            const themeHex = '<?= addslashes($themeHex ?? '#0ea5e9') ?>';
            const palette = ['#0ea5e9', '#10b981', '#f59e0b', '#6366f1', '#ef4444', '#ec4899', '#06b6d4', '#84cc16', '#94a3b8'];
            Chart.defaults.font.family = "'Inter', 'Noto Sans JP', sans-serif";
            Chart.defaults.font.size = 11;

            function makeDoughnut(ctxId, legendId, labels, data, colors) {
                const ctx = document.getElementById(ctxId);
                const legend = document.getElementById(legendId);
                if (!ctx || labels.length === 0) return;
                const total = data.reduce((a, b) => a + b, 0);
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors.slice(0, data.length),
                            borderWidth: 2,
                            borderColor: '#fff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '55%',
                        plugins: { legend: { display: false } },
                    }
                });
                if (legend) {
                    legend.innerHTML = labels.map((l, i) => {
                        const pct = total > 0 ? ((data[i] / total) * 100).toFixed(0) : 0;
                        return '<div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:' + colors[i] + '"></span><span class="text-xs text-slate-600 truncate flex-1">' + (l || '') + '</span><span class="text-[11px] font-bold text-slate-500">' + pct + '%</span></div>';
                    }).join('');
                }
            }

            <?php if (!empty($mediaDistribution)): ?>
            makeDoughnut('mediaChart', 'mediaLegend',
                <?= json_encode(array_keys($mediaDistribution)) ?>,
                <?= json_encode(array_values($mediaDistribution)) ?>,
                palette
            );
            <?php endif; ?>
            <?php if ($hasAnyStatus): ?>
            (function() {
                const statusLabels = <?= json_encode($statusLabels) ?>;
                const stats = <?= json_encode($stats) ?>;
                const labels = [];
                const data = [];
                const statusOrder = ['watched', 'watching', 'wanna_watch', 'on_hold', 'stop_watching'];
                statusOrder.forEach(k => {
                    if ((stats[k] ?? 0) > 0) {
                        labels.push(statusLabels[k] || k);
                        data.push(stats[k]);
                    }
                });
                makeDoughnut('statusChart', 'statusLegend', labels, data, ['#10b981', '#0ea5e9', '#f59e0b', '#94a3b8', '#64748b']);
            })();
            <?php endif; ?>
            <?php if (!empty($seasonDistribution)): ?>
            makeDoughnut('seasonChart', 'seasonLegend',
                <?= json_encode(array_keys($seasonDistribution)) ?>,
                <?= json_encode(array_values($seasonDistribution)) ?>,
                palette
            );
            <?php endif; ?>
        })();
        <?php endif; ?>
    </script>
</body>
</html>
