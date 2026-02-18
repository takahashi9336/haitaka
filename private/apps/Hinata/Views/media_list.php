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
            padding-bottom: 56.25%; /* 16:9 */
            background: #f1f5f9;
            overflow: hidden;
            border-radius: 2px;
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

        /* 動画モーダル（メンバー帳と同様のアニメーション） */
        #videoModal { opacity: 0; }
        #videoModal.active {
            display: block !important;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            opacity: 1;
        }
        @keyframes backdropFadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        @keyframes backdropFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }
        #videoModal.modal-opening {
            animation: backdropFadeIn 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        #videoModal.modal-opening .video-modal-content {
            animation: modalExpandFromPoint 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        #videoModal.modal-closing {
            animation: backdropFadeOut 0.3s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards;
        }
        #videoModal.modal-closing .video-modal-content {
            animation: modalShrinkToPoint 0.3s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards;
        }
        .video-desc {
            font-size: 0.7rem;
            line-height: 1.3;
            max-height: 3.6em;
            overflow: hidden;
            white-space: pre-wrap;
        }
        .video-desc.expanded {
            max-height: none;
        }
        @keyframes modalExpandFromPoint {
            0% {
                transform: translate(var(--modal-translate-x, 0), var(--modal-translate-y, 0)) scale(0.3);
                opacity: 0;
            }
            100% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
        }
        @keyframes modalShrinkToPoint {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--modal-translate-x, 0), var(--modal-translate-y, 0)) scale(0.3);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-video text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画一覧</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                ポータルへ戻る
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10" id="mainScrollArea">
            <div class="max-w-5xl mx-auto">
                
                <!-- 公式チャンネルリンク -->
                <section class="mb-8">
                    <div class="flex flex-wrap items-center gap-3 mb-6">
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

                <!-- フィルター・表示切替 -->
                <section class="bg-white rounded-2xl border border-sky-100 shadow-sm p-4 mb-6">
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

                        <!-- 表示形式切替 -->
                        <div class="flex items-center gap-2 ml-auto">
                            <button id="btnViewGrid" class="h-9 px-3 bg-sky-500 text-white rounded-lg text-xs font-bold transition">
                                <i class="fa-solid fa-th"></i> ブロック
                            </button>
                            <button id="btnViewList" class="h-9 px-3 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold transition">
                                <i class="fa-solid fa-list"></i> 一覧
                            </button>
                        </div>
                    </div>
                </section>

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
    </main>

    <!-- 動画再生モーダル -->
    <div id="videoModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-slate-900/80 backdrop-blur-xl transition-all">
        <div class="relative w-full max-w-4xl mx-auto md:my-10 min-h-full flex items-center p-4">
            <div class="video-modal-content bg-white w-full rounded-[2rem] md:rounded-[3rem] shadow-2xl overflow-hidden relative">
                <button onclick="closeVideoModal()" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-10 hover:bg-white shadow-lg transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
                <div class="p-6 md:p-8 pt-16">
                    <div id="videoModalEmbed" class="aspect-video w-full rounded-xl overflow-hidden bg-black shadow-xl">
                        <iframe id="videoModalIframe" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <div id="videoModalExternal" class="hidden aspect-video w-full rounded-xl bg-slate-100 flex flex-col items-center justify-center gap-4 p-8">
                        <i class="fa-solid fa-external-link-alt text-4xl text-slate-400"></i>
                        <p class="text-sm text-slate-600 text-center">この動画はモーダル内で再生できません</p>
                        <a id="videoModalExternalLink" href="#" target="_blank" class="px-6 py-3 bg-sky-500 text-white rounded-full font-bold text-sm hover:bg-sky-600 transition">
                            新しいタブで開く
                        </a>
                    </div>
                    <div class="mt-4">
                        <span id="videoModalCategory" class="category-badge bg-sky-100 text-sky-700"></span>
                        <h2 id="videoModalTitle" class="text-lg font-bold text-slate-800 mt-2"></h2>
                        <p id="videoModalDate" class="text-xs text-slate-400 mt-1"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

        let offset = 0;
        let isLoading = false;
        let hasMore = true;
        let currentView = 'grid'; // 'grid' or 'list'

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
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            loadVideos();
        }
        filterCategory.addEventListener('change', onFilterChange);
        filterMember.addEventListener('change', onFilterChange);
        filterGeneration.addEventListener('change', onFilterChange);
        filterSort.addEventListener('change', onFilterChange);

        // 表示形式切替（再読み込みで新しい形式で表示）
        function switchView(view) {
            currentView = view;
            offset = 0;
            hasMore = true;
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            if (view === 'grid') {
                btnViewGrid.classList.remove('bg-slate-100', 'text-slate-600');
                btnViewGrid.classList.add('bg-sky-500', 'text-white');
                btnViewList.classList.remove('bg-sky-500', 'text-white');
                btnViewList.classList.add('bg-slate-100', 'text-slate-600');
                videoContainer.className = 'grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8';
            } else {
                btnViewList.classList.remove('bg-slate-100', 'text-slate-600');
                btnViewList.classList.add('bg-sky-500', 'text-white');
                btnViewGrid.classList.remove('bg-sky-500', 'text-white');
                btnViewGrid.classList.add('bg-slate-100', 'text-slate-600');
                videoContainer.className = 'flex flex-col gap-1.5';
            }
            loadVideos();
        }
        btnViewGrid.addEventListener('click', () => switchView('grid'));
        btnViewList.addEventListener('click', () => switchView('list'));

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
                });

                const response = await fetch(`/hinata/api/load_more_media.php?${params}`);
                const result = await response.json();

                if (result.status === 'success') {
                    result.data.forEach(video => {
                        if (currentView === 'grid') {
                            videoContainer.innerHTML += renderVideoCard(video);
                        } else {
                            videoContainer.innerHTML += renderVideoRow(video);
                        }
                    });

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

        function toggleDesc(ev) {
            ev.stopPropagation();
            const btn = ev.currentTarget;
            const container = btn.closest('.video-desc-container');
            if (!container) return;
            const p = container.querySelector('.video-desc');
            if (!p) return;
            const expanded = p.classList.toggle('expanded');
            btn.textContent = expanded ? '閉じる' : '...もっと見る';
        }

        // ブロック形式のカード生成
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
            const hasDesc = video.description && video.description.trim() !== '';
            const dataVideo = JSON.stringify({
                platform: video.platform,
                media_key: video.media_key,
                sub_key: video.sub_key || '',
                title: video.title,
                category: video.category,
                thumbnail_url: video.thumbnail_url || '',
                description: video.description || '',
                release_date: primaryDate,
                created_at: video.created_at || ''
            }).replace(/&/g, '&amp;').replace(/"/g, '&quot;');

            const dateStr = primaryDate ? new Date(primaryDate).toLocaleDateString('ja-JP') : '';
            const thumbUrl = getThumbnailUrl(video);
            return `
                <div class="video-card bg-white rounded-sm border border-slate-100 overflow-hidden" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                    <div class="video-thumbnail rounded-t-sm">
                        <img src="${thumbUrl}" alt="${escapeHtml(video.title)}" onerror="this.src='/assets/images/no-image.jpg'">
                        <div class="play-icon">
                            <i class="fa-solid fa-play text-white text-2xl ml-1"></i>
                        </div>
                    </div>
                    <div class="pt-3 pb-1">
                        <h3 class="text-sm text-slate-700 leading-snug line-clamp-2">${escapeHtml(video.title)}</h3>
                        ${dateStr ? `<p class="text-xs text-slate-400 mt-1">${dateStr}</p>` : ''}
                        ${hasDesc ? `
                        <div class="mt-1 text-[11px] text-slate-500 video-desc-container">
                            <p class="video-desc">${escapeHtml(video.description || '')}</p>
                            <button type="button" class="text-[10px] text-sky-600 font-bold mt-0.5 hover:underline" onclick="toggleDesc(event)">...もっと見る</button>
                        </div>` : ''}
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
            const hasDesc = video.description && video.description.trim() !== '';
            const dataVideo = JSON.stringify({
                platform: video.platform,
                media_key: video.media_key,
                sub_key: video.sub_key || '',
                title: video.title,
                category: video.category,
                thumbnail_url: video.thumbnail_url || '',
                description: video.description || '',
                release_date: primaryDate,
                created_at: video.created_at || ''
            }).replace(/&/g, '&amp;').replace(/"/g, '&quot;');

            const dateStr = primaryDate
                ? new Date(primaryDate).toLocaleDateString('ja-JP')
                : (video.created_at ? '登録日: ' + new Date(video.created_at).toLocaleDateString('ja-JP') : '');
            const thumbUrl = getThumbnailUrl(video);
            return `
                <div class="video-card bg-white rounded-lg border border-sky-100 shadow-sm px-3 py-2 flex items-center gap-3 hover:bg-sky-50/50 transition cursor-pointer" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                    <div class="w-16 shrink-0 aspect-video rounded overflow-hidden bg-slate-100 flex-shrink-0 relative">
                        <img src="${thumbUrl}" alt="" class="w-full h-full object-cover" onerror="this.src='/assets/images/no-image.jpg'">
                    </div>
                    <div class="flex-1 min-w-0 flex items-center gap-4">
                        <span class="category-badge ${categoryColor} shrink-0">${video.category}</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-bold text-slate-800 truncate">${escapeHtml(video.title)}</h3>
                            ${hasDesc ? `
                            <div class="mt-0.5 text-[11px] text-slate-500 video-desc-container">
                                <p class="video-desc">${escapeHtml(video.description || '')}</p>
                                <button type="button" class="text-[10px] text-sky-600 font-bold mt-0.5 hover:underline" onclick="toggleDesc(event)">...もっと見る</button>
                            </div>` : ''}
                        </div>
                        <span class="text-xs text-slate-400 shrink-0">${dateStr}</span>
                    </div>
                </div>
            `;
        }

        // プラットフォーム別のURL生成
        function getVideoUrl(video) {
            if (video.platform === 'youtube') {
                return `https://www.youtube.com/watch?v=${video.media_key}`;
            } else if (video.platform === 'tiktok') {
                return `https://www.tiktok.com/${video.sub_key || '@user'}/video/${video.media_key}`;
            } else if (video.platform === 'instagram') {
                return `https://www.instagram.com/reel/${video.media_key}/`;
            }
            return '#';
        }

        // 埋め込み可能なURLを取得（YouTubeのみモーダル内再生対応）
        function getEmbedUrl(video) {
            if (video.platform === 'youtube') {
                return `https://www.youtube.com/embed/${video.media_key}?rel=0`;
            }
            if (video.platform === 'tiktok') {
                return `https://www.tiktok.com/embed/v2/${video.media_key}`;
            }
            if (video.platform === 'instagram') {
                return `https://www.instagram.com/reel/${video.media_key}/embed/`;
            }
            return null;
        }

        // モーダルで動画を開く（メンバー帳と同様のクリック位置から拡大アニメーション）
        function openVideoModal(cardEl, ev) {
            const dataStr = cardEl.getAttribute('data-video');
            if (!dataStr) return;
            let video;
            try {
                video = JSON.parse(dataStr);
            } catch (e) {
                return;
            }

            const modal = document.getElementById('videoModal');
            const iframe = document.getElementById('videoModalIframe');
            const embedArea = document.getElementById('videoModalEmbed');
            const externalArea = document.getElementById('videoModalExternal');
            const externalLink = document.getElementById('videoModalExternalLink');

            // クリック位置から画面中央へのアニメーション用
            if (ev && ev.currentTarget) {
                const rect = ev.currentTarget.getBoundingClientRect();
                const clickX = rect.left + rect.width / 2;
                const clickY = rect.top + rect.height / 2;
                const viewportCenterX = window.innerWidth / 2;
                const viewportCenterY = window.innerHeight / 2;
                modal.style.setProperty('--modal-translate-x', `${clickX - viewportCenterX}px`);
                modal.style.setProperty('--modal-translate-y', `${clickY - viewportCenterY}px`);
            } else {
                modal.style.setProperty('--modal-translate-x', '0');
                modal.style.setProperty('--modal-translate-y', '0');
            }

            const embedUrl = getEmbedUrl(video);
            if (embedUrl) {
                embedArea.classList.remove('hidden');
                externalArea.classList.add('hidden');
                iframe.src = embedUrl;
            } else {
                embedArea.classList.add('hidden');
                externalArea.classList.remove('hidden');
                externalLink.href = getVideoUrl(video);
                iframe.src = '';
            }

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
            const categoryEl = document.getElementById('videoModalCategory');
            categoryEl.textContent = video.category || '';
            categoryEl.className = 'category-badge ' + (categoryColors[video.category] || 'bg-slate-100 text-slate-600');
            document.getElementById('videoModalTitle').textContent = video.title || '';
            const primaryDate = video.release_date || video.created_at || '';
            document.getElementById('videoModalDate').textContent = primaryDate
                ? new Date(primaryDate).toLocaleDateString('ja-JP')
                : (video.created_at ? '登録日: ' + new Date(video.created_at).toLocaleDateString('ja-JP') : '');

            modal.classList.remove('modal-closing');
            modal.classList.add('active', 'modal-opening');
            document.body.style.overflow = 'hidden';

            setTimeout(() => modal.classList.remove('modal-opening'), 650);
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const iframe = document.getElementById('videoModalIframe');
            modal.classList.remove('modal-opening');
            modal.classList.add('modal-closing');
            setTimeout(() => {
                modal.classList.remove('active', 'modal-closing');
                iframe.src = '';
                document.body.style.overflow = '';
            }, 300);
        }
        document.getElementById('videoModal').onclick = (e) => {
            if (e.target.id === 'videoModal') closeVideoModal();
        };

        // HTMLエスケープ
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // モバイルメニュー
        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };
    </script>
</body>
</html>
