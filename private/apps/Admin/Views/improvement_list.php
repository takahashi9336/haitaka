<?php
/**
 * 対応管理 View（一覧・編集）
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$statusFilter = $_GET['status'] ?? '';
$screenNameFilter = $_GET['screen_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>対応管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex ?? '#64748b') ?>; }
        .admin-btn-primary { background-color: var(--admin-theme); }
        .admin-btn-primary:hover { filter: brightness(1.08); }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .improvement-list-table { table-layout: fixed; min-width: 800px; }
        .improvement-list-table td.screen-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .improvement-list-table td.content-cell { white-space: pre-wrap; word-break: break-word; line-height: 1.5; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; -webkit-line-clamp: 4; line-clamp: 4; }
        .improvement-list-table th:nth-child(2),
        .improvement-list-table td.content-cell { width: 53%; min-width: 280px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?? 'bg-slate-50' ?>">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/admin/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg bg-slate-700">
                    <i class="fa-solid fa-list-check text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">対応管理</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-[1400px] mx-auto w-full">
                <?php if ($success ?? null): ?>
                <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700 font-bold"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error ?? null): ?>
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 font-bold"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">改善事項一覧</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">システムの対応・改善事項の管理</p>
                        </div>
                        <button type="button" onclick="document.getElementById('newItemForm').classList.toggle('hidden')" class="shrink-0 admin-btn-primary text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-plus"></i> 新規追加
                        </button>
                    </div>

                    <!-- フィルタ -->
                    <form method="get" action="/admin/improvement_list.php" class="flex flex-wrap gap-3 mb-6">
                        <select name="status" class="h-9 border border-slate-200 rounded-lg px-3 text-sm">
                            <option value="">すべてのステータス</option>
                            <?php foreach (\App\Admin\Model\ImprovementItemModel::STATUS_LABELS as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $statusFilter === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="screen_name" value="<?= htmlspecialchars($screenNameFilter) ?>" placeholder="画面名で検索" class="h-9 border border-slate-200 rounded-lg px-3 text-sm w-48">
                        <button type="submit" class="h-9 px-4 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-200">絞り込み</button>
                    </form>

                    <!-- 新規追加フォーム -->
                    <div id="newItemForm" class="hidden mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <form method="post" action="/admin/improvement_list.php">
                            <input type="hidden" name="action" value="create">
                            <div class="grid gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">画面名</label>
                                    <input type="text" name="screen_name" required class="w-full h-9 border border-slate-200 rounded-lg px-3 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">改善事項</label>
                                    <textarea name="content" required rows="2" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm"></textarea>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="admin-btn-primary text-white text-xs font-black px-4 py-2 rounded-lg">追加</button>
                                    <button type="button" onclick="document.getElementById('newItemForm').classList.add('hidden')" class="text-slate-500 text-xs font-bold px-4 py-2">キャンセル</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="improvement-list-table w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-4 font-black text-[10px] text-slate-400 tracking-wider w-[16%] min-w-[140px]">画面名</th>
                                    <th class="px-4 py-4 font-black text-[10px] text-slate-400 tracking-wider">改善事項</th>
                                    <th class="px-4 py-4 font-black text-[10px] text-slate-400 tracking-wider w-[10%] min-w-[90px]">ステータス</th>
                                    <th class="px-4 py-4 font-black text-[10px] text-slate-400 tracking-wider w-[9%] min-w-[85px]">追加日</th>
                                    <th class="px-4 py-4 font-black text-[10px] text-slate-400 tracking-wider text-right w-[12%] min-w-[110px]">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm font-bold">改善事項がありません</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <?php
                                    $statusLabels = \App\Admin\Model\ImprovementItemModel::STATUS_LABELS;
                                    $statusLabel = $statusLabels[$item['status']] ?? $item['status'];
                                    $statusClass = match($item['status']) {
                                        'pending' => 'bg-amber-50 text-amber-700',
                                        'done' => 'bg-emerald-50 text-emerald-700',
                                        'cancelled' => 'bg-slate-100 text-slate-500',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="screen-name px-4 py-4 font-mono text-xs font-bold text-slate-700" title="<?= htmlspecialchars($item['screen_name']) ?>"><?= htmlspecialchars($item['screen_name']) ?></td>
                                    <td class="content-cell px-4 py-4 text-sm text-slate-700"><?= nl2br(htmlspecialchars($item['content'])) ?></td>
                                    <td class="px-4 py-4 shrink-0">
                                        <span class="text-[10px] font-black px-2 py-1 rounded-full <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-slate-500 shrink-0"><?= htmlspecialchars(date('Y/m/d', strtotime($item['created_at']))) ?></td>
                                    <td class="px-4 py-4 text-right shrink-0">
                                        <button type="button" class="edit-item-btn text-[10px] font-black tracking-wider px-3 py-1.5 rounded-lg transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200"
                                            data-id="<?= (int)$item['id'] ?>"
                                            data-screen-name="<?= htmlspecialchars($item['screen_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-content="<?= htmlspecialchars($item['content'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-memo="<?= htmlspecialchars($item['memo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">編集</button>
                                        <form method="post" action="/admin/improvement_list.php" class="inline ml-1" onsubmit="return confirm('削除しますか？');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="text-[10px] font-black tracking-wider px-3 py-1.5 rounded-lg transition-colors text-red-500 hover:bg-red-50">削除</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 編集モーダル -->
    <div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/40" style="display: none;">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6">
            <h3 class="font-black text-lg text-slate-800 mb-4">編集</h3>
            <form method="post" action="/admin/improvement_list.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="_filter_status" value="<?= htmlspecialchars($statusFilter) ?>">
                <input type="hidden" name="_filter_screen_name" value="<?= htmlspecialchars($screenNameFilter) ?>">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">画面名</label>
                        <input type="text" name="screen_name" id="editScreenName" required class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">改善事項</label>
                        <textarea name="content" id="editContent" required rows="3" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">ステータス</label>
                        <select name="status" id="editStatus" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm">
                            <?php foreach (\App\Admin\Model\ImprovementItemModel::STATUS_LABELS as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">メモ</label>
                        <textarea name="memo" id="editMemo" rows="2" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="admin-btn-primary text-white text-xs font-black px-6 py-2.5 rounded-lg">保存</button>
                        <button type="button" onclick="closeEditModal()" class="text-slate-500 text-xs font-bold px-6 py-2.5">キャンセル</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        function openEditModal(id, screenName, content, status, memo) {
            document.getElementById('editId').value = id;
            document.getElementById('editScreenName').value = screenName || '';
            document.getElementById('editContent').value = content || '';
            document.getElementById('editStatus').value = status || 'pending';
            document.getElementById('editMemo').value = memo || '';
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').style.display = 'none';
        }
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        document.querySelectorAll('.edit-item-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openEditModal(
                    this.dataset.id,
                    this.dataset.screenName || '',
                    this.dataset.content || '',
                    this.dataset.status || 'pending',
                    this.dataset.memo || ''
                );
            });
        });
    </script>
</body>
</html>
