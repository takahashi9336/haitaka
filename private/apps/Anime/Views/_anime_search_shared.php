<div id="animePosterPreview" class="fixed inset-0 bg-black/80 z-[70] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); AnimePosterPreview.close()">
    <button class="absolute top-4 right-4 text-white/70 hover:text-white transition text-2xl" onclick="AnimePosterPreview.close()"><i class="fa-solid fa-xmark"></i></button>
    <img id="animePosterPreviewImg" src="" alt="" class="max-w-[90vw] max-h-[85vh] rounded-xl shadow-2xl object-contain transition-transform duration-200 scale-95" onclick="event.stopPropagation()">
</div>

<div id="animePreview" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); AnimePreview.close()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-[95vw] max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div id="animePreviewContent" class="p-6"></div>
    </div>
</div>

<script>
(function() {
    function _esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

    const AnimePosterPreview = {
        open(imgUrl) {
            if (!imgUrl) return;
            const img = document.getElementById('animePosterPreviewImg');
            img.src = imgUrl;
            const el = document.getElementById('animePosterPreview');
            el.classList.remove('pointer-events-none', 'opacity-0');
            el.classList.add('pointer-events-auto', 'opacity-100');
            img.classList.remove('scale-95');
            img.classList.add('scale-100');
        },
        close() {
            const el = document.getElementById('animePosterPreview');
            const img = document.getElementById('animePosterPreviewImg');
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

    const AnimePreview = {
        _cache: {},

        store(work) {
            if (work && work.id) this._cache[work.id] = work;
        },

        openById(id) {
            const w = this._cache[id];
            if (!w) return;
            this.open(w);
        },

        open(w) {
            this.store(w);
            const rawUrl = w.images?.recommended_url || w.images?.facebook?.og_image_url || '';
            const imgUrl = rawUrl ? rawUrl.replace(/^http:\/\//i, 'https://') : '';
            const season = w.season_name_text || w.season_name || '';
            const episodes = w.episodes_count ? `${w.episodes_count}話` : '';
            const mediaText = w.media_text || '';
            const detailUrl = `/anime/detail.php?id=${w.id}`;

            const poster = imgUrl
                ? `<img src="${_esc(imgUrl)}" class="w-40 md:w-56 aspect-[16/9] rounded-xl shadow-lg cursor-pointer shrink-0 object-cover" onclick="AnimePosterPreview.open('${_esc(imgUrl)}')">`
                : `<div class="w-40 md:w-56 aspect-[16/9] bg-slate-100 rounded-xl flex items-center justify-center shrink-0"><i class="fa-solid fa-tv text-slate-400 text-xl"></i></div>`;

            const content = document.getElementById('animePreviewContent');
            content.innerHTML = `
                <div class="flex items-start gap-4 mb-4">
                    ${poster}
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg font-black text-slate-800 leading-tight mb-1">${_esc(w.title || '')}</h2>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-2">
                            ${season ? `<span><i class="fa-solid fa-calendar mr-0.5"></i>${_esc(season)}</span>` : ''}
                            ${episodes ? `<span><i class="fa-solid fa-list-ol mr-0.5"></i>${_esc(episodes)}</span>` : ''}
                            ${mediaText ? `<span class="px-2 py-0.5 rounded-full bg-slate-100 text-[11px] font-bold text-slate-500">${_esc(mediaText)}</span>` : ''}
                        </div>
                        <a href="${detailUrl}" class="inline-flex items-center gap-1 text-[11px] text-sky-500 font-bold hover:text-sky-600">
                            <i class="fa-solid fa-arrow-right"></i>詳細ページを開く
                        </a>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-4" id="animePreviewActions_${w.id}">
                    <button onclick="AnimePreview.addStatus(${w.id}, 'wanna_watch')" class="flex-1 px-4 py-2.5 anime-theme-btn text-white text-sm font-bold rounded-xl transition">
                        <i class="fa-solid fa-bookmark mr-1.5"></i>見たいリストに追加
                    </button>
                    <button onclick="AnimePreview.addStatus(${w.id}, 'watching')" class="flex-1 px-4 py-2.5 bg-sky-100 hover:bg-sky-200 text-sky-700 text-sm font-bold rounded-xl transition">
                        <i class="fa-solid fa-play mr-1.5"></i>見てるに追加
                    </button>
                    <button onclick="AnimePreview.addStatus(${w.id}, 'watched')" class="flex-1 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl transition">
                        <i class="fa-solid fa-check mr-1.5"></i>見たに追加
                    </button>
                </div>
                <div class="flex gap-2">
                    <button onclick="AnimePreview.close()" class="flex-1 px-4 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-xl hover:bg-slate-50 transition">
                        閉じる
                    </button>
                </div>
            `;

            const el = document.getElementById('animePreview');
            el.classList.remove('pointer-events-none', 'opacity-0');
            el.classList.add('pointer-events-auto', 'opacity-100');
        },

        close() {
            const el = document.getElementById('animePreview');
            el.classList.add('opacity-0');
            el.classList.remove('opacity-100');
            setTimeout(() => {
                el.classList.add('pointer-events-none');
                el.classList.remove('pointer-events-auto');
            }, 200);
        },

        async addStatus(workId, kind) {
            const actionsEl = document.getElementById(`animePreviewActions_${workId}`);
            if (actionsEl) actionsEl.innerHTML = '<div class="w-full text-center py-2"><i class="fa-solid fa-spinner fa-spin text-slate-400"></i></div>';
            try {
                const result = await App.post('/anime/api/set_status.php', { work_id: workId, kind: kind });
                if (result.status === 'success') {
                    const labels = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
                    App.toast(labels[kind] + 'に追加しました');
                    if (actionsEl) {
                        const label = labels[kind] || kind;
                        actionsEl.innerHTML = `<div class="w-full text-center py-2"><span class="inline-flex items-center gap-1 text-sky-600 font-bold text-sm bg-sky-50 px-3 py-1.5 rounded-full"><i class="fa-solid fa-check"></i>${label}済み</span></div>`;
                    }
                    AnimePreview.close();
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

    const AnimeSearch = {
        inputId: null,
        resultsId: null,
        wrapperId: null,
        onAddedCallback: null,
        currentQuery: '',
        currentPage: 0,
        totalCount: 0,
        isLoading: false,
        _cache: {},

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
                if (e.key === 'Escape') this.closeResults();
            });
        },

        async search() {
            const input = document.getElementById(this.inputId);
            const query = input?.value.trim();
            if (!query) return;

            this.currentQuery = query;
            this.currentPage = 1;
            this.totalCount = 0;
            this.isLoading = true;

            const container = document.getElementById(this.resultsId);
            container.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-6"><i class="fa-solid fa-spinner fa-spin text-xl text-slate-300"></i></div>';
            try {
                const res = await fetch(`/anime/api/search.php?q=${encodeURIComponent(query)}&page=1`);
                const json = await res.json();
                if (json.status !== 'success') {
                    container.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">${_esc(json.message || 'エラー')}</div>`;
                    return;
                }
                const works = json.data || [];
                this.totalCount = json.total_count || 0;
                if (works.length === 0) {
                    container.innerHTML = '<div class="text-center py-6 text-slate-400 text-sm">Annictで見つかりませんでした</div>';
                    return;
                }
                const headerHtml = this.totalCount > works.length
                    ? `<div class="flex items-center justify-between px-4 py-2 bg-slate-50 border-b border-slate-100 rounded-t-xl"><span class="text-[11px] text-slate-400">${this.totalCount} 件ヒット</span></div>`
                    : '';
                const listHtml = works.map(w => this.renderResult(w)).join('');
                container.innerHTML = headerHtml + listHtml;
            } catch (e) {
                console.error(e);
                container.innerHTML = '<div class="text-center py-6 text-red-500 text-sm">エラーが発生しました</div>';
            } finally {
                this.isLoading = false;
            }
        },

        renderResult(w) {
            const rawUrl = w.images?.recommended_url || w.images?.facebook?.og_image_url || '';
            const imgUrl = rawUrl ? rawUrl.replace(/^http:\/\//i, 'https://') : '';
            const safeImgUrl = imgUrl ? _esc(imgUrl) : '';
            const poster = imgUrl
                ? `<img src="${safeImgUrl}" alt="" class="w-10 h-[60px] object-cover rounded-lg shrink-0 cursor-zoom-in" loading="lazy" referrerpolicy="no-referrer" onclick="event.stopPropagation(); AnimePosterPreview.open('${safeImgUrl}')" onerror="var d=document.createElement('div');d.className='w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0';d.innerHTML='<i class=\\'fa-solid fa-tv text-slate-400 text-xs\\'></i>';this.parentNode.replaceChild(d,this)">`
                : `<div class="w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-tv text-slate-400 text-xs"></i></div>`;
            const season = w.season_name_text || w.season_name || '';
            const episodes = w.episodes_count ? `${w.episodes_count}話` : '';
            const userStatus = w.user_status || (w.status && w.status.kind) || '';

            this._cache[w.id] = w;

            let actionsHtml;
            const labelMap = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
            if (userStatus && labelMap[userStatus]) {
                const label = labelMap[userStatus];
                actionsHtml = `<span class="text-[11px] font-bold text-sky-600 bg-sky-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>${_esc(label)}済</span>`;
            } else {
                actionsHtml = `
                <button onclick="event.stopPropagation(); AnimeSearch.addWork(${w.id}, 'wanna_watch', this)" class="w-8 h-8 flex items-center justify-center rounded-full anime-theme-btn text-white text-xs shadow-sm" title="見たい">
                    <i class="fa-solid fa-bookmark"></i>
                </button>
                <button onclick="event.stopPropagation(); AnimeSearch.addWork(${w.id}, 'watching', this)" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-500 text-xs bg-white hover:bg-slate-50 transition shadow-sm" title="見てる">
                    <i class="fa-solid fa-play"></i>
                </button>
                <button onclick="event.stopPropagation(); AnimeSearch.addWork(${w.id}, 'watched', this)" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-500 text-xs bg-white hover:bg-slate-50 transition shadow-sm" title="見た">
                    <i class="fa-solid fa-check"></i>
                </button>`;
            }

            return `<div class="flex items-center gap-3 px-4 py-2.5 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition cursor-pointer" data-work-id="${w.id}" onclick="AnimeSearch.openPreview(${w.id})">
                ${poster}
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-slate-800 line-clamp-1">${_esc(w.title)}</h4>
                    <div class="flex items-center gap-2 text-[11px] text-slate-400">${_esc(season)} ${episodes ? episodes : ''}</div>
                </div>
                <div class="shrink-0 flex items-center gap-1.5 anime-search-actions">
                    ${actionsHtml}
                </div>
            </div>`;
        },

        openPreview(id) {
            const w = this._cache[id];
            if (!w) return;
            if (typeof AnimePreview !== 'undefined' && AnimePreview && typeof AnimePreview.open === 'function') {
                AnimePreview.open(w);
            }
        },

        async addWork(workId, kind, btn) {
            btn.disabled = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            try {
                const result = await App.post('/anime/api/set_status.php', { work_id: workId, kind: kind });
                if (result.status === 'success') {
                    const labels = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
                    App.toast(labels[kind] + 'に追加しました');
                    const row = btn.closest('[data-work-id]');
                    const actionsDiv = row?.querySelector('.anime-search-actions');
                    if (actionsDiv) {
                        const label = labels[kind] || kind;
                        actionsDiv.innerHTML = `<span class="text-[11px] font-bold text-sky-600 bg-sky-50 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>${label}済</span>`;
                    }
                    if (this.onAddedCallback) this.onAddedCallback(kind, workId);
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
            const el = document.getElementById(this.resultsId);
            if (el) el.classList.add('hidden');
        }
    };

    window.AnimePosterPreview = AnimePosterPreview;
    window.AnimePreview = AnimePreview;
    window.AnimeSearch = AnimeSearch;
})();
</script>
