<?php
/**
 * ミーグリ予定・レポ View
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
require_once __DIR__ . '/inc/meetgreet_member_pill_style.inc.php';

$today = date('Y-m-d');
$dayNames = ['日','月','火','水','木','金','土'];

$groupedFuture = [];
$groupedPast = [];
if (!empty($groupedSlots)) {
    foreach ($groupedSlots as $date => $slots) {
        if ($date >= $today) {
            $groupedFuture[$date] = $slots;
        } else {
            $groupedPast[$date] = $slots;
        }
    }
}
$firstFutureDate = null;
foreach ($groupedFuture as $d => $_) {
    $firstFutureDate = $d;
    break;
}
$lastPastDate = null;
if (!empty($groupedPast)) {
    $pk = array_keys($groupedPast);
    $lastPastDate = end($pk);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ミーグリ予定 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        :root { --mg-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .accordion-trigger { cursor: pointer; user-select: none; }
        .accordion-trigger:hover { background-color: rgba(0,0,0,0.02); }
        .report-area { min-height: 60px; }
        .slot-row { transition: background-color 0.15s; }
        .slot-row:hover { background-color: rgb(248 250 252); }
        .modal-backdrop { background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); }
        .preview-table th { white-space: nowrap; }
        /* 遠征管理（live_trip/index）の期間トグルと同じ見た目 */
        .mg-period-toggle.is-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(15,23,42,0.08); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-xl"></i></button>
                <a href="/hinata/" class="text-slate-400 p-2 shrink-0 transition <?= $isThemeHex ? 'hover:opacity-80' : 'hover:text-' . $themeTailwind . '-500' ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-ticket text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-base md:text-lg tracking-tight truncate">ミーグリ予定</h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button id="openImportBtn" class="text-xs font-bold px-3 py-1.5 rounded-full <?= $cardIconText ?> <?= $cardIconBg ?> hover:opacity-90"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>+ 予定を追加</button>
            </div>
        </header>

        <div id="scrollContainer" class="flex-1 overflow-y-auto p-4 pb-24">
            <div class="max-w-3xl mx-auto w-full space-y-3">
                <?php
                $mgKpiOshiBoxes = $mgKpiOshiBoxes ?? [];
                $mgKpiShowOshi = !empty($mgKpiOshiBoxes);
                $mgKpiGridClass = $mgKpiShowOshi
                    ? 'grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2 sm:gap-3'
                    : 'grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2 sm:gap-3';
                ?>
                <section class="<?= htmlspecialchars($mgKpiGridClass) ?>" aria-label="ミーグリ予定サマリー">
                    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm flex flex-col min-h-0 sm:min-h-[120px] sm:p-4">
                        <div class="text-xs font-bold text-slate-500">直近の予定</div>
                        <?php if (($mgKpiNearestDate ?? null) !== null && ($mgKpiNearestDays ?? null) !== null): ?>
                        <div class="mt-1 flex flex-wrap items-baseline">
                            <?php if ((int)$mgKpiNearestDays === 0): ?>
                                <span class="text-2xl font-black text-slate-900">本日</span>
                            <?php else: ?>
                                <span class="text-sm font-bold text-slate-500">あと</span><span class="text-2xl font-black text-slate-900 tabular-nums mx-0.5"><?= (int)$mgKpiNearestDays ?></span><span class="text-sm font-bold text-slate-500 ml-1">日後</span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="mt-1 text-2xl font-black text-slate-400">予定なし</div>
                        <?php endif; ?>
                        <?php if (($mgKpiNearestProgressPct ?? null) !== null): ?>
                        <div class="mt-auto pt-2 sm:pt-3">
                            <div class="h-1.5 w-full rounded-full bg-sky-100 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$mgKpiNearestProgressPct ?>">
                                <div class="h-full rounded-full bg-sky-500 transition-all duration-500" style="width: <?= (int)$mgKpiNearestProgressPct ?>%;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm flex flex-col min-h-0 sm:min-h-[120px] sm:p-4">
                        <div class="text-xs font-bold text-slate-500">保有チケット枚数</div>
                        <div class="mt-1 text-2xl font-black text-slate-900"><?= number_format((int)($mgKpiTotalFutureTickets ?? 0)) ?><span class="text-sm font-bold text-slate-500 ml-1">枚</span></div>
                    </div>
                    <?php if ($mgKpiShowOshi): ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm flex flex-col min-h-0 sm:min-h-[120px] sm:p-4">
                        <div class="text-xs font-bold text-slate-500">推しの枚数</div>
                        <div class="mt-2 flex flex-wrap items-start justify-start gap-3 sm:mt-3 sm:gap-5">
                            <?php foreach ($mgKpiOshiBoxes as $box):
                                $oshiColor = hinata_meetgreet_member_text_color(
                                    !empty($box['color1']) ? (string)$box['color1'] : null,
                                    '#334155'
                                );
                                $oshiColorEsc = htmlspecialchars($oshiColor, ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="flex flex-col items-center gap-1.5 min-w-[5rem] max-w-[9rem] shrink-0 text-center sm:min-w-[4.5rem] sm:max-w-[7rem]">
                                <div class="text-sm font-bold leading-snug line-clamp-2 sm:text-xs" style="color: <?= $oshiColorEsc ?>;"><?= htmlspecialchars((string)$box['name']) ?></div>
                                <div class="text-base font-black tabular-nums sm:text-sm" style="color: <?= $oshiColorEsc ?>;"><?= (int)$box['tickets'] ?><span class="ml-0.5 text-xs font-bold opacity-80 sm:text-[10px]">枚</span></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if (empty($groupedSlots)): ?>
                    <p class="text-center text-slate-400 text-xs py-16 tracking-wider">ミーグリ予定がありません</p>
                <?php else: ?>
                    <div class="flex flex-wrap gap-3 mb-4 items-start">
                        <div id="mgFilterBar" class="inline-flex items-center gap-2 flex-wrap w-fit max-w-full p-1 rounded-xl bg-slate-100 border border-slate-200" role="group" aria-label="予定の表示範囲">
                            <button type="button" class="mg-period-toggle px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white whitespace-nowrap is-active" data-mg-filter="future" aria-pressed="true">今後の予定</button>
                            <button type="button" class="mg-period-toggle px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white whitespace-nowrap" data-mg-filter="past" aria-pressed="false">過去の予定</button>
                            <button type="button" class="mg-period-toggle px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white whitespace-nowrap" data-mg-filter="all" aria-pressed="false">すべて</button>
                        </div>
                    </div>

                    <div id="mgSectionFuture" class="space-y-3">
                        <?php if (empty($groupedFuture)): ?>
                            <p id="mgEmptyFuture" class="text-center text-slate-400 text-xs py-10 tracking-wider">今後の予定はありません</p>
                        <?php else: ?>
                            <?php foreach ($groupedFuture as $date => $slots):
                                $isOpen = ($date === $firstFutureDate);
                                require __DIR__ . '/partials/meetgreet_slot_day_card.php';
                            endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div id="mgPastSubheader" class="hidden pt-4 border-t border-slate-200/80">
                        <h2 class="text-[11px] font-black text-slate-500 tracking-wider">過去の予定</h2>
                    </div>
                    <div id="mgSectionPast" class="space-y-3 hidden">
                        <?php if (empty($groupedPast)): ?>
                            <p id="mgEmptyPast" class="text-center text-slate-400 text-xs py-10 tracking-wider">過去の予定はありません</p>
                        <?php else: ?>
                            <?php foreach ($groupedPast as $date => $slots):
                                $isOpen = false;
                                require __DIR__ . '/partials/meetgreet_slot_day_card.php';
                            endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- インポートモーダル -->
    <div id="importModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-backdrop absolute inset-0" onclick="MG.closeImport()"></div>
        <div class="relative z-10 flex items-center justify-center min-h-full p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                    <h2 class="font-bold text-slate-700">ミーグリ予定を追加</h2>
                    <button onclick="MG.closeImport()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <!-- タブ切り替え -->
                <div class="flex border-b border-slate-100 px-6">
                    <button id="tabImport" type="button" class="add-tab px-4 py-2 text-xs font-bold border-b-2 -mb-px transition" style="border-color: var(--mg-theme); color: var(--mg-theme);" onclick="MG.switchAddTab('import')">
                        <i class="fa-solid fa-file-import mr-1"></i>テキストで一括追加
                    </button>
                    <button id="tabManual" type="button" class="add-tab px-4 py-2 text-xs font-bold text-slate-400 border-b-2 border-transparent -mb-px hover:text-slate-600 transition" onclick="MG.switchAddTab('manual')">
                        <i class="fa-solid fa-pen-to-square mr-1"></i>手動で1件追加
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <!-- 手動追加フォーム -->
                    <div id="manualAddForm" class="hidden space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">イベント（任意）</label>
                            <select id="manualEvent" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);">
                                <option value="">選択しない</option>
                            </select>
                            <div class="mt-1 text-[10px] text-slate-400">イベントを先に選ぶと日付が自動入力されます</div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">日付 <span class="text-red-500">*</span></label>
                            <input type="date" id="manualDate" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">部 <span class="text-red-500">*</span></label>
                            <select id="manualSlot" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);" required>
                                <option value="">選択</option>
                                <option value="第1部">第1部</option>
                                <option value="第2部">第2部</option>
                                <option value="第3部">第3部</option>
                                <option value="第4部">第4部</option>
                                <option value="第5部">第5部</option>
                                <option value="第6部">第6部</option>
                                <option value="other">その他（下で入力）</option>
                            </select>
                            <input type="text" id="manualSlotOther" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm mt-2 hidden" placeholder="部名を入力" style="focus:ring-color: var(--mg-theme);">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">メンバー <span class="text-red-500">*</span></label>
                            <select id="manualMember" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);" required>
                                <?php require __DIR__ . '/partials/member_select_options.php'; ?>
                            </select>
                        </div>
                        <div id="manualTimeGrid" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">開始時刻</label>
                                <input type="time" id="manualStartTime" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">終了時刻</label>
                                <input type="time" id="manualEndTime" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);">
                            </div>
                        </div>
                        <div id="manualSingleTickets">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">枚数</label>
                            <input type="number" id="manualTickets" min="0" value="0" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);">
                        </div>
                        <div id="manualRoundTickets" class="hidden">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-2">部ごとの枚数</label>
                            <div id="manualRoundTicketList" class="space-y-2"></div>
                            <div class="mt-1 text-[10px] text-slate-400">0枚の部は登録しません</div>
                        </div>
                        <button onclick="MG.submitManualAdd()" class="w-full h-11 rounded-lg font-bold text-white mt-3 transition active:scale-95" style="background: var(--mg-theme);">
                            <i class="fa-solid fa-plus mr-1"></i>登録する
                        </button>
                    </div>
                    <!-- Step 1: テキスト入力 -->
                    <div id="importStep1">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider">forTUNE meetsの当選結果を貼り付け</label>
                            <?php $guideKey = 'meetgreet_import'; require_once __DIR__ . '/../../../components/guide_display.php'; ?>
                        </div>
                        <textarea id="importText" rows="8" class="w-full border border-slate-200 rounded-lg p-4 text-sm outline-none focus:ring-2 resize-y font-mono" style="focus:ring-color: var(--mg-theme);"
                                  placeholder="当選&#10;2026年2月21日(土)&#10;&#10;第１部&#10;&#10;髙橋未来虹&#10;&#10;9枚（9口）"></textarea>
                        <div class="flex items-center gap-2 mt-3">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider shrink-0">日付（手動指定）</label>
                            <input type="date" id="importDate" class="flex-1 h-9 border border-slate-200 rounded-lg px-3 text-xs outline-none focus:ring-2" style="focus:ring-color: var(--mg-theme);">
                            <span class="text-[9px] text-slate-400">テキストから自動検出</span>
                        </div>
                        <button onclick="MG.parseInput()" class="w-full h-11 rounded-lg font-bold text-white mt-3 transition active:scale-95" style="background: var(--mg-theme);">
                            <i class="fa-solid fa-file-import mr-1"></i>取り込む
                        </button>
                    </div>

                    <!-- Step 2: プレビュー -->
                    <div id="importStep2" class="hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <button onclick="MG.backToStep1()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-arrow-left"></i></button>
                            <span id="previewDateLabel" class="text-sm font-bold text-slate-700"></span>
                        </div>
                        <div id="previewVenue" class="hidden mb-2 text-xs text-slate-500"><i class="fa-solid fa-location-dot mr-1 text-slate-400"></i><span id="previewVenueText"></span></div>
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="preview-table w-full text-sm">
                                <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2 text-left">部</th>
                                        <th class="px-3 py-2 text-left">メンバー</th>
                                        <th class="px-3 py-2 text-center">枚数</th>
                                    </tr>
                                </thead>
                                <tbody id="previewBody" class="divide-y divide-slate-50"></tbody>
                            </table>
                        </div>
                        <!-- イベント紐付け -->
                        <div id="eventLinkSection" class="hidden mt-3 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="linkEventCheck" checked class="w-4 h-4 rounded border-emerald-300" style="accent-color: #10b981;">
                                <span class="text-xs font-bold text-emerald-700"><i class="fa-solid fa-link mr-1"></i>イベントと紐付ける</span>
                            </label>
                            <p id="eventLinkName" class="text-xs text-emerald-600 mt-1 ml-6"></p>
                        </div>
                        <div id="parseWarning" class="hidden mt-2 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i><span id="parseWarningText"></span>
                        </div>
                        <button onclick="MG.submitImport()" class="w-full h-11 rounded-lg font-bold text-white mt-3 transition active:scale-95" style="background: var(--mg-theme);">
                            <i class="fa-solid fa-check mr-1"></i>この内容で登録する
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
    const memberList = <?= json_encode(array_map(fn($m) => ['id' => $m['id'], 'name' => $m['name']], $members), JSON_UNESCAPED_UNICODE) ?>;
    const mgEvents = <?= json_encode(array_map(fn($e) => [
        'id' => $e['id'],
        'event_name' => $e['event_name'],
        'event_date' => $e['event_date'],
        'category' => (int)$e['category'],
        'mg_rounds' => (int)($e['mg_rounds'] ?? 0),
    ], $mgEvents), JSON_UNESCAPED_UNICODE) ?>;
    const mgToday = <?= json_encode($today, JSON_UNESCAPED_UNICODE) ?>;
    const mgHasFuture = <?= !empty($groupedFuture) ? 'true' : 'false' ?>;
    const mgHasPast = <?= !empty($groupedPast) ? 'true' : 'false' ?>;
    const mgLastPastDate = <?= json_encode($lastPastDate, JSON_UNESCAPED_UNICODE) ?>;

    const MG = {
        parsedSlots: [],
        matchedEventId: null,
        mgFilter: 'future',

        setFilter(mode) {
            const secF = document.getElementById('mgSectionFuture');
            const secP = document.getElementById('mgSectionPast');
            const sub = document.getElementById('mgPastSubheader');
            if (!secF || !secP) return;

            this.mgFilter = mode;
            document.querySelectorAll('#mgFilterBar [data-mg-filter]').forEach(btn => {
                const on = btn.getAttribute('data-mg-filter') === mode;
                btn.classList.toggle('is-active', on);
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            if (mode === 'future') {
                secF.classList.remove('hidden');
                secP.classList.add('hidden');
                if (sub) sub.classList.add('hidden');
                this._collapsePastAccordions();
            } else if (mode === 'past') {
                secF.classList.add('hidden');
                secP.classList.remove('hidden');
                if (sub) sub.classList.add('hidden');
                if (mgLastPastDate) {
                    mgOpenDateAccordion(mgLastPastDate);
                }
            } else {
                secF.classList.remove('hidden');
                if (mgHasPast) {
                    secP.classList.remove('hidden');
                    if (sub) sub.classList.remove('hidden');
                } else {
                    secP.classList.add('hidden');
                    if (sub) sub.classList.add('hidden');
                }
                this._collapsePastAccordions();
            }
        },

        _collapsePastAccordions() {
            document.querySelectorAll('#mgSectionPast [data-date-group]').forEach(grp => {
                const d = grp.getAttribute('data-date-group');
                if (!d || d >= mgToday) return;
                const el = document.getElementById('content-' + d);
                const icon = document.getElementById('icon-' + d);
                if (el && !el.classList.contains('hidden')) {
                    el.classList.add('hidden');
                    if (icon) icon.classList.remove('rotate-180');
                }
            });
        },

        // --- アコーディオン ---
        toggleDate(date) {
            const el = document.getElementById('content-' + date);
            const icon = document.getElementById('icon-' + date);
            if (!el) return;
            const opening = el.classList.contains('hidden');
            el.classList.toggle('hidden');
            if (icon) icon.classList.toggle('rotate-180', opening);

            let opened = JSON.parse(localStorage.getItem('mg_opened_dates') || '[]');
            if (opening) { if (!opened.includes(date)) opened.push(date); }
            else { opened = opened.filter(d => d !== date); }
            localStorage.setItem('mg_opened_dates', JSON.stringify(opened));
        },

        // --- レポ ---
        openReport(id) {
            const editEl = document.getElementById('report-edit-' + id);
            const displayEl = document.getElementById('report-display-' + id);
            if (!editEl) return;
            const isOpen = !editEl.classList.contains('hidden');
            if (isOpen) {
                editEl.classList.add('hidden');
                if (displayEl) displayEl.classList.remove('hidden');
            } else {
                editEl.classList.remove('hidden');
                if (displayEl) displayEl.classList.add('hidden');
                const ta = document.getElementById('report-textarea-' + id);
                if (ta) ta.focus();
            }
        },

        cancelReport(id) {
            const editEl = document.getElementById('report-edit-' + id);
            const displayEl = document.getElementById('report-display-' + id);
            if (editEl) editEl.classList.add('hidden');
            if (displayEl) displayEl.classList.remove('hidden');
        },

        async saveReport(id) {
            const ta = document.getElementById('report-textarea-' + id);
            if (!ta) return;
            const res = await App.post('/hinata/api/meetgreet_save_report.php', { id, report: ta.value });
            if (res.status === 'success') {
                App.toast('レポを保存しました');
                location.reload();
            } else {
                App.toast('保存に失敗しました');
            }
        },

        // --- 削除 ---
        async deleteSlot(id) {
            if (!confirm('このスロットを削除しますか？')) return;
            const res = await App.post('/hinata/api/meetgreet_delete.php', { id });
            if (res.status === 'success') {
                App.toast('削除しました');
                location.reload();
            } else {
                App.toast('削除に失敗しました');
            }
        },

        async deleteDate(date) {
            if (!confirm('この日のミーグリ予定を全て削除しますか？')) return;
            const res = await App.post('/hinata/api/meetgreet_delete.php', { event_date: date });
            if (res.status === 'success') {
                App.toast('削除しました');
                location.reload();
            } else {
                App.toast('削除に失敗しました');
            }
        },

        // --- イベント紐づけ ---
        showLinkSelect(date) {
            const btn = document.getElementById('link-btn-' + date);
            const wrap = document.getElementById('link-select-wrap-' + date);
            const sel = document.getElementById('link-select-' + date);
            if (!btn || !wrap || !sel) return;
            btn.classList.add('hidden');
            wrap.classList.remove('hidden');
            sel.innerHTML = '<option value="">-- イベント選択 --</option>';
            const candidates = mgEvents.filter(e => e.event_date === date);
            const others = mgEvents.filter(e => e.event_date !== date);
            if (candidates.length) {
                const og = document.createElement('optgroup');
                og.label = '同日のイベント';
                candidates.forEach(e => {
                    const o = document.createElement('option');
                    o.value = e.id;
                    o.textContent = `${e.event_date} ${e.event_name}`;
                    og.appendChild(o);
                });
                sel.appendChild(og);
            }
            if (others.length) {
                const og2 = document.createElement('optgroup');
                og2.label = 'その他のMGイベント';
                others.forEach(e => {
                    const o = document.createElement('option');
                    o.value = e.id;
                    o.textContent = `${e.event_date} ${e.event_name}`;
                    og2.appendChild(o);
                });
                sel.appendChild(og2);
            }
        },

        cancelLink(date) {
            const btn = document.getElementById('link-btn-' + date);
            const wrap = document.getElementById('link-select-wrap-' + date);
            if (btn) btn.classList.remove('hidden');
            if (wrap) wrap.classList.add('hidden');
        },

        async submitLink(date) {
            const sel = document.getElementById('link-select-' + date);
            if (!sel || !sel.value) { App.toast('イベントを選択してください'); return; }
            const res = await App.post('/hinata/api/meetgreet_link_event.php', { date, event_id: parseInt(sel.value) });
            if (res.status === 'success') {
                App.toast('イベントを紐づけました');
                location.reload();
            } else {
                App.toast(res.message || '紐づけに失敗しました');
            }
        },

        // --- インポート・手動追加 ---
        switchAddTab(tab) {
            const isImport = tab === 'import';
            document.getElementById('importStep1').classList.toggle('hidden', !isImport);
            document.getElementById('importStep2').classList.add('hidden');
            document.getElementById('manualAddForm').classList.toggle('hidden', isImport);
            document.querySelectorAll('.add-tab').forEach((btn, i) => {
                const active = (tab === 'import' && i === 0) || (tab === 'manual' && i === 1);
                btn.classList.toggle('text-slate-400', !active);
                btn.style.borderColor = active ? 'var(--mg-theme)' : 'transparent';
                btn.style.color = active ? 'var(--mg-theme)' : '';
            });
            if (tab === 'manual') {
                const today = new Date().toISOString().slice(0, 10);
                document.getElementById('manualDate').value = today;
            }
        },

        openImport() {
            document.getElementById('importModal').classList.remove('hidden');
            this.switchAddTab('import');
            document.getElementById('importText').value = '';
            document.getElementById('importDate').value = '';
            document.getElementById('manualDate').value = new Date().toISOString().slice(0, 10);
            document.getElementById('manualSlot').value = '';
            document.getElementById('manualSlotOther').value = '';
            document.getElementById('manualSlotOther').classList.add('hidden');
            document.getElementById('manualMember').value = '';
            document.getElementById('manualStartTime').value = '';
            document.getElementById('manualEndTime').value = '';
            document.getElementById('manualTickets').value = '0';
        },

        closeImport() {
            document.getElementById('importModal').classList.add('hidden');
        },

        backToStep1() {
            document.getElementById('importStep1').classList.remove('hidden');
            document.getElementById('importStep2').classList.add('hidden');
        },

        parseInput() {
            const text = document.getElementById('importText').value.trim();
            if (!text) { App.toast('テキストを貼り付けてください'); return; }

            const manualDate = document.getElementById('importDate').value;
            let result;

            if (/^(当選|落選)/m.test(text)) {
                result = this._parseFortuneFormat(text);
            } else {
                result = this._parseTabFormat(text);
            }

            const dateVal = manualDate || result.detectedDate;
            if (!dateVal) {
                App.toast('日付を検出できませんでした。手動で入力してください');
                return;
            }
            if (!manualDate && result.detectedDate) {
                document.getElementById('importDate').value = result.detectedDate;
            }

            if (result.slots.length === 0) {
                App.toast('取り込めるデータがありません（落選のみ？）');
                return;
            }

            const unmatched = result.slots.filter(s => !s.member_id);
            if (unmatched.length > 0) {
                const names = [...new Set(unmatched.map(s => s.member_name_raw))];
                result.warnings.push(names.join(', ') + ' がメンバーマスタに一致しません');
            }

            this.parsedSlots = result.slots;
            this._renderPreview(dateVal, result.slots, result.warnings, result.detectedVenue);
        },

        _parseFortuneFormat(text) {
            const lines = text.split(/\r?\n/);
            const nonBlank = lines.map(l => l.trim()).filter(l => l);
            const slots = [];
            const warnings = [];
            let detectedDate = null;
            let detectedVenue = null;
            const fullToHalf = s => s.replace(/[０-９]/g, c => String.fromCharCode(c.charCodeAt(0) - 0xFEE0));

            let i = 0;
            while (i < nonBlank.length) {
                if (nonBlank[i] !== '当選' && nonBlank[i] !== '落選') { i++; continue; }

                const status = nonBlank[i];
                const dateLine = nonBlank[i + 1] || '';
                const slotLine = nonBlank[i + 2] || '';
                const memberLine = nonBlank[i + 3] || '';
                const ticketLine = nonBlank[i + 4] || '';
                i += 5;

                if (status === '落選') continue;

                const dateMatch = dateLine.match(/(\d{4})年(\d{1,2})月(\d{1,2})日/);
                if (dateMatch && !detectedDate) {
                    detectedDate = `${dateMatch[1]}-${dateMatch[2].padStart(2, '0')}-${dateMatch[3].padStart(2, '0')}`;
                }
                const venueMatch = dateLine.match(/[＠@](.+)$/);
                if (venueMatch && !detectedVenue) {
                    detectedVenue = venueMatch[1].trim();
                }

                const slotName = fullToHalf(slotLine.replace(/[＜<＞>]/g, '').trim());
                const ticketMatch = ticketLine.match(/(\d+)\s*枚/);
                const ticketCount = ticketMatch ? parseInt(ticketMatch[1], 10) : 0;

                const memberNames = memberLine.split(/[・·]/).map(n => n.trim()).filter(n => n);
                for (const name of memberNames) {
                    slots.push({
                        slot_name: slotName,
                        start_time: null,
                        end_time: null,
                        member_name_raw: name,
                        member_id: this._matchMember(name),
                        ticket_count: ticketCount,
                    });
                }
            }

            return { slots, detectedDate, detectedVenue, warnings };
        },

        _parseTabFormat(text) {
            const lines = text.split(/\r?\n/).filter(l => l.trim());
            const slots = [];
            const warnings = [];

            for (const line of lines) {
                const cols = line.split('\t');
                if (cols.length < 2) continue;

                const field1 = cols[0].trim();
                const slotMatch = field1.match(/^(第\d+部)\s+(\d{1,2}:\d{2})\s*[～〜~\-－]+\s*(\d{1,2}:\d{2})$/);
                if (slotMatch) {
                    const memberNameRaw = (cols[1] || '').trim();
                    slots.push({
                        slot_name: slotMatch[1],
                        start_time: slotMatch[2],
                        end_time: slotMatch[3],
                        member_name_raw: memberNameRaw,
                        member_id: this._matchMember(memberNameRaw),
                        ticket_count: this._parseTicketCount(cols[2] || ''),
                    });
                } else {
                    const slotOnlyMatch = field1.match(/^(第\d+部)/);
                    if (slotOnlyMatch) {
                        const memberNameRaw = (cols[1] || '').trim();
                        slots.push({
                            slot_name: slotOnlyMatch[1],
                            start_time: null,
                            end_time: null,
                            member_name_raw: memberNameRaw,
                            member_id: this._matchMember(memberNameRaw),
                            ticket_count: this._parseTicketCount(cols[2] || ''),
                        });
                    }
                }
            }

            return { slots, detectedDate: null, detectedVenue: null, warnings };
        },

        _matchMember(rawName) {
            const normalized = rawName.replace(/[\s　]+/g, '');
            for (const m of memberList) {
                if (m.name.replace(/[\s　]+/g, '') === normalized) return m.id;
            }
            return null;
        },

        _parseTicketCount(str) {
            const m = str.match(/(\d+)\s*枚/);
            return m ? parseInt(m[1], 10) : 0;
        },

        _renderPreview(dateVal, slots, warnings, venue) {
            const dt = new Date(dateVal + 'T00:00:00');
            const dow = ['日','月','火','水','木','金','土'][dt.getDay()];
            document.getElementById('previewDateLabel').textContent =
                `${dt.getFullYear()}年${dt.getMonth()+1}月${dt.getDate()}日（${dow}）`;

            const venueEl = document.getElementById('previewVenue');
            if (venue) {
                document.getElementById('previewVenueText').textContent = venue;
                venueEl.classList.remove('hidden');
            } else {
                venueEl.classList.add('hidden');
            }

            const tbody = document.getElementById('previewBody');
            tbody.innerHTML = '';
            for (const s of slots) {
                const matched = s.member_id ? memberList.find(m => m.id === s.member_id) : null;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-3 py-2 font-bold text-slate-700">${this._esc(s.slot_name)}</td>
                    <td class="px-3 py-2">
                        <span class="font-bold ${s.member_id ? 'text-slate-700' : 'text-amber-500'}">${this._esc(matched ? matched.name : s.member_name_raw)}</span>
                        ${!s.member_id ? '<span class="text-[9px] text-amber-500 ml-1">未マッチ</span>' : ''}
                    </td>
                    <td class="px-3 py-2 text-center font-bold">${s.ticket_count}枚</td>
                `;
                tbody.appendChild(tr);
            }

            const warnEl = document.getElementById('parseWarning');
            if (warnings.length > 0) {
                document.getElementById('parseWarningText').textContent = warnings.join(' / ');
                warnEl.classList.remove('hidden');
            } else {
                warnEl.classList.add('hidden');
            }

            const matchedEvent = mgEvents.find(e => e.event_date === dateVal);
            const linkSection = document.getElementById('eventLinkSection');
            if (matchedEvent) {
                this.matchedEventId = matchedEvent.id;
                const catLabel = matchedEvent.category === 3 ? 'リアルミーグリ' : 'ミーグリ';
                document.getElementById('eventLinkName').textContent = `${catLabel}: ${matchedEvent.event_name}`;
                document.getElementById('linkEventCheck').checked = true;
                linkSection.classList.remove('hidden');
            } else {
                this.matchedEventId = null;
                linkSection.classList.add('hidden');
            }

            document.getElementById('importStep1').classList.add('hidden');
            document.getElementById('importStep2').classList.remove('hidden');
        },

        async submitImport() {
            const dateVal = document.getElementById('importDate').value;
            if (!dateVal) { App.toast('日付が未設定です'); return; }
            const linkCheck = document.getElementById('linkEventCheck');
            const eventId = (this.matchedEventId && linkCheck && linkCheck.checked) ? this.matchedEventId : null;
            const res = await App.post('/hinata/api/meetgreet_import.php', {
                event_date: dateVal,
                event_id: eventId,
                slots: this.parsedSlots,
            });
            if (res.status === 'success') {
                App.toast(res.message);
                this.closeImport();
                location.reload();
            } else {
                App.toast('登録に失敗しました: ' + (res.message || ''));
            }
        },

        async submitManualAdd() {
            const dateVal = document.getElementById('manualDate').value;
            const manualEventSel = document.getElementById('manualEvent');
            const slotSel = document.getElementById('manualSlot').value;
            const slotOther = document.getElementById('manualSlotOther').value.trim();
            const memberId = document.getElementById('manualMember').value;
            const startTime = document.getElementById('manualStartTime').value;
            const endTime = document.getElementById('manualEndTime').value;
            const ticketCount = parseInt(document.getElementById('manualTickets').value || '0', 10);

            if (!dateVal) { App.toast('日付を選択してください'); return; }
            if (!memberId) { App.toast('メンバーを選択してください'); return; }

            const member = memberList.find(m => String(m.id) === memberId);

            let eventId = null;
            if (manualEventSel && manualEventSel.value) {
                eventId = parseInt(manualEventSel.value, 10);
                if (isNaN(eventId)) eventId = null;
            } else {
                const matchedEvent = mgEvents.find(e => e.event_date === dateVal);
                eventId = matchedEvent ? matchedEvent.id : null;
            }

            let slots = [];
            if (manualEventSel && manualEventSel.value) {
                // イベント選択時: 部ごとの枚数入力（時刻は不要）
                const inputs = Array.from(document.querySelectorAll('#manualRoundTicketList input[data-mg-round-slot]'));
                for (const inp of inputs) {
                    const sn = inp.getAttribute('data-mg-round-slot') || '';
                    const c = parseInt(inp.value || '0', 10);
                    if (!sn) continue;
                    if (isNaN(c) || c <= 0) continue;
                    slots.push({
                        slot_name: sn,
                        start_time: null,
                        end_time: null,
                        member_id: parseInt(memberId, 10),
                        member_name_raw: member ? member.name : null,
                        ticket_count: c,
                    });
                }
                if (slots.length === 0) { App.toast('部ごとの枚数を入力してください'); return; }
            } else {
                // イベント未選択時: 従来どおり 1件
                let slotName = slotSel === 'other' ? slotOther : slotSel;
                if (!slotName) { App.toast('部を選択または入力してください'); return; }
                slots = [{
                    slot_name: slotName,
                    start_time: startTime || null,
                    end_time: endTime || null,
                    member_id: parseInt(memberId, 10),
                    member_name_raw: member ? member.name : null,
                    ticket_count: isNaN(ticketCount) ? 0 : ticketCount,
                }];
            }

            const res = await App.post('/hinata/api/meetgreet_import.php', {
                event_date: dateVal,
                event_id: eventId,
                slots: slots,
            });
            if (res.status === 'success') {
                App.toast(res.message);
                this.closeImport();
                location.reload();
            } else {
                App.toast('登録に失敗しました: ' + (res.message || ''));
            }
        },

        _esc(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        },
    };

    document.querySelectorAll('#mgFilterBar [data-mg-filter]').forEach(btn => {
        btn.addEventListener('click', () => MG.setFilter(btn.getAttribute('data-mg-filter')));
    });

    document.getElementById('openImportBtn').onclick = () => MG.openImport();
    document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    document.getElementById('manualSlot').onchange = function() {
        const otherEl = document.getElementById('manualSlotOther');
        otherEl.classList.toggle('hidden', this.value !== 'other');
        if (this.value !== 'other') otherEl.value = '';
    };
    // 手動追加：イベント先行（イベント選択→日付自動入力）
    (function initManualEventSelect() {
        const sel = document.getElementById('manualEvent');
        if (!sel) return;
        const dateEl = document.getElementById('manualDate');
        const slotWrap = document.getElementById('manualSlot')?.closest('div');
        const timeGrid = document.getElementById('manualTimeGrid');
        const singleTickets = document.getElementById('manualSingleTickets');
        const roundTickets = document.getElementById('manualRoundTickets');
        const roundList = document.getElementById('manualRoundTicketList');

        const fmt = (e) => {
            const catLabel = e.category === 3 ? 'リアルミーグリ' : (e.category === 2 ? 'オンラインミーグリ' : 'ミーグリ');
            const d = e.event_date ? ` ${e.event_date}` : '';
            return `${catLabel}: ${e.event_name}${d}`;
        };
        // 一覧を日付降順（新しい→古い）に寄せる
        const sorted = mgEvents.slice().sort((a, b) => String(b.event_date || '').localeCompare(String(a.event_date || '')));
        for (const e of sorted) {
            const opt = document.createElement('option');
            opt.value = String(e.id);
            opt.textContent = fmt(e);
            sel.appendChild(opt);
        }

        const buildRoundInputs = (slotNames) => {
            if (!roundList) return;
            roundList.innerHTML = '';
            for (const sn of slotNames) {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3';
                row.innerHTML = `
                    <div class="w-20 shrink-0 text-sm font-bold text-slate-700">${MG._esc(sn)}</div>
                    <div class="flex-1">
                        <input type="number" min="0" value="0" data-mg-round-slot="${MG._esc(sn)}"
                               class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2"
                               style="focus:ring-color: var(--mg-theme);" placeholder="枚数">
                    </div>
                `;
                roundList.appendChild(row);
            }
        };

        const setEventMode = async (eventId) => {
            const isOn = !!eventId;
            if (dateEl) {
                dateEl.disabled = isOn;
                dateEl.classList.toggle('bg-slate-100', isOn);
                dateEl.classList.toggle('cursor-not-allowed', isOn);
            }
            if (slotWrap) slotWrap.classList.toggle('hidden', isOn);
            if (timeGrid) timeGrid.classList.toggle('hidden', isOn); // 要望: イベント選択時は時刻不要
            if (singleTickets) singleTickets.classList.toggle('hidden', isOn);
            if (roundTickets) roundTickets.classList.toggle('hidden', !isOn);

            if (!isOn) return;
            const ev = mgEvents.find(e => String(e.id) === String(eventId));
            if (ev && ev.event_date && dateEl) {
                dateEl.value = ev.event_date;
            }

            // 部の候補: mg_rounds があれば第1部..第N部、なければイベントに紐づく既存スロットから推測
            let slotNames = [];
            const rounds = ev ? (parseInt(ev.mg_rounds, 10) || 0) : 0;
            if (rounds > 0) {
                slotNames = Array.from({ length: rounds }, (_, i) => `第${i + 1}部`);
            } else {
                try {
                    const resp = await fetch('/hinata/api/meetgreet_event_slots.php?event_id=' + encodeURIComponent(String(eventId)));
                    const res = await resp.json();
                    if (res && res.status === 'success' && Array.isArray(res.slots)) {
                        const set = new Set();
                        res.slots.forEach(s => { if (s && s.slot_name) set.add(String(s.slot_name)); });
                        slotNames = Array.from(set);
                    }
                } catch (e) { /* ignore */ }
                if (slotNames.length === 0) {
                    slotNames = ['第1部','第2部','第3部','第4部','第5部','第6部'];
                }
            }
            buildRoundInputs(slotNames);
        };

        sel.onchange = function() {
            setEventMode(this.value || '');
        };

        // 初期状態
        setEventMode('');
    })();

    // 保存済みのアコーディオン状態を復元 + ダッシュボード等からのフォーカス
    function mgOpenDateAccordion(dateStr) {
        const el = document.getElementById('content-' + dateStr);
        const icon = document.getElementById('icon-' + dateStr);
        if (el && el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            if (icon) icon.classList.add('rotate-180');
        }
    }

    window.onload = () => {
        const opened = JSON.parse(localStorage.getItem('mg_opened_dates') || '[]');
        opened.forEach(date => {
            if (MG.mgFilter === 'future' && date < mgToday) return;
            if (MG.mgFilter === 'past' && date >= mgToday) return;
            mgOpenDateAccordion(date);
        });

        const p = new URLSearchParams(window.location.search);
        const focusSlot = (p.get('focus_slot_id') || '').replace(/\D/g, '');
        const focusDate = p.get('focus_event_date') || '';
        if (focusSlot) {
            setTimeout(() => {
                const row = document.querySelector('.slot-row[data-slot-id="' + focusSlot + '"]');
                if (row) {
                    const grp = row.closest('[data-date-group]');
                    const d = grp ? grp.getAttribute('data-date-group') : '';
                    if (d && d < mgToday) {
                        if (mgHasFuture && mgHasPast) MG.setFilter('all');
                        else if (mgHasPast) MG.setFilter('past');
                    } else if (d) {
                        MG.setFilter('future');
                    }
                    if (grp) mgOpenDateAccordion(grp.getAttribute('data-date-group'));
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('ring-2', 'ring-sky-400', 'bg-sky-50/70');
                    setTimeout(() => row.classList.remove('ring-2', 'ring-sky-400', 'bg-sky-50/70'), 2400);
                }
                window.history.replaceState({}, '', '/hinata/meetgreet.php');
            }, 400);
        } else if (/^\d{4}-\d{2}-\d{2}$/.test(focusDate)) {
            setTimeout(() => {
                if (focusDate < mgToday) {
                    if (mgHasFuture && mgHasPast) MG.setFilter('all');
                    else if (mgHasPast) MG.setFilter('past');
                } else {
                    MG.setFilter('future');
                }
                mgOpenDateAccordion(focusDate);
                const grp = document.querySelector('[data-date-group="' + focusDate + '"]');
                if (grp) grp.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.history.replaceState({}, '', '/hinata/meetgreet.php');
            }, 400);
        }
    };
    </script>
</body>
</html>
