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
        @media (max-width: 768px) { .sidebar { position: fixed; transform: translateX(-100%); } .sidebar.mobile-open { transform: translateX(0); } }
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
        <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <?php unset($_SESSION['flash_error']); ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= $isEdit ? '/live_trip/update.php' : '/live_trip/store.php' ?>" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$trip['id'] ?>">
            <?php endif; ?>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-600 mb-2">イベント</label>
                <p class="text-sm text-slate-500 mb-3">複数登録可（2日フェス、前日移動など）</p>
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
                <button type="button" id="add-event-btn" class="mt-2 text-emerald-600 hover:underline font-bold text-sm"><i class="fa-solid fa-plus mr-1"></i>イベントを追加</button>
                <p class="text-sm text-slate-500 mt-2">
                    <a href="/live_trip/lt_event_create.php?redirect=<?= urlencode($isEdit ? '/live_trip/edit.php?id=' . (int)$trip['id'] : '/live_trip/create.php') ?>" class="text-emerald-600 hover:underline">+ 汎用イベントを新規登録</a>
                </p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-600 mb-2">遠征全体の感想</label>
                <textarea name="impression" rows="4" placeholder="複数イベントの総括・振り返り" class="w-full border border-slate-200 rounded-lg px-4 py-2"><?= htmlspecialchars($trip['impression'] ?? $_POST['impression'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="lt-theme-btn text-white px-6 py-2 rounded-lg font-bold"><?= $isEdit ? '更新' : '登録' ?></button>
                <?php $cancelUrl = $isEdit ? '/live_trip/show.php?id=' . (int)$trip['id'] : '/live_trip/'; ?>
                <a href="<?= htmlspecialchars($cancelUrl) ?>" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">キャンセル</a>
            </div>
        </form>
    </div>
</main>

<script>
(function() {
    const hinataEvents = <?= json_encode($hinataEvents ?? []) ?>;
    const ltEvents = <?= json_encode($ltEvents ?? []) ?>;
    let eventIdx = <?= $isEdit ? count($tripEvents) : 0 ?>;

    function buildEventRow() {
        const id = eventIdx++;
        const div = document.createElement('div');
        div.className = 'event-row flex flex-wrap gap-2 items-start p-3 bg-slate-50 rounded-lg border border-slate-200';
        div.innerHTML = `
            <div class="w-full md:w-auto">
                <select name="events[${id}][event_type]" class="event-type-select border border-slate-200 rounded px-2 py-1 text-sm">
                    <option value="hinata">日向坂</option>
                    <option value="generic">汎用</option>
                </select>
            </div>
            <div class="hinata-select w-full md:w-48">
                <select name="events[${id}][hn_event_id]" class="hn-event-select w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    <option value="">選択</option>
                    ${hinataEvents.map(e => '<option value="'+e.id+'">'+escapeHtml(e.event_name)+' ('+e.event_date+')</option>').join('')}
                </select>
            </div>
            <div class="generic-select w-full md:w-48" style="display:none">
                <select name="events[${id}][lt_event_id]" class="lt-event-select w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    <option value="">選択</option>
                    ${ltEvents.map(e => '<option value="'+e.id+'">'+escapeHtml(e.event_name)+' ('+e.event_date+')</option>').join('')}
                </select>
            </div>
            <div class="flex-1 min-w-[120px]"><input type="text" name="events[${id}][seat_info]" placeholder="座席" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
            <div class="flex-1 min-w-[120px]"><input type="text" name="events[${id}][impression]" placeholder="感想" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
            <button type="button" class="event-remove-btn text-red-500 hover:text-red-700 text-sm" title="削除"><i class="fa-solid fa-trash-can"></i></button>
        `;
        div.querySelector('.event-type-select').addEventListener('change', function() {
            const v = this.value;
            div.querySelector('.hinata-select').style.display = v === 'hinata' ? 'block' : 'none';
            div.querySelector('.generic-select').style.display = v === 'generic' ? 'block' : 'none';
            const hnSel = div.querySelector('.hn-event-select');
            const ltSel = div.querySelector('.lt-event-select');
            hnSel.name = v === 'hinata' ? `events[${id}][hn_event_id]` : '';
            ltSel.name = v === 'generic' ? `events[${id}][lt_event_id]` : '';
        });
        div.querySelector('.event-remove-btn').addEventListener('click', function() {
            div.remove();
        });
        div.querySelector('.event-type-select').dispatchEvent(new Event('change'));
        return div;
    }
    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
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

    <?php if (!$isEdit || empty($tripEvents)): ?>
    document.getElementById('add-event-btn')?.click();
    <?php endif; ?>
})();
</script>
</body>
</html>
