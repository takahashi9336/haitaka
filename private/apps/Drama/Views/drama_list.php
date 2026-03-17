<?php
$appKey = 'drama';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$viewMode = $_GET['view'] ?? ($_COOKIE['dr_view_mode'] ?? 'grid');
// controller から $tab / $category が渡ってくる前提だが、直アクセス時の安全策としてフォールバック
if (!isset($tab)) {
    $tab = $_GET['tab'] ?? 'wanna_watch';
}
if (!isset($category)) {
    $category = $_GET['category'] ?? 'all';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アニメ/ドラマリスト - MyPlatform</title>
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

        .dr-card { transition: all 0.2s ease; }
        .dr-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .poster-placeholder { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); }

        .tab-btn { position: relative; padding-bottom: 0.75rem; }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--dr-theme);
            border-radius: 3px 3px 0 0;
        }

        .list-row { transition: background-color 0.15s; }
        .list-row:hover { background-color: #f8fafc; }

        .view-toggle-btn.active { background-color: var(--dr-theme); color: #fff; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <!-- ヘッダー -->
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/drama/" class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?> hover:brightness-110 transition"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?> title="アニメ/ドラマダッシュボード">
                    <i class="fa-solid fa-masks-theater text-sm"></i>
                </a>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">アニメ/ドラマ</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/drama/import.php" class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition" title="一括登録">
                    <i class="fa-solid fa-file-import"></i>
                    <span class="hidden sm:inline">一括登録</span>
                </a>
                <?php if ($tmdbConfigured): ?>
                    <button onclick="DrSearchModal.open()" class="flex items-center gap-2 px-4 py-2 dr-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-plus"></i>
                        <span class="hidden sm:inline">作品を追加</span>
                </button>
                <?php else: ?>
                <div class="text-xs text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> TMDB APIキー未設定
                </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- タブバー -->
        <div class="bg-white/90 backdrop-blur-sm border-b border-slate-200 px-4 md:px-12 py-2 shrink-0 z-[5]">
            <div class="max-w-7xl mx-auto flex flex-nowrap items-center gap-3 md:gap-6">
                <button class="tab-btn text-sm font-bold py-2 whitespace-nowrap <?= $tab === 'wanna_watch' ? 'active dr-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="changeDrTab('wanna_watch')">
                    <i class="fa-solid fa-bookmark mr-1"></i>見たい<span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= (int)($wannaWatchCount ?? 0) ?></span>
                </button>
                <button class="tab-btn text-sm font-bold py-2 whitespace-nowrap <?= $tab === 'watching' ? 'active dr-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="changeDrTab('watching')">
                    <i class="fa-solid fa-play mr-1"></i>見てる<span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= (int)($watchingCount ?? 0) ?></span>
                </button>
                <button class="tab-btn text-sm font-bold py-2 whitespace-nowrap <?= $tab === 'watched' ? 'active dr-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="changeDrTab('watched')">
                    <i class="fa-solid fa-check-circle mr-1"></i>見た<span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= (int)($watchedCount ?? 0) ?></span>
                </button>

                <div class="ml-auto flex items-center gap-2">
                    <select onchange="applyDrCategory(this.value)" class="text-xs border border-slate-200 rounded-lg px-1.5 md:px-2 py-1.5 text-slate-500 focus:outline-none focus:ring-1 focus:ring-[var(--dr-theme)] max-w-[7rem] md:max-w-none mr-2">
                        <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>すべて</option>
                        <option value="anime" <?= $category === 'anime' ? 'selected' : '' ?>>アニメ</option>
                        <option value="drama" <?= $category === 'drama' ? 'selected' : '' ?>>ドラマ</option>
                    </select>
                    <div class="flex items-center bg-slate-100 rounded-lg p-0.5">
                        <button onclick="setDrViewMode('grid')" class="view-toggle-btn w-8 h-8 rounded-md flex items-center justify-center text-sm transition <?= $viewMode === 'grid' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>" title="ブロック表示">
                            <i class="fa-solid fa-table-cells"></i>
                        </button>
                        <button onclick="setDrViewMode('list')" class="view-toggle-btn w-8 h-8 rounded-md flex items-center justify-center text-sm transition <?= $viewMode === 'list' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>" title="一覧表示">
                            <i class="fa-solid fa-list"></i>
                        </button>
                    </div>
                    <select onchange="applyDrSortOrder(this.value)" class="text-xs border border-slate-200 rounded-lg px-1.5 md:px-2 py-1.5 text-slate-500 focus:outline-none focus:ring-1 focus:ring-[var(--dr-theme)] max-w-[7rem] md:max-w-none">
                        <option value="created_at-DESC" <?= ($sort === 'created_at' && $order === 'DESC') ? 'selected' : '' ?>>追加日 (新しい順)</option>
                        <option value="created_at-ASC" <?= ($sort === 'created_at' && $order === 'ASC') ? 'selected' : '' ?>>追加日 (古い順)</option>
                        <option value="first_air_date-DESC" <?= ($sort === 'first_air_date' && $order === 'DESC') ? 'selected' : '' ?>>初回放送 (新しい順)</option>
                        <option value="title-ASC" <?= ($sort === 'title' && $order === 'ASC') ? 'selected' : '' ?>>タイトル (A→Z)</option>
                        <?php if ($tab === 'watched'): ?>
                        <option value="rating-DESC" <?= ($sort === 'rating' && $order === 'DESC') ? 'selected' : '' ?>>評価 (高い順)</option>
                        <option value="watched_date-DESC" <?= ($sort === 'watched_date' && $order === 'DESC') ? 'selected' : '' ?>>視聴完了日 (新しい順)</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <?php if (!empty($filter)): ?>
        <div class="bg-blue-50 border-b border-blue-100 px-6 md:px-12 py-2 shrink-0">
            <div class="max-w-7xl mx-auto flex items-center gap-3">
                <i class="fa-solid fa-filter text-blue-400 text-xs"></i>
                <span class="text-xs font-bold text-blue-600">
                    <?php if ($filter === 'this_month'): ?>
                    <?= date('Y年n月') ?>の視聴完了ドラマを表示中（<?= count($series ?? []) ?>本）
                    <?php endif; ?>
                </span>
                <a href="?tab=<?= htmlspecialchars($tab) ?>&category=<?= htmlspecialchars($category) ?>" class="ml-auto text-xs text-blue-500 hover:text-blue-700 font-bold transition">
                    <i class="fa-solid fa-xmark mr-0.5"></i>フィルタ解除
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- コンテンツ -->
        <div class="flex-1 overflow-y-auto" data-scroll-persist="drama-list">
            <div class="max-w-7xl mx-auto px-6 md:px-12 py-6">

                <?php if (empty($series)): ?>
                <div class="text-center py-20">
                    <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-clapperboard text-4xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">
                        <?php if ($tab === 'wanna_watch'): ?>
                        まだ見たいドラマがありません
                        <?php elseif ($tab === 'watching'): ?>
                        まだ視聴中のドラマがありません
                        <?php else: ?>
                        まだ見たドラマがありません
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-slate-400 mb-6">アニメ/ドラマを追加してリストを作りましょう</p>
                    <?php if ($tmdbConfigured): ?>
                    <button onclick="DrSearchModal.open()" class="px-6 py-2.5 dr-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                        <i class="fa-solid fa-plus mr-2"></i>ドラマを追加
                    </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>

                <!-- グリッド表示 -->
                <div id="drViewGrid" class="<?= $viewMode === 'grid' ? '' : 'hidden' ?>">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        <?php foreach ($series as $dr):
                            $drGenres = [];
                            if (!empty($dr['genres'])) {
                                $g = json_decode($dr['genres'], true);
                                if (is_array($g)) $drGenres = array_filter($g, 'is_string');
                            }
                            $drTags = [];
                            if (!empty($dr['tags'])) {
                                $t = json_decode($dr['tags'], true);
                                if (is_array($t)) $drTags = array_filter($t, 'is_string');
                            }
                        ?>
                        <div class="dr-card bg-white rounded-xl overflow-hidden shadow-sm border border-slate-100 cursor-pointer"
                             onclick="goDrDetail(<?= (int)$dr['id'] ?>)"
                             data-title="<?= htmlspecialchars(mb_strtolower($dr['title'] ?? '')) ?>"
                             data-genres="<?= htmlspecialchars(implode(',', $drGenres)) ?>">
                            <div class="aspect-[2/3] relative overflow-hidden">
                                <?php if (!empty($dr['poster_path']) || !empty($dr['backdrop_path'])):
                                    $imgPath = $dr['poster_path'] ?: $dr['backdrop_path'];
                                ?>
                                <img src="https://image.tmdb.org/t/p/w500<?= htmlspecialchars($imgPath) ?>"
                                     alt="<?= htmlspecialchars($dr['title'] ?? '') ?>"
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="w-full h-full poster-placeholder flex items-center justify-center">
                                    <i class="fa-solid fa-clapperboard text-4xl text-slate-400"></i>
                                </div>
                                <?php endif; ?>

                                <?php if ($tab === 'watched' && !empty($dr['rating'])): ?>
                                <div class="absolute top-2 right-2 bg-black/70 text-amber-400 text-xs font-bold px-2 py-1 rounded-lg flex items-center gap-1">
                                    <i class="fa-solid fa-star text-[10px]"></i> <?= (int)$dr['rating'] ?>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($dr['vote_average']) && $dr['vote_average'] > 0): ?>
                                <div class="absolute bottom-2 left-2 bg-black/70 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
                                    <i class="fa-solid fa-star text-amber-400 mr-0.5"></i><?= number_format($dr['vote_average'], 1) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3">
                                <h3 class="text-sm font-bold text-slate-800 line-clamp-2 leading-snug mb-1"><?= htmlspecialchars($dr['title'] ?? '') ?></h3>
                                <div class="flex items-center gap-1.5 flex-wrap text-[11px] text-slate-400">
                                    <?php if (!empty($dr['first_air_date'])): ?>
                                    <span><?= date('Y年', strtotime($dr['first_air_date'])) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($dr['number_of_seasons'])): ?>
                                    <span><?= (int)$dr['number_of_seasons'] ?>期</span>
                                    <?php endif; ?>
                                    <?php if (!empty($dr['number_of_episodes'])): ?>
                                    <span><?= (int)$dr['number_of_episodes'] ?>話</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- リスト表示 -->
                <div id="drViewList" class="<?= $viewMode === 'list' ? '' : 'hidden' ?>">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-100">
                        <?php foreach ($series as $dr):
                            $drGenresL = [];
                            if (!empty($dr['genres'])) {
                                $g2 = json_decode($dr['genres'], true);
                                if (is_array($g2)) $drGenresL = array_filter($g2, 'is_string');
                            }
                        ?>
                        <div class="list-row flex items-center gap-4 px-4 py-3 cursor-pointer"
                             onclick="goDrDetail(<?= (int)$dr['id'] ?>)"
                             data-title="<?= htmlspecialchars(mb_strtolower($dr['title'] ?? '')) ?>"
                             data-genres="<?= htmlspecialchars(implode(',', $drGenresL)) ?>">
                            <?php if (!empty($dr['poster_path']) || !empty($dr['backdrop_path'])):
                                $pPath = $dr['poster_path'] ?: $dr['backdrop_path'];
                            ?>
                            <img src="https://image.tmdb.org/t/p/w185<?= htmlspecialchars($pPath) ?>"
                                 alt="<?= htmlspecialchars($dr['title'] ?? '') ?>"
                                 class="w-10 h-[60px] object-cover rounded-lg shrink-0 shadow-sm"
                                 loading="lazy">
                            <?php else: ?>
                            <div class="w-10 h-[60px] poster-placeholder rounded-lg flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-clapperboard text-slate-400 text-sm"></i>
                            </div>
                            <?php endif; ?>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($dr['title'] ?? '') ?></h3>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                    <?php if (!empty($dr['first_air_date'])): ?>
                                    <span><?= date('Y年', strtotime($dr['first_air_date'])) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($dr['number_of_seasons'])): ?>
                                    <span><?= (int)$dr['number_of_seasons'] ?>期</span>
                                    <?php endif; ?>
                                    <?php if (!empty($dr['number_of_episodes'])): ?>
                                    <span><?= (int)$dr['number_of_episodes'] ?>話</span>
                                    <?php endif; ?>
                                    <?php if (!empty($drGenresL)): ?>
                                    <span class="text-slate-300">|</span>
                                    <span class="truncate"><?= htmlspecialchars(implode(', ', array_slice($drGenresL, 0, 3))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="shrink-0 flex items-center gap-3">
                                <?php if ($tab === 'watched' && !empty($dr['rating'])): ?>
                                <div class="text-amber-400 text-sm font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-star text-xs"></i><?= (int)$dr['rating'] ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($tab === 'watched' && !empty($dr['watched_date'])): ?>
                                <span class="text-[11px] text-slate-400 hidden sm:inline"><?= date('Y/m/d', strtotime($dr['watched_date'])) ?></span>
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
    <div id="drSearchOverlay" class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-[10vh] opacity-0 pointer-events-none transition-opacity" onclick="DrSearchModal.close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[80vh] flex flex-col" onclick="event.stopPropagation()">
            <div class="p-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="flex-1 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="drSearchInput" placeholder="ドラマタイトルを入力..."
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)] focus:border-transparent"
                               onkeydown="if(event.key==='Enter') DrSearchModal.search()">
                    </div>
                    <button onclick="DrSearchModal.search()" class="px-5 py-3 dr-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0">
                        検索
                    </button>
                    <button onclick="DrSearchModal.close()" class="p-3 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>
            <div id="drSearchResults" class="flex-1 overflow-y-auto p-4">
                <div class="text-center py-12 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-3xl mb-3"></i>
                    <p class="text-sm">TMDBからドラマを検索して追加できます</p>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <?php require_once __DIR__ . '/_drama_search_shared.php'; ?>
    <script>
        const drCurrentTab = '<?= htmlspecialchars($tab) ?>';
        const drCurrentCategory = '<?= htmlspecialchars($category) ?>';

        function goDrDetail(id) {
            const view = document.getElementById('drViewList').classList.contains('hidden') ? 'grid' : 'list';
            const params = new URLSearchParams(location.search);
            params.set('view', view);
            sessionStorage.setItem('app:lastListParams:/drama/list.php', '?' + params.toString());
            location.href = '/drama/detail.php?id=' + id;
        }

        function applyDrSortOrder(val) {
            const [sort, order] = val.split('-');
            const url = new URL(location.href);
            url.searchParams.set('tab', drCurrentTab);
            url.searchParams.set('category', drCurrentCategory);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
            location.href = url.toString();
        }

        function applyDrCategory(category) {
            const url = new URL(location.href);
            url.searchParams.set('tab', drCurrentTab);
            url.searchParams.set('category', category);
            location.href = url.toString();
        }

        function changeDrTab(tab) {
            const url = new URL(location.href);
            url.searchParams.set('tab', tab);
            url.searchParams.set('category', drCurrentCategory);
            url.searchParams.delete('filter');
            url.searchParams.delete('sort');
            url.searchParams.delete('order');
            location.href = url.toString();
        }

        function setDrViewMode(mode) {
            document.cookie = `dr_view_mode=${mode};path=/;max-age=${365*86400}`;
            document.getElementById('drViewGrid').classList.toggle('hidden', mode !== 'grid');
            document.getElementById('drViewList').classList.toggle('hidden', mode !== 'list');
            document.querySelectorAll('.view-toggle-btn').forEach((btn, i) => {
                const isActive = (i === 0 && mode === 'grid') || (i === 1 && mode === 'list');
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('text-slate-400', !isActive);
            });
        }

        const DrSearchModal = {
            currentQuery: '',
            currentPage: 0,
            totalPages: 0,
            isLoading: false,
            _observer: null,

            open() {
                const el = document.getElementById('drSearchOverlay');
                el.classList.remove('pointer-events-none', 'opacity-0');
                el.classList.add('opacity-100');
                setTimeout(() => document.getElementById('drSearchInput').focus(), 100);
            },
            close() {
                const el = document.getElementById('drSearchOverlay');
                el.classList.add('opacity-0');
                el.classList.remove('opacity-100');
                setTimeout(() => {
                    el.classList.add('pointer-events-none');
                }, 200);
                this._destroyObserver();
            },
            async search() {
                const query = document.getElementById('drSearchInput').value.trim();
                if (!query) return;

                this.currentQuery = query;
                this.currentPage = 1;
                this.totalPages = 0;
                this.isLoading = true;
                this._destroyObserver();

                const container = document.getElementById('drSearchResults');
                container.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-slate-300"></i></div>';
                try {
                    const res = await fetch(`/drama/api/search.php?q=${encodeURIComponent(query)}&page=1`);
                    const json = await res.json();
                    if (json.status !== 'success') {
                        container.innerHTML = `<div class="text-center py-8 text-red-500 text-sm">${(json.message || '検索に失敗しました')}</div>`;
                        return;
                    }
                    const results = json.data || {};
                    const list = results.results || [];
                    this.totalPages = results.total_pages || 1;
                    if (!list.length) {
                        container.innerHTML = '<div class="text-center py-6 text-slate-400 text-sm">TMDBで見つかりませんでした</div>';
                        return;
                    }
                    list.forEach(s => DramaPreview.store(s));
                    const listHtml = list.map(s => DramaSearch.renderResult(s)).join('');
                    const sentinelHtml = this.currentPage < this.totalPages
                        ? '<div id="drSearchResultsSentinel" class="flex items-center justify-center py-4 text-slate-400 text-xs gap-2"><i class="fa-solid fa-angles-down text-[10px]"></i>スクロールで続きを表示</div>'
                        : '';
                    container.innerHTML = `<div id="drSearchResultsList">${listHtml}</div>${sentinelHtml}`;
                    this._initObserver();
                } catch (e) {
                    console.error(e);
                    container.innerHTML = '<div class="text-center py-8 text-red-500 text-sm">検索中にエラーが発生しました</div>';
                } finally {
                    this.isLoading = false;
                }
            },
            async loadMore() {
                if (this.isLoading || this.currentPage >= this.totalPages) return;
                this.isLoading = true;
                this.currentPage++;

                const sentinel = document.getElementById('drSearchResultsSentinel');
                if (sentinel) sentinel.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-slate-300"></i>';

                try {
                    const res = await fetch(`/drama/api/search.php?q=${encodeURIComponent(this.currentQuery)}&page=${this.currentPage}`);
                    const json = await res.json();
                    if (json.status === 'success') {
                        const results = json.data || {};
                        const list = results.results || [];
                        list.forEach(s => DramaPreview.store(s));
                        const listEl = document.getElementById('drSearchResultsList');
                        if (listEl) listEl.insertAdjacentHTML('beforeend', list.map(s => DramaSearch.renderResult(s)).join(''));
                    }
                    if (this.currentPage >= this.totalPages) {
                        if (sentinel) sentinel.remove();
                        this._destroyObserver();
                    } else if (sentinel) {
                        sentinel.innerHTML = '<i class="fa-solid fa-angles-down text-[10px]"></i>スクロールで続きを表示';
                    }
                } catch (e) {
                    console.error(e);
                    if (sentinel) sentinel.innerHTML = '<span class="text-red-400">読み込みエラー</span>';
                    this.currentPage--;
                } finally {
                    this.isLoading = false;
                }
            },
            _initObserver() {
                this._destroyObserver();
                const sentinel = document.getElementById('drSearchResultsSentinel');
                if (!sentinel) return;
                const container = document.getElementById('drSearchResults');
                this._observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) this.loadMore();
                }, { root: container, threshold: 0.1 });
                this._observer.observe(sentinel);
            },
            _destroyObserver() {
                if (this._observer) {
                    this._observer.disconnect();
                    this._observer = null;
                }
            }
        };

        DramaSearch.init({
            inputId: 'drDashSearchInput', // ダッシュボードと共通の関数仕様を満たすためダミー
            resultsId: 'drDashSearchResults',
            wrapperId: 'drDashSearchWrapper'
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                DrSearchModal.close();
                DramaSearch.closeResults();
                DramaPosterPreview.close();
                DramaPreview.close();
            }
        });
    </script>
</body>
</html>

