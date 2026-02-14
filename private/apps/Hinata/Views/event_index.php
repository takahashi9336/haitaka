<?php
/**
 * イベント一覧 View (日本語版)
 * 物理パス: haitaka/private/apps/Hinata/Views/event_index.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

// カテゴリ設定のヘルパー
$getCatInfo = function($catId) {
    return match((int)$catId) {
        1 => ['name' => 'ライブ', 'color' => '#3b82f6'],
        2 => ['name' => 'ミーグリ', 'color' => '#10b981'],
        3 => ['name' => 'リアルミーグリ', 'color' => '#f59e0b'],
        default => ['name' => 'その他', 'color' => '#64748b'],
    };
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>イベントスケジュール - Hinata Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .tab-btn.active { background: var(--hinata-theme); color: white; border-color: var(--hinata-theme); }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .date-col { width: 65px; flex-shrink: 0; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-calendar-days text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight">イベント</h1>
            </div>
            <div class="flex items-center gap-2">
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="/hinata/event_admin.php" class="text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition flex items-center gap-1"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span>管理</span>
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="bg-white border-b <?= $cardBorder ?> px-4 py-2 flex items-center justify-between shrink-0">
            <div class="flex p-1 bg-slate-100 rounded-xl">
                <button onclick="switchTab('list')" id="tab-list" class="tab-btn active px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider transition-all">リスト</button>
                <button onclick="switchTab('calendar')" id="tab-calendar" class="tab-btn px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider transition-all">カレンダー</button>
            </div>
            <span class="text-[10px] font-black text-slate-400 tracking-wider"><?= date('n月 Y年') ?></span>
        </div>

        <div class="flex-1 overflow-y-auto custom-scroll">
            <div id="view-list" class="p-4 md:p-8 space-y-3 max-w-4xl mx-auto">
                <?php if (empty($events)): ?>
                <div class="text-center py-20 bg-white rounded-[2.5rem] border <?= $cardBorder ?> shadow-sm">
                    <i class="fa-solid fa-calendar-xmark text-4xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400 font-bold">予定されているイベントはありません</p>
                </div>
                <?php else: ?>
                    <?php 
                    foreach ($events as $e): 
                        $cat = $getCatInfo($e['category']);
                        $time = \Core\Utils\DateUtil::format($e['event_date'], 'H:i');
                        $dateM = \Core\Utils\DateUtil::format($e['event_date'], 'n/j');
                        $dateD = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'][\Core\Utils\DateUtil::format($e['event_date'], 'D')];
                        $isToday = date('Y-m-d') === \Core\Utils\DateUtil::format($e['event_date'], 'Y-m-d');
                    ?>
                    <div id="event-card-<?= $e['id'] ?>" class="bg-white rounded-lg border border-sky-50 shadow-sm overflow-hidden hover:border-sky-200 transition-all" data-event-id="<?= $e['id'] ?>">
                        <div class="flex items-stretch cursor-pointer active:bg-slate-50" onclick="toggleDetail(<?= $e['id'] ?>)">
                            <div class="date-col flex flex-col items-center justify-center py-4 bg-slate-50/50 border-r border-slate-100 <?= $isToday ? 'bg-sky-50' : '' ?>">
                                <span class="text-[10px] font-black text-slate-400 tracking-wider"><?= $dateD ?></span>
                                <span class="text-lg font-black text-slate-700 leading-none"><?= $dateM ?></span>
                            </div>
                            <div class="w-1.5 shrink-0" style="background-color: <?= $cat['color'] ?>;"></div>
                            <div class="flex-1 p-4 min-w-0 flex items-center justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[9px] font-black text-slate-400 tracking-wider"><?= htmlspecialchars($cat['name']) ?></span>
                                        <span class="text-[10px] font-bold text-slate-400"><?= $time ?></span>
                                    </div>
                                    <h3 class="font-black text-slate-800 text-sm md:text-base truncate"><?= htmlspecialchars($e['event_name']) ?></h3>
                                    <?php if(!empty($e['event_place'])): ?>
                                    <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot text-sky-300"></i> <?= htmlspecialchars($e['event_place']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="px-2"><i id="arrow-<?= $e['id'] ?>" class="fa-solid fa-chevron-down text-slate-200 text-xs transition-transform duration-300"></i></div>
                            </div>
                        </div>

                        <div id="detail-<?= $e['id'] ?>" class="hidden border-t border-slate-50 bg-slate-50/30 p-4 space-y-4">
                            <?php if (!empty($e['event_info'])): ?>
                                <div class="text-xs text-slate-600 bg-white p-4 rounded-xl border border-slate-100 shadow-sm whitespace-pre-wrap"><?= htmlspecialchars($e['event_info']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($e['event_url'])): ?>
                                <a href="<?= htmlspecialchars($e['event_url']) ?>" target="_blank" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-4 py-2 rounded-full border border-sky-100 hover:bg-sky-100 transition inline-flex items-center gap-2">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> 特設サイトを開く
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($e['video_key'])): ?>
                                <div class="aspect-video w-full max-w-2xl mx-auto rounded-lg overflow-hidden bg-black shadow-lg ring-4 ring-white">
                                    <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?= htmlspecialchars($e['video_key']) ?>?rel=0" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="view-calendar" class="hidden p-4 md:p-8">
                <div class="bg-white p-4 md:p-8 rounded-[2.5rem] border border-sky-50 shadow-sm max-w-5xl mx-auto">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        function toggleDetail(id) {
            const detail = document.getElementById('detail-' + id);
            const arrow = document.getElementById('arrow-' + id);
            const card = document.getElementById('event-card-' + id);
            if (!detail) return;
            const isHidden = detail.classList.contains('hidden');
            detail.classList.toggle('hidden');
            if (arrow) arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            
            // 選択状態の背景色を更新
            if (isHidden) {
                // 展開する場合: まず全てのイベントカードの黄色背景を削除
                document.querySelectorAll('[data-event-id]').forEach(eventCard => {
                    eventCard.classList.remove('bg-yellow-50', 'border-yellow-300');
                    eventCard.classList.add('bg-white', 'border-sky-50');
                });
                
                // このカードのみ黄色背景に
                if (card) {
                    card.classList.add('bg-yellow-50', 'border-yellow-300');
                    card.classList.remove('bg-white', 'border-sky-50');
                }
            } else {
                // 閉じる場合: このカードの白背景に戻す
                if (card) {
                    card.classList.remove('bg-yellow-50', 'border-yellow-300');
                    card.classList.add('bg-white', 'border-sky-50');
                }
            }
        }

        let calendar = null;
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const rawEvents = <?= json_encode($events) ?>;
            const formattedEvents = rawEvents.map(e => {
                const colors = { 1: '#3b82f6', 2: '#10b981', 3: '#f59e0b' };
                return { id: e.id, title: e.event_name, start: e.event_date, color: colors[e.category] || '#64748b' };
            });
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                events: formattedEvents,
                height: 'auto',
                aspectRatio: 1.35,
                eventClick: function(info) {
                    switchTab('list');
                    setTimeout(() => {
                        const card = document.getElementById('detail-' + info.event.id);
                        if (card) {
                            card.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            if (card.classList.contains('hidden')) toggleDetail(info.event.id);
                        }
                    }, 100);
                }
            });
            calendar.render();
            
            // URLパラメータからイベントIDを取得して自動選択
            const urlParams = new URLSearchParams(window.location.search);
            const eventId = urlParams.get('event_id');
            if (eventId) {
                setTimeout(() => {
                    switchTab('list');
                    const card = document.getElementById('detail-' + eventId);
                    if (card) {
                        card.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (card.classList.contains('hidden')) {
                            toggleDetail(parseInt(eventId));
                        }
                    }
                    // URLパラメータをクリア
                    window.history.replaceState({}, '', '/hinata/events.php');
                }, 500);
            }
        });

        function switchTab(mode) {
            document.getElementById('view-list').classList.toggle('hidden', mode !== 'list');
            document.getElementById('view-calendar').classList.toggle('hidden', mode !== 'calendar');
            document.getElementById('tab-list').classList.toggle('active', mode === 'list');
            document.getElementById('tab-calendar').classList.toggle('active', mode === 'calendar');
            if (mode === 'calendar' && calendar) setTimeout(() => calendar.updateSize(), 10);
        }
    </script>
</body>
</html>