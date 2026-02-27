<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$escapedQuery = htmlspecialchars($query ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escapedQuery ? "「{$escapedQuery}」の検索結果" : '映画検索' ?> - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        }
        .result-card { transition: all 0.2s ease; }
        .result-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .poster-placeholder { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/movie/" class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?> hover:brightness-110 transition"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?> title="映画ダッシュボード">
                    <i class="fa-solid fa-film text-sm"></i>
                </a>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">映画検索</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="/movie/list.php" class="flex items-center gap-2 px-3 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-list"></i>
                    <span class="hidden sm:inline">マイリスト</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto" id="mainScroll">
            <div class="max-w-6xl mx-auto px-6 md:px-12 py-6">

                <?php if ($tmdbConfigured): ?>
                <div class="mb-6">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="searchInput"
                                   value="<?= $escapedQuery ?>"
                                   placeholder="映画タイトルで検索..."
                                   class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] focus:border-transparent shadow-sm"
                                   onkeydown="if(event.key==='Enter') SearchPage.search()"
                                   autocomplete="off">
                        </div>
                        <button onclick="SearchPage.search()" class="px-6 py-3 mv-theme-btn text-white text-sm font-bold rounded-xl transition shrink-0 shadow-sm">
                            検索
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-12 text-amber-600 bg-amber-50 rounded-xl mb-6">
                    <i class="fa-solid fa-triangle-exclamation text-2xl mb-2"></i>
                    <p class="text-sm">TMDB APIキーが設定されていません</p>
                </div>
                <?php endif; ?>

                <div id="searchStatus" class="mb-4 hidden">
                    <p class="text-sm text-slate-500" id="searchStatusText"></p>
                </div>

                <div id="searchResults">
                    <?php if (empty($query)): ?>
                    <div class="text-center py-20 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-4xl mb-4"></i>
                        <p class="text-sm">検索キーワードを入力してください</p>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-20">
                        <i class="fa-solid fa-spinner fa-spin text-3xl text-slate-300"></i>
                    </div>
                    <?php endif; ?>
                </div>

                <?php require_once __DIR__ . '/_tmdb_attribution.php'; ?>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/_movie_search_shared.php'; ?>

    <script src="/assets/js/core.js"></script>
    <script>
    const SearchPage = {
        currentQuery: '',
        currentPage: 0,
        totalPages: 0,
        totalResults: 0,
        isLoading: false,
        _observer: null,

        init(initialQuery) {
            if (initialQuery) {
                document.getElementById('searchInput').value = initialQuery;
                this.doSearch(initialQuery);
            }
        },

        search() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;
            const url = new URL(window.location);
            url.searchParams.set('q', query);
            history.replaceState(null, '', url);
            this.doSearch(query);
        },

        async doSearch(query) {
            this.currentQuery = query;
            this.currentPage = 1;
            this.totalPages = 0;
            this.totalResults = 0;
            this.isLoading = true;
            this._destroyObserver();

            const container = document.getElementById('searchResults');
            container.innerHTML = '<div class="text-center py-20"><i class="fa-solid fa-spinner fa-spin text-3xl text-slate-300"></i></div>';

            const statusEl = document.getElementById('searchStatus');
            statusEl.classList.add('hidden');

            try {
                const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(query)}&page=1`);
                const json = await res.json();

                if (json.status !== 'success') {
                    container.innerHTML = `<div class="text-center py-20 text-red-500 text-sm">${json.message}</div>`;
                    return;
                }

                const movies = json.data.results || [];
                this.totalPages = json.data.total_pages || 1;
                this.totalResults = json.data.total_results || 0;

                if (movies.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-20 text-slate-400">
                            <i class="fa-solid fa-face-sad-tear text-4xl mb-4"></i>
                            <p class="text-sm">「${_esc(query)}」に一致する映画が見つかりませんでした</p>
                        </div>` + this.renderManualAdd(query);
                    this.updateStatus(query, 0);
                    return;
                }

                this.updateStatus(query, this.totalResults);
                movies.forEach(m => MoviePreview.storeMovie(m));
                const gridHtml = `<div id="searchResultsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">${movies.map(m => this.renderCard(m)).join('')}</div>`;
                const sentinelHtml = this.currentPage < this.totalPages
                    ? '<div id="searchSentinel" class="flex items-center justify-center py-6 text-slate-400 text-sm gap-2"><i class="fa-solid fa-angles-down text-xs"></i>スクロールで続きを表示</div>'
                    : '';
                container.innerHTML = gridHtml + sentinelHtml + this.renderManualAdd(query);
                this._initObserver();
            } catch (e) {
                console.error(e);
                container.innerHTML = '<div class="text-center py-20 text-red-500 text-sm">検索中にエラーが発生しました</div>';
            } finally {
                this.isLoading = false;
            }
        },

        async loadMore() {
            if (this.isLoading || this.currentPage >= this.totalPages) return;
            this.isLoading = true;
            this.currentPage++;

            const sentinel = document.getElementById('searchSentinel');
            if (sentinel) sentinel.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-slate-300 text-lg"></i>';

            try {
                const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(this.currentQuery)}&page=${this.currentPage}`);
                const json = await res.json();
                if (json.status === 'success') {
                    const movies = json.data.results || [];
                    movies.forEach(m => MoviePreview.storeMovie(m));
                    const grid = document.getElementById('searchResultsGrid');
                    if (grid) grid.insertAdjacentHTML('beforeend', movies.map(m => this.renderCard(m)).join(''));
                }
                if (this.currentPage >= this.totalPages) {
                    if (sentinel) sentinel.remove();
                    this._destroyObserver();
                } else if (sentinel) {
                    sentinel.innerHTML = '<i class="fa-solid fa-angles-down text-xs"></i>スクロールで続きを表示';
                }
            } catch (e) {
                console.error(e);
                if (sentinel) sentinel.innerHTML = '<span class="text-red-400 text-sm">読み込みエラー</span>';
                this.currentPage--;
            } finally {
                this.isLoading = false;
            }
        },

        _initObserver() {
            this._destroyObserver();
            const sentinel = document.getElementById('searchSentinel');
            if (!sentinel) return;
            const scrollContainer = document.getElementById('mainScroll');
            this._observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) this.loadMore();
            }, { root: scrollContainer, threshold: 0.1 });
            this._observer.observe(sentinel);
        },

        _destroyObserver() {
            if (this._observer) { this._observer.disconnect(); this._observer = null; }
        },

        updateStatus(query, total) {
            const statusEl = document.getElementById('searchStatus');
            const textEl = document.getElementById('searchStatusText');
            statusEl.classList.remove('hidden');
            textEl.innerHTML = `<span class="font-bold text-slate-700">「${_esc(query)}」</span>の検索結果 <span class="font-bold mv-theme-text">${total.toLocaleString()}</span> 件`;
        },

        renderCard(m) {
            const poster = m.poster_path
                ? `<img src="https://image.tmdb.org/t/p/w342${m.poster_path}" alt="${_esc(m.title)}" class="w-full aspect-[2/3] object-cover rounded-xl" loading="lazy">`
                : `<div class="w-full aspect-[2/3] poster-placeholder rounded-xl flex items-center justify-center"><i class="fa-solid fa-film text-slate-400 text-2xl"></i></div>`;
            const year = m.release_date ? m.release_date.substring(0, 4) : '';
            const rating = m.vote_average ? m.vote_average.toFixed(1) : '';

            let statusBadge = '';
            if (m.user_status === 'watchlist') {
                statusBadge = '<div class="absolute top-2 left-2 text-[10px] font-bold text-white bg-blue-500/90 backdrop-blur-sm px-2 py-0.5 rounded-lg"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい</div>';
            } else if (m.user_status === 'watched') {
                statusBadge = '<div class="absolute top-2 left-2 text-[10px] font-bold text-white bg-green-500/90 backdrop-blur-sm px-2 py-0.5 rounded-lg"><i class="fa-solid fa-check mr-0.5"></i>見た</div>';
            }

            let actionHtml = '';
            if (!m.user_status) {
                actionHtml = `
                    <div class="flex gap-1.5 mt-2">
                        <button onclick="event.stopPropagation(); SearchPage.addMovie(${m.id}, 'watchlist', this)" class="flex-1 text-[11px] font-bold text-white py-1.5 rounded-lg mv-theme-btn transition"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい</button>
                        <button onclick="event.stopPropagation(); SearchPage.addMovie(${m.id}, 'watched', this)" class="flex-1 text-[11px] font-bold text-slate-500 border border-slate-200 py-1.5 rounded-lg hover:bg-slate-50 transition"><i class="fa-solid fa-check mr-0.5"></i>見た</button>
                    </div>`;
            }

            return `
                <div class="result-card bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden cursor-pointer" data-tmdb-id="${m.id}" onclick="SearchPage.openPreview(${m.id})">
                    <div class="relative">
                        ${poster}
                        ${statusBadge}
                        ${rating ? `<div class="absolute bottom-2 right-2 text-[11px] font-bold text-white bg-black/60 backdrop-blur-sm px-2 py-0.5 rounded-lg"><i class="fa-solid fa-star text-amber-400 text-[9px] mr-0.5"></i>${rating}</div>` : ''}
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-bold text-slate-800 line-clamp-2 leading-tight">${_esc(m.title)}</h3>
                        <p class="text-[11px] text-slate-400 mt-1">${year ? year + '年' : ''}</p>
                        ${actionHtml}
                    </div>
                </div>`;
        },

        renderManualAdd(query) {
            const escaped = _esc(query);
            return `
                <div class="mt-6 border border-dashed border-slate-200 rounded-xl p-5 bg-slate-50/50">
                    <p class="text-xs text-slate-400 mb-3"><i class="fa-solid fa-pen mr-1"></i>TMDBに無い場合、タイトルだけで追加できます</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-700 flex-1 truncate">「${escaped}」</span>
                        <button onclick="SearchPage.addManual('watchlist')" class="text-xs font-bold text-white px-4 py-2 rounded-lg mv-theme-btn transition whitespace-nowrap"><i class="fa-solid fa-bookmark mr-1"></i>見たいに追加</button>
                        <button onclick="SearchPage.addManual('watched')" class="text-xs font-bold text-slate-500 border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-50 transition whitespace-nowrap"><i class="fa-solid fa-check mr-1"></i>見たに追加</button>
                    </div>
                </div>`;
        },

        openPreview(tmdbId) {
            const m = MoviePreview.cache[tmdbId];
            if (m) MoviePreview.open(m);
        },

        async addMovie(tmdbId, status, btn) {
            btn.disabled = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            try {
                const result = await App.post('/movie/api/add.php', { tmdb_id: tmdbId, status: status });
                if (result.status === 'success') {
                    App.toast(result.message);
                    if (MoviePreview.cache[tmdbId]) {
                        MoviePreview.cache[tmdbId].user_status = status;
                        MoviePreview.cache[tmdbId].user_movie_id = result.data?.id || null;
                    }
                    const card = btn.closest('[data-tmdb-id]');
                    const posterDiv = card.querySelector('.relative');
                    const existingBadge = posterDiv.querySelector('[class*="absolute top-2 left-2"]');
                    if (existingBadge) existingBadge.remove();
                    const badge = status === 'watchlist'
                        ? '<div class="absolute top-2 left-2 text-[10px] font-bold text-white bg-blue-500/90 backdrop-blur-sm px-2 py-0.5 rounded-lg"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい</div>'
                        : '<div class="absolute top-2 left-2 text-[10px] font-bold text-white bg-green-500/90 backdrop-blur-sm px-2 py-0.5 rounded-lg"><i class="fa-solid fa-check mr-0.5"></i>見た</div>';
                    posterDiv.insertAdjacentHTML('beforeend', badge);
                    const actionContainer = btn.parentElement;
                    if (actionContainer) actionContainer.remove();
                } else {
                    App.toast(result.message || '追加に失敗しました');
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            } catch (e) {
                console.error(e);
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        },

        async addManual(status) {
            const query = this.currentQuery;
            if (!query) return;
            try {
                const result = await App.post('/movie/api/add_manual.php', { title: query, status: status });
                if (result.status === 'success') {
                    App.toast(result.message);
                } else {
                    App.toast(result.message || '追加に失敗しました');
                }
            } catch (e) {
                console.error(e);
                App.toast('エラーが発生しました');
            }
        }
    };

    <?php if (!empty($query)): ?>
    SearchPage.init(<?= json_encode($query, JSON_UNESCAPED_UNICODE) ?>);
    <?php endif; ?>

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { PosterPreview.close(); MoviePreview.close(); }
    });
    </script>
</body>
</html>
