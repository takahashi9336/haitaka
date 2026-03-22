<?php
// ドラマ（TVシリーズ）検索・プレビュー共通 JS
?>
<!-- ポスタープレビューモーダル -->
<div id="drPosterPreview" class="fixed inset-0 bg-black/80 z-[70] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); DramaPosterPreview.close()">
    <button class="absolute top-4 right-4 text-white/70 hover:text-white transition text-2xl" onclick="DramaPosterPreview.close()"><i class="fa-solid fa-xmark"></i></button>
    <img id="drPosterPreviewImg" src="" alt="" class="max-w-[90vw] max-h-[85vh] rounded-xl shadow-2xl object-contain transition-transform duration-200 scale-95" onclick="event.stopPropagation()">
</div>

<!-- ドラマプレビューモーダル -->
<div id="dramaPreview" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); DramaPreview.close()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-[95vw] max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div id="dramaPreviewContent" class="p-6"></div>
    </div>
</div>

<script>
const DR_GENRE_MAP = {10759:'アクション&アドベンチャー',16:'アニメーション',35:'コメディ',80:'クライム',99:'ドキュメンタリー',18:'ドラマ',10751:'ファミリー',10762:'キッズ',9648:'ミステリー',10763:'ニュース',10764:'リアリティ',10765:'SF & ファンタジー',10766:'ソープ',10767:'トーク',10768:'戦争 & 政治',37:'西部劇'};

function _drEsc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

const DramaPosterPreview = {
    open(imgUrl) {
        if (!imgUrl) return;
        const img = document.getElementById('drPosterPreviewImg');
        // サムネイル(w185)が渡ってきた場合は、拡大用にw500へ差し替え
        const largeUrl = imgUrl.replace('/w185', '/w500');
        img.src = largeUrl;
        const el = document.getElementById('drPosterPreview');
        el.classList.remove('pointer-events-none', 'opacity-0');
        el.classList.add('pointer-events-auto', 'opacity-100');
        img.classList.remove('scale-95');
        img.classList.add('scale-100');
    },
    close() {
        const el = document.getElementById('drPosterPreview');
        const img = document.getElementById('drPosterPreviewImg');
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

const DramaPreview = {
    _cache: {},

    store(series) {
        if (series && series.id) this._cache[series.id] = series;
    },

    openById(id) {
        const s = this._cache[id];
        if (!s) return;
        this.open(s);
    },

    open(s) {
        this.store(s);
        const imgPath = s.poster_path || s.backdrop_path || '';
        const imgUrl = imgPath ? ('https://image.tmdb.org/t/p/w500' + imgPath) : '';
        const year = (s.first_air_date || '').substring(0, 4);
        const seasons = s.number_of_seasons ? `${s.number_of_seasons}シーズン` : '';
        const episodes = s.number_of_episodes ? `${s.number_of_episodes}話` : '';
        const genres = (s.genre_ids || []).map(id => DR_GENRE_MAP[id]).filter(Boolean);
        const overview = s.overview || '';
        const detailUrl = s.user_series_id ? `/drama/detail.php?id=${s.user_series_id}` : '';

        const poster = imgUrl
            ? `<img src="${_drEsc(imgUrl)}" class="w-28 md:w-32 aspect-[2/3] rounded-xl shadow-lg cursor-pointer shrink-0 object-cover" loading="lazy" onclick="event.stopPropagation(); DramaPosterPreview.open('${_drEsc(imgUrl)}')">`
            : `<div class="w-28 md:w-32 aspect-[2/3] bg-slate-100 rounded-xl flex items-center justify-center shrink-0"><i class="fa-solid fa-clapperboard text-slate-400 text-2xl"></i></div>`;

        let statusBadge = '';
        const labels = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
        if (s.user_status && labels[s.user_status]) {
            const label = labels[s.user_status];
            const badgeClass = s.user_status === 'watched' ? 'text-green-600 bg-green-50' : (s.user_status === 'watching' ? 'text-sky-600 bg-sky-50' : 'text-amber-600 bg-amber-50');
            statusBadge = `<span class="inline-flex items-center gap-1 text-xs font-bold ${badgeClass} px-2.5 py-1 rounded-full"><i class="fa-solid fa-check"></i>${_drEsc(label)}済</span>`;
        }

        const isWatched = s.user_status === 'watched';
        const isPartial = s.user_status === 'wanna_watch' || s.user_status === 'watching';
        let actionsHtml = '';
        if (isWatched) {
            actionsHtml = detailUrl
                ? `<a href="${detailUrl}" class="block w-full text-center px-4 py-2.5 bg-violet-500 hover:bg-violet-600 text-white text-sm font-bold rounded-xl transition"><i class="fa-solid fa-arrow-right mr-1.5"></i>詳細ページを開く</a>`
                : '';
        } else if (isPartial) {
            const detailLink = detailUrl ? `<a href="${detailUrl}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-50 transition"><i class="fa-solid fa-arrow-right mr-1.5"></i>詳細</a>` : '';
            actionsHtml = `
                ${detailLink}
                <button onclick="DramaPreview.addStatus(${s.id}, 'watched')" class="flex-1 min-w-[120px] px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl transition">
                    <i class="fa-solid fa-check mr-1.5"></i>見たに追加
                </button>`;
        } else if (!s.user_status) {
            actionsHtml = `
                <button onclick="DramaPreview.addStatus(${s.id}, 'wanna_watch')" class="flex-1 min-w-[100px] px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition">
                    <i class="fa-solid fa-bookmark mr-1.5"></i>見たいに追加
                </button>
                <button onclick="DramaPreview.addStatus(${s.id}, 'watching')" class="flex-1 min-w-[100px] px-4 py-2.5 bg-sky-500 hover:bg-sky-600 text-white text-sm font-bold rounded-xl transition">
                    <i class="fa-solid fa-play mr-1.5"></i>見てるに追加
                </button>
                <button onclick="DramaPreview.addStatus(${s.id}, 'watched')" class="flex-1 min-w-[100px] px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl transition">
                    <i class="fa-solid fa-check mr-1.5"></i>見たに追加
                </button>`;
        }

        const content = document.getElementById('dramaPreviewContent');
        content.innerHTML = `
            <div class="flex flex-col md:flex-row items-start gap-4 mb-4">
                ${poster}
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-black text-slate-800 leading-tight mb-1">${_drEsc(s.name || s.title || '')}</h2>
                    ${s.original_name && s.original_name !== s.name ? `<p class="text-xs text-slate-400 mb-1">${_drEsc(s.original_name)}</p>` : ''}
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-2">
                        ${year ? `<span><i class="fa-solid fa-calendar mr-0.5"></i>${_drEsc(year)}年</span>` : ''}
                        ${seasons ? `<span><i class="fa-solid fa-layer-group mr-0.5"></i>${_drEsc(seasons)}</span>` : ''}
                        ${episodes ? `<span><i class="fa-solid fa-list-ol mr-0.5"></i>${_drEsc(episodes)}</span>` : ''}
                    </div>
                    ${genres.length ? `<div class="flex flex-wrap gap-1 mb-2">${genres.map(g => `<span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${_drEsc(g)}</span>`).join('')}</div>` : ''}
                    ${overview ? `<p class="text-xs text-slate-600 line-clamp-4 mb-2">${_drEsc(overview)}</p>` : ''}
                    <div class="flex items-center justify-between mb-2">
                        <div>${statusBadge}</div>
                        ${detailUrl ? `<a href="${detailUrl}" class="inline-flex items-center gap-1 text-[11px] text-violet-500 font-bold hover:text-violet-600"><i class="fa-solid fa-arrow-right"></i>詳細ページを開く</a>` : ''}
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mb-4" id="dramaPreviewActions_${s.id}">
                ${actionsHtml}
            </div>
            <div class="flex gap-2">
                <button onclick="DramaPreview.close()" class="flex-1 px-4 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-xl hover:bg-slate-50 transition">
                    閉じる
                </button>
            </div>
        `;

        const el = document.getElementById('dramaPreview');
        el.classList.remove('pointer-events-none', 'opacity-0');
        el.classList.add('pointer-events-auto', 'opacity-100');
    },

    close() {
        const el = document.getElementById('dramaPreview');
        el.classList.add('opacity-0');
        el.classList.remove('opacity-100');
        setTimeout(() => {
            el.classList.add('pointer-events-none');
            el.classList.remove('pointer-events-auto');
        }, 200);
    },

    async addStatus(tmdbId, status) {
        const actionsEl = document.getElementById(`dramaPreviewActions_${tmdbId}`);
        if (actionsEl) actionsEl.innerHTML = '<div class="w-full text-center py-2"><i class="fa-solid fa-spinner fa-spin text-slate-400"></i></div>';
        try {
            const result = await App.post('/drama/api/add.php', { tmdb_id: tmdbId, status: status });
            if (result.status === 'success') {
                const labels = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
                App.toast(labels[status] + 'に追加しました');
                const cached = this._cache[tmdbId];
                if (cached) {
                    cached.user_status = status;
                    cached.user_series_id = result.data?.id ?? cached.user_series_id;
                }
                if (actionsEl) {
                    actionsEl.innerHTML = `<div class="w-full text-center py-2"><span class="inline-flex items-center gap-1 text-violet-600 font-bold text-sm bg-violet-50 px-3 py-1.5 rounded-full"><i class="fa-solid fa-check"></i>${_drEsc(labels[status])}済み</span></div>`;
                }
                DramaPreview.close();
            } else {
                App.toast(result.message || '追加に失敗しました');
                if (actionsEl) actionsEl.innerHTML = '';
            }
        } catch (e) {
            console.error(e);
            App.toast('エラーが発生しました');
            if (actionsEl) actionsEl.innerHTML = '';
        }
    }
};

const DramaSearch = {
    inputId: null,
    resultsId: null,
    wrapperId: null,
    onAdded: null,
    currentQuery: '',
    currentPage: 0,
    totalPages: 0,
    totalResults: 0,
    isLoading: false,
    _observer: null,
    _cache: {},

    init(config) {
        this.inputId = config.inputId;
        this.resultsId = config.resultsId;
        this.wrapperId = config.wrapperId;
        this.onAdded = config.onAdded || null;

        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById(this.wrapperId);
            const results = document.getElementById(this.resultsId);
            if (!wrapper || !results) return;
            if (!wrapper.contains(e.target)) {
                this.closeResults();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeResults();
                DramaPosterPreview.close();
                DramaPreview.close();
            }
        });
    },

    async search() {
        const input = document.getElementById(this.inputId);
        const query = input?.value.trim();
        if (!query) return;

        this.currentQuery = query;
        this.currentPage = 1;
        this.totalPages = 0;
        this.totalResults = 0;
        this.isLoading = true;
        this._destroyObserver();

        const container = document.getElementById(this.resultsId);
        container.classList.remove('hidden');
        container.innerHTML = '<div class="text-center py-6"><i class="fa-solid fa-spinner fa-spin text-xl text-slate-300"></i></div>';
        try {
            const res = await fetch(`/drama/api/search.php?q=${encodeURIComponent(query)}&page=1`);
            const json = await res.json();
            if (json.status !== 'success') {
                container.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">${_drEsc(json.message || '検索に失敗しました')}</div>`;
                return;
            }
            const results = json.data || {};
            const seriesList = results.results || [];
            this.totalPages = results.total_pages || 1;
            this.totalResults = results.total_results || 0;

            if (!seriesList.length) {
                container.innerHTML = '<div class="text-center py-4 text-slate-400 text-sm">TMDBで見つかりませんでした</div>';
                return;
            }

            seriesList.forEach(s => { this._cache[s.id] = s; DramaPreview.store(s); });

            const searchPageLink = `/drama/search.php?q=${encodeURIComponent(query)}`;
            const headerHtml = this.totalResults > seriesList.length
                ? `<div class="flex items-center justify-between px-4 py-2 bg-slate-50 border-b border-slate-100 rounded-t-xl"><span class="text-[11px] text-slate-400">${this.totalResults.toLocaleString()} 件ヒット</span><a href="${searchPageLink}" class="text-[11px] font-bold text-violet-500 hover:underline">すべての検索結果を見る <i class="fa-solid fa-arrow-right text-[9px] ml-0.5"></i></a></div>`
                : '';
            const listHtml = seriesList.map(s => this.renderResult(s)).join('');
            const sentinelHtml = this.currentPage < this.totalPages
                ? `<div id="${this.resultsId}_sentinel" class="flex items-center justify-center py-3 text-slate-400 text-xs gap-2"><i class="fa-solid fa-angles-down text-[10px]"></i>スクロールで続きを表示</div>`
                : '';
            container.innerHTML = `${headerHtml}<div id="${this.resultsId}_list">${listHtml}</div>${sentinelHtml}`;
            this._initObserver();
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="text-center py-6 text-red-500 text-sm">エラーが発生しました</div>';
        } finally {
            this.isLoading = false;
        }
    },

    async loadMore() {
        if (this.isLoading || this.currentPage >= this.totalPages) return;
        this.isLoading = true;
        this.currentPage++;

        const sentinel = document.getElementById(`${this.resultsId}_sentinel`);
        if (sentinel) sentinel.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-slate-300"></i>';

        try {
            const res = await fetch(`/drama/api/search.php?q=${encodeURIComponent(this.currentQuery)}&page=${this.currentPage}`);
            const json = await res.json();
            if (json.status === 'success') {
                const results = json.data || {};
                const seriesList = results.results || [];
                seriesList.forEach(s => { this._cache[s.id] = s; DramaPreview.store(s); });
                const listEl = document.getElementById(`${this.resultsId}_list`);
                if (listEl) listEl.insertAdjacentHTML('beforeend', seriesList.map(s => this.renderResult(s)).join(''));
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
        const sentinel = document.getElementById(`${this.resultsId}_sentinel`);
        if (!sentinel) return;
        const container = document.getElementById(this.resultsId);
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
    },

    renderResult(s) {
        const imgPath = s.poster_path || s.backdrop_path || '';
        const imgUrl = imgPath ? ('https://image.tmdb.org/t/p/w185' + imgPath) : '';
        const safeImgUrl = imgUrl ? _drEsc(imgUrl) : '';
        const year = (s.first_air_date || '').substring(0, 4);
        const rating = s.vote_average ? Number(s.vote_average).toFixed(1) : '';
        const seasons = s.number_of_seasons ? `${s.number_of_seasons}期` : '';
        const episodes = s.number_of_episodes ? `${s.number_of_episodes}話` : '';
        const userStatus = s.user_status || '';

        this._cache[s.id] = s;

        let statusBadge = '';
        const labels = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
        if (userStatus && labels[userStatus]) {
            const label = labels[userStatus];
            const color = userStatus === 'watched' ? 'green' : (userStatus === 'watching' ? 'sky' : 'amber');
            statusBadge = `<span class="text-[11px] font-bold text-${color}-600 bg-${color}-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>${_drEsc(label)}済</span>`;
        }

        let actionsHtml = '';
        if (!userStatus) {
            actionsHtml = `
                <button onclick="event.stopPropagation(); DramaSearch.addSeries(${s.id}, 'wanna_watch', this)" class="w-8 h-8 flex items-center justify-center rounded-full bg-violet-500 text-white text-xs shadow-sm" title="見たい">
                    <i class="fa-solid fa-bookmark"></i>
                </button>
                <button onclick="event.stopPropagation(); DramaSearch.addSeries(${s.id}, 'watching', this)" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-500 text-xs bg-white hover:bg-slate-50 transition shadow-sm" title="見てる">
                    <i class="fa-solid fa-play"></i>
                </button>
                <button onclick="event.stopPropagation(); DramaSearch.addSeries(${s.id}, 'watched', this)" class="w-8 h-8 flex items-center justifycenter rounded-full border border-slate-200 text-slate-500 text-xs bg-white hover:bg-slate-50 transition shadow-sm" title="見た">
                    <i class="fa-solid fa-check"></i>
                </button>`;
        }

        const poster = imgUrl
            ? `<img src="${safeImgUrl}" alt="" class="w-10 h-[60px] object-cover rounded-lg shrink-0 cursor-zoom-in" loading="lazy" onclick="event.stopPropagation(); DramaPosterPreview.open('${safeImgUrl}')" onerror="var d=document.createElement('div');d.className='w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0';d.innerHTML='<i class=\\'fa-solid fa-clapperboard text-slate-400 text-xs\\'></i>';this.parentNode.replaceChild(d,this)">`
            : `<div class="w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-clapperboard text-slate-400 text-xs"></i></div>`;

        return `
            <div class="flex items-center gap-3 px-4 py-2.5 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition cursor-pointer" data-drama-id="${s.id}" onclick="DramaSearch.openPreview(${s.id})">
                ${poster}
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-slate-800 line-clamp-1">${_drEsc(s.name || s.title || '')}</h4>
                    <div class="flex items-center gap-2 text-[11px] text-slate-400">
                        ${year ? `<span>${_drEsc(year)}年</span>` : ''}
                        ${rating ? `<span class="text-amber-500"><i class="fa-solid fa-star text-[9px]"></i> ${_drEsc(rating)}</span>` : ''}
                        ${(seasons || episodes) ? `<span class="truncate">${_drEsc([seasons, episodes].filter(Boolean).join(' / '))}</span>` : ''}
                    </div>
                </div>
                <div class="shrink-0 flex items-center gap-1.5 dr-search-actions">
                    ${statusBadge || actionsHtml}
                </div>
            </div>`;
    },

    openPreview(id) {
        const s = this._cache[id];
        if (!s) return;
        if (typeof DramaPreview !== 'undefined' && DramaPreview && typeof DramaPreview.open === 'function') {
            DramaPreview.open(s);
        }
    },

    async addSeries(tmdbId, status, btn) {
        btn.disabled = true;
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        try {
            const result = await App.post('/drama/api/add.php', { tmdb_id: tmdbId, status: status });
            if (result.status === 'success') {
                App.toast(status === 'watched' ? '見たに追加しました' : (status === 'watching' ? '見てるに追加しました' : '見たいに追加しました'));
                const row = btn.closest('[data-drama-id]');
                if (row) {
                    const labelMap = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
                    const color = status === 'watched' ? 'green' : (status === 'watching' ? 'sky' : 'amber');
                    const actions = row.querySelector('.dr-search-actions');
                    if (actions) {
                        actions.innerHTML = `<span class="text-[11px] font-bold text-${color}-600 bg-${color}-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>${_drEsc(labelMap[status])}済</span>`;
                    }
                }
                if (this.onAdded) this.onAdded(status, tmdbId);
            } else {
                App.toast(result.message || '追加に失敗しました');
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        } catch (e) {
            console.error(e);
            App.toast('エラーが発生しました');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    },

    closeResults() {
        this._destroyObserver();
        const el = document.getElementById(this.resultsId);
        if (el) el.classList.add('hidden');
    }
};

window.DramaPosterPreview = DramaPosterPreview;
window.DramaPreview = DramaPreview;
window.DramaSearch = DramaSearch;
</script>

