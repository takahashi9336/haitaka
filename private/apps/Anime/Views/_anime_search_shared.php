<script>
(function() {
    function _esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

    const AnimeSearch = {
        inputId: null,
        resultsId: null,
        wrapperId: null,
        onAddedCallback: null,
        currentQuery: '',
        currentPage: 0,
        totalCount: 0,
        isLoading: false,

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
            const poster = imgUrl
                ? `<img src="${_esc(imgUrl)}" alt="" class="w-10 h-[60px] object-cover rounded-lg shrink-0" loading="lazy" referrerpolicy="no-referrer" onerror="var d=document.createElement('div');d.className='w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0';d.innerHTML='<i class=\\'fa-solid fa-tv text-slate-400 text-xs\\'></i>';this.parentNode.replaceChild(d,this)">`
                : `<div class="w-10 h-[60px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-tv text-slate-400 text-xs"></i></div>`;
            const season = w.season_name_text || w.season_name || '';
            const episodes = w.episodes_count ? `${w.episodes_count}話` : '';

            const actionsHtml = `
                <button onclick="event.stopPropagation(); AnimeSearch.addWork(${w.id}, 'wanna_watch', this)" class="text-[11px] font-bold text-white px-2.5 py-1.5 rounded-lg anime-theme-btn transition whitespace-nowrap"><i class="fa-solid fa-bookmark mr-0.5"></i>見たい</button>
                <button onclick="event.stopPropagation(); AnimeSearch.addWork(${w.id}, 'watching', this)" class="text-[11px] font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap"><i class="fa-solid fa-play mr-0.5"></i>見てる</button>
                <button onclick="event.stopPropagation(); AnimeSearch.addWork(${w.id}, 'watched', this)" class="text-[11px] font-bold text-slate-500 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition whitespace-nowrap"><i class="fa-solid fa-check mr-0.5"></i>見た</button>`;

            return `<div class="flex items-center gap-3 px-4 py-2.5 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition" data-work-id="${w.id}">
                ${poster}
                <a href="/anime/detail.php?id=${w.id}" class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-slate-800 line-clamp-1">${_esc(w.title)}</h4>
                    <div class="flex items-center gap-2 text-[11px] text-slate-400">${_esc(season)} ${episodes ? episodes : ''}</div>
                </a>
                <div class="shrink-0 flex items-center gap-1.5 anime-search-actions">
                    ${actionsHtml}
                </div>
            </div>`;
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

    window.AnimeSearch = AnimeSearch;
})();
</script>
