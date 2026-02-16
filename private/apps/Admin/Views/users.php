<?php
/**
 * ユーザー管理 View（管理者専用）
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        <?php if ($isThemeHex): ?>
        .admin-btn-primary { background-color: var(--admin-theme); }
        .admin-btn-primary:hover { filter: brightness(1.08); }
        .admin-link { color: var(--admin-theme); }
        .admin-link:hover { background-color: <?= htmlspecialchars($themeLight ?: 'rgba(100,116,139,0.12)') ?>; }
        .admin-focus:focus { --tw-ring-color: var(--admin-theme); }
        .admin-link-btn { color: var(--admin-theme); background-color: <?= htmlspecialchars($themeLight ?: 'rgba(100,116,139,0.12)') ?>; }
        .admin-link-btn:hover { background-color: var(--admin-theme); color: #fff; }
        <?php endif; ?>
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
                    <i class="fa-solid fa-users-gear text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ユーザー管理</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-4xl mx-auto w-full">
                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-users text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-800">ユーザー一覧</h2>
                                <p class="text-[10px] font-bold text-slate-400 tracking-wider">登録ユーザーの管理とパスワードリセット</p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('createUserModal').classList.remove('hidden')" class="shrink-0 w-full sm:w-auto <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-user-plus"></i> ユーザーを追加
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm min-w-[400px]">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">ユーザーID</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">権限</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider text-right">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($allUsers as $u): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($u['id_name']) ?></td>
                                    <td class="px-6 py-4 text-xs font-bold tracking-wider text-slate-500"><?= $u['role'] === 'admin' ? '管理者' : 'ユーザー' ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button type="button" onclick="openResetModal(<?= (int)$u['id'] ?>, <?= json_encode($u['id_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)" class="text-[10px] font-black tracking-wider px-3 py-1.5 rounded-lg transition-colors <?= $isThemeHex ? 'admin-link-btn' : $cardIconText . ' bg-' . $themeTailwind . '-50 hover:bg-' . $themeTailwind . '-100' ?>">リセット</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ユーザー追加モーダル -->
    <div id="createUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-800">ユーザーを追加</h2>
                    <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <form id="formCreateUser" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ユーザーID（ログインID）</label>
                        <input type="text" name="id_name" required maxlength="64" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all" placeholder="半角英数字など">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">初期パスワード</label>
                        <input type="password" name="password" required minlength="6" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all" placeholder="6文字以上">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">権限（ロール）</label>
                        <select name="role" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all">
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r['role_key']) ?>"><?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['role_key']) ?>)</option>
                            <?php endforeach; ?>
                            <?php if (empty($roles)): ?>
                            <option value="user">ユーザー</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>登録する</button>
                        <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all">キャンセル</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- パスワードリセットモーダル -->
    <div id="resetModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-800">パスワードをリセット</h2>
                    <button type="button" onclick="document.getElementById('resetModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <p class="text-sm text-slate-500 mb-4"><span id="resetTargetName" class="font-bold text-slate-700"></span> の新しいパスワードを入力してください。</p>
                <form id="formReset" class="space-y-4">
                    <input type="hidden" name="target_id" id="resetTargetId">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">新しいパスワード</label>
                        <input type="password" name="new_password" id="resetNewPassword" required minlength="6" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all" placeholder="6文字以上">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>リセットする</button>
                        <button type="button" onclick="document.getElementById('resetModal').classList.add('hidden')" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all">キャンセル</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        document.getElementById('formCreateUser').onsubmit = async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            const res = await App.post('/users_settings/api/create_user.php', data);
            if (res.status === 'success') {
                alert('ユーザーを登録しました');
                document.getElementById('createUserModal').classList.add('hidden');
                e.target.reset();
                location.reload();
            } else {
                alert('エラー: ' + (res.message || '登録に失敗しました'));
            }
        };

        function openResetModal(userId, idName) {
            document.getElementById('resetTargetId').value = userId;
            document.getElementById('resetTargetName').textContent = idName;
            document.getElementById('resetNewPassword').value = '';
            document.getElementById('resetModal').classList.remove('hidden');
        }
        document.getElementById('formReset').addEventListener('submit', async (e) => {
            e.preventDefault();
            const targetId = document.getElementById('resetTargetId').value;
            const newPassword = document.getElementById('resetNewPassword').value;
            const res = await App.post('/users_settings/api/admin_reset.php', { target_id: targetId, new_password: newPassword });
            if (res.status === 'success') {
                alert('パスワードをリセットしました');
                document.getElementById('resetModal').classList.add('hidden');
            } else {
                alert('エラー: ' + (res.message || 'リセットに失敗しました'));
            }
        });
    </script>
</body>
</html>
