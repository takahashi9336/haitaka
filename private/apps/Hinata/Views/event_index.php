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
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
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
        .modal-backdrop { background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); }
        .fc-event { cursor: pointer !important; }
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
                <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
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
                                        <a href="/hinata/meetgreet.php" class="ml-auto text-[10px] font-bold text-emerald-500 hover:text-emerald-700 transition">
                                            詳細 <i class="fa-solid fa-arrow-right text-[8px]"></i>
                                        </a>
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
                                            <div class="shrink-0 font-bold text-slate-600"><?= (int)$slot['ticket_count'] ?><span class="text-[10px] text-slate-400">枚</span></div>
                                            <?php if (!empty($slot['report'])): ?>
                                                <i class="fa-solid fa-pen-to-square text-emerald-400 text-[10px] shrink-0" title="レポあり"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
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

    <!-- イベント詳細モーダル（カレンダー表示時） -->
    <div id="eventModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-backdrop absolute inset-0" onclick="closeEventModal()"></div>
        <div class="relative z-10 flex items-center justify-center min-h-full p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 shrink-0">
                    <div id="modalCatStrip" class="w-1.5 h-10 rounded-full shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span id="modalCatName" class="text-[9px] font-black text-slate-400 tracking-wider"></span>
                            <span id="modalDate" class="text-[10px] font-bold text-slate-400"></span>
                        </div>
                        <h2 id="modalTitle" class="font-black text-slate-800 text-base truncate"></h2>
                    </div>
                    <button onclick="closeEventModal()" class="text-slate-400 hover:text-slate-600 p-1 shrink-0"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div id="modalBody" class="flex-1 overflow-y-auto px-6 py-4 space-y-4 custom-scroll"></div>
                <div class="px-6 py-3 border-t border-slate-100 shrink-0 flex justify-end">
                    <button onclick="viewInList()" class="text-[10px] font-bold text-slate-500 bg-slate-100 px-4 py-2 rounded-lg hover:bg-slate-200 transition flex items-center gap-1">
                        <i class="fa-solid fa-list"></i> リストで見る
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
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

        const catColors = { 1: '#3b82f6', 2: '#10b981', 3: '#f59e0b' };
        const catNames = { 1: 'ライブ', 2: 'ミーグリ', 3: 'リアルミーグリ' };
        const dowNames = ['日','月','火','水','木','金','土'];

        const rawEvents = <?= json_encode($events, JSON_UNESCAPED_UNICODE) ?>;
        const eventSlotsData = <?= json_encode($eventSlots ?? [], JSON_UNESCAPED_UNICODE) ?>;

        const eventsById = {};
        rawEvents.forEach(e => { eventsById[e.id] = e; });

        let currentModalEventId = null;

        function openEventModal(eventId) {
            const e = eventsById[eventId];
            if (!e) return;
            currentModalEventId = eventId;

            const color = catColors[e.category] || '#64748b';
            const catName = catNames[e.category] || 'その他';
            const dt = new Date(e.event_date + 'T00:00:00');
            const dateStr = `${dt.getFullYear()}/${dt.getMonth()+1}/${dt.getDate()}（${dowNames[dt.getDay()]}）`;

            document.getElementById('modalCatStrip').style.backgroundColor = color;
            document.getElementById('modalCatName').textContent = catName;
            document.getElementById('modalDate').textContent = dateStr;
            document.getElementById('modalTitle').textContent = e.event_name;

            let bodyHtml = '';

            if (e.event_place) {
                bodyHtml += `<div class="flex items-center gap-2 text-xs text-slate-500"><i class="fa-solid fa-location-dot text-sky-300"></i>${_esc(e.event_place)}</div>`;
            }

            // ミーグリ予定
            const slots = eventSlotsData[eventId];
            if (slots && slots.length > 0) {
                bodyHtml += `<div class="bg-white rounded-xl border border-emerald-100 shadow-sm overflow-hidden">`;
                bodyHtml += `<div class="px-4 py-2 bg-emerald-50 flex items-center gap-2">`;
                bodyHtml += `<i class="fa-solid fa-ticket text-emerald-500 text-xs"></i>`;
                bodyHtml += `<span class="text-[10px] font-bold text-emerald-700 tracking-wider">自分のミーグリ予定</span>`;
                bodyHtml += `<a href="/hinata/meetgreet.php" class="ml-auto text-[10px] font-bold text-emerald-500 hover:text-emerald-700 transition">詳細 <i class="fa-solid fa-arrow-right text-[8px]"></i></a>`;
                bodyHtml += `</div><div class="divide-y divide-slate-50">`;
                for (const s of slots) {
                    const memberName = s.member_name || s.member_name_raw || '不明';
                    const memberColor = s.color1 || '#94a3b8';
                    const timeStr = (s.start_time && s.end_time) ? s.start_time.substring(0,5) + '～' + s.end_time.substring(0,5) : '';
                    bodyHtml += `<div class="px-4 py-2 flex items-center gap-4 text-xs">`;
                    bodyHtml += `<div class="w-20 shrink-0"><span class="font-bold text-slate-700">${_esc(s.slot_name)}</span>`;
                    if (timeStr) bodyHtml += `<div class="text-[10px] text-slate-400">${timeStr}</div>`;
                    bodyHtml += `</div>`;
                    bodyHtml += `<div class="flex-1 min-w-0"><span class="font-bold" style="color:${memberColor}">${_esc(memberName)}</span></div>`;
                    bodyHtml += `<div class="shrink-0 font-bold text-slate-600">${parseInt(s.ticket_count)}<span class="text-[10px] text-slate-400">枚</span></div>`;
                    if (s.report) bodyHtml += `<i class="fa-solid fa-pen-to-square text-emerald-400 text-[10px] shrink-0" title="レポあり"></i>`;
                    bodyHtml += `</div>`;
                }
                bodyHtml += `</div></div>`;
            }

            if (e.event_info) {
                bodyHtml += `<div class="text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100 whitespace-pre-wrap">${_esc(e.event_info)}</div>`;
            }

            if (e.event_url) {
                bodyHtml += `<a href="${_esc(e.event_url)}" target="_blank" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-4 py-2 rounded-full border border-sky-100 hover:bg-sky-100 transition inline-flex items-center gap-2"><i class="fa-solid fa-arrow-up-right-from-square"></i> 特設サイトを開く</a>`;
            }

            if (e.video_key) {
                bodyHtml += `<div class="aspect-video w-full rounded-lg overflow-hidden bg-black shadow-lg"><iframe width="100%" height="100%" src="https://www.youtube.com/embed/${_esc(e.video_key)}?rel=0" frameborder="0" allowfullscreen></iframe></div>`;
            }

            if (!bodyHtml) {
                bodyHtml = '<p class="text-xs text-slate-400 text-center py-4">詳細情報はありません</p>';
            }

            document.getElementById('modalBody').innerHTML = bodyHtml;
            document.getElementById('eventModal').classList.remove('hidden');
        }

        function closeEventModal() {
            document.getElementById('eventModal').classList.add('hidden');
            currentModalEventId = null;
        }

        function viewInList() {
            const eid = currentModalEventId;
            closeEventModal();
            switchTab('list');
            if (eid) {
                setTimeout(() => {
                    const card = document.getElementById('event-card-' + eid);
                    if (card) {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const detail = document.getElementById('detail-' + eid);
                        if (detail && detail.classList.contains('hidden')) toggleDetail(eid);
                    }
                }, 100);
            }
        }

        function _esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        let calendar = null;
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const formattedEvents = rawEvents.map(e => {
                return { id: e.id, title: e.event_name, start: e.event_date, color: catColors[e.category] || '#64748b' };
            });
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                events: formattedEvents,
                height: 'auto',
                aspectRatio: 1.35,
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    openEventModal(info.event.id);
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

        function toggleAttendance(eventId, btn) {
            App.post('/hinata/api/toggle_attendance.php', { event_id: eventId }, function(res) {
                if (res.status === 'success') {
                    var attended = res.attended;
                    btn.dataset.attended = attended ? '1' : '0';
                    btn.className = 'attendance-btn text-[10px] font-bold px-3 py-1.5 rounded-full border transition flex items-center gap-1 ' +
                        (attended ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-slate-500 border-slate-200 hover:border-sky-300');
                    btn.querySelector('span').textContent = attended ? '参戦済み' : '参戦した';
                    App.toast(res.message, 'success');
                }
            });
        }

        function loadSetlist(eventId) {
            var container = document.getElementById('setlist-' + eventId);
            if (!container) return;
            if (!container.classList.contains('hidden')) {
                container.classList.add('hidden');
                return;
            }
            container.innerHTML = '<p class="text-xs text-slate-400 py-2"><i class="fa-solid fa-spinner fa-spin mr-1"></i>読み込み中...</p>';
            container.classList.remove('hidden');
            fetch('/hinata/api/get_event_setlist.php?event_id=' + eventId)
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data.setlist.length) {
                        <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                        container.innerHTML = '<div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">' +
                            '<p class="text-xs text-slate-400 mb-3">セットリスト未登録</p>' +
                            '<button onclick="openSetlistEditor(' + eventId + ')" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-3 py-1.5 rounded-full hover:bg-sky-100 transition"><i class="fa-solid fa-plus mr-1"></i>セットリストを登録</button>' +
                            '</div>';
                        <?php else: ?>
                        container.innerHTML = '<p class="text-xs text-slate-400 py-2">セットリストは未登録です</p>';
                        <?php endif; ?>
                        return;
                    }
                    var html = '<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">';
                    html += '<div class="px-4 py-2 bg-indigo-50 flex items-center gap-2"><i class="fa-solid fa-list-ol text-indigo-500 text-xs"></i><span class="text-[10px] font-bold text-indigo-700 tracking-wider">セットリスト</span><span class="text-[10px] text-indigo-400 ml-auto">' + res.data.setlist.length + '曲</span></div>';
                    html += '<ol class="divide-y divide-slate-50">';
                    var lastEncore = false;
                    res.data.setlist.forEach(function(item, i) {
                        if (item.encore && !lastEncore) {
                            html += '<li class="px-4 py-1.5 bg-slate-50 text-center"><span class="text-[9px] font-black text-slate-400 tracking-wider">ENCORE</span></li>';
                        }
                        lastEncore = !!item.encore;
                        html += '<li class="px-4 py-2 flex items-center gap-3 text-xs">';
                        html += '<span class="text-slate-400 w-5 text-right font-mono">' + (i + 1) + '</span>';
                        html += '<a href="/hinata/song.php?id=' + item.song_id + '" class="flex-1 font-bold text-slate-800 hover:text-sky-600 transition truncate">' + _esc(item.song_title) + '</a>';
                        html += '<span class="text-[10px] text-slate-400 shrink-0">' + _esc(item.release_title) + '</span>';
                        html += '</li>';
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
            var container = document.getElementById('setlist-' + eventId);
            if (!container) return;

            fetch('/hinata/api/get_event_setlist.php?event_id=' + eventId)
                .then(r => r.json())
                .then(res => {
                    var existing = (res.status === 'success' && res.data.setlist) ? res.data.setlist : [];
                    var html = '<div class="bg-white rounded-xl border border-sky-100 shadow-sm p-4 space-y-3">';
                    html += '<h4 class="text-[10px] font-black text-slate-500 tracking-wider">セットリスト編集</h4>';
                    html += '<div id="setlist-editor-items-' + eventId + '" class="space-y-1">';
                    existing.forEach(function(item, i) {
                        html += setlistItemRow(eventId, i, item.song_id, item.encore);
                    });
                    html += '</div>';
                    html += '<div class="flex items-center gap-2">';
                    html += '<button onclick="addSetlistItem(' + eventId + ')" class="text-[10px] font-bold text-sky-600 bg-sky-50 px-3 py-1.5 rounded-full hover:bg-sky-100 transition"><i class="fa-solid fa-plus mr-1"></i>曲を追加</button>';
                    html += '<button onclick="saveSetlist(' + eventId + ')" class="text-[10px] font-bold text-white bg-sky-500 px-4 py-1.5 rounded-full hover:bg-sky-600 transition"><i class="fa-solid fa-check mr-1"></i>保存</button>';
                    html += '</div></div>';
                    container.innerHTML = html;
                    container.classList.remove('hidden');
                });
        }

        function setlistItemRow(eventId, index, selectedSongId, isEncore) {
            var opts = '<option value="">-- 楽曲を選択 --</option>';
            allSongs.forEach(function(s) {
                opts += '<option value="' + s.id + '"' + (s.id == selectedSongId ? ' selected' : '') + '>' + _esc(s.title) + ' (' + _esc(s.release_title) + ')</option>';
            });
            return '<div class="flex items-center gap-2" data-index="' + index + '">' +
                '<span class="text-[10px] text-slate-400 w-5 text-right">' + (index + 1) + '</span>' +
                '<select class="setlist-song-select flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-sky-200">' + opts + '</select>' +
                '<label class="flex items-center gap-1 text-[10px] text-slate-500 shrink-0"><input type="checkbox" class="setlist-encore-check"' + (isEncore ? ' checked' : '') + '> EN</label>' +
                '<button onclick="this.parentElement.remove()" class="text-slate-300 hover:text-red-400 text-xs"><i class="fa-solid fa-xmark"></i></button>' +
                '</div>';
        }

        function addSetlistItem(eventId) {
            var list = document.getElementById('setlist-editor-items-' + eventId);
            var count = list.children.length;
            list.insertAdjacentHTML('beforeend', setlistItemRow(eventId, count, '', false));
        }

        function saveSetlist(eventId) {
            var list = document.getElementById('setlist-editor-items-' + eventId);
            var items = [];
            list.querySelectorAll('[data-index]').forEach(function(row, i) {
                var select = row.querySelector('.setlist-song-select');
                var encore = row.querySelector('.setlist-encore-check');
                if (select && select.value) {
                    items.push({
                        song_id: parseInt(select.value),
                        sort_order: i + 1,
                        encore: encore && encore.checked ? 1 : 0
                    });
                }
            });
            App.post('/hinata/api/save_setlist.php', { event_id: eventId, items: items }, function(res) {
                if (res.status === 'success') {
                    App.toast('セットリストを保存しました', 'success');
                    loadSetlist(eventId);
                } else {
                    App.toast(res.message || 'エラー', 'error');
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>