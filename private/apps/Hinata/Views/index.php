<?php
/**
 * 日向坂ネタ帳詳細 View (完全版)
 * 物理パス: haitaka/private/apps/Hinata/Views/index.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ネタ帳 - Hinata Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .color-strip { width: 8px; flex-shrink: 0; }
        .neta-item.done { opacity: 0.5; }
        .neta-item.done .neta-content { text-decoration: line-through; }
        .show-done-false .neta-item.done { display: none !important; }
        
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
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 show-done-false">

    <!-- 共通サイドバー -->
    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative bg-[#f0f9ff]">
        <header class="h-14 bg-white border-b border-sky-100 flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-xl"></i></button>
                <a href="/hinata/" class="text-slate-400 hover:text-sky-500 p-2 transition"><i class="fa-solid fa-chevron-left"></i></a>
                <h1 class="font-bold text-slate-700 text-sm">推し活ネタ帳</h1>
            </div>
            <div class="flex items-center gap-3">
                <button id="toggleFormBtn" class="md:hidden text-sky-500 text-xs font-bold bg-sky-50 px-3 py-1.5 rounded-full">+ 追加</button>
                <label class="flex items-center gap-1 cursor-pointer">
                    <span class="text-[10px] font-bold text-slate-400">完了表示</span>
                    <input type="checkbox" id="doneToggle" class="w-4 h-4 rounded text-sky-500 border-slate-300">
                </label>
            </div>
        </header>

        <div id="scrollContainer" class="flex-1 overflow-y-auto p-4 space-y-4 pb-24">
            <div class="max-w-2xl mx-auto w-full space-y-4">
                
                <!-- 登録・編集フォーム -->
                <section id="netaFormContainer" class="bg-white border border-sky-100 rounded-xl p-5 shadow-sm form-hidden md:block">
                    <form id="netaForm" class="space-y-4">
                        <input type="hidden" name="id" id="neta_id">
                        <div class="flex justify-between items-center">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Post / Edit Talk</label>
                            <button type="button" id="cancelEdit" class="hidden text-[10px] text-red-400 font-bold bg-red-50 px-2 py-0.5 rounded">キャンセル</button>
                        </div>
                        <select name="member_id" id="form_member_id" required class="w-full h-11 border border-slate-100 rounded-lg px-4 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-sky-100 transition-all">
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

                <!-- 一覧エリア -->
                <div class="space-y-3">
                    <?php if(!empty($groupedNeta)): foreach ($groupedNeta as $mid => $group): ?>
                        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm flex">
                            <!-- グラデーション帯 -->
                            <div class="color-strip" style="background: linear-gradient(to bottom, <?= $group['color1'] ?>, <?= $group['color2'] ?>);"></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between px-4 py-4 cursor-pointer hover:bg-slate-50 transition" onclick="toggleAccordion(<?= $mid ?>)">
                                    <div class="flex items-center gap-3">
                                        <span class="font-bold text-slate-700"><?= htmlspecialchars($group['member_name']) ?></span>
                                        <span class="text-[10px] text-slate-400 font-bold px-2 py-0.5 bg-slate-100 rounded-full"><?= count($group['items']) ?> items</span>
                                    </div>
                                    <i id="icon-<?= $mid ?>" class="fa-solid fa-chevron-down text-slate-300 text-xs transition-transform duration-300"></i>
                                </div>
                                <div id="list-<?= $mid ?>" class="divide-y divide-slate-50 border-t border-slate-50 hidden accordion-content">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <div class="neta-item p-4 flex items-start gap-3 group <?= $item['status'] === 'done' ? 'done' : '' ?>" onclick="editNeta(<?= htmlspecialchars(json_encode($item)) ?>)">
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
                        <p class="text-center text-slate-400 text-xs py-10 uppercase tracking-widest">No Data Stocked</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        const STORAGE_KEY = 'hinata_opened_accordions';

        window.onload = () => {
            restoreAccordions(); // アコーディオンの状態復元
            restoreScroll();     // スクロール位置の復元
        };

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