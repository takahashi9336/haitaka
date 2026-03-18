<?php
$appKey = 'anime';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$animeTheme = getThemeVarsForApp('anime');
$themePrimaryHex = $animeTheme['themePrimaryHex'] ?? '#0ea5e9';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アニメ一括登録 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --anime-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .anime-theme-btn { background-color: var(--anime-theme); }
        .anime-theme-btn:hover { filter: brightness(1.08); }
        .anime-theme-text { color: var(--anime-theme); }
        .anime-theme-border { border-color: var(--anime-theme); }
        .anime-theme-ring:focus { --tw-ring-color: var(--anime-theme); }
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
        .result-row { transition: background-color 0.15s; }
        .result-row:hover { background-color: #f8fafc; }
        .result-row.excluded { opacity: 0.4; }
        .progress-bar { transition: width 0.3s ease; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/anime/list.php" class="flex items-center gap-2 text-slate-500 hover:text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span class="text-sm font-bold">アニメリスト</span>
                </a>
                <span class="text-slate-300">|</span>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white shadow bg-sky-500">
                        <i class="fa-solid fa-file-import text-xs"></i>
                    </div>
                    <h1 class="font-black text-slate-700 text-lg tracking-tighter">一括登録</h1>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto px-6 md:px-12 py-6 space-y-6">

                <?php if (!$oauthConfigured): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                    Annict OAuth が未設定です。<code class="bg-amber-100 px-1 rounded">private/.env</code> に
                    <code class="bg-amber-100 px-1 rounded">ANNICT_CLIENT_ID</code> /
                    <code class="bg-amber-100 px-1 rounded">ANNICT_CLIENT_SECRET</code> /
                    <code class="bg-amber-100 px-1 rounded">ANNICT_REDIRECT_URI</code> を設定すると Annict アカウントと連携できます。<br>
                    連携していない場合でも、公開APIを使った作品検索は可能です。
                </div>
                <?php elseif (!$hasToken): ?>
                <div class="bg-sky-50 border border-sky-100 rounded-xl p-4 text-sm text-sky-800 flex items-start gap-3">
                    <div class="mt-0.5">
                        <i class="fa-solid fa-link text-sky-500"></i>
                    </div>
                    <div>
                        <p class="font-bold text-sm mb-1">Annict と連携すると、より正確に作品を検索できます</p>
                        <p class="text-xs text-slate-500 mb-2">Annict の視聴状況と連携することで、重複登録を防ぎつつ作品を取り込めます（任意）。</p>
                        <a href="<?= htmlspecialchars($authorizeUrl) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-sky-500 text-white text-xs font-bold rounded-lg hover:bg-sky-600 transition">
                            <i class="fa-solid fa-right-to-bracket"></i>Annictで連携する
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- STEP 1: タイトル入力 -->
                <div id="step1" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-bold text-slate-800 mb-1">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full anime-theme-btn text-white text-xs font-bold mr-2">1</span>
                        アニメタイトルを入力
                    </h2>
                    <p class="text-xs text-slate-400 mb-4 ml-8">1行に1タイトルずつ入力してください（日本語タイトル推奨）</p>

                    <textarea id="titleInput" rows="10" placeholder="【推しの子】&#10;僕のヒーローアカデミア&#10;進撃の巨人&#10;SPY×FAMILY&#10;..."
                              class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--anime-theme)] focus:border-transparent resize-y font-mono leading-relaxed"></textarea>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mt-4 gap-3">
                        <p class="text-xs text-slate-400"><span id="lineCount">0</span> 件のタイトル</p>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                                <i class="fa-solid fa-folder-open text-sm anime-theme-text"></i>
                                <select id="defaultStatus" class="bg-transparent text-sm font-bold text-slate-700 focus:outline-none cursor-pointer pr-1">
                                    <option value="wanna_watch">見たいに追加</option>
                                    <option value="watching">見てるに追加</option>
                                    <option value="watched">見たに追加</option>
                                </select>
                            </div>
                            <button onclick="AnimeBulkImport.startSearch()" class="px-5 py-2.5 anime-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition" id="searchBtn">
                                <i class="fa-solid fa-magnifying-glass mr-1.5"></i>Annict検索プレビュー
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: 検索プログレス -->
                <div id="step2" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 hidden">
                    <h2 class="text-base font-bold text-slate-800 mb-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full anime-theme-btn text-white text-xs font-bold mr-2">2</span>
                        Annict 検索中...
                    </h2>
                    <div class="flex items-center gap-4 mb-3">
                        <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                            <div id="progressBar" class="progress-bar h-full rounded-full" style="width: 0%; background-color: var(--anime-theme);"></div>
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
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full anime-theme-btn text-white text-xs font-bold mr-2">3</span>
                                検索結果プレビュー
                            </h2>
                            <div class="flex items-center gap-3 text-xs">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>Annict発見 <span id="countFound" class="font-bold">0</span></span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>登録済 <span id="countExisting" class="font-bold">0</span></span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>未検出 <span id="countMissing" class="font-bold">0</span></span>
                            </div>
                        </div>

                        <div id="previewList" class="divide-y divide-slate-100"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button onclick="AnimeBulkImport.reset()" class="px-4 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                            <i class="fa-solid fa-arrow-rotate-left mr-1"></i>やり直す</button>
                        <button onclick="AnimeBulkImport.registerAll()" id="registerBtn" class="px-6 py-2.5 anime-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-check mr-1.5"></i>チェック済みを一括登録
                        </button>
                    </div>
                </div>

                <!-- STEP 4: 完了 -->
                <div id="step4" class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center hidden">
                    <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-check text-3xl text-green-500"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 mb-2" id="doneTitle">登録完了</h2>
                    <p class="text-sm text-slate-500 mb-6 whitespace-pre-line" id="doneMessage"></p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="/anime/list.php" class="px-5 py-2.5 anime-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-list mr-1"></i>アニメリストへ
                        </a>
                        <button onclick="AnimeBulkImport.reset()" class="px-5 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                            続けて登録
                        </button>
                    </div>
                </div>

                <p class="text-xs text-slate-400 mt-2">アニメ作品データ提供: <a href="https://annict.com" target="_blank" rel="noopener" class="underline hover:text-slate-600">Annict</a></p>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        (function() {
            var titleInput = document.getElementById('titleInput');
            titleInput.addEventListener('input', function () {
                var lines = titleInput.value.split('\n').filter(function (l) { return l.trim() !== ''; });
                document.getElementById('lineCount').textContent = lines.length;
            });

            window.AnimeBulkImport = {
                results: [],

                getTitles: function () {
                    return titleInput.value.split('\n').map(function (l) { return l.trim(); }).filter(function (l) { return l !== ''; });
                },

                startSearch: function () {
                    var self = this;
                    var titles = self.getTitles();
                    if (titles.length === 0) {
                        App.toast('タイトルを入力してください');
                        return;
                    }

                    self.results = [];
                    document.getElementById('step2').classList.remove('hidden');
                    document.getElementById('step3').classList.add('hidden');
                    document.getElementById('step4').classList.add('hidden');
                    document.getElementById('searchBtn').disabled = true;

                    var total = titles.length;

                    var run = function (i) {
                        if (i >= titles.length) {
                            document.getElementById('searchBtn').disabled = false;
                            self.showPreview();
                            return;
                        }

                        var title = titles[i];
                        var pct = Math.round(((i + 1) / total) * 100);
                        document.getElementById('progressBar').style.width = pct + '%';
                        document.getElementById('progressText').textContent = (i + 1) + ' / ' + total;
                        document.getElementById('progressCurrent').textContent = '検索中: ' + title;

                        var result = { inputTitle: title, match: null, matchType: 'missing' };

                        fetch('/anime/api/search.php?q=' + encodeURIComponent(title) + '&page=1')
                            .then(function (res) { return res.json(); })
                            .then(function (json) {
                                if (json.status === 'success' && Object.prototype.toString.call(json.data) === '[object Array]' && json.data.length > 0) {
                                    var works = json.data;
                                    var first = works[0];
                                    result.match = first;
                                    if (first.user_status) {
                                        result.matchType = 'existing';
                                    } else {
                                        result.matchType = 'found';
                                    }
                                }
                            })
                            .catch(function (e) {
                                console.error('Search error for', title, e);
                            })
                            .finally(function () {
                                self.results.push(result);
                                setTimeout(function () { run(i + 1); }, 280);
                            });
                    };

                    run(0);
                },

                showPreview: function () {
                    document.getElementById('step2').classList.add('hidden');
                    document.getElementById('step3').classList.remove('hidden');

                    var countFound = 0, countExisting = 0, countMissing = 0;
                    var container = document.getElementById('previewList');
                    container.innerHTML = '';

                    for (var i = 0; i < this.results.length; i++) {
                        var r = this.results[i];
                        if (r.matchType === 'found') countFound++;
                        else if (r.matchType === 'existing') countExisting++;
                        else countMissing++;
                        container.innerHTML += this.renderPreviewRow(r, i);
                    }

                    document.getElementById('countFound').textContent = countFound;
                    document.getElementById('countExisting').textContent = countExisting;
                    document.getElementById('countMissing').textContent = countMissing;
                },

                renderPreviewRow: function (r, idx) {
                    var isExisting = r.matchType === 'existing';
                    var isMissing = r.matchType === 'missing';
                    var checked = !isExisting && !isMissing;
                    var w = r.match;

                    var statusBadge, poster, info;

                    if (r.matchType === 'found' && w) {
                        statusBadge = '<span class="inline-flex items-center gap-1 text-[11px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-circle-check"></i> Annict発見</span>';
                        var rawUrl = (w.images && w.images.recommended_url)
                            ? w.images.recommended_url
                            : (w.images && w.images.facebook && w.images.facebook.og_image_url)
                                ? w.images.facebook.og_image_url
                                : '';
                        var imgUrl = rawUrl ? rawUrl.replace(/^http:\/\//i, 'https://') : '';
                        poster = imgUrl
                            ? '<img src="' + this.esc(imgUrl) + '" class="w-12 h-[72px] object-cover rounded-lg shrink-0" loading="lazy">'
                            : '<div class="w-12 h-[72px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-tv text-slate-400 text-sm"></i></div>';
                        var season = w.season_name_text || w.season_name || '';
                        var episodes = w.episodes_count ? w.episodes_count + '話' : '';
                        info =
                            '<div class="font-bold text-sm text-slate-800 line-clamp-1">' + this.esc(w.title || '') + '</div>' +
                            '<div class="text-[11px] text-slate-400 mt-0.5">' + this.esc(season) + ' ' + episodes + '</div>';
                    } else if (r.matchType === 'existing' && w) {
                        statusBadge = '<span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full"><i class="fa-solid fa-check"></i> 登録済</span>';
                        var rawUrl2 = (w.images && w.images.recommended_url)
                            ? w.images.recommended_url
                            : (w.images && w.images.facebook && w.images.facebook.og_image_url)
                                ? w.images.facebook.og_image_url
                                : '';
                        var imgUrl2 = rawUrl2 ? rawUrl2.replace(/^http:\/\//i, 'https://') : '';
                        poster = imgUrl2
                            ? '<img src="' + this.esc(imgUrl2) + '" class="w-12 h-[72px] object-cover rounded-lg shrink-0 opacity-50" loading="lazy">'
                            : '<div class="w-12 h-[72px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0 opacity-50"><i class="fa-solid fa-tv text-slate-400 text-sm"></i></div>';
                        var labelMap = { wanna_watch: '見たい', watching: '見てる', watched: '見た' };
                        var us = w.user_status || (w.status && w.status.kind) || '';
                        var label = labelMap[us] || 'リスト登録済';
                        info =
                            '<div class="font-bold text-sm text-slate-400 line-clamp-1">' + this.esc(w.title || '') + '</div>' +
                            '<div class="text-[11px] text-slate-400">' + this.esc(label) + ' </div>';
                    } else {
                        statusBadge = '<span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-circle-exclamation"></i> 未検出</span>';
                        poster = '<div class="w-12 h-[72px] bg-slate-100 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-question text-slate-400"></i></div>';
                        info =
                            '<div class="font-bold text-sm text-slate-800 line-clamp-1">' + this.esc(r.inputTitle) + '</div>' +
                            '<div class="text-[11px] text-amber-500">Annictで見つかりませんでした（この行は登録されません）</div>';
                    }

                    var rowHtml = ''
                        + '<div class="result-row flex items-center gap-3 py-3 ' + (isExisting || isMissing ? 'excluded' : '') + '" data-idx="' + idx + '">'
                        + '  <label class="shrink-0 flex items-center">'
                        + '    <input type="checkbox" class="row-check w-4 h-4 rounded border-slate-300 accent-[var(--anime-theme)]"'
                        + '           data-idx="' + idx + '" ' + (checked ? 'checked' : '') + ' ' + (isExisting || isMissing ? 'disabled' : '')
                        + '           onchange="AnimeBulkImport.toggleRow(' + idx + ', this.checked)">'
                        + '  </label>'
                        +    poster
                        + '  <div class="flex-1 min-w-0">'
                        + '    <div class="flex items-center gap-2 mb-0.5">'
                        +        statusBadge
                        + '      <span class="text-[10px] text-slate-300">入力: ' + this.esc(r.inputTitle) + '</span>'
                        + '    </div>'
                        +      info
                        + '  </div>'
                        + '</div>';

                    return rowHtml;
                },

                toggleRow: function (idx, checked) {
                    var row = document.querySelector('.result-row[data-idx="' + idx + '"]');
                    if (!row) return;
                    if (checked) {
                        row.classList.remove('excluded');
                    } else {
                        row.classList.add('excluded');
                    }
                },

                registerAll: function () {
                    var self = this;
                    var items = [];
                    for (var i = 0; i < self.results.length; i++) {
                        var r = self.results[i];
                        var checkbox = document.querySelector('.row-check[data-idx="' + i + '"]');
                        if (!checkbox || !checkbox.checked) continue;
                        if (!r.match || r.matchType !== 'found') continue;

                        var defaultStatus = document.getElementById('defaultStatus').value;
                        var workId = r.match.id;
                        if (!workId) continue;
                        items.push({ work_id: workId, status: defaultStatus, title: r.inputTitle });
                    }

                    if (items.length === 0) {
                        App.toast('登録するアニメを選択してください');
                        return;
                    }

                    var btn = document.getElementById('registerBtn');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i>登録中...';

                    var added = 0;
                    var failed = 0;
                    var errors = [];
                    var idx = 0;

                    var runRegister = function () {
                        if (idx >= items.length) {
                            document.getElementById('step3').classList.add('hidden');
                            document.getElementById('step4').classList.remove('hidden');

                            var msg = added + '件を登録しました';
                            if (failed > 0) {
                                msg += '（' + failed + '件はエラー）';
                            }
                            if (errors.length > 0) {
                                msg += '\n\nエラー詳細:\n' + errors.join('\n');
                            }
                            document.getElementById('doneMessage').textContent = msg;

                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa-solid fa-check mr-1.5"></i>チェック済みを一括登録';
                            return;
                        }

                        var item = items[idx];
                        App.post('/anime/api/set_status.php', {
                            work_id: item.work_id,
                            kind: item.status
                        }).then(function (res) {
                            if (res.status === 'success') {
                                added++;
                            } else {
                                failed++;
                                errors.push((item.title || '不明') + ': ' + (res.message || '登録に失敗しました'));
                            }
                        }).catch(function (e) {
                            console.error(e);
                            failed++;
                            errors.push((item.title || '不明') + ': 通信エラー');
                        }).finally(function () {
                            idx++;
                            setTimeout(runRegister, 150);
                        });
                    };

                    runRegister();
                },

                reset: function () {
                    this.results = [];
                    titleInput.value = '';
                    document.getElementById('lineCount').textContent = '0';
                    document.getElementById('step2').classList.add('hidden');
                    document.getElementById('step3').classList.add('hidden');
                    document.getElementById('step4').classList.add('hidden');
                    document.getElementById('step1').scrollIntoView({ behavior: 'smooth' });
                },

                esc: function (str) {
                    if (!str) return '';
                    var d = document.createElement('div');
                    d.textContent = str;
                    return d.innerHTML;
                }
            };
        })();
    </script>
</body>
</html>

