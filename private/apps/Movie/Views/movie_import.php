<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画一括登録 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --mv-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .mv-theme-btn { background-color: var(--mv-theme); }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        .mv-theme-text { color: var(--mv-theme); }
        .mv-theme-border { border-color: var(--mv-theme); }
        .mv-theme-ring:focus { --tw-ring-color: var(--mv-theme); }
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
        .poster-placeholder { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); }

        .result-row { transition: background-color 0.15s; }
        .result-row:hover { background-color: #f8fafc; }
        .result-row.excluded { opacity: 0.4; }

        .alt-modal { opacity: 0; pointer-events: none; transition: opacity 0.2s; }
        .alt-modal.active { opacity: 1; pointer-events: auto; }

        .progress-bar { transition: width 0.3s ease; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/movie/" class="flex items-center gap-2 text-slate-500 hover:text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span class="text-sm font-bold">映画リスト</span>
                </a>
                <span class="text-slate-300">|</span>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white shadow <?= $headerIconBg ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-file-import text-xs"></i>
                    </div>
                    <h1 class="font-black text-slate-700 text-lg tracking-tighter">一括登録</h1>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto px-6 md:px-12 py-6 space-y-6">

                <?php if (!$tmdbConfigured): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                    TMDB APIキーが設定されていません。<code class="bg-amber-100 px-1 rounded">private/.env</code> に <code class="bg-amber-100 px-1 rounded">TMDB_API_KEY=xxx</code> を追加してください。                    <br>仮登録のみ利用可能です。                </div>
                <?php endif; ?>

                <!-- STEP 1: タイトル入力:-->
                <div id="step1" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-bold text-slate-800 mb-1">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mv-theme-btn text-white text-xs font-bold mr-2">1</span>
                        映画タイトルを入力                    </h2>
                    <p class="text-xs text-slate-400 mb-4 ml-8">1行に1タイトルずつ入力してください</p>

                    <textarea id="titleInput" rows="10" placeholder="インターステラー&#10;ショーシャンクの空に&#10;千と千尋の神隠し&#10;The Dark Knight&#10;..."
                              class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] focus:border-transparent resize-y font-mono leading-relaxed"></textarea>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mt-4 gap-3">
                        <p class="text-xs text-slate-400"><span id="lineCount">0</span> 件のタイトル</p>
                        <div class="flex items-center gap-3">
                            <!-- ② 目立つプルダウン -->
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                                <i class="fa-solid fa-folder-open text-sm mv-theme-text"></i>
                                <select id="defaultStatus" class="bg-transparent text-sm font-bold text-slate-700 focus:outline-none cursor-pointer pr-1">
                                    <option value="watchlist" <?= ($_GET['status'] ?? 'watchlist') === 'watchlist' ? 'selected' : '' ?>>
                                        見たいリストに追加
                                    </option>
                                    <option value="watched" <?= ($_GET['status'] ?? '') === 'watched' ? 'selected' : '' ?>>
                                        見たリストに追加
                                    </option>
                                </select>
                            </div>
                            <button onclick="BulkImport.startSearch()" class="px-5 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition" id="searchBtn">
                                <i class="fa-solid fa-magnifying-glass mr-1.5"></i>TMDB検索プレビュー
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: 検索プログレス -->
                <div id="step2" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 hidden">
                    <h2 class="text-base font-bold text-slate-800 mb-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mv-theme-btn text-white text-xs font-bold mr-2">2</span>
                        TMDB検索中...
                    </h2>
                    <div class="flex items-center gap-4 mb-3">
                        <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                            <div id="progressBar" class="progress-bar h-full rounded-full" style="width: 0%; background-color: var(--mv-theme);"></div>
                        </div>
                        <span id="progressText" class="text-sm font-bold text-slate-500 shrink-0">0 / 0</span>
                    </div>
                    <p id="progressCurrent" class="text-xs text-slate-400"></p>
                </div>

                <!-- STEP 3: プレビュー結果 -->
                <div id="step3" class="hidden space-y-4">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-base font-bold text-slate-800">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mv-theme-btn text-white text-xs font-bold mr-2">3</span>
                                検索結果プレビュー
                            </h2>
                            <div class="flex items-center gap-3 text-xs">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>TMDB発要E<span id="countFound" class="font-bold">0</span></span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>仮登録 <span id="countPlaceholder" class="font-bold">0</span></span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>登録済<span id="countExisting" class="font-bold">0</span></span>
                            </div>
                        </div>

                        <div id="previewList" class="divide-y divide-slate-100"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button onclick="BulkImport.reset()" class="px-4 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                            <i class="fa-solid fa-arrow-rotate-left mr-1"></i>めEｊ直い                        </button>
                        <button onclick="BulkImport.registerAll()" id="registerBtn" class="px-6 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-check mr-1.5"></i>チェック済みを一括登録
                        </button>
                    </div>
                </div>

                <!-- STEP 4: 完亁E-->
                <div id="step4" class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center hidden">
                    <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-check text-3xl text-green-500"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 mb-2" id="doneTitle">登録完亁E</h2>
                    <p class="text-sm text-slate-500 mb-6" id="doneMessage"></p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="/movie/" class="px-5 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-list mr-1"></i>映画リストへ
                        </a>
                        <button onclick="BulkImport.reset()" class="px-5 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                            続けて登録
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- 候補選択モーダル -->
    <div id="altModal" class="alt-modal fixed inset-0 bg-black/50 z-50 flex items-center justify-center" onclick="AltModal.close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col" onclick="event.stopPropagation()">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">候補を選択</h3>
                <button onclick="AltModal.close()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="altList" class="flex-1 overflow-y-auto p-4 space-y-2"></div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const tmdbConfigured = <?= $tmdbConfigured ? 'true' : 'false' ?>;

        const titleInput = document.getElementById('titleInput');
        titleInput.addEventListener('input', () => {
            const lines = titleInput.value.split('\n').filter(l => l.trim() !== '');
            document.getElementById('lineCount').textContent = lines.length;
        });

        const BulkImport = {
            results: [],

            getTitles() {
                return titleInput.value.split('\n').map(l => l.trim()).filter(l => l !== '');
            },

            async startSearch() {
                const titles = this.getTitles();
                if (titles.length === 0) {
                    App.toast('タイトルを入力してください');
                    return;
                }

                this.results = [];
                document.getElementById('step2').classList.remove('hidden');
                document.getElementById('step3').classList.add('hidden');
                document.getElementById('step4').classList.add('hidden');
                document.getElementById('searchBtn').disabled = true;

                const total = titles.length;

                for (let i = 0; i < titles.length; i++) {
                    const title = titles[i];
                    const pct = Math.round(((i + 1) / total) * 100);
                    document.getElementById('progressBar').style.width = pct + '%';
                    document.getElementById('progressText').textContent = `${i + 1} / ${total}`;
                    document.getElementById('progressCurrent').textContent = `検索中: ${title}`;

                    let result = { inputTitle: title, match: null, alternatives: [], matchType: 'placeholder' };

                    if (tmdbConfigured) {
                        try {
                            const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(title)}`);
                            const json = await res.json();

                            if (json.status === 'success' && json.data.results && json.data.results.length > 0) {
                                const movies = json.data.results;
                                const first = movies[0];

                                if (first.user_status) {
                                    result.matchType = 'existing';
                                    result.match = first;
                                } else {
                                    result.matchType = 'found';
                                    result.match = first;
                                }
                                result.alternatives = movies.slice(0, 8);
                            }
                        } catch (e) {
                            console.error('Search error for', title, e);
                        }
                    }

                    this.results.push(result);
                    // TMDB API レート制限対筁E 40req/10s なので ~250ms間隔
                    if (tmdbConfigured && i < titles.length - 1) {
                        await new Promise(r => setTimeout(r, 280));
                    }
                }

                document.getElementById('searchBtn').disabled = false;
                this.showPreview();
            },

            showPreview() {
                document.getElementById('step2').classList.add('hidden');
                document.getElementById('step3').classList.remove('hidden');

                let countFound = 0, countPlaceholder = 0, countExisting = 0;
                const container = document.getElementById('previewList');
                container.innerHTML = '';

                this.results.forEach((r, idx) => {
                    if (r.matchType === 'found') countFound++;
                    else if (r.matchType === 'placeholder') countPlaceholder++;
                    else if (r.matchType === 'existing') countExisting++;

                    container.innerHTML += this.renderPreviewRow(r, idx);
                });

                document.getElementById('countFound').textContent = countFound;
                document.getElementById('countPlaceholder').textContent = countPlaceholder;
                document.getElementById('countExisting').textContent = countExisting;
            },

            renderPreviewRow(r, idx) {
                const isExisting = r.matchType === 'existing';
                const checked = !isExisting;
                const m = r.match;

                let statusBadge, poster, info;

                if (r.matchType === 'found' && m) {
                    statusBadge = '<span class="flex items-center gap-1 text-[11px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-circle-check"></i> TMDB発要E</span>';
                    poster = m.poster_path
                        ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-12 h-[72px] object-cover rounded-lg shrink-0" loading="lazy">`
                        : `<div class="w-12 h-[72px] poster-placeholder rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-film text-slate-400 text-sm"></i></div>`;
                    const year = m.release_date ? m.release_date.substring(0, 4) : '';
                    const rating = m.vote_average ? `<i class="fa-solid fa-star text-amber-400 text-[9px]"></i> ${m.vote_average.toFixed(1)}` : '';
                    info = `
                        <div class="font-bold text-sm text-slate-800 line-clamp-1">${this.esc(m.title)}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">${year} ${rating}</div>
                        ${m.original_title && m.original_title !== m.title ? `<div class="text-[10px] text-slate-400 line-clamp-1">${this.esc(m.original_title)}</div>` : ''}`;
                } else if (r.matchType === 'existing' && m) {
                    statusBadge = '<span class="flex items-center gap-1 text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full"><i class="fa-solid fa-check"></i> 登録済</span>';
                    poster = m.poster_path
                        ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-12 h-[72px] object-cover rounded-lg shrink-0 opacity-50" loading="lazy">`
                        : `<div class="w-12 h-[72px] poster-placeholder rounded-lg flex items-center justify-center shrink-0 opacity-50"><i class="fa-solid fa-film text-slate-400 text-sm"></i></div>`;
                    info = `<div class="font-bold text-sm text-slate-400 line-clamp-1">${this.esc(m.title)}</div>
                            <div class="text-[11px] text-slate-400">${m.user_status === 'watched' ? '見た済' : '見たい登録済'}</div>`;
                } else {
                    statusBadge = '<span class="flex items-center gap-1 text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-clock"></i> 仮登録</span>';
                    poster = `<div class="w-12 h-[72px] poster-placeholder rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-question text-slate-400"></i></div>`;
                    info = `<div class="font-bold text-sm text-slate-800 line-clamp-1">${this.esc(r.inputTitle)}</div>
                            <div class="text-[11px] text-amber-500">TMDBで見つかりませんでした（後から検索して紐付け可能）</div>`;
                }

                const altBtn = (r.alternatives && r.alternatives.length > 1 && !isExisting)
                    ? `<button onclick="AltModal.open(${idx})" class="text-[11px] text-slate-400 hover:text-slate-600 transition" title="候補を表示"><i class="fa-solid fa-arrows-rotate mr-0.5"></i>候裁E/button>`
                    : '';

                return `
                <div class="result-row flex items-center gap-3 py-3 ${isExisting ? 'excluded' : ''}" data-idx="${idx}">
                    <label class="shrink-0 flex items-center">
                        <input type="checkbox" class="row-check w-4 h-4 rounded border-slate-300 accent-[var(--mv-theme)]"
                               data-idx="${idx}" ${checked ? 'checked' : ''} ${isExisting ? 'disabled' : ''}
                               onchange="BulkImport.toggleRow(${idx}, this.checked)">
                    </label>
                    ${poster}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            ${statusBadge}
                            <span class="text-[10px] text-slate-300">入力: ${this.esc(r.inputTitle)}</span>
                        </div>
                        ${info}
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        ${altBtn}
                    </div>
                </div>`;
            },

            toggleRow(idx, checked) {
                const row = document.querySelector(`.result-row[data-idx="${idx}"]`);
                row.classList.toggle('excluded', !checked);
            },

            async registerAll() {
                const items = [];
                this.results.forEach((r, idx) => {
                    const checkbox = document.querySelector(`.row-check[data-idx="${idx}"]`);
                    if (!checkbox || !checkbox.checked) return;

                    const defaultStatus = document.getElementById('defaultStatus').value;
                    const item = {
                        title: r.inputTitle,
                        status: defaultStatus,
                    };

                    if (r.match && r.matchType === 'found') {
                        item.tmdb_id = r.match.id;
                        item.tmdb_data = {
                            id: r.match.id,
                            title: r.match.title,
                            original_title: r.match.original_title,
                            overview: r.match.overview,
                            poster_path: r.match.poster_path,
                            backdrop_path: r.match.backdrop_path,
                            release_date: r.match.release_date,
                            vote_average: r.match.vote_average,
                            vote_count: r.match.vote_count,
                            genre_ids: r.match.genre_ids,
                        };
                    }

                    items.push(item);
                });

                if (items.length === 0) {
                    App.toast('登録する映画を選択してください');
                    return;
                }

                const btn = document.getElementById('registerBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i>登録中...';

                try {
                    const result = await App.post('/movie/api/bulk_add.php', { items });

                    if (result.status === 'success') {
                        document.getElementById('step3').classList.add('hidden');
                        document.getElementById('step4').classList.remove('hidden');
                        document.getElementById('doneMessage').textContent = result.message;

                        if (result.data && result.data.errors && result.data.errors.length > 0) {
                            document.getElementById('doneMessage').textContent += '\nエラー: ' + result.data.errors.join(', ');
                        }
                    } else {
                        App.toast(result.message || '登録に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('登録中にエラーが発生しました');
                }

                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check mr-1.5"></i>チェック済みを一括登録';
            },

            reset() {
                this.results = [];
                titleInput.value = '';
                document.getElementById('lineCount').textContent = '0';
                document.getElementById('step2').classList.add('hidden');
                document.getElementById('step3').classList.add('hidden');
                document.getElementById('step4').classList.add('hidden');
                document.getElementById('step1').scrollIntoView({ behavior: 'smooth' });
            },

            updateMatch(idx, altIdx) {
                const r = this.results[idx];
                if (!r.alternatives || !r.alternatives[altIdx]) return;
                const newMatch = r.alternatives[altIdx];
                r.match = newMatch;

                if (newMatch.user_status) {
                    r.matchType = 'existing';
                } else {
                    r.matchType = 'found';
                }
                this.showPreview();
            },

            esc(str) {
                if (!str) return '';
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        };

        const AltModal = {
            currentIdx: null,

            open(idx) {
                this.currentIdx = idx;
                const r = BulkImport.results[idx];
                const container = document.getElementById('altList');
                container.innerHTML = '';

                if (!r.alternatives || r.alternatives.length === 0) {
                    container.innerHTML = '<p class="text-sm text-slate-400 text-center py-8">候補がありません</p>';
                } else {
                    r.alternatives.forEach((m, altIdx) => {
                        const poster = m.poster_path
                            ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-12 h-[72px] object-cover rounded-lg shrink-0" loading="lazy">`
                            : `<div class="w-12 h-[72px] poster-placeholder rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-film text-slate-400 text-sm"></i></div>`;
                        const year = m.release_date ? m.release_date.substring(0, 4) : '';
                        const rating = m.vote_average ? `<i class="fa-solid fa-star text-amber-400 text-[9px]"></i> ${m.vote_average.toFixed(1)}` : '';
                        const isSelected = r.match && r.match.id === m.id;
                        const userStatus = m.user_status ? `<span class="text-[10px] text-slate-400">(${m.user_status === 'watched' ? '見た済' : '見たい中'})</span>` : '';

                        container.innerHTML += `
                        <div class="flex items-center gap-3 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition ${isSelected ? 'ring-2 ring-[var(--mv-theme)] bg-violet-50' : ''}"
                             onclick="AltModal.select(${altIdx})">
                            ${poster}
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-sm text-slate-800 line-clamp-1">${BulkImport.esc(m.title)}</div>
                                <div class="text-[11px] text-slate-400">${year} ${rating} ${userStatus}</div>
                                ${m.original_title && m.original_title !== m.title ? `<div class="text-[10px] text-slate-400 line-clamp-1">${BulkImport.esc(m.original_title)}</div>` : ''}
                            </div>
                            ${isSelected ? '<i class="fa-solid fa-check text-[var(--mv-theme)] shrink-0"></i>' : ''}
                        </div>`;
                    });

                    // 仮登録オプション
                    const isPlaceholder = r.matchType === 'placeholder';
                    container.innerHTML += `
                    <div class="flex items-center gap-3 p-3 rounded-xl cursor-pointer hover:bg-amber-50 transition border border-dashed border-amber-200 mt-2 ${isPlaceholder ? 'ring-2 ring-amber-400 bg-amber-50' : ''}"
                         onclick="AltModal.selectPlaceholder()">
                        <div class="w-12 h-[72px] rounded-lg flex items-center justify-center shrink-0 bg-amber-50">
                            <i class="fa-solid fa-clock text-amber-400"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-sm text-amber-700">仮登録にする</div>
                            <div class="text-[11px] text-amber-500">TMDB情報なしで。{BulkImport.esc(r.inputTitle)}」として登録</div>
                        </div>
                        ${isPlaceholder ? '<i class="fa-solid fa-check text-amber-500 shrink-0"></i>' : ''}
                    </div>`;
                }

                document.getElementById('altModal').classList.add('active');
            },

            close() {
                document.getElementById('altModal').classList.remove('active');
                this.currentIdx = null;
            },

            select(altIdx) {
                if (this.currentIdx === null) return;
                BulkImport.updateMatch(this.currentIdx, altIdx);
                this.close();
            },

            selectPlaceholder() {
                if (this.currentIdx === null) return;
                const r = BulkImport.results[this.currentIdx];
                r.match = null;
                r.matchType = 'placeholder';
                BulkImport.showPreview();
                this.close();
            }
        };

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') AltModal.close();
        });
    </script>
</body>
</html>
