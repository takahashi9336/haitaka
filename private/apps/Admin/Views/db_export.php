<?php
/**
 * DB一括抽出 View（管理画面の子画面・管理者専用）
 */
$uri = $_SERVER['REQUEST_URI'];
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB一括抽出 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        <?php if ($isThemeHex): ?>
        .admin-focus:focus { --tw-ring-color: var(--admin-theme); }
        .export-card:hover { border-color: var(--admin-theme); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
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
                    <i class="fa-solid fa-file-export text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">DB一括抽出</h1>
            </div>
            <p class="text-[10px] font-bold text-slate-400 tracking-wider">管理者専用</p>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-4xl mx-auto w-full">
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight mb-2">一括出力</h2>
                    <p class="text-slate-500 font-medium">全テーブルのスキーマを、CREATE文・Markdown概要・JSON のいずれかでダウンロードできます。AI共有やドキュメント用にご利用ください。</p>
                </div>

                <div class="grid gap-4 md:grid-cols-1">
                    <!-- 全CREATE文 -->
                    <a href="/admin/db_export.php?download=all_create" class="export-card block bg-white rounded-xl border border-slate-200 shadow-sm p-6 transition-all hover:border-slate-300">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-database text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-slate-800 mb-1">全CREATE文（.sql）</h3>
                                <p class="text-sm text-slate-500 mb-3">全テーブルの <code class="bg-slate-100 px-1 rounded text-xs">CREATE TABLE</code> 文を1つのSQLファイルで取得。スキーマバックアップ・AI共有に最適。</p>
                                <span class="inline-flex items-center gap-2 text-sm font-bold <?= $isThemeHex ? '' : 'text-' . $themeTailwind . '-600' ?>"<?= $isThemeHex ? ' style="color: var(--admin-theme)"' : '' ?>>
                                    <i class="fa-solid fa-download text-xs"></i>
                                    ダウンロード
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- スキーマ概要（Markdown） -->
                    <a href="/admin/db_export.php?download=schema_md" class="export-card block bg-white rounded-xl border border-slate-200 shadow-sm p-6 transition-all hover:border-slate-300">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-file-lines text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-slate-800 mb-1">スキーマ概要（.md）</h3>
                                <p class="text-sm text-slate-500 mb-3">全テーブル・カラムをMarkdownの表形式でまとめたドキュメント。READMEや設計書への貼り付けに便利。</p>
                                <span class="inline-flex items-center gap-2 text-sm font-bold <?= $isThemeHex ? '' : 'text-' . $themeTailwind . '-600' ?>"<?= $isThemeHex ? ' style="color: var(--admin-theme)"' : '' ?>>
                                    <i class="fa-solid fa-download text-xs"></i>
                                    ダウンロード
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- JSON -->
                    <a href="/admin/db_export.php?download=schema_json" class="export-card block bg-white rounded-xl border border-slate-200 shadow-sm p-6 transition-all hover:border-slate-300">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-code text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-slate-800 mb-1">JSON出力（.json）</h3>
                                <p class="text-sm text-slate-500 mb-3">テーブル名・カラム情報（型・NULL・キー・デフォルト等）とCREATE文を構造化したJSON。ツール連携や自動処理向け。</p>
                                <span class="inline-flex items-center gap-2 text-sm font-bold <?= $isThemeHex ? '' : 'text-' . $themeTailwind . '-600' ?>"<?= $isThemeHex ? ' style="color: var(--admin-theme)"' : '' ?>>
                                    <i class="fa-solid fa-download text-xs"></i>
                                    ダウンロード
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = function() { document.getElementById('sidebar').classList.add('mobile-open'); };
    </script>
</body>
</html>
