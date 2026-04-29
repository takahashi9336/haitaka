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
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
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
        <?php
        $currentPeriod = $_GET['period'] ?? 'all';
        $currentSort = $_GET['sort'] ?? 'date_desc';
        $baseUrl = '/live_trip/';
        ?>
        <?php if (empty($trips)): ?>
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
            <i class="fa-solid fa-plane text-4xl text-slate-300 mb-4"></i>
            <p class="text-slate-500 mb-6">まだ遠征がありません</p>
            <a href="/live_trip/create.php" class="lt-theme-btn text-white px-6 py-3 rounded-xl font-bold inline-block">
                最初の遠征を登録
            </a>
        </div>
        <?php else: ?>
        <div class="flex flex-wrap gap-2 mb-4 items-center justify-between">
            <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                <a href="<?= $baseUrl ?>?period=upcoming<?= $currentSort !== 'date_desc' ? '&sort=' . htmlspecialchars($currentSort) : '' ?>" class="px-3 py-2 text-sm font-medium <?= $currentPeriod === 'upcoming' ? 'bg-slate-100 text-slate-800' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">今後の遠征</a>
                <a href="<?= $baseUrl ?>?period=past<?= $currentSort !== 'date_desc' ? '&sort=' . htmlspecialchars($currentSort) : '' ?>" class="px-3 py-2 text-sm font-medium border-l border-slate-200 <?= $currentPeriod === 'past' ? 'bg-slate-100 text-slate-800' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">過去の遠征</a>
                <a href="<?= $baseUrl ?>?period=all<?= $currentSort !== 'date_desc' ? '&sort=' . htmlspecialchars($currentSort) : '' ?>" class="px-3 py-2 text-sm font-medium border-l border-slate-200 <?= $currentPeriod === 'all' ? 'bg-slate-100 text-slate-800' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">すべて</a>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">並び順:</label>
                <select id="sortSelect" class="border border-slate-200 rounded px-2 py-1 text-sm">
                    <option value="date_desc" <?= $currentSort === 'date_desc' ? 'selected' : '' ?>>新しい順</option>
                    <option value="date_asc" <?= $currentSort === 'date_asc' ? 'selected' : '' ?>>古い順</option>
                </select>
            </div>
        </div>
        <div class="space-y-3">
            <?php foreach ($trips as $t):
                $ed = $t['event_date'] ?? '';
                $firstDate = ($ed !== '' && strpos($ed, '〜') !== false) ? trim(substr($ed, 0, strpos($ed, '〜'))) : $ed;
                $lastDate = ($ed !== '' && strpos($ed, '〜') !== false) ? trim(substr($ed, strpos($ed, '〜') + 3)) : $ed;
                $today = date('Y-m-d');
                $relativeDate = '';
                if ($ed !== '') {
                    $isUpcoming = $lastDate >= $today;
                    $refDate = $isUpcoming ? $firstDate : $lastDate;
                    $d1 = new DateTime($refDate);
                    $d2 = new DateTime($today);
                    $diff = $d1->diff($d2, true);
                    $days = (int)($diff->days ?? 0);
                    if ($isUpcoming) {
                        $relativeDate = $days === 0 ? '今日' : ($days === 1 ? '明日' : 'あと' . $days . '日');
                    } else {
                        $relativeDate = $days === 0 ? '昨日' : ($days === 1 ? '1日前' : $days . '日前');
                    }
                }
                $totalExp = (int)($t['total_expense'] ?? 0);
                $checkTotal = (int)($t['checklist_total'] ?? 0);
                $checkChecked = (int)($t['checklist_checked'] ?? 0);
            ?>
            <div class="block bg-white border border-slate-200 rounded-xl p-4 hover:border-slate-300 hover:shadow-md transition relative">
                <a href="/live_trip/show.php?id=<?= (int)$t['id'] ?>" class="absolute inset-0 z-0 rounded-xl" aria-hidden="true"></a>
                <div class="flex justify-between items-start gap-2 relative z-10 pointer-events-none">
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-slate-800"><?= htmlspecialchars($t['title'] ?? $t['event_name'] ?? '（無題の遠征）') ?></h3>
                        <p class="text-sm text-slate-500 mt-1">
                            <?= htmlspecialchars($ed !== '' ? $ed : 'イベント未紐付') ?>
                            <?php if ($relativeDate): ?><span class="text-slate-400">(<?= htmlspecialchars($relativeDate) ?>)</span><?php endif; ?>
                            <?php if (!empty($t['event_place'])): ?>
                                · <?= htmlspecialchars($t['event_place']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="flex flex-wrap gap-3 mt-2 text-xs text-slate-600">
                            <?php if ($totalExp > 0): ?>
                            <span><i class="fa-solid fa-yen-sign text-slate-400 mr-0.5"></i>¥<?= number_format($totalExp) ?></span>
                            <?php endif; ?>
                            <?php if ($checkTotal > 0): ?>
                            <span><i class="fa-solid fa-list-check text-slate-400 mr-0.5"></i><?= $checkChecked ?>/<?= $checkTotal ?></span>
                            <?php endif; ?>
                            <a href="/live_trip/shiori.php?id=<?= (int)$t['id'] ?>" target="_blank" rel="noopener" class="lt-theme-link hover:underline relative z-20 pointer-events-auto"><i class="fa-solid fa-book mr-0.5"></i>しおり</a>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 shrink-0 mt-1"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('mobile-open');
});
document.getElementById('sortSelect')?.addEventListener('change', function() {
    var url = new URL(window.location.href);
    url.searchParams.set('sort', this.value);
    if (!url.searchParams.has('period')) url.searchParams.set('period', 'all');
    window.location.href = url.toString();
});
</script>
</body>
</html>
