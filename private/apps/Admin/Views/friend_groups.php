<?php
/**
 * グループ管理 View（管理者専用）
 * 友人視聴共有機能: 視聴履歴を共有するユーザーグループ
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$cardIconText = $cardIconText ?? '';
$themeTailwind = $themeTailwind ?? 'indigo';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>グループ管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        <?php if ($isThemeHex): ?>
        .admin-btn-primary { background-color: var(--admin-theme); }
        .admin-btn-primary:hover { filter: brightness(1.08); }
        .admin-link-btn { color: var(--admin-theme); background-color: <?= htmlspecialchars($themeLight ?: 'rgba(100,116,139,0.12)') ?>; }
        .admin-link-btn:hover { background-color: var(--admin-theme); color: #fff; }
        .admin-focus:focus { --tw-ring-color: var(--admin-theme); }
        <?php endif; ?>
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/admin/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-users-rectangle text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">グループ管理</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-4xl mx-auto w-full">
                <?php if (!empty($_SESSION['admin_error'])): ?>
                <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium"><?= htmlspecialchars($_SESSION['admin_error']) ?></div>
                <?php unset($_SESSION['admin_error']); endif; ?>
                <?php if (!empty($_SESSION['admin_success'])): ?>
                <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium"><?= htmlspecialchars($_SESSION['admin_success']) ?></div>
                <?php unset($_SESSION['admin_success']); endif; ?>

                <?php if ($editGroup): ?>
                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm mb-6">
                    <h2 class="text-base font-bold text-slate-800 mb-4">グループを編集</h2>
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="action" value="update_group">
                        <input type="hidden" name="group_id" value="<?= (int)$editGroup['id'] ?>">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">グループ名</label>
                            <input type="text" name="group_name" required value="<?= htmlspecialchars($editGroup['name']) ?>" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-2">メンバー</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-slate-100 rounded-xl p-4">
                                <?php
                                $editMemberIds = array_column($editMembers, 'user_id');
                                foreach ($allUsers as $u):
                                    $checked = in_array((int)$u['id'], $editMemberIds, true);
                                ?>
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded-lg px-2 py-1">
                                    <input type="checkbox" name="member_ids[]" value="<?= (int)$u['id'] ?>" <?= $checked ? 'checked' : '' ?> class="rounded">
                                    <span class="text-sm"><?= htmlspecialchars($u['id_name']) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="<?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>更新</button>
                            <a href="/admin/friend_groups.php" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all flex items-center">キャンセル</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-users-rectangle text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-800">ユーザーグループ</h2>
                                <p class="text-[10px] font-bold text-slate-400 tracking-wider">同一グループ内で視聴履歴を共有</p>
                            </div>
                        </div>
                        <?php if (!$editGroup): ?>
                        <button type="button" onclick="document.getElementById('createGroupModal').classList.remove('hidden')" class="shrink-0 w-full sm:w-auto <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-plus"></i> グループを作成
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($groups as $g): ?>
                        <div class="flex items-center justify-between gap-4 p-4 rounded-xl border border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <div>
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($g['name']) ?></p>
                                <p class="text-xs text-slate-500"><?= (int)$g['member_count'] ?> 人</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="/admin/friend_groups.php?edit=<?= (int)$g['id'] ?>" class="text-xs font-bold <?= $isThemeHex ? 'admin-link-btn' : $cardIconText . ' bg-' . $themeTailwind . '-50 hover:bg-' . $themeTailwind . '-100' ?> px-3 py-1.5 rounded-lg transition">編集</a>
                                <form method="post" class="inline" onsubmit="return confirm('削除してよろしいですか？');">
                                    <input type="hidden" name="action" value="delete_group">
                                    <input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">削除</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($groups)): ?>
                        <p class="py-8 text-center text-slate-400 text-sm">グループはありません</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- グループ作成モーダル -->
    <div id="createGroupModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 md:p-8 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-800">グループを作成</h2>
                    <button type="button" onclick="document.getElementById('createGroupModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="create_group">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">グループ名</label>
                        <input type="text" name="group_name" required class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all" placeholder="例: 家族">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-2">メンバー</label>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-slate-100 rounded-xl p-4">
                            <?php foreach ($allUsers as $u): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded-lg px-2 py-1">
                                <input type="checkbox" name="member_ids[]" value="<?= (int)$u['id'] ?>" class="rounded">
                                <span class="text-sm"><?= htmlspecialchars($u['id_name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">同一グループのメンバーは互いの視聴履歴を参照できます。</p>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>作成</button>
                        <button type="button" onclick="document.getElementById('createGroupModal').classList.add('hidden')" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all">キャンセル</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
