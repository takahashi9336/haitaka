<?php
$appKey = 'dashboard';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$themeHex = '#0ea5e9';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アニメ - <?= htmlspecialchars($tabLabels[$tab] ?? '一覧') ?> - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-100 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/anime/" class="w-8 h-8 rounded-lg flex items-center justify-center bg-sky-500 text-white shadow">
                    <i class="fa-solid fa-tv text-sm"></i>
                </a>
                <h1 class="font-black text-slate-700 text-xl"><?= htmlspecialchars($tabLabels[$tab] ?? '一覧') ?></h1>
            </div>
            <a href="/anime/" class="text-xs font-bold text-slate-500 hover:text-slate-700">ダッシュボード</a>
        </header>

        <div class="flex-1 overflow-y-auto px-3 py-4 sm:px-4 md:p-6">
            <div class="max-w-5xl mx-auto">
                <!-- タブ -->
                <div class="flex gap-1 mb-6 overflow-x-auto pb-1">
                    <?php foreach ($tabLabels as $t => $label): ?>
                    <a href="/anime/list.php?tab=<?= $t ?>" class="shrink-0 px-4 py-2 rounded-lg text-sm font-bold transition <?= $t === $tab ? 'bg-sky-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!$oauthConfigured): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-800">
                    <p class="font-bold">Annict OAuth が未設定です</p>
                    <p class="text-sm mt-2">検索機能を使用するには .env に Annict 設定が必要です。</p>
                </div>
                <?php elseif (empty($works)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-12 text-center text-slate-500">
                    <i class="fa-solid fa-tv text-4xl mb-3 text-slate-300"></i>
                    <p class="font-bold"><?= htmlspecialchars($tabLabels[$tab]) ?>の作品がありません</p>
                    <p class="text-sm mt-1">ダッシュボードで検索して作品を追加してください</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($works as $w): ?>
                    <a href="/anime/detail.php?id=<?= (int)($w['id'] ?? 0) ?>" class="block group min-w-0 overflow-hidden">
                        <div class="aspect-[2/3] rounded-lg overflow-hidden bg-slate-100 mb-2">
                            <?php if (!empty($w['images']['recommended_url'])): ?>
                            <img src="<?= htmlspecialchars($w['images']['recommended_url']) ?>" alt="" class="w-full h-full object-contain object-center group-hover:scale-105 transition" loading="lazy">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center"><i class="fa-solid fa-tv text-3xl text-slate-300"></i></div>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($w['title'] ?? '') ?></p>
                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($w['season_name_text'] ?? $w['season_name'] ?? '') ?></p>
                        <?php if (isset($w['episodes_count']) && $w['episodes_count'] > 0): ?>
                        <p class="text-[10px] text-slate-400"><?= $w['episodes_count'] ?>話</p>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
</body>
</html>
