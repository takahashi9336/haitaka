<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画一括編集 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --mv-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .mv-theme-btn { background-color: var(--mv-theme); }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        .mv-theme-text { color: var(--mv-theme); }
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
        .edit-row { transition: background-color 0.12s; }
        .edit-row:hover { background-color: #f8fafc; }
        .edit-row.deleted { opacity: 0.35; text-decoration: line-through; }
        .edit-row.changed { background-color: #fffbeb; }
        .edit-input { font-size: 12px; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 6px; }
        .edit-input:focus { outline: none; border-color: var(--mv-theme); box-shadow: 0 0 0 2px rgba(var(--mv-theme-rgb, 99,102,241), 0.15); }
        .tab-btn { position: relative; padding-bottom: 0.75rem; }
        .tab-btn.active::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 3px; background-color: var(--mv-theme); border-radius: 3px 3px 0 0;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/movie/list.php?tab=<?= htmlspecialchars($tab) ?>" class="flex items-center gap-2 text-slate-500 hover:text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span class="text-sm font-bold">映画リスト</span>
                </a>
                <span class="text-slate-300">|</span>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white shadow <?= $headerIconBg ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                    </div>
                    <h1 class="font-black text-slate-700 text-lg tracking-tighter">一括編集</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span id="changeCount" class="text-xs text-slate-400 hidden"><span id="changeNum">0</span>件の変更</span>
                <button onclick="BulkEdit.save()" id="saveBtn" class="px-5 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition disabled:opacity-50" disabled>
                    <i class="fa-solid fa-check mr-1.5"></i>保存
                </button>
            </div>
        </header>

        <!-- タブバー -->
        <div class="bg-white/90 backdrop-blur-sm border-b border-slate-200 px-6 md:px-12 py-2 shrink-0">
            <div class="max-w-none mx-auto flex items-center gap-6">
                <button class="tab-btn text-sm font-bold py-2 <?= $tab === 'watchlist' ? 'active mv-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="location.href='?tab=watchlist'">
                    <i class="fa-solid fa-bookmark mr-1.5"></i>見たい
                    <span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= $watchlistCount ?></span>
                </button>
                <button class="tab-btn text-sm font-bold py-2 <?= $tab === 'watched' ? 'active mv-theme-text' : 'text-slate-400 hover:text-slate-600' ?>"
                        onclick="location.href='?tab=watched'">
                    <i class="fa-solid fa-check-circle mr-1.5"></i>見た
                    <span class="ml-1 text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full"><?= $watchedCount ?></span>
                </button>
            </div>
        </div>

        <!-- テーブル -->
        <div class="flex-1 overflow-auto">
            <?php if (empty($movies)): ?>
            <div class="text-center py-20">
                <i class="fa-solid fa-film text-4xl text-slate-300 mb-4"></i>
                <p class="text-sm text-slate-400">映画がありません</p>
            </div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-slate-50 z-[2] border-b border-slate-200">
                    <tr class="text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-3 w-8">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300 accent-[var(--mv-theme)]"
                                   onchange="BulkEdit.toggleSelectAll(this.checked)" checked>
                        </th>
                        <th class="py-3 px-2 w-14"></th>
                        <th class="py-3 px-3">タイトル</th>
                        <th class="py-3 px-3 w-28">ステータス</th>
                        <th class="py-3 px-3 w-20">評価</th>
                        <th class="py-3 px-3 w-36">視聴日</th>
                        <th class="py-3 px-3 w-48 hidden lg:table-cell">メモ</th>
                        <th class="py-3 px-3 w-16">削除</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($movies as $mv): ?>
                    <tr class="edit-row" data-id="<?= $mv['id'] ?>"
                        data-orig='<?= htmlspecialchars(json_encode([
                            'status' => $mv['status'],
                            'rating' => $mv['rating'],
                            'watched_date' => $mv['watched_date'],
                            'memo' => $mv['memo'] ?? '',
                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>
                        <td class="py-2 px-3">
                            <input type="checkbox" class="row-select rounded border-slate-300 accent-[var(--mv-theme)]" checked>
                        </td>
                        <td class="py-2 px-2">
                            <?php if (!empty($mv['poster_path'])): ?>
                            <img src="https://image.tmdb.org/t/p/w92<?= htmlspecialchars($mv['poster_path']) ?>"
                                 class="w-9 h-[54px] object-cover rounded-md" loading="lazy">
                            <?php else: ?>
                            <div class="w-9 h-[54px] poster-placeholder rounded-md flex items-center justify-center">
                                <i class="fa-solid fa-film text-slate-400 text-[10px]"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <a href="/movie/detail.php?id=<?= $mv['id'] ?>" class="text-sm font-bold text-slate-800 hover:text-[var(--mv-theme)] transition line-clamp-1" target="_blank">
                                <?= htmlspecialchars($mv['title']) ?>
                            </a>
                            <?php if (!empty($mv['release_date'])): ?>
                            <p class="text-[10px] text-slate-400"><?= date('Y年', strtotime($mv['release_date'])) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <select class="edit-input w-full edit-status" onchange="BulkEdit.markChanged(this)">
                                <option value="watchlist" <?= $mv['status'] === 'watchlist' ? 'selected' : '' ?>>見たい</option>
                                <option value="watched" <?= $mv['status'] === 'watched' ? 'selected' : '' ?>>見た</option>
                            </select>
                        </td>
                        <td class="py-2 px-3">
                            <select class="edit-input w-full edit-rating" onchange="BulkEdit.markChanged(this)">
                                <option value="">-</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= (int)$mv['rating'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                        <td class="py-2 px-3">
                            <input type="date" class="edit-input w-full edit-date"
                                   value="<?= htmlspecialchars($mv['watched_date'] ?? '') ?>"
                                   onchange="BulkEdit.markChanged(this)">
                        </td>
                        <td class="py-2 px-3 hidden lg:table-cell">
                            <input type="text" class="edit-input w-full edit-memo" placeholder="メモ..."
                                   value="<?= htmlspecialchars($mv['memo'] ?? '') ?>"
                                   onchange="BulkEdit.markChanged(this)">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <button onclick="BulkEdit.toggleDelete(this)" class="text-slate-300 hover:text-red-500 transition text-sm delete-btn" title="削除">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="px-6"><?php require __DIR__ . '/_tmdb_attribution.php'; ?></div>
            <?php endif; ?>
        </div>

        <!-- フッター -->
        <?php if (!empty($movies)): ?>
        <div class="bg-white border-t border-slate-200 px-6 py-3 flex items-center justify-between shrink-0">
            <span class="text-xs text-slate-400"><?= count($movies) ?>件</span>
            <button onclick="BulkEdit.save()" class="px-5 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition disabled:opacity-50" id="saveBtn2" disabled>
                <i class="fa-solid fa-check mr-1.5"></i>変更を保存
            </button>
        </div>
        <?php endif; ?>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const currentTab = '<?= htmlspecialchars($tab) ?>';

        const BulkEdit = {
            deletedIds: new Set(),

            markChanged(el) {
                const row = el.closest('tr.edit-row');
                const orig = JSON.parse(row.dataset.orig);
                const current = this.getRowData(row);
                const changed = orig.status !== current.status
                    || String(orig.rating ?? '') !== String(current.rating)
                    || (orig.watched_date ?? '') !== (current.watched_date ?? '')
                    || (orig.memo ?? '') !== (current.memo ?? '');
                row.classList.toggle('changed', changed);
                this.updateSaveBtn();
            },

            getRowData(row) {
                return {
                    status: row.querySelector('.edit-status').value,
                    rating: row.querySelector('.edit-rating').value,
                    watched_date: row.querySelector('.edit-date').value,
                    memo: row.querySelector('.edit-memo')?.value ?? '',
                };
            },

            toggleDelete(btn) {
                const row = btn.closest('tr.edit-row');
                const id = parseInt(row.dataset.id);
                if (this.deletedIds.has(id)) {
                    this.deletedIds.delete(id);
                    row.classList.remove('deleted');
                    btn.classList.remove('text-red-500');
                    btn.classList.add('text-slate-300');
                    row.querySelectorAll('.edit-input, .row-select').forEach(el => el.disabled = false);
                } else {
                    this.deletedIds.add(id);
                    row.classList.add('deleted');
                    btn.classList.add('text-red-500');
                    btn.classList.remove('text-slate-300');
                    row.querySelectorAll('.edit-input, .row-select').forEach(el => el.disabled = true);
                }
                this.updateSaveBtn();
            },

            toggleSelectAll(checked) {
                document.querySelectorAll('.row-select').forEach(cb => {
                    if (!cb.disabled) cb.checked = checked;
                });
            },

            updateSaveBtn() {
                const changedRows = document.querySelectorAll('tr.edit-row.changed').length;
                const deleteCount = this.deletedIds.size;
                const total = changedRows + deleteCount;
                const enabled = total > 0;
                document.getElementById('saveBtn').disabled = !enabled;
                document.getElementById('saveBtn2').disabled = !enabled;
                const counter = document.getElementById('changeCount');
                const num = document.getElementById('changeNum');
                if (total > 0) {
                    counter.classList.remove('hidden');
                    num.textContent = total;
                } else {
                    counter.classList.add('hidden');
                }
            },

            async save() {
                const updates = [];
                const deletes = [...this.deletedIds];

                document.querySelectorAll('tr.edit-row.changed').forEach(row => {
                    const id = parseInt(row.dataset.id);
                    if (this.deletedIds.has(id)) return;
                    const cb = row.querySelector('.row-select');
                    if (!cb.checked) return;

                    const data = this.getRowData(row);
                    updates.push({
                        id: id,
                        status: data.status,
                        rating: data.rating,
                        watched_date: data.watched_date,
                        memo: data.memo,
                    });
                });

                if (updates.length === 0 && deletes.length === 0) {
                    App.toast('変更がありません');
                    return;
                }

                if (deletes.length > 0) {
                    if (!confirm(`${deletes.length}件を削除します。よろしいですか？`)) return;
                }

                const btns = [document.getElementById('saveBtn'), document.getElementById('saveBtn2')];
                btns.forEach(b => { b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i>保存中...'; });

                try {
                    const result = await App.post('/movie/api/bulk_update.php', { updates, deletes });
                    if (result.status === 'success') {
                        App.toast(result.message);
                        location.reload();
                    } else {
                        App.toast(result.message || '保存に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('保存中にエラーが発生しました');
                }

                btns.forEach(b => { b.disabled = false; });
                document.getElementById('saveBtn').innerHTML = '<i class="fa-solid fa-check mr-1.5"></i>保存';
                document.getElementById('saveBtn2').innerHTML = '<i class="fa-solid fa-check mr-1.5"></i>変更を保存';
            }
        };
    </script>
</body>
</html>
