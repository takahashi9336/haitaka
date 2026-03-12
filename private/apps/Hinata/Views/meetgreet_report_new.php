<?php
/**
 * ミーグリ レポ 新規作成フォーム（スロット未指定時）
 * メンバー・日付・部名を選択してスロットを作成→レポページへリダイレクト
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レポ新規作成 - Hinata Portal</title>
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
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-xl"></i></button>
                <a href="/hinata/meetgreet.php" class="text-slate-400 p-2 shrink-0 transition hover:opacity-80"><i class="fa-solid fa-chevron-left"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md shrink-0" style="background: var(--mg-theme);">
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                </div>
                <h1 class="font-bold text-slate-700 text-sm">レポ新規作成</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4">
            <div class="max-w-lg mx-auto">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="font-bold text-slate-700 text-sm flex items-center gap-2">
                            <i class="fa-solid fa-handshake text-xs" style="color: var(--mg-theme);"></i>
                            ミーグリ情報を入力
                        </h2>
                        <p class="text-[10px] text-slate-400 mt-1">新しいミーグリ予定を作成してレポを書きます</p>
                    </div>
                    <div class="px-5 py-5 space-y-5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1.5">メンバー <span class="text-red-400">*</span></label>
                            <select id="memberSelect" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);">
                                <?php
                                $memberSelectBlankLabel = 'メンバーを選択';
                                require __DIR__ . '/partials/member_select_options.php';
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1.5">日付 <span class="text-red-400">*</span></label>
                            <input type="date" id="eventDate" value="<?= htmlspecialchars($eventData['event_date'] ?? date('Y-m-d')) ?>" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1.5">イベント（任意）</label>
                            <select id="eventSelect" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);" onchange="onEventSelect(this)">
                                <option value="">イベントを選択（紐づけない場合は空欄）</option>
                                <?php foreach ($mgEvents as $ev):
                                    $evDate = date('n/j', strtotime($ev['event_date']));
                                    $catLabel = (int)$ev['category'] === 2 ? 'MG' : 'リアルMG';
                                    $rounds = (int)($ev['mg_rounds'] ?? 0);
                                    $selected = ($eventData && (int)$eventData['id'] === (int)$ev['id']) ? ' selected' : '';
                                ?>
                                <option value="<?= (int)$ev['id'] ?>" data-date="<?= htmlspecialchars($ev['event_date']) ?>" data-rounds="<?= $rounds ?>"<?= $selected ?>>
                                    <?= $evDate ?> <?= htmlspecialchars($ev['event_name']) ?>（<?= $catLabel ?>）
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 部選択セクション（mg_rounds がある場合に表示） -->
                        <div id="roundsSection" class="hidden">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1.5">参加する部を選択</label>
                            <div id="roundsCheckboxList" class="space-y-2"></div>
                        </div>

                        <!-- 既存部セクション（mg_rounds がない場合、イベント選択時に表示） -->
                        <div id="existingSlotsSection" class="hidden">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1.5">登録済みの部</label>
                            <div id="existingSlotsList" class="space-y-2"></div>
                        </div>

                        <!-- 部名入力セクション（mg_rounds がない場合に表示） -->
                        <div id="newSlotSection">
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1.5">部名</label>
                            <input type="text" id="slotName" value="1部" placeholder="例: 1部, 2部" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);">
                        </div>

                        <div class="space-y-3">
                            <button onclick="registerSchedule()" id="registerBtn" class="hidden w-full h-11 rounded-lg font-bold text-white text-sm transition active:scale-95 flex items-center justify-center gap-2" style="background: var(--mg-theme);">
                                <i class="fa-solid fa-calendar-plus"></i>参加予定を登録
                            </button>
                            <button onclick="createSlotAndGo()" id="createBtn" class="w-full h-11 rounded-lg font-bold text-sm transition active:scale-95 flex items-center justify-center gap-2 border-2" style="border-color: var(--mg-theme); color: var(--mg-theme);">
                                <i class="fa-solid fa-pen-to-square"></i>作成してレポを書く
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="/hinata/meetgreet.php" class="text-xs text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-arrow-left mr-1"></i>スケジュールに戻る
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
    let cachedSlots = {};
    let cachedExistingSlots = [];
    let currentRounds = 0;

    async function onEventSelect(sel) {
        const opt = sel.options[sel.selectedIndex];
        const eventId = sel.value;

        if (opt && opt.dataset.date) {
            document.getElementById('eventDate').value = opt.dataset.date;
        }

        currentRounds = opt ? parseInt(opt.dataset.rounds) || 0 : 0;

        if (!eventId) {
            resetSections();
            return;
        }

        let existingSlots = [];
        try {
            const resp = await fetch('/hinata/api/meetgreet_event_slots.php?event_id=' + eventId);
            const res = await resp.json();
            if (res.status === 'success') existingSlots = res.slots || [];
        } catch (e) { /* ignore */ }

        cachedSlots = {};
        existingSlots.forEach(s => { cachedSlots[s.id] = s; });
        cachedExistingSlots = existingSlots;

        renderSections();
    }

    function resetSections() {
        document.getElementById('roundsSection').classList.add('hidden');
        document.getElementById('existingSlotsSection').classList.add('hidden');
        document.getElementById('newSlotSection').classList.remove('hidden');
        document.getElementById('roundsCheckboxList').innerHTML = '';
        document.getElementById('existingSlotsList').innerHTML = '';
        document.getElementById('registerBtn').classList.add('hidden');
        document.getElementById('createBtn').classList.remove('hidden');
        document.getElementById('createBtn').style.cssText = 'background: var(--mg-theme); color: #fff;';
        document.getElementById('createBtn').className = document.getElementById('createBtn').className.replace('border-2', '');
        cachedSlots = {};
        cachedExistingSlots = [];
    }

    function renderSections() {
        const selectedMemberId = parseInt(document.getElementById('memberSelect').value) || 0;
        const roundsSection = document.getElementById('roundsSection');
        const existingSection = document.getElementById('existingSlotsSection');
        const newSlotSection = document.getElementById('newSlotSection');
        const registerBtn = document.getElementById('registerBtn');
        const createBtn = document.getElementById('createBtn');

        if (currentRounds > 0) {
            buildRoundsCheckboxes(selectedMemberId);
            roundsSection.classList.remove('hidden');
            existingSection.classList.add('hidden');
            newSlotSection.classList.add('hidden');
            registerBtn.classList.remove('hidden');
            createBtn.classList.add('hidden');
        } else {
            roundsSection.classList.add('hidden');
            newSlotSection.classList.remove('hidden');
            registerBtn.classList.add('hidden');
            createBtn.classList.remove('hidden');
            createBtn.style.cssText = 'background: var(--mg-theme); color: #fff;';
            buildExistingSlotsList();
        }
    }

    function buildRoundsCheckboxes(selectedMemberId) {
        const slotNameMap = {};
        cachedExistingSlots.forEach(s => {
            if (!selectedMemberId || s.member_id === selectedMemberId) {
                slotNameMap[s.slot_name] = s;
            }
        });

        let html = '';
        for (let i = 1; i <= currentRounds; i++) {
            const name = i + '部';
            const existing = slotNameMap[name];
            const hasReport = existing && existing.report_count > 0;

            let statusHtml = '';
            let actionHtml = '';

            if (hasReport) {
                statusHtml = '<span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-check mr-0.5"></i>レポ済</span>';
                actionHtml = `<a href="/hinata/meetgreet_report.php?slot_id=${existing.id}" onclick="event.stopPropagation()" class="text-[10px] font-bold px-2 py-0.5 rounded-full transition" style="color: var(--mg-theme);">レポ <i class="fa-solid fa-chevron-right text-[8px]"></i></a>`;
            } else if (existing) {
                statusHtml = '<span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">登録済み</span>';
                actionHtml = `<a href="/hinata/meetgreet_report.php?slot_id=${existing.id}" onclick="event.stopPropagation()" class="text-[10px] font-bold px-2 py-0.5 rounded-full transition" style="color: var(--mg-theme);">レポを書く <i class="fa-solid fa-chevron-right text-[8px]"></i></a>`;
            }

            const checked = existing ? 'checked disabled' : '';
            html += `<label class="flex items-center gap-3 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-white transition">
                <input type="checkbox" name="round" value="${escHtml(name)}" ${checked} class="w-4 h-4 rounded accent-[var(--mg-theme)]">
                <span class="flex-1 text-sm font-medium text-slate-700">${escHtml(name)}</span>
                <div class="flex items-center gap-2">${statusHtml}${actionHtml}</div>
            </label>`;
        }
        document.getElementById('roundsCheckboxList').innerHTML = html;
    }

    function buildExistingSlotsList() {
        const section = document.getElementById('existingSlotsSection');
        const list = document.getElementById('existingSlotsList');
        if (cachedExistingSlots.length === 0) {
            section.classList.add('hidden');
            list.innerHTML = '';
            return;
        }
        let html = '';
        cachedExistingSlots.forEach(s => {
            const memberLabel = s.member_name ? `<span class="text-slate-400 text-[10px] ml-1">${escHtml(s.member_name)}</span>` : '';
            const reportBadge = s.report_count > 0
                ? `<span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-check mr-0.5"></i>レポ済</span>`
                : '';
            html += `<button type="button" onclick="goToSlot(${s.id})"
                class="w-full flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm hover:border-[color:var(--mg-theme)] hover:bg-white transition group">
                <span class="font-medium text-slate-700">${escHtml(s.slot_name)}${memberLabel}</span>
                <div class="flex items-center gap-2">
                    ${reportBadge}
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-[color:var(--mg-theme)] transition"></i>
                </div>
            </button>`;
        });
        list.innerHTML = html;
        section.classList.remove('hidden');
    }

    function goToSlot(slotId) {
        location.href = '/hinata/meetgreet_report.php?slot_id=' + slotId;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    async function registerSchedule() {
        const memberId = parseInt(document.getElementById('memberSelect').value);
        const eventDate = document.getElementById('eventDate').value;
        const eventId = parseInt(document.getElementById('eventSelect').value) || null;

        if (!memberId) { App.toast('メンバーを選択してください'); return; }
        if (!eventDate) { App.toast('日付を入力してください'); return; }

        const checks = document.querySelectorAll('#roundsCheckboxList input[type="checkbox"]:checked:not(:disabled)');
        if (checks.length === 0) { App.toast('参加する部を選択してください'); return; }

        const btn = document.getElementById('registerBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>登録中...';

        try {
            for (const cb of checks) {
                await App.post('/hinata/api/meetgreet_create_slot.php', {
                    member_id: memberId,
                    event_date: eventDate,
                    slot_name: cb.value,
                    event_id: eventId,
                });
            }
            App.toast(checks.length + '件の参加予定を登録しました');
            location.href = '/hinata/meetgreet.php';
        } catch (e) {
            App.toast('登録に失敗しました');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-calendar-plus"></i>参加予定を登録';
        }
    }

    async function createSlotAndGo() {
        const memberId = document.getElementById('memberSelect').value;
        const eventDate = document.getElementById('eventDate').value;
        const slotName = document.getElementById('slotName').value.trim() || '1部';
        const eventSelect = document.getElementById('eventSelect');
        const eventId = eventSelect ? parseInt(eventSelect.value) || null : null;

        if (!memberId) { App.toast('メンバーを選択してください'); return; }
        if (!eventDate) { App.toast('日付を入力してください'); return; }

        const btn = document.getElementById('createBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>作成中...';

        try {
            const res = await App.post('/hinata/api/meetgreet_create_slot.php', {
                member_id: parseInt(memberId),
                event_date: eventDate,
                slot_name: slotName,
                event_id: eventId,
            });
            if (res.status === 'success' && res.slot_id) {
                location.href = '/hinata/meetgreet_report.php?slot_id=' + res.slot_id;
            } else {
                App.toast(res.message || '作成に失敗しました');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>作成してレポを書く';
            }
        } catch (e) {
            App.toast('作成に失敗しました');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>作成してレポを書く';
        }
    }

    document.getElementById('memberSelect').addEventListener('change', () => {
        if (currentRounds > 0 && cachedExistingSlots.length >= 0) {
            renderSections();
        }
    });

    (function() {
        const sel = document.getElementById('eventSelect');
        if (sel && sel.value) onEventSelect(sel);
    })();

    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.add('mobile-open');
    });
    </script>
</body>
</html>
