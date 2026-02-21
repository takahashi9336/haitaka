<?php
/**
 * ミーグリ予定・レポ View
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$today = date('Y-m-d');
$dayNames = ['日','月','火','水','木','金','土'];
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
                <h1 class="font-bold text-slate-700 text-sm truncate">ミーグリ予定</h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button id="openImportBtn" class="text-xs font-bold px-3 py-1.5 rounded-full <?= $cardIconText ?> <?= $cardIconBg ?> hover:opacity-90"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>+ 予定を追加</button>
            </div>
        </header>

        <div id="scrollContainer" class="flex-1 overflow-y-auto p-4 pb-24">
            <div class="max-w-3xl mx-auto w-full space-y-3">
                <?php if (empty($groupedSlots)): ?>
                    <p class="text-center text-slate-400 text-xs py-16 tracking-wider">ミーグリ予定がありません</p>
                <?php else: ?>
                    <?php
                    $firstFuture = null;
                    foreach ($groupedSlots as $date => $slots) {
                        if ($date >= $today && $firstFuture === null) $firstFuture = $date;
                    }
                    if ($firstFuture === null) {
                        $dates = array_keys($groupedSlots);
                        $firstFuture = end($dates);
                    }
                    ?>
                    <?php foreach ($groupedSlots as $date => $slots):
                        $dt = new DateTime($date);
                        $dow = $dayNames[(int)$dt->format('w')];
                        $dateLabel = $dt->format('Y年n月j日') . "（{$dow}）";
                        $isPast = $date < $today;
                        $isOpen = ($date === $firstFuture);
                        $totalTickets = array_sum(array_column($slots, 'ticket_count'));
                        $hasReport = false;
                        $linkedEventName = null;
                        foreach ($slots as $s) {
                            if (!empty($s['report'])) $hasReport = true;
                            if (!empty($s['linked_event_name'])) $linkedEventName = $s['linked_event_name'];
                        }
                    ?>
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm" data-date-group="<?= $date ?>">
                        <div class="accordion-trigger flex items-center justify-between px-5 py-4 <?= $isPast ? 'opacity-60' : '' ?>"
                             onclick="MG.toggleDate('<?= $date ?>')">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-slate-700"><?= $dateLabel ?></span>
                                <span class="text-[10px] text-slate-400 font-bold px-2 py-0.5 bg-slate-100 rounded-full"><?= count($slots) ?>部</span>
                                <?php if ($totalTickets > 0): ?>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background: color-mix(in srgb, var(--mg-theme) 10%, white); color: var(--mg-theme);"><?= $totalTickets ?>枚</span>
                                <?php endif; ?>
                                <?php if ($linkedEventName): ?>
                                    <span class="text-[10px] font-bold text-emerald-600 px-2 py-0.5 bg-emerald-50 rounded-full"><i class="fa-solid fa-link mr-0.5"></i><?= htmlspecialchars($linkedEventName) ?></span>
                                <?php endif; ?>
                                <?php if ($hasReport): ?>
                                    <i class="fa-solid fa-pen-to-square text-xs" style="color: var(--mg-theme);"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <i id="icon-<?= $date ?>" class="fa-solid fa-chevron-down text-slate-300 text-xs transition-transform duration-300 <?= $isOpen ? 'rotate-180' : '' ?>"></i>
                            </div>
                        </div>

                        <div id="content-<?= $date ?>" class="border-t border-slate-100 <?= $isOpen ? '' : 'hidden' ?>">
                            <!-- テーブルヘッダ -->
                            <div class="grid grid-cols-12 gap-2 px-5 py-2 bg-slate-50 text-[10px] font-bold text-slate-400 tracking-wider">
                                <div class="col-span-3">時間帯</div>
                                <div class="col-span-4">メンバー</div>
                                <div class="col-span-2 text-center">枚数</div>
                                <div class="col-span-3 text-right">操作</div>
                            </div>

                            <?php foreach ($slots as $slot): ?>
                            <div class="slot-row border-t border-slate-50" data-slot-id="<?= $slot['id'] ?>">
                                <div class="grid grid-cols-12 gap-2 px-5 py-3 items-center">
                                    <div class="col-span-3">
                                        <div class="text-xs font-bold text-slate-700"><?= htmlspecialchars($slot['slot_name']) ?></div>
                                        <?php if ($slot['start_time'] && $slot['end_time']): ?>
                                            <div class="text-[10px] text-slate-400"><?= substr($slot['start_time'], 0, 5) ?>～<?= substr($slot['end_time'], 0, 5) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-span-4">
                                        <?php
                                        $displayName = $slot['member_name'] ?? $slot['member_name_raw'] ?? '不明';
                                        $color1 = $slot['color1'] ?? '#94a3b8';
                                        ?>
                                        <span class="text-sm font-bold" style="color: <?= htmlspecialchars($color1) ?>;"><?= htmlspecialchars($displayName) ?></span>
                                        <?php if (!$slot['member_id'] && $slot['member_name_raw']): ?>
                                            <span class="text-[9px] text-amber-500 font-bold ml-1">未マッチ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-span-2 text-center">
                                        <span class="text-sm font-bold text-slate-600"><?= (int)$slot['ticket_count'] ?><span class="text-[10px] text-slate-400">枚</span></span>
                                    </div>
                                    <div class="col-span-3 flex items-center justify-end gap-2">
                                        <button onclick="MG.openReport(<?= $slot['id'] ?>)" class="text-[10px] font-bold px-2 py-1 rounded-md transition <?= !empty($slot['report']) ? 'text-white' : 'text-slate-500 bg-slate-100 hover:bg-slate-200' ?>"
                                                <?= !empty($slot['report']) ? 'style="background: var(--mg-theme);"' : '' ?>>
                                            <i class="fa-solid fa-pen-to-square mr-0.5"></i><?= !empty($slot['report']) ? 'レポ' : 'レポを書く' ?>
                                        </button>
                                        <button onclick="MG.deleteSlot(<?= $slot['id'] ?>)" class="text-slate-300 hover:text-red-400 p-1 transition" title="削除">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- レポ表示エリア -->
                                <?php if (!empty($slot['report'])): ?>
                                <div id="report-display-<?= $slot['id'] ?>" class="px-5 pb-3">
                                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-3 text-xs text-slate-600 leading-relaxed">
                                        <?= nl2br(htmlspecialchars($slot['report'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- レポ編集エリア（非表示） -->
                                <div id="report-edit-<?= $slot['id'] ?>" class="px-5 pb-3 hidden">
                                    <textarea id="report-textarea-<?= $slot['id'] ?>" class="report-area w-full border border-slate-200 rounded-lg p-3 text-xs outline-none focus:ring-2 resize-y" style="focus:ring-color: var(--mg-theme);"
                                              placeholder="レポ・メモを記入..."><?= htmlspecialchars($slot['report'] ?? '') ?></textarea>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button onclick="MG.cancelReport(<?= $slot['id'] ?>)" class="text-[10px] font-bold text-slate-400 px-3 py-1.5 rounded-md hover:bg-slate-100 transition">キャンセル</button>
                                        <button onclick="MG.saveReport(<?= $slot['id'] ?>)" class="text-[10px] font-bold text-white px-3 py-1.5 rounded-md transition" style="background: var(--mg-theme);">保存</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <!-- 日付一括削除 -->
                            <div class="px-5 py-2 border-t border-slate-50 flex justify-end">
                                <button onclick="MG.deleteDate('<?= $date ?>')" class="text-[10px] text-slate-300 hover:text-red-400 font-bold transition">
                                    <i class="fa-solid fa-trash-can mr-1"></i>この日の予定を全て削除
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <!-- Step 1: 入力 -->
                    <div id="importStep1">
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-2">forTUNE meetsの当選結果を貼り付け</label>
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
    const mgEvents = <?= json_encode(array_map(fn($e) => ['id' => $e['id'], 'event_name' => $e['event_name'], 'event_date' => $e['event_date'], 'category' => (int)$e['category']], $mgEvents), JSON_UNESCAPED_UNICODE) ?>;

    const MG = {
        parsedSlots: [],
        matchedEventId: null,

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

        // --- インポート ---
        openImport() {
            document.getElementById('importModal').classList.remove('hidden');
            document.getElementById('importStep1').classList.remove('hidden');
            document.getElementById('importStep2').classList.add('hidden');
            document.getElementById('importText').value = '';
            document.getElementById('importDate').value = '';
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

        _esc(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        },
    };

    document.getElementById('openImportBtn').onclick = () => MG.openImport();
    document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

    // 保存済みのアコーディオン状態を復元
    window.onload = () => {
        const opened = JSON.parse(localStorage.getItem('mg_opened_dates') || '[]');
        opened.forEach(date => {
            const el = document.getElementById('content-' + date);
            const icon = document.getElementById('icon-' + date);
            if (el && el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if (icon) icon.classList.add('rotate-180');
            }
        });
    };
    </script>
</body>
</html>
