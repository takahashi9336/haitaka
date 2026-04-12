<?php
/**
 * Health: トレーニングメニュー View
 */
$appKey = 'health_training_menu';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニングメニュー - Health</title>
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
        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
        }
    </style>
    <?php if ($isThemeHex): ?>
    <style>
        input[type="number"] { accent-color: var(--health-theme); }
    </style>
    <?php endif; ?>
    <style>:root { --health-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/health/" class="text-slate-400 hover:text-slate-600 transition shrink-0"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-dumbbell text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">トレーニングメニュー</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-10">
            <div class="max-w-4xl mx-auto space-y-8">
                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h2 class="text-sm font-black text-slate-600 mb-4">メニューを追加</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="flex flex-col md:col-span-2">
                            <label class="text-xs font-black text-slate-500 mb-1 ml-1">メニュー名</label>
                            <input type="text" id="menuName" placeholder="例: スクワット" class="border p-2 rounded-md border-slate-300 h-[42px]">
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-black text-slate-500 mb-1 ml-1">回数</label>
                            <input type="number" id="menuReps" min="1" value="10" class="border p-2 rounded-md border-slate-300 h-[42px]">
                        </div>
                        <div class="flex flex-col justify-end">
                            <button id="addBtn" class="<?= $btnBgClass ?> text-white p-2 rounded-md transition h-[42px] font-bold"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                                追加 (Ctrl+Enter)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <h2 class="text-sm font-black text-slate-600 p-4 pb-0">登録済みメニュー</h2>
                    <table class="w-full text-left border-collapse mt-2">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="p-4 text-sm font-black text-slate-600">メニュー名</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-32">回数</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-16 text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody id="menuList" class="divide-y divide-slate-200"></tbody>
                    </table>
                    <div id="emptyMsg" class="p-8 text-center text-slate-400 hidden">メニューがありません</div>
                </div>

                <div>
                    <h2 class="text-sm font-black text-slate-600 mb-3">参照動画</h2>
                    <div class="rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden bg-black">
                        <div class="aspect-video w-full max-w-3xl mx-auto">
                            <iframe
                                class="w-full h-full"
                                src="https://www.youtube.com/embed/ieQLwxA2qGY"
                                title="YouTube 動画"
                                loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        let items = [];

        function esc(s) {
            return (s || '').toString()
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        }

        function handleKeyDown(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                addItem();
            }
        }

        async function loadItems() {
            const res = await App.post('/health/api/training_list.php', {});
            if (res && res.status === 'success') {
                items = res.items || [];
                render();
            } else {
                App.toast((res && res.message) ? res.message : '読み込みに失敗しました');
            }
        }

        async function addItem() {
            const name = document.getElementById('menuName').value.trim();
            const repsRaw = document.getElementById('menuReps').value;
            const reps = repsRaw === '' ? 1 : parseInt(repsRaw, 10);
            if (!name) return;
            if (Number.isNaN(reps) || reps < 1) {
                App.toast('回数は1以上を指定してください');
                return;
            }

            const res = await App.post('/health/api/training_create.php', { name, reps });
            if (res && res.status === 'success') {
                document.getElementById('menuName').value = '';
                document.getElementById('menuReps').value = '10';
                document.getElementById('menuName').focus();
                await loadItems();
            } else {
                App.toast((res && res.message) ? res.message : '追加に失敗しました');
            }
        }

        async function updateItem(id, patch) {
            const res = await App.post('/health/api/training_update.php', Object.assign({ id }, patch));
            if (!(res && res.status === 'success')) {
                App.toast((res && res.message) ? res.message : '更新に失敗しました');
            }
        }

        async function deleteItem(id) {
            const ok = confirm('削除してよろしいですか？');
            if (!ok) return;
            const res = await App.post('/health/api/training_delete.php', { id });
            if (res && res.status === 'success') {
                await loadItems();
            } else {
                App.toast((res && res.message) ? res.message : '削除に失敗しました');
            }
        }

        function render() {
            const list = document.getElementById('menuList');
            const emptyMsg = document.getElementById('emptyMsg');

            if (!items || items.length === 0) {
                list.innerHTML = '';
                emptyMsg.classList.remove('hidden');
                return;
            }
            emptyMsg.classList.add('hidden');

            list.innerHTML = items.map(item => {
                const name = esc(item.name);
                const reps = parseInt(item.reps, 10) || 1;
                return `
                    <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-none">
                        <td class="p-4">
                            <input type="text" value="${name}" data-id="${item.id}" data-field="name"
                                class="w-full border border-slate-200 rounded px-2 py-1 text-sm font-bold text-slate-700 focus:border-slate-300">
                        </td>
                        <td class="p-4 w-32">
                            <input type="number" min="1" value="${reps}" data-id="${item.id}" data-field="reps"
                                class="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:border-slate-300">
                        </td>
                        <td class="p-4 w-16 text-center">
                            <button type="button" data-del-id="${item.id}" class="text-slate-300 hover:text-red-500 transition-colors">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            list.querySelectorAll('input[data-field="name"]').forEach(el => {
                el.addEventListener('change', async () => {
                    const id = parseInt(el.dataset.id, 10);
                    const v = el.value.trim();
                    if (!v) {
                        await loadItems();
                        return;
                    }
                    await updateItem(id, { name: v });
                    await loadItems();
                });
            });
            list.querySelectorAll('input[data-field="reps"]').forEach(el => {
                el.addEventListener('change', async () => {
                    const id = parseInt(el.dataset.id, 10);
                    let v = parseInt(el.value, 10);
                    if (Number.isNaN(v) || v < 1) {
                        await loadItems();
                        return;
                    }
                    await updateItem(id, { reps: v });
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

        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        document.getElementById('addBtn').onclick = addItem;
        ['menuName', 'menuReps'].forEach(id => {
            document.getElementById(id).addEventListener('keydown', handleKeyDown);
        });

        window.addEventListener('DOMContentLoaded', async () => {
            document.getElementById('menuName').focus();
            await loadItems();
        });
    </script>
</body>
</html>
