<!-- 画像プレビューモーダル -->
<div id="posterPreview" class="fixed inset-0 bg-black/80 z-[70] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); PosterPreview.close()">
    <button class="absolute top-4 right-4 text-white/70 hover:text-white transition text-2xl" onclick="PosterPreview.close()"><i class="fa-solid fa-xmark"></i></button>
    <img id="posterPreviewImg" src="" alt="" class="max-w-[90vw] max-h-[85vh] rounded-xl shadow-2xl object-contain transition-transform duration-200 scale-95" onclick="event.stopPropagation()">
</div>

<!-- 映画プレビューモーダル -->
<div id="moviePreview" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="MoviePreview.close()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-[95vw] max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div id="moviePreviewContent" class="p-6"></div>
    </div>
</div>

<script>
const GENRE_MAP = {28:'アクション',12:'アドベンチャー',16:'アニメーション',35:'コメディ',80:'クライム',99:'ドキュメンタリー',18:'ドラマ',10751:'ファミリー',14:'ファンタジー',36:'ヒストリー',27:'ホラー',10402:'ミュージック',9648:'ミステリー',10749:'ロマンス',878:'SF',10770:'テレビ映画',53:'スリラー',10752:'戦争',37:'西部劇'};

function _esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

// ─── ポスタープレビュー ───
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
        setTimeout(() => { el.classList.add('pointer-events-none'); el.classList.remove('pointer-events-auto'); img.src = ''; }, 200);
    }
};

// ─── 映画プレビューモーダル ───
const MoviePreview = {
    cache: {},
    onAdded: null,

    storeMovie(m) {
        if (m && m.id) this.cache[m.id] = m;
    },

    open(tmdbData) {
        const m = tmdbData;
        this.storeMovie(m);
        const hasExtra = !!(m._extra);
        const extra = m._extra || {};

        const poster = m.poster_path
            ? `<img src="https://image.tmdb.org/t/p/w342${m.poster_path}" class="w-28 h-auto rounded-xl shadow-lg cursor-pointer shrink-0" onclick="PosterPreview.open('${m.poster_path}')">`
            : '';
        const year = m.release_date ? m.release_date.substring(0, 4) + '年' : '';
        const rating = m.vote_average ? Number(m.vote_average).toFixed(1) : '';
        const genres = (m.genre_ids || []).map(id => GENRE_MAP[id]).filter(Boolean);
        const genreHtml = genres.map(g => `<span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${_esc(g)}</span>`).join('');
        const googleUrl = 'https://www.google.com/search?q=' + encodeURIComponent((m.title || '') + ' 映画');

        let runtimeHtml = '';
        if (hasExtra && extra.runtime) {
            const h = Math.floor(extra.runtime / 60);
            const mins = extra.runtime % 60;
            runtimeHtml = `<i class="fa-solid fa-clock mr-0.5"></i>${h ? h + '時間' : ''}${mins}分`;
        }

        let taglineHtml = '';
        if (hasExtra && extra.tagline) {
            taglineHtml = `<p class="text-xs italic text-slate-400 mb-3">${_esc(extra.tagline)}</p>`;
        }

        let providersHtml;
        if (hasExtra) {
            providersHtml = this.buildProvidersHtml(extra.providers);
        } else {
            providersHtml = '<div class="flex items-center gap-2 text-slate-400 text-xs py-2"><i class="fa-solid fa-spinner fa-spin"></i>配信情報を取得中...</div>';
        }

        const isRegistered = m.user_status === 'watchlist' || m.user_status === 'watched';
        let actionsHtml;
        if (isRegistered) {
            const detailUrl = `/movie/detail.php?id=${m.user_movie_id}`;
            const badge = m.user_status === 'watched'
                ? '<span class="text-green-600 font-bold text-sm"><i class="fa-solid fa-check-circle mr-1"></i>見た済み</span>'
                : '<span class="text-blue-600 font-bold text-sm"><i class="fa-solid fa-bookmark mr-1"></i>見たい済み</span>';
            actionsHtml = `
                <div class="flex items-center justify-between mb-3">${badge}</div>
                <a href="${detailUrl}" class="block w-full text-center px-4 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-xl transition mb-2">
                    <i class="fa-solid fa-arrow-right mr-1.5"></i>映画の詳細を見る
                </a>`;
        } else {
            actionsHtml = `
                <div class="flex flex-wrap gap-2 mb-2" id="mpActions_${m.id}">
                    <button onclick="MoviePreview.addToList(${m.id}, 'watchlist')" class="flex-1 px-4 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-xl transition">
                        <i class="fa-solid fa-bookmark mr-1.5"></i>見たいリストに追加
                    </button>
                    <button onclick="MoviePreview.addToList(${m.id}, 'watched')" class="flex-1 px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white text-sm font-bold rounded-xl transition">
                        <i class="fa-solid fa-eye mr-1.5"></i>見たリストに追加
                    </button>
                </div>`;
        }

        const content = document.getElementById('moviePreviewContent');
        content.innerHTML = `
            <div class="flex items-start gap-4 mb-4">
                ${poster}
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-black text-slate-800 leading-tight mb-1">${_esc(m.title)}</h2>
                    ${m.original_title && m.original_title !== m.title ? `<p class="text-xs text-slate-400 mb-2">${_esc(m.original_title)}</p>` : ''}
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-3">
                        ${year ? `<span><i class="fa-solid fa-calendar mr-0.5"></i>${year}</span>` : ''}
                        ${rating ? `<span class="text-amber-500 font-bold"><i class="fa-solid fa-star mr-0.5"></i>${rating}</span>` : ''}
                        <span id="mpRuntime_${m.id}">${runtimeHtml}</span>
                    </div>
                    ${genreHtml ? `<div class="flex flex-wrap gap-1 mb-3">${genreHtml}</div>` : ''}
                </div>
            </div>
            <div id="mpTagline_${m.id}">${taglineHtml}</div>
            ${m.overview ? `<p class="text-sm text-slate-600 leading-relaxed mb-4">${_esc(m.overview)}</p>` : ''}
            <div id="mpProviders_${m.id}" class="mb-4">${providersHtml}</div>
            ${actionsHtml}
            <div class="flex gap-2">
                <a href="${googleUrl}" target="_blank" rel="noopener" class="flex-1 text-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold rounded-xl transition">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>Google検索
                </a>
                <button onclick="MoviePreview.close()" class="px-4 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-xl hover:bg-slate-50 transition">
                    閉じる
                </button>
            </div>`;

        const el = document.getElementById('moviePreview');
        el.classList.remove('pointer-events-none', 'opacity-0');
        el.classList.add('pointer-events-auto', 'opacity-100');

        if (!hasExtra) this.loadExtra(m.id);
    },

    async loadExtra(tmdbId) {
        const cached = this.cache[tmdbId];
        if (cached && cached._extra) {
            this.renderExtra(tmdbId, cached._extra);
            return;
        }
        try {
            const res = await fetch(`/movie/api/tmdb_detail.php?tmdb_id=${tmdbId}`);
            const json = await res.json();
            if (json.status === 'success') {
                if (this.cache[tmdbId]) this.cache[tmdbId]._extra = json.data;
                this.renderExtra(tmdbId, json.data);
            } else {
                const el = document.getElementById(`mpProviders_${tmdbId}`);
                if (el) el.innerHTML = '';
            }
        } catch (e) {
            console.error(e);
            const el = document.getElementById(`mpProviders_${tmdbId}`);
            if (el) el.innerHTML = '';
        }
    },

    buildProvidersHtml(wp) {
        if (!wp || (!wp.flatrate?.length && !wp.rent?.length && !wp.buy?.length)) {
            return '<p class="text-xs text-slate-400 mb-1"><i class="fa-solid fa-tv mr-1"></i>日本での配信情報がありません</p>';
        }
        const sec = (items, label) => {
            if (!items?.length) return '';
            let s = `<p class="text-[10px] font-bold text-slate-400 mb-1.5">${label}</p><div class="flex flex-wrap gap-1.5 mb-2">`;
            items.forEach(p => {
                const logo = p.logo_path ? `<img src="https://image.tmdb.org/t/p/w45${p.logo_path}" class="w-4 h-4 rounded" loading="lazy">` : '';
                s += `<div class="flex items-center gap-1 bg-white rounded-lg px-2 py-1 border border-slate-100" title="${_esc(p.provider_name)}">${logo}<span class="text-[11px] font-bold text-slate-600">${_esc(p.provider_name)}</span></div>`;
            });
            return s + '</div>';
        };
        let html = '<div class="border border-slate-100 rounded-xl p-3 bg-slate-50/50">';
        html += '<p class="text-[11px] font-bold text-slate-400 mb-2"><i class="fa-solid fa-tv mr-1"></i>配信サービス</p>';
        html += sec(wp.flatrate, '見放題');
        html += sec(wp.rent, 'レンタル');
        html += sec(wp.buy, '購入');
        if (wp.link) {
            html += `<a href="${wp.link}" target="_blank" rel="noopener" class="text-[10px] text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-up-right-from-square mr-0.5"></i>TMDBで詳細を見る</a>`;
        }
        html += '<p class="text-[9px] text-slate-400 mt-1">配信情報: <a href="https://www.justwatch.com/" target="_blank" rel="noopener" class="underline hover:text-slate-600">JustWatch</a></p>';
        html += '</div>';
        return html;
    },

    renderExtra(tmdbId, extra) {
        const rtEl = document.getElementById(`mpRuntime_${tmdbId}`);
        if (rtEl && extra.runtime) {
            const h = Math.floor(extra.runtime / 60);
            const mins = extra.runtime % 60;
            rtEl.innerHTML = `<i class="fa-solid fa-clock mr-0.5"></i>${h ? h + '時間' : ''}${mins}分`;
        }
        const tagEl = document.getElementById(`mpTagline_${tmdbId}`);
        if (tagEl && extra.tagline) {
            tagEl.innerHTML = `<p class="text-xs italic text-slate-400 mb-3">${_esc(extra.tagline)}</p>`;
        }
        const provEl = document.getElementById(`mpProviders_${tmdbId}`);
        if (provEl) provEl.innerHTML = this.buildProvidersHtml(extra.providers);
    },

    close() {
        const el = document.getElementById('moviePreview');
        el.classList.add('opacity-0');
        el.classList.remove('opacity-100');
        setTimeout(() => { el.classList.add('pointer-events-none'); el.classList.remove('pointer-events-auto'); }, 200);
    },

    async addToList(tmdbId, status) {
        const actionsEl = document.getElementById(`mpActions_${tmdbId}`);
        if (actionsEl) actionsEl.innerHTML = '<div class="text-center py-2"><i class="fa-solid fa-spinner fa-spin text-slate-400"></i></div>';
        try {
            const result = await App.post('/movie/api/add.php', { tmdb_id: tmdbId, status: status });
            if (result.status === 'success') {
                App.toast(status === 'watched' ? '見たリストに追加しました' : '見たいリストに追加しました');
                const newId = result.data?.id || null;
                if (this.cache[tmdbId]) {
                    this.cache[tmdbId].user_status = status;
                    this.cache[tmdbId].user_movie_id = newId;
                }
                const badge = status === 'watched'
                    ? '<span class="text-green-600 font-bold text-sm"><i class="fa-solid fa-check-circle mr-1"></i>追加しました</span>'
                    : '<span class="text-blue-600 font-bold text-sm"><i class="fa-solid fa-bookmark mr-1"></i>追加しました</span>';
                if (actionsEl) actionsEl.innerHTML = `<div class="text-center py-2">${badge}</div>`;
                if (this.onAdded) this.onAdded(status, tmdbId);
            } else {
                App.toast(result.message || '追加に失敗しました');
                this.close();
            }
        } catch (e) {
            console.error(e);
            App.toast('エラーが発生しました');
            this.close();
        }
    }
};

// ─── 映画検索（共通） ───
const MovieSearch = {
    inputId: null,
    resultsId: null,
    wrapperId: null,
    onAddedCallback: null,

    init(config) {
        this.inputId = config.inputId;
        this.resultsId = config.resultsId;
        this.wrapperId = config.wrapperId;
        this.onAddedCallback = config.onAdded || null;

        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById(this.wrapperId);
            if (wrapper && !wrapper.contains(e.target)) this.closeResults();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { PosterPreview.close(); MoviePreview.close(); this.closeResults(); }
        });
    },

    async search() {
        const input = document.getElementById(this.inputId);
        const query = input?.value.trim();
        if (!query) return;
        const container = document.getElementById(this.resultsId);
        container.classList.remove('hidden');
        container.innerHTML = '<div class="text-center py-6"><i class="fa-solid fa-spinner fa-spin text-xl text-slate-300"></i></div>';
        try {
            const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(query)}`);
            const json = await res.json();
            const manualHtml = this.renderManualAdd(query);
            if (json.status !== 'success') {
                container.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">${json.message}</div>` + manualHtml;
                return;
            }
            const movies = json.data.results || [];
            if (movies.length === 0) {
                container.innerHTML = '<div class="text-center py-4 text-slate-400 text-sm">TMDBで見つかりませんでした</div>' + manualHtml;
                return;
            }
            movies.slice(0, 10).forEach(m => MoviePreview.storeMovie(m));
            container.innerHTML = movies.slice(0, 10).map(m => this.renderResult(m)).join('') + manualHtml;
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="text-center py-6 text-red-500 text-sm">エラーが発生しました</div>';
        }
    },

    renderManualAdd(query) {
        const escaped = _esc(query);
        return `<div class="border-t border-dashed border-slate-200 px-4 py-3 bg-slate-50/50 rounded-b-xl">
            <p class="text-[11px] text-slate-400 mb-2"><i class="fa-solid fa-pen mr-1"></i>TMDBに無い場合、タイトルだけで追加できます</p>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-700 flex-1 truncate">「${escaped}」</span>
                <button onclick="MovieSearch.addManual('watchlist')" class="text-[11px] font-bold text-white px-2.5 py-1.5 rounded-lg mv-theme-btn transition whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たいに追加</button>
                <button onclick="MovieSearch.addManual('watched')" class="text-[11px] font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見たに追加</button>
            </div>
        </div>`;
    },

    async addManual(status) {
        const input = document.getElementById(this.inputId);
        const query = input?.value.trim();
        if (!query) return;
        try {
            const result = await App.post('/movie/api/add_manual.php', { title: query, status });
            if (result.status === 'success') {
                App.toast(result.message);
                if (this.onAddedCallback) { this.onAddedCallback(status, null); return; }
                this.closeResults();
                input.value = '';
            } else {
                App.toast(result.message || '追加に失敗しました');
            }
        } catch (e) { console.error(e); App.toast('エラーが発生しました'); }
    },

    renderResult(m) {
        const poster = m.poster_path
            ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-10 h-[60px] object-cover rounded-lg shrink-0 cursor-pointer hover:brightness-90 transition" loading="lazy" onclick="event.stopPropagation(); PosterPreview.open('${m.poster_path}')">`
            : `<div class="w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-film text-slate-400 text-xs"></i></div>`;
        const year = m.release_date ? m.release_date.substring(0, 4) + '年' : '';
        const rating = m.vote_average ? `<i class="fa-solid fa-star text-amber-400 text-[9px]"></i> ${m.vote_average.toFixed(1)}` : '';

        let statusBadge = '';
        if (m.user_status === 'watchlist') {
            statusBadge = '<span class="text-[11px] font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい済</span>';
        } else if (m.user_status === 'watched') {
            statusBadge = '<span class="text-[11px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見た済</span>';
        }

        let actionHtml = '';
        if (!m.user_status) {
            actionHtml = `
                <button onclick="event.stopPropagation(); MovieSearch.addMovie(${m.id}, 'watchlist', this)" class="text-[11px] font-bold text-white px-2.5 py-1.5 rounded-lg mv-theme-btn transition whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい</button>
                <button onclick="event.stopPropagation(); MovieSearch.addMovie(${m.id}, 'watched', this)" class="text-[11px] font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見た</button>`;
        }

        return `<div class="flex items-center gap-3 px-4 py-2.5 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition cursor-pointer" data-tmdb-id="${m.id}" onclick="MovieSearch.openPreview(${m.id})">
            ${poster}
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-bold text-slate-800 line-clamp-1">${_esc(m.title)}</h4>
                <div class="flex items-center gap-2 text-[11px] text-slate-400">${year} ${rating}</div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5">
                ${statusBadge}
                ${actionHtml}
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
                const newId = result.data?.id || null;
                if (MoviePreview.cache[tmdbId]) {
                    MoviePreview.cache[tmdbId].user_status = status;
                    MoviePreview.cache[tmdbId].user_movie_id = newId;
                }
                const row = btn.closest('[data-tmdb-id]');
                const actionDiv = row.querySelector('.shrink-0.flex');
                if (status === 'watchlist') {
                    actionDiv.innerHTML = '<span class="text-[11px] font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい済</span>';
                } else {
                    actionDiv.innerHTML = '<span class="text-[11px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見た済</span>';
                }
                if (this.onAddedCallback) this.onAddedCallback(status, tmdbId);
            } else {
                App.toast(result.message || '追加に失敗しました');
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        } catch (e) { console.error(e); btn.disabled = false; btn.innerHTML = origHtml; }
    },

    closeResults() {
        const el = document.getElementById(this.resultsId);
        if (el) el.classList.add('hidden');
    }
};
</script>
