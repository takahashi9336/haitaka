<?php
/**
 * イベント一覧 View (4ビューモード対応)
 * 物理パス: haitaka/private/apps/Hinata/Views/event_index.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$getCatInfo = function($catId) {
    return match((int)$catId) {
        1  => ['name' => 'ライブ',        'color' => '#3b82f6', 'icon' => 'fa-music'],
        2  => ['name' => 'ミーグリ',      'color' => '#10b981', 'icon' => 'fa-handshake'],
        3  => ['name' => 'リアルミーグリ', 'color' => '#f59e0b', 'icon' => 'fa-users'],
        4  => ['name' => 'リリース',      'color' => '#8b5cf6', 'icon' => 'fa-compact-disc'],
        5  => ['name' => 'メディア',      'color' => '#ec4899', 'icon' => 'fa-tv'],
        6  => ['name' => 'スペイベ',      'color' => '#f97316', 'icon' => 'fa-star'],
        99 => ['name' => 'その他',        'color' => '#64748b', 'icon' => 'fa-calendar'],
        default => ['name' => 'その他',   'color' => '#64748b', 'icon' => 'fa-calendar'],
    };
};
$allCategories = [
    ['id' => 0,  'name' => 'すべて',        'color' => 'var(--hinata-theme)', 'icon' => 'fa-layer-group'],
    ['id' => 1,  'name' => 'ライブ',        'color' => '#3b82f6', 'icon' => 'fa-music'],
    ['id' => 2,  'name' => 'ミーグリ',      'color' => '#10b981', 'icon' => 'fa-handshake'],
    ['id' => 3,  'name' => 'リアルMG',      'color' => '#f59e0b', 'icon' => 'fa-users'],
    ['id' => 4,  'name' => 'リリース',      'color' => '#8b5cf6', 'icon' => 'fa-compact-disc'],
    ['id' => 5,  'name' => 'メディア',      'color' => '#ec4899', 'icon' => 'fa-tv'],
    ['id' => 6,  'name' => 'スペイベ',      'color' => '#f97316', 'icon' => 'fa-star'],
    ['id' => 99, 'name' => 'その他',        'color' => '#64748b', 'icon' => 'fa-calendar'],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>イベントスケジュール - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .date-col { width: 65px; flex-shrink: 0; }
        .detail-panel { max-height: 0; overflow: hidden; transition: max-height 0.35s ease, opacity 0.3s ease; opacity: 0; }
        .detail-panel.open { opacity: 1; }
        .event-card-past { opacity: 0.55; }
        .event-card-past:hover { opacity: 0.85; }
        .filter-chip { transition: all 0.2s; }
        .filter-chip.active { color: white !important; }

        /* View Mode Switcher */
        .vm-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; color: #94a3b8; }
        .vm-btn:hover { background: #f1f5f9; color: #64748b; }
        .vm-btn.active { background: var(--hinata-theme); color: white; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }

        /* Calendar: shared day cell */
        .cal-day { min-height: 34px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; border-radius: 6px; transition: background 0.15s; }
        .cal-day:hover { background: #f1f5f9; }
        .cal-day.other-month { opacity: 0.2; }
        .cal-day .day-num { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 11px; font-weight: 700; line-height: 1; }
        .cal-day.today .day-num { background: var(--hinata-theme); color: white; }
        .cal-day.selected { background: #e0f2fe; }
        .cal-day.today.selected .day-num { box-shadow: 0 0 0 2px white, 0 0 0 4px var(--hinata-theme); }
        /* Desktop: 3-month sidebar */
        .cal-sidebar { width: 340px; }
        .cal-sidebar .cal-day { min-height: 40px; }
        .cal-sidebar .cal-day .day-num { width: 30px; height: 30px; font-size: 13px; }
        /* Mobile: collapsible single month with swipe */
        .cal-mobile-wrap { overflow: hidden; transition: max-height 0.3s ease; }
        .cal-mobile-grid { transition: transform 0.3s ease; }

        /* Timeline View */
        .tl-line { position: absolute; left: 50%; width: 2px; top: 0; bottom: 0; background: #e2e8f0; transform: translateX(-50%); }
        @media (max-width: 768px) { .tl-line { left: 20px; } }
        .tl-node { width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #e2e8f0; position: absolute; left: 50%; transform: translateX(-50%); top: 24px; z-index: 2; }
        @media (max-width: 768px) { .tl-node { left: 20px; } }
        .tl-item { position: relative; width: 50%; padding: 8px 24px; margin-bottom: 8px; }
        .tl-item.tl-left { margin-left: 0; padding-right: 40px; text-align: right; }
        .tl-item.tl-right { margin-left: 50%; padding-left: 40px; }
        @media (max-width: 768px) {
            .tl-item, .tl-item.tl-left, .tl-item.tl-right { width: 100%; margin-left: 0; padding-left: 48px; padding-right: 8px; text-align: left; }
        }
        .tl-today-marker { position: relative; z-index: 3; display: flex; align-items: center; gap: 8px; margin: 16px 0; }
        .tl-today-marker::before, .tl-today-marker::after { content: ''; flex: 1; height: 2px; background: var(--hinata-theme); }

        /* Dashboard View */
        .dash-scroll { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 8px; scroll-behavior: smooth; scrollbar-width: none; }
        .dash-scroll::-webkit-scrollbar { display: none; }
        .dash-card { flex: 0 0 260px; cursor: pointer; transition: transform 0.2s; }
        .dash-card:hover { transform: translateY(-3px); }
        .stat-card { background: white; border-radius: 16px; padding: 16px; display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 80px; }

        /* Master-Detail View */
        .md-list { width: 340px; flex-shrink: 0; border-right: 1px solid #e2e8f0; overflow-y: auto; }
        @media (max-width: 1024px) { .md-list { width: 100%; border-right: none; } }
        .md-list-item { cursor: pointer; transition: background 0.15s; }
        .md-list-item:hover { background: #f8fafc; }
        .md-list-item.active { background: #eff6ff; border-left: 3px solid var(--hinata-theme); }
        .md-detail { flex: 1; overflow-y: auto; }
        @media (max-width: 1024px) {
            .md-detail-overlay { position: fixed; inset: 0; z-index: 40; background: rgba(0,0,0,0.3); }
            .md-detail-panel { position: fixed; right: 0; top: 0; bottom: 0; width: 90%; max-width: 480px; z-index: 50; background: white; box-shadow: -4px 0 24px rgba(0,0,0,0.1); transform: translateX(100%); transition: transform 0.3s ease; }
            .md-detail-panel.open { transform: translateX(0); }
        }

        /* Slide Panel (shared for Timeline/Dashboard) */
        .slide-panel-overlay { position: fixed; inset: 0; z-index: 40; background: rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.25s; pointer-events: none; }
        .slide-panel-overlay.open { opacity: 1; pointer-events: auto; }
        .slide-panel { position: fixed; right: 0; top: 0; bottom: 0; width: 90%; max-width: 480px; z-index: 50; background: white; box-shadow: -4px 0 24px rgba(0,0,0,0.1); transform: translateX(100%); transition: transform 0.3s ease; overflow-y: auto; }
        .slide-panel.open { transform: translateX(0); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <!-- Header -->
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-calendar-days text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight">イベント</h1>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="flex bg-slate-100 rounded-xl p-0.5 gap-0.5">
                    <button onclick="switchViewMode('calendar')" data-vm="calendar" class="vm-btn active" title="カレンダー+リスト"><i class="fa-solid fa-calendar-days text-xs"></i></button>
                    <button onclick="switchViewMode('timeline')" data-vm="timeline" class="vm-btn" title="タイムライン"><i class="fa-solid fa-timeline text-xs"></i></button>
                    <button onclick="switchViewMode('dashboard')" data-vm="dashboard" class="vm-btn" title="ダッシュボード"><i class="fa-solid fa-gauge-high text-xs"></i></button>
                    <button onclick="switchViewMode('master')" data-vm="master" class="vm-btn" title="マスター・ディテール"><i class="fa-solid fa-columns text-xs"></i></button>
                </div>
                <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                <a href="/hinata/event_admin.php" class="text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition flex items-center gap-1 ml-1"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span class="hidden sm:inline">管理</span>
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- ========== VIEW 1: Calendar + List ========== -->
        <div id="view-calendar" class="flex-1 flex flex-col md:flex-row min-h-0">
            <!-- Desktop: 3-month sidebar -->
            <div id="calSidebar" class="cal-sidebar hidden md:flex flex-col bg-white border-r border-slate-100 shrink-0 overflow-y-auto custom-scroll">
                <div class="sticky top-0 z-[2] bg-white border-b border-slate-50 px-5 py-3 flex items-center justify-between">
                    <button onclick="MiniCal.nav(-1)" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center"><i class="fa-solid fa-chevron-left text-slate-400 text-sm"></i></button>
                    <button onclick="MiniCal.goToday()" class="text-sm font-black text-slate-500 hover:text-slate-700 tracking-wider transition">TODAY</button>
                    <button onclick="MiniCal.nav(1)" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center"><i class="fa-solid fa-chevron-right text-slate-400 text-sm"></i></button>
                </div>
                <div id="calSideContent" class="p-5 space-y-6"></div>
            </div>

            <!-- Mobile: swipeable single-month calendar -->
            <div id="miniCalMobile" class="md:hidden bg-white border-b <?= $cardBorder ?> shrink-0">
                <div class="px-3 pt-2 pb-0">
                    <div class="flex items-center justify-between mb-1">
                        <button onclick="MiniCal.nav(-1)" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center"><i class="fa-solid fa-chevron-left text-slate-400 text-xs"></i></button>
                        <button onclick="MiniCal.goToday()" id="calTitle" class="text-xs font-black text-slate-700 tracking-tight hover:text-slate-500 transition cursor-pointer"></button>
                        <button onclick="MiniCal.nav(1)" class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center"><i class="fa-solid fa-chevron-right text-slate-400 text-xs"></i></button>
                    </div>
                    <div id="calDowHeader" class="grid grid-cols-7 text-center mb-0.5"></div>
                    <div id="calMobileWrap" class="cal-mobile-wrap">
                        <div id="calMobileGrid" class="cal-mobile-grid grid grid-cols-7"></div>
                    </div>
                </div>
                <button id="calToggle" class="w-full flex items-center justify-center py-0.5 text-slate-300 hover:text-slate-500 transition">
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
            </div>

            <!-- Right content area: filters + event list -->
            <div class="flex-1 flex flex-col min-h-0 min-w-0">
                <div class="bg-white border-b <?= $cardBorder ?> px-4 py-2 shrink-0">
                    <div class="flex justify-center gap-1.5 overflow-x-auto pb-0.5" style="scrollbar-width:none; -webkit-overflow-scrolling:touch;">
                        <?php foreach ($allCategories as $fc): ?>
                        <button onclick="filterCategory(<?= $fc['id'] ?>)" data-filter-cat="<?= $fc['id'] ?>"
                            class="filter-chip shrink-0 flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border transition <?= $fc['id'] === 0 ? 'text-white border-transparent' : 'text-slate-500 bg-white border-slate-200 hover:border-slate-300' ?>"
                            <?= $fc['id'] === 0 ? 'style="background:var(--hinata-theme);"' : '' ?>>
                            <i class="fa-solid <?= $fc['icon'] ?> text-[8px]" <?= $fc['id'] !== 0 ? 'style="color:' . $fc['color'] . ';"' : '' ?>></i>
                            <?= htmlspecialchars($fc['name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scroll" id="mainScroll">
                    <div id="eventListContainer" class="p-4 md:p-8 space-y-3 max-w-4xl mx-auto">
                    <?php if (empty($events)): ?>
                    <div class="text-center py-20 bg-white rounded-[2.5rem] border <?= $cardBorder ?> shadow-sm">
                        <i class="fa-solid fa-calendar-xmark text-4xl text-slate-200 mb-4"></i>
                        <p class="text-slate-400 font-bold">予定されているイベントはありません</p>
                    </div>
                    <?php else: ?>
                        <?php if (!empty($nextEvent)):
                            $neCat = $getCatInfo($nextEvent['category']);
                            $neDaysLeft = max(0, (int)$nextEvent['days_left']);
                            $neDate = \Core\Utils\DateUtil::format($nextEvent['event_date'], 'Y/n/j');
                            $neDow = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'][\Core\Utils\DateUtil::format($nextEvent['event_date'], 'D')];
                        ?>
                        <div class="rounded-xl overflow-hidden shadow-md cursor-pointer mb-2" onclick="scrollToEvent(<?= $nextEvent['id'] ?>)" style="background: linear-gradient(135deg, <?= $neCat['color'] ?>, <?= $neCat['color'] ?>cc);">
                            <div class="px-5 py-4 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center shrink-0"><i class="fa-solid <?= $neCat['icon'] ?> text-white text-lg"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10px] font-bold text-white/70 tracking-wider mb-0.5">NEXT EVENT</div>
                                    <h3 class="font-black text-white text-sm md:text-base truncate"><?= htmlspecialchars($nextEvent['event_name']) ?></h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] text-white/80"><?= $neDate ?>（<?= $neDow ?>）</span>
                                        <?php if (!empty($nextEvent['event_place'])): ?>
                                        <span class="text-[10px] text-white/60"><i class="fa-solid fa-location-dot mr-0.5"></i><?= htmlspecialchars($nextEvent['event_place']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="shrink-0 text-center bg-white/20 rounded-xl px-3 py-2">
                                    <?php if ($neDaysLeft === 0): ?>
                                    <div class="text-xl font-black text-white leading-none">TODAY</div>
                                    <?php else: ?>
                                    <div class="text-2xl font-black text-white leading-none"><?= $neDaysLeft ?></div>
                                    <div class="text-[9px] font-bold text-white/70">日後</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php
                        $currentMonth = '';
                        $todaySeparatorInserted = false;
                        $todayStr = date('Y-m-d');
                        foreach ($events as $e):
                            $cat = $getCatInfo($e['category']);
                            $time = \Core\Utils\DateUtil::format($e['event_date'], 'H:i');
                            $dateM = \Core\Utils\DateUtil::format($e['event_date'], 'n/j');
                            $dateD = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'][\Core\Utils\DateUtil::format($e['event_date'], 'D')];
                            $eventDateStr = \Core\Utils\DateUtil::format($e['event_date'], 'Y-m-d');
                            $isToday = $todayStr === $eventDateStr;
                            $isPast = $eventDateStr < $todayStr;
                            $eventMonth = \Core\Utils\DateUtil::format($e['event_date'], 'Y-m');
                        ?>
                        <?php if ($eventMonth !== $currentMonth): $currentMonth = $eventMonth; ?>
                        <div class="sticky top-0 z-[5] -mx-4 md:-mx-8 px-4 md:px-8 py-2 bg-gradient-to-r from-slate-100/95 to-slate-50/80 backdrop-blur-sm" data-month-header="<?= $eventMonth ?>">
                            <span class="text-xs font-black text-slate-400 tracking-wider"><?= \Core\Utils\DateUtil::format($e['event_date'], 'Y年 n月') ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!$todaySeparatorInserted && !$isPast && !$isToday): $todaySeparatorInserted = true; ?>
                        <div id="today-separator" class="flex items-center gap-3 py-1">
                            <div class="flex-1 h-px bg-sky-300"></div>
                            <span class="text-[10px] font-black text-sky-500 tracking-widest shrink-0"><i class="fa-solid fa-caret-right mr-1"></i>TODAY</span>
                            <div class="flex-1 h-px bg-sky-300"></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($isToday && !$todaySeparatorInserted): $todaySeparatorInserted = true; endif; ?>

                        <div id="event-card-<?= $e['id'] ?>" class="bg-white rounded-lg border border-sky-50 shadow-sm overflow-hidden hover:border-sky-200 transition-all <?= $isPast ? 'event-card-past' : '' ?>" data-event-id="<?= $e['id'] ?>" data-category="<?= (int)$e['category'] ?>" data-date="<?= $eventDateStr ?>">
                            <div class="flex items-stretch cursor-pointer active:bg-slate-50" onclick="toggleDetail(<?= $e['id'] ?>)">
                                <div class="date-col flex flex-col items-center justify-center py-4 border-r border-slate-100 <?= $isToday ? 'bg-sky-50' : 'bg-slate-50/50' ?>">
                                    <span class="text-[10px] font-black tracking-wider <?= $isToday ? 'text-sky-500' : 'text-slate-400' ?>"><?= $dateD ?></span>
                                    <span class="text-lg font-black leading-none <?= $isToday ? 'text-sky-600' : 'text-slate-700' ?>"><?= $dateM ?></span>
                                    <?php if ($isToday): ?><span class="text-[8px] font-black text-sky-500 mt-0.5 tracking-widest">TODAY</span><?php endif; ?>
                                </div>
                                <div class="w-1.5 shrink-0" style="background-color: <?= $isPast ? '#cbd5e1' : $cat['color'] ?>;"></div>
                                <div class="flex-1 p-4 min-w-0 flex items-center justify-between">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <i class="fa-solid <?= $cat['icon'] ?> text-[8px]" style="color: <?= $cat['color'] ?>;"></i>
                                            <span class="text-[9px] font-black text-slate-400 tracking-wider"><?= htmlspecialchars($cat['name']) ?></span>
                                            <?php if (in_array((int)$e['category'], [2, 3]) && !empty($e['my_status'])):
                                                $badgeDefs = [1 => ['l' => '参加予定','c' => '#10b981'], 4 => ['l' => '当選','c' => '#0ea5e9'], 5 => ['l' => '落選','c' => '#f43f5e'], 3 => ['l' => '検討中','c' => '#f59e0b'], 2 => ['l' => '不参加','c' => '#94a3b8']];
                                                $bd = $badgeDefs[(int)$e['my_status']] ?? null;
                                                if ($bd): ?>
                                            <span id="status-badge-<?= $e['id'] ?>" class="text-[8px] font-bold text-white px-1.5 py-0.5 rounded-full leading-none" style="background:<?= $bd['c'] ?>;"><?= $bd['l'] ?></span>
                                            <?php endif; endif; ?>
                                            <span class="text-[10px] font-bold text-slate-400"><?= $time ?></span>
                                        </div>
                                        <h3 class="font-black text-slate-800 text-sm md:text-base truncate"><?= htmlspecialchars($e['event_name']) ?></h3>
                                        <?php if(!empty($e['event_place'])): ?>
                                        <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1"><i class="fa-solid fa-location-dot text-sky-300"></i> <?= htmlspecialchars($e['event_place']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="px-2"><i id="arrow-<?= $e['id'] ?>" class="fa-solid fa-chevron-down text-slate-200 text-xs transition-transform duration-300"></i></div>
                                </div>
                            </div>
                            <div id="detail-<?= $e['id'] ?>" class="detail-panel border-t border-slate-50 bg-slate-50/30">
                                <div class="p-4 space-y-4">
                                <?php if ((int)$e['category'] === 1): ?>
                                <div class="flex items-center gap-2">
                                    <button onclick="toggleAttendance(<?= $e['id'] ?>, this)" class="attendance-btn text-[10px] font-bold px-3 py-1.5 rounded-full border transition flex items-center gap-1 <?= in_array($e['id'], $attendedEventIds ?? []) ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-slate-500 border-slate-200 hover:border-sky-300' ?>" data-attended="<?= in_array($e['id'], $attendedEventIds ?? []) ? '1' : '0' ?>">
                                        <i class="fa-solid fa-flag text-[8px]"></i>
                                        <span><?= in_array($e['id'], $attendedEventIds ?? []) ? '参戦済み' : '参戦した' ?></span>
                                    </button>
                                    <button onclick="loadSetlist(<?= $e['id'] ?>)" class="text-[10px] font-bold text-slate-400 hover:text-slate-600 px-3 py-1.5 rounded-full border border-slate-200 hover:border-slate-300 transition flex items-center gap-1">
                                        <i class="fa-solid fa-list-ol text-[8px]"></i>セットリスト
                                    </button>
                                </div>
                                <div id="setlist-<?= $e['id'] ?>" class="hidden"></div>
                                <?php endif; ?>
                                <?php if (!empty($eventSlots[$e['id']])): ?>
                                    <div class="bg-white rounded-xl border border-emerald-100 shadow-sm overflow-hidden">
                                        <div class="px-4 py-2 bg-emerald-50 flex items-center gap-2">
                                            <i class="fa-solid fa-ticket text-emerald-500 text-xs"></i>
                                            <span class="text-[10px] font-bold text-emerald-700 tracking-wider">自分のミーグリ予定</span>
                                            <a href="/hinata/meetgreet.php" class="ml-auto text-[10px] font-bold text-emerald-500 hover:text-emerald-700 transition">詳細 <i class="fa-solid fa-arrow-right text-[8px]"></i></a>
                                        </div>
                                        <div class="divide-y divide-slate-50">
                                            <?php foreach ($eventSlots[$e['id']] as $slot): ?>
                                            <div class="px-4 py-2 flex items-center gap-4 text-xs">
                                                <div class="w-20 shrink-0">
                                                    <span class="font-bold text-slate-700"><?= htmlspecialchars($slot['slot_name']) ?></span>
                                                    <?php if ($slot['start_time'] && $slot['end_time']): ?>
                                                        <div class="text-[10px] text-slate-400"><?= substr($slot['start_time'], 0, 5) ?>～<?= substr($slot['end_time'], 0, 5) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <?php $slotColor = $slot['color1'] ?? '#94a3b8'; ?>
                                                    <span class="font-bold" style="color: <?= htmlspecialchars($slotColor) ?>;"><?= htmlspecialchars($slot['member_name'] ?? $slot['member_name_raw'] ?? '不明') ?></span>
                                                </div>
                                                <div class="shrink-0 font-bold text-slate-600"><?= isset($ticketUsedSums[(int)$slot['id']]) ? $ticketUsedSums[(int)$slot['id']] : (int)($slot['ticket_count'] ?? 0) ?><span class="text-[10px] text-slate-400">枚</span></div>
                                                <?php if (!empty($slot['report'])): ?><i class="fa-solid fa-pen-to-square text-emerald-400 text-[10px] shrink-0" title="レポあり"></i><?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (in_array((int)$e['category'], [2, 3])): ?>
                                    <?php $myStatus = (int)($e['my_status'] ?? 0); ?>
                                    <div class="flex flex-wrap items-center gap-1.5" id="status-chips-<?= $e['id'] ?>">
                                        <span class="text-[9px] font-black text-slate-400 tracking-wider mr-1">参戦予定</span>
                                        <?php
                                        $statusDefs = [
                                            1 => ['label' => '参加予定', 'color' => '#10b981', 'icon' => 'fa-circle-check'],
                                            4 => ['label' => '当選',     'color' => '#0ea5e9', 'icon' => 'fa-trophy'],
                                            5 => ['label' => '落選',     'color' => '#f43f5e', 'icon' => 'fa-circle-xmark'],
                                            3 => ['label' => '検討中',   'color' => '#f59e0b', 'icon' => 'fa-clock'],
                                            2 => ['label' => '不参加',   'color' => '#94a3b8', 'icon' => 'fa-ban'],
                                        ];
                                        foreach ($statusDefs as $stVal => $stDef):
                                            $isActive = $myStatus === $stVal;
                                        ?>
                                        <button onclick="toggleEventStatus(<?= $e['id'] ?>, <?= $stVal ?>, this)"
                                            class="es-chip text-[10px] font-bold px-2.5 py-1 rounded-full border transition flex items-center gap-1 <?= $isActive ? 'text-white border-transparent' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300' ?>"
                                            <?= $isActive ? 'style="background:' . $stDef['color'] . ';"' : '' ?>
                                            data-st="<?= $stVal ?>">
                                            <i class="fa-solid <?= $stDef['icon'] ?> text-[8px]"></i>
                                            <span><?= $stDef['label'] ?></span>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <a href="/hinata/meetgreet_report.php?event_id=<?= (int)$e['id'] ?>" class="text-[10px] font-bold text-white px-4 py-2 rounded-full transition inline-flex items-center gap-2 hover:opacity-90" style="background: var(--hinata-theme);">
                                        <i class="fa-solid fa-pen-to-square"></i>レポを書く
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($e['event_info'])): ?>
                                    <div class="text-xs text-slate-600 bg-white p-4 rounded-xl border border-slate-100 shadow-sm whitespace-pre-wrap"><?= htmlspecialchars($e['event_info']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($e['event_url'])): ?>
                                    <a href="<?= htmlspecialchars($e['event_url']) ?>" target="_blank" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-4 py-2 rounded-full border border-sky-100 hover:bg-sky-100 transition inline-flex items-center gap-2"><i class="fa-solid fa-arrow-up-right-from-square"></i> 特設サイトを開く</a>
                                <?php endif; ?>
                                <?php if (!empty($e['video_key'])): ?>
                                    <div class="aspect-video w-full max-w-2xl mx-auto rounded-lg overflow-hidden bg-black shadow-lg ring-4 ring-white">
                                        <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?= htmlspecialchars($e['video_key']) ?>?rel=0" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>

        <!-- ========== VIEW 2: Timeline ========== -->
        <div id="view-timeline" class="flex-1 flex flex-col min-h-0 hidden">
            <div class="flex-1 overflow-y-auto custom-scroll">
                <div id="timelineContainer" class="relative max-w-3xl mx-auto p-6 md:p-10"></div>
            </div>
        </div>

        <!-- ========== VIEW 3: Dashboard ========== -->
        <div id="view-dashboard" class="flex-1 flex flex-col min-h-0 hidden">
            <div class="flex-1 overflow-y-auto custom-scroll">
                <div id="dashboardContainer" class="max-w-5xl mx-auto p-4 md:p-8 space-y-6"></div>
            </div>
        </div>

        <!-- ========== VIEW 4: Master-Detail ========== -->
        <div id="view-master" class="flex-1 flex min-h-0 hidden">
            <div id="mdListPane" class="md-list custom-scroll bg-white"></div>
            <div id="mdDetailPane" class="md-detail custom-scroll bg-slate-50 hidden lg:block">
                <div id="mdDetailContent" class="p-6 md:p-10"></div>
            </div>
        </div>
        <div id="mdMobileOverlay" class="hidden" onclick="closeMdDetail()">
            <div class="md-detail-overlay"></div>
            <div id="mdMobilePanel" class="md-detail-panel custom-scroll" onclick="event.stopPropagation()">
                <div id="mdMobileContent" class="p-5"></div>
            </div>
        </div>
    </main>

    <!-- Slide Panel (for Timeline/Dashboard detail) -->
    <div id="slidePanelOverlay" class="slide-panel-overlay" onclick="closeSlidePanel()"></div>
    <div id="slidePanel" class="slide-panel custom-scroll">
        <div class="sticky top-0 bg-white border-b border-slate-100 px-5 py-3 flex items-center justify-between z-10">
            <span class="text-[10px] font-black text-slate-400 tracking-wider">イベント詳細</span>
            <button onclick="closeSlidePanel()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div id="slidePanelContent" class="p-5 space-y-4"></div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = function() { document.getElementById('sidebar').classList.add('mobile-open'); };

        var catColors = { 1: '#3b82f6', 2: '#10b981', 3: '#f59e0b', 4: '#8b5cf6', 5: '#ec4899', 6: '#f97316', 99: '#64748b' };
        var catNames = { 1: 'ライブ', 2: 'ミーグリ', 3: 'リアルミーグリ', 4: 'リリース', 5: 'メディア', 6: 'スペイベ', 99: 'その他' };
        var catIcons = { 1: 'fa-music', 2: 'fa-handshake', 3: 'fa-users', 4: 'fa-compact-disc', 5: 'fa-tv', 6: 'fa-star', 99: 'fa-calendar' };
        var dowNames = ['日','月','火','水','木','金','土'];
        var rawEvents = <?= json_encode($events, JSON_UNESCAPED_UNICODE) ?>;
        var eventSlotsData = <?= json_encode($eventSlots ?? [], JSON_UNESCAPED_UNICODE) ?>;
        var ticketUsedSums = <?= json_encode($ticketUsedSums ?? [], JSON_UNESCAPED_UNICODE) ?>;
        var attendedIds = <?= json_encode($attendedEventIds ?? [], JSON_UNESCAPED_UNICODE) ?>;
        var currentViewMode = 'calendar';
        var activeFilter = 0;

        function _esc(str) { if (!str) return ''; var d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
        function _dateInfo(dateStr) {
            var d = new Date(dateStr + 'T00:00:00');
            return { y: d.getFullYear(), m: d.getMonth() + 1, day: d.getDate(), dow: dowNames[d.getDay()], full: d.getFullYear() + '/' + (d.getMonth()+1) + '/' + d.getDate() };
        }
        function _todayStr() { var t = new Date(); return t.getFullYear() + '-' + String(t.getMonth()+1).padStart(2,'0') + '-' + String(t.getDate()).padStart(2,'0'); }

        // ---- Event Status (MG participation) ----
        var esStatusDefs = [
            { val: 1, label: '参加予定', color: '#10b981', icon: 'fa-circle-check' },
            { val: 4, label: '当選',     color: '#0ea5e9', icon: 'fa-trophy' },
            { val: 5, label: '落選',     color: '#f43f5e', icon: 'fa-circle-xmark' },
            { val: 3, label: '検討中',   color: '#f59e0b', icon: 'fa-clock' },
            { val: 2, label: '不参加',   color: '#94a3b8', icon: 'fa-ban' }
        ];
        var esStatusMap = {};
        esStatusDefs.forEach(function(d) { esStatusMap[d.val] = d; });

        function renderStatusChipsHtml(e) {
            var myStatus = parseInt(e.my_status) || 0;
            var h = '<div class="flex flex-wrap items-center gap-1.5" id="status-chips-slide-' + e.id + '">';
            h += '<span class="text-[9px] font-black text-slate-400 tracking-wider mr-1">参戦予定</span>';
            for (var i = 0; i < esStatusDefs.length; i++) {
                var sd = esStatusDefs[i];
                var isActive = myStatus === sd.val;
                h += '<button onclick="toggleEventStatus(' + e.id + ',' + sd.val + ',this)" class="es-chip text-[10px] font-bold px-2.5 py-1 rounded-full border transition flex items-center gap-1 ';
                h += isActive ? 'text-white border-transparent' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300';
                h += '"' + (isActive ? ' style="background:' + sd.color + ';"' : '') + ' data-st="' + sd.val + '">';
                h += '<i class="fa-solid ' + sd.icon + ' text-[8px]"></i><span>' + sd.label + '</span></button>';
            }
            h += '</div>';
            return h;
        }

        function toggleEventStatus(eventId, status, btn) {
            var evtObj = null;
            for (var i = 0; i < rawEvents.length; i++) { if (rawEvents[i].id == eventId) { evtObj = rawEvents[i]; break; } }
            var currentStatus = evtObj ? (parseInt(evtObj.my_status) || 0) : 0;
            var newStatus = (currentStatus === status) ? 0 : status;

            App.post('/hinata/api/save_event_status.php', { event_id: eventId, status: newStatus }, function(res) {
                if (res.status !== 'success') { App.toast(res.message || 'エラー', 'error'); return; }
                if (evtObj) evtObj.my_status = newStatus;

                document.querySelectorAll('#status-chips-' + eventId + ' .es-chip, #status-chips-slide-' + eventId + ' .es-chip').forEach(function(chip) {
                    var chipSt = parseInt(chip.dataset.st);
                    if (chipSt === newStatus) {
                        chip.className = 'es-chip text-[10px] font-bold px-2.5 py-1 rounded-full border transition flex items-center gap-1 text-white border-transparent';
                        chip.style.background = esStatusMap[chipSt].color;
                    } else {
                        chip.className = 'es-chip text-[10px] font-bold px-2.5 py-1 rounded-full border transition flex items-center gap-1 bg-white text-slate-500 border-slate-200 hover:border-slate-300';
                        chip.style.background = '';
                    }
                });

                _updateStatusBadge(eventId, newStatus);
                var sd = newStatus ? esStatusMap[newStatus] : null;
                App.toast(sd ? sd.label + ' に設定しました' : 'ステータスを解除しました', 'success');
            });
        }

        function _updateStatusBadge(eventId, newStatus) {
            var badge = document.getElementById('status-badge-' + eventId);
            if (newStatus && esStatusMap[newStatus]) {
                var sd = esStatusMap[newStatus];
                if (!badge) {
                    var card = document.getElementById('event-card-' + eventId);
                    if (card) {
                        var catSpan = card.querySelector('.tracking-wider');
                        if (catSpan) {
                            badge = document.createElement('span');
                            badge.id = 'status-badge-' + eventId;
                            badge.className = 'text-[8px] font-bold text-white px-1.5 py-0.5 rounded-full leading-none';
                            catSpan.insertAdjacentElement('afterend', badge);
                        }
                    }
                }
                if (badge) {
                    badge.textContent = sd.label;
                    badge.style.background = sd.color;
                    badge.style.display = '';
                }
            } else if (badge) {
                badge.style.display = 'none';
            }
        }

        // ---- View Mode Switcher ----
        function switchViewMode(mode) {
            currentViewMode = mode;
            ['calendar','timeline','dashboard','master'].forEach(function(v) {
                var el = document.getElementById('view-' + v);
                if (!el) return;
                if (v === mode) { el.classList.remove('hidden'); } else { el.classList.add('hidden'); }
            });
            if (mode === 'calendar') MiniCal.render();
            document.querySelectorAll('[data-vm]').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.vm === mode);
            });
            if (mode === 'timeline') TimelineView.render();
            if (mode === 'dashboard') DashboardView.render();
            if (mode === 'master') MasterDetailView.render();
        }

        // ---- Shared Detail Renderer ----
        function renderDetailHtml(eventId) {
            var e = null;
            for (var i = 0; i < rawEvents.length; i++) { if (rawEvents[i].id == eventId) { e = rawEvents[i]; break; } }
            if (!e) return '<p class="text-xs text-slate-400">イベントが見つかりません</p>';
            var color = catColors[e.category] || '#64748b';
            var catName = catNames[e.category] || 'その他';
            var icon = catIcons[e.category] || 'fa-calendar';
            var di = _dateInfo(e.event_date.substring(0, 10));
            var today = _todayStr();
            var isPast = e.event_date.substring(0, 10) < today;

            var h = '';
            h += '<div class="flex items-center gap-3 mb-4">';
            h += '<div class="w-10 h-10 rounded-lg flex items-center justify-center text-white shadow-md" style="background:' + color + '"><i class="fa-solid ' + icon + '"></i></div>';
            h += '<div class="flex-1 min-w-0">';
            h += '<div class="flex items-center gap-2 mb-0.5"><span class="text-[9px] font-black text-slate-400 tracking-wider">' + _esc(catName) + '</span></div>';
            h += '<h2 class="font-black text-slate-800 text-base">' + _esc(e.event_name) + '</h2>';
            h += '</div></div>';
            h += '<div class="flex items-center gap-3 text-xs text-slate-500 mb-4">';
            h += '<span><i class="fa-solid fa-calendar mr-1" style="color:' + color + '"></i>' + di.full + '（' + di.dow + '）</span>';
            if (e.event_place) h += '<span><i class="fa-solid fa-location-dot mr-1 text-sky-300"></i>' + _esc(e.event_place) + '</span>';
            h += '</div>';

            if (parseInt(e.category) === 1) {
                var att = attendedIds.indexOf(parseInt(e.id)) !== -1;
                h += '<div class="flex items-center gap-2 mb-3">';
                h += '<button onclick="toggleAttendanceSlide(' + e.id + ', this)" class="attendance-btn text-[10px] font-bold px-3 py-1.5 rounded-full border transition flex items-center gap-1 ' + (att ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-slate-500 border-slate-200 hover:border-sky-300') + '" data-attended="' + (att ? '1' : '0') + '">';
                h += '<i class="fa-solid fa-flag text-[8px]"></i><span>' + (att ? '参戦済み' : '参戦した') + '</span></button>';
                h += '<button onclick="loadSetlistSlide(' + e.id + ')" class="text-[10px] font-bold text-slate-400 hover:text-slate-600 px-3 py-1.5 rounded-full border border-slate-200 hover:border-slate-300 transition flex items-center gap-1"><i class="fa-solid fa-list-ol text-[8px]"></i>セットリスト</button>';
                h += '</div>';
                h += '<div id="setlist-slide-' + e.id + '" class="hidden"></div>';
            }

            var slots = eventSlotsData[e.id];
            if (slots && slots.length > 0) {
                h += '<div class="bg-white rounded-xl border border-emerald-100 shadow-sm overflow-hidden">';
                h += '<div class="px-4 py-2 bg-emerald-50 flex items-center gap-2"><i class="fa-solid fa-ticket text-emerald-500 text-xs"></i><span class="text-[10px] font-bold text-emerald-700 tracking-wider">自分のミーグリ予定</span></div>';
                h += '<div class="divide-y divide-slate-50">';
                for (var s = 0; s < slots.length; s++) {
                    var sl = slots[s];
                    h += '<div class="px-4 py-2 flex items-center gap-4 text-xs">';
                    h += '<div class="w-20 shrink-0"><span class="font-bold text-slate-700">' + _esc(sl.slot_name) + '</span></div>';
                    h += '<div class="flex-1 min-w-0"><span class="font-bold" style="color:' + (sl.color1 || '#94a3b8') + '">' + _esc(sl.member_name || sl.member_name_raw || '不明') + '</span></div>';
                    var _tc = (ticketUsedSums[sl.id] !== undefined) ? parseInt(ticketUsedSums[sl.id]) : (parseInt(sl.ticket_count) || 0);
                    h += '<div class="shrink-0 font-bold text-slate-600">' + _tc + '<span class="text-[10px] text-slate-400">枚</span></div>';
                    h += '</div>';
                }
                h += '</div></div>';
            }

            if (parseInt(e.category) === 2 || parseInt(e.category) === 3) {
                h += renderStatusChipsHtml(e);
                h += '<a href="/hinata/meetgreet_report.php?event_id=' + e.id + '" class="text-[10px] font-bold text-white px-4 py-2 rounded-full transition inline-flex items-center gap-2 hover:opacity-90" style="background:var(--hinata-theme)"><i class="fa-solid fa-pen-to-square"></i>レポを書く</a>';
            }

            if (e.event_info) h += '<div class="text-xs text-slate-600 bg-white p-4 rounded-xl border border-slate-100 shadow-sm whitespace-pre-wrap">' + _esc(e.event_info) + '</div>';
            if (e.event_url) h += '<a href="' + _esc(e.event_url) + '" target="_blank" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-4 py-2 rounded-full border border-sky-100 hover:bg-sky-100 transition inline-flex items-center gap-2"><i class="fa-solid fa-arrow-up-right-from-square"></i> 特設サイトを開く</a>';
            if (e.video_key) h += '<div class="aspect-video w-full rounded-lg overflow-hidden bg-black shadow-lg"><iframe width="100%" height="100%" src="https://www.youtube.com/embed/' + _esc(e.video_key) + '?rel=0" frameborder="0" allowfullscreen></iframe></div>';
            return h;
        }

        function toggleAttendanceSlide(eventId, btn) {
            App.post('/hinata/api/toggle_attendance.php', { event_id: eventId }, function(res) {
                if (res.status === 'success') {
                    var attended = res.attended;
                    btn.dataset.attended = attended ? '1' : '0';
                    btn.className = 'attendance-btn text-[10px] font-bold px-3 py-1.5 rounded-full border transition flex items-center gap-1 ' + (attended ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-slate-500 border-slate-200 hover:border-sky-300');
                    btn.querySelector('span').textContent = attended ? '参戦済み' : '参戦した';
                    var idx = attendedIds.indexOf(parseInt(eventId));
                    if (attended && idx === -1) attendedIds.push(parseInt(eventId));
                    if (!attended && idx !== -1) attendedIds.splice(idx, 1);
                    App.toast(res.message, 'success');
                }
            });
        }

        function loadSetlistSlide(eventId) {
            var container = document.getElementById('setlist-slide-' + eventId);
            if (!container) return;
            if (!container.classList.contains('hidden')) { container.classList.add('hidden'); return; }
            container.innerHTML = '<p class="text-xs text-slate-400 py-2"><i class="fa-solid fa-spinner fa-spin mr-1"></i>読み込み中...</p>';
            container.classList.remove('hidden');
            fetch('/hinata/api/get_event_setlist.php?event_id=' + eventId)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.status !== 'success' || !res.data.setlist.length) {
                        container.innerHTML = '<p class="text-xs text-slate-400 py-2">セットリストは未登録です</p>';
                        return;
                    }
                    var html = '<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">';
                    html += '<div class="px-4 py-2 bg-indigo-50 flex items-center gap-2"><i class="fa-solid fa-list-ol text-indigo-500 text-xs"></i><span class="text-[10px] font-bold text-indigo-700 tracking-wider">セットリスト</span><span class="text-[10px] text-indigo-400 ml-auto">' + res.data.setlist.length + '曲</span></div>';
                    html += '<ol class="divide-y divide-slate-50">';
                    var lastEncore = false;
                    res.data.setlist.forEach(function(item, i) {
                        if (item.encore && !lastEncore) html += '<li class="px-4 py-1.5 bg-slate-50 text-center"><span class="text-[9px] font-black text-slate-400 tracking-wider">ENCORE</span></li>';
                        lastEncore = !!item.encore;
                        html += '<li class="px-4 py-2 flex items-center gap-3 text-xs"><span class="text-slate-400 w-5 text-right font-mono">' + (i+1) + '</span><a href="/hinata/song.php?id=' + item.song_id + '" class="flex-1 font-bold text-slate-800 hover:text-sky-600 transition truncate">' + _esc(item.song_title) + '</a><span class="text-[10px] text-slate-400 shrink-0">' + _esc(item.release_title) + '</span></li>';
                    });
                    html += '</ol></div>';
                    container.innerHTML = html;
                });
        }

        // ---- Slide Panel ----
        function openSlidePanel(eventId) {
            document.getElementById('slidePanelContent').innerHTML = renderDetailHtml(eventId);
            document.getElementById('slidePanelOverlay').classList.add('open');
            document.getElementById('slidePanel').classList.add('open');
        }
        function closeSlidePanel() {
            document.getElementById('slidePanelOverlay').classList.remove('open');
            document.getElementById('slidePanel').classList.remove('open');
        }

        // ---- Filtered events helper ----
        function getFilteredEvents() {
            if (activeFilter === 0) return rawEvents;
            return rawEvents.filter(function(e) { return parseInt(e.category) === activeFilter; });
        }

        // ===== VIEW 2: Timeline =====
        var TimelineView = {
            render: function() {
                var container = document.getElementById('timelineContainer');
                if (!container) return;
                var events = getFilteredEvents();
                var today = _todayStr();
                var html = '<div class="tl-line"></div>';
                var todayInserted = false;
                var lastMonth = '';
                var side = 0;

                for (var i = 0; i < events.length; i++) {
                    var e = events[i];
                    var dateStr = e.event_date.substring(0, 10);
                    var isPast = dateStr < today;
                    var isToday = dateStr === today;
                    var color = catColors[e.category] || '#64748b';
                    var icon = catIcons[e.category] || 'fa-calendar';
                    var di = _dateInfo(dateStr);
                    var month = dateStr.substring(0, 7);

                    if (month !== lastMonth) {
                        lastMonth = month;
                        html += '<div class="text-center py-3 relative z-[3]"><span class="bg-slate-200 text-slate-500 text-[10px] font-black tracking-wider px-3 py-1 rounded-full">' + di.y + '年 ' + di.m + '月</span></div>';
                    }

                    if (!todayInserted && !isPast && !isToday) {
                        todayInserted = true;
                        html += '<div class="tl-today-marker"><span class="bg-white border-2 px-3 py-1 rounded-full text-[10px] font-black tracking-widest shrink-0" style="border-color:var(--hinata-theme);color:var(--hinata-theme)">TODAY</span></div>';
                    }
                    if (isToday && !todayInserted) todayInserted = true;

                    var cls = side % 2 === 0 ? 'tl-left' : 'tl-right';
                    html += '<div class="tl-item ' + cls + '" onclick="openSlidePanel(' + e.id + ')">';
                    html += '<div class="tl-node" style="background:' + color + ';box-shadow:0 0 0 2px ' + color + '33;"></div>';
                    html += '<div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 hover:shadow-md transition cursor-pointer' + (isPast ? ' opacity-50' : '') + '">';
                    html += '<div class="flex items-center gap-2 mb-1.5"><i class="fa-solid ' + icon + ' text-[10px]" style="color:' + color + '"></i><span class="text-[9px] font-black text-slate-400 tracking-wider">' + _esc(catNames[e.category] || 'その他') + '</span></div>';
                    html += '<h3 class="font-black text-slate-800 text-sm mb-1">' + _esc(e.event_name) + '</h3>';
                    html += '<div class="text-[10px] text-slate-400"><i class="fa-regular fa-calendar mr-1"></i>' + di.full + '（' + di.dow + '）</div>';
                    if (e.event_place) html += '<div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-solid fa-location-dot mr-1 text-sky-300"></i>' + _esc(e.event_place) + '</div>';
                    html += '</div></div>';
                    side++;
                }
                container.innerHTML = html;
            }
        };

        // ===== VIEW 3: Dashboard =====
        var DashboardView = {
            render: function() {
                var container = document.getElementById('dashboardContainer');
                if (!container) return;
                var today = _todayStr();
                var events = getFilteredEvents();
                var upcoming = [], past = [], thisMonth = [], nextMonth = [];
                var nowM = new Date().getMonth(), nowY = new Date().getFullYear();
                var nm = nowM === 11 ? 0 : nowM + 1;
                var ny = nowM === 11 ? nowY + 1 : nowY;

                for (var i = 0; i < events.length; i++) {
                    var ds = events[i].event_date.substring(0, 10);
                    if (ds < today) past.push(events[i]); else upcoming.push(events[i]);
                    var d = new Date(ds + 'T00:00:00');
                    if (d.getMonth() === nowM && d.getFullYear() === nowY) thisMonth.push(events[i]);
                    if (d.getMonth() === nm && d.getFullYear() === ny) nextMonth.push(events[i]);
                }

                var nextEvt = upcoming.length > 0 ? upcoming[0] : null;
                var html = '';

                // Hero
                if (nextEvt) {
                    var nc = catColors[nextEvt.category] || '#64748b';
                    var ni = catIcons[nextEvt.category] || 'fa-calendar';
                    var nd = _dateInfo(nextEvt.event_date.substring(0, 10));
                    var daysLeft = Math.max(0, Math.ceil((new Date(nextEvt.event_date.substring(0,10) + 'T00:00:00') - new Date(today + 'T00:00:00')) / 86400000));
                    html += '<div class="rounded-2xl overflow-hidden shadow-lg cursor-pointer" onclick="openSlidePanel(' + nextEvt.id + ')" style="background:linear-gradient(135deg, ' + nc + ', ' + nc + 'cc)">';
                    html += '<div class="px-6 py-6 md:px-8 md:py-8 flex items-center gap-5">';
                    html += '<div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center shrink-0"><i class="fa-solid ' + ni + ' text-white text-2xl"></i></div>';
                    html += '<div class="flex-1 min-w-0"><div class="text-[10px] font-bold text-white/70 tracking-wider mb-1">NEXT EVENT</div>';
                    html += '<h2 class="font-black text-white text-lg md:text-xl truncate">' + _esc(nextEvt.event_name) + '</h2>';
                    html += '<div class="flex items-center gap-3 mt-1.5"><span class="text-xs text-white/80">' + nd.full + '（' + nd.dow + '）</span>';
                    if (nextEvt.event_place) html += '<span class="text-xs text-white/60"><i class="fa-solid fa-location-dot mr-0.5"></i>' + _esc(nextEvt.event_place) + '</span>';
                    html += '</div></div>';
                    html += '<div class="shrink-0 text-center bg-white/20 rounded-2xl px-4 py-3">';
                    html += daysLeft === 0 ? '<div class="text-2xl font-black text-white">TODAY</div>' : '<div class="text-3xl font-black text-white leading-none">' + daysLeft + '</div><div class="text-[10px] font-bold text-white/70 mt-0.5">日後</div>';
                    html += '</div></div></div>';
                }

                // Stats
                html += '<div class="flex gap-3 overflow-x-auto pb-1" style="scrollbar-width:none">';
                html += '<div class="stat-card border border-slate-100 shadow-sm"><div class="text-2xl font-black text-slate-700">' + events.length + '</div><div class="text-[10px] font-bold text-slate-400">全イベント</div></div>';
                html += '<div class="stat-card border border-slate-100 shadow-sm"><div class="text-2xl font-black text-sky-500">' + upcoming.length + '</div><div class="text-[10px] font-bold text-slate-400">予定</div></div>';
                html += '<div class="stat-card border border-slate-100 shadow-sm"><div class="text-2xl font-black text-slate-400">' + past.length + '</div><div class="text-[10px] font-bold text-slate-400">終了</div></div>';
                var attCount = 0;
                for (var a = 0; a < events.length; a++) { if (attendedIds.indexOf(parseInt(events[a].id)) !== -1) attCount++; }
                html += '<div class="stat-card border border-slate-100 shadow-sm"><div class="text-2xl font-black text-emerald-500">' + attCount + '</div><div class="text-[10px] font-bold text-slate-400">参戦</div></div>';
                html += '</div>';

                // This month
                html += this.renderMonthSection('今月', thisMonth, today);
                html += this.renderMonthSection('来月', nextMonth, today);

                // All upcoming
                if (upcoming.length > 0) {
                    html += '<div><h3 class="text-xs font-black text-slate-400 tracking-wider mb-3"><i class="fa-solid fa-arrow-right mr-1.5"></i>今後のイベント</h3>';
                    html += '<div class="space-y-2">';
                    for (var u = 0; u < Math.min(upcoming.length, 20); u++) {
                        html += this.renderListItem(upcoming[u], today);
                    }
                    html += '</div></div>';
                }
                container.innerHTML = html;
            },

            renderMonthSection: function(label, events, today) {
                if (events.length === 0) return '';
                var html = '<div><h3 class="text-xs font-black text-slate-400 tracking-wider mb-3"><i class="fa-solid fa-calendar-week mr-1.5"></i>' + label + ' <span class="text-slate-300">(' + events.length + ')</span></h3>';
                html += '<div class="dash-scroll">';
                for (var i = 0; i < events.length; i++) {
                    var e = events[i];
                    var color = catColors[e.category] || '#64748b';
                    var icon = catIcons[e.category] || 'fa-calendar';
                    var di = _dateInfo(e.event_date.substring(0, 10));
                    var isPast = e.event_date.substring(0, 10) < today;
                    html += '<div class="dash-card bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden' + (isPast ? ' opacity-50' : '') + '" onclick="openSlidePanel(' + e.id + ')">';
                    html += '<div class="h-1.5" style="background:' + color + '"></div>';
                    html += '<div class="p-4"><div class="flex items-center gap-2 mb-2"><i class="fa-solid ' + icon + ' text-[10px]" style="color:' + color + '"></i><span class="text-[9px] font-black text-slate-400 tracking-wider">' + _esc(catNames[e.category] || '') + '</span></div>';
                    html += '<h4 class="font-black text-slate-800 text-sm truncate mb-1">' + _esc(e.event_name) + '</h4>';
                    html += '<div class="text-[10px] text-slate-400">' + di.m + '/' + di.day + '（' + di.dow + '）</div>';
                    if (e.event_place) html += '<div class="text-[10px] text-slate-400 mt-0.5 truncate"><i class="fa-solid fa-location-dot mr-0.5 text-sky-300"></i>' + _esc(e.event_place) + '</div>';
                    html += '</div></div>';
                }
                html += '</div></div>';
                return html;
            },

            renderListItem: function(e, today) {
                var color = catColors[e.category] || '#64748b';
                var icon = catIcons[e.category] || 'fa-calendar';
                var di = _dateInfo(e.event_date.substring(0, 10));
                var isPast = e.event_date.substring(0, 10) < today;
                var h = '<div class="flex items-center gap-3 bg-white rounded-lg border border-slate-100 shadow-sm p-3 cursor-pointer hover:shadow-md transition' + (isPast ? ' opacity-50' : '') + '" onclick="openSlidePanel(' + e.id + ')">';
                h += '<div class="w-1.5 h-10 rounded-full shrink-0" style="background:' + color + '"></div>';
                h += '<div class="w-12 text-center shrink-0"><div class="text-xs font-black text-slate-700">' + di.m + '/' + di.day + '</div><div class="text-[10px] text-slate-400">' + di.dow + '</div></div>';
                h += '<div class="flex-1 min-w-0"><h4 class="font-bold text-slate-800 text-xs truncate">' + _esc(e.event_name) + '</h4>';
                if (e.event_place) h += '<div class="text-[10px] text-slate-400 truncate">' + _esc(e.event_place) + '</div>';
                h += '</div>';
                h += '<i class="fa-solid ' + icon + ' text-[10px] shrink-0" style="color:' + color + '"></i>';
                h += '</div>';
                return h;
            }
        };

        // ===== VIEW 4: Master-Detail =====
        var MasterDetailView = {
            selectedId: null,

            render: function() {
                this.renderList();
                this.selectedId = null;
                var content = document.getElementById('mdDetailContent');
                if (content) content.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-slate-300 py-20"><i class="fa-solid fa-hand-pointer text-4xl mb-3"></i><p class="text-sm font-bold">イベントを選択してください</p></div>';
            },

            renderList: function() {
                var pane = document.getElementById('mdListPane');
                if (!pane) return;
                var events = getFilteredEvents();
                var today = _todayStr();
                var html = '';

                // Filter chips
                html += '<div class="sticky top-0 z-10 bg-white border-b border-slate-100 px-3 py-2"><div class="flex gap-1 overflow-x-auto" style="scrollbar-width:none">';
                var cats = [{id:0,name:'ALL',color:'var(--hinata-theme)'}];
                for (var c in catNames) { cats.push({id: parseInt(c), name: catNames[c], color: catColors[c]}); }
                for (var f = 0; f < cats.length; f++) {
                    var isAct = cats[f].id === activeFilter;
                    html += '<button onclick="filterCategoryMd(' + cats[f].id + ')" class="shrink-0 text-[9px] font-bold px-2 py-0.5 rounded-full border transition ' + (isAct ? 'text-white border-transparent' : 'text-slate-500 border-slate-200') + '"' + (isAct ? ' style="background:' + cats[f].color + '"' : '') + '>' + _esc(cats[f].name) + '</button>';
                }
                html += '</div></div>';

                var lastMonth = '';
                for (var i = 0; i < events.length; i++) {
                    var e = events[i];
                    var dateStr = e.event_date.substring(0, 10);
                    var isPast = dateStr < today;
                    var isToday = dateStr === today;
                    var color = catColors[e.category] || '#64748b';
                    var icon = catIcons[e.category] || 'fa-calendar';
                    var di = _dateInfo(dateStr);
                    var month = dateStr.substring(0, 7);
                    if (month !== lastMonth) {
                        lastMonth = month;
                        html += '<div class="px-3 py-1.5 bg-slate-50 border-b border-slate-100"><span class="text-[10px] font-black text-slate-400 tracking-wider">' + di.y + '年 ' + di.m + '月</span></div>';
                    }
                    html += '<div class="md-list-item px-3 py-3 border-b border-slate-50 flex items-center gap-3' + (isPast ? ' opacity-50' : '') + (this.selectedId == e.id ? ' active' : '') + '" data-md-id="' + e.id + '" onclick="MasterDetailView.select(' + e.id + ')">';
                    html += '<div class="w-1 h-8 rounded-full shrink-0" style="background:' + color + '"></div>';
                    html += '<div class="w-10 text-center shrink-0">';
                    html += '<div class="text-xs font-black ' + (isToday ? 'text-sky-600' : 'text-slate-700') + '">' + di.m + '/' + di.day + '</div>';
                    html += '<div class="text-[9px] ' + (isToday ? 'text-sky-400' : 'text-slate-400') + '">' + di.dow + '</div>';
                    html += '</div>';
                    html += '<div class="flex-1 min-w-0"><h4 class="font-bold text-slate-800 text-xs truncate">' + _esc(e.event_name) + '</h4>';
                    html += '<div class="text-[9px] text-slate-400 flex items-center gap-1"><i class="fa-solid ' + icon + ' text-[7px]" style="color:' + color + '"></i>' + _esc(catNames[e.category] || '') + '</div>';
                    html += '</div></div>';
                }
                pane.innerHTML = html;
            },

            select: function(eventId) {
                this.selectedId = eventId;
                document.querySelectorAll('[data-md-id]').forEach(function(el) {
                    el.classList.toggle('active', el.dataset.mdId == eventId);
                });
                var html = renderDetailHtml(eventId);

                if (window.innerWidth >= 1024) {
                    document.getElementById('mdDetailContent').innerHTML = html;
                    document.getElementById('mdDetailPane').classList.remove('hidden');
                } else {
                    document.getElementById('mdMobileContent').innerHTML = html;
                    document.getElementById('mdMobileOverlay').classList.remove('hidden');
                    setTimeout(function() { document.getElementById('mdMobilePanel').classList.add('open'); }, 10);
                }
            }
        };

        function closeMdDetail() {
            document.getElementById('mdMobilePanel').classList.remove('open');
            setTimeout(function() { document.getElementById('mdMobileOverlay').classList.add('hidden'); }, 300);
        }

        function filterCategoryMd(catId) {
            activeFilter = catId;
            MasterDetailView.renderList();
        }

        // ===== Mini Calendar (View 1) =====
        var MiniCal = {
            currentYear: 0, currentMonth: 0, selectedDate: null, eventDots: {}, allEventDots: {},
            mobileCollapsed: true,

            buildDots: function(events) {
                var dots = {};
                for (var i = 0; i < events.length; i++) {
                    var d = events[i].event_date; if (!d) continue;
                    var key = d.substring(0, 10);
                    var color = catColors[events[i].category] || '#64748b';
                    if (!dots[key]) dots[key] = [];
                    if (dots[key].length < 3 && dots[key].indexOf(color) === -1) dots[key].push(color);
                }
                return dots;
            },
            rebuildDots: function(catId) {
                this.eventDots = catId === 0 ? this.allEventDots : this.buildDots(rawEvents.filter(function(e) { return parseInt(e.category) === catId; }));
            },

            dowHeaderHtml: function(fontSize) {
                var days = ['日','月','火','水','木','金','土'];
                var h = '<div class="grid grid-cols-7 text-center mb-0.5">';
                for (var i = 0; i < 7; i++) {
                    var c = i === 0 ? 'text-red-400' : i === 6 ? 'text-blue-400' : 'text-slate-400';
                    h += '<span class="' + fontSize + ' font-bold ' + c + '">' + days[i] + '</span>';
                }
                return h + '</div>';
            },

            renderMonthGrid: function(y, m) {
                var firstDay = new Date(y, m, 1).getDay(), daysInMonth = new Date(y, m + 1, 0).getDate(), daysInPrev = new Date(y, m, 0).getDate();
                var todayStr = _todayStr();
                var html = '', totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
                for (var i = 0; i < totalCells; i++) {
                    var dayNum, dateStr, isOther = false;
                    if (i < firstDay) { dayNum = daysInPrev - firstDay + 1 + i; var pm = m === 0 ? 11 : m - 1, py = m === 0 ? y - 1 : y; dateStr = py + '-' + String(pm + 1).padStart(2, '0') + '-' + String(dayNum).padStart(2, '0'); isOther = true; }
                    else if (i - firstDay >= daysInMonth) { dayNum = i - firstDay - daysInMonth + 1; var nm2 = m === 11 ? 0 : m + 1, ny2 = m === 11 ? y + 1 : y; dateStr = ny2 + '-' + String(nm2 + 1).padStart(2, '0') + '-' + String(dayNum).padStart(2, '0'); isOther = true; }
                    else { dayNum = i - firstDay + 1; dateStr = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(dayNum).padStart(2, '0'); }
                    var cls = 'cal-day'; if (isOther) cls += ' other-month'; if (dateStr === todayStr) cls += ' today'; if (dateStr === this.selectedDate) cls += ' selected';
                    var dow = i % 7, numCls = dow === 0 && !isOther ? ' text-red-500' : dow === 6 && !isOther ? ' text-blue-500' : ' text-slate-700';
                    var dots = this.eventDots[dateStr], dotsHtml = '<div class="flex justify-center gap-0.5 h-1.5 mt-0.5">';
                    if (dots) { for (var dd = 0; dd < dots.length; dd++) dotsHtml += '<span class="w-1.5 h-1.5 rounded-full" style="background:' + dots[dd] + ';"></span>'; }
                    dotsHtml += '</div>';
                    html += '<div class="' + cls + '" data-cal-date="' + dateStr + '" onclick="MiniCal.onDayClick(\'' + dateStr + '\')"><span class="day-num' + numCls + '">' + dayNum + '</span>' + dotsHtml + '</div>';
                }
                return html;
            },

            renderMobile: function() {
                var y = this.currentYear, m = this.currentMonth;
                var titleEl = document.getElementById('calTitle');
                if (titleEl) titleEl.textContent = y + '年 ' + (m + 1) + '月';
                var dowEl = document.getElementById('calDowHeader');
                if (dowEl && !dowEl.hasChildNodes()) {
                    var days = ['日','月','火','水','木','金','土'];
                    var dh = '';
                    for (var i = 0; i < 7; i++) {
                        var c = i === 0 ? 'text-red-400' : i === 6 ? 'text-blue-400' : 'text-slate-400';
                        dh += '<span class="text-[10px] font-bold ' + c + '">' + days[i] + '</span>';
                    }
                    dowEl.innerHTML = dh;
                }
                var grid = document.getElementById('calMobileGrid');
                if (grid) grid.innerHTML = this.renderMonthGrid(y, m);
                this.updateMobileCollapse();
            },

            renderDesktop: function() {
                var container = document.getElementById('calSideContent');
                if (!container || container.offsetParent === null) return;
                var html = '';
                for (var offset = 0; offset < 3; offset++) {
                    var d = new Date(this.currentYear, this.currentMonth + offset, 1);
                    var sy = d.getFullYear(), sm = d.getMonth();
                    html += '<div class="mb-3">';
                    html += '<div class="text-sm font-black text-slate-600 text-center mb-2 tracking-wider">' + sy + '年 ' + (sm + 1) + '月</div>';
                    html += this.dowHeaderHtml('text-xs');
                    html += '<div class="grid grid-cols-7" id="calSideGrid' + offset + '">' + this.renderMonthGrid(sy, sm) + '</div>';
                    html += '</div>';
                }
                container.innerHTML = html;
            },

            updateMobileCollapse: function() {
                var wrap = document.getElementById('calMobileWrap');
                var grid = document.getElementById('calMobileGrid');
                if (!wrap || !grid) return;
                if (this.mobileCollapsed) {
                    var todayCell = grid.querySelector('.cal-day.today');
                    var rowIdx = 0;
                    if (todayCell) {
                        var cells = Array.from(grid.children);
                        rowIdx = Math.floor(cells.indexOf(todayCell) / 7);
                    }
                    wrap.style.maxHeight = '34px';
                    grid.style.transform = 'translateY(-' + (rowIdx * 34) + 'px)';
                } else {
                    wrap.style.maxHeight = (grid.scrollHeight + 4) + 'px';
                    grid.style.transform = '';
                }
            },

            render: function() { this.renderMobile(); this.renderDesktop(); },

            nav: function(dir) {
                this.currentMonth += dir;
                if (this.currentMonth > 11) { this.currentMonth = 0; this.currentYear++; }
                if (this.currentMonth < 0) { this.currentMonth = 11; this.currentYear--; }
                this.render();
            },
            goToday: function() {
                var now = new Date(); this.currentYear = now.getFullYear(); this.currentMonth = now.getMonth();
                this.selectedDate = null; this.render(); scrollToToday();
            },
            selectDate: function(dateStr) {
                this.selectedDate = dateStr;
                var d = new Date(dateStr + 'T00:00:00');
                if (d.getFullYear() !== this.currentYear || d.getMonth() !== this.currentMonth) {
                    this.currentYear = d.getFullYear(); this.currentMonth = d.getMonth();
                }
                this.render();
            },
            onDayClick: function(dateStr) {
                this.selectDate(dateStr);
                var cards = document.querySelectorAll('#eventListContainer [data-date]');
                for (var i = 0; i < cards.length; i++) { if (cards[i].dataset.date >= dateStr && cards[i].style.display !== 'none') { cards[i].scrollIntoView({ behavior: 'smooth', block: 'start' }); break; } }
            },
            syncFromScroll: function() {
                var scroll = document.getElementById('mainScroll');
                var cards = document.querySelectorAll('#eventListContainer [data-date]');
                var scrollTop = scroll.scrollTop + 80; var visibleDate = null;
                for (var i = 0; i < cards.length; i++) { if (cards[i].style.display === 'none') continue; if (cards[i].offsetTop <= scrollTop) visibleDate = cards[i].dataset.date; else break; }
                if (!visibleDate && cards.length > 0) { for (var j = 0; j < cards.length; j++) { if (cards[j].style.display !== 'none') { visibleDate = cards[j].dataset.date; break; } } }
                if (visibleDate) {
                    var d = new Date(visibleDate + 'T00:00:00');
                    if (d.getFullYear() !== this.currentYear || d.getMonth() !== this.currentMonth) {
                        this.currentYear = d.getFullYear(); this.currentMonth = d.getMonth(); this.render();
                    }
                }
            },
            init: function() {
                var now = new Date(); this.currentYear = now.getFullYear(); this.currentMonth = now.getMonth();
                this.allEventDots = this.buildDots(rawEvents); this.eventDots = this.allEventDots;
                this.render();
            }
        };

        // ---- Toggle Detail (calendar+list accordion) ----
        function toggleDetail(id) {
            var detail = document.getElementById('detail-' + id), arrow = document.getElementById('arrow-' + id), card = document.getElementById('event-card-' + id);
            if (!detail) return;
            var isOpen = detail.classList.contains('open');
            var clickedDate = card ? card.dataset.date : '';
            if (!isOpen) {
                document.querySelectorAll('.detail-panel.open').forEach(function(p) {
                    var parentCard = p.closest('[data-event-id]');
                    if (parentCard && parentCard.dataset.date !== clickedDate) {
                        p.style.maxHeight = '0'; p.classList.remove('open');
                        var ar = parentCard.querySelector('.fa-chevron-down'); if (ar) ar.style.transform = 'rotate(0deg)';
                        parentCard.classList.remove('bg-yellow-50', 'border-yellow-300'); parentCard.classList.add('bg-white', 'border-sky-50');
                    }
                });
                detail.style.maxHeight = detail.scrollHeight + 'px'; detail.classList.add('open');
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                if (card) { card.classList.add('bg-yellow-50', 'border-yellow-300'); card.classList.remove('bg-white', 'border-sky-50'); }
            } else {
                detail.style.maxHeight = '0'; detail.classList.remove('open');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                if (card) { card.classList.remove('bg-yellow-50', 'border-yellow-300'); card.classList.add('bg-white', 'border-sky-50'); }
            }
        }

        // ---- Category Filter (calendar+list) ----
        function filterCategory(catId) {
            activeFilter = catId;
            document.querySelectorAll('[data-filter-cat]').forEach(function(btn) {
                var bCat = parseInt(btn.dataset.filterCat);
                if (bCat === catId) { btn.classList.add('active', 'text-white'); btn.classList.remove('text-slate-500', 'bg-white', 'border-slate-200'); btn.style.background = bCat === 0 ? 'var(--hinata-theme)' : (catColors[bCat] || '#64748b'); btn.style.borderColor = 'transparent'; }
                else { btn.classList.remove('active', 'text-white'); btn.classList.add('text-slate-500', 'bg-white', 'border-slate-200'); btn.style.background = ''; btn.style.borderColor = ''; }
            });
            document.querySelectorAll('#eventListContainer [data-event-id]').forEach(function(card) { card.style.display = (catId === 0 || parseInt(card.dataset.category) === catId) ? '' : 'none'; });
            document.querySelectorAll('#eventListContainer [data-month-header]').forEach(function(hdr) { hdr.style.display = catId === 0 ? '' : 'none'; });
            var sep = document.getElementById('today-separator'); if (sep) sep.style.display = catId === 0 ? '' : 'none';
            MiniCal.rebuildDots(catId); MiniCal.render();
            if (currentViewMode === 'timeline') TimelineView.render();
            if (currentViewMode === 'dashboard') DashboardView.render();
        }

        function scrollToEvent(eventId) {
            var card = document.getElementById('event-card-' + eventId);
            if (card) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); var detail = document.getElementById('detail-' + eventId); if (detail && !detail.classList.contains('open')) setTimeout(function() { toggleDetail(eventId); }, 400); }
        }
        function scrollToToday() {
            var sep = document.getElementById('today-separator');
            if (sep) { sep.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            else { var ts = _todayStr(); var cards = document.querySelectorAll('#eventListContainer [data-date]'); for (var i = 0; i < cards.length; i++) { if (cards[i].dataset.date >= ts && cards[i].style.display !== 'none') { cards[i].scrollIntoView({ behavior: 'smooth', block: 'center' }); break; } } }
        }

        // ---- Scroll sync ----
        var _scrollTimer = null;
        document.getElementById('mainScroll').addEventListener('scroll', function() {
            if (_scrollTimer) clearTimeout(_scrollTimer);
            _scrollTimer = setTimeout(function() { MiniCal.syncFromScroll(); }, 150);
        }, { passive: true });

        // Mobile calendar toggle
        document.getElementById('calToggle').onclick = function() {
            MiniCal.mobileCollapsed = !MiniCal.mobileCollapsed;
            MiniCal.updateMobileCollapse();
            var icon = this.querySelector('i');
            icon.classList.toggle('fa-chevron-down', MiniCal.mobileCollapsed);
            icon.classList.toggle('fa-chevron-up', !MiniCal.mobileCollapsed);
        };

        // Mobile swipe on calendar
        (function() {
            var el = document.getElementById('miniCalMobile');
            if (!el) return;
            var startX = 0, startY = 0, swiping = false;
            el.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                swiping = true;
            }, { passive: true });
            el.addEventListener('touchend', function(e) {
                if (!swiping) return;
                swiping = false;
                var dx = e.changedTouches[0].clientX - startX;
                var dy = e.changedTouches[0].clientY - startY;
                if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                    MiniCal.nav(dx < 0 ? 1 : -1);
                }
            }, { passive: true });
        })();

        document.addEventListener('DOMContentLoaded', function() {
            MiniCal.init();
            var urlParams = new URLSearchParams(window.location.search);
            var eventId = urlParams.get('event_id');
            if (eventId) {
                setTimeout(function() { var card = document.getElementById('event-card-' + eventId); if (card) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); var detail = document.getElementById('detail-' + eventId); if (detail && !detail.classList.contains('open')) toggleDetail(parseInt(eventId)); } window.history.replaceState({}, '', '/hinata/events.php'); }, 500);
            } else { setTimeout(scrollToToday, 300); }
        });

        // ---- Attendance / Setlist (calendar+list inline) ----
        function toggleAttendance(eventId, btn) {
            App.post('/hinata/api/toggle_attendance.php', { event_id: eventId }, function(res) {
                if (res.status === 'success') {
                    var attended = res.attended; btn.dataset.attended = attended ? '1' : '0';
                    btn.className = 'attendance-btn text-[10px] font-bold px-3 py-1.5 rounded-full border transition flex items-center gap-1 ' + (attended ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-slate-500 border-slate-200 hover:border-sky-300');
                    btn.querySelector('span').textContent = attended ? '参戦済み' : '参戦した';
                    var idx = attendedIds.indexOf(parseInt(eventId)); if (attended && idx === -1) attendedIds.push(parseInt(eventId)); if (!attended && idx !== -1) attendedIds.splice(idx, 1);
                    App.toast(res.message, 'success');
                }
            });
        }

        function loadSetlist(eventId) {
            var container = document.getElementById('setlist-' + eventId); if (!container) return;
            if (!container.classList.contains('hidden')) { container.classList.add('hidden'); return; }
            container.innerHTML = '<p class="text-xs text-slate-400 py-2"><i class="fa-solid fa-spinner fa-spin mr-1"></i>読み込み中...</p>';
            container.classList.remove('hidden');
            fetch('/hinata/api/get_event_setlist.php?event_id=' + eventId).then(function(r) { return r.json(); }).then(function(res) {
                if (res.status !== 'success' || !res.data.setlist.length) {
                    <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                    container.innerHTML = '<div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4"><p class="text-xs text-slate-400 mb-3">セットリスト未登録</p><button onclick="openSetlistEditor(' + eventId + ')" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-3 py-1.5 rounded-full hover:bg-sky-100 transition"><i class="fa-solid fa-plus mr-1"></i>セットリストを登録</button></div>';
                    <?php else: ?>
                    container.innerHTML = '<p class="text-xs text-slate-400 py-2">セットリストは未登録です</p>';
                    <?php endif; ?>
                    return;
                }
                var html = '<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">';
                html += '<div class="px-4 py-2 bg-indigo-50 flex items-center gap-2"><i class="fa-solid fa-list-ol text-indigo-500 text-xs"></i><span class="text-[10px] font-bold text-indigo-700 tracking-wider">セットリスト</span><span class="text-[10px] text-indigo-400 ml-auto">' + res.data.setlist.length + '曲</span></div>';
                html += '<ol class="divide-y divide-slate-50">'; var lastEncore = false;
                res.data.setlist.forEach(function(item, i) {
                    if (item.encore && !lastEncore) html += '<li class="px-4 py-1.5 bg-slate-50 text-center"><span class="text-[9px] font-black text-slate-400 tracking-wider">ENCORE</span></li>';
                    lastEncore = !!item.encore;
                    html += '<li class="px-4 py-2 flex items-center gap-3 text-xs"><span class="text-slate-400 w-5 text-right font-mono">' + (i+1) + '</span><a href="/hinata/song.php?id=' + item.song_id + '" class="flex-1 font-bold text-slate-800 hover:text-sky-600 transition truncate">' + _esc(item.song_title) + '</a><span class="text-[10px] text-slate-400 shrink-0">' + _esc(item.release_title) + '</span></li>';
                });
                html += '</ol>';
                <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                html += '<div class="px-4 py-2 border-t border-slate-50"><button onclick="openSetlistEditor(' + eventId + ')" class="text-[10px] font-bold text-sky-600 hover:text-sky-700 transition"><i class="fa-solid fa-pen mr-0.5"></i>編集</button></div>';
                <?php endif; ?>
                html += '</div>';
                container.innerHTML = html;
            });
        }

        <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
        var allSongs = <?= json_encode(
            (function() {
                $songModel = new \App\Hinata\Model\SongModel();
                $songs = $songModel->getAllSongsWithRelease();
                return array_map(fn($s) => ['id' => $s['id'], 'title' => $s['title'], 'release_title' => $s['release_title'] ?? ''], $songs);
            })(),
            JSON_UNESCAPED_UNICODE
        ) ?>;

        function openSetlistEditor(eventId) {
            var container = document.getElementById('setlist-' + eventId); if (!container) return;
            fetch('/hinata/api/get_event_setlist.php?event_id=' + eventId).then(function(r) { return r.json(); }).then(function(res) {
                var existing = (res.status === 'success' && res.data.setlist) ? res.data.setlist : [];
                var html = '<div class="bg-white rounded-xl border border-sky-100 shadow-sm p-4 space-y-3">';
                html += '<h4 class="text-[10px] font-black text-slate-500 tracking-wider">セットリスト編集</h4>';
                html += '<div id="setlist-editor-items-' + eventId + '" class="space-y-1">';
                existing.forEach(function(item, i) { html += setlistItemRow(eventId, i, item.song_id, item.encore); });
                html += '</div><div class="flex items-center gap-2">';
                html += '<button onclick="addSetlistItem(' + eventId + ')" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-3 py-1.5 rounded-full hover:bg-sky-100 transition"><i class="fa-solid fa-plus mr-1"></i>曲を追加</button>';
                html += '<button onclick="saveSetlist(' + eventId + ')" class="text-[10px] font-bold text-white bg-sky-500 px-4 py-1.5 rounded-full hover:bg-sky-600 transition"><i class="fa-solid fa-check mr-1"></i>保存</button>';
                html += '</div></div>';
                container.innerHTML = html; container.classList.remove('hidden');
            });
        }
        function setlistItemRow(eventId, index, selectedSongId, isEncore) {
            var opts = '<option value="">-- 楽曲を選択 --</option>';
            allSongs.forEach(function(s) { opts += '<option value="' + s.id + '"' + (s.id == selectedSongId ? ' selected' : '') + '>' + _esc(s.title) + ' (' + _esc(s.release_title) + ')</option>'; });
            return '<div class="flex items-center gap-2" data-index="' + index + '"><span class="text-[10px] text-slate-400 w-5 text-right">' + (index+1) + '</span><select class="setlist-song-select flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-sky-200">' + opts + '</select><label class="flex items-center gap-1 text-[10px] text-slate-500 shrink-0"><input type="checkbox" class="setlist-encore-check"' + (isEncore ? ' checked' : '') + '> EN</label><button onclick="this.parentElement.remove()" class="text-slate-300 hover:text-red-400 text-xs"><i class="fa-solid fa-xmark"></i></button></div>';
        }
        function addSetlistItem(eventId) { var list = document.getElementById('setlist-editor-items-' + eventId); list.insertAdjacentHTML('beforeend', setlistItemRow(eventId, list.children.length, '', false)); }
        function saveSetlist(eventId) {
            var list = document.getElementById('setlist-editor-items-' + eventId); var items = [];
            list.querySelectorAll('[data-index]').forEach(function(row, i) { var select = row.querySelector('.setlist-song-select'), encore = row.querySelector('.setlist-encore-check'); if (select && select.value) items.push({ song_id: parseInt(select.value), sort_order: i+1, encore: encore && encore.checked ? 1 : 0 }); });
            App.post('/hinata/api/save_setlist.php', { event_id: eventId, items: items }, function(res) { if (res.status === 'success') { App.toast('セットリストを保存しました', 'success'); loadSetlist(eventId); } else { App.toast(res.message || 'エラー', 'error'); } });
        }
        <?php endif; ?>
    </script>
</body>
</html>
