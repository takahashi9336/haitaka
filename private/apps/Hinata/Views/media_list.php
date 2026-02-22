<?php
/**
 * 動画一覧 View
 * 物理パス: haitaka/private/apps/Hinata/Views/media_list.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>動画一覧 - 日向坂ポータル</title>
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

        .video-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .video-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.1);
        }

        .video-thumbnail {
            position: relative;
            padding-bottom: 56.25%; /* 16:9（標準） */
            background: #f1f5f9;
            overflow: hidden;
            border-radius: 2px;
        }
        /* 小さめカード用：16:9を維持（上下が切れないように） */
        .video-thumbnail-sm {
            padding-bottom: 56.25%; /* 16:9 */
        }
        .video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* 公式VIDEO風：セクションタイトル（ティール/ミント） */
        .video-section-title {
            color: #0d9488;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        /* 公式チャンネルボタン（青→緑グラデーション） */
        .btn-official-channel {
            background: linear-gradient(90deg, #0ea5e9 0%, #10b981 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.2s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.35);
        }
        .btn-official-channel:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .video-thumbnail .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .video-card:hover .play-icon {
            opacity: 1;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        #loadingSpinner {
            display: none;
        }
        #loadingSpinner.active {
            display: flex;
        }

        .media-type-tab {
            background: #f1f5f9;
            color: #64748b;
        }
        .media-type-tab:hover {
            background: #e2e8f0;
        }
        .media-type-tab.active-tab {
            background: var(--hinata-theme, #0ea5e9);
            color: white;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
        }

        .video-thumbnail-portrait {
            position: relative;
            padding-bottom: 177.78%; /* 9:16 */
            background: #f1f5f9;
            overflow: hidden;
            border-radius: 2px;
        }
        .video-thumbnail-portrait img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex ?? '#0ea5e9') ?>; }

        /* 動画モーダルのスタイルは video_modal.php 共通コンポーネントで定義 */
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 min-h-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-video text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画一覧</h1>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($isHinataAdmin ?? false): ?>
                <a href="/hinata/media_register.php" class="text-xs font-bold text-white bg-red-500 px-4 py-2 rounded-full hover:bg-red-600 transition shadow-sm">
                    <i class="fa-solid fa-circle-plus mr-1"></i>メディア登録
                </a>
                <?php endif; ?>
                <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                    ポータルへ戻る
                </a>
            </div>
        </header>

        <div class="flex-1 flex flex-col min-h-0">
            <!-- 固定エリア：公式チャンネル + フィルター・ソート -->
            <div class="shrink-0 px-4 pt-3 md:p-10 pb-0 md:pb-4">
                <div class="max-w-5xl mx-auto">
                    <!-- 公式チャンネルリンク -->
                    <section class="hidden md:block mb-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="https://www.youtube.com/@46officialyoutubechannel99" target="_blank" rel="noopener noreferrer" class="btn-official-channel shrink-0">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                <span>youtube 公式チャンネル</span>
                                <i class="fa-solid fa-chevron-right text-[10px] opacity-90"></i>
                            </a>
                            <a href="https://www.youtube.com/@hinatazakachannel" target="_blank" rel="noopener noreferrer" class="btn-official-channel shrink-0">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                <span>日向坂ちゃんねる</span>
                                <i class="fa-solid fa-chevron-right text-[10px] opacity-90"></i>
                            </a>
                        </div>
                    </section>

                    <!-- メディア種別タブ -->
                    <nav id="mediaTypeTabs" class="flex gap-1 mb-3">
                        <button class="media-type-tab active-tab h-9 px-5 rounded-full text-xs font-bold transition" data-type="">すべて</button>
                        <button class="media-type-tab h-9 px-5 rounded-full text-xs font-bold transition" data-type="video">
                            <i class="fa-solid fa-film mr-1"></i>動画
                        </button>
                        <button class="media-type-tab h-9 px-5 rounded-full text-xs font-bold transition" data-type="short">
                            <i class="fa-solid fa-mobile-screen mr-1"></i>ショート
                        </button>
                        <button class="media-type-tab h-9 px-5 rounded-full text-xs font-bold transition" data-type="live">
                            <i class="fa-solid fa-tower-broadcast mr-1"></i>ライブ
                        </button>
                    </nav>

                    <!-- フィルター・表示切替 -->
                    <section class="bg-white rounded-2xl border border-sky-100 shadow-sm">
                        <button id="filterToggleBtn" class="md:hidden w-full flex items-center justify-between px-4 py-3 text-xs font-bold text-slate-600">
                            <span><i class="fa-solid fa-sliders mr-1.5"></i>絞り込み・表示設定</span>
                            <i id="filterToggleIcon" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform"></i>
                        </button>
                        <div id="filterBody" class="hidden md:block p-4 md:pt-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- カテゴリフィルター -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-slate-600">カテゴリ:</label>
                                    <select id="filterCategory" class="h-9 border border-sky-100 rounded-lg px-3 text-xs outline-none bg-slate-50">
                                        <option value="">すべて</option>
                                        <?php foreach ($categories as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- メンバー -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-slate-600">メンバー:</label>
                                    <select id="filterMember" class="h-9 border border-sky-100 rounded-lg px-3 text-xs outline-none bg-slate-50 min-w-[140px]">
                                        <option value="">すべて</option>
                                        <?php foreach ($members as $m): ?>
                                            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['name']) ?><?= !empty($m['is_active']) ? '' : ' (卒)' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- 期別 -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-slate-600">期別:</label>
                                    <select id="filterGeneration" class="h-9 border border-sky-100 rounded-lg px-3 text-xs outline-none bg-slate-50">
                                        <option value="">すべて</option>
                                        <?php
                                        $gens = array_unique(array_map(fn($m) => (int)($m['generation'] ?? 0), $members));
                                        sort($gens);
                                        foreach ($gens as $g):
                                            if ($g <= 0) continue;
                                        ?>
                                            <option value="<?= $g ?>"><?= $g ?>期生</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- 並び順 -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-slate-600">並び順:</label>
                                    <select id="filterSort" class="h-9 border border-sky-100 rounded-lg px-3 text-xs outline-none bg-slate-50">
                                        <option value="newest" selected>アップロード日が新しい順</option>
                                        <option value="oldest">アップロード日が古い順</option>
                                        <option value="title">タイトル順</option>
                                    </select>
                                </div>

                                <!-- 表示形式・サイズ切替 -->
                                <div class="flex items-center gap-2 ml-auto">
                                    <div class="flex items-center gap-1 mr-2">
                                        <button id="btnViewGrid" class="h-9 px-3 bg-sky-500 text-white rounded-lg text-xs font-bold transition">
                                            <i class="fa-solid fa-th"></i> ブロック
                                        </button>
                                        <button id="btnViewList" class="h-9 px-3 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold transition">
                                            <i class="fa-solid fa-list"></i> 一覧
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button id="btnCardSizeNormal" class="h-9 px-2 bg-sky-500 text-white rounded-lg text-[11px] font-bold transition min-w-[52px]">
                                            標準
                                        </button>
                                        <button id="btnCardSizeSmall" class="h-9 px-2 bg-slate-100 text-slate-600 rounded-lg text-[11px] font-bold transition min-w-[52px]">
                                            小さめ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- スクロールエリア：動画のみ -->
            <div class="flex-1 min-h-0 overflow-y-auto p-6 md:p-10 pt-4 md:pt-6" id="mainScrollArea">
                <div class="max-w-5xl mx-auto">
                    <!-- 動画コンテナ（公式VIDEO風：2列・余白多め） -->
                    <div id="videoContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <!-- JavaScript で動的生成 -->
                    </div>

                    <!-- ローディングスピナー -->
                    <div id="loadingSpinner" class="justify-center items-center py-8">
                        <i class="fa-solid fa-spinner fa-spin text-3xl text-sky-500"></i>
                    </div>

                    <!-- 最下部マーカー -->
                    <div id="scrollTrigger" class="h-20"></div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../../components/video_modal.php'; ?>

    <script>
        const videoContainer = document.getElementById('videoContainer');
        const mainScrollArea = document.getElementById('mainScrollArea');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const scrollTrigger = document.getElementById('scrollTrigger');
        const filterCategory = document.getElementById('filterCategory');
        const filterMember = document.getElementById('filterMember');
        const filterGeneration = document.getElementById('filterGeneration');
        const filterSort = document.getElementById('filterSort');
        const btnViewGrid = document.getElementById('btnViewGrid');
        const btnViewList = document.getElementById('btnViewList');
        const btnCardSizeNormal = document.getElementById('btnCardSizeNormal');
        const btnCardSizeSmall = document.getElementById('btnCardSizeSmall');

        let offset = 0;
        let isLoading = false;
        let currentRotation = 0;
        let hasMore = true;
        let currentView = 'grid'; // 'grid' or 'list'
        let currentCardSize = 'normal'; // 'normal' or 'small'
        let currentMediaType = ''; // '' | 'video' | 'short' | 'live'
        let renderedCategoryHeaders = new Set(); // メンバー/期別絞り込み時のカテゴリ帯用
        // カテゴリ表示順（その他は常に最後）
        const CATEGORY_ORDER = ['CM', 'Hinareha', 'Live', 'MV', 'SelfIntro', 'SoloPV', 'Special', 'Teaser', 'Trailer', 'Variety', 'その他'];

        // 初回ロード
        loadVideos();

        // 無限スクロール監視
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isLoading && hasMore) {
                    loadVideos();
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(scrollTrigger);

        // フィルター変更時
        function onFilterChange() {
            offset = 0;
            hasMore = true;
            renderedCategoryHeaders.clear();
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            loadVideos();
        }
        filterCategory.addEventListener('change', onFilterChange);
        filterMember.addEventListener('change', onFilterChange);
        filterGeneration.addEventListener('change', onFilterChange);
        filterSort.addEventListener('change', onFilterChange);

        document.querySelectorAll('.media-type-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.media-type-tab').forEach(t => t.classList.remove('active-tab'));
                tab.classList.add('active-tab');
                currentMediaType = tab.dataset.type;
                updateVideoContainerLayout();
                onFilterChange();
            });
        });

        function isShortLayout() {
            return currentMediaType === 'short';
        }

        function updateVideoContainerLayout() {
            if (currentView === 'grid') {
                if (isShortLayout()) {
                    videoContainer.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4';
                } else if (currentCardSize === 'small') {
                    videoContainer.className = 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4';
                } else {
                    videoContainer.className = 'grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8';
                }
            } else {
                videoContainer.className = 'flex flex-col gap-1.5';
            }
        }

        // 表示形式切替（再読み込みで新しい形式で表示）
        function switchView(view) {
            currentView = view;
            offset = 0;
            hasMore = true;
            renderedCategoryHeaders.clear();
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            if (view === 'grid') {
                btnViewGrid.classList.remove('bg-slate-100', 'text-slate-600');
                btnViewGrid.classList.add('bg-sky-500', 'text-white');
                btnViewList.classList.remove('bg-sky-500', 'text-white');
                btnViewList.classList.add('bg-slate-100', 'text-slate-600');
            } else {
                btnViewList.classList.remove('bg-slate-100', 'text-slate-600');
                btnViewList.classList.add('bg-sky-500', 'text-white');
                btnViewGrid.classList.remove('bg-sky-500', 'text-white');
                btnViewGrid.classList.add('bg-slate-100', 'text-slate-600');
            }
            updateVideoContainerLayout();
            loadVideos();
        }
        btnViewGrid.addEventListener('click', () => switchView('grid'));
        btnViewList.addEventListener('click', () => switchView('list'));

        function switchCardSize(size) {
            currentCardSize = size;
            offset = 0;
            hasMore = true;
            renderedCategoryHeaders.clear();
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            if (size === 'normal') {
                btnCardSizeNormal.classList.remove('bg-slate-100', 'text-slate-600');
                btnCardSizeNormal.classList.add('bg-sky-500', 'text-white');
                btnCardSizeSmall.classList.remove('bg-sky-500', 'text-white');
                btnCardSizeSmall.classList.add('bg-slate-100', 'text-slate-600');
            } else {
                btnCardSizeSmall.classList.remove('bg-slate-100', 'text-slate-600');
                btnCardSizeSmall.classList.add('bg-sky-500', 'text-white');
                btnCardSizeNormal.classList.remove('bg-sky-500', 'text-white');
                btnCardSizeNormal.classList.add('bg-slate-100', 'text-slate-600');
            }
            updateVideoContainerLayout();
            loadVideos();
        }
        btnCardSizeNormal.addEventListener('click', () => switchCardSize('normal'));
        btnCardSizeSmall.addEventListener('click', () => switchCardSize('small'));

        // 動画読み込み
        async function loadVideos() {
            if (isLoading || !hasMore) return;

            isLoading = true;
            loadingSpinner.classList.add('active');

            try {
                const params = new URLSearchParams({
                    offset: offset,
                    limit: 25,
                    category: filterCategory.value,
                    sort: filterSort.value,
                    member_id: filterMember.value || '0',
                    generation: filterGeneration.value || '',
                    media_type: currentMediaType,
                });

                const response = await fetch(`/hinata/api/load_more_media.php?${params}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const useCategoryBands = (filterMember.value !== '' || filterGeneration.value !== '');
                    if (useCategoryBands) {
                        // カテゴリごとにグルーピング（表示順でソート）
                        const groups = {};
                        result.data.forEach(video => {
                            const key = video.category || 'その他';
                            if (!groups[key]) groups[key] = [];
                            groups[key].push(video);
                        });
                        const sortedCategories = Object.keys(groups).sort((a, b) => {
                            const ia = CATEGORY_ORDER.indexOf(a);
                            const ib = CATEGORY_ORDER.indexOf(b);
                            const ai = ia >= 0 ? ia : CATEGORY_ORDER.length;
                            const bi = ib >= 0 ? ib : CATEGORY_ORDER.length;
                            return ai - bi;
                        });
                        sortedCategories.forEach(category => {
                            const videos = groups[category];
                            const catId = 'cat-' + category.replace(/\s/g, '_');
                            let headerEl = videoContainer.querySelector('#' + catId);
                            // 新規セクション：ヘッダーを先に追加
                            if (!renderedCategoryHeaders.has(category)) {
                                renderedCategoryHeaders.add(category);
                                const headerHtml = `
                                    <div id="${catId}" class="col-span-full mt-6 mb-3 pb-2 border-b-2 border-sky-200 category-section-header">
                                        <h2 class="text-lg font-black text-sky-600">${escapeHtml(category)}</h2>
                                    </div>
                                `;
                                // 挿入位置：次に来るべきカテゴリのヘッダーより前、無ければ末尾
                                const nextCatIdx = CATEGORY_ORDER.indexOf(category) + 1;
                                let insertBeforeEl = null;
                                for (let i = nextCatIdx; i < CATEGORY_ORDER.length; i++) {
                                    const nid = 'cat-' + CATEGORY_ORDER[i].replace(/\s/g, '_');
                                    const nextH = videoContainer.querySelector('#' + nid);
                                    if (nextH) { insertBeforeEl = nextH; break; }
                                }
                                if (!insertBeforeEl) insertBeforeEl = null; // 末尾に追加
                                const wrap = document.createElement('div');
                                wrap.innerHTML = headerHtml;
                                if (insertBeforeEl && insertBeforeEl.parentNode === videoContainer) {
                                    videoContainer.insertBefore(wrap.firstElementChild, insertBeforeEl);
                                } else {
                                    videoContainer.appendChild(wrap.firstElementChild);
                                }
                                headerEl = videoContainer.querySelector('#' + catId);
                            }
                            // 動画をこのセクション内の末尾（次のセクションヘッダー直前）に挿入
                            let insertAnchor = null; // null = 末尾に追加
                            if (headerEl) {
                                let n = headerEl.nextElementSibling;
                                while (n) {
                                    if (n.classList && n.classList.contains('category-section-header')) {
                                        insertAnchor = n; // 次のセクションヘッダー直前に挿入
                                        break;
                                    }
                                    n = n.nextElementSibling;
                                }
                            }
                            const frag = document.createDocumentFragment();
                            videos.forEach(video => {
                                const div = document.createElement('div');
                                div.innerHTML = currentView === 'grid' ? renderVideoCard(video) : renderVideoRow(video);
                                const el = div.firstElementChild || div.firstChild;
                                if (el) frag.appendChild(el);
                            });
                            if (insertAnchor && insertAnchor.parentNode === videoContainer) {
                                videoContainer.insertBefore(frag, insertAnchor);
                            } else {
                                videoContainer.appendChild(frag);
                            }
                        });
                    } else {
                        // 通常はフラットに追加
                        result.data.forEach(video => {
                            if (currentView === 'grid') {
                                videoContainer.innerHTML += renderVideoCard(video);
                            } else {
                                videoContainer.innerHTML += renderVideoRow(video);
                            }
                        });
                    }

                    offset += result.data.length;
                    hasMore = result.has_more;

                    if (!hasMore) {
                        scrollTrigger.innerHTML = '<p class="text-center text-slate-400 text-sm">すべての動画を表示しました</p>';
                    }
                } else {
                    alert('エラー: ' + (result.message || '不明なエラー'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                isLoading = false;
                loadingSpinner.classList.remove('active');
            }
        }

        // 表示用サムネイルURL（DBの thumbnail_url が空でも YouTube は media_key から生成可能）
        function getThumbnailUrl(video) {
            if (video.thumbnail_url) return video.thumbnail_url;
            if (video.platform === 'youtube' && video.media_key) {
                return `https://img.youtube.com/vi/${video.media_key}/mqdefault.jpg`;
            }
            return '/assets/images/no-image.jpg';
        }

        // ブロック形式のカード生成（サイズは currentCardSize で切替）
        function renderVideoCard(video) {
            const categoryColors = {
                'CM': 'bg-slate-100 text-slate-700',
                'Hinareha': 'bg-amber-100 text-amber-700',
                'Live': 'bg-purple-100 text-purple-700',
                'MV': 'bg-pink-100 text-pink-700',
                'SelfIntro': 'bg-cyan-100 text-cyan-700',
                'SoloPV': 'bg-blue-100 text-blue-700',
                'Special': 'bg-orange-100 text-orange-700',
                'Teaser': 'bg-emerald-100 text-emerald-700',
                'Trailer': 'bg-rose-100 text-rose-700',
                'Variety': 'bg-yellow-100 text-yellow-700',
            };
            const categoryColor = categoryColors[video.category] || 'bg-slate-100 text-slate-600';
            const primaryDate = video.upload_date || video.release_date || '';
            const videoIsShort = video.media_type === 'short' || (isShortLayout());
            const dataVideo = JSON.stringify({
                platform: video.platform,
                media_key: video.media_key,
                sub_key: video.sub_key || '',
                title: video.title,
                category: video.category,
                thumbnail_url: video.thumbnail_url || '',
                description: video.description || '',
                release_date: primaryDate,
                created_at: video.created_at || '',
                media_type: video.media_type || '',
            }).replace(/&/g, '&amp;').replace(/"/g, '&quot;');

            const dateStr = primaryDate ? new Date(primaryDate).toLocaleDateString('ja-JP') : '';
            const thumbUrl = getThumbnailUrl(video);
            const isSmall = currentCardSize === 'small';

            if (videoIsShort && isShortLayout()) {
                return `
                    <div class="video-card bg-white rounded-lg border border-slate-100 overflow-hidden" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                        <div class="video-thumbnail-portrait rounded-t-lg">
                            <img src="${thumbUrl}" alt="${escapeHtml(video.title)}" onerror="this.src='/assets/images/no-image.jpg'">
                            <div class="play-icon">
                                <i class="fa-solid fa-play text-white text-xl ml-0.5"></i>
                            </div>
                            ${platformBadgeHtml(video.platform)}
                        </div>
                        <div class="p-2">
                            <h3 class="text-[11px] text-slate-700 leading-snug line-clamp-2">${escapeHtml(video.title)}</h3>
                            ${dateStr ? `<p class="text-[10px] text-slate-400 mt-0.5">${dateStr}</p>` : ''}
                        </div>
                    </div>
                `;
            }

            const outerClass = isSmall
                ? 'video-card bg-white rounded border border-slate-100 overflow-hidden text-[11px]'
                : 'video-card bg-white rounded-sm border border-slate-100 overflow-hidden';
            const bodyClass = isSmall ? 'pt-2 pb-1 px-2' : 'pt-3 pb-1';
            const titleClass = isSmall
                ? 'text-[11px] text-slate-700 leading-snug line-clamp-2'
                : 'text-sm text-slate-700 leading-snug line-clamp-2';
            const dateClass = isSmall
                ? 'text-[10px] text-slate-400 mt-0.5'
                : 'text-xs text-slate-400 mt-1';
            return `
                <div class="${outerClass}" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                    <div class="video-thumbnail ${isSmall ? 'video-thumbnail-sm' : 'rounded-t-sm'}">
                        <img src="${thumbUrl}" alt="${escapeHtml(video.title)}" onerror="this.src='/assets/images/no-image.jpg'">
                        <div class="play-icon">
                            <i class="fa-solid fa-play text-white text-2xl ml-1"></i>
                        </div>
                        ${platformBadgeHtml(video.platform)}
                    </div>
                    <div class="${bodyClass}">
                        <h3 class="${titleClass}">${escapeHtml(video.title)}</h3>
                        ${dateStr ? `<p class="${dateClass}">${dateStr}</p>` : ''}
                    </div>
                </div>
            `;
        }

        // 一覧形式の行生成
        function renderVideoRow(video) {
            const categoryColors = {
                'CM': 'bg-slate-100 text-slate-700',
                'Hinareha': 'bg-amber-100 text-amber-700',
                'Live': 'bg-purple-100 text-purple-700',
                'MV': 'bg-pink-100 text-pink-700',
                'SelfIntro': 'bg-cyan-100 text-cyan-700',
                'SoloPV': 'bg-blue-100 text-blue-700',
                'Special': 'bg-orange-100 text-orange-700',
                'Teaser': 'bg-emerald-100 text-emerald-700',
                'Trailer': 'bg-rose-100 text-rose-700',
                'Variety': 'bg-yellow-100 text-yellow-700',
            };
            const categoryColor = categoryColors[video.category] || 'bg-slate-100 text-slate-600';
            const primaryDate = video.upload_date || video.release_date || '';
            const dataVideo = JSON.stringify({
                platform: video.platform,
                media_key: video.media_key,
                sub_key: video.sub_key || '',
                title: video.title,
                category: video.category,
                thumbnail_url: video.thumbnail_url || '',
                description: video.description || '',
                release_date: primaryDate,
                created_at: video.created_at || '',
                media_type: video.media_type || '',
            }).replace(/&/g, '&amp;').replace(/"/g, '&quot;');

            const dateStr = primaryDate
                ? new Date(primaryDate).toLocaleDateString('ja-JP')
                : (video.created_at ? '登録日: ' + new Date(video.created_at).toLocaleDateString('ja-JP') : '');
            const thumbUrl = getThumbnailUrl(video);
            const isVideoShort = video.media_type === 'short';
            return `
                <div class="video-card bg-white rounded-lg border border-sky-100 shadow-sm px-3 py-2 flex items-center gap-3 hover:bg-sky-50/50 transition cursor-pointer" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                    <div class="w-16 shrink-0 ${isVideoShort ? 'aspect-[9/16]' : 'aspect-video'} rounded overflow-hidden bg-slate-100 flex-shrink-0 relative">
                        <img src="${thumbUrl}" alt="" class="w-full h-full object-cover" onerror="this.src='/assets/images/no-image.jpg'">
                    </div>
                    <div class="flex-1 min-w-0 flex items-center gap-4">
                        ${platformBadgeInline(video.platform)}
                        <span class="category-badge ${categoryColor} shrink-0">${video.category || ''}</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-bold text-slate-800 truncate">${escapeHtml(video.title)}</h3>
                        </div>
                        <span class="text-xs text-slate-400 shrink-0">${dateStr}</span>
                    </div>
                </div>
            `;
        }

        function getVideoUrl(video) { return _vmGetVideoUrl(video); }
        function getEmbedUrl(video) { return _vmGetEmbedUrl(video); }

        function openVideoModal(cardEl, ev) {
            const dataStr = cardEl.getAttribute('data-video');
            if (!dataStr) return;
            try { openVideoModalWithData(JSON.parse(dataStr), ev); } catch(e) {}
        }

        // HTMLエスケープ
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function platformBadgeHtml(platform) {
            if (!platform) return '';
            const cfg = {
                youtube:   { bg: 'bg-red-600', icon: 'fa-youtube', label: 'Youtube' },
                instagram: { bg: 'bg-gradient-to-r from-purple-500 to-pink-500', icon: 'fa-instagram', label: 'Instagram' },
                tiktok:    { bg: 'bg-slate-800', icon: 'fa-tiktok', label: 'TikTok' },
            };
            const c = cfg[platform];
            if (!c) return '';
            return `<span class="absolute top-1.5 left-1.5 text-[9px] font-bold text-white px-1.5 py-0.5 rounded ${c.bg}"><i class="fa-brands fa-${c.icon} mr-0.5"></i>${c.label}</span>`;
        }

        function platformBadgeInline(platform) {
            if (!platform) return '';
            const cfg = {
                youtube:   { bg: 'bg-red-600', icon: 'fa-youtube', label: 'Youtube' },
                instagram: { bg: 'bg-gradient-to-r from-purple-500 to-pink-500', icon: 'fa-instagram', label: 'Instagram' },
                tiktok:    { bg: 'bg-slate-800', icon: 'fa-tiktok', label: 'TikTok' },
            };
            const c = cfg[platform];
            if (!c) return '';
            return `<span class="text-[9px] font-bold text-white px-1.5 py-0.5 rounded shrink-0 ${c.bg}"><i class="fa-brands fa-${c.icon} mr-0.5"></i>${c.label}</span>`;
        }

        // フィルター折り畳み（スマホ）
        document.getElementById('filterToggleBtn').addEventListener('click', () => {
            const body = document.getElementById('filterBody');
            const icon = document.getElementById('filterToggleIcon');
            body.classList.toggle('hidden');
            icon.style.transform = body.classList.contains('hidden') ? '' : 'rotate(180deg)';
        });

        // モバイルメニュー
        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };
    </script>
</body>
</html>
