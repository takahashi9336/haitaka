<?php
/**
 * 友達管理 View（管理者専用）
 * 友人視聴共有機能: 知り合いを1対1で登録
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
    <title>友達管理 - MyPlatform</title>
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
                    <i class="fa-solid fa-user-group text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">友達管理</h1>
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

                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-user-group text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-800">友達（知り合い）登録</h2>
                                <p class="text-[10px] font-bold text-slate-400 tracking-wider">視聴履歴を互いに共有するペアを登録</p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('addFriendModal').classList.remove('hidden')" class="shrink-0 w-full sm:w-auto <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-user-plus"></i> 友達を登録
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm min-w-[400px]">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">ユーザーA</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">ユーザーB</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">登録日</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider text-right">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($friends as $f): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($f['user_id_name']) ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($f['friend_id_name']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-500"><?= htmlspecialchars($f['created_at'] ?? '') ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="post" class="inline" onsubmit="return confirm('削除してよろしいですか？');">
                                            <input type="hidden" name="action" value="delete_friend">
                                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                            <button type="submit" class="text-[10px] font-bold text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">削除</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($friends)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">登録済みの友達はありません</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 友達登録モーダル -->
    <div id="addFriendModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-800">友達を登録</h2>
                    <button type="button" onclick="document.getElementById('addFriendModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="add_friend">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ユーザーA</label>
                        <select name="user_id_a" required class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all">
                            <option value="">選択してください</option>
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['id_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ユーザーB</label>
                        <select name="user_id_b" required class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all">
                            <option value="">選択してください</option>
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['id_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-xs text-slate-500">登録したペアは互いの視聴履歴（アニメ・映画・ドラマ）を参照できます。</p>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>登録する</button>
                        <button type="button" onclick="document.getElementById('addFriendModal').classList.add('hidden')" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all">キャンセル</button>
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
