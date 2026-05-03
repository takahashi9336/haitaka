<?php
$appKey = 'live_trip';
$isEdit = isset($trip) && !empty($trip['id']);
$tripEvents = $trip['events'] ?? [];
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? '遠征を編集' : '遠征を追加' ?> - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --lt-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .lt-theme-btn { background-color: var(--lt-theme); }
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { width: 240px; }
        @media (max-width: 768px) { .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; } .sidebar.mobile-open { transform: translateX(0); } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 overflow-auto">
    <header class="h-16 bg-white border-b border-slate-200 flex items-center px-6 shrink-0">
        <a href="<?= $isEdit ? '/live_trip/show.php?id=' . (int)$trip['id'] : '/live_trip/' ?>" class="text-slate-500 hover:text-slate-700 mr-4"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="font-black text-slate-700"><?= $isEdit ? '遠征を編集' : '遠征を追加' ?></h1>
    </header>

    <div class="p-6 max-w-2xl">

        <form method="post" action="<?= $isEdit ? '/live_trip/update.php' : '/live_trip/store.php' ?>" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$trip['id'] ?>">
            <?php endif; ?>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-600 mb-2">遠征タイトル <span class="text-red-500">*</span></label>
                <input type="text" name="title" required maxlength="255"
                       value="<?= htmlspecialchars($trip['title'] ?? $_POST['title'] ?? '') ?>"
                       placeholder="例: 2026春ツアー 横浜2days遠征"
                       class="w-full border border-slate-200 rounded-lg px-4 py-2">
            </div>

            <div class="mb-6">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <label class="text-sm font-bold text-slate-600">イベント</label>
                    <span class="text-sm text-slate-500 shrink-0">任意</span>
                </div>
                <div id="events-container" class="space-y-4">
                    <?php if ($isEdit && !empty($tripEvents)): ?>
                        <?php foreach ($tripEvents as $i => $ev): ?>
                        <div class="event-row flex flex-wrap gap-2 items-start p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <input type="hidden" name="events[<?= $i ?>][event_type]" value="<?= htmlspecialchars($ev['event_type']) ?>">
                            <?php if ($ev['event_type'] === 'hinata'): ?>
                            <input type="hidden" name="events[<?= $i ?>][hn_event_id]" value="<?= (int)$ev['hn_event_id'] ?>">
                            <input type="hidden" name="events[<?= $i ?>][lt_event_id]" value="">
                            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($ev['event_name'] ?? '') ?> (<?= htmlspecialchars($ev['event_date'] ?? '') ?>)</span>
                            <div class="flex-1 min-w-[200px]"><input type="text" name="events[<?= $i ?>][seat_info]" value="<?= htmlspecialchars($ev['seat_info'] ?? '') ?>" placeholder="座席" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div class="flex-1 min-w-[200px]"><input type="text" name="events[<?= $i ?>][impression]" value="<?= htmlspecialchars($ev['impression'] ?? '') ?>" placeholder="感想" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <?php else: ?>
                            <input type="hidden" name="events[<?= $i ?>][hn_event_id]" value="">
                            <input type="hidden" name="events[<?= $i ?>][lt_event_id]" value="<?= (int)$ev['lt_event_id'] ?>">
                            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($ev['event_name'] ?? '') ?> (<?= htmlspecialchars($ev['event_date'] ?? '') ?>)</span>
                            <div class="flex-1 min-w-[200px]"><input type="text" name="events[<?= $i ?>][seat_info]" value="<?= htmlspecialchars($ev['seat_info'] ?? '') ?>" placeholder="座席" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div class="flex-1 min-w-[200px]"><input type="text" name="events[<?= $i ?>][impression]" value="<?= htmlspecialchars($ev['impression'] ?? '') ?>" placeholder="感想" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <?php endif; ?>
                            <button type="button" class="event-remove-btn text-red-500 hover:text-red-700 text-sm" title="削除"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="event-rows-add" class="mt-3 space-y-4"></div>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <button type="button" id="add-event-btn" class="text-emerald-600 hover:underline font-bold text-sm"><i class="fa-solid fa-plus mr-1"></i>イベントを追加</button>
                    <a href="/live_trip/lt_event_create.php?redirect=<?= urlencode($isEdit ? '/live_trip/edit.php?id=' . (int)$trip['id'] : '/live_trip/create.php') ?>" class="text-slate-500 hover:text-slate-700 text-sm">+ 汎用イベントを新規登録</a>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap justify-end gap-3">
                <?php $cancelUrl = $isEdit ? '/live_trip/show.php?id=' . (int)$trip['id'] : '/live_trip/'; ?>
                <a href="<?= htmlspecialchars($cancelUrl) ?>" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">キャンセル</a>
                <button type="submit" class="lt-theme-btn text-white px-6 py-2 rounded-lg font-bold"><?= $isEdit ? '更新' : '登録' ?></button>
            </div>
        </form>
    </div>
</main>

<script>
(function() {
    const hinataEvents = <?= json_encode($hinataEvents ?? []) ?>;
    const ltEvents = <?= json_encode($ltEvents ?? []) ?>;
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    let eventIdx = <?= $isEdit ? count($tripEvents) : 0 ?>;

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    /** @param {string|undefined} s */
    function parseYmdParts(s) {
        if (!s || typeof s !== 'string') return null;
        const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return null;
        return { y: +m[1], mo: +m[2], d: +m[3] };
    }
    function compareEvents(a, b) {
        const pa = parseYmdParts(a.event_date);
        const pb = parseYmdParts(b.event_date);
        if (pa && pb) {
            if (pa.y !== pb.y) return pa.y - pb.y;
            if (pa.mo !== pb.mo) return pa.mo - pb.mo;
            return pa.d - pb.d;
        }
        if (pa && !pb) return -1;
        if (!pa && pb) return 1;
        return String(a.event_name || '').localeCompare(String(b.event_name || ''), 'ja');
    }

    /** 日向坂 hn_events.category（絵文字のみ。ラベル文言は出さない） */
    const HINATA_CATEGORY_EMOJI = {
        1: '🎵',
        2: '🤝',
        3: '👥',
        4: '💿',
        5: '📺',
        6: '⭐',
        99: '📌'
    };

    /**
     * 表示例: 09/05 🎵 ひなたフェスday1
     * @param {{event_name?:string,event_date?:string,category?:number|string}} e
     * @param {'hinata'|'generic'} source
     */
    function formatEventOptionText(e, source) {
        const name = e.event_name != null ? String(e.event_name) : '';
        const p = parseYmdParts(e.event_date);
        const dateStr = p
            ? (String(p.mo).padStart(2, '0') + '/' + String(p.d).padStart(2, '0'))
            : '日付未設定';
        let badge;
        if (source === 'hinata') {
            const c = parseInt(String(e.category != null ? e.category : ''), 10);
            badge = HINATA_CATEGORY_EMOJI[c] || '📌';
        } else {
            badge = '🗓️';
        }
        return dateStr + ' ' + badge + ' ' + name;
    }
    function groupEventsByMonth(events) {
        const groups = {};
        for (const e of events || []) {
            const p = parseYmdParts(e.event_date);
            const key = p ? (String(p.y) + '-' + String(p.mo).padStart(2, '0')) : 'z_undated';
            if (!groups[key]) groups[key] = [];
            groups[key].push(e);
        }
        Object.keys(groups).forEach(function(k) { groups[k].sort(compareEvents); });
        return groups;
    }
    function sortMonthKeys(keys) {
        const rest = keys.filter(function(k) { return k !== 'z_undated'; }).sort();
        if (keys.indexOf('z_undated') !== -1) rest.push('z_undated');
        return rest;
    }
    function monthLabelForKey(key) {
        if (key === 'z_undated') return '日付未設定';
        const parts = key.split('-');
        return parts[0] + '年' + String(parseInt(parts[1], 10)) + '月';
    }
    /**
     * @param {Array<{id:number|string,event_name?:string,event_date?:string,category?:number|string}>} events
     * @param {'hinata'|'generic'} source
     */
    function buildEventSelectInnerHtml(events, source) {
        let html = '<option value="">イベントを選択</option>';
        const groups = groupEventsByMonth(events);
        const keys = sortMonthKeys(Object.keys(groups));
        for (let i = 0; i < keys.length; i++) {
            const key = keys[i];
            const list = groups[key] || [];
            if (!list.length) continue;
            html += '<optgroup label="' + escapeHtml(monthLabelForKey(key)) + '">';
            for (let j = 0; j < list.length; j++) {
                const e = list[j];
                html += '<option value="' + String(e.id) + '">' + escapeHtml(formatEventOptionText(e, source)) + '</option>';
            }
            html += '</optgroup>';
        }
        return html;
    }

    function buildEventRow() {
        const id = eventIdx++;
        const div = document.createElement('div');
        div.className = 'event-row flex flex-wrap gap-2 items-start p-3 bg-slate-50 rounded-lg border border-slate-200';
        const seatImpressionFields = isEdit
            ? `<div class="flex-1 min-w-[100px]"><input type="text" name="events[${id}][seat_info]" placeholder="座席" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                <div class="flex-1 min-w-[100px]"><input type="text" name="events[${id}][impression]" placeholder="感想" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>`
            : '';
        div.innerHTML = `
            <div class="w-full flex flex-col gap-3">
                <div class="flex items-center gap-2 p-1 rounded-xl bg-slate-100 border border-slate-200 w-full">
                    <button type="button" class="event-type-toggle flex-1 px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white" data-type="hinata">🩵日向坂</button>
                    <button type="button" class="event-type-toggle flex-1 px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white" data-type="generic">🗓️その他</button>
                </div>
                <input type="hidden" name="events[${id}][event_type]" class="event-type-input" value="hinata">
                <div class="flex flex-wrap gap-2 items-start w-full">
                    <div class="event-select-wrap hinata-select flex-1 min-w-[160px]">
                        <select name="events[${id}][hn_event_id]" class="hn-event-select w-full border border-slate-200 rounded px-2 py-1 text-sm">
                            ${buildEventSelectInnerHtml(hinataEvents, 'hinata')}
                        </select>
                    </div>
                    <div class="event-select-wrap generic-select flex-1 min-w-[160px]" style="display:none">
                        <select name="events[${id}][lt_event_id]" class="lt-event-select w-full border border-slate-200 rounded px-2 py-1 text-sm">
                            ${buildEventSelectInnerHtml(ltEvents, 'generic')}
                        </select>
                    </div>
                    ${seatImpressionFields}
                    <button type="button" class="event-remove-btn text-red-500 hover:text-red-700 text-sm shrink-0 self-center" title="削除"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>
        `;
        const typeInput = div.querySelector('.event-type-input');
        const typeBtns = div.querySelectorAll('.event-type-toggle');
        const hinataWrap = div.querySelector('.hinata-select');
        const genericWrap = div.querySelector('.generic-select');
        const hnSel = div.querySelector('.hn-event-select');
        const ltSel = div.querySelector('.lt-event-select');
        function setType(t) {
            typeInput.value = t;
            typeBtns.forEach(bt => {
                const active = bt.dataset.type === t;
                bt.classList.toggle('bg-white', active);
                bt.classList.toggle('shadow-sm', active);
                bt.classList.toggle('text-slate-800', active);
                bt.classList.toggle('text-slate-600', !active);
            });
            hinataWrap.style.display = t === 'hinata' ? 'block' : 'none';
            genericWrap.style.display = t === 'generic' ? 'block' : 'none';
            hnSel.name = t === 'hinata' ? `events[${id}][hn_event_id]` : '';
            ltSel.name = t === 'generic' ? `events[${id}][lt_event_id]` : '';
        }
        typeBtns.forEach(bt => bt.addEventListener('click', () => setType(bt.dataset.type)));
        div.querySelector('.event-remove-btn').addEventListener('click', () => div.remove());
        setType('hinata');
        return div;
    }

    document.getElementById('add-event-btn')?.addEventListener('click', function() {
        const container = document.getElementById('event-rows-add');
        container.appendChild(buildEventRow());
    });

    document.getElementById('events-container')?.addEventListener('click', function(e) {
        if (e.target.closest('.event-remove-btn')) {
            e.target.closest('.event-row')?.remove();
        }
    });

})();
</script>
<?php require_once __DIR__ . '/../../../components/flash_toast.php'; ?>
<script>
document.querySelectorAll('form[method="post"]').forEach(function(f) {
    f.addEventListener('submit', function() {
        f.querySelectorAll('button[type="submit"]').forEach(function(btn) {
            if (!btn.disabled) { btn.disabled = true; btn.dataset.origHtml = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>送信中...'; }
        });
    });
});
</script>
</body>
</html>
