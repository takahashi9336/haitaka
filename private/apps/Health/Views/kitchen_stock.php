<?php
/**
 * Health: 食材ストック View
 */
$appKey = 'health_kitchen_stock';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>食材ストック - Health</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        input[type="text"]:focus, input[type="date"]:focus {
            outline: none;
        }
    </style>
    <?php if ($isThemeHex): ?>
    <style>
        input[type="checkbox"] { accent-color: var(--health-theme); }
    </style>
    <?php endif; ?>
    <style>:root { --health-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/health/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-basket-shopping text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">食材ストック</h1>
            </div>
            <button id="copyMdBtn" class="<?= $btnBgClass ?> text-white px-4 py-2 rounded-lg font-bold text-xs shadow-md transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                <i class="fa-solid fa-copy mr-2"></i>Markdown形式でコピー
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-10">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="flex flex-col">
                            <label class="text-xs font-black text-slate-500 mb-1 ml-1">食材名</label>
                            <input type="text" id="itemName" placeholder="例: 鶏むね肉" class="border p-2 rounded-md border-slate-300 h-[42px]">
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-black text-slate-500 mb-1 ml-1">数量</label>
                            <input type="text" id="itemQty" placeholder="例: 2枚" class="border p-2 rounded-md border-slate-300 h-[42px]">
                        </div>
                        <div class="flex gap-4">
                            <div class="flex flex-col flex-1">
                                <label class="text-xs font-black text-slate-500 mb-1 ml-1">購入日</label>
                                <input type="date" id="itemDate" class="border p-2 rounded-md border-slate-300 text-sm h-[42px]">
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                <input type="checkbox" id="itemFrozen" class="h-4 w-4 border-slate-300 rounded"<?= $isThemeHex ? ' style="accent-color: var(--health-theme)"' : '' ?>>
                                <label for="itemFrozen" class="text-xs font-black text-slate-500">冷凍</label>
                            </div>
                        </div>
                        <div class="flex flex-col justify-end">
                            <button id="addBtn" class="<?= $btnBgClass ?> text-white p-2 rounded-md transition h-[42px] font-bold"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                                追加 (Ctrl+Enter)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="p-4 text-sm font-black text-slate-600">食材 / 購入日</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-28">数量</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-16 text-center">冷凍</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-16 text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody id="stockList" class="divide-y divide-slate-200"></tbody>
                    </table>
                    <div id="emptyMsg" class="p-8 text-center text-slate-400 hidden">在庫がありません</div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        let inventory = [];

        function todayStr() {
            return new Date().toISOString().split('T')[0];
        }

        function handleKeyDown(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                addItem();
            }
        }

        async function loadItems() {
            const res = await App.post('/health/api/list.php', {});
            if (res && res.status === 'success') {
                inventory = res.items || [];
                render();
            } else {
                App.toast((res && res.message) ? res.message : '読み込みに失敗しました');
            }
        }

        async function addItem() {
            const name = document.getElementById('itemName').value.trim();
            const qty = document.getElementById('itemQty').value.trim();
            const date = document.getElementById('itemDate').value;
            const isFrozen = document.getElementById('itemFrozen').checked;
            if (!name) return;

            const res = await App.post('/health/api/create.php', {
                name,
                qty: qty || null,
                purchased_date: date || null,
                is_frozen: isFrozen ? 1 : 0
            });
            if (res && res.status === 'success') {
                document.getElementById('itemName').value = '';
                document.getElementById('itemQty').value = '';
                document.getElementById('itemName').focus();
                await loadItems();
            } else {
                App.toast((res && res.message) ? res.message : '追加に失敗しました');
            }
        }

        async function updateItem(id, patch) {
            const res = await App.post('/health/api/update.php', Object.assign({ id }, patch));
            if (!(res && res.status === 'success')) {
                App.toast((res && res.message) ? res.message : '更新に失敗しました');
            }
        }

        async function deleteItem(id) {
            const ok = confirm('削除してよろしいですか？');
            if (!ok) return;
            const res = await App.post('/health/api/delete.php', { id });
            if (res && res.status === 'success') {
                await loadItems();
            } else {
                App.toast((res && res.message) ? res.message : '削除に失敗しました');
            }
        }

        function render() {
            const list = document.getElementById('stockList');
            const emptyMsg = document.getElementById('emptyMsg');

            if (!inventory || inventory.length === 0) {
                list.innerHTML = '';
                emptyMsg.classList.remove('hidden');
                return;
            }
            emptyMsg.classList.add('hidden');

            list.innerHTML = inventory.map(item => {
                const name = (item.name || '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;");
                const qty = (item.qty || '-').toString().replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;");
                const date = item.purchased_date ? item.purchased_date : '未記入';
                const frozen = !!(item.is_frozen && parseInt(item.is_frozen, 10) === 1);
                return `
                    <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-none">
                        <td class="p-4">
                            <div class="font-black text-slate-700">${name}</div>
                            <div class="text-xs text-slate-400">購入日: ${date}</div>
                        </td>
                        <td class="p-4 w-28">
                            <input type="text" value="${qty}" data-id="${item.id}"
                                class="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:border-slate-300">
                        </td>
                        <td class="p-4 w-16 text-center">
                            <input type="checkbox" ${frozen ? 'checked' : ''} data-id="${item.id}"
                                class="h-4 w-4 border-slate-300 rounded">
                        </td>
                        <td class="p-4 w-16 text-center">
                            <button data-del-id="${item.id}" class="text-slate-300 hover:text-red-500 transition-colors">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            list.querySelectorAll('input[type="text"][data-id]').forEach(el => {
                el.addEventListener('change', async () => {
                    const id = parseInt(el.dataset.id, 10);
                    await updateItem(id, { qty: el.value.trim() || null });
                    await loadItems();
                });
            });
            list.querySelectorAll('input[type="checkbox"][data-id]').forEach(el => {
                el.addEventListener('change', async () => {
                    const id = parseInt(el.dataset.id, 10);
                    await updateItem(id, { is_frozen: el.checked ? 1 : 0 });
                    await loadItems();
                });
            });
            list.querySelectorAll('button[data-del-id]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = parseInt(btn.dataset.delId, 10);
                    await deleteItem(id);
                });
            });
        }

        function copyAsMarkdown() {
            if (!inventory || inventory.length === 0) {
                App.toast('在庫が空です。');
                return;
            }
            const now = new Date();
            const dateStr = `${now.getFullYear()}/${now.getMonth()+1}/${now.getDate()}`;

            let md = `## キッチン在庫リスト (${dateStr})\n\n`;
            md += `以下の在庫リストを元に、消費期限が近そうなものや効率的なレシピ、買い足すべきものを提案してください：\n\n`;
            md += `| 食材名 | 数量 | 購入日 | 冷凍 |\n`;
            md += `| :--- | :--- | :--- | :--- |\n`;

            inventory.forEach(item => {
                const frozen = (item.is_frozen && parseInt(item.is_frozen, 10) === 1) ? 'はい' : 'いいえ';
                md += `| ${item.name} | ${item.qty || '-'} | ${item.purchased_date || '未記入'} | ${frozen} |\n`;
            });

            navigator.clipboard.writeText(md).then(() => {
                App.toast('クリップボードにコピーしました');
            }).catch(() => {
                App.toast('コピーに失敗しました');
            });
        }

        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        document.getElementById('addBtn').onclick = addItem;
        document.getElementById('copyMdBtn').onclick = copyAsMarkdown;

        ['itemName','itemQty','itemDate'].forEach(id => {
            document.getElementById(id).addEventListener('keydown', handleKeyDown);
        });

        window.addEventListener('DOMContentLoaded', async () => {
            document.getElementById('itemDate').value = todayStr();
            document.getElementById('itemName').focus();
            await loadItems();
        });
    </script>
</body>
</html>

