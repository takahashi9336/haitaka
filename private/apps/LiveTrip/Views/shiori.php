<?php
/**
 * 遠征のしおり - 当日用コンパクトビュー
 * スマホでパッと見やすい1枚
 */
$appKey = 'live_trip';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$eventPlace = $trip['event_place'] ?? $trip['hn_event_place'] ?? '';
$eventDates = array_values(array_unique(array_filter(array_column($trip['events'] ?? [], 'event_date'))));
sort($eventDates);
$eventPlaceForMaps = $eventPlace ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($eventPlace) : '#';
// イベント日付ごとの開場・開演（タイムライン項目から紐づけ）
$eventDoorCurtain = [];
foreach ($mergedTimeline ?? [] as $m) {
    if (($m['type'] ?? '') === 'timeline') {
        $ti = $m['data'] ?? [];
        $d = $m['date'] ?? '';
        $label = $ti['label'] ?? '';
        $time = trim($ti['scheduled_time'] ?? '');
        if ($d && $time && (strpos($label, '開場') !== false || strpos($label, '開演') !== false)) {
            if (!isset($eventDoorCurtain[$d])) $eventDoorCurtain[$d] = [];
            if (strpos($label, '開場') !== false) $eventDoorCurtain[$d]['開場'] = $time;
            elseif (strpos($label, '開演') !== false) $eventDoorCurtain[$d]['開演'] = $time;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>しおり: <?= htmlspecialchars($trip['event_name'] ?? '遠征') ?></title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --shiori-theme: <?= htmlspecialchars($themePrimaryHex ?? '#10b981') ?>; }
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .shiori-block { font-size: 15px; }
        .shiori-label { font-size: 11px; color: #64748b; font-weight: 700; }
        .shiori-header { background-color: var(--shiori-theme); }
        .shiori-link { color: var(--shiori-theme); }
        .shiori-time { color: #334155; font-weight: 700; }
        .shiori-timeline { position: relative; padding-left: 2rem; }
        .shiori-timeline::before {
            content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0;
            width: 2px; background: #e2e8f0;
        }
        .shiori-timeline-node { position: relative; }
        .shiori-timeline-node .shiori-node-icon {
            position: absolute; left: -1.5rem; top: 0.3rem;
            width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: #fff; border: 2px solid; z-index: 1;
        }
        .shiori-day-band { margin-left: -1rem; margin-right: -1rem; padding: 0.5rem 1rem; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 pb-8">

<header class="shiori-header sticky top-0 z-10 text-white px-4 py-3 flex items-center justify-between shadow-md">
    <a href="/live_trip/show.php?id=<?= (int)$trip['id'] ?>" class="text-white/90 hover:text-white text-sm"><i class="fa-solid fa-arrow-left mr-1"></i>詳細へ</a>
    <h1 class="font-bold text-lg truncate max-w-[60%]"><?= htmlspecialchars($trip['event_name'] ?? '遠征') ?></h1>
    <span class="w-14"></span>
</header>

<div class="px-4 py-4 space-y-4 max-w-lg mx-auto">
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-2">イベント</p>
        <?php foreach ($trip['events'] ?? [] as $ev): 
            $evDate = $ev['event_date'] ?? '';
            $evPlace = $ev['event_place'] ?? $ev['hn_event_place'] ?? '';
            $dc = $eventDoorCurtain[$evDate] ?? [];
            $evMapsUrl = $evPlace ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($evPlace) : '#';
        ?>
        <div class="mb-4 last:mb-0 pb-4 last:pb-0 border-b border-slate-100 last:border-0">
            <p class="font-bold"><?= htmlspecialchars($ev['event_name'] ?? '') ?> <span class="text-slate-500 font-normal text-sm"><?= htmlspecialchars($evDate) ?></span></p>
            <?php if ($evPlace): ?>
            <a href="<?= htmlspecialchars($evMapsUrl) ?>" target="_blank" rel="noopener" class="shiori-link font-medium text-sm mt-1 inline-flex items-center gap-1">
                <?= htmlspecialchars($evPlace) ?> <i class="fa-solid fa-external-link text-xs"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($dc)): ?>
            <p class="text-sm text-slate-600 mt-2">
                <i class="fa-solid fa-door-open text-slate-400 mr-1"></i><?php if (!empty($dc['開場'])): ?>開場 <?= htmlspecialchars($dc['開場']) ?><?php endif; ?><?php if (!empty($dc['開場']) && !empty($dc['開演'])): ?>　<?php endif; ?><?php if (!empty($dc['開演'])): ?><i class="fa-solid fa-star text-slate-400 ml-2 mr-1"></i>開演 <?= htmlspecialchars($dc['開演']) ?><?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($trip['events'] ?? [])): ?>
        <p class="font-bold"><?= htmlspecialchars($trip['event_date'] ?? '') ?></p>
        <?php if ($eventPlace): ?>
        <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="shiori-link font-medium text-sm mt-1 inline-flex items-center gap-1">
            <?= htmlspecialchars($eventPlace) ?> <i class="fa-solid fa-external-link text-xs"></i>
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($hotelStays)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">宿泊</p>
        <?php foreach ($hotelStays as $h):
            $mapsUrl = (new \App\LiveTrip\Model\HotelStayModel())->getGoogleMapsUrl($h);
        ?>
        <div class="mb-4 last:mb-0 pb-4 last:pb-0 border-b border-slate-100 last:border-0">
            <p class="font-bold"><?= htmlspecialchars($h['hotel_name']) ?></p>
            <?php if ($mapsUrl !== '#'): ?>
            <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="shiori-link text-sm">地図で開く</a>
            <?php endif; ?>
            <?php if ($h['reservation_no']): ?><p class="text-sm mt-1">予約番号: <strong><?= htmlspecialchars($h['reservation_no']) ?></strong></p><?php endif; ?>
            <?php if ($h['check_in']): ?><p class="text-sm">チェックイン: <?= htmlspecialchars($h['check_in']) ?></p><?php endif; ?>
            <?php if ($h['check_out']): ?><p class="text-sm">チェックアウト: <?= htmlspecialchars($h['check_out']) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($destinations)): 
        $destModel = new \App\LiveTrip\Model\DestinationModel();
    ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">目的地</p>
        <?php foreach ($destinations as $d):
            $dMapsUrl = $destModel->getGoogleMapsUrl($d);
            $dTypeLabel = \App\LiveTrip\Model\DestinationModel::$types[$d['destination_type'] ?? 'other'] ?? 'その他';
        ?>
        <div class="mb-4 last:mb-0 pb-4 last:pb-0 border-b border-slate-100 last:border-0">
            <p class="font-bold"><?= htmlspecialchars($d['name']) ?> <span class="text-slate-500 font-normal text-sm"><?= htmlspecialchars($dTypeLabel) ?></span></p>
            <?php if ($dMapsUrl !== '#'): ?>
            <a href="<?= htmlspecialchars($dMapsUrl) ?>" target="_blank" rel="noopener" class="shiori-link text-sm">地図で開く</a>
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($d['address'] ?? $d['name']) ?>&travelmode=transit" target="_blank" rel="noopener" class="shiori-link text-sm ml-2"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a>
            <?php endif; ?>
            <?php if (!empty($d['visit_date'])): ?><p class="text-sm mt-1">訪問予定: <?= htmlspecialchars($d['visit_date']) ?><?= !empty($d['visit_time']) ? ' ' . htmlspecialchars($d['visit_time']) : '' ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($transportLegs)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">移動</p>
        <?php foreach ($transportLegs as $t): ?>
        <div class="mb-3 last:mb-0">
            <?php if ($t['departure_date']): ?><p class="text-xs text-slate-500"><?= htmlspecialchars($t['departure_date']) ?></p><?php endif; ?>
            <p class="font-medium"><?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?></p>
            <?php if ($t['departure'] || $t['arrival']): ?>
            <p class="text-sm text-slate-600"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?><?= $t['duration_min'] ? ' ('.$t['duration_min'].'分)' : '' ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($mergedTimeline)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">タイムライン</p>
        <div class="shiori-timeline space-y-0">
        <?php
        $lastDate = '';
        $themeHex = $themePrimaryHex ?? '#10b981';
        foreach ($mergedTimeline as $m):
            $d = $m['date'] ?? '';
            if ($d !== $lastDate):
                $isFirstGroup = ($lastDate === '');
                $lastDate = $d;
                $dayLabel = $d;
                if (!empty($eventDates) && $d) {
                    $firstEv = $eventDates[0];
                    $lastEv = $eventDates[count($eventDates) - 1];
                    if (in_array($d, $eventDates, true)) $dayLabel .= '（当日）';
                    elseif ($d < $firstEv) $dayLabel .= '（前日）';
                    elseif ($d > $lastEv) $dayLabel .= '（翌日）';
                }
        ?>
        <div class="shiori-day-band -mx-4 px-4 py-2 bg-slate-100 text-slate-600 font-bold text-sm <?= $isFirstGroup ? 'mt-0' : 'mt-3' ?>">
            <?= htmlspecialchars($dayLabel) ?>
        </div>
        <?php endif;
            $ti = $m['type'] === 'timeline' ? $m['data'] : null;
            $tl = $m['type'] === 'transport' ? $m['data'] : null;
            $iconClass = 'fa-clock';
            $nodeColor = '#64748b';
            if ($m['type'] === 'transport' && $tl):
                $tt = $tl['transport_type'] ?? '';
                if (preg_match('/新幹線|電車|在来線|列車/', $tt)) { $iconClass = 'fa-train'; $nodeColor = '#3b82f6'; }
                elseif (strpos($tt, '車') !== false) { $iconClass = 'fa-car'; $nodeColor = '#3b82f6'; }
                else { $iconClass = 'fa-bus'; $nodeColor = '#3b82f6'; }
            elseif ($m['type'] === 'timeline' && $ti):
                $label = $ti['label'] ?? '';
                if (strpos($label, '開場') !== false) { $iconClass = 'fa-door-open'; $nodeColor = $themeHex; }
                elseif (strpos($label, '開演') !== false) { $iconClass = 'fa-star'; $nodeColor = $themeHex; }
            endif;
        ?>
        <div class="shiori-timeline-node flex gap-3 py-2.5 border-b border-slate-200 last:border-0" style="--node-color: <?= htmlspecialchars($nodeColor) ?>">
            <span class="font-mono text-sm shiori-time w-12 shrink-0"><?= htmlspecialchars($m['time'] !== '99:99' ? $m['time'] : '') ?></span>
            <div class="min-w-0 flex-1 pl-1">
                <span class="shiori-node-icon" style="color: <?= htmlspecialchars($nodeColor) ?>; border-color: <?= htmlspecialchars($nodeColor) ?>"><i class="fa-solid <?= htmlspecialchars($iconClass) ?> text-xs"></i></span>
                <?php if ($m['type'] === 'timeline'): ?>
                <p class="font-medium text-slate-800"><?= htmlspecialchars($ti['label']) ?></p>
                <?php if ($ti['memo']): ?><p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($ti['memo']) ?></p><?php endif; ?>
                <?php else: ?>
                <p class="font-medium text-slate-800"><?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?></p>
                <?php
                $routeMemo = trim($tl['route_memo'] ?? '');
                $hasRouteInMemo = $routeMemo !== '' && (strpos($routeMemo, '→') !== false || strpos($routeMemo, '駅') !== false);
                if (($tl['departure'] || $tl['arrival']) && !$hasRouteInMemo):
                ?><p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($tl['departure']) ?> → <?= htmlspecialchars($tl['arrival']) ?></p><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($checklistItems)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">チェックリスト</p>
        <?php foreach ($checklistItems as $ci): ?>
        <div class="shiori-checklist-row flex items-center gap-2 py-2 border-b border-slate-50 last:border-0" data-id="<?= (int)$ci['id'] ?>">
            <button type="button" class="shiori-checklist-toggle text-left flex-1 flex items-center gap-2 w-full min-w-0" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>">
                <i class="shiori-checklist-icon fa-<?= $ci['checked'] ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle' ?> text-slate-300 w-5 shrink-0"></i>
                <span class="shiori-checklist-label <?= $ci['checked'] ? 'line-through text-slate-400' : '' ?>"><?= htmlspecialchars($ci['item_name']) ?></span>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($hotelStays) && empty($transportLegs) && empty($mergedTimeline) && empty($checklistItems)): ?>
    <p class="text-slate-500 text-center py-8">ホテル・移動・タイムライン・チェックリストを登録すると、ここに表示されます。</p>
    <?php endif; ?>
</div>

<script>
(function() {
    var tripId = <?= (int)$trip['id'] ?>;
    document.querySelectorAll('.shiori-checklist-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var trip = this.dataset.trip;
            fetch('/live_trip/checklist_toggle.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body: 'id=' + id + '&trip_plan_id=' + trip
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'ok') {
                    document.querySelectorAll('.shiori-checklist-row[data-id="' + id + '"]').forEach(function(row) {
                        var icon = row.querySelector('.shiori-checklist-icon');
                        var label = row.querySelector('.shiori-checklist-label');
                        if (!icon || !label) return;
                        if (data.checked) {
                            icon.className = 'shiori-checklist-icon fa-solid fa-circle-check text-emerald-500 w-5 shrink-0';
                            label.classList.add('line-through', 'text-slate-400');
                        } else {
                            icon.className = 'shiori-checklist-icon fa-regular fa-circle text-slate-300 w-5 shrink-0';
                            label.classList.remove('line-through', 'text-slate-400');
                        }
                    });
                }
            });
        });
    });
})();
</script>
</body>
</html>
