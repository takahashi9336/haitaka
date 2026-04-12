<?php
/**
 * Health ポータル View
 */
$appKey = 'health';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health - MyPlatform</title>
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
        .app-card { transition: all 0.25s; }
        .app-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgb(0 0 0 / 0.08); }
        .app-card.disabled { opacity: 0.55; cursor: not-allowed; }
        .app-card.disabled:hover { transform: none; box-shadow: none; }
    </style>
    <style>:root { --health-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-heart-pulse text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">Health</h1>
            </div>
            <p class="text-[10px] font-bold text-slate-400 tracking-wider"><?= htmlspecialchars($user['id_name'] ?? '') ?></p>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight mb-2">ヘルス機能</h2>
                    <p class="text-slate-500 font-medium">日々の生活を支える小さなユーティリティ。</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                    <a href="/health/kitchen_stock.php" class="app-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform <?= $cardDeco ?>">
                            <i class="fa-solid fa-basket-shopping text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-basket-shopping text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">食材ストック</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">冷蔵庫の在庫を一覧・編集。</p>
                        </div>
                    </a>

                    <a href="/health/training_menu.php" class="app-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform <?= $cardDeco ?>">
                            <i class="fa-solid fa-dumbbell text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                                <i class="fa-solid fa-dumbbell text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">トレーニングメニュー</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メニューと回数を記録し、動画を参照。</p>
                        </div>
                    </a>

                    <div class="app-card disabled group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block aspect-square md:aspect-auto min-h-0">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 <?= $cardDeco ?>">
                            <i class="fa-solid fa-calendar-week text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors bg-slate-50 text-slate-500">
                                <i class="fa-solid fa-calendar-week text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">1週間の献立</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">在庫＋条件からAIで提案（準備中）。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>

