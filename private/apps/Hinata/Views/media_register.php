<?php
/**
 * メディア登録 View（YouTube検索 + URL貼り付け）
 * 物理パス: haitaka/private/apps/Hinata/Views/media_register.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メディア登録 - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .tab-btn { transition: all 0.2s; }
        .tab-btn.active { border-bottom: 3px solid; font-weight: 900; }
        .platform-youtube .tab-btn.active { border-color: #ef4444; color: #ef4444; }
        .platform-instagram .tab-btn.active { border-color: #e1306c; color: #e1306c; }
        .platform-tiktok .tab-btn.active { border-color: #010101; color: #010101; }
        .status-ok { background: #dbeafe; color: #1e40af; }
        .status-registered { background: #f1f5f9; color: #64748b; }
        .status-error { background: #fee2e2; color: #991b1b; }
        .video-grid-item { transition: all 0.2s; }
        .video-grid-item:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.1); }
        .video-grid-item.selected { ring: 2px; outline: 3px solid #3b82f6; outline-offset: 2px; }
        .video-grid-item.registered { opacity: 0.5; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-circle-plus text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">メディア登録</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="/hinata/media_list.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                    動画一覧へ
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-6xl mx-auto">

                <!-- タブナビゲーション -->
                <div class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm mb-6">
                    <div class="flex border-b border-slate-200">
                        <button class="tab-btn active flex-1 py-4 text-sm font-bold text-center text-red-500" data-tab="youtube">
                            <i class="fa-brands fa-youtube mr-1"></i> YouTube
                        </button>
                        <button class="tab-btn flex-1 py-4 text-sm font-bold text-center text-slate-400 hover:text-slate-600" data-tab="instagram">
                            <i class="fa-brands fa-instagram mr-1"></i> Instagram
                        </button>
                        <button class="tab-btn flex-1 py-4 text-sm font-bold text-center text-slate-400 hover:text-slate-600" data-tab="tiktok">
                            <i class="fa-brands fa-tiktok mr-1"></i> TikTok
                        </button>
                    </div>

                    <!-- ===== YouTube タブ ===== -->
                    <div id="tab-youtube" class="tab-content p-6">
                        <!-- モード切替 -->
                        <div class="flex gap-3 mb-5">
                            <button id="ytModeSearch" class="px-4 py-2 text-xs font-bold rounded-full bg-red-500 text-white transition <?= $youtubeConfigured ? '' : 'opacity-50 cursor-not-allowed' ?>">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i> チャンネル検索
                                <?php if (!$youtubeConfigured): ?>
                                    <span class="ml-1 text-[10px]">(APIキー未設定)</span>
                                <?php endif; ?>
                            </button>
                            <button id="ytModeUrl" class="px-4 py-2 text-xs font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                                <i class="fa-solid fa-link mr-1"></i> URL貼り付け
                            </button>
                        </div>

                        <!-- モードA: チャンネル検索 -->
                        <div id="ytSearchPanel" class="<?= $youtubeConfigured ? '' : 'hidden' ?>">
                            <div class="flex flex-wrap gap-3 mb-4">
                                <select id="ytChannelSelect" class="h-10 border <?= $cardBorder ?> rounded-xl px-3 text-sm bg-slate-50">
                                    <?php foreach ($presetChannels as $chId => $chName): ?>
                                        <option value="<?= htmlspecialchars($chId) ?>"><?= htmlspecialchars($chName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button id="ytBtnChannelLoad" class="h-10 px-5 bg-red-500 text-white text-xs font-bold rounded-full hover:bg-red-600 transition">
                                    <i class="fa-solid fa-list mr-1"></i> 動画一覧を取得
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-3 mb-4">
                                <input id="ytSearchQuery" type="text" placeholder="キーワード検索（例: MV, ひなリハ）" class="h-10 flex-1 min-w-[200px] border <?= $cardBorder ?> rounded-xl px-4 text-sm bg-slate-50">
                                <button id="ytBtnSearch" class="h-10 px-5 bg-red-500 text-white text-xs font-bold rounded-full hover:bg-red-600 transition">
                                    <i class="fa-solid fa-magnifying-glass mr-1"></i> 検索
                                </button>
                            </div>

                            <!-- 検索結果 -->
                            <div id="ytSearchResults" class="hidden">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs text-slate-500"><span id="ytResultCount">0</span>件の動画</p>
                                    <div class="flex gap-2">
                                        <button id="ytSelectAll" class="text-xs font-bold text-blue-500 hover:text-blue-700">全選択</button>
                                        <span class="text-slate-300">|</span>
                                        <button id="ytDeselectAll" class="text-xs font-bold text-slate-400 hover:text-slate-600">全解除</button>
                                    </div>
                                </div>
                                <div id="ytVideoGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-4"></div>
                                <div id="ytPagination" class="flex justify-center gap-3"></div>
                            </div>
                        </div>

                        <!-- モードB: URL貼り付け -->
                        <div id="ytUrlPanel" class="<?= $youtubeConfigured ? 'hidden' : '' ?>">
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-600 mb-2">YouTube URL（1行1URL）</label>
                                <textarea id="ytUrlTextarea" rows="6" placeholder="https://www.youtube.com/watch?v=xxxxx&#10;https://youtu.be/yyyyy&#10;..." class="w-full border <?= $cardBorder ?> rounded-xl px-4 py-3 text-sm bg-slate-50 font-mono"></textarea>
                            </div>
                            <button id="ytBtnFetchUrl" class="h-10 px-5 bg-red-500 text-white text-xs font-bold rounded-full hover:bg-red-600 transition">
                                <i class="fa-solid fa-download mr-1"></i> 情報取得
                            </button>
                            <div id="ytUrlResults" class="mt-4 hidden"></div>
                        </div>
                    </div>

                    <!-- ===== Instagram タブ ===== -->
                    <div id="tab-instagram" class="tab-content p-6 hidden">
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-4 mb-5">
                            <p class="text-xs text-slate-600">
                                <i class="fa-solid fa-info-circle text-purple-500 mr-1"></i>
                                Instagram はAPI制限により自動取得が困難なため、<strong>URL + タイトル手動入力</strong>で登録します。
                            </p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-600 mb-2">Instagram URL（1行1URL）</label>
                            <textarea id="igUrlTextarea" rows="6" placeholder="https://www.instagram.com/reel/XXXXX/&#10;https://www.instagram.com/hinatazaka46/reel/XXXXX/&#10;https://www.instagram.com/p/YYYYY/&#10;..." class="w-full border <?= $cardBorder ?> rounded-xl px-4 py-3 text-sm bg-slate-50 font-mono"></textarea>
                        </div>
                        <button id="igBtnParse" class="h-10 px-5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs font-bold rounded-full hover:opacity-90 transition">
                            <i class="fa-solid fa-link mr-1"></i> URL解析
                        </button>
                        <div id="igUrlResults" class="mt-4 hidden"></div>
                    </div>

                    <!-- ===== TikTok タブ ===== -->
                    <div id="tab-tiktok" class="tab-content p-6 hidden">
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-5">
                            <p class="text-xs text-slate-600">
                                <i class="fa-solid fa-info-circle text-slate-500 mr-1"></i>
                                TikTok oEmbed APIにより、<strong>タイトル・サムネイル・投稿者を自動取得</strong>できます（APIキー不要）。
                            </p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-600 mb-2">TikTok URL（1行1URL）</label>
                            <textarea id="ttUrlTextarea" rows="6" placeholder="https://www.tiktok.com/@hinatazaka46/video/1234567890&#10;..." class="w-full border <?= $cardBorder ?> rounded-xl px-4 py-3 text-sm bg-slate-50 font-mono"></textarea>
                        </div>
                        <button id="ttBtnFetch" class="h-10 px-5 bg-slate-800 text-white text-xs font-bold rounded-full hover:bg-slate-900 transition">
                            <i class="fa-solid fa-download mr-1"></i> 情報取得
                        </button>
                        <div id="ttUrlResults" class="mt-4 hidden"></div>
                    </div>
                </div>

                <!-- 登録セクション（共通） -->
                <div id="registerSection" class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-6 hidden">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-800 mb-1">登録プレビュー</h2>
                            <p class="text-xs text-slate-500">選択した動画の情報を確認し、カテゴリを設定して一括登録できます。</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select id="bulkCategory" class="h-10 border <?= $cardBorder ?> rounded-xl px-3 text-sm bg-slate-50">
                                <option value="">（未選択）</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="btnApplyCategory" class="h-10 px-4 bg-slate-100 text-slate-600 text-xs font-bold rounded-full hover:bg-slate-200 transition">
                                一括適用
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mb-4 text-xs">
                        <span class="text-blue-600 font-bold">新規: <span id="regCountNew">0</span>件</span>
                        <span class="text-slate-400 font-bold">登録済: <span id="regCountRegistered">0</span>件</span>
                        <span class="text-red-500 font-bold">エラー: <span id="regCountError">0</span>件</span>
                    </div>

                    <div id="registerTableWrap" class="overflow-x-auto mb-4">
                        <table class="w-full border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="text-left text-xs font-bold text-slate-600 p-3 w-8"><input type="checkbox" id="regCheckAll" checked></th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">サムネイル</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">プラットフォーム</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">タイトル</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">種別</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">カテゴリ</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">ステータス</th>
                                </tr>
                            </thead>
                            <tbody id="registerTableBody"></tbody>
                        </table>
                    </div>

                    <div class="flex gap-3">
                        <button id="btnRegister" class="h-11 px-8 bg-green-500 text-white font-bold text-sm rounded-full hover:bg-green-600 transition shadow-lg shadow-green-200 flex items-center gap-2">
                            <i class="fa-solid fa-check"></i> 一括登録
                        </button>
                        <button id="btnClearRegister" class="h-11 px-6 bg-slate-100 text-slate-600 font-bold text-sm rounded-full hover:bg-slate-200 transition">
                            クリア
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
    (function() {
        /* ----------------------------------------------------------------
         *  State
         * ---------------------------------------------------------------- */
        let registerItems = [];
        let currentTab = 'youtube';
        let ytPageTokens = { next: null, prev: null };
        const youtubeConfigured = <?= json_encode($youtubeConfigured) ?>;

        const categories = <?= json_encode(array_keys($categories)) ?>;

        /* ----------------------------------------------------------------
         *  Tab switching
         * ---------------------------------------------------------------- */
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                currentTab = tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                document.getElementById('tab-' + tab).classList.remove('hidden');

                const wrapper = document.querySelector('.tab-btn.active').closest('.bg-white');
                wrapper.classList.remove('platform-youtube', 'platform-instagram', 'platform-tiktok');
                wrapper.classList.add('platform-' + tab);
            });
        });

        /* ----------------------------------------------------------------
         *  YouTube: Mode switching
         * ---------------------------------------------------------------- */
        const ytModeSearch = document.getElementById('ytModeSearch');
        const ytModeUrl = document.getElementById('ytModeUrl');
        const ytSearchPanel = document.getElementById('ytSearchPanel');
        const ytUrlPanel = document.getElementById('ytUrlPanel');

        ytModeSearch.addEventListener('click', () => {
            if (!youtubeConfigured) return;
            ytModeSearch.className = 'px-4 py-2 text-xs font-bold rounded-full bg-red-500 text-white transition';
            ytModeUrl.className = 'px-4 py-2 text-xs font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition';
            ytSearchPanel.classList.remove('hidden');
            ytUrlPanel.classList.add('hidden');
        });
        ytModeUrl.addEventListener('click', () => {
            ytModeUrl.className = 'px-4 py-2 text-xs font-bold rounded-full bg-red-500 text-white transition';
            ytModeSearch.className = 'px-4 py-2 text-xs font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition' + (youtubeConfigured ? '' : ' opacity-50 cursor-not-allowed');
            ytUrlPanel.classList.remove('hidden');
            ytSearchPanel.classList.add('hidden');
        });

        /* ----------------------------------------------------------------
         *  YouTube: Channel video list (Mode A)
         * ---------------------------------------------------------------- */
        document.getElementById('ytBtnChannelLoad').addEventListener('click', () => loadYtChannel());
        document.getElementById('ytBtnSearch').addEventListener('click', () => searchYt());
        document.getElementById('ytSearchQuery').addEventListener('keydown', e => { if (e.key === 'Enter') searchYt(); });

        async function loadYtChannel(pageToken) {
            const channelId = document.getElementById('ytChannelSelect').value;
            const btn = document.getElementById('ytBtnChannelLoad');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> 取得中...';
            try {
                const params = new URLSearchParams({ channel_id: channelId, max_results: 25 });
                if (pageToken) params.set('page_token', pageToken);
                const res = await fetch('/hinata/api/youtube_channel_videos.php?' + params);
                const json = await res.json();
                if (json.status === 'success') {
                    renderYtGrid(json.data);
                    ytPageTokens = { next: json.next_page_token, prev: json.prev_page_token };
                    renderYtPagination(json.total_results, () => loadYtChannel);
                    document.getElementById('ytSearchResults').classList.remove('hidden');
                    document.getElementById('ytResultCount').textContent = json.total_results || json.data.length;
                } else {
                    alert(json.message || 'エラーが発生しました');
                }
            } catch (e) { alert('通信エラー: ' + e.message); }
            finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-list mr-1"></i> 動画一覧を取得';
            }
        }

        async function searchYt(pageToken) {
            const query = document.getElementById('ytSearchQuery').value.trim();
            if (!query) { alert('キーワードを入力してください'); return; }
            const channelId = document.getElementById('ytChannelSelect').value;
            const btn = document.getElementById('ytBtnSearch');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> 検索中...';
            try {
                const params = new URLSearchParams({ q: query, channel_id: channelId, max_results: 25 });
                if (pageToken) params.set('page_token', pageToken);
                const res = await fetch('/hinata/api/youtube_search.php?' + params);
                const json = await res.json();
                if (json.status === 'success') {
                    renderYtGrid(json.data);
                    ytPageTokens = { next: json.next_page_token, prev: json.prev_page_token };
                    renderYtPagination(json.total_results, () => searchYt);
                    document.getElementById('ytSearchResults').classList.remove('hidden');
                    document.getElementById('ytResultCount').textContent = json.total_results || json.data.length;
                } else {
                    alert(json.message || 'エラーが発生しました');
                }
            } catch (e) { alert('通信エラー: ' + e.message); }
            finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass mr-1"></i> 検索';
            }
        }

        function renderYtGrid(videos) {
            const grid = document.getElementById('ytVideoGrid');
            grid.innerHTML = videos.map(v => `
                <div class="video-grid-item bg-white rounded-xl border border-slate-200 overflow-hidden cursor-pointer ${v.is_registered ? 'registered' : ''}"
                     data-video='${JSON.stringify(v).replace(/'/g, "&#39;")}'>
                    <div class="relative" style="padding-bottom:56.25%">
                        <img src="${v.thumbnail_url}" alt="" class="absolute inset-0 w-full h-full object-cover">
                        ${v.is_registered ? '<div class="absolute inset-0 bg-black/40 flex items-center justify-center"><span class="text-white text-xs font-bold bg-black/60 px-2 py-1 rounded">登録済</span></div>' : ''}
                        ${v.media_type && v.media_type !== 'video' ? `<span class="absolute top-1 left-1 text-[9px] font-bold text-white px-1.5 py-0.5 rounded ${v.media_type === 'short' ? 'bg-blue-500' : 'bg-red-600'}">${v.media_type === 'short' ? 'Short' : 'LIVE'}</span>` : ''}
                    </div>
                    <div class="p-2">
                        <p class="text-xs font-bold text-slate-700 line-clamp-2 leading-tight">${escHtml(v.title)}</p>
                        <p class="text-[10px] text-slate-400 mt-1">${v.published_at ? new Date(v.published_at).toLocaleDateString('ja-JP') : ''}</p>
                    </div>
                    <div class="px-2 pb-2 ${v.is_registered ? 'hidden' : ''}">
                        <label class="flex items-center gap-1 text-xs text-blue-600 cursor-pointer">
                            <input type="checkbox" class="yt-video-check rounded" data-vid="${v.video_id}">
                            <span>選択</span>
                        </label>
                    </div>
                </div>
            `).join('');

            grid.querySelectorAll('.video-grid-item').forEach(card => {
                card.addEventListener('click', (e) => {
                    if (e.target.tagName === 'INPUT') return;
                    const cb = card.querySelector('.yt-video-check');
                    if (cb && !card.classList.contains('registered')) {
                        cb.checked = !cb.checked;
                        card.classList.toggle('selected', cb.checked);
                    }
                });
            });
        }

        function renderYtPagination(total, loaderFnGetter) {
            const div = document.getElementById('ytPagination');
            let html = '';
            if (ytPageTokens.prev) {
                html += `<button class="yt-page-btn px-4 py-2 text-xs font-bold rounded-full bg-slate-100 hover:bg-slate-200 transition" data-dir="prev"><i class="fa-solid fa-chevron-left mr-1"></i> 前へ</button>`;
            }
            if (ytPageTokens.next) {
                html += `<button class="yt-page-btn px-4 py-2 text-xs font-bold rounded-full bg-slate-100 hover:bg-slate-200 transition" data-dir="next">次へ <i class="fa-solid fa-chevron-right ml-1"></i></button>`;
            }
            div.innerHTML = html;
            div.querySelectorAll('.yt-page-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const token = btn.dataset.dir === 'next' ? ytPageTokens.next : ytPageTokens.prev;
                    const fn = loaderFnGetter();
                    fn(token);
                });
            });
        }

        document.getElementById('ytSelectAll').addEventListener('click', () => {
            document.querySelectorAll('.yt-video-check').forEach(cb => { cb.checked = true; cb.closest('.video-grid-item')?.classList.add('selected'); });
        });
        document.getElementById('ytDeselectAll').addEventListener('click', () => {
            document.querySelectorAll('.yt-video-check').forEach(cb => { cb.checked = false; cb.closest('.video-grid-item')?.classList.remove('selected'); });
        });

        // YouTube検索結果から登録セクションに追加
        document.getElementById('ytSearchResults').addEventListener('click', (e) => {
            // 「選択した動画を登録」ボタンは個別ではなく、下部の登録セクションで一括処理
        });

        /* ----------------------------------------------------------------
         *  YouTube: URL paste (Mode B)
         * ---------------------------------------------------------------- */
        document.getElementById('ytBtnFetchUrl').addEventListener('click', () => fetchOembedUrls('ytUrlTextarea', 'ytUrlResults'));

        /* ----------------------------------------------------------------
         *  TikTok: URL paste
         * ---------------------------------------------------------------- */
        document.getElementById('ttBtnFetch').addEventListener('click', () => fetchOembedUrls('ttUrlTextarea', 'ttUrlResults'));

        /* ----------------------------------------------------------------
         *  Instagram: URL parse
         * ---------------------------------------------------------------- */
        document.getElementById('igBtnParse').addEventListener('click', () => fetchOembedUrls('igUrlTextarea', 'igUrlResults'));

        async function fetchOembedUrls(textareaId, resultsId) {
            const textarea = document.getElementById(textareaId);
            const resultsDiv = document.getElementById(resultsId);
            const urls = textarea.value.trim().split('\n').map(u => u.trim()).filter(u => u);
            if (urls.length === 0) { alert('URLを入力してください'); return; }

            const btn = textarea.parentElement.nextElementSibling;
            btn.disabled = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> 取得中...';

            try {
                const res = await fetch('/hinata/api/fetch_oembed.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ urls }),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    renderUrlResults(json.data, resultsDiv);
                    resultsDiv.classList.remove('hidden');
                } else {
                    alert(json.message || 'エラーが発生しました');
                }
            } catch (e) { alert('通信エラー: ' + e.message); }
            finally {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }

        function renderUrlResults(items, container) {
            const html = items.map((item, i) => {
                if (item.status === 'error') {
                    return `<div class="flex items-center gap-3 p-3 bg-red-50 border border-red-200 rounded-xl mb-2">
                        <i class="fa-solid fa-exclamation-circle text-red-400"></i>
                        <span class="text-xs text-red-600">${escHtml(item.url)} — ${escHtml(item.message)}</span>
                    </div>`;
                }
                const isIg = item.platform === 'instagram';
                return `<div class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl mb-2 url-result-row" data-result='${JSON.stringify(item).replace(/'/g, "&#39;")}'>
                    <input type="checkbox" class="url-item-check mt-1 rounded" checked>
                    <div class="relative group shrink-0">
                        ${item.thumbnail_url
                            ? `<img src="${item.thumbnail_url}" class="url-result-thumb w-20 h-12 rounded object-cover border border-slate-200">`
                            : `<div class="url-result-thumb w-20 h-12 bg-slate-200 rounded flex items-center justify-center text-slate-400 text-[10px]">${platformLabel(item.platform)}</div>`
                        }
                        <label class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition cursor-pointer" title="サムネイルをアップロード">
                            <i class="fa-solid fa-camera"></i>
                            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden url-result-thumb-upload" data-idx="${i}">
                        </label>
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="text" class="url-item-title w-full border border-slate-200 rounded px-2 py-1 text-sm mb-1"
                               value="${escAttr(item.title)}" placeholder="タイトルを入力${isIg ? '（必須）' : ''}">
                        <textarea class="url-item-desc w-full border border-slate-200 rounded px-2 py-1 text-xs mb-1 text-slate-600" rows="2"
                                  placeholder="本文 / description">${escHtml(item.description || '')}</textarea>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400">
                            <span class="font-bold uppercase">${item.platform}</span>
                            ${item.author_name ? `<span>by ${escHtml(item.author_name)}</span>` : ''}
                            ${item.published_at ? `<span><i class="fa-solid fa-calendar mr-0.5"></i>${new Date(item.published_at).toLocaleDateString('ja-JP')}</span>` : ''}
                            ${item.dup_status === 'Registered'
                                ? '<span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full font-bold">登録済</span>'
                                : '<span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full font-bold">新規</span>'}
                        </div>
                    </div>
                </div>`;
            }).join('');

            container.innerHTML = html + `
                <button class="url-add-to-register mt-3 h-10 px-5 bg-blue-500 text-white text-xs font-bold rounded-full hover:bg-blue-600 transition">
                    <i class="fa-solid fa-plus mr-1"></i> 登録リストに追加
                </button>`;

            container.querySelector('.url-add-to-register').addEventListener('click', () => {
                addUrlResultsToRegister(container);
                container.innerHTML = '';
                container.classList.add('hidden');
                const textarea = container.parentElement.querySelector('textarea');
                if (textarea) textarea.value = '';
            });

            container.querySelectorAll('.url-result-thumb-upload').forEach(input => {
                input.addEventListener('change', (e) => uploadThumbForItem(e, parseInt(input.dataset.idx), 'url-result', container));
            });
        }

        function addUrlResultsToRegister(container) {
            const rows = container.querySelectorAll('.url-result-row');
            rows.forEach(row => {
                const cb = row.querySelector('.url-item-check');
                if (!cb || !cb.checked) return;
                const data = JSON.parse(row.dataset.result);
                const title = row.querySelector('.url-item-title').value.trim();
                if (!title) return;
                if (data.dup_status === 'Registered') return;
                const desc = row.querySelector('.url-item-desc')?.value.trim() || '';

                registerItems.push({
                    platform: data.platform,
                    media_key: data.media_key,
                    sub_key: data.sub_key || null,
                    title: title,
                    description: desc,
                    thumbnail_url: data.thumbnail_url || '',
                    author_name: data.author_name || '',
                    published_at: data.published_at || null,
                    media_type: data.media_type || null,
                    category: document.getElementById('bulkCategory').value,
                    url: data.url,
                });
            });
            renderRegisterTable();
        }

        /* ----------------------------------------------------------------
         *  YouTube Grid → Register (for Mode A)
         * ---------------------------------------------------------------- */
        function collectYtGridSelected() {
            const checked = document.querySelectorAll('.yt-video-check:checked');
            checked.forEach(cb => {
                const card = cb.closest('.video-grid-item');
                const v = JSON.parse(card.dataset.video);
                if (v.is_registered) return;
                if (registerItems.some(r => r.media_key === v.video_id)) return;
                registerItems.push({
                    platform: 'youtube',
                    media_key: v.video_id,
                    sub_key: null,
                    title: v.title,
                    description: v.description || '',
                    thumbnail_url: v.thumbnail_url || '',
                    author_name: v.channel_title || '',
                    published_at: v.published_at || null,
                    media_type: v.media_type || null,
                    category: document.getElementById('bulkCategory').value,
                    url: 'https://www.youtube.com/watch?v=' + v.video_id,
                });
            });
            renderRegisterTable();
        }

        // Insert a button after YT search results to add to register
        const ytSearchResults = document.getElementById('ytSearchResults');
        const addToRegBtn = document.createElement('button');
        addToRegBtn.className = 'mt-3 h-10 px-5 bg-blue-500 text-white text-xs font-bold rounded-full hover:bg-blue-600 transition';
        addToRegBtn.innerHTML = '<i class="fa-solid fa-plus mr-1"></i> 選択した動画を登録リストに追加';
        addToRegBtn.addEventListener('click', collectYtGridSelected);
        ytSearchResults.appendChild(addToRegBtn);

        /* ----------------------------------------------------------------
         *  Register section
         * ---------------------------------------------------------------- */
        function renderRegisterTable() {
            const section = document.getElementById('registerSection');
            const tbody = document.getElementById('registerTableBody');

            if (registerItems.length === 0) {
                section.classList.add('hidden');
                return;
            }
            section.classList.remove('hidden');

            let countNew = 0, countReg = 0, countErr = 0;

            tbody.innerHTML = registerItems.map((item, i) => {
                const isNew = true;
                countNew++;
                return `<tr class="border-b border-slate-100 hover:bg-slate-50" data-idx="${i}">
                    <td class="p-3"><input type="checkbox" class="reg-item-check rounded" data-idx="${i}" checked></td>
                    <td class="p-3">
                        <div class="relative group">
                            ${item.thumbnail_url
                                ? `<img src="${item.thumbnail_url}" class="reg-thumb-img w-20 h-12 rounded object-cover border border-slate-200" data-idx="${i}">`
                                : `<div class="reg-thumb-img w-20 h-12 bg-slate-200 rounded flex items-center justify-center text-slate-400 text-[10px]" data-idx="${i}">${platformLabel(item.platform)}</div>`
                            }
                            <label class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition cursor-pointer" title="サムネイルをアップロード">
                                <i class="fa-solid fa-camera"></i>
                                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden reg-thumb-upload" data-idx="${i}">
                            </label>
                        </div>
                    </td>
                    <td class="p-3"><span class="text-xs font-bold uppercase px-2 py-0.5 rounded-full ${platformBadge(item.platform)}">${item.platform}</span></td>
                    <td class="p-3">
                        <input type="text" class="reg-title-input w-full border border-slate-200 rounded px-2 py-1 text-sm" value="${escAttr(item.title)}" data-idx="${i}">
                    </td>
                    <td class="p-3">
                        <select class="reg-media-type-select border border-slate-200 rounded px-2 py-1 text-xs" data-idx="${i}">
                            <option value="" ${!item.media_type ? 'selected' : ''}>--</option>
                            <option value="video" ${item.media_type === 'video' ? 'selected' : ''}>動画</option>
                            <option value="short" ${item.media_type === 'short' ? 'selected' : ''}>ショート</option>
                            <option value="live" ${item.media_type === 'live' ? 'selected' : ''}>ライブ</option>
                        </select>
                    </td>
                    <td class="p-3">
                        <select class="reg-category-select border border-slate-200 rounded px-2 py-1 text-xs" data-idx="${i}">
                            <option value="" ${!item.category ? 'selected' : ''}>（未選択）</option>
                            ${categories.map(c => `<option value="${c}" ${c === item.category ? 'selected' : ''}>${c}</option>`).join('')}
                        </select>
                    </td>
                    <td class="p-3"><span class="text-xs px-2 py-0.5 rounded-full status-ok font-bold">新規</span></td>
                </tr>`;
            }).join('');

            document.getElementById('regCountNew').textContent = countNew;
            document.getElementById('regCountRegistered').textContent = countReg;
            document.getElementById('regCountError').textContent = countErr;

            // Title / category change listeners
            tbody.querySelectorAll('.reg-title-input').forEach(input => {
                input.addEventListener('change', () => {
                    registerItems[input.dataset.idx].title = input.value.trim();
                });
            });
            tbody.querySelectorAll('.reg-media-type-select').forEach(sel => {
                sel.addEventListener('change', () => {
                    registerItems[sel.dataset.idx].media_type = sel.value || null;
                });
            });
            tbody.querySelectorAll('.reg-category-select').forEach(sel => {
                sel.addEventListener('change', () => {
                    registerItems[sel.dataset.idx].category = sel.value;
                });
            });
            tbody.querySelectorAll('.reg-thumb-upload').forEach(input => {
                input.addEventListener('change', (e) => uploadThumbForItem(e, parseInt(input.dataset.idx), 'register'));
            });
        }

        // Check all
        document.getElementById('regCheckAll').addEventListener('change', (e) => {
            document.querySelectorAll('.reg-item-check').forEach(cb => cb.checked = e.target.checked);
        });

        // Bulk category apply
        document.getElementById('btnApplyCategory').addEventListener('click', () => {
            const cat = document.getElementById('bulkCategory').value;
            registerItems.forEach(item => item.category = cat);
            renderRegisterTable();
        });

        // Clear
        document.getElementById('btnClearRegister').addEventListener('click', () => {
            registerItems = [];
            renderRegisterTable();
        });

        // Register
        document.getElementById('btnRegister').addEventListener('click', async () => {
            const checked = document.querySelectorAll('.reg-item-check:checked');
            const indices = [...checked].map(cb => parseInt(cb.dataset.idx));
            const items = indices.map(i => registerItems[i]).filter(Boolean);

            if (items.length === 0) { alert('登録する項目を選択してください'); return; }
            if (!confirm(`${items.length}件を登録します。よろしいですか？`)) return;

            const btn = document.getElementById('btnRegister');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> 登録中...';

            try {
                const res = await fetch('/hinata/api/bulk_register_media.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items }),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message || '登録が完了しました');
                    registerItems = [];
                    renderRegisterTable();
                } else {
                    alert('エラー: ' + (json.message || '不明なエラー'));
                }
            } catch (e) { alert('通信エラー: ' + e.message); }
            finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> 一括登録';
            }
        });

        /* ----------------------------------------------------------------
         *  Helpers
         * ---------------------------------------------------------------- */
        async function uploadThumbForItem(e, idx, mode, container) {
            const file = e.target.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await fetch('/hinata/api/upload_thumbnail.php', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.status !== 'success') { alert('アップロード失敗: ' + (json.message || '')); return; }
                const url = json.thumbnail_url;
                if (mode === 'register') {
                    registerItems[idx].thumbnail_url = url;
                    renderRegisterTable();
                } else if (mode === 'url-result' && container) {
                    const row = container.querySelectorAll('.url-result-row')[idx];
                    if (row) {
                        const data = JSON.parse(row.dataset.result);
                        data.thumbnail_url = url;
                        row.dataset.result = JSON.stringify(data);
                        const thumb = row.querySelector('.url-result-thumb');
                        if (thumb) {
                            const img = document.createElement('img');
                            img.src = url;
                            img.className = 'url-result-thumb w-20 h-12 rounded object-cover border border-slate-200';
                            thumb.replaceWith(img);
                        }
                    }
                }
            } catch (err) { alert('通信エラー: ' + err.message); }
        }

        function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
        function escAttr(s) { return (s || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
        function platformLabel(p) {
            return { youtube: 'YouTube', instagram: 'Instagram', tiktok: 'TikTok' }[p] || p;
        }
        function platformBadge(p) {
            return {
                youtube: 'bg-red-100 text-red-600',
                instagram: 'bg-purple-100 text-purple-600',
                tiktok: 'bg-slate-100 text-slate-700',
            }[p] || 'bg-slate-100 text-slate-600';
        }

        // Mobile menu
        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };

        // Initialize platform class on tab container
        document.querySelector('.tab-btn.active').closest('.bg-white').classList.add('platform-youtube');
    })();
    </script>
</body>
</html>
