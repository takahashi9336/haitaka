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
        .lt-period-toggle.is-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(15,23,42,0.08); }
        .lt-trip-accent { background-color: var(--lt-theme); }
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
        <div class="max-w-5xl mx-auto w-full">
        <?php
        $currentPeriod = $_GET['period'] ?? 'upcoming';
        $currentSort = $_GET['sort'] ?? 'date_desc';
        $baseUrl = '/live_trip/';
        ?>
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4" aria-label="KPI">
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="text-xs font-bold text-slate-500">今後の遠征</div>
                <div class="mt-1 text-2xl font-black text-slate-900"><?= (int)($kpiUpcomingCount ?? 0) ?><span class="text-sm font-bold text-slate-500 ml-1">件</span></div>
                <div class="mt-1 text-xs text-slate-500"><?= htmlspecialchars((string)($kpiNextText ?? '')) ?></div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="text-xs font-bold text-slate-500">総遠征数</div>
                <div class="mt-1 text-2xl font-black text-slate-900"><?= (int)($kpiTotalCount ?? 0) ?><span class="text-sm font-bold text-slate-500 ml-1">件</span></div>
                <div class="mt-1 text-xs text-slate-500">過去も含む</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="text-xs font-bold text-slate-500">累計費用</div>
                <div class="mt-1 text-2xl font-black text-slate-900">¥<?= number_format((int)($kpiTotalExpense ?? 0)) ?></div>
                <div class="mt-1 text-xs text-slate-500">全遠征合計</div>
            </div>
        </section>

        <?php if (empty($allTrips)): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
                <i class="fa-solid fa-plane text-4xl text-slate-300 mb-4"></i>
                <p class="text-slate-500 mb-6">まだ遠征がありません</p>
                <a href="/live_trip/create.php" class="lt-theme-btn text-white px-6 py-3 rounded-xl font-bold inline-block">
                    最初の遠征を登録
                </a>
            </div>
        <?php else: ?>
            <div class="flex flex-wrap gap-3 mb-4 items-center justify-between">
                <div class="inline-flex items-center gap-2 flex-wrap w-fit max-w-full p-1 rounded-xl bg-slate-100 border border-slate-200">
                    <button type="button" class="lt-period-toggle px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white whitespace-nowrap <?= $currentPeriod === 'upcoming' ? 'is-active' : '' ?>" data-period="upcoming">今後の遠征</button>
                    <button type="button" class="lt-period-toggle px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white whitespace-nowrap <?= $currentPeriod === 'past' ? 'is-active' : '' ?>" data-period="past">過去の遠征</button>
                    <button type="button" class="lt-period-toggle px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white whitespace-nowrap <?= $currentPeriod === 'all' ? 'is-active' : '' ?>" data-period="all">すべて</button>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-500">並び順</label>
                    <select id="sortSelect" class="border border-slate-200 rounded px-2 py-1 text-sm bg-white">
                        <option value="date_desc" <?= $currentSort === 'date_desc' ? 'selected' : '' ?>>新しい順</option>
                        <option value="date_asc" <?= $currentSort === 'date_asc' ? 'selected' : '' ?>>古い順</option>
                    </select>
                </div>
            </div>

            <?php
                $firstTimelineLabelsLocal = $firstTimelineLabels ?? [];
                /** @var array<int,string> $firstTimelineLabelsLocal */
                $renderTripCard = function(array $t) use ($firstTimelineLabelsLocal): void {
                    $ed = (string)($t['event_date'] ?? '');
                    $firstDate = ($ed !== '' && strpos($ed, '〜') !== false) ? trim(substr($ed, 0, strpos($ed, '〜'))) : $ed;
                    $lastDate = ($ed !== '' && strpos($ed, '〜') !== false) ? trim(substr($ed, strpos($ed, '〜') + 3)) : $ed;
                    $today = date('Y-m-d');
                    $relativeDate = '';
                    $isUpcoming = null;
                    $daysDiff = null;
                    if ($ed !== '' && $lastDate !== '') {
                        $isUpcoming = $lastDate >= $today;
                        $refDate = $isUpcoming ? $firstDate : $lastDate;
                        try {
                            $d1 = new DateTime($refDate);
                            $d2 = new DateTime($today);
                            $diff = $d1->diff($d2, true);
                            $days = (int)($diff->days ?? 0);
                            $daysDiff = $days;
                            if ($isUpcoming) {
                                $relativeDate = $days === 0 ? '今日' : ($days === 1 ? '明日' : 'あと' . $days . '日');
                            } else {
                                $relativeDate = $days === 0 ? '昨日' : ($days === 1 ? '1日前' : $days . '日前');
                            }
                        } catch (\Throwable $e) { }
                    }

                    $dateMonth = '—';
                    $dateDay = '?';
                    $dateYear = '—';
                    if ($firstDate !== '') {
                        $ts = strtotime($firstDate);
                        if ($ts !== false) {
                            $dateMonth = strtoupper(date('M', $ts));
                            $dateDay = date('d', $ts);
                            $dateYear = date('Y', $ts);
                        }
                    }

                    $totalExp = (int)($t['total_expense'] ?? 0);
                    $checkTotal = (int)($t['checklist_total'] ?? 0);
                    $checkChecked = (int)($t['checklist_checked'] ?? 0);
                    $placeText = trim((string)($t['event_place'] ?? ''));
                    if ($placeText === '') {
                        $placeText = trim((string)($firstTimelineLabelsLocal[(int)($t['id'] ?? 0)] ?? ''));
                    }
                    if ($placeText === '') {
                        $placeText = '会場未登録';
                    }

                    $eventNameForBadge = trim((string)($t['event_name'] ?? ''));
                    $badgeLabel = '';
                    $badgeClass = 'bg-slate-100 text-slate-700';
                    if ($eventNameForBadge === '') {
                        $badgeLabel = 'イベント未紐付';
                        $badgeClass = 'bg-rose-50 text-rose-700';
                    } elseif (mb_strpos($eventNameForBadge, 'フェス') !== false) {
                        $badgeLabel = 'フェス';
                        $badgeClass = 'bg-indigo-50 text-indigo-700';
                    } elseif (preg_match('/(ミーグリ|ミート|個別トーク|握手)/u', $eventNameForBadge)) {
                        $badgeLabel = 'ミーグリ';
                        $badgeClass = 'bg-fuchsia-50 text-fuchsia-700';
                    } elseif (preg_match('/(コンサート|ライブ|誕祭)/u', $eventNameForBadge)) {
                        $badgeLabel = 'コンサート';
                        $badgeClass = 'bg-sky-50 text-sky-700';
                    } else {
                        $badgeLabel = 'その他';
                        $badgeClass = 'bg-slate-100 text-slate-700';
                    }

                    // カラーラインと同系色のカウントダウンバッジ
                    $countdownClass = 'bg-slate-100 text-slate-700';
                    if ($isUpcoming === true) {
                        $countdownClass = (is_int($daysDiff) && $daysDiff <= 30)
                            ? 'bg-amber-100 text-amber-800'
                            : 'bg-emerald-100 text-emerald-700';
                    } elseif ($isUpcoming === false) {
                        $countdownClass = 'bg-slate-100 text-slate-600';
                    }

                    // 🟡 近日（30日以内）／🟢 今後／⬜ 過去
                    $accentClass = 'bg-slate-200';
                    if ($isUpcoming === true) {
                        $accentClass = (is_int($daysDiff) && $daysDiff <= 30) ? 'bg-amber-300' : 'bg-emerald-300';
                    } elseif ($isUpcoming === false) {
                        $accentClass = 'bg-slate-200';
                    }
                ?>
                    <?php
                    ?>
                    <div class="bg-white border border-slate-200 rounded-xl py-4 pr-4 pl-0 hover:border-slate-300 hover:shadow-md transition relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 z-20 <?= htmlspecialchars($accentClass) ?>" aria-hidden="true"></div>
                        <a href="/live_trip/show.php?id=<?= (int)$t['id'] ?>" class="absolute inset-0 z-0 rounded-xl" aria-hidden="true"></a>

                        <div class="relative z-10 pointer-events-none flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4 pl-5 sm:pl-0">
                            <!-- mobile: 日付は上部ヘッダ帯（左カラムにしない） -->
                            <div class="sm:hidden -mt-4 -mr-4 mb-1 bg-slate-50/80 border-b border-slate-200 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="text-center w-[86px]">
                                            <div class="text-[10px] font-black text-slate-500 tracking-wider leading-tight"><?= htmlspecialchars($dateMonth) ?></div>
                                            <div class="text-2xl font-black text-slate-900 leading-tight"><?= htmlspecialchars($dateDay) ?></div>
                                            <div class="text-[10px] font-bold text-slate-500 leading-tight"><?= htmlspecialchars($dateYear) ?></div>
                                        </div>
                                        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black <?= $countdownClass ?>">
                                            <?= htmlspecialchars($relativeDate !== '' ? $relativeDate : '未設定') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- desktop: 左カラム -->
                            <div class="hidden sm:flex shrink-0 self-stretch -my-4 pr-4 pl-4 py-4 bg-slate-50/80 border-r border-slate-200 items-center">
                                <div class="text-center w-[92px]">
                                    <div class="text-[10px] font-black text-slate-500 tracking-wider leading-tight"><?= htmlspecialchars($dateMonth) ?></div>
                                    <div class="text-[28px] font-black text-slate-900 leading-tight"><?= htmlspecialchars($dateDay) ?></div>
                                    <div class="text-[10px] font-bold text-slate-500 leading-tight"><?= htmlspecialchars($dateYear) ?></div>
                                    <div class="mt-2 inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black <?= $countdownClass ?>">
                                        <?= htmlspecialchars($relativeDate !== '' ? $relativeDate : '未設定') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="font-black text-slate-900 truncate"><?= htmlspecialchars($t['title'] ?? $t['event_name'] ?? '（無題の遠征）') ?></h3>
                                <div class="mt-1 text-sm text-slate-600 truncate">
                                    <i class="fa-solid fa-location-dot mr-1 text-[#EA4335]"></i><?= htmlspecialchars($placeText) ?>
                                </div>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                                </div>
                            </div>

                            <div class="pointer-events-auto flex flex-wrap sm:flex-nowrap items-center gap-4 sm:gap-6 shrink-0">
                                <div class="text-right">
                                    <div class="text-[11px] font-bold text-slate-500">費用</div>
                                    <div class="font-black text-slate-900"><?= $totalExp > 0 ? ('¥' . number_format($totalExp)) : '—' ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] font-bold text-slate-500">リスト</div>
                                    <div class="font-black <?= $checkTotal > 0 && $checkChecked >= $checkTotal ? 'text-emerald-700' : 'text-slate-900' ?>"><?= (int)$checkChecked ?> / <?= (int)$checkTotal ?></div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-slate-300 hidden md:inline-block"></i>
                            </div>
                        </div>
                    </div>
                <?php
                };
            ?>

            <div id="group-upcoming" class="space-y-3 <?= $currentPeriod === 'past' ? 'hidden' : '' ?>">
                <div id="bar-upcoming" class="<?= $currentPeriod === 'all' ? '' : 'hidden' ?> text-xs font-black text-slate-500 px-1">今後の遠征（<?= isset($upcomingTripsView) ? count($upcomingTripsView) : 0 ?>件）</div>
                <?php foreach (($upcomingTripsView ?? []) as $t) { $renderTripCard($t); } ?>
            </div>

            <div id="group-past" class="space-y-3 mt-6 <?= $currentPeriod === 'upcoming' ? 'hidden' : '' ?>">
                <div id="bar-past" class="<?= $currentPeriod === 'all' ? '' : 'hidden' ?> text-xs font-black text-slate-500 px-1">過去の遠征（<?= isset($pastTripsView) ? count($pastTripsView) : 0 ?>件）</div>
                <?php foreach (($pastTripsView ?? []) as $t) { $renderTripCard($t); } ?>
                <?php if (!empty($undatedTripsView ?? [])): ?>
                    <div id="bar-undated" class="<?= $currentPeriod === 'all' ? '' : 'hidden' ?> text-xs font-black text-slate-500 px-1 mt-6">日程未設定（<?= count($undatedTripsView) ?>件）</div>
                    <div id="group-undated" class="space-y-3 <?= $currentPeriod === 'all' ? '' : 'hidden' ?>">
                        <?php foreach (($undatedTripsView ?? []) as $t) { $renderTripCard($t); } ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('mobile-open');
});
document.querySelectorAll('.lt-period-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
        const period = btn.getAttribute('data-period') || 'upcoming';
        setPeriod(period);
    });
});
function setPeriod(period) {
    const buttons = document.querySelectorAll('.lt-period-toggle');
    buttons.forEach((b) => b.classList.toggle('is-active', (b.getAttribute('data-period') || '') === period));

    const gUpcoming = document.getElementById('group-upcoming');
    const gPast = document.getElementById('group-past');
    const gUndated = document.getElementById('group-undated');
    const bUpcoming = document.getElementById('bar-upcoming');
    const bPast = document.getElementById('bar-past');
    const bUndated = document.getElementById('bar-undated');

    if (period === 'upcoming') {
        gUpcoming?.classList.remove('hidden');
        gPast?.classList.add('hidden');
        gUndated?.classList.add('hidden');
        bUpcoming?.classList.add('hidden');
        bPast?.classList.add('hidden');
        bUndated?.classList.add('hidden');
    } else if (period === 'past') {
        gUpcoming?.classList.add('hidden');
        gPast?.classList.remove('hidden');
        gUndated?.classList.add('hidden');
        bUpcoming?.classList.add('hidden');
        bPast?.classList.add('hidden');
        bUndated?.classList.add('hidden');
    } else {
        gUpcoming?.classList.remove('hidden');
        gPast?.classList.remove('hidden');
        // undated は存在する場合のみ表示
        if (gUndated) gUndated.classList.remove('hidden');
        bUpcoming?.classList.remove('hidden');
        bPast?.classList.remove('hidden');
        bUndated?.classList.remove('hidden');
    }

    const url = new URL(window.location.href);
    url.searchParams.set('period', period);
    history.replaceState(null, '', url.toString());
}
document.getElementById('sortSelect')?.addEventListener('change', function() {
    var url = new URL(window.location.href);
    url.searchParams.set('sort', this.value);
    if (!url.searchParams.has('period')) url.searchParams.set('period', 'upcoming');
    window.location.href = url.toString();
});
</script>
</body>
</html>
