<?php
/**
 * 日向坂ネタ帳詳細 View (完全版)
 * 物理パス: haitaka/private/apps/Hinata/Views/index.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ネタ帳 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .color-strip { width: 8px; flex-shrink: 0; }
        .neta-item.done { opacity: 0.5; }
        .neta-item.done .neta-content { text-decoration: line-through; }
        .show-done-false .neta-item.done { display: none !important; }
        
        .neta-card { 
            transition: box-shadow 0.2s, transform 0.2s; 
            box-shadow: 0 2px 8px -2px rgba(0,0,0,0.1), 0 4px 12px -4px rgba(0,0,0,0.08);
        }
        .neta-card:hover, .neta-card:focus-within { 
            box-shadow: 0 8px 24px -6px rgba(0,0,0,0.18), 0 12px 28px -8px rgba(0,0,0,0.12); 
        }
        .neta-card .card-status { opacity: 0; transition: opacity 0.2s; }
        .neta-card:hover .card-status, .neta-card:focus-within .card-status { opacity: 1; }
        .fav-badge { font-size: 10px; font-weight: 700; }
        .neta-cards-columns { column-count: 1; column-gap: 0.75rem; }
        @media (min-width: 640px) { .neta-cards-columns { column-count: 2; } }
        @media (min-width: 1024px) { .neta-cards-columns { column-count: 3; } }
        .neta-cards-columns .neta-card { break-inside: avoid; margin-bottom: 0.75rem; }
        
        /* サイドバー共通スタイル */
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            /* モバイル展開時は強制的に文字を表示 */
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
            #netaFormContainer.form-hidden { display: none; }
        }
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 show-done-false <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <!-- 共通サイドバー -->
    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-xl"></i></button>
                <a href="/hinata/" class="text-slate-400 p-2 shrink-0 transition <?= $isThemeHex ? 'hover:opacity-80' : 'hover:text-' . $themeTailwind . '-500' ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-lightbulb text-sm"></i></div>
                <h1 class="font-bold text-slate-700 text-sm truncate">推し活ネタ帳</h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button id="toggleFormBtn" class="text-xs font-bold px-3 py-1.5 rounded-full <?= $cardIconText ?> <?= $cardIconBg ?> hover:opacity-90"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>+ 追加</button>
            </div>
        </header>

        <div id="scrollContainer" class="flex-1 overflow-y-auto p-4 space-y-4 pb-24">
            <div class="max-w-4xl mx-auto w-full space-y-4">
                <!-- 表示形式・完了表示（ヘッダーから移動） -->
                <div class="flex flex-wrap items-center justify-between gap-3 py-2">
                    <div class="flex bg-slate-100 rounded-full p-1 text-[10px] font-bold">
                        <button id="btnViewCard" class="px-3 py-1 rounded-full bg-white shadow text-slate-700" title="カード表示">カード</button>
                        <button id="btnViewList" class="px-3 py-1 rounded-full text-slate-500" title="一覧表示">一覧</button>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="text-[10px] font-bold text-slate-400">完了表示</span>
                        <input type="checkbox" id="doneToggle" class="w-4 h-4 rounded border-slate-300 <?= $cardIconText ?>"<?= $isThemeHex ? ' style="accent-color: var(--hinata-theme)"' : '' ?>>
                    </label>
                </div>

                <!-- 登録・編集フォーム -->
                <section id="netaFormContainer" class="bg-white border <?= $cardBorder ?> rounded-xl p-5 shadow-sm form-hidden md:block">
                    <form id="netaForm" class="space-y-4">
                        <input type="hidden" name="id" id="neta_id">
                        <div class="flex justify-between items-center">
                            <label class="text-[10px] font-black text-slate-400 tracking-wider">ネタを投稿・編集</label>
                            <button type="button" id="cancelEdit" class="hidden text-[10px] text-red-400 font-bold bg-red-50 px-2 py-0.5 rounded">キャンセル</button>
                        </div>
                        <select name="member_id" id="form_member_id" required class="w-full h-11 border border-slate-100 rounded-lg px-4 text-sm bg-slate-50 outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-100' ?> transition-all">
                            <option value="">メンバーを選択</option>
                            <?php if(!empty($members)): foreach ($members as $m): ?>
                                <?php $favLevel = (int)($m['favorite_level'] ?? 0); ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= $favLevel >= 2 ? '❤️ ' : ($favLevel === 1 ? '⭐ ' : '') ?>
                                    <?= htmlspecialchars($m['name']) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <textarea name="content" id="form_content" required placeholder="何を話す？" class="w-full border border-slate-100 rounded-lg p-4 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-sky-100 min-h-[80px] transition-all"></textarea>
                        <button type="submit" id="submitBtn" class="w-full bg-sky-500 text-white h-12 rounded-lg font-bold shadow-lg shadow-sky-200 active:scale-95 transition-transform">
                            ネタを追加
                        </button>
                    </form>
                </section>

                <!-- カード表示エリア（デフォルト） -->
                <div id="cardView" class="space-y-8">
                    <?php if(!empty($groupedNeta)): foreach ($groupedNeta as $mid => $group): 
                        $favLevel = (int)($group['favorite_level'] ?? 0);
                        $favLabel = $favLevel >= 2 ? '推し' : ($favLevel === 1 ? '気になる' : '');
                        $color1 = htmlspecialchars($group['color1'] ?? '#7cc7e8');
                        $color2 = htmlspecialchars($group['color2'] ?? $color1);
                    ?>
                        <div>
                            <!-- メンバー帯（名前の下にバー、その下にカード） -->
                            <div class="mb-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <h2 class="text-base font-black text-slate-700"><?= htmlspecialchars($group['member_name']) ?></h2>
                                    <?php if ($favLabel): ?>
                                        <span class="fav-badge px-2 py-0.5 rounded-full bg-sky-100 text-sky-600 text-[10px]"><?= $favLabel ?></span>
                                    <?php endif; ?>
                                    <span class="text-[10px] font-bold text-slate-400"><?= count($group['items']) ?> 件</span>
                                </div>
                                <div class="h-1.5 rounded-full w-full" style="background: linear-gradient(to right, <?= $color1 ?>, <?= $color2 ?>);"></div>
                            </div>
                            <!-- カード群（カラムレイアウトで詰めて表示） -->
                            <div class="neta-cards-columns">
                                <?php foreach ($group['items'] as $item): ?>
                                    <div class="neta-card neta-item relative bg-white border-l-4 rounded-xl p-4 cursor-pointer outline-none focus:outline-none focus:ring-2 focus:ring-sky-200 w-full <?= $item['status'] === 'done' ? 'done' : '' ?>" style="border-left-color: <?= $color1 ?>;" tabindex="0" onclick="editNeta(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)">
                                        <div class="card-status absolute top-2 right-2 flex flex-col items-center gap-0.5" onclick="event.stopPropagation()">
                                            <label class="cursor-pointer">
                                                <input type="checkbox" <?= $item['status'] === 'done' ? 'checked' : '' ?> 
                                                       onchange="toggleStatus(<?= $item['id'] ?>, this.checked)"
                                                       class="w-4 h-4 rounded border-slate-300 text-sky-500">
                                            </label>
                                            <button onclick="event.stopPropagation(); deleteNeta(<?= $item['id'] ?>)" class="text-slate-300 hover:text-red-400 p-1" title="削除">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </div>
                                        <div class="text-sm text-slate-700 leading-relaxed neta-content pr-6">
                                            <?= nl2br(htmlspecialchars($item['content'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-center text-slate-400 text-xs py-10 tracking-wider">データがありません</p>
                    <?php endif; ?>
                </div>

                <!-- 一覧表示エリア（非表示がデフォルト） -->
                <div id="listView" class="space-y-3 hidden">
                    <?php if(!empty($groupedNeta)): foreach ($groupedNeta as $mid => $group): 
                        $favLevel = (int)($group['favorite_level'] ?? 0);
                        $favLabel = $favLevel >= 2 ? '推し' : ($favLevel === 1 ? '気になる' : '');
                    ?>
                        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm flex">
                            <div class="color-strip" style="background: linear-gradient(to bottom, <?= $group['color1'] ?>, <?= $group['color2'] ?>);"></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between px-4 py-4 cursor-pointer hover:bg-slate-50 transition" onclick="toggleAccordion(<?= $mid ?>)">
                                    <div class="flex items-center gap-3">
                                        <span class="font-bold text-slate-700"><?= htmlspecialchars($group['member_name']) ?></span>
                                        <?php if ($favLabel): ?><span class="fav-badge text-sky-600"><?= $favLabel ?></span><?php endif; ?>
                                        <span class="text-[10px] text-slate-400 font-bold px-2 py-0.5 bg-slate-100 rounded-full"><?= count($group['items']) ?> 件</span>
                                    </div>
                                    <i id="icon-<?= $mid ?>" class="fa-solid fa-chevron-down text-slate-300 text-xs transition-transform duration-300"></i>
                                </div>
                                <div id="list-<?= $mid ?>" class="divide-y divide-slate-50 border-t border-slate-50 hidden accordion-content">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <div class="neta-item p-4 flex items-start gap-3 group <?= $item['status'] === 'done' ? 'done' : '' ?>" onclick="editNeta(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)">
                                            <div onclick="event.stopPropagation()">
                                                <input type="checkbox" <?= $item['status'] === 'done' ? 'checked' : '' ?> 
                                                       onchange="toggleStatus(<?= $item['id'] ?>, this.checked)"
                                                       class="w-5 h-5 rounded border-slate-300 text-sky-500 mt-0.5">
                                            </div>
                                            <div class="flex-1 text-sm text-slate-600 leading-relaxed neta-content cursor-pointer">
                                                <?= nl2br(htmlspecialchars($item['content'])) ?>
                                            </div>
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.stopPropagation()">
                                                <button onclick="deleteNeta(<?= $item['id'] ?>)" class="text-slate-200 hover:text-red-400 p-1">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-center text-slate-400 text-xs py-10 tracking-wider">データがありません</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const STORAGE_KEY = 'hinata_opened_accordions';

        window.onload = () => {
            restoreAccordions(); // アコーディオンの状態復元
            restoreScroll();     // スクロール位置の復元
        };

        // 表示形式切替（デフォルト: カード）
        const cardView = document.getElementById('cardView');
        const listView = document.getElementById('listView');
        const btnViewCard = document.getElementById('btnViewCard');
        const btnViewList = document.getElementById('btnViewList');
        let viewMode = localStorage.getItem('hinata_neta_view') || 'card';
        
        function setViewMode(mode) {
            viewMode = mode;
            localStorage.setItem('hinata_neta_view', mode);
            if (mode === 'card') {
                cardView.classList.remove('hidden');
                listView.classList.add('hidden');
                btnViewCard.classList.add('bg-white', 'shadow', 'text-slate-700');
                btnViewCard.classList.remove('text-slate-500');
                btnViewList.classList.remove('bg-white', 'shadow', 'text-slate-700');
                btnViewList.classList.add('text-slate-500');
            } else {
                cardView.classList.add('hidden');
                listView.classList.remove('hidden');
                btnViewList.classList.add('bg-white', 'shadow', 'text-slate-700');
                btnViewList.classList.remove('text-slate-500');
                btnViewCard.classList.remove('bg-white', 'shadow', 'text-slate-700');
                btnViewCard.classList.add('text-slate-500');
            }
        }
        setViewMode(viewMode);
        btnViewCard.onclick = () => setViewMode('card');
        btnViewList.onclick = () => setViewMode('list');

        // 完了表示トグル
        document.getElementById('doneToggle').onchange = function() {
            document.body.classList.toggle('show-done-false', !this.checked);
        };

        // スマホ用フォームトグル
        document.getElementById('toggleFormBtn').onclick = function() {
            document.getElementById('netaFormContainer').classList.toggle('form-hidden');
        };

        /**
         * アコーディオンの開閉と状態保存
         */
        function toggleAccordion(id) {
            const list = document.getElementById('list-' + id);
            const icon = document.getElementById('icon-' + id);
            if (!list) return;

            const isOpening = list.classList.contains('hidden');
            list.classList.toggle('hidden');
            if (icon) icon.style.transform = isOpening ? 'rotate(180deg)' : 'rotate(0deg)';

            // localStorageに開いているIDを保存
            let opened = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            if (isOpening) {
                if (!opened.includes(id)) opened.push(id);
            } else {
                opened = opened.filter(openedId => openedId !== id);
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(opened));
        }

        /**
         * 状態の復元
         */
        function restoreAccordions() {
            const opened = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            opened.forEach(id => {
                const list = document.getElementById('list-' + id);
                const icon = document.getElementById('icon-' + id);
                if (list) {
                    list.classList.remove('hidden');
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            });
        }

        /**
         * スクロール位置の保存（リロード直前）
         */
        function saveScroll() {
            const container = document.getElementById('scrollContainer');
            if (container) {
                sessionStorage.setItem('hinata_scroll_pos', container.scrollTop);
            }
        }

        /**
         * スクロール位置の復元
         */
        function restoreScroll() {
            const pos = sessionStorage.getItem('hinata_scroll_pos');
            const container = document.getElementById('scrollContainer');
            if (pos && container) {
                container.scrollTop = pos;
            }
        }

        /**
         * 編集モード切り替え
         */
        function editNeta(item) {
            document.getElementById('netaFormContainer').classList.remove('form-hidden');
            document.getElementById('neta_id').value = item.id;
            document.getElementById('form_member_id').value = item.member_id;
            document.getElementById('form_content').value = item.content;
            document.getElementById('submitBtn').innerText = '変更を保存';
            document.getElementById('cancelEdit').classList.remove('hidden');
            
            // フォームへスクロール
            const container = document.getElementById('scrollContainer');
            if (container) container.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /**
         * フォームリセット
         */
        document.getElementById('cancelEdit').onclick = function() {
            document.getElementById('netaForm').reset();
            document.getElementById('neta_id').value = '';
            document.getElementById('submitBtn').innerText = 'ネタを追加';
            this.classList.add('hidden');
        };

        /**
         * 保存処理
         */
        document.getElementById('netaForm').onsubmit = async (e) => {
            e.preventDefault();
            saveScroll(); 
            const res = await App.post('api/save_neta.php', Object.fromEntries(new FormData(e.target)));
            if (res.status === 'success') {
                location.reload();
            } else {
                alert('エラー: ' + res.message);
            }
        };

        /**
         * 完了ステータスのトグル
         */
        async function toggleStatus(id, checked) {
            saveScroll();
            const res = await App.post('api/update_neta_status.php', {
                id: id,
                status: checked ? 'done' : 'stock'
            });
            if (res.status === 'success') {
                location.reload();
            } else {
                alert('更新に失敗しました');
            }
        }

        /**
         * ネタの削除
         */
        async function deleteNeta(id) {
            if (!confirm('このネタを削除してもよろしいですか？')) return;
            saveScroll();
            const res = await App.post('api/delete_neta.php', { id });
            if (res.status === 'success') {
                location.reload();
            } else {
                alert('削除に失敗しました');
            }
        }
    </script>
</body>
</html>