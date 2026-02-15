<?php
/**
 * 管理画面ポータル View
 * カード色は親画面（管理画面）のテーマ色を継承。セッションの apps から取得するため追加のDBアクセスなし。
 */
$adminApp = null;
$apps = $_SESSION['user']['apps'] ?? [];
if (is_array($apps)) {
    foreach ($apps as $a) {
        if (isset($a['app_key']) && $a['app_key'] === 'admin') {
            $adminApp = $a;
            break;
        }
    }
}
$themePrimary = $adminApp['theme_primary'] ?? 'slate';
$themeLight   = $adminApp['theme_light']   ?? null;
$isThemeHex   = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $themePrimary);
$themeTailwind = 'slate';
if (!$isThemeHex && preg_match('/^([a-z]+)/', $themePrimary, $m)) {
    $allowed = ['indigo' => 1, 'sky' => 1, 'slate' => 1, 'amber' => 1, 'orange' => 1, 'violet' => 1, 'emerald' => 1];
    $themeTailwind = isset($allowed[$m[1]]) ? $m[1] : 'slate';
}
$cardIconBg   = $isThemeHex ? '' : "bg-{$themeTailwind}-50";
$cardIconText = $isThemeHex ? '' : "text-{$themeTailwind}-600";
$cardIconHover = $isThemeHex ? '' : "group-hover:bg-{$themeTailwind}-500 group-hover:text-white";
$cardDeco    = $isThemeHex ? '' : "text-{$themeTailwind}-500";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if ($isThemeHex):
        $bgHex = (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $themeLight ?? '') ? $themeLight : null);
        if (!$bgHex && preg_match('/^#([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})$/i', $themePrimary, $hex)) {
            $r = hexdec($hex[1]); $g = hexdec($hex[2]); $b = hexdec($hex[3]);
            $bgHex = "rgba($r,$g,$b,0.15)";
        }
        $bgHex = $bgHex ?: 'rgba(100,116,139,0.15)';
    ?>
    <style>
        .admin-portal-card .card-icon { background-color: <?= htmlspecialchars($bgHex) ?>; color: <?= htmlspecialchars($themePrimary) ?>; }
        .admin-portal-card:hover .card-icon { background-color: <?= htmlspecialchars($themePrimary) ?>; color: #fff; }
        .admin-portal-card .card-deco { color: <?= htmlspecialchars($themePrimary) ?>; }
    </style>
    <?php endif; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .app-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .app-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgb(0 0 0 / 0.08); }
    </style>
</head>
<body class="bg-[#f1f5f9] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 bg-slate-700 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-300">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">管理画面</h1>
            </div>
            <p class="text-[10px] font-bold text-slate-400 tracking-wider">管理者専用</p>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight mb-2">管理ツール</h2>
                    <p class="text-slate-500 font-medium">データベースの確認やユーザー管理を行います。</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-6">
                    <!-- DBビューワ -->
                    <a href="/db_viewer/" class="app-card admin-portal-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-database text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-database text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">DBビューワ</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">データベースのテーブル内容を参照します。</p>
                        </div>
                    </a>

                    <!-- DB一括抽出 -->
                    <a href="/admin/db_export.php" class="app-card admin-portal-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-file-export text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-file-export text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">DB一括抽出</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">全CREATE文・スキーマ概要・JSONを一括ダウンロード。</p>
                        </div>
                    </a>

                    <!-- ユーザー管理 -->
                    <a href="/admin/users.php" class="app-card admin-portal-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-users-gear text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-users-gear text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ユーザー管理</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ユーザーの追加・パスワードリセットを行います。</p>
                        </div>
                    </a>

                    <!-- アプリ管理 -->
                    <a href="/admin/apps.php" class="app-card admin-portal-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-layer-group text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-layer-group text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">アプリ管理</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">sys_apps の登録・編集・削除。保存で全セッション破棄。</p>
                        </div>
                    </a>

                    <!-- ロール管理 -->
                    <a href="/admin/roles.php" class="app-card admin-portal-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-user-tag text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-user-tag text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ロール管理</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">sys_roles の登録・編集。制限表示時はアプリ割り当て。保存で全セッション破棄。</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
