<?php
/**
 * アプリ管理 View（管理者専用）
 * $appsTree: 親子ツリー、$allApps: フラット一覧（親選択用）
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
// クロージャ use で参照するため、ここで確実に定義（theme_from_session で設定済み）
$cardIconText = $cardIconText ?? '';
$themeTailwind = $themeTailwind ?? 'indigo';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アプリ管理 - MyPlatform</title>
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
                    <i class="fa-solid fa-layer-group text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">アプリ管理</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-12">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                    <p class="text-sm text-slate-500 min-w-0">sys_apps のツリー一覧。保存すると全ユーザーのセッションを破棄します。</p>
                    <button type="button" onclick="openAppModal()" class="<?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2 whitespace-nowrap shrink-0"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-plus"></i> アプリを追加
                    </button>
                </div>
                <?php if (!empty($_SESSION['admin_error'])): ?>
                <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium"><?= htmlspecialchars($_SESSION['admin_error']) ?></div>
                <?php unset($_SESSION['admin_error']); endif; ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <ul class="divide-y divide-slate-50">
                        <?php
                        $renderApp = function ($app, $depth = 0) use (&$renderApp, $isThemeHex, $cardIconText, $themeTailwind) {
                            $indent = $depth * 24;
                            $isSystem = !empty($app['is_system']);
                            ?>
                            <li class="flex items-center justify-between gap-2 md:gap-4 px-4 md:px-6 py-3 hover:bg-slate-50/50" style="padding-left: <?= $indent + 24 ?>px;">
                                <div class="flex items-center gap-2 md:gap-3 min-w-0 flex-1">
                                    <?php if (!empty($app['icon_class'])): ?>
                                    <i class="fa-solid <?= htmlspecialchars($app['icon_class']) ?> text-slate-400 w-5 text-center shrink-0"></i>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-800"><?= htmlspecialchars($app['name']) ?></span>
                                        <span class="text-xs text-slate-400 ml-2"><?= htmlspecialchars($app['app_key']) ?></span>
                                        <?php if ($isSystem): ?><span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded ml-2 whitespace-nowrap inline-block">システム</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" onclick='openAppModal(<?= json_encode($app, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="text-xs font-bold admin-link <?= !$isThemeHex ? $cardIconText . ' hover:bg-' . $themeTailwind . '-50' : '' ?> px-3 py-1.5 rounded-lg transition">編集</button>
                                    <?php if (!$isSystem): ?>
                                    <form method="post" class="inline" onsubmit="return confirm('削除してよろしいですか？');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$app['id'] ?>">
                                        <button type="submit" class="text-xs font-bold text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">削除</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php foreach ($app['children'] ?? [] as $child) {
                                $renderApp($child, $depth + 1);
                            } ?>
                        <?php };
                        foreach ($appsTree as $app) {
                            $renderApp($app, 0);
                        }
                        if (empty($appsTree)): ?>
                            <li class="px-6 py-8 text-center text-slate-400 text-sm">アプリがありません。追加してください。</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- 追加・編集モーダル -->
    <div id="appModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 id="appModalTitle" class="text-lg font-bold text-slate-800">アプリを追加</h2>
                    <button type="button" onclick="document.getElementById('appModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <form id="formApp" method="post">
                    <input type="hidden" name="action" id="formAppAction" value="create">
                    <input type="hidden" name="id" id="formAppId">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">app_key</label>
                            <input type="text" name="app_key" id="f_app_key" required maxlength="64" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="task_manager, hinata 等">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">表示名</label>
                            <input type="text" name="name" id="f_name" required maxlength="100" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="タスク管理">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">親アプリ</label>
                            <select name="parent_id" id="f_parent_id" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm">
                                <option value="">なし（トップレベル）</option>
                                <?php foreach ($allApps as $a): if (isset($a['parent_id']) && $a['parent_id'] !== null && $a['parent_id'] !== '') continue; ?>
                                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?> (<?= htmlspecialchars($a['app_key']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">route_prefix</label>
                            <input type="text" name="route_prefix" id="f_route_prefix" maxlength="128" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="/task_manager/, /hinata/">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">path（子のとき）</label>
                            <input type="text" name="path" id="f_path" maxlength="255" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="members.php">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">icon_class（FontAwesome）</label>
                            <input type="text" name="icon_class" id="f_icon_class" maxlength="64" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="fa-list-check">
                        </div>
                        <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/50">
                            <p class="text-[10px] font-black text-slate-400 tracking-wider mb-2">テーマ色（サイドバー選択時の色）</p>
                            <p class="text-xs text-slate-500 mb-3">カラーピッカーで選択するか、プリセットをクリック。子画面は未設定時は親の色を継承します。</p>
                            <div class="flex flex-wrap gap-3 items-end mb-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">メイン色（primary）</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" id="f_theme_primary_picker" value="#6366f1" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer" title="カラー選択">
                                        <input type="text" name="theme_primary" id="f_theme_primary" maxlength="32" class="flex-1 border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="#6366f1 または indigo">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">薄い色（背景・light）</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" id="f_theme_light_picker" value="#eef2ff" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer" title="カラー選択">
                                        <input type="text" name="theme_light" id="f_theme_light" maxlength="32" class="flex-1 border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="#eef2ff または indigo-50">
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="text-[10px] text-slate-500 mr-1 self-center">プリセット:</span>
                                <button type="button" data-primary="#6366f1" data-light="#eef2ff" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-indigo-500 hover:ring-2 ring-indigo-300 transition" title="indigo"></button>
                                <button type="button" data-primary="#0ea5e9" data-light="#f0f9ff" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-sky-500 hover:ring-2 ring-sky-300 transition" title="sky"></button>
                                <button type="button" data-primary="#64748b" data-light="#f8fafc" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-slate-500 hover:ring-2 ring-slate-300 transition" title="slate"></button>
                                <button type="button" data-primary="#f59e0b" data-light="#fffbeb" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-amber-500 hover:ring-2 ring-amber-300 transition" title="amber"></button>
                                <button type="button" data-primary="#ea580c" data-light="#fff7ed" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-orange-500 hover:ring-2 ring-orange-300 transition" title="orange"></button>
                                <button type="button" data-primary="#8b5cf6" data-light="#f5f3ff" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-violet-500 hover:ring-2 ring-violet-300 transition" title="violet"></button>
                                <button type="button" data-primary="#10b981" data-light="#ecfdf5" class="theme-preset w-8 h-8 rounded-lg border border-slate-200 bg-emerald-500 hover:ring-2 ring-emerald-300 transition" title="emerald"></button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">default_route</label>
                            <input type="text" name="default_route" id="f_default_route" maxlength="128" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="/index.php">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">description</label>
                            <input type="text" name="description" id="f_description" maxlength="255" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="管理用メモ">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">sort_order</label>
                            <input type="number" name="sort_order" id="f_sort_order" value="0" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm">
                        </div>
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_visible" id="f_is_visible" value="1" checked class="rounded border-slate-300">
                                <span class="text-sm font-bold text-slate-700">表示する</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="admin_only" id="f_admin_only" value="1" class="rounded border-slate-300">
                                <span class="text-sm font-bold text-slate-700">管理者のみ</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_system" id="f_is_system" value="1" class="rounded border-slate-300">
                                <span class="text-sm font-bold text-slate-700">システム固定（削除不可）</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6 pt-4 border-t border-slate-100">
                        <button type="submit" class="flex-1 <?= $isThemeHex ? 'admin-btn-primary' : $btnBgClass ?> text-white text-xs font-black tracking-wider h-12 rounded-xl transition-all"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>保存</button>
                        <button type="button" onclick="document.getElementById('appModal').classList.add('hidden')" class="px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold h-12 rounded-xl transition-all">キャンセル</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        function setThemeFromHex(hex) {
            if (!/^#[0-9A-Fa-f]{6}$/.test(hex)) return hex;
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            const l = (r * 0.299 + g * 0.587 + b * 0.114) / 255;
            const light = l > 0.7 ? '#f8fafc' : '#' + [r, g, b].map(c => Math.min(255, Math.round(c + (255 - c) * 0.85))).map(c => c.toString(16).padStart(2, '0')).join('');
            return light;
        }
        document.getElementById('f_theme_primary_picker').addEventListener('input', function() {
            const hex = this.value;
            document.getElementById('f_theme_primary').value = hex;
            document.getElementById('f_theme_light_picker').value = setThemeFromHex(hex);
            document.getElementById('f_theme_light').value = setThemeFromHex(hex);
        });
        document.getElementById('f_theme_light_picker').addEventListener('input', function() {
            document.getElementById('f_theme_light').value = this.value;
        });
        document.getElementById('f_theme_primary').addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                document.getElementById('f_theme_primary_picker').value = this.value;
            }
        });
        document.getElementById('f_theme_light').addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                document.getElementById('f_theme_light_picker').value = this.value;
            }
        });
        document.querySelectorAll('.theme-preset').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('f_theme_primary').value = this.dataset.primary;
                document.getElementById('f_theme_light').value = this.dataset.light;
                document.getElementById('f_theme_primary_picker').value = this.dataset.primary;
                document.getElementById('f_theme_light_picker').value = this.dataset.light;
            });
        });

        function openAppModal(app) {
            const modal = document.getElementById('appModal');
            const form = document.getElementById('formApp');
            const title = document.getElementById('appModalTitle');
            document.getElementById('formAppAction').value = app ? 'update' : 'create';
            document.getElementById('formAppId').value = app ? app.id : '';
            if (app) {
                document.getElementById('f_app_key').value = app.app_key || '';
                document.getElementById('f_name').value = app.name || '';
                document.getElementById('f_parent_id').value = app.parent_id || '';
                document.getElementById('f_route_prefix').value = app.route_prefix || '';
                document.getElementById('f_path').value = app.path || '';
                document.getElementById('f_icon_class').value = app.icon_class || '';
                const primary = app.theme_primary || '';
                const light = app.theme_light || '';
                document.getElementById('f_theme_primary').value = primary;
                document.getElementById('f_theme_light').value = light;
                if (/^#[0-9A-Fa-f]{6}$/.test(primary)) {
                    document.getElementById('f_theme_primary_picker').value = primary;
                } else {
                    document.getElementById('f_theme_primary_picker').value = '#6366f1';
                }
                if (/^#[0-9A-Fa-f]{6}$/.test(light)) {
                    document.getElementById('f_theme_light_picker').value = light;
                } else {
                    document.getElementById('f_theme_light_picker').value = '#eef2ff';
                }
                document.getElementById('f_default_route').value = app.default_route || '';
                document.getElementById('f_description').value = app.description || '';
                document.getElementById('f_sort_order').value = app.sort_order ?? 0;
                document.getElementById('f_is_visible').checked = app.is_visible != 0;
                document.getElementById('f_admin_only').checked = app.admin_only == 1;
                document.getElementById('f_is_system').checked = app.is_system == 1;
                title.textContent = 'アプリを編集';
            } else {
                form.reset();
                document.getElementById('formAppAction').value = 'create';
                document.getElementById('formAppId').value = '';
                document.getElementById('f_sort_order').value = '0';
                document.getElementById('f_is_visible').checked = true;
                document.getElementById('f_theme_primary_picker').value = '#6366f1';
                document.getElementById('f_theme_light_picker').value = '#eef2ff';
                title.textContent = 'アプリを追加';
            }
            modal.classList.remove('hidden');
        }
    </script>
</body>
</html>
