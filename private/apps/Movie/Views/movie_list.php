<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$viewMode = $_GET['view'] ?? ($_COOKIE['mv_view_mode'] ?? 'grid');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画リスト - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --mv-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .mv-theme-btn { background-color: var(--mv-theme); }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        .mv-theme-text { color: var(--mv-theme); }
        .mv-theme-border { border-color: var(--mv-theme); }
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

        .movie-card { transition: all 0.2s ease; }
        .movie-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .poster-placeholder { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); }

        .tab-btn { position: relative; padding-bottom: 0.75rem; }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--mv-theme);
            border-radius: 3px 3px 0 0;
        }

        .search-overlay { opacity: 0; pointer-events: none; transition: opacity 0.25s ease; }
        .search-overlay.active { opacity: 1; pointer-events: auto; }

        .star-rating .star { cursor: pointer; transition: color 0.15s; }
        .star-rating .star:hover, .star-rating .star.filled { color: #f59e0b; }

        .movie-card .card-watched-btn { opacity: 0; transition: opacity 0.2s; }
        .movie-card:hover .card-watched-btn { opacity: 1; }

        .list-row { transition: background-color 0.15s; }
        .list-row:hover { background-color: #f8fafc; }

        .view-toggle-btn.active { background-color: var(--mv-theme); color: #fff; }

        .inline-search-panel { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, opacity 0.25s ease; opacity: 0; }
        .inline-search-panel.open { max-height: 200px; opacity: 1; overflow: visible; }
        @media (min-width: 768px) { .inline-search-panel { max-height: none !important; opacity: 1 !important; overflow: visible !important; } }
        .inline-results { box-shadow: 0 12px 36px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04); }
        .inline-result-row { transition: background-color 0.12s; }
        .inline-result-row:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <!-- ヘッダー -->
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-film text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">映画リスト</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/movie/bulk_edit.php?tab=<?= htmlspecialchars($tab) ?>" class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition" title="一括編集">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span class="hidden sm:inline">一括編集</span>
                </a>
                <a href="/movie/import.php?status=<?= htmlspecialchars($tab) ?>" class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition" title="一括登録">
                    <i class="fa-solid fa-file-import"></i>
                    <span class="hidden sm:inline">一括登録</span>
                </a>
                <?php if ($tmdbConfigured): ?>
                <button onclick="SearchModal.open()" class="flex items-center gap-2 px-4 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span class="hidden sm:inline">映画を検索</span>
                </button>
                <?php else: ?>
                <div class="text-xs text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> TMDB APIキー未設定                </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- タブバー（固定）-->
        <div class="bg-white/90 backdrop-blur-sm border-b border-slate-200 px-6 md:px-12 py-2 shrink-0 z-[5]">
            <div class="max-w-7xl mx-auto flex items-center gap-6">
                <button class="tab-btn text-sm font-bold py-2 <?= $tab === 'watchlist' ? 'active mv-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="location.href='?tab=watchlist'">
                    <i class="fa-solid fa-bookmark mr-1.5"></i>見たい                    <span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= $watchlistCount ?></span>
                </button>
                <button class="tab-btn text-sm font-bold py-2 <?= $tab === 'watched' ? 'active mv-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="location.href='?tab=watched'">
                    <i class="fa-solid fa-check-circle mr-1.5"></i>見た
                    <span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= $watchedCount ?></span>
                </button>

                <div class="ml-auto flex items-center gap-2">
                    <!-- 表示分崛 -->
                    <div class="flex items-center bg-slate-100 rounded-lg p-0.5">
                        <button onclick="setViewMode('grid')" class="view-toggle-btn w-8 h-8 rounded-md flex items-center justify-center text-sm transition <?= $viewMode === 'grid' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>" title="ブロック表示">
                            <i class="fa-solid fa-table-cells"></i>
                        </button>
                        <button onclick="setViewMode('list')" class="view-toggle-btn w-8 h-8 rounded-md flex items-center justify-center text-sm transition <?= $viewMode === 'list' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>" title="一覧表示">
                            <i class="fa-solid fa-list"></i>
                        </button>
                    </div>
                    <select onchange="applySortOrder(this.value)" class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-500 focus:outline-none focus:ring-1 focus:ring-[var(--mv-theme)]">
                        <option value="created_at-DESC" <?= ($sort === 'created_at' && $order === 'DESC') ? 'selected' : '' ?>>追加日 (新しい順)</option>
                        <option value="created_at-ASC" <?= ($sort === 'created_at' && $order === 'ASC') ? 'selected' : '' ?>>追加日 (古い順)</option>
                        <option value="release_date-DESC" <?= ($sort === 'release_date' && $order === 'DESC') ? 'selected' : '' ?>>公開日 (新しい順)</option>
                        <option value="title-ASC" <?= ($sort === 'title' && $order === 'ASC') ? 'selected' : '' ?>>タイトル (A→Z)</option>
                        <?php if ($tab === 'watched'): ?>
                        <option value="rating-DESC" <?= ($sort === 'rating' && $order === 'DESC') ? 'selected' : '' ?>>評価 (高い順)</option>
                        <option value="watched_date-DESC" <?= ($sort === 'watched_date' && $order === 'DESC') ? 'selected' : '' ?>>視聴日 (新しい順)</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- インライン検索バー -->
        <?php if ($tmdbConfigured): ?>
        <div class="bg-white/90 backdrop-blur-sm border-b border-slate-200 shrink-0 z-[8] relative" id="inlineSearchArea">
            <!-- モバイル: トグルボタン -->
            <button onclick="InlineSearch.toggle()" id="inlineSearchToggle"
                    class="md:hidden w-full flex items-center gap-2 px-6 py-2.5 text-sm text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>映画を検索して追加...</span>
                <i class="fa-solid fa-chevron-down text-[10px] ml-auto transition-transform duration-300" id="inlineSearchChevron"></i>
            </button>
            <!-- 検索入力 (PC: 常時表示, モバイル: 折りたたみ) -->
            <div id="inlineSearchContent" class="hidden md:block inline-search-panel md:!max-h-none md:!opacity-100">
                <div class="max-w-7xl mx-auto px-6 md:px-12 py-2.5 md:py-3">
                    <div class="relative" id="inlineSearchWrapper">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" id="inlineSearchInput"
                                       placeholder="映画タイトルで検索して追加..."
                                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] focus:border-transparent"
                                       onkeydown="if(event.key==='Enter') InlineSearch.search()"
                                       autocomplete="off">
                            </div>
                            <button onclick="InlineSearch.search()" class="px-4 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg transition shrink-0">
                                検索
                            </button>
                        </div>
                        <!-- 検索結果ドロップダウン -->
                        <div id="inlineSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-white rounded-xl inline-results max-h-[60vh] overflow-y-auto z-50">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- コンテンツ（スクロール領域）-->
        <div class="flex-1 overflow-y-auto" data-scroll-persist="movie-list">
            <div class="max-w-7xl mx-auto px-6 md:px-12 py-6">

                <!-- 映画一覧 -->
                <?php if (empty($movies)): ?>
                <div class="text-center py-20">
                    <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-film text-4xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">
                        <?= $tab === 'watchlist' ? 'まだ見たい映画がありません' : 'まだ見た映画がありません' ?>
                    </h3>
                    <p class="text-sm text-slate-400 mb-6">映画を検索してリストに追加しましょう</p>
                    <?php if ($tmdbConfigured): ?>
                    <button onclick="SearchModal.open()" class="px-6 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                        <i class="fa-solid fa-magnifying-glass mr-2"></i>映画を検索
                    </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>

                <!-- ④ グリッド表示 -->
                <div id="viewGrid" class="<?= $viewMode === 'grid' ? '' : 'hidden' ?>">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        <?php foreach ($movies as $mv): ?>
                        <div class="movie-card bg-white rounded-xl overflow-hidden shadow-sm border border-slate-100 cursor-pointer relative"
                             onclick="goDetail(<?= $mv['id'] ?>)">
                            <div class="aspect-[2/3] relative overflow-hidden">
                                <?php if (!empty($mv['poster_path'])): ?>
                                <img src="https://image.tmdb.org/t/p/w342<?= htmlspecialchars($mv['poster_path']) ?>"
                                     alt="<?= htmlspecialchars($mv['title']) ?>"
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="w-full h-full poster-placeholder flex items-center justify-center">
                                    <i class="fa-solid fa-film text-4xl text-slate-400"></i>
                                </div>
                                <?php endif; ?>

                                <?php if ($tab === 'watched' && $mv['rating']): ?>
                                <div class="absolute top-2 right-2 bg-black/70 text-amber-400 text-xs font-bold px-2 py-1 rounded-lg flex items-center gap-1">
                                    <i class="fa-solid fa-star text-[10px]"></i> <?= $mv['rating'] ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($mv['vote_average'] && $mv['vote_average'] > 0): ?>
                                <div class="absolute bottom-2 left-2 bg-black/70 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
                                    <i class="fa-solid fa-star text-amber-400 mr-0.5"></i><?= number_format($mv['vote_average'], 1) ?>
                                </div>
                                <?php endif; ?>

                                <!-- ⑤ 見たいタブ 「見た」ボタン -->
                                <?php if ($tab === 'watchlist'): ?>
                                <button onclick="event.stopPropagation(); WatchedModal.open(<?= $mv['id'] ?>)"
                                        class="card-watched-btn absolute top-2 right-2 bg-white/90 hover:bg-green-500 hover:text-white text-green-600 text-xs font-bold px-2.5 py-1.5 rounded-lg shadow-lg transition backdrop-blur-sm"
                                        title="見た映画に変更">
                                    <i class="fa-solid fa-check mr-1"></i>見た
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="p-3">
                                <h3 class="text-sm font-bold text-slate-800 line-clamp-2 leading-snug mb-1"><?= htmlspecialchars($mv['title']) ?></h3>
                                <?php if (!empty($mv['release_date'])): ?>
                                <p class="text-[11px] text-slate-400"><?= date('Y年', strtotime($mv['release_date'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ④ リスト表示 -->
                <div id="viewList" class="<?= $viewMode === 'list' ? '' : 'hidden' ?>">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-100">
                        <?php foreach ($movies as $mv): ?>
                        <div class="list-row flex items-center gap-4 px-4 py-3 cursor-pointer"
                             onclick="goDetail(<?= $mv['id'] ?>)">
                            <!-- ポスター（小）-->
                            <?php if (!empty($mv['poster_path'])): ?>
                            <img src="https://image.tmdb.org/t/p/w92<?= htmlspecialchars($mv['poster_path']) ?>"
                                 alt="<?= htmlspecialchars($mv['title']) ?>"
                                 class="w-10 h-[60px] object-cover rounded-lg shrink-0 shadow-sm"
                                 loading="lazy">
                            <?php else: ?>
                            <div class="w-10 h-[60px] poster-placeholder rounded-lg flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-film text-slate-400 text-sm"></i>
                            </div>
                            <?php endif; ?>

                            <!-- 情報 -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($mv['title']) ?></h3>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                    <?php if (!empty($mv['release_date'])): ?>
                                    <span><?= date('Y年', strtotime($mv['release_date'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($mv['vote_average'] && $mv['vote_average'] > 0): ?>
                                    <span class="text-amber-500"><i class="fa-solid fa-star text-[9px]"></i> <?= number_format($mv['vote_average'], 1) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($mv['genres'])):
                                        $g = json_decode($mv['genres'], true);
                                        if (is_array($g) && count($g) > 0): ?>
                                    <span class="text-slate-300">|</span>
                                    <span class="truncate"><?= htmlspecialchars(implode(', ', array_slice(array_filter($g, 'is_string'), 0, 3))) ?></span>
                                    <?php endif; endif; ?>
                                </div>
                            </div>

                            <!-- 評価・アクション -->
                            <div class="shrink-0 flex items-center gap-3">
                                <?php if ($tab === 'watched' && $mv['rating']): ?>
                                <div class="text-amber-400 text-sm font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-star text-xs"></i><?= $mv['rating'] ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($tab === 'watched' && $mv['watched_date']): ?>
                                <span class="text-[11px] text-slate-400 hidden sm:inline"><?= date('Y/m/d', strtotime($mv['watched_date'])) ?></span>
                                <?php endif; ?>

                                <!-- ⑤ 見たいタブ 「見た」ボタン -->
                                <?php if ($tab === 'watchlist'): ?>
                                <button onclick="event.stopPropagation(); WatchedModal.open(<?= $mv['id'] ?>)"
                                        class="text-xs font-bold text-green-600 bg-green-50 hover:bg-green-500 hover:text-white px-3 py-1.5 rounded-lg transition"
                                        title="見た映画に変更">
                                    <i class="fa-solid fa-check mr-1"></i>見た
                                </button>
                                <?php endif; ?>

                                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- TMDB検索モーダル -->
    <div id="searchOverlay" class="search-overlay fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-[10vh]" onclick="SearchModal.close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[80vh] flex flex-col" onclick="event.stopPropagation()">
            <div class="p-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="flex-1 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="searchInput" placeholder="映画タイトルを入力..."
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] focus:border-transparent"
                               onkeydown="if(event.key==='Enter') SearchModal.search()">
                    </div>
                    <button onclick="SearchModal.search()" class="px-5 py-3 mv-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0">
                        検索
                    </button>
                    <button onclick="SearchModal.close()" class="p-3 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>
            <div id="searchResults" class="flex-1 overflow-y-auto p-4">
                <div class="text-center py-12 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-3xl mb-3"></i>
                    <p class="text-sm">TMDBから映画を検索できまい</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 「見た」に変更モーダル -->
    <div id="watchedModal" class="search-overlay fixed inset-0 bg-black/50 z-[60] flex items-center justify-center" onclick="WatchedModal.close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-check-circle mv-theme-text mr-2"></i>見た映画に登録</h3>
            <input type="hidden" id="watchedMovieId">
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">視聴日 <span class="text-slate-400 font-normal">（任意）</span></label>
                    <input type="date" id="watchedDate" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-2 block">評価</label>
                    <div class="star-rating flex gap-1" id="watchedRating">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <span class="star text-slate-300 text-xl" data-value="<?= $i ?>" onclick="WatchedModal.setRating(<?= $i ?>)">
                            <i class="fa-solid fa-star"></i>
                        </span>
                        <?php endfor; ?>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">タップで評価 (1-10)</p>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">メモ・感想</label>
                    <textarea id="watchedMemo" rows="3" placeholder="感想をメモ..."
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button onclick="WatchedModal.close()" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">キャンセル</button>
                <button onclick="WatchedModal.save()" class="flex-1 px-4 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-lg transition">登録</button>
            </div>
        </div>
    </div>

    <!-- 画像プレビューモーダル -->
    <div id="posterPreview" class="fixed inset-0 bg-black/80 z-[70] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); PosterPreview.close()">
        <button class="absolute top-4 right-4 text-white/70 hover:text-white transition text-2xl" onclick="PosterPreview.close()">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <img id="posterPreviewImg" src="" alt=""
             class="max-w-[90vw] max-h-[85vh] rounded-xl shadow-2xl object-contain transition-transform duration-200 scale-95"
             onclick="event.stopPropagation()">
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const currentTab = '<?= htmlspecialchars($tab) ?>';

        function goDetail(id) {
            App.saveListParams('/movie/');
            location.href = '/movie/detail.php?id=' + id;
        }

        function applySortOrder(val) {
            const [sort, order] = val.split('-');
            const url = new URL(location.href);
            url.searchParams.set('tab', currentTab);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
            location.href = url.toString();
        }

        function setViewMode(mode) {
            document.cookie = `mv_view_mode=${mode};path=/;max-age=${365*86400}`;
            document.getElementById('viewGrid').classList.toggle('hidden', mode !== 'grid');
            document.getElementById('viewList').classList.toggle('hidden', mode !== 'list');
            document.querySelectorAll('.view-toggle-btn').forEach((btn, i) => {
                const isActive = (i === 0 && mode === 'grid') || (i === 1 && mode === 'list');
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('text-slate-400', !isActive);
            });
        }

        const SearchModal = {
            open() {
                document.getElementById('searchOverlay').classList.add('active');
                setTimeout(() => document.getElementById('searchInput').focus(), 100);
            },
            close() {
                document.getElementById('searchOverlay').classList.remove('active');
            },
            async search() {
                const query = document.getElementById('searchInput').value.trim();
                if (!query) return;

                const container = document.getElementById('searchResults');
                container.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-slate-300"></i></div>';

                try {
                    const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(query)}`);
                    const json = await res.json();

                    const manualHtml = this.renderManualAdd(query);

                    if (json.status !== 'success') {
                        container.innerHTML = `<div class="text-center py-8 text-red-500 text-sm">${json.message}</div>` + manualHtml;
                        return;
                    }

                    const movies = json.data.results || [];
                    if (movies.length === 0) {
                        container.innerHTML = '<div class="text-center py-6 text-slate-400 text-sm">TMDBで見つかりませんでした</div>' + manualHtml;
                        return;
                    }

                    container.innerHTML = movies.map(m => this.renderResult(m)).join('') + manualHtml;
                } catch (e) {
                    console.error(e);
                    container.innerHTML = '<div class="text-center py-8 text-red-500 text-sm">検索中にエラーが発生しました</div>';
                }
            },
            renderManualAdd(query) {
                const escaped = this.escapeHtml(query);
                return `
                <div class="border-t border-dashed border-slate-200 p-4 bg-slate-50/50">
                    <p class="text-[11px] text-slate-400 mb-2"><i class="fa-solid fa-pen mr-1"></i>TMDBに無い場合、タイトルだけで追加できます</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-700 flex-1 truncate">「${escaped}」</span>
                        <button onclick="SearchModal.addManual('watchlist')" class="text-xs font-bold text-white px-3 py-1.5 rounded-lg mv-theme-btn transition whitespace-nowrap">
                            <i class="fa-solid fa-bookmark mr-1"></i>見たいに追加
                        </button>
                        <button onclick="SearchModal.addManual('watched')" class="text-xs font-bold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap">
                            <i class="fa-solid fa-check mr-1"></i>見たに追加
                        </button>
                    </div>
                </div>`;
            },
            async addManual(status) {
                const query = document.getElementById('searchInput').value.trim();
                if (!query) return;
                try {
                    const result = await App.post('/movie/api/add_manual.php', { title: query, status: status });
                    if (result.status === 'success') {
                        App.toast(result.message);
                        this.close();
                        if (status === currentTab) {
                            location.reload();
                        }
                    } else {
                        App.toast(result.message || '追加に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            },
            renderResult(m) {
                const poster = m.poster_path
                    ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-16 h-24 object-cover rounded-lg shrink-0 cursor-pointer hover:brightness-90 transition" loading="lazy" onclick="event.stopPropagation(); PosterPreview.open('${m.poster_path}')">`
                    : `<div class="w-16 h-24 poster-placeholder rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-film text-slate-400"></i></div>`;
                const year = m.release_date ? m.release_date.substring(0, 4) + '年' : '';
                const rating = m.vote_average ? `<span class="text-amber-500"><i class="fa-solid fa-star text-[10px]"></i> ${m.vote_average.toFixed(1)}</span>` : '';
                const overview = m.overview ? m.overview.substring(0, 80) + (m.overview.length > 80 ? '...' : '') : '';

                let actionBtn = '';
                if (m.user_status === 'watchlist') {
                    actionBtn = `<span class="text-xs font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-bookmark mr-1"></i>見たい登録済</span>`;
                } else if (m.user_status === 'watched') {
                    actionBtn = `<span class="text-xs font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-check mr-1"></i>見た済</span>`;
                } else {
                    actionBtn = `
                        <button onclick="event.stopPropagation(); SearchModal.addMovie(${m.id}, 'watchlist', this)" class="text-xs font-bold text-white px-2.5 py-1.5 rounded-lg mv-theme-btn transition" title="見たいリストに追加">
                            <i class="fa-solid fa-bookmark mr-1"></i>見たい                        </button>
                        <button onclick="event.stopPropagation(); SearchModal.addMovie(${m.id}, 'watched', this)" class="text-xs font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition" title="見たリストに追加">
                            <i class="fa-solid fa-check mr-1"></i>見た
                        </button>`;
                }

                return `
                <div class="flex gap-3 p-3 rounded-xl hover:bg-slate-50 transition" data-tmdb-id="${m.id}">
                    ${poster}
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-800 line-clamp-1">${this.escapeHtml(m.title)}</h4>
                        <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                            <span>${year}</span>
                            ${rating}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">${this.escapeHtml(overview)}</p>
                        <div class="flex items-center gap-2 mt-2">${actionBtn}</div>
                    </div>
                </div>`;
            },
            async addMovie(tmdbId, status, btn) {
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    const result = await App.post('/movie/api/add.php', {
                        tmdb_id: tmdbId,
                        status: status,
                    });

                    if (result.status === 'success') {
                        App.toast(result.message);
                        if (status === currentTab) {
                            location.reload();
                            return;
                        }
                        const row = btn.closest('[data-tmdb-id]');
                        const actionDiv = row.querySelector('.flex.items-center.gap-2:last-child');
                        if (status === 'watchlist') {
                            actionDiv.innerHTML = '<span class="text-xs font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-bookmark mr-1"></i>見たい登録済</span>';
                        } else {
                            actionDiv.innerHTML = '<span class="text-xs font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-check mr-1"></i>見た済</span>';
                        }
                    } else {
                        App.toast(result.message || '追加に失敗しました');
                        btn.disabled = false;
                        btn.innerHTML = status === 'watchlist'
                            ? '<i class="fa-solid fa-bookmark mr-1"></i>見たい'
                            : '<i class="fa-solid fa-check mr-1"></i>見た';
                    }
                } catch (e) {
                    console.error(e);
                    btn.disabled = false;
                }
            },
            escapeHtml(str) {
                if (!str) return '';
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        };

        const WatchedModal = {
            rating: 0,
            open(movieId) {
                document.getElementById('watchedMovieId').value = movieId;
                document.getElementById('watchedDate').value = '';
                document.getElementById('watchedMemo').value = '';
                this.setRating(0);
                document.getElementById('watchedModal').classList.add('active');
            },
            close() {
                document.getElementById('watchedModal').classList.remove('active');
            },
            setRating(val) {
                this.rating = val;
                document.querySelectorAll('#watchedRating .star').forEach(s => {
                    const v = parseInt(s.dataset.value);
                    s.classList.toggle('filled', v <= val);
                    s.classList.toggle('text-slate-300', v > val);
                });
            },
            async save() {
                const id = document.getElementById('watchedMovieId').value;
                const data = {
                    id: parseInt(id),
                    status: 'watched',
                    watched_date: document.getElementById('watchedDate').value || null,
                    rating: this.rating || null,
                    memo: document.getElementById('watchedMemo').value.trim() || null,
                };

                const result = await App.post('/movie/api/update.php', data);
                if (result.status === 'success') {
                    App.toast('見たリストに移動しました');
                    this.close();
                    location.reload();
                } else {
                    App.toast(result.message || '更新に失敗しました');
                }
            }
        };

        const InlineSearch = {
            isOpen: false,

            toggle() {
                this.isOpen = !this.isOpen;
                const content = document.getElementById('inlineSearchContent');
                const chevron = document.getElementById('inlineSearchChevron');
                if (this.isOpen) {
                    content.classList.remove('hidden');
                    requestAnimationFrame(() => content.classList.add('open'));
                    chevron.style.transform = 'rotate(180deg)';
                    setTimeout(() => document.getElementById('inlineSearchInput').focus(), 150);
                } else {
                    content.classList.remove('open');
                    chevron.style.transform = '';
                    this.closeResults();
                    setTimeout(() => content.classList.add('hidden'), 300);
                }
            },

            async search() {
                const query = document.getElementById('inlineSearchInput').value.trim();
                if (!query) return;

                const container = document.getElementById('inlineSearchResults');
                container.classList.remove('hidden');
                container.innerHTML = '<div class="text-center py-6"><i class="fa-solid fa-spinner fa-spin text-xl text-slate-300"></i></div>';

                try {
                    const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(query)}`);
                    const json = await res.json();

                    const manualAddHtml = this.renderManualAdd(query);

                    if (json.status !== 'success') {
                        container.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">${json.message}</div>` + manualAddHtml;
                        return;
                    }

                    const movies = json.data.results || [];
                    if (movies.length === 0) {
                        container.innerHTML = '<div class="text-center py-4 text-slate-400 text-sm">TMDBで見つかりませんでした</div>' + manualAddHtml;
                        return;
                    }

                    container.innerHTML = movies.slice(0, 10).map(m => this.renderResult(m)).join('') + manualAddHtml;
                } catch (e) {
                    console.error(e);
                    container.innerHTML = '<div class="text-center py-6 text-red-500 text-sm">エラーが発生しました</div>';
                }
            },

            renderManualAdd(query) {
                const escaped = SearchModal.escapeHtml(query);
                return `
                <div class="border-t border-dashed border-slate-200 px-4 py-3 bg-slate-50/50 rounded-b-xl">
                    <p class="text-[11px] text-slate-400 mb-2"><i class="fa-solid fa-pen mr-1"></i>TMDBに無い場合、タイトルだけで追加できます</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-700 flex-1 truncate">「${escaped}」</span>
                        <button onclick="InlineSearch.addManual('watchlist')" class="text-[11px] font-bold text-white px-2.5 py-1.5 rounded-lg mv-theme-btn transition whitespace-nowrap">
                            <i class="fa-solid fa-bookmark mr-0.5"></i>見たいに追加
                        </button>
                        <button onclick="InlineSearch.addManual('watched')" class="text-[11px] font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap">
                            <i class="fa-solid fa-check mr-0.5"></i>見たに追加
                        </button>
                    </div>
                </div>`;
            },

            async addManual(status) {
                const query = document.getElementById('inlineSearchInput').value.trim();
                if (!query) return;

                try {
                    const result = await App.post('/movie/api/add_manual.php', { title: query, status: status });
                    if (result.status === 'success') {
                        App.toast(result.message);
                        if (status === currentTab) {
                            location.reload();
                            return;
                        }
                        this.closeResults();
                        document.getElementById('inlineSearchInput').value = '';
                        this.updateTabCount(status, 1);
                    } else {
                        App.toast(result.message || '追加に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            },

            renderResult(m) {
                const poster = m.poster_path
                    ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-10 h-[60px] object-cover rounded-lg shrink-0 cursor-pointer hover:brightness-90 transition" loading="lazy" onclick="event.stopPropagation(); PosterPreview.open('${m.poster_path}')">`
                    : `<div class="w-10 h-[60px] poster-placeholder rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-film text-slate-400 text-xs"></i></div>`;
                const year = m.release_date ? m.release_date.substring(0, 4) + '年' : '';
                const rating = m.vote_average ? `<i class="fa-solid fa-star text-amber-400 text-[9px]"></i> ${m.vote_average.toFixed(1)}` : '';

                let actionHtml = '';
                if (m.user_status === 'watchlist') {
                    actionHtml = '<span class="text-[11px] font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい済</span>';
                } else if (m.user_status === 'watched') {
                    actionHtml = '<span class="text-[11px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見た済</span>';
                } else {
                    actionHtml = `
                        <button onclick="event.stopPropagation(); InlineSearch.addMovie(${m.id}, 'watchlist', this)" class="text-[11px] font-bold text-white px-2.5 py-1.5 rounded-lg mv-theme-btn transition whitespace-nowrap">
                            <i class="fa-solid fa-bookmark mr-0.5"></i>見たい
                        </button>
                        <button onclick="event.stopPropagation(); InlineSearch.addMovie(${m.id}, 'watched', this)" class="text-[11px] font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap">
                            <i class="fa-solid fa-check mr-0.5"></i>見た
                        </button>`;
                }

                return `
                <div class="inline-result-row flex items-center gap-3 px-4 py-2.5 border-b border-slate-100 last:border-b-0" data-tmdb-id="${m.id}">
                    ${poster}
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-800 line-clamp-1">${SearchModal.escapeHtml(m.title)}</h4>
                        <div class="flex items-center gap-2 text-[11px] text-slate-400">${year} ${rating}</div>
                    </div>
                    <div class="shrink-0 flex items-center gap-1.5">${actionHtml}</div>
                </div>`;
            },

            async addMovie(tmdbId, status, btn) {
                try {
                    btn.disabled = true;
                    const origHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    const result = await App.post('/movie/api/add.php', {
                        tmdb_id: tmdbId,
                        status: status,
                    });

                    if (result.status === 'success') {
                        App.toast(result.message);
                        if (status === currentTab) {
                            location.reload();
                            return;
                        }
                        const row = btn.closest('[data-tmdb-id]');
                        const actionDiv = row.querySelector('.shrink-0.flex');
                        if (status === 'watchlist') {
                            actionDiv.innerHTML = '<span class="text-[11px] font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい済</span>';
                        } else {
                            actionDiv.innerHTML = '<span class="text-[11px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見た済</span>';
                        }
                        InlineSearch.updateTabCount(status, 1);
                    } else {
                        App.toast(result.message || '追加に失敗しました');
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                    }
                } catch (e) {
                    console.error(e);
                    btn.disabled = false;
                }
            },

            updateTabCount(status, delta) {
                const tabs = document.querySelectorAll('.tab-btn');
                const idx = status === 'watchlist' ? 0 : 1;
                const badge = tabs[idx]?.querySelector('.rounded-full');
                if (badge) {
                    const current = parseInt(badge.textContent) || 0;
                    badge.textContent = current + delta;
                }
            },

            closeResults() {
                const el = document.getElementById('inlineSearchResults');
                if (el) el.classList.add('hidden');
            }
        };

        const PosterPreview = {
            open(posterPath) {
                const img = document.getElementById('posterPreviewImg');
                img.src = 'https://image.tmdb.org/t/p/w500' + posterPath;
                const el = document.getElementById('posterPreview');
                el.classList.remove('pointer-events-none', 'opacity-0');
                el.classList.add('pointer-events-auto', 'opacity-100');
                img.classList.remove('scale-95');
                img.classList.add('scale-100');
            },
            close() {
                const el = document.getElementById('posterPreview');
                const img = document.getElementById('posterPreviewImg');
                el.classList.add('opacity-0');
                el.classList.remove('opacity-100');
                img.classList.add('scale-95');
                img.classList.remove('scale-100');
                setTimeout(() => {
                    el.classList.add('pointer-events-none');
                    el.classList.remove('pointer-events-auto');
                    img.src = '';
                }, 200);
            }
        };

        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('inlineSearchWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                InlineSearch.closeResults();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                PosterPreview.close();
                SearchModal.close();
                WatchedModal.close();
                InlineSearch.closeResults();
            }
        });
    </script>
</body>
</html>
