<?php
/**
 * ロール管理 View（管理者専用）
 * $allRoles, $allApps, $roleAppIds[role_id => [app_id, ...]]
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ロール管理 - MyPlatform</title>
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
        <?php endif; ?>
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        @media (max-width: 767px) {
            .roles-table-wrap { display: none; }
        }
        @media (min-width: 768px) {
            .roles-card-list { display: none; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/admin/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-user-tag text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ロール管理</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                    <p class="text-sm text-slate-500">sys_roles の一覧。保存すると全ユーザーのセッションを破棄します。</p>
                    <button type="button" onclick="openRoleModal()" class="<?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2 whitespace-nowrap shrink-0"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-plus"></i> ロールを追加
                    </button>
                </div>
                <!-- スマホ: カード一覧 -->
                <div class="roles-card-list space-y-3 mb-6">
                    <?php foreach ($allRoles as $r): ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($r['role_key']) ?></p>
                                <p class="text-sm text-slate-600 mt-0.5"><?= htmlspecialchars($r['name']) ?></p>
                                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($r['default_route'] ?? '') ?></p>
                                <p class="text-xs font-bold mt-1 <?= ($r['sidebar_mode'] ?? '') === 'restricted' ? 'text-amber-600' : 'text-slate-400' ?>"><?= ($r['sidebar_mode'] ?? 'full') === 'restricted' ? '制限' : '全表示' ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick='openRoleModal(<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>, <?= json_encode($roleAppIds[(int)$r['id']] ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="text-xs font-bold admin-link <?= !$isThemeHex ? $cardIconText . ' hover:bg-' . $themeTailwind . '-50' : '' ?> px-3 py-1.5 rounded-lg transition">編集</button>
                                <form method="post" class="inline" onsubmit="return confirm('削除してよろしいですか？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">削除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($allRoles)): ?>
                    <p class="text-center text-slate-400 text-sm py-8">ロールがありません。</p>
                    <?php endif; ?>
                </div>
                <!-- PC: テーブル -->
                <div class="roles-table-wrap bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">role_key</th>
                                <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">表示名</th>
                                <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">初期URL</th>
                                <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">サイドバー</th>
                                <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($allRoles as $r): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($r['role_key']) ?></td>
                                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($r['name']) ?></td>
                                <td class="px-6 py-4 text-slate-500 text-xs"><?= htmlspecialchars($r['default_route'] ?? '') ?></td>
                                <td class="px-6 py-4 text-xs font-bold <?= ($r['sidebar_mode'] ?? '') === 'restricted' ? 'text-amber-600' : 'text-slate-400' ?>"><?= ($r['sidebar_mode'] ?? 'full') === 'restricted' ? '制限' : '全表示' ?></td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" onclick='openRoleModal(<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>, <?= json_encode($roleAppIds[(int)$r['id']] ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="text-xs font-bold admin-link <?= !$isThemeHex ? $cardIconText . ' hover:bg-' . $themeTailwind . '-50' : '' ?> px-3 py-1.5 rounded-lg transition">編集</button>
                                    <form method="post" class="inline" onsubmit="return confirm('削除してよろしいですか？');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="text-xs font-bold text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">削除</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($allRoles)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">ロールがありません。</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 追加・編集モーダル -->
    <div id="roleModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 id="roleModalTitle" class="text-lg font-bold text-slate-800">ロールを追加</h2>
                    <button type="button" onclick="document.getElementById('roleModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <form id="formRole" method="post">
                    <input type="hidden" name="action" id="formRoleAction" value="create">
                    <input type="hidden" name="id" id="formRoleId">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">role_key</label>
                            <input type="text" name="role_key" id="f_role_key" required maxlength="32" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="admin, user, hinata">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">表示名</label>
                            <input type="text" name="name" id="f_role_name" required maxlength="64" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="管理者">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">説明</label>
                            <input type="text" name="description" id="f_role_description" maxlength="255" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ログイン後の初期URL（default_route）</label>
                            <input type="text" name="default_route" id="f_role_default_route" maxlength="128" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" value="/index.php" placeholder="/index.php">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ロゴ文言（logo_text）</label>
                            <input type="text" name="logo_text" id="f_logo_text" maxlength="64" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="空欄=MyPlatform">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">サイドバー（sidebar_mode）</label>
                            <select name="sidebar_mode" id="f_sidebar_mode" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm">
                                <option value="full">full（全アプリ表示）</option>
                                <option value="restricted">restricted（許可アプリのみ）</option>
                            </select>
                        </div>
                        <div id="roleAppsBlock" class="hidden border border-slate-100 rounded-xl p-4 bg-slate-50/50">
                            <p class="text-[10px] font-black text-slate-400 tracking-wider mb-2">このロールで表示するアプリ（restricted のとき）</p>
                            <div class="max-h-48 overflow-y-auto space-y-2">
                                <?php foreach ($allApps as $a): ?>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="app_ids[]" value="<?= (int)$a['id'] ?>" class="role-app-cb rounded border-slate-300">
                                    <span class="text-sm text-slate-700"><?= htmlspecialchars($a['name']) ?> (<?= htmlspecialchars($a['app_key']) ?>)</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6 pt-4 border-t border-slate-100">
                        <button type="submit" class="flex-1 <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>保存</button>
                        <button type="button" onclick="document.getElementById('roleModal').classList.add('hidden')" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all">キャンセル</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        document.getElementById('f_sidebar_mode').addEventListener('change', function() {
            document.getElementById('roleAppsBlock').classList.toggle('hidden', this.value !== 'restricted');
        });

        function openRoleModal(role, appIds) {
            const modal = document.getElementById('roleModal');
            const form = document.getElementById('formRole');
            const title = document.getElementById('roleModalTitle');
            document.getElementById('formRoleAction').value = role ? 'update' : 'create';
            document.getElementById('formRoleId').value = role ? role.id : '';
            const appIdsSet = new Set((appIds || []).map(String));
            document.querySelectorAll('.role-app-cb').forEach(function(cb) {
                cb.checked = appIdsSet.has(cb.value);
            });
            if (role) {
                document.getElementById('f_role_key').value = role.role_key || '';
                document.getElementById('f_role_name').value = role.name || '';
                document.getElementById('f_role_description').value = role.description || '';
                document.getElementById('f_role_default_route').value = role.default_route || '/index.php';
                document.getElementById('f_logo_text').value = role.logo_text || '';
                document.getElementById('f_sidebar_mode').value = role.sidebar_mode || 'full';
                title.textContent = 'ロールを編集';
            } else {
                form.reset();
                document.getElementById('formRoleAction').value = 'create';
                document.getElementById('formRoleId').value = '';
                document.getElementById('f_role_default_route').value = '/index.php';
                title.textContent = 'ロールを追加';
            }
            document.getElementById('roleAppsBlock').classList.toggle('hidden', document.getElementById('f_sidebar_mode').value !== 'restricted');
            modal.classList.remove('hidden');
        }
    </script>
</body>
</html>
