<?php
$appKey = 'live_trip';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>遠征管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --lt-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .lt-theme-btn { background-color: var(--lt-theme); }
        .lt-theme-btn:hover { filter: brightness(1.08); }
        .lt-theme-link { color: var(--lt-theme); }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 overflow-auto overflow-x-hidden w-full">
    <header class="h-auto min-h-14 bg-white border-b <?= $headerBorder ?> flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 py-2 shrink-0">
        <div class="flex items-center gap-2 min-w-0 shrink">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars"></i></button>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0 <?= $headerIconBg ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-plane text-sm"></i>
            </div>
            <h1 class="font-black text-slate-700 text-lg sm:text-xl truncate">遠征管理</h1>
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="/live_trip/my_list.php" class="px-3 py-2 border border-slate-200 rounded-lg text-xs sm:text-sm font-bold hover:bg-slate-50 whitespace-nowrap" title="マイリスト"><i class="fa-solid fa-list sm:mr-1"></i><span class="hidden sm:inline">マイリスト</span></a>
            <a href="/live_trip/create.php" class="lt-theme-btn text-white px-3 py-2 rounded-lg font-bold text-xs sm:text-sm whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i><span class="hidden sm:inline">遠征を</span>追加
            </a>
        </div>
    </header>

    <div class="p-4 sm:p-6 flex-1 min-w-0">
        <?php if (empty($trips)): ?>
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
            <i class="fa-solid fa-plane text-4xl text-slate-300 mb-4"></i>
            <p class="text-slate-500 mb-6">まだ遠征がありません</p>
            <a href="/live_trip/create.php" class="lt-theme-btn text-white px-6 py-3 rounded-xl font-bold inline-block">
                最初の遠征を登録
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($trips as $t): ?>
            <a href="/live_trip/show.php?id=<?= (int)$t['id'] ?>" class="block bg-white border border-slate-200 rounded-xl p-4 hover:border-slate-300 hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-slate-800"><?= htmlspecialchars($t['event_name'] ?? '（未設定）') ?></h3>
                        <p class="text-sm text-slate-500 mt-1">
                            <?= htmlspecialchars($t['event_date'] ?? '') ?>
                            <?php if (!empty($t['event_place'])): ?>
                                · <?= htmlspecialchars($t['event_place']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('mobile-open');
});
</script>
</body>
</html>
