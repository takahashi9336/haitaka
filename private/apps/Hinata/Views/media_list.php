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
            border-radius: 0; /* 角丸はカード外枠で統一（portal 寄せ） */
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

        .media-type-tab,
        .platform-tab {
            color: #475569; /* slate-600 */
        }
        .media-type-tab:hover,
        .platform-tab:hover {
            background: rgba(255, 255, 255, 0.65);
        }
        .media-type-tab.active-tab,
        .platform-tab.active-tab {
            background: #ffffff;
            color: #0f172a; /* slate-900 */
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .video-thumbnail-portrait {
            position: relative;
            padding-bottom: 177.78%; /* 9:16 */
            background: #f1f5f9;
            overflow: hidden;
            border-radius: 0; /* 角丸はカード外枠で統一（portal 寄せ） */
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
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-video text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight">動画一覧</h1>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($isHinataAdmin ?? false): ?>
                <a href="/hinata/media_register.php" class="text-xs font-bold text-white bg-red-500 px-4 py-2 rounded-full hover:bg-red-600 transition shadow-sm">
                    <i class="fa-solid fa-circle-plus mr-1"></i>メディア登録
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- スクロール領域（フィルタ + 動画） -->
        <div class="flex-1 min-h-0 overflow-y-auto" id="mainScrollArea">
            <!-- フィルター・タブ -->
            <div class="px-4 pt-3 md:p-6 pb-0 md:pb-4">
                <div class="max-w-[76.8rem] mx-auto">
                    <!-- メディア種別タブ + プラットフォーム（トグル） -->
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <nav id="mediaTypeTabs">
                            <div class="inline-flex items-center gap-1 p-1 rounded-full bg-slate-100 border border-slate-200 shadow-sm">
                                <button class="media-type-tab active-tab h-10 px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-2" data-type="">
                                    すべて
                                </button>
                                <button class="media-type-tab h-10 px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-2" data-type="video">
                                    <i class="fa-solid fa-play hidden sm:inline"></i>
                                    動画
                                </button>
                                <button class="media-type-tab h-10 px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-2" data-type="short">
                                    <i class="fa-solid fa-tablet-screen-button hidden sm:inline"></i>
                                    ショート
                                </button>
                                <button class="media-type-tab h-10 px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-2" data-type="live">
                                    <i class="fa-solid fa-circle hidden sm:inline"></i>
                                    ライブ
                                </button>
                            </div>
                        </nav>

                        <div id="platformTabs" class="inline-flex items-center gap-1 p-1 rounded-full bg-slate-100 border border-slate-200 shadow-sm">
                            <input type="hidden" id="filterPlatform" value="">
                            <button type="button" class="platform-tab active-tab h-10 px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-2" data-platform="">
                                すべて
                            </button>
                            <button type="button" class="platform-tab h-10 px-3 sm:px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-1 sm:gap-2" data-platform="youtube" aria-label="YouTube" title="YouTube">
                                <i class="fa-brands fa-youtube text-red-500"></i>
                                <span class="hidden sm:inline">YouTube</span>
                            </button>
                            <button type="button" class="platform-tab h-10 px-3 sm:px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-1 sm:gap-2" data-platform="tiktok" aria-label="TikTok" title="TikTok">
                                <i class="fa-brands fa-tiktok text-slate-900"></i>
                                <span class="hidden sm:inline">TikTok</span>
                            </button>
                            <button type="button" class="platform-tab h-10 px-3 sm:px-5 rounded-full text-[11px] sm:text-xs font-bold transition flex items-center gap-1 sm:gap-2" data-platform="instagram" aria-label="Instagram" title="Instagram">
                                <i class="fa-brands fa-instagram text-pink-500"></i>
                                <span class="hidden sm:inline">Instagram</span>
                            </button>
                        </div>
                    </div>

                    <!-- フィルター・表示切替 -->
                    <section class="bg-white rounded-2xl border border-sky-100 shadow-sm">
                        <button id="filterToggleBtn" class="md:hidden w-full flex items-center justify-between px-4 py-3 text-xs font-bold text-slate-600">
                            <span><i class="fa-solid fa-sliders mr-1.5"></i>絞り込み・表示設定</span>
                            <i id="filterToggleIcon" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform"></i>
                        </button>
                        <div id="filterBody" class="hidden md:block p-4 md:pt-4">
                            <?php
                            $oshi = $_SESSION['oshi'] ?? [];
                            if (!empty($oshi)):
                            ?>
                            <div class="flex items-center gap-2 mb-3 pb-3 border-b border-slate-100">
                                <?php if (!empty($oshi[9])): ?>
                                <button type="button" class="oshi-quick-filter h-7 px-3 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-100 transition" data-member-id="<?= $oshi[9]['id'] ?>"><i class="fa-solid fa-crown mr-1"></i><?= htmlspecialchars($oshi[9]['name']) ?></button>
                                <?php endif; ?>
                                <?php if (!empty($oshi[8])): ?>
                                <button type="button" class="oshi-quick-filter h-7 px-3 rounded-full text-[10px] font-bold bg-pink-50 text-pink-600 border border-pink-200 hover:bg-pink-100 transition" data-member-id="<?= $oshi[8]['id'] ?>"><i class="fa-solid fa-heart mr-1"></i><?= htmlspecialchars($oshi[8]['name']) ?></button>
                                <?php endif; ?>
                                <?php if (!empty($oshi[7])): ?>
                                <button type="button" class="oshi-quick-filter h-7 px-3 rounded-full text-[10px] font-bold bg-rose-50 text-rose-500 border border-rose-200 hover:bg-rose-100 transition" data-member-id="<?= $oshi[7]['id'] ?>"><i class="fa-regular fa-heart mr-1"></i><?= htmlspecialchars($oshi[7]['name']) ?></button>
                                <?php endif; ?>
                                <button type="button" id="oshiQuickClearBtn" class="hidden h-7 w-7 rounded-full bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200 transition flex items-center justify-center" title="推しフィルタ解除" aria-label="推しフィルタ解除">
                                    <i class="fa-solid fa-xmark text-[11px]"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- カテゴリフィルター -->
                                <div class="relative" data-dropdown>
                                    <input type="hidden" id="filterCategory" value="">
                                    <button type="button" class="h-9 border border-sky-100 rounded-lg bg-slate-50 flex items-center gap-2 px-3 pr-2 text-left hover:bg-slate-100/70 transition" data-dropdown-button aria-haspopup="listbox" aria-expanded="false">
                                        <span class="text-[11px] font-bold text-slate-600 whitespace-nowrap">カテゴリ</span>
                                        <span class="text-xs text-slate-700 truncate max-w-[10rem]" data-dropdown-value>すべて</span>
                                        <i class="fa-solid fa-chevron-down ml-auto text-[10px] text-slate-400"></i>
                                    </button>
                                    <div class="hidden absolute z-20 mt-1 w-[14rem] rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden max-h-72 overflow-y-auto" data-dropdown-menu role="listbox">
                                        <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="">すべて</button>
                                        <?php foreach ($categories as $key => $label): ?>
                                            <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- メンバー -->
                                <div class="relative" data-dropdown>
                                    <input type="hidden" id="filterMember" value="">
                                    <button type="button" class="h-9 border border-sky-100 rounded-lg bg-slate-50 flex items-center gap-2 px-3 pr-2 text-left hover:bg-slate-100/70 transition min-w-[220px]" data-dropdown-button aria-haspopup="listbox" aria-expanded="false">
                                        <span class="text-[11px] font-bold text-slate-600 whitespace-nowrap">メンバー</span>
                                        <span class="text-xs text-slate-700 truncate max-w-[12rem]" data-dropdown-value>すべて</span>
                                        <i class="fa-solid fa-chevron-down ml-auto text-[10px] text-slate-400"></i>
                                    </button>
                                    <div class="hidden absolute z-20 mt-1 w-[22rem] max-w-[90vw] rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden max-h-96 overflow-y-auto" data-dropdown-menu role="listbox">
                                        <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="">すべて</button>
                                        <?php
                                        $memberSelectBlankLabel = 'すべて';
                                        $memberSelectShowGraduate = true;
                                        foreach ($members as $m):
                                            $mid = (string)($m['id'] ?? '');
                                            $mname = (string)($m['name'] ?? '');
                                            if ($mid === '' || $mname === '') continue;
                                        ?>
                                            <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="<?= htmlspecialchars($mid) ?>"><?= htmlspecialchars($mname) ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- 期別 -->
                                <div class="relative" data-dropdown>
                                    <input type="hidden" id="filterGeneration" value="">
                                    <button type="button" class="h-9 border border-sky-100 rounded-lg bg-slate-50 flex items-center gap-2 px-3 pr-2 text-left hover:bg-slate-100/70 transition" data-dropdown-button aria-haspopup="listbox" aria-expanded="false">
                                        <span class="text-[11px] font-bold text-slate-600 whitespace-nowrap">期別</span>
                                        <span class="text-xs text-slate-700 truncate max-w-[7rem]" data-dropdown-value>すべて</span>
                                        <i class="fa-solid fa-chevron-down ml-auto text-[10px] text-slate-400"></i>
                                    </button>
                                    <div class="hidden absolute z-20 mt-1 w-[10rem] rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden max-h-72 overflow-y-auto" data-dropdown-menu role="listbox">
                                        <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="">すべて</button>
                                        <?php
                                        $gens = array_unique(array_map(fn($m) => (int)($m['generation'] ?? 0), $members));
                                        sort($gens);
                                        foreach ($gens as $g):
                                            if ($g <= 0) continue;
                                        ?>
                                            <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="<?= $g ?>"><?= $g ?>期生</button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- 表示形式・サイズ切替 -->
                                <div class="flex items-center gap-2 ml-auto">
                                    <div class="flex items-center gap-1 mr-2">
                                        <!-- 並び順（表示切替エリアへ移動） -->
                                        <div class="relative mr-2" data-dropdown data-sort-dropdown>
                                            <input type="hidden" id="filterSort" value="newest">
                                            <button type="button" class="h-9 rounded-full border border-slate-200 bg-white px-3 pr-2 flex items-center gap-2 text-left hover:bg-slate-50 transition shadow-sm" data-dropdown-button aria-haspopup="listbox" aria-expanded="false">
                                                <span class="text-xs font-bold text-slate-700" data-dropdown-value>新しい順</span>
                                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                                            </button>
                                            <div class="hidden absolute z-20 mt-1 w-[10rem] rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden" data-dropdown-menu role="listbox">
                                                <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="newest">新しい順</button>
                                                <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="oldest">古い順</button>
                                                <button type="button" class="w-full px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left" data-value="title">タイトル順</button>
                                            </div>
                                        </div>

                                        <!-- 表示形式トグル（アイコンのみ） -->
                                        <div class="h-9 p-0.5 rounded-full bg-slate-100 border border-slate-200 flex items-center shadow-sm">
                                            <button id="btnViewGrid" type="button" class="h-8 w-9 rounded-full bg-sky-500 text-white flex items-center justify-center transition" aria-label="ブロック表示" title="ブロック表示">
                                                <i class="fa-solid fa-table-cells text-[12px]"></i>
                                            </button>
                                            <button id="btnViewList" type="button" class="h-8 w-9 rounded-full text-slate-500 flex items-center justify-center transition hover:bg-white/80" aria-label="一覧表示" title="一覧表示">
                                                <i class="fa-solid fa-bars text-[12px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- サイズトグル（アイコンのみ） -->
                                    <div class="h-9 p-0.5 rounded-full bg-slate-100 border border-slate-200 flex items-center shadow-sm">
                                        <button id="btnCardSizeSmall" type="button" class="h-8 w-9 rounded-full bg-sky-500 text-white flex items-center justify-center transition" aria-label="カード小さめ" title="カード小さめ">
                                            <i class="fa-regular fa-square text-[10px]"></i>
                                        </button>
                                        <button id="btnCardSizeNormal" type="button" class="h-8 w-9 rounded-full text-slate-500 flex items-center justify-center transition hover:bg-white/80" aria-label="カード大きめ" title="カード大きめ">
                                            <i class="fa-solid fa-square text-[14px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- 動画一覧 -->
            <div class="p-6 md:p-6 pt-4 md:pt-6">
                <div class="max-w-[76.8rem] mx-auto">
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

                <!-- 最上部へ戻る（スクロールエリア内 左下） -->
                <div class="sticky left-0 bottom-5 z-[90] pointer-events-none">
                    <div class="pointer-events-auto">
                        <button id="backToTopBtn" type="button" class="hidden ml-5 w-12 h-12 rounded-full bg-slate-900/80 text-white shadow-lg hover:bg-slate-900 transition flex items-center justify-center backdrop-blur" aria-label="最上部へ戻る" title="最上部へ戻る">
                            <i class="fa-solid fa-arrow-up text-sm"></i>
                        </button>
                    </div>
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
        const filterPlatform = document.getElementById('filterPlatform');
        const filterCategory = document.getElementById('filterCategory');
        const filterMember = document.getElementById('filterMember');
        const filterGeneration = document.getElementById('filterGeneration');
        const filterSort = document.getElementById('filterSort');
        const btnViewGrid = document.getElementById('btnViewGrid');
        const btnViewList = document.getElementById('btnViewList');
        const btnCardSizeNormal = document.getElementById('btnCardSizeNormal');
        const btnCardSizeSmall = document.getElementById('btnCardSizeSmall');
        const backToTopBtn = document.getElementById('backToTopBtn');

        // カスタムドロップダウン（UIは1要素、値はhidden inputで保持）
        function initFilterDropdowns() {
            const dropdowns = document.querySelectorAll('[data-dropdown]');

            function closeAll(exceptEl = null) {
                dropdowns.forEach(dd => {
                    if (exceptEl && dd === exceptEl) return;
                    const btn = dd.querySelector('[data-dropdown-button]');
                    const menu = dd.querySelector('[data-dropdown-menu]');
                    if (!btn || !menu) return;
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                });
            }

            dropdowns.forEach(dd => {
                const input = dd.querySelector('input[type="hidden"]');
                const btn = dd.querySelector('[data-dropdown-button]');
                const menu = dd.querySelector('[data-dropdown-menu]');
                const valueEl = dd.querySelector('[data-dropdown-value]');
                if (!input || !btn || !menu || !valueEl) return;

                const items = Array.from(menu.querySelectorAll('button[data-value]'));

                function syncLabelFromValue() {
                    const v = input.value;
                    const match = items.find(b => b.dataset.value === v);
                    valueEl.textContent = match ? (match.textContent || '').trim() : (items[0]?.textContent || 'すべて').trim();
                }

                btn.addEventListener('click', () => {
                    const isOpen = !menu.classList.contains('hidden');
                    if (isOpen) {
                        closeAll();
                        return;
                    }
                    closeAll(dd);
                    menu.classList.remove('hidden');
                    btn.setAttribute('aria-expanded', 'true');
                });

                items.forEach(item => {
                    item.addEventListener('click', () => {
                        const nextVal = item.dataset.value ?? '';
                        if (input.value !== nextVal) {
                            input.value = nextVal;
                            syncLabelFromValue();
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            syncLabelFromValue();
                        }
                        closeAll();
                    });
                });

                syncLabelFromValue();
                input.addEventListener('change', syncLabelFromValue);
            });

            document.addEventListener('click', (e) => {
                const target = e.target;
                if (!(target instanceof Element)) return;
                const inside = target.closest('[data-dropdown]');
                if (!inside) closeAll();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeAll();
            });
        }

        // プラットフォーム（トグル）
        function initPlatformTabs() {
            const input = document.getElementById('filterPlatform');
            const tabs = document.querySelectorAll('.platform-tab');
            if (!input || tabs.length === 0) return;

            function setActive(nextVal) {
                tabs.forEach(t => {
                    const isActive = (t.dataset.platform ?? '') === nextVal;
                    t.classList.toggle('active-tab', isActive);
                });
            }

            setActive(input.value || '');

            tabs.forEach(t => {
                t.addEventListener('click', () => {
                    const nextVal = t.dataset.platform ?? '';
                    if (input.value !== nextVal) {
                        input.value = nextVal;
                        setActive(nextVal);
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        setActive(nextVal);
                    }
                });
            });

            input.addEventListener('change', () => setActive(input.value || ''));
        }

        let offset = 0;
        let isLoading = false;
        let currentRotation = 0;
        let hasMore = true;
        let currentView = 'grid'; // 'grid' or 'list'
        let currentCardSize = 'small'; // 'normal'(大きめ) or 'small'(小さめ) デフォルトは小さめ
        let currentMediaType = ''; // '' | 'video' | 'short' | 'live'

        // URLパラメータの member_id / platform で初期フィルタ
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('member_id')) {
            filterMember.value = urlParams.get('member_id');
        }
        if (urlParams.has('platform')) {
            const platform = urlParams.get('platform');
            if (['youtube', 'tiktok', 'instagram'].includes(platform)) {
                filterPlatform.value = platform;
            }
        }

        initFilterDropdowns();
        initPlatformTabs();

        // 最上部へ戻るボタン
        if (backToTopBtn) {
            backToTopBtn.addEventListener('click', () => {
                mainScrollArea.scrollTo({ top: 0, behavior: 'smooth' });
            });
            const updateBackToTop = () => {
                backToTopBtn.classList.toggle('hidden', mainScrollArea.scrollTop < 400);
            };
            mainScrollArea.addEventListener('scroll', updateBackToTop, { passive: true });
            updateBackToTop();
        }

        // 初回ロード（デフォルト小さめのレイアウトを適用）
        updateVideoContainerLayout();
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
            // platform / media_type の変更でも列数を更新する
            updateVideoContainerLayout();
            offset = 0;
            hasMore = true;
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            loadVideos();
        }
        filterPlatform.addEventListener('change', onFilterChange);
        filterCategory.addEventListener('change', onFilterChange);
        filterMember.addEventListener('change', onFilterChange);
        filterGeneration.addEventListener('change', onFilterChange);
        filterSort.addEventListener('change', onFilterChange);

        // 推しクイックフィルタボタン
        const oshiQuickClearBtn = document.getElementById('oshiQuickClearBtn');

        function syncOshiQuickClear() {
            const mid = (filterMember && typeof filterMember.value === 'string') ? filterMember.value : '';
            const hasSelectedOshi = Array.from(document.querySelectorAll('.oshi-quick-filter'))
                .some(b => (b.dataset.memberId ?? '') === mid && mid !== '');
            if (oshiQuickClearBtn) {
                oshiQuickClearBtn.classList.toggle('hidden', !hasSelectedOshi);
            }
        }

        document.querySelectorAll('.oshi-quick-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                const mid = btn.dataset.memberId;
                filterMember.value = mid;
                onFilterChange();
                document.querySelectorAll('.oshi-quick-filter').forEach(b => b.classList.remove('ring-2'));
                btn.classList.add('ring-2');
                syncOshiQuickClear();
            });
        });
        if (oshiQuickClearBtn) {
            oshiQuickClearBtn.addEventListener('click', () => {
                filterMember.value = '';
                document.querySelectorAll('.oshi-quick-filter').forEach(b => b.classList.remove('ring-2'));
                syncOshiQuickClear();
                onFilterChange();
            });
        }

        // メンバーフィルタが外部要因で変わった場合も追従
        filterMember.addEventListener('change', () => {
            const mid = filterMember.value || '';
            document.querySelectorAll('.oshi-quick-filter').forEach(b => {
                b.classList.toggle('ring-2', (b.dataset.memberId ?? '') === mid && mid !== '');
            });
            syncOshiQuickClear();
        });

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
            const p = (filterPlatform && typeof filterPlatform.value === 'string') ? filterPlatform.value : '';
            return currentMediaType === 'short' || p === 'tiktok' || p === 'instagram';
        }

        function updateVideoContainerLayout() {
            if (currentView === 'grid') {
                if (isShortLayout()) {
                    if (currentCardSize === 'small') {
                        // 縦長: 小さめ（PCは6列、スマホは現状維持）
                        videoContainer.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 md:gap-4';
                    } else {
                        // 縦長: 大きめ（PCは4列、スマホは現状維持）
                        videoContainer.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4';
                    }
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
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            if (view === 'grid') {
                btnViewGrid.classList.add('bg-sky-500', 'text-white');
                btnViewGrid.classList.remove('text-slate-500');
                btnViewList.classList.remove('bg-sky-500', 'text-white');
                btnViewList.classList.add('text-slate-500');
            } else {
                btnViewList.classList.add('bg-sky-500', 'text-white');
                btnViewList.classList.remove('text-slate-500');
                btnViewGrid.classList.remove('bg-sky-500', 'text-white');
                btnViewGrid.classList.add('text-slate-500');
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
            videoContainer.innerHTML = '';
            scrollTrigger.innerHTML = '<div class="h-20"></div>';
            if (size === 'normal') {
                btnCardSizeNormal.classList.add('bg-sky-500', 'text-white');
                btnCardSizeNormal.classList.remove('text-slate-500');
                btnCardSizeSmall.classList.remove('bg-sky-500', 'text-white');
                btnCardSizeSmall.classList.add('text-slate-500');
            } else {
                btnCardSizeSmall.classList.add('bg-sky-500', 'text-white');
                btnCardSizeSmall.classList.remove('text-slate-500');
                btnCardSizeNormal.classList.remove('bg-sky-500', 'text-white');
                btnCardSizeNormal.classList.add('text-slate-500');
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
                    limit: 50,
                    platform: filterPlatform.value || '',
                    category: filterCategory.value,
                    sort: filterSort.value,
                    member_id: filterMember.value || '0',
                    generation: filterGeneration.value || '',
                    media_type: currentMediaType,
                });

                const response = await fetch(`/hinata/api/load_more_media.php?${params}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // 常にフラットに追加（カテゴリ別の帯表示は行わない）
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
            return '/assets/images/no-image.svg';
        }

        // ブロック形式のカード生成（サイズは currentCardSize で切替）
        function renderVideoCard(video) {
            const categoryColors = {
                'CM': 'bg-slate-50 text-slate-600 border border-slate-200',
                'Hinareha': 'bg-amber-50 text-amber-700 border border-amber-200',
                'Live': 'bg-purple-50 text-purple-700 border border-purple-200',
                'MV': 'bg-pink-50 text-pink-700 border border-pink-200',
                'SelfIntro': 'bg-cyan-50 text-cyan-700 border border-cyan-200',
                'SoloPV': 'bg-sky-50 text-sky-700 border border-sky-200',
                'Special': 'bg-orange-50 text-orange-700 border border-orange-200',
                'Teaser': 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'Trailer': 'bg-rose-50 text-rose-700 border border-rose-200',
                'Variety': 'bg-yellow-50 text-yellow-700 border border-yellow-200',
            };
            const categoryColor = categoryColors[video.category] || 'bg-sky-50 text-sky-700 border border-sky-200';
            const primaryDate = video.upload_date || video.release_date || '';
            const videoIsShort = video.media_type === 'short' || isShortLayout();
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
            const cat = (video.category || '').trim();
            const catHtml = cat
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${categoryColor}">#${escapeHtml(cat)}</span>`
                : '';

            if (isShortLayout()) {
                return `
                    <div class="video-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                        <div class="video-thumbnail-portrait">
                            <img src="${thumbUrl}" alt="${escapeHtml(video.title)}" onerror="this.onerror=null;this.src='/assets/images/no-image.svg'">
                            <div class="play-icon">
                                <i class="fa-solid fa-play text-white text-xl ml-0.5"></i>
                            </div>
                            ${platformBadgeHtml(video.platform)}
                        </div>
                        <div class="p-2">
                            <h3 class="text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">${escapeHtml(video.title)}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                ${catHtml}
                                ${dateStr ? `<span class="ml-auto text-[10px] text-slate-400">${dateStr}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            const outerClass = isSmall
                ? 'video-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm text-[11px]'
                : 'video-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm';
            const bodyClass = isSmall ? 'pt-2 pb-1 px-2' : 'pt-3 pb-1';
            const titleClass = 'text-sm font-semibold text-slate-800 line-clamp-2 leading-snug';
            const dateClass = isSmall
                ? 'text-[10px] text-slate-400 mt-0.5 text-right'
                : 'text-xs text-slate-400 mt-1 text-right';
            return `
                <div class="${outerClass}" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                    <div class="video-thumbnail ${isSmall ? 'video-thumbnail-sm' : ''}">
                        <img src="${thumbUrl}" alt="${escapeHtml(video.title)}" onerror="this.onerror=null;this.src='/assets/images/no-image.svg'">
                        <div class="play-icon">
                            <i class="fa-solid fa-play text-white text-2xl ml-1"></i>
                        </div>
                        ${platformBadgeHtml(video.platform)}
                    </div>
                    <div class="${bodyClass}">
                        <h3 class="${titleClass}">${escapeHtml(video.title)}</h3>
                        <div class="mt-1 flex items-center gap-2">
                            ${catHtml}
                            ${dateStr ? `<span class="ml-auto ${dateClass.replace('text-right', '')}">${dateStr}</span>` : ''}
                        </div>
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
            const isVideoShort = video.media_type === 'short' || isShortLayout();
            return `
                <div class="video-card bg-white rounded-2xl border border-slate-200 shadow-sm px-3 py-2 flex items-center gap-3 hover:bg-slate-50/70 transition cursor-pointer" data-video="${dataVideo}" onclick="openVideoModal(this, event)">
                    <div class="w-16 shrink-0 ${isVideoShort ? 'aspect-[9/16]' : 'aspect-video'} rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 relative">
                        <img src="${thumbUrl}" alt="" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='/assets/images/no-image.svg'">
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
                youtube:   { icon: 'fa-brands fa-youtube', text: 'YouTube', iconCls: 'text-red-500' },
                instagram: { icon: 'fa-brands fa-instagram', text: 'Instagram', iconCls: 'text-pink-500' },
                tiktok:    { icon: 'fa-brands fa-tiktok', text: 'TikTok', iconCls: 'text-slate-900' },
            };
            const c = cfg[platform];
            if (!c) return '';
            return `
                <span class="absolute top-2 left-2 inline-flex items-center gap-1.5 px-2 py-1 sm:px-2 sm:py-1 rounded-full bg-white/90 backdrop-blur border border-white/60 shadow-sm">
                    <i class="${c.icon} ${c.iconCls} text-[11px]"></i>
                    <span class="hidden sm:inline text-[10px] font-black leading-none ${c.iconCls}">${c.text}</span>
                </span>
            `.trim();
        }

        function platformBadgeInline(platform) {
            if (!platform) return '';
            const cfg = {
                youtube:   { bg: 'bg-red-600', icon: 'fa-brands fa-youtube' },
                instagram: { bg: 'bg-gradient-to-r from-purple-500 to-pink-500', icon: 'fa-brands fa-instagram' },
                tiktok:    { bg: 'bg-slate-800', icon: 'fa-brands fa-tiktok' },
            };
            const c = cfg[platform];
            if (!c) return '';
            return `<span class="inline-flex w-5 h-5 ${c.bg} rounded-full items-center justify-center shadow shrink-0"><i class="${c.icon} text-[10px] text-white"></i></span>`;
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
