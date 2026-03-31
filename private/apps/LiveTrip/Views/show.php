<?php
$appKey = 'live_trip';
require_once __DIR__ . '/../../../components/theme_from_session.php';
\Core\Database::connect();
$eventPlace = $trip['event_place'] ?? $trip['hn_event_place'] ?? '';
$eventDates = array_values(array_unique(array_filter(array_column($trip['events'] ?? [], 'event_date'))));
sort($eventDates);
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
$eventPlaceForMaps = $eventPlace ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($eventPlace) : '#';

$staticMapSvc = new \App\LiveTrip\Service\MapsStaticMapService();
$venueMapUrl = null;
$firstEv = $trip['events'][0] ?? null;
if ($firstEv && ($lat = trim($firstEv['venue_latitude'] ?? '')) !== '' && ($lng = trim($firstEv['venue_longitude'] ?? '')) !== '') {
    $venueMapUrl = $staticMapSvc->getStaticMapUrl($lat, $lng, 320, 120);
}
$hotelMapUrls = [];
foreach ($hotelStays ?? [] as $h) {
    $lat = trim($h['latitude'] ?? '');
    $lng = trim($h['longitude'] ?? '');
    if ($lat !== '' && $lng !== '') {
        $url = $staticMapSvc->getStaticMapUrl($lat, $lng, 320, 120);
        if ($url) $hotelMapUrls[(int)$h['id']] = $url;
    }
}
$destinationMapUrls = [];
foreach ($destinations ?? [] as $d) {
    $lat = trim($d['latitude'] ?? '');
    $lng = trim($d['longitude'] ?? '');
    if ($lat !== '' && $lng !== '') {
        $url = $staticMapSvc->getStaticMapUrl($lat, $lng, 320, 120);
        if ($url) $destinationMapUrls[(int)$d['id']] = $url;
    }
}
$destinationModel = new \App\LiveTrip\Model\DestinationModel();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($trip['event_name'] ?? '遠征') ?> - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --lt-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .lt-theme-btn { background-color: var(--lt-theme); }
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { width: 240px; }
        @media (max-width: 768px) { .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; } .sidebar.mobile-open { transform: translateX(0); } }
        .section-card { break-inside: avoid; }
        .lt-tab { padding: 0.5rem 0.75rem; font-weight: 600; font-size: 0.8125rem; color: #64748b; border-bottom: 2px solid transparent; transition: all 0.15s; white-space: nowrap; }
        @media (min-width: 640px) { .lt-tab { padding: 0.75rem 1rem; font-size: 0.875rem; } }
        .lt-tab:hover { color: var(--lt-theme); }
        .lt-tab.active { color: var(--lt-theme); border-bottom-color: var(--lt-theme); }
        .lt-tab-panel { display: none; }
        .lt-tab-panel.active { display: block; }
        .lt-checklist-row { transition: background 0.1s; }
        .lt-checklist-row:hover .lt-edit-btn { opacity: 1; }
        .lt-edit-btn { opacity: 0.5; }
        .edit-form.hidden { display: none !important; }
        .lt-tab-bar { -webkit-overflow-scrolling: touch; }
        .lt-checklist-sortable .sortable-ghost { opacity: 0.4; }
        .lt-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; display: flex; align-items: center; justify-content: center; }
        .lt-modal-overlay.hidden { display: none; }
        .lt-modal { background: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: 28rem; width: 100%; max-height: 90vh; overflow-y: auto; }
        .lt-timeline-view-btn.is-active { background: var(--lt-theme); color: #fff; border-color: var(--lt-theme); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 overflow-auto overflow-x-hidden w-full">
    <header class="min-h-14 bg-white border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 sm:px-6 py-3 shrink-0">
        <div class="flex items-start gap-2 min-w-0 shrink">
            <a href="/live_trip/" class="text-slate-500 hover:text-slate-700 pt-0.5 shrink-0"><i class="fa-solid fa-arrow-left"></i></a>
            <div class="min-w-0 flex-1">
                <h1 class="font-black text-slate-700 text-base sm:text-lg truncate"><?= htmlspecialchars($trip['event_name'] ?? '遠征') ?></h1>
                <p class="text-xs sm:text-sm text-slate-500 truncate">
                    <?= htmlspecialchars($trip['event_date'] ?? '') ?>
                    <?php if ($eventPlace): ?>
                        · <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="text-sky-600 hover:underline"><?= htmlspecialchars($eventPlace) ?> <i class="fa-solid fa-external-link text-[10px]"></i></a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="flex gap-2 shrink-0 flex-wrap">
            <a href="/live_trip/shiori.php?id=<?= (int)$trip['id'] ?>" class="px-3 py-2 lt-theme-btn text-white rounded-lg text-xs sm:text-sm font-bold whitespace-nowrap" target="_blank">しおり</a>
            <a href="/live_trip/edit.php?id=<?= (int)$trip['id'] ?>" class="px-3 py-2 border border-slate-200 rounded-lg text-xs sm:text-sm font-bold hover:bg-slate-50 whitespace-nowrap">編集</a>
            <form method="post" action="/live_trip/delete.php" onsubmit="return confirm('この遠征を削除しますか？');" class="inline">
                <input type="hidden" name="id" value="<?= (int)$trip['id'] ?>">
                <button type="submit" class="px-3 py-2 border border-red-200 text-red-600 rounded-lg text-xs sm:text-sm font-bold hover:bg-red-50 whitespace-nowrap">削除</button>
            </form>
        </div>
    </header>

    <div class="lt-tab-bar flex gap-1 border-b border-slate-200 bg-slate-50 px-2 sm:px-4 overflow-x-auto shrink-0">
        <button type="button" class="lt-tab shrink-0" data-tab="summary"><i class="fa-solid fa-book mr-1 text-slate-400"></i>サマリ</button>
        <button type="button" class="lt-tab shrink-0" data-tab="info"><i class="fa-solid fa-ticket mr-1 text-slate-400"></i>参加情報</button>
        <button type="button" class="lt-tab shrink-0" data-tab="expense"><i class="fa-solid fa-yen-sign mr-1 text-slate-400"></i>費用</button>
        <button type="button" class="lt-tab shrink-0" data-tab="hotel"><i class="fa-solid fa-hotel mr-1 text-slate-400"></i>宿泊</button>
        <button type="button" class="lt-tab shrink-0" data-tab="destination"><i class="fa-solid fa-map-location-dot mr-1 text-slate-400"></i>目的地</button>
        <button type="button" class="lt-tab shrink-0" data-tab="transport"><i class="fa-solid fa-train mr-1 text-slate-400"></i>移動</button>
        <button type="button" class="lt-tab shrink-0" data-tab="timeline"><i class="fa-solid fa-clock mr-1 text-slate-400"></i>タイムライン</button>
        <button type="button" class="lt-tab shrink-0" data-tab="checklist"><i class="fa-solid fa-list-check mr-1 text-slate-400"></i>チェックリスト</button>
    </div>

    <div class="p-4 sm:p-6 max-w-4xl lt-tab-panels min-w-0">
        <div class="lt-tab-panel max-w-4xl" data-panel="summary" id="panel-summary">
        <?php
        $summaryTotalExpense = 0;
        foreach ($expenses ?? [] as $ex) { $summaryTotalExpense += (int)($ex['amount'] ?? 0); }
        foreach ($transportLegs ?? [] as $tl) { $summaryTotalExpense += (int)($tl['amount'] ?? 0); }
        foreach ($hotelStays ?? [] as $h) { $summaryTotalExpense += (int)($h['price'] ?? 0); }
        $summaryCheckTotal = count($checklistItems ?? []);
        $summaryCheckChecked = array_sum(array_column($checklistItems ?? [], 'checked'));
        $summaryNextActions = [];
        if (empty($hotelStays)) $summaryNextActions[] = ['label' => '宿泊を追加', 'tab' => 'hotel'];
        if (empty($destinations)) $summaryNextActions[] = ['label' => '目的地を追加', 'tab' => 'destination'];
        if (empty($transportLegs)) $summaryNextActions[] = ['label' => '移動を追加', 'tab' => 'transport'];
        if ($summaryCheckTotal === 0) $summaryNextActions[] = ['label' => 'チェックリストを追加', 'tab' => 'checklist'];
        ?>
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-book text-slate-400"></i> しおりサマリ
            </h2>
            <div class="flex flex-wrap gap-4 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <?php if ($summaryTotalExpense > 0): ?>
                <div><span class="text-xs font-bold text-slate-500">費用合計</span><p class="font-bold text-slate-800">¥<?= number_format($summaryTotalExpense) ?></p></div>
                <?php endif; ?>
                <?php if ($summaryCheckTotal > 0): ?>
                <div><span class="text-xs font-bold text-slate-500">チェック進捗</span><p class="font-bold text-slate-800"><?= $summaryCheckChecked ?>/<?= $summaryCheckTotal ?></p></div>
                <?php endif; ?>
                <div><span class="text-xs font-bold text-slate-500">しおり</span><p><a href="/live_trip/shiori.php?id=<?= (int)$trip['id'] ?>" target="_blank" class="lt-theme-link font-medium hover:underline">当日用を開く <i class="fa-solid fa-external-link text-xs"></i></a></p></div>
                <?php if (!empty($summaryNextActions)): ?>
                <div class="w-full pt-2 border-t border-slate-200"><span class="text-xs font-bold text-slate-500">次のアクション</span>
                    <p class="flex flex-wrap gap-2 mt-1">
                        <?php foreach ($summaryNextActions as $a): ?>
                        <button type="button" class="text-sm lt-theme-link hover:underline js-switch-tab" data-tab="<?= htmlspecialchars($a['tab']) ?>"><?= htmlspecialchars($a['label']) ?></button>
                        <?php endforeach; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
                <?php if (!empty($mergedTimeline)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <p class="text-xs font-bold text-slate-500">タイムライン</p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="lt-timeline-summary-view-btn lt-timeline-view-btn px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold text-slate-600 hover:bg-slate-50" data-view="calendar">日表示</button>
                            <button type="button" class="lt-timeline-summary-view-btn lt-timeline-view-btn px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold text-slate-600 hover:bg-slate-50" data-view="list">リスト</button>
                        </div>
                    </div>

                    <?php
                    $dayStartMinSummary = 6 * 60;
                    $dayEndMinSummary = 24 * 60;
                    $pxPerMinSummary = 1; // 1時間=60px（要望: 半分）
                    $laneHeightSummary = ($dayEndMinSummary - $dayStartMinSummary) * $pxPerMinSummary;
                    $timelineByDaySummary = [];
                    foreach ($mergedTimeline ?? [] as $m) {
                        $d = $m['date'] ?? '';
                        $t = $m['time'] ?? '';
                        if (!$d || !$t || $t === '99:99') continue;
                        if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($t), $mmS)) continue;
                        $startMin = ((int)$mmS[1]) * 60 + (int)$mmS[2];
                        $timelineByDaySummary[$d] ??= [];
                        $timelineByDaySummary[$d][] = $m + ['_startMin' => $startMin];
                    }
                    ksort($timelineByDaySummary);
                    ?>

                    <div class="lt-summary-timeline-calendar hidden space-y-5">
                        <?php foreach ($timelineByDaySummary as $day => $rows): ?>
                            <?php
                            $dayLabel = $day;
                            if (!empty($eventDates) && $day) {
                                $firstEv = $eventDates[0];
                                $lastEv = $eventDates[count($eventDates) - 1];
                                if (in_array($day, $eventDates, true)) $dayLabel .= '（当日）';
                                elseif ($day < $firstEv) $dayLabel .= '（前日）';
                                elseif ($day > $lastEv) $dayLabel .= '（翌日）';
                            }
                            $sameStartBuckets = [];
                            foreach ($rows as $r) {
                                $k = (string)($r['_startMin'] ?? 0);
                                $sameStartBuckets[$k] ??= 0;
                                $sameStartBuckets[$k]++;
                            }
                            $sameStartIndex = [];
                            ?>
                            <div class="lt-summary-day">
                                <p class="text-xs font-bold text-slate-500 mb-2"><?= htmlspecialchars($dayLabel) ?></p>
                                <div class="grid grid-cols-[48px_1fr] gap-3">
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        <?php for ($h = 6; $h <= 23; $h++): ?>
                                            <div class="h-[60px] flex items-start justify-end pr-1"><?= sprintf('%02d:00', $h) ?></div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="relative rounded-lg border border-slate-200 bg-white overflow-hidden" style="height: <?= (int)$laneHeightSummary ?>px;">
                                        <?php for ($h = 6; $h <= 23; $h++): ?>
                                            <div class="absolute left-0 right-0 border-t border-slate-100" style="top: <?= (int)(($h*60 - $dayStartMinSummary) * $pxPerMinSummary) ?>px;"></div>
                                        <?php endfor; ?>
                                        <?php foreach ($rows as $m): ?>
                                            <?php
                                            $startMin = (int)($m['_startMin'] ?? 0);
                                            $dur = (int)($m['duration_min'] ?? 30);
                                            if ($dur <= 0) $dur = 30;
                                            $endMin = $startMin + $dur;
                                            $visStart = max($startMin, $dayStartMinSummary);
                                            $visEnd = min($endMin, $dayEndMinSummary);
                                            if ($visEnd <= $visStart) continue;
                                            $top = ($visStart - $dayStartMinSummary) * $pxPerMinSummary;
                                            $height = ($visEnd - $visStart) * $pxPerMinSummary;
                                            if ($height < 16) $height = 16;
                                            if ($top + $height > $laneHeightSummary) $height = max(16, $laneHeightSummary - $top);

                                            $bucketKey = (string)$startMin;
                                            $sameStartIndex[$bucketKey] ??= 0;
                                            $idx = $sameStartIndex[$bucketKey]++;
                                            $bucketCount = (int)($sameStartBuckets[$bucketKey] ?? 1);
                                            $cols = $bucketCount >= 2 ? 2 : 1;
                                            $col = $cols === 2 ? ($idx % 2) : 0;
                                            $left = $cols === 2 ? (8 + $col * 50) : 8;
                                            $widthStyle = $cols === 2 ? 'width: calc(50% - 12px);' : 'right: 8px;';

                                            $bg = $m['type'] === 'transport' ? 'bg-sky-50 border-sky-200' : 'bg-emerald-50 border-emerald-200';
                                            $label = '';
                                            $sub = '';
                                            $itemId = 0;
                                            if ($m['type'] === 'timeline') {
                                                $ti = $m['data'] ?? [];
                                                $itemId = (int)($ti['id'] ?? 0);
                                                $label = (string)($ti['label'] ?? '');
                                                $sub = (string)($ti['memo'] ?? '');
                                            } else {
                                                $tl = $m['data'] ?? [];
                                                $itemId = (int)($tl['id'] ?? 0);
                                                $label = trim(($tl['transport_type'] ?? '') . ' ' . ($tl['route_memo'] ?? ''));
                                                $sub = trim(($tl['departure'] ?? '') . ($tl['arrival'] ? ' → ' . ($tl['arrival'] ?? '') : ''));
                                            }
                                            ?>
                                            <div class="lt-event absolute rounded-lg border shadow-sm <?= $bg ?> p-2 text-[11px] text-slate-700 overflow-hidden cursor-pointer"
                                                 data-lt-type="<?= htmlspecialchars($m['type']) ?>"
                                                 data-lt-id="<?= (int)$itemId ?>"
                                                 data-lt-date="<?= htmlspecialchars($day) ?>"
                                                 data-lt-time="<?= htmlspecialchars($m['time']) ?>"
                                                 data-lt-duration="<?= (int)$dur ?>"
                                                 data-lt-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
                                                 data-lt-memo="<?= htmlspecialchars($sub, ENT_QUOTES) ?>"
                                                 data-lt-scheduled-date="<?= htmlspecialchars((string)($m['date'] ?? '')) ?>"
                                                 data-lt-transport-type="<?= htmlspecialchars((string)(($m['data']['transport_type'] ?? '')), ENT_QUOTES) ?>"
                                                 data-lt-route-memo="<?= htmlspecialchars((string)(($m['data']['route_memo'] ?? '')), ENT_QUOTES) ?>"
                                                 data-lt-departure="<?= htmlspecialchars((string)(($m['data']['departure'] ?? '')), ENT_QUOTES) ?>"
                                                 data-lt-arrival="<?= htmlspecialchars((string)(($m['data']['arrival'] ?? '')), ENT_QUOTES) ?>"
                                                 data-lt-duration-min-transport="<?= (int)(($m['data']['duration_min'] ?? 0)) ?>"
                                                 data-lt-amount="<?= (int)(($m['data']['amount'] ?? 0)) ?>"
                                                 data-lt-maps-link="<?= htmlspecialchars((string)(($m['data']['maps_link'] ?? '')), ENT_QUOTES) ?>"
                                                 style="top: <?= (int)$top ?>px; left: <?= (int)$left ?>px; <?= $widthStyle ?> height: <?= (int)$height ?>px;">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="font-mono font-bold text-slate-600 shrink-0"><?= htmlspecialchars($m['time']) ?></span>
                                                    <span class="font-bold truncate flex-1 min-w-0"><?= htmlspecialchars($label) ?></span>
                                                    <span class="text-slate-400 font-normal shrink-0">(<?= (int)$dur ?>m)</span>
                                                </div>
                                                <?php if ($sub !== ''): ?><div class="text-[10px] text-slate-500 mt-1 truncate"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="lt-summary-timeline-list">
                    <?php
                    $lastDate = '';
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
                    <p class="text-xs font-bold text-slate-500 <?= $isFirstGroup ? 'mt-0' : 'mt-3' ?> mb-1"><?= htmlspecialchars($dayLabel) ?></p>
                    <?php endif; ?>
                    <div class="flex gap-3 py-2 border-b border-slate-200 last:border-0">
                        <span class="font-mono font-bold text-emerald-600 w-14 shrink-0"><?= htmlspecialchars($m['time'] !== '99:99' ? $m['time'] : '') ?></span>
                        <div>
                            <?php if ($m['type'] === 'timeline'): $t = $m['data']; ?>
                            <p class="font-medium"><?= htmlspecialchars($t['label']) ?><?= $t['memo'] ? ' · ' . htmlspecialchars($t['memo']) : '' ?></p>
                            <?php else: $t = $m['data']; ?>
                            <p class="font-medium"><i class="fa-solid fa-train text-slate-400 mr-1"></i><?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?></p>
                            <?php if ($t['departure'] || $t['arrival']): ?><p class="text-sm text-slate-500"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?></p><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-2">イベント</p>
                    <?php foreach ($trip['events'] ?? [] as $ev): 
                        $evDate = $ev['event_date'] ?? '';
                        $evPlace = $ev['event_place'] ?? $ev['hn_event_place'] ?? '';
                        $dc = $eventDoorCurtain[$evDate] ?? [];
                    ?>
                    <div class="mb-4 last:mb-0 pb-4 last:pb-0 border-b border-slate-100 last:border-0">
                        <p class="font-bold"><?= htmlspecialchars($ev['event_name'] ?? '') ?> <span class="text-slate-500 font-normal text-sm"><?= htmlspecialchars($evDate) ?></span></p>
                        <?php if ($evPlace): ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($evPlace) ?>" target="_blank" rel="noopener" class="text-sky-600 font-medium text-sm mt-1 inline-flex items-center gap-1"><?= htmlspecialchars($evPlace) ?> <i class="fa-solid fa-external-link text-xs"></i></a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($evPlace) ?>&travelmode=transit" target="_blank" rel="noopener" class="text-sky-600 text-sm ml-2 inline-flex items-center gap-1"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a>
                        <?php endif; ?>
                        <?php if (!empty($dc)): ?>
                        <p class="text-sm text-slate-600 mt-2">
                            <i class="fa-solid fa-door-open text-slate-400 mr-1"></i><?php if (!empty($dc['開場'])): ?>開場 <?= htmlspecialchars($dc['開場']) ?><?php endif; ?><?php if (!empty($dc['開場']) && !empty($dc['開演'])): ?>　<?php endif; ?><?php if (!empty($dc['開演'])): ?><i class="fa-solid fa-star text-slate-400 ml-2 mr-1"></i>開演 <?= htmlspecialchars($dc['開演']) ?><?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($ev === ($trip['events'][0] ?? null) && $venueMapUrl): ?>
                        <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="block mt-2 rounded-lg overflow-hidden border border-slate-200 max-w-[320px]">
                            <img src="<?= htmlspecialchars($venueMapUrl) ?>" alt="会場" width="320" height="120" class="w-full h-auto">
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($trip['events'] ?? [])): ?>
                    <p class="font-bold"><?= htmlspecialchars($trip['event_date'] ?? '') ?></p>
                    <?php if ($eventPlace): ?>
                    <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="text-sky-600 font-medium text-sm mt-1 inline-flex items-center gap-1"><?= htmlspecialchars($eventPlace) ?> <i class="fa-solid fa-external-link text-xs"></i></a>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($eventPlace) ?>&travelmode=transit" target="_blank" rel="noopener" class="text-sky-600 text-sm ml-2 inline-flex items-center gap-1"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a>
                    <?php endif; ?>
                    <?php if ($venueMapUrl): ?>
                    <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="block mt-2 rounded-lg overflow-hidden border border-slate-200 max-w-[320px]">
                        <img src="<?= htmlspecialchars($venueMapUrl) ?>" alt="会場" width="320" height="120" class="w-full h-auto">
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($hotelStays)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">宿泊</p>
                    <?php foreach ($hotelStays as $h):
                        $mapsUrl = (new \App\LiveTrip\Model\HotelStayModel())->getGoogleMapsUrl($h);
                        $hotelMapUrl = $hotelMapUrls[(int)$h['id']] ?? null;
                    ?>
                    <div class="mb-3 last:mb-0">
                        <p class="font-bold"><?= htmlspecialchars($h['hotel_name']) ?></p>
                        <?php if ($mapsUrl !== '#'): ?><a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="text-sky-600 text-sm">地図で開く</a><a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($h['address'] ?: $h['hotel_name']) ?>&travelmode=transit" target="_blank" rel="noopener" class="text-sky-600 text-sm ml-2"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a><?php endif; ?>
                        <?php if ($hotelMapUrl): ?>
                        <a href="<?= htmlspecialchars($mapsUrl !== '#' ? $mapsUrl : '#') ?>" target="_blank" rel="noopener" class="block mt-2 rounded-lg overflow-hidden border border-slate-200 max-w-[320px]">
                            <img src="<?= htmlspecialchars($hotelMapUrl) ?>" alt="<?= htmlspecialchars($h['hotel_name']) ?>" width="320" height="120" class="w-full h-auto">
                        </a>
                        <?php endif; ?>
                        <?php if ($h['reservation_no']): ?><p class="text-sm">予約番号: <?= htmlspecialchars($h['reservation_no']) ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($destinations)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">目的地</p>
                    <?php foreach ($destinations as $d):
                        $dMapsUrl = $destinationModel->getGoogleMapsUrl($d);
                        $dMapUrl = $destinationMapUrls[(int)$d['id']] ?? null;
                        $dTypeLabel = \App\LiveTrip\Model\DestinationModel::$types[$d['destination_type'] ?? 'other'] ?? 'その他';
                    ?>
                    <div class="mb-3 last:mb-0">
                        <p class="font-bold"><?= htmlspecialchars($d['name']) ?> <span class="text-xs font-normal text-slate-500"><?= htmlspecialchars($dTypeLabel) ?></span></p>
                        <?php if ($dMapsUrl !== '#'): ?><a href="<?= htmlspecialchars($dMapsUrl) ?>" target="_blank" rel="noopener" class="text-sky-600 text-sm">地図で開く</a><a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($d['address'] ?? $d['name']) ?>&travelmode=transit" target="_blank" rel="noopener" class="text-sky-600 text-sm ml-2"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a><?php endif; ?>
                        <?php if ($dMapUrl): ?>
                        <a href="<?= htmlspecialchars($dMapsUrl !== '#' ? $dMapsUrl : '#') ?>" target="_blank" rel="noopener" class="block mt-2 rounded-lg overflow-hidden border border-slate-200 max-w-[320px]">
                            <img src="<?= htmlspecialchars($dMapUrl) ?>" alt="<?= htmlspecialchars($d['name']) ?>" width="320" height="120" class="w-full h-auto">
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($d['visit_date'])): ?><p class="text-sm">訪問予定: <?= htmlspecialchars($d['visit_date']) ?><?= !empty($d['visit_time']) ? ' ' . htmlspecialchars($d['visit_time']) : '' ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($transportLegs)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">移動</p>
                    <?php foreach ($transportLegs as $t): ?>
                    <div class="mb-2">
                        <?php if ($t['departure_date']): ?><p class="text-xs text-slate-500"><?= htmlspecialchars($t['departure_date']) ?></p><?php endif; ?>
                        <p><?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?></p>
                        <?php if ($t['departure'] || $t['arrival']): ?><p class="text-sm text-slate-600"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?><?= $t['duration_min'] ? ' ('.$t['duration_min'].'分)' : '' ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($checklistItems)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">チェックリスト</p>
                    <?php foreach ($checklistItems as $ci): ?>
                    <div class="lt-checklist-row flex items-center gap-2 py-1 border-b border-slate-200/50 last:border-0" data-id="<?= (int)$ci['id'] ?>">
                        <button type="button" class="checklist-toggle text-left flex-1 flex items-center gap-2 hover:opacity-80 transition" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>">
                            <i class="checklist-icon fa-<?= $ci['checked'] ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle text-slate-300' ?> w-4"></i>
                            <span class="checklist-label <?= $ci['checked'] ? 'line-through text-slate-400' : '' ?>"><?= htmlspecialchars($ci['item_name']) ?></span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (empty($hotelStays) && empty($destinations) && empty($transportLegs) && empty($mergedTimeline) && empty($checklistItems)): ?>
                <p class="text-slate-500 text-sm">宿泊・目的地・移動・タイムライン・チェックリストを登録すると、ここに表示されます。各タブから追加してください。</p>
                <?php endif; ?>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="info" id="panel-info">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-ticket text-slate-400"></i> 参加情報
            </h2>
            <form method="post" action="/live_trip/participation_update.php" class="space-y-4">
                <input type="hidden" name="id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="info">
                <?php if (!empty($trip['events'])): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">イベント別・座席・感想</p>
                    <div class="space-y-3">
                    <?php foreach ($trip['events'] as $ev): ?>
                    <div class="py-3 border-b border-slate-100 last:border-0 last:pb-0 mb-3 last:mb-0">
                        <input type="hidden" name="events[<?= (int)$ev['id'] ?>][tpe_id]" value="<?= (int)$ev['id'] ?>">
                        <input type="hidden" name="events[<?= (int)$ev['id'] ?>][event_type]" value="<?= htmlspecialchars($ev['event_type'] ?? '') ?>">
                        <input type="hidden" name="events[<?= (int)$ev['id'] ?>][hn_event_id]" value="<?= (int)($ev['hn_event_id'] ?? 0) ?>">
                        <p class="font-medium text-slate-700 mb-2"><?= htmlspecialchars($ev['event_name'] ?? '') ?> (<?= htmlspecialchars($ev['event_date'] ?? '') ?>)</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-0.5">座席</label>
                                <input type="text" name="events[<?= (int)$ev['id'] ?>][seat_info]" value="<?= htmlspecialchars($ev['seat_info'] ?? '') ?>" placeholder="アリーナ○列、天空席など" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-0.5">感想</label>
                                <textarea name="events[<?= (int)$ev['id'] ?>][impression]" rows="2" placeholder="参加後の感想" class="w-full border border-slate-200 rounded px-3 py-2 text-sm"><?= htmlspecialchars($ev['impression'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <label class="block text-xs font-bold text-slate-500 mb-1">遠征全体の感想</label>
                    <textarea name="impression" rows="4" placeholder="複数イベントの総括・振り返り" class="w-full border border-slate-200 rounded-lg px-4 py-2 text-sm mt-1"><?= htmlspecialchars($trip['impression'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">保存</button>
            </form>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="expense" id="panel-expense">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-yen-sign text-slate-400"></i> 費用
            </h2>
            <?php
            $totalExpense = 0;
            foreach ($expenses as $ex) { $totalExpense += (int)($ex['amount'] ?? 0); }
            $totalTransport = 0;
            foreach ($transportLegs as $tl) { $totalTransport += (int)($tl['amount'] ?? 0); }
            $totalHotel = 0;
            foreach ($hotelStays ?? [] as $h) { $totalHotel += (int)($h['price'] ?? 0); }
            $categoryOrder = array_keys(\App\LiveTrip\Model\ExpenseModel::$categories);
            usort($expenses, function($a, $b) use ($categoryOrder) {
                $posA = array_search($a['category'] ?? '', $categoryOrder);
                $posB = array_search($b['category'] ?? '', $categoryOrder);
                $posA = $posA === false ? 999 : $posA;
                $posB = $posB === false ? 999 : $posB;
                if ($posA !== $posB) return $posA - $posB;
                return ((int)($a['id'] ?? 0)) - ((int)($b['id'] ?? 0));
            });
            ?>
            <form method="post" action="/live_trip/expense_store.php" class="flex flex-wrap gap-2 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="expense">
                <select name="category" class="border border-slate-200 rounded px-2 py-1 text-sm" required>
                    <?php foreach (\App\LiveTrip\Model\ExpenseModel::$categories as $k => $v): ?>
                    <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="amount" placeholder="金額" class="border border-slate-200 rounded px-2 py-1 w-24 text-sm" required>
                <input type="text" name="memo" placeholder="メモ" class="border border-slate-200 rounded px-2 py-1 flex-1 min-w-24 text-sm">
                <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">追加</button>
            </form>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm space-y-4">
                <?php
                $groupedByCat = [];
                foreach ($expenses as $ex) {
                    $c = $ex['category'] ?? 'other';
                    if (!isset($groupedByCat[$c])) $groupedByCat[$c] = [];
                    $groupedByCat[$c][] = $ex;
                }
                foreach ($categoryOrder as $catKey):
                    if (empty($groupedByCat[$catKey])) continue;
                    $catLabel = \App\LiveTrip\Model\ExpenseModel::$categories[$catKey] ?? $catKey;
                ?>
                <div>
                    <p class="font-bold text-slate-700"><?= htmlspecialchars($catLabel) ?></p>
                    <hr class="border-slate-200 my-1">
                    <div class="space-y-2 mt-2">
                        <?php foreach ($groupedByCat[$catKey] as $ex):
                            $exLabel = trim($ex['memo'] ?? '') !== '' ? trim($ex['memo']) : $catLabel;
                        ?>
                        <div class="expense-item py-2 border-b border-slate-100 last:border-0">
                            <div class="expense-view flex justify-between items-center">
                                <span class="text-slate-700"><?= htmlspecialchars($exLabel) ?></span>
                                <span class="flex items-center gap-2">
                                    <span class="font-medium">¥<?= number_format((int)($ex['amount'] ?? 0)) ?></span>
                                    <button type="button" class="expense-edit-btn text-slate-500 text-sm hover:text-slate-700" title="編集"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <form method="post" action="/live_trip/expense_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                        <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
                                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                        <input type="hidden" name="tab" value="expense">
                                        <button type="submit" class="text-red-500 text-sm hover:underline"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </span>
                            </div>
                            <form method="post" action="/live_trip/expense_update.php" class="expense-edit-form edit-form hidden flex flex-wrap gap-2 items-center p-2 bg-slate-50 rounded">
                                <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
                                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                <input type="hidden" name="tab" value="expense">
                                <select name="category" class="border border-slate-200 rounded px-2 py-1 text-sm" required>
                                    <?php foreach (\App\LiveTrip\Model\ExpenseModel::$categories as $k => $v): ?>
                                    <option value="<?= htmlspecialchars($k) ?>" <?= ($ex['category'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="amount" value="<?= (int)($ex['amount'] ?? 0) ?>" class="border border-slate-200 rounded px-2 py-1 w-24 text-sm" required>
                                <input type="text" name="memo" value="<?= htmlspecialchars($ex['memo'] ?? '') ?>" placeholder="メモ" class="border border-slate-200 rounded px-2 py-1 flex-1 min-w-24 text-sm">
                                <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                                <button type="button" class="expense-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($totalTransport > 0): ?>
                <div>
                    <p class="font-bold text-slate-700">交通費（移動タブから登録）</p>
                    <hr class="border-slate-200 my-1">
                    <div class="space-y-2 mt-2">
                        <?php foreach ($transportLegs as $tl): ?>
                        <?php if (!empty($tl['amount']) && (int)$tl['amount'] > 0): ?>
                        <div class="py-2 border-b border-slate-100 last:border-0 flex justify-between items-center text-sm">
                            <span class="text-slate-700">
                                <?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?>
                                <?php if ($tl['departure'] || $tl['arrival']): ?><span class="text-slate-500">(<?= htmlspecialchars($tl['departure'] ?? '') ?>→<?= htmlspecialchars($tl['arrival'] ?? '') ?>)</span><?php endif; ?>
                            </span>
                            <span class="font-medium">¥<?= number_format((int)$tl['amount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <p class="pt-2 font-bold text-slate-700 text-right">小計: ¥<?= number_format($totalTransport) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($totalHotel > 0): ?>
                <div>
                    <p class="font-bold text-slate-700">ホテル代（宿泊タブから登録）</p>
                    <hr class="border-slate-200 my-1">
                    <div class="space-y-2 mt-2">
                        <?php foreach ($hotelStays ?? [] as $h): ?>
                        <?php if (!empty($h['price']) && (int)$h['price'] > 0): ?>
                        <div class="py-2 border-b border-slate-100 last:border-0 flex justify-between items-center text-sm">
                            <span class="text-slate-700"><?= htmlspecialchars($h['hotel_name']) ?></span>
                            <span class="font-medium">¥<?= number_format((int)$h['price']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <p class="pt-2 font-bold text-slate-700 text-right">小計: ¥<?= number_format($totalHotel) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($totalExpense > 0 || $totalTransport > 0 || $totalHotel > 0): ?>
                <p class="pt-2 font-bold text-slate-700 border-t border-slate-200 mt-2 text-right">合計: ¥<?= number_format($totalExpense + $totalTransport + $totalHotel) ?></p>
                <?php endif; ?>
                <?php if ($totalExpense === 0 && $totalTransport === 0 && $totalHotel === 0): ?>
                <p class="text-slate-500 text-sm">費用タブ、宿泊タブ、または移動タブから追加してください。</p>
                <?php endif; ?>
            </div>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="hotel" id="panel-hotel">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-hotel text-slate-400"></i> 宿泊
            </h2>
            <form method="post" action="/live_trip/hotel_store.php" class="space-y-3 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="hotel">
                <input type="hidden" name="latitude" value="">
                <input type="hidden" name="longitude" value="">
                <input type="hidden" name="place_id" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">ホテル名 *</label>
                        <input type="text" name="hotel_name" required class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">住所</label>
                        <input type="text" name="address" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="Google Maps用">
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">自宅から距離</label>
                        <input type="text" name="distance_from_home" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="約○km">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">所要時間</label>
                        <input type="text" name="time_from_home" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="約○分">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">会場から距離</label>
                        <input type="text" name="distance_from_venue" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">会場から所要時間</label>
                        <input type="text" name="time_from_venue" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">チェックイン</label>
                        <input type="date" name="check_in" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">チェックアウト</label>
                        <input type="date" name="check_out" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">予約番号</label>
                        <input type="text" name="reservation_no" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">料金(円)</label>
                        <input type="number" name="price" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">メモ</label>
                    <input type="text" name="memo" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                </div>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">宿泊を追加</button>
            </form>
            <div class="space-y-4">
                <?php foreach ($hotelStays ?? [] as $h): 
                    $mapsUrl = (new \App\LiveTrip\Model\HotelStayModel())->getGoogleMapsUrl($h);
                    $hotelMapUrl = $hotelMapUrls[(int)$h['id']] ?? null;
                ?>
                <div class="hotel-item p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div class="hotel-view">
                        <div class="flex justify-between">
                            <h3 class="font-bold">
                                <?php if ($mapsUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="text-sky-600 hover:underline"><?= htmlspecialchars($h['hotel_name']) ?> <i class="fa-solid fa-external-link text-xs"></i></a>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($h['address'] ?: $h['hotel_name']) ?>&travelmode=transit" target="_blank" rel="noopener" class="text-sky-600 text-sm ml-2"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a>
                                <?php else: ?>
                                <?= htmlspecialchars($h['hotel_name']) ?>
                                <?php endif; ?>
                            </h3>
                            <span class="flex gap-1">
                                <button type="button" class="hotel-edit-btn text-slate-500 text-sm hover:text-slate-700" title="編集"><i class="fa-solid fa-pen text-xs"></i></button>
                                <form method="post" action="/live_trip/hotel_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                    <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                    <input type="hidden" name="tab" value="hotel">
                                    <button type="submit" class="text-red-500 text-sm"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </span>
                        </div>
                        <?php if ($h['address']): ?><p class="text-sm text-slate-500"><?= htmlspecialchars($h['address']) ?></p><?php endif; ?>
                        <?php if ($hotelMapUrl): ?>
                        <a href="<?= htmlspecialchars($mapsUrl !== '#' ? $mapsUrl : '#') ?>" target="_blank" rel="noopener" class="block mt-2 rounded-lg overflow-hidden border border-slate-200 max-w-[320px]">
                            <img src="<?= htmlspecialchars($hotelMapUrl) ?>" alt="<?= htmlspecialchars($h['hotel_name']) ?>" width="320" height="120" class="w-full h-auto">
                        </a>
                        <?php endif; ?>
                        <?php if ($h['distance_from_home'] || $h['time_from_home']): ?>
                        <p class="text-sm">自宅から: <?= htmlspecialchars($h['distance_from_home'] ?? '') ?> <?= htmlspecialchars($h['time_from_home'] ?? '') ?></p>
                        <?php endif; ?>
                        <?php if ($h['distance_from_venue'] || $h['time_from_venue']): ?>
                        <p class="text-sm">会場から: <?= htmlspecialchars($h['distance_from_venue'] ?? '') ?> <?= htmlspecialchars($h['time_from_venue'] ?? '') ?></p>
                        <?php endif; ?>
                        <?php if ($h['check_in']): ?><p class="text-sm">チェックイン: <?= htmlspecialchars($h['check_in']) ?></p><?php endif; ?>
                        <?php if ($h['reservation_no']): ?><p class="text-sm">予約番号: <?= htmlspecialchars($h['reservation_no']) ?></p><?php endif; ?>
                        <?php if ($h['price']): ?><p class="text-sm">¥<?= number_format($h['price']) ?></p><?php endif; ?>
                        <?php if ($h['memo']): ?><p class="text-sm text-slate-600"><?= htmlspecialchars($h['memo']) ?></p><?php endif; ?>
                    </div>
                    <form method="post" action="/live_trip/hotel_update.php" class="hotel-edit-form edit-form hidden space-y-3 p-4 bg-slate-50 rounded-lg mt-2">
                        <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                        <input type="hidden" name="tab" value="hotel">
                        <input type="hidden" name="latitude" value="<?= htmlspecialchars($h['latitude'] ?? '') ?>">
                        <input type="hidden" name="longitude" value="<?= htmlspecialchars($h['longitude'] ?? '') ?>">
                        <input type="hidden" name="place_id" value="<?= htmlspecialchars($h['place_id'] ?? '') ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">ホテル名 *</label><input type="text" name="hotel_name" value="<?= htmlspecialchars($h['hotel_name'] ?? '') ?>" required class="w-full border border-slate-200 rounded px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">住所</label><input type="text" name="address" value="<?= htmlspecialchars($h['address'] ?? '') ?>" class="w-full border border-slate-200 rounded px-3 py-2 text-sm"></div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">自宅から距離</label><input type="text" name="distance_from_home" value="<?= htmlspecialchars($h['distance_from_home'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">所要時間</label><input type="text" name="time_from_home" value="<?= htmlspecialchars($h['time_from_home'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">会場から距離</label><input type="text" name="distance_from_venue" value="<?= htmlspecialchars($h['distance_from_venue'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">会場から所要時間</label><input type="text" name="time_from_venue" value="<?= htmlspecialchars($h['time_from_venue'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">チェックイン</label><input type="date" name="check_in" value="<?= htmlspecialchars($h['check_in'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">チェックアウト</label><input type="date" name="check_out" value="<?= htmlspecialchars($h['check_out'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">予約番号</label><input type="text" name="reservation_no" value="<?= htmlspecialchars($h['reservation_no'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">料金(円)</label><input type="number" name="price" value="<?= (int)($h['price'] ?? 0) ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">メモ</label><input type="text" name="memo" value="<?= htmlspecialchars($h['memo'] ?? '') ?>" class="w-full border border-slate-200 rounded px-3 py-2 text-sm"></div>
                        <div class="flex gap-2"><button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">保存</button><button type="button" class="hotel-cancel-btn px-4 py-2 border border-slate-200 rounded text-sm">キャンセル</button></div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="destination" id="panel-destination">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-slate-400"></i> 目的地
            </h2>
            <form method="post" action="/live_trip/destination_store.php" class="space-y-3 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="destination">
                <input type="hidden" name="latitude" id="destination-latitude" value="">
                <input type="hidden" name="longitude" id="destination-longitude" value="">
                <input type="hidden" name="place_id" id="destination-place-id" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 mb-1">名前 *</label>
                        <input type="text" id="destination-name" name="name" required class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="店名・施設名など（日本語入力で候補を選択すると住所に反映）" autocomplete="off">
                        <div id="destination-name-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">種別</label>
                        <select name="destination_type" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                            <?php foreach (\App\LiveTrip\Model\DestinationModel::$types as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">住所</label>
                    <input type="text" id="destination-address" name="address" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="Google Maps用（名前で候補選択時に自動反映）">
                </div>
                <div class="grid grid-cols-2 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">訪問予定日</label>
                        <input type="date" name="visit_date" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">目安時刻</label>
                        <input type="text" name="visit_time" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="例 10:00">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">メモ</label>
                    <input type="text" name="memo" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="営業時間・注意など">
                </div>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">目的地を追加</button>
            </form>
            <div class="space-y-4">
                <?php foreach ($destinations ?? [] as $d):
                    $mapsUrl = $destinationModel->getGoogleMapsUrl($d);
                    $destMapUrl = $destinationMapUrls[(int)$d['id']] ?? null;
                    $typeLabel = \App\LiveTrip\Model\DestinationModel::$types[$d['destination_type'] ?? 'other'] ?? 'その他';
                ?>
                <div class="destination-item p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div class="destination-view">
                        <div class="flex justify-between">
                            <h3 class="font-bold">
                                <?php if ($mapsUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="text-sky-600 hover:underline"><?= htmlspecialchars($d['name']) ?> <i class="fa-solid fa-external-link text-xs"></i></a>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($d['address'] ?? $d['name']) ?>&travelmode=transit" target="_blank" rel="noopener" class="text-sky-600 text-sm ml-2"><i class="fa-solid fa-train text-slate-400"></i>電車で案内</a>
                                <?php else: ?>
                                <?= htmlspecialchars($d['name']) ?>
                                <?php endif; ?>
                                <span class="text-xs font-normal text-slate-500 ml-2"><?= htmlspecialchars($typeLabel) ?></span>
                            </h3>
                            <span class="flex gap-1">
                                <button type="button" class="destination-edit-btn text-slate-500 text-sm hover:text-slate-700" title="編集"><i class="fa-solid fa-pen text-xs"></i></button>
                                <form method="post" action="/live_trip/destination_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                    <input type="hidden" name="tab" value="destination">
                                    <button type="submit" class="text-red-500 text-sm"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </span>
                        </div>
                        <?php if (!empty($d['address'])): ?><p class="text-sm text-slate-500"><?= htmlspecialchars($d['address']) ?></p><?php endif; ?>
                        <?php if ($destMapUrl): ?>
                        <a href="<?= htmlspecialchars($mapsUrl !== '#' ? $mapsUrl : '#') ?>" target="_blank" rel="noopener" class="block mt-2 rounded-lg overflow-hidden border border-slate-200 max-w-[320px]">
                            <img src="<?= htmlspecialchars($destMapUrl) ?>" alt="<?= htmlspecialchars($d['name']) ?>" width="320" height="120" class="w-full h-auto">
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($d['visit_date'])): ?><p class="text-sm">訪問予定: <?= htmlspecialchars($d['visit_date']) ?><?= !empty($d['visit_time']) ? ' ' . htmlspecialchars($d['visit_time']) : '' ?></p><?php endif; ?>
                        <?php if (!empty($d['memo'])): ?><p class="text-sm text-slate-600"><?= htmlspecialchars($d['memo']) ?></p><?php endif; ?>
                    </div>
                    <form method="post" action="/live_trip/destination_update.php" class="destination-edit-form edit-form hidden space-y-3 p-4 bg-slate-50 rounded-lg mt-2">
                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                        <input type="hidden" name="tab" value="destination">
                        <input type="hidden" name="latitude" value="<?= htmlspecialchars($d['latitude'] ?? '') ?>">
                        <input type="hidden" name="longitude" value="<?= htmlspecialchars($d['longitude'] ?? '') ?>">
                        <input type="hidden" name="place_id" value="<?= htmlspecialchars($d['place_id'] ?? '') ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">名前 *</label><input type="text" name="name" value="<?= htmlspecialchars($d['name'] ?? '') ?>" required class="w-full border border-slate-200 rounded px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">種別</label><select name="destination_type" class="w-full border border-slate-200 rounded px-3 py-2 text-sm"><?php foreach (\App\LiveTrip\Model\DestinationModel::$types as $k => $label): ?><option value="<?= htmlspecialchars($k) ?>" <?= ($d['destination_type'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">住所</label><input type="text" name="address" value="<?= htmlspecialchars($d['address'] ?? '') ?>" class="w-full border border-slate-200 rounded px-3 py-2 text-sm"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">訪問予定日</label><input type="date" name="visit_date" value="<?= htmlspecialchars($d['visit_date'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">目安時刻</label><input type="text" name="visit_time" value="<?= htmlspecialchars($d['visit_time'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">メモ</label><input type="text" name="memo" value="<?= htmlspecialchars($d['memo'] ?? '') ?>" class="w-full border border-slate-200 rounded px-3 py-2 text-sm"></div>
                        <div class="flex gap-2"><button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">保存</button><button type="button" class="destination-cancel-btn px-4 py-2 border border-slate-200 rounded text-sm">キャンセル</button></div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="transport" id="panel-transport">
        <?php
        $transportCandidates = [];
        foreach ($trip['events'] ?? [] as $ev) {
            $p = trim($ev['event_place'] ?? $ev['hn_event_place'] ?? '');
            if ($p !== '') $transportCandidates[] = ['label' => $p . ' (会場)', 'value' => $p];
        }
        foreach ($hotelStays ?? [] as $h) {
            $v = trim($h['address'] ?? '') ?: trim($h['hotel_name'] ?? '');
            if ($v !== '') $transportCandidates[] = ['label' => $h['hotel_name'] . ' (宿泊)', 'value' => $v];
        }
        foreach ($destinations ?? [] as $d) {
            $v = trim($d['address'] ?? '') ?: trim($d['name'] ?? '');
            if ($v !== '') $transportCandidates[] = ['label' => $d['name'] . ' (' . (\App\LiveTrip\Model\DestinationModel::$types[$d['destination_type'] ?? 'other'] ?? 'その他') . ')', 'value' => $v];
        }
        ?>
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-train text-slate-400"></i> 移動
            </h2>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <p class="text-sm text-slate-600 mb-3">移動を追加する際に交通費も登録できます。費用タブで集計を確認できます。</p>
                <form id="transport-add-form" method="post" action="/live_trip/transport_store.php" class="space-y-3">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="transport">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">出発日</label>
                        <input type="date" id="transport-departure-date" name="departure_date" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">出発時刻</label>
                        <input type="time" id="transport-scheduled-time" name="scheduled_time" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" title="タイムラインに表示・Google Mapで検索の出発時刻">
                    </div>
                </div>
                <?php if (!empty($transportCandidates)): ?>
                <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                    <button type="button" id="transport-quick-select-toggle" class="text-sm font-bold text-slate-600 hover:text-slate-800 flex items-center gap-1">
                        <i class="fa-solid fa-chevron-down transport-quick-select-chevron"></i> 登録済みから選ぶ
                    </button>
                    <div id="transport-quick-select-body" class="hidden mt-2 space-y-2">
                        <p class="text-xs text-slate-500 mb-2">下のボタンをクリックすると、発・着の入力欄に反映されます。</p>
                        <div>
                            <span class="text-xs font-bold text-slate-500">発にセット:</span>
                            <div class="flex flex-wrap gap-1.5 mt-1" id="transport-quick-departure">
                                <?php foreach ($transportCandidates as $c): ?>
                                <button type="button" class="transport-quick-set-dep px-2 py-1.5 text-xs rounded-md border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 cursor-pointer shadow-sm" title="クリックで「発」に入力" data-value="<?= htmlspecialchars($c['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['label']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-500">着にセット:</span>
                            <div class="flex flex-wrap gap-1.5 mt-1" id="transport-quick-arrival">
                                <?php foreach ($transportCandidates as $c): ?>
                                <button type="button" class="transport-quick-set-arr px-2 py-1.5 text-xs rounded-md border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 cursor-pointer shadow-sm" title="クリックで「着」に入力" data-value="<?= htmlspecialchars($c['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['label']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 mb-1">発</label>
                        <input type="text" id="transport-departure" name="departure" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="発着駅・空港" autocomplete="off">
                        <div id="transport-departure-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 mb-1">着</label>
                        <input type="text" id="transport-arrival" name="arrival" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" autocomplete="off">
                        <div id="transport-arrival-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">所要時間(分)</label>
                        <input type="number" id="transport-duration-min" name="duration_min" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">交通費(円)</label>
                        <input type="number" name="transport_amount" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="費用も登録">
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" id="transport-google-maps-btn" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 inline-flex items-center gap-1" title="発・着を入力してからクリック">
                        <i class="fa-solid fa-map-location-dot text-slate-500"></i>Google Mapで検索
                    </button>
                </div>
                <div class="space-y-2">
                    <p class="text-sm text-slate-600">Google Mapsでルートを選んだあと、共有リンクをコピーして貼り付けてください。</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" id="transport-maps-link-input" class="flex-1 min-w-0 border border-slate-200 rounded px-3 py-2 text-sm" placeholder="https://www.google.com/maps/dir/... または 短縮URL" autocomplete="off">
                        <button type="button" id="transport-apply-link-btn" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50">
                            <i class="fa-solid fa-link mr-1"></i>リンクを反映
                        </button>
                    </div>
                    <input type="hidden" name="maps_link" id="transport-maps-link" value="">
                    <span id="transport-link-status" class="text-sm text-slate-500"></span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">交通機関</label>
                        <input type="text" id="transport-type" name="transport_type" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="新幹線、在来線など">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">経路メモ</label>
                    <input type="text" id="transport-route-memo" name="route_memo" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="東京→名古屋 のぞみ、名古屋→会場 在来線">
                </div>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">移動を追加</button>
                </form>
            </div>
            <div class="space-y-3">
                <?php foreach ($transportLegs ?? [] as $t): ?>
                <div class="transport-item p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div class="transport-view flex justify-between items-start">
                        <div>
                            <?php if (!empty($t['scheduled_time'])): ?><span class="font-mono font-bold text-emerald-600 mr-2"><?= htmlspecialchars($t['scheduled_time']) ?></span><?php endif; ?>
                            <?php if ($t['departure_date']): ?><span class="text-xs font-bold text-slate-500"><?= htmlspecialchars($t['departure_date']) ?></span><?php endif; ?>
                            <p class="text-slate-700"><?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?><?= !empty($t['amount']) ? ' ¥' . number_format($t['amount']) : '' ?></p>
                            <?php if ($t['departure'] || $t['arrival']): ?>
                            <p class="text-sm text-slate-500"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?><?= $t['duration_min'] ? ' (' . $t['duration_min'] . '分)' : '' ?></p>
                            <?php endif; ?>
                            <?php if (!empty($t['maps_link'])): ?>
                            <p class="mt-1"><a href="<?= htmlspecialchars($t['maps_link']) ?>" target="_blank" rel="noopener noreferrer" class="text-sm text-emerald-600 hover:underline"><i class="fa-solid fa-map-location-dot mr-1"></i>保存したルートで開く</a></p>
                            <?php endif; ?>
                        </div>
                        <span class="flex gap-1">
                            <button type="button" class="transport-edit-btn text-slate-500 text-sm hover:text-slate-700" title="編集"><i class="fa-solid fa-pen text-xs"></i></button>
                            <form method="post" action="/live_trip/transport_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                <input type="hidden" name="tab" value="transport">
                                <button type="submit" class="text-red-500 text-sm"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </span>
                    </div>
                    <form method="post" action="/live_trip/transport_update.php" class="transport-edit-form edit-form hidden space-y-3 p-3 bg-slate-50 rounded mt-2">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                        <input type="hidden" name="tab" value="transport">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">出発日</label><input type="date" name="departure_date" value="<?= htmlspecialchars($t['departure_date'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">出発時刻</label><input type="time" name="scheduled_time" value="<?php $st = $t['scheduled_time'] ?? ''; echo htmlspecialchars(preg_match('/^(\d{1,2}):(\d{2})/', trim($st), $m) ? sprintf('%02d:%02d', (int)$m[1], (int)$m[2]) : ''); ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">交通機関</label><input type="text" name="transport_type" value="<?= htmlspecialchars($t['transport_type'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">経路メモ</label><input type="text" name="route_memo" value="<?= htmlspecialchars($t['route_memo'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">発</label><input type="text" name="departure" value="<?= htmlspecialchars($t['departure'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">着</label><input type="text" name="arrival" value="<?= htmlspecialchars($t['arrival'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">所要時間(分)</label><input type="number" name="duration_min" value="<?= (int)($t['duration_min'] ?? 0) ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">交通費(円)</label><input type="number" name="amount" value="<?= (int)($t['amount'] ?? 0) ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="0"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">保存したルート（Google Maps リンク）</label>
                            <?php if (!empty($t['maps_link'])): ?><a href="<?= htmlspecialchars($t['maps_link']) ?>" target="_blank" rel="noopener noreferrer" class="text-sm text-emerald-600 hover:underline mr-2">地図で開く</a><?php endif; ?>
                            <input type="text" name="maps_link" value="<?= htmlspecialchars($t['maps_link'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm mt-1" placeholder="https://... 貼り付けて更新可">
                        </div>
                        <div class="flex gap-2"><button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button><button type="button" class="transport-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button></div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="timeline" id="panel-timeline">
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-clock text-slate-400"></i> タイムライン
                </h2>
                <div class="flex items-center gap-2">
                    <button type="button" class="lt-timeline-view-btn px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50" data-view="calendar" title="Googleカレンダー風">日表示</button>
                    <button type="button" class="lt-timeline-view-btn px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50" data-view="list" title="一覧表示">リスト</button>
                </div>
            </div>
            <form method="post" action="/live_trip/timeline_store.php" class="flex flex-wrap gap-2 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="timeline">
                <div><label class="block text-xs font-bold text-slate-500 mb-1">日付</label><input type="date" name="scheduled_date" class="border border-slate-200 rounded px-3 py-2 text-sm w-36" title="未入力時は当日"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">項目</label><input type="text" name="label" placeholder="開場、開演など" class="border border-slate-200 rounded px-3 py-2 text-sm w-32" required></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">時刻</label><input type="time" name="scheduled_time" class="border border-slate-200 rounded px-3 py-2 text-sm w-32"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">終了</label><input type="time" name="end_time" class="border border-slate-200 rounded px-3 py-2 text-sm w-32" title="未指定なら30分扱い"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">所要(分)</label><input type="number" name="duration_min" min="1" class="border border-slate-200 rounded px-3 py-2 text-sm w-28" placeholder="30"></div>
                <input type="text" name="memo" placeholder="メモ" class="border border-slate-200 rounded px-3 py-2 text-sm flex-1 min-w-24">
                <button type="submit" class="lt-theme-btn text-white px-3 py-2 rounded text-sm">追加</button>
            </form>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
            <?php
            $dayStartMin = 6 * 60;
            $dayEndMin = 24 * 60;
            $pxPerMin = 1; // 1時間=60px（半分）
            $laneHeight = ($dayEndMin - $dayStartMin) * $pxPerMin;

            $timelineByDay = [];
            foreach ($mergedTimeline ?? [] as $m) {
                $d = $m['date'] ?? '';
                $t = $m['time'] ?? '';
                if (!$d || !$t || $t === '99:99') continue;
                if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($t), $mm)) continue;
                $startMin = ((int)$mm[1]) * 60 + (int)$mm[2];
                $timelineByDay[$d] ??= [];
                $timelineByDay[$d][] = $m + ['_startMin' => $startMin];
            }
            ksort($timelineByDay);
            ?>
            <div class="lt-timeline-calendar hidden space-y-6">
                <?php foreach ($timelineByDay as $day => $rows): ?>
                    <?php
                    $dayLabel = $day;
                    if (!empty($eventDates) && $day) {
                        $firstEv = $eventDates[0];
                        $lastEv = $eventDates[count($eventDates) - 1];
                        if (in_array($day, $eventDates, true)) $dayLabel .= '（当日）';
                        elseif ($day < $firstEv) $dayLabel .= '（前日）';
                        elseif ($day > $lastEv) $dayLabel .= '（翌日）';
                    }
                    $sameStartBuckets = [];
                    foreach ($rows as $r) {
                        $k = (string)($r['_startMin'] ?? 0);
                        $sameStartBuckets[$k] ??= 0;
                        $sameStartBuckets[$k]++;
                    }
                    $sameStartIndex = [];
                    ?>
                    <div class="lt-day">
                        <p class="text-xs font-bold text-slate-500 mb-2"><?= htmlspecialchars($dayLabel) ?></p>
                        <div class="lt-day-grid grid grid-cols-[52px_1fr] gap-3">
                            <div class="lt-time-col text-[11px] text-slate-500 font-mono">
                                <?php for ($h = 6; $h <= 23; $h++): ?>
                                    <div class="h-[60px] flex items-start justify-end pr-1"><?= sprintf('%02d:00', $h) ?></div>
                                <?php endfor; ?>
                            </div>
                            <div class="lt-lane relative rounded-lg border border-slate-200 bg-white overflow-hidden" style="height: <?= (int)$laneHeight ?>px;">
                                <?php for ($h = 6; $h <= 23; $h++): ?>
                                    <div class="absolute left-0 right-0 border-t border-slate-100" style="top: <?= (int)(($h*60 - $dayStartMin) * $pxPerMin) ?>px;"></div>
                                <?php endfor; ?>
                                <?php foreach ($rows as $m): ?>
                                    <?php
                                    $startMin = (int)($m['_startMin'] ?? 0);
                                    $dur = (int)($m['duration_min'] ?? 30);
                                    if ($dur <= 0) $dur = 30;
                                    $endMin = $startMin + $dur;
                                    $visStart = max($startMin, $dayStartMin);
                                    $visEnd = min($endMin, $dayEndMin);
                                    if ($visEnd <= $visStart) continue;
                                    $top = ($visStart - $dayStartMin) * $pxPerMin;
                                    $height = ($visEnd - $visStart) * $pxPerMin;
                                    if ($height < 18) $height = 18;
                                    if ($top + $height > $laneHeight) $height = max(18, $laneHeight - $top);

                                    $bucketKey = (string)$startMin;
                                    $sameStartIndex[$bucketKey] ??= 0;
                                    $idx = $sameStartIndex[$bucketKey]++;
                                    $bucketCount = (int)($sameStartBuckets[$bucketKey] ?? 1);
                                    $cols = $bucketCount >= 2 ? 2 : 1;
                                    $col = $cols === 2 ? ($idx % 2) : 0;
                                    $left = $cols === 2 ? (8 + $col * 50) : 8;
                                    $right = $cols === 2 ? 8 : 8;
                                    $widthStyle = $cols === 2 ? 'width: calc(50% - 12px);' : 'right: 8px;';

                                    $bg = $m['type'] === 'transport' ? 'bg-sky-50 border-sky-200' : 'bg-emerald-50 border-emerald-200';
                                    $label = '';
                                    $sub = '';
                                    if ($m['type'] === 'timeline') {
                                        $ti = $m['data'] ?? [];
                                        $label = (string)($ti['label'] ?? '');
                                        $sub = (string)($ti['memo'] ?? '');
                                    } else {
                                        $tl = $m['data'] ?? [];
                                        $label = trim(($tl['transport_type'] ?? '') . ' ' . ($tl['route_memo'] ?? ''));
                                        $sub = trim(($tl['departure'] ?? '') . ($tl['arrival'] ? ' → ' . ($tl['arrival'] ?? '') : ''));
                                    }
                                    ?>
                                    <div class="lt-event absolute rounded-lg border shadow-sm <?= $bg ?> p-2 text-xs text-slate-700 overflow-hidden cursor-pointer"
                                         data-lt-type="<?= htmlspecialchars($m['type']) ?>"
                                         data-lt-id="<?= (int)(($m['data']['id'] ?? 0)) ?>"
                                         data-lt-date="<?= htmlspecialchars($day) ?>"
                                         data-lt-time="<?= htmlspecialchars($m['time']) ?>"
                                         data-lt-duration="<?= (int)$dur ?>"
                                         data-lt-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
                                         data-lt-memo="<?= htmlspecialchars($sub, ENT_QUOTES) ?>"
                                         data-lt-scheduled-date="<?= htmlspecialchars((string)($m['date'] ?? '')) ?>"
                                         data-lt-transport-type="<?= htmlspecialchars((string)(($m['data']['transport_type'] ?? '')), ENT_QUOTES) ?>"
                                         data-lt-route-memo="<?= htmlspecialchars((string)(($m['data']['route_memo'] ?? '')), ENT_QUOTES) ?>"
                                         data-lt-departure="<?= htmlspecialchars((string)(($m['data']['departure'] ?? '')), ENT_QUOTES) ?>"
                                         data-lt-arrival="<?= htmlspecialchars((string)(($m['data']['arrival'] ?? '')), ENT_QUOTES) ?>"
                                         data-lt-duration-min-transport="<?= (int)(($m['data']['duration_min'] ?? 0)) ?>"
                                         data-lt-amount="<?= (int)(($m['data']['amount'] ?? 0)) ?>"
                                         data-lt-maps-link="<?= htmlspecialchars((string)(($m['data']['maps_link'] ?? '')), ENT_QUOTES) ?>"
                                         style="top: <?= (int)$top ?>px; left: <?= (int)$left ?>px; <?= $widthStyle ?> height: <?= (int)$height ?>px;">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="font-mono font-bold text-slate-600 shrink-0"><?= htmlspecialchars($m['time']) ?></span>
                                                    <span class="font-bold truncate flex-1 min-w-0"><?= htmlspecialchars($label) ?></span>
                                                    <span class="text-slate-400 font-normal shrink-0">(<?= (int)$dur ?>m)</span>
                                                </div>
                                            </div>
                                            <div class="shrink-0 flex gap-1">
                                                <?php if ($m['type'] === 'timeline'): $ti = $m['data']; ?>
                                                    <button type="button" class="timeline-edit-btn text-slate-500 text-[11px] hover:text-slate-700" title="編集" data-id="<?= (int)$ti['id'] ?>"><i class="fa-solid fa-pen"></i></button>
                                                    <form method="post" action="/live_trip/timeline_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                                        <input type="hidden" name="id" value="<?= (int)$ti['id'] ?>">
                                                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                                        <input type="hidden" name="tab" value="timeline">
                                                        <button type="submit" class="text-red-500 text-[11px]"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                <?php else: $tl = $m['data']; ?>
                                                    <button type="button" class="transport-timeline-edit-btn text-slate-500 text-[11px] hover:text-slate-700" title="時刻を編集"><i class="fa-solid fa-pen"></i></button>
                                                    <form method="post" action="/live_trip/transport_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                                        <input type="hidden" name="id" value="<?= (int)$tl['id'] ?>">
                                                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                                        <input type="hidden" name="tab" value="timeline">
                                                        <button type="submit" class="text-red-500 text-[11px]"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($sub !== ''): ?><div class="text-[11px] text-slate-500 mt-1 truncate"><?= htmlspecialchars($sub) ?></div><?php endif; ?>

                                        <?php if ($m['type'] === 'timeline'): $ti = $m['data']; ?>
                                        <?php
                                            $st = trim($ti['scheduled_time'] ?? '');
                                            $prefillEnd = '';
                                            if (preg_match('/^(\d{1,2}):(\d{2})/', $st, $mm3)) {
                                                $sm = ((int)$mm3[1]) * 60 + (int)$mm3[2];
                                                $em = $sm + (int)$dur;
                                                if ($em > 0 && $em <= 24*60) {
                                                    $prefillEnd = sprintf('%02d:%02d', intdiv($em, 60), $em % 60);
                                                }
                                            }
                                        ?>
                                        <form method="post" action="/live_trip/timeline_update.php" class="timeline-edit-form edit-form hidden flex flex-wrap gap-2 items-center p-2 bg-slate-50 rounded mt-2">
                                            <input type="hidden" name="id" value="<?= (int)$ti['id'] ?>">
                                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                            <input type="hidden" name="tab" value="timeline">
                                            <div><label class="block text-xs font-bold text-slate-500 mb-0.5">日付</label><input type="date" name="scheduled_date" value="<?= htmlspecialchars($ti['scheduled_date'] ?? '') ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32"></div>
                                            <input type="text" name="label" value="<?= htmlspecialchars($ti['label'] ?? '') ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-28" required>
                                            <input type="time" name="scheduled_time" value="<?php $st = $ti['scheduled_time'] ?? ''; echo htmlspecialchars(preg_match('/^(\d{1,2}):(\d{2})/', trim($st), $m4) ? sprintf('%02d:%02d', (int)$m4[1], (int)$m4[2]) : ''); ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32">
                                            <input type="time" name="end_time" value="<?= htmlspecialchars($prefillEnd) ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32" title="未指定なら30分扱い">
                                            <input type="number" name="duration_min" min="1" value="<?= (int)$dur ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-24" title="所要時間(分)。指定すると終了より優先">
                                            <input type="text" name="memo" value="<?= htmlspecialchars($ti['memo'] ?? '') ?>" placeholder="メモ" class="border border-slate-200 rounded px-2 py-1 text-sm flex-1 min-w-24">
                                            <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                                            <button type="button" class="timeline-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($m['type'] === 'transport'): $tl = $m['data']; ?>
                                        <form method="post" action="/live_trip/transport_update.php" class="transport-timeline-edit-form edit-form hidden flex flex-wrap gap-2 items-center p-2 bg-slate-50 rounded mt-2">
                                            <input type="hidden" name="id" value="<?= (int)$tl['id'] ?>">
                                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                            <input type="hidden" name="tab" value="timeline">
                                            <input type="hidden" name="departure_date" value="<?= htmlspecialchars($tl['departure_date'] ?? '') ?>">
                                            <input type="hidden" name="transport_type" value="<?= htmlspecialchars($tl['transport_type'] ?? '') ?>">
                                            <input type="hidden" name="route_memo" value="<?= htmlspecialchars($tl['route_memo'] ?? '') ?>">
                                            <input type="hidden" name="departure" value="<?= htmlspecialchars($tl['departure'] ?? '') ?>">
                                            <input type="hidden" name="arrival" value="<?= htmlspecialchars($tl['arrival'] ?? '') ?>">
                                            <input type="hidden" name="duration_min" value="<?= (int)($tl['duration_min'] ?? 0) ?>">
                                            <input type="hidden" name="amount" value="<?= (int)($tl['amount'] ?? 0) ?>">
                                            <input type="hidden" name="maps_link" value="<?= htmlspecialchars($tl['maps_link'] ?? '') ?>">
                                            <label class="text-sm font-bold text-slate-600">出発時刻:</label>
                                            <input type="time" name="scheduled_time" value="<?php $st = $tl['scheduled_time'] ?? ''; echo htmlspecialchars(preg_match('/^(\d{1,2}):(\d{2})/', trim($st), $m5) ? sprintf('%02d:%02d', (int)$m5[1], (int)$m5[2]) : ''); ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32">
                                            <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                                            <button type="button" class="transport-timeline-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($timelineByDay)): ?>
                    <p class="text-slate-500 text-sm">タイムラインがありません。</p>
                <?php endif; ?>
            </div>

            <div class="lt-timeline-list">
            <?php
            $lastDate = '';
            foreach ($mergedTimeline ?? [] as $m):
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
            <p class="text-xs font-bold text-slate-500 <?= $isFirstGroup ? 'mt-0' : 'mt-4' ?> mb-2"><?= htmlspecialchars($dayLabel) ?></p>
            <?php endif; ?>
            <div class="timeline-row py-2 border-b border-slate-100 <?= $m['type'] === 'timeline' ? 'timeline-item' : '' ?>">
                <div class="timeline-view flex justify-between items-center">
                    <span class="font-mono font-bold text-emerald-600 w-14 shrink-0"><?= htmlspecialchars($m['time'] !== '99:99' ? $m['time'] : '') ?></span>
                    <span class="flex-1">
                        <?php if ($m['type'] === 'timeline'): $ti = $m['data']; ?>
                        <?= htmlspecialchars($ti['label']) ?><?= $ti['memo'] ? ' · ' . htmlspecialchars($ti['memo']) : '' ?>
                        <?php else: $tl = $m['data']; ?>
                        <i class="fa-solid fa-train text-slate-400 mr-1"></i><?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?><?= $tl['departure'] || $tl['arrival'] ? ' (' . htmlspecialchars($tl['departure'] ?? '') . '→' . htmlspecialchars($tl['arrival'] ?? '') . ')' : '' ?>
                        <?php endif; ?>
                    </span>
                    <span class="flex gap-1">
                        <?php if ($m['type'] === 'timeline'): $ti = $m['data']; ?>
                        <button type="button" class="timeline-edit-btn text-slate-500 text-sm hover:text-slate-700" title="編集" data-id="<?= (int)$ti['id'] ?>"><i class="fa-solid fa-pen text-xs"></i></button>
                        <form method="post" action="/live_trip/timeline_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                            <input type="hidden" name="id" value="<?= (int)$ti['id'] ?>">
                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                            <input type="hidden" name="tab" value="timeline">
                            <button type="submit" class="text-red-500 text-sm"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                        <?php else: $tl = $m['data']; ?>
                        <button type="button" class="transport-timeline-edit-btn text-slate-500 text-sm hover:text-slate-700" title="時刻を編集"><i class="fa-solid fa-pen text-xs"></i></button>
                        <form method="post" action="/live_trip/transport_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                            <input type="hidden" name="id" value="<?= (int)$tl['id'] ?>">
                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                            <input type="hidden" name="tab" value="timeline">
                            <button type="submit" class="text-red-500 text-sm"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($m['type'] === 'timeline'): $ti = $m['data']; ?>
                <form method="post" action="/live_trip/timeline_update.php" class="timeline-edit-form edit-form hidden flex flex-wrap gap-2 items-center p-2 bg-slate-50 rounded mt-1">
                    <input type="hidden" name="id" value="<?= (int)$ti['id'] ?>">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="timeline">
                    <div><label class="block text-xs font-bold text-slate-500 mb-0.5">日付</label><input type="date" name="scheduled_date" value="<?= htmlspecialchars($ti['scheduled_date'] ?? '') ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32"></div>
                    <input type="text" name="label" value="<?= htmlspecialchars($ti['label'] ?? '') ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-28" required>
                    <input type="time" name="scheduled_time" value="<?php $st = $ti['scheduled_time'] ?? ''; echo htmlspecialchars(preg_match('/^(\d{1,2}):(\d{2})/', trim($st), $m) ? sprintf('%02d:%02d', (int)$m[1], (int)$m[2]) : ''); ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32">
                    <?php
                        $dur = (int)($m['duration_min'] ?? 30);
                        if ($dur <= 0) $dur = 30;
                        $prefillEnd = '';
                        if (preg_match('/^(\d{1,2}):(\d{2})/', trim($ti['scheduled_time'] ?? ''), $mm6)) {
                            $sm = ((int)$mm6[1]) * 60 + (int)$mm6[2];
                            $em = $sm + $dur;
                            if ($em > 0 && $em <= 24*60) $prefillEnd = sprintf('%02d:%02d', intdiv($em, 60), $em % 60);
                        }
                    ?>
                    <input type="time" name="end_time" value="<?= htmlspecialchars($prefillEnd) ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32" title="未指定なら30分扱い">
                    <input type="number" name="duration_min" min="1" value="<?= (int)$dur ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-24" title="所要時間(分)。指定すると終了より優先">
                    <input type="text" name="memo" value="<?= htmlspecialchars($ti['memo'] ?? '') ?>" placeholder="メモ" class="border border-slate-200 rounded px-2 py-1 text-sm flex-1 min-w-24">
                    <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                    <button type="button" class="timeline-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                </form>
                <?php endif; ?>
                <?php if ($m['type'] === 'transport'): $tl = $m['data']; ?>
                <form method="post" action="/live_trip/transport_update.php" class="transport-timeline-edit-form edit-form hidden flex flex-wrap gap-2 items-center p-2 bg-slate-50 rounded mt-1">
                    <input type="hidden" name="id" value="<?= (int)$tl['id'] ?>">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="timeline">
                    <input type="hidden" name="departure_date" value="<?= htmlspecialchars($tl['departure_date'] ?? '') ?>">
                    <input type="hidden" name="transport_type" value="<?= htmlspecialchars($tl['transport_type'] ?? '') ?>">
                    <input type="hidden" name="route_memo" value="<?= htmlspecialchars($tl['route_memo'] ?? '') ?>">
                    <input type="hidden" name="departure" value="<?= htmlspecialchars($tl['departure'] ?? '') ?>">
                    <input type="hidden" name="arrival" value="<?= htmlspecialchars($tl['arrival'] ?? '') ?>">
                    <input type="hidden" name="duration_min" value="<?= (int)($tl['duration_min'] ?? 0) ?>">
                    <input type="hidden" name="amount" value="<?= (int)($tl['amount'] ?? 0) ?>">
                    <input type="hidden" name="maps_link" value="<?= htmlspecialchars($tl['maps_link'] ?? '') ?>">
                    <label class="text-sm font-bold text-slate-600">出発時刻:</label>
                    <input type="time" name="scheduled_time" value="<?php $st = $tl['scheduled_time'] ?? ''; echo htmlspecialchars(preg_match('/^(\d{1,2}):(\d{2})/', trim($st), $m) ? sprintf('%02d:%02d', (int)$m[1], (int)$m[2]) : ''); ?>" class="border border-slate-200 rounded px-2 py-1 text-sm w-32">
                    <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                    <button type="button" class="transport-timeline-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
            </div>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="checklist" id="panel-checklist">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-slate-400"></i> チェックリスト
            </h2>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <div class="flex justify-between items-center gap-2 mb-2">
                    <p class="text-xs font-bold text-slate-500">マイリストを適用</p>
                    <a href="/live_trip/my_list.php?redirect=<?= urlencode('/live_trip/show.php?id=' . (int)$trip['id'] . '#checklist') ?>" class="text-sm lt-theme-link hover:underline shrink-0" title="持ち物マイリストの管理">マイリストを管理</a>
                </div>
                <form method="post" action="/live_trip/apply_mylist.php" class="flex flex-wrap gap-2">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="checklist">
                    <select name="my_list_id" class="border border-slate-200 rounded px-3 py-2 text-sm flex-1 min-w-0">
                        <option value="">選択</option>
                        <?php
                        $myLists = [];
                        try { $myLists = (new \App\LiveTrip\Model\MyListModel())->getListsForSelect(); } catch (\Throwable $e) { }
                        foreach ($myLists as $ml): ?>
                        <option value="<?= (int)$ml['id'] ?>"><?= htmlspecialchars($ml['list_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-3 py-2 border border-slate-200 rounded text-sm hover:bg-slate-50 shrink-0">適用</button>
                </form>
            </div>
            <form id="checklist-add-form" class="flex gap-2 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="text" name="item_name" id="checklist-item-input" placeholder="チケット確認、財布など" class="border border-slate-200 rounded px-3 py-2 text-sm flex-1" required>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">追加</button>
            </form>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <div class="flex justify-between items-center gap-2 mb-3">
                    <p class="text-xs font-bold text-slate-500">チェックリスト</p>
                    <button type="button" id="mylist-register-btn" class="text-sm font-medium lt-theme-link hover:underline shrink-0">
                        <i class="fa-solid fa-cloud-arrow-up mr-0.5"></i>マイリスト登録
                    </button>
                </div>
                <div id="checklist-list" class="lt-checklist-sortable">
            <?php foreach ($checklistItems ?? [] as $ci): ?>
            <div class="lt-checklist-row flex items-center gap-2 py-2 border-b border-slate-100" data-id="<?= (int)$ci['id'] ?>">
                <span class="lt-checklist-drag-handle cursor-grab text-slate-400 hover:text-slate-600 shrink-0" title="並び替え"><i class="fa-solid fa-grip-vertical text-xs"></i></span>
                <button type="button" class="checklist-toggle text-left flex-1 flex items-center gap-2 min-w-0" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>">
                    <i class="checklist-icon fa-<?= $ci['checked'] ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle text-slate-300' ?> w-5"></i>
                    <span class="checklist-label <?= $ci['checked'] ? 'line-through text-slate-400' : '' ?>"><?= htmlspecialchars($ci['item_name']) ?></span>
                </button>
                <button type="button" class="lt-edit-btn text-slate-500 text-sm hover:text-slate-700 px-1" title="編集" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>" data-name="<?= htmlspecialchars($ci['item_name']) ?>"><i class="fa-solid fa-pen text-xs"></i></button>
                <button type="button" class="checklist-delete text-red-500 text-sm hover:text-red-600" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>"><i class="fa-solid fa-trash-can"></i></button>
            </div>
            <?php endforeach; ?>
                </div>
            </div>
        </div>
        </div>
    </div>
</main>

<div id="mylist-register-modal" class="lt-modal-overlay hidden" aria-hidden="true">
    <div class="lt-modal m-4" role="dialog" aria-labelledby="mylist-modal-title">
        <div class="p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 id="mylist-modal-title" class="font-bold text-slate-700">マイリストに登録</h3>
                <button type="button" id="mylist-modal-close" class="text-slate-400 hover:text-slate-600 p-1" aria-label="閉じる"><i class="fa-solid fa-times"></i></button>
            </div>
            <p class="text-sm text-slate-500 mb-4">チェックリストをマイリストテンプレートとして保存します</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">新規リストを作成</label>
                    <form method="post" action="/live_trip/save_checklist_to_mylist.php" class="flex gap-2 mt-1">
                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                        <input type="hidden" name="tab" value="checklist">
                        <input type="hidden" name="action" value="new">
                        <input type="text" name="list_name" placeholder="例: 遠征基本セット" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
                        <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold shrink-0">作成</button>
                    </form>
                </div>
                <?php if (!empty($myLists)): ?>
                <div class="pt-3 border-t border-slate-200">
                    <label class="block text-xs font-bold text-slate-500 mb-1">既存リストを更新</label>
                    <form method="post" action="/live_trip/save_checklist_to_mylist.php" class="flex gap-2 mt-1">
                        <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                        <input type="hidden" name="tab" value="checklist">
                        <input type="hidden" name="action" value="add">
                        <select name="my_list_id" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
                            <option value="">選択</option>
                            <?php foreach ($myLists as $ml): ?>
                            <option value="<?= (int)$ml['id'] ?>"><?= htmlspecialchars($ml['list_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 shrink-0">更新</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="lt-timeline-modal" class="lt-modal-overlay hidden" aria-hidden="true">
    <div class="lt-modal m-4" role="dialog" aria-labelledby="lt-timeline-modal-title">
        <div class="p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 id="lt-timeline-modal-title" class="font-bold text-slate-700">予定を編集</h3>
                <button type="button" id="lt-timeline-modal-close" class="text-slate-400 hover:text-slate-600 p-1" aria-label="閉じる"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div class="text-xs text-slate-500">
                    <span id="lt-timeline-modal-meta"></span>
                </div>

                <form method="post" action="/live_trip/timeline_update.php" id="lt-timeline-modal-form-timeline" class="space-y-3 hidden">
                    <input type="hidden" name="id" id="lt-timeline-modal-timeline-id" value="">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="timeline">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">日付</label>
                            <input type="date" name="scheduled_date" id="lt-timeline-modal-date" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">開始</label>
                            <input type="time" name="scheduled_time" id="lt-timeline-modal-start" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">終了</label>
                            <input type="time" name="end_time" id="lt-timeline-modal-end" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" title="未指定なら30分扱い">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">所要(分)</label>
                            <input type="number" name="duration_min" id="lt-timeline-modal-duration" min="1" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="30">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">項目</label>
                        <input type="text" name="label" id="lt-timeline-modal-label" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">メモ</label>
                        <input type="text" name="memo" id="lt-timeline-modal-memo" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50" data-modal-cancel>キャンセル</button>
                        <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold">保存</button>
                    </div>
                </form>

                <form method="post" action="/live_trip/transport_update.php" id="lt-timeline-modal-form-transport" class="space-y-3 hidden">
                    <input type="hidden" name="id" id="lt-timeline-modal-transport-id" value="">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="timeline">
                    <input type="hidden" name="departure_date" id="lt-timeline-modal-transport-date" value="">
                    <input type="hidden" name="transport_type" id="lt-timeline-modal-transport-type" value="">
                    <input type="hidden" name="route_memo" id="lt-timeline-modal-route-memo" value="">
                    <input type="hidden" name="departure" id="lt-timeline-modal-departure" value="">
                    <input type="hidden" name="arrival" id="lt-timeline-modal-arrival" value="">
                    <input type="hidden" name="duration_min" id="lt-timeline-modal-transport-duration" value="">
                    <input type="hidden" name="amount" id="lt-timeline-modal-amount" value="">
                    <input type="hidden" name="maps_link" id="lt-timeline-modal-maps-link" value="">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">出発</label>
                            <input type="time" name="scheduled_time" id="lt-timeline-modal-transport-start" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">到着</label>
                            <input type="time" id="lt-timeline-modal-transport-end" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" title="所要(分)から自動計算。直接入力も可">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1">所要(分)</label>
                            <input type="number" id="lt-timeline-modal-transport-duration-edit" min="1" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="30">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50" data-modal-cancel>キャンセル</button>
                        <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var tripId = <?= (int)$trip['id'] ?>;

    function esc(s) { var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function rowHtml(ci) {
        var checked = parseInt(ci.checked,10)||0;
        var iconCls = checked ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle text-slate-300';
        var labelCls = checked ? 'line-through text-slate-400' : '';
        return '<div class="lt-checklist-row flex items-center gap-2 py-2 border-b border-slate-100" data-id="'+ci.id+'">'+
            '<span class="lt-checklist-drag-handle cursor-grab text-slate-400 hover:text-slate-600 shrink-0" title="並び替え"><i class="fa-solid fa-grip-vertical text-xs"></i></span>'+
            '<button type="button" class="checklist-toggle text-left flex-1 flex items-center gap-2 min-w-0" data-id="'+ci.id+'" data-trip="'+tripId+'">'+
            '<i class="checklist-icon fa-'+iconCls+' w-5"></i>'+
            '<span class="checklist-label '+labelCls+'">'+esc(ci.item_name||'')+'</span></button>'+
            '<button type="button" class="lt-edit-btn text-slate-500 text-sm hover:text-slate-700 px-1" title="編集" data-id="'+ci.id+'" data-trip="'+tripId+'" data-name="'+esc(ci.item_name||'')+'"><i class="fa-solid fa-pen text-xs"></i></button>'+
            '<button type="button" class="checklist-delete text-red-500 text-sm hover:text-red-600" data-id="'+ci.id+'" data-trip="'+tripId+'"><i class="fa-solid fa-trash-can"></i></button>'+
        '</div>';
    }
    function bindRow(row) {
        row.querySelector('.checklist-toggle')?.addEventListener('click', onToggle);
        row.querySelector('.lt-edit-btn')?.addEventListener('click', onEdit);
        row.querySelector('.checklist-delete')?.addEventListener('click', onDelete);
    }
    function onToggle() {
        var id=this.dataset.id, trip=this.dataset.trip;
        fetch('/live_trip/checklist_toggle.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:'id='+id+'&trip_plan_id='+trip })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.status==='ok') {
                    document.querySelectorAll('.lt-checklist-row[data-id="'+id+'"]').forEach(function(row){
                        var icon = row.querySelector('.checklist-icon');
                        var label = row.querySelector('.checklist-label');
                        if (!icon || !label) return;
                        var wClass = icon.classList.contains('w-4') ? 'w-4' : 'w-5';
                        if (data.checked) {
                            icon.className = 'checklist-icon fa-solid fa-circle-check text-emerald-500 '+wClass;
                            label.classList.add('line-through','text-slate-400');
                        } else {
                            icon.className = 'checklist-icon fa-regular fa-circle text-slate-300 '+wClass;
                            label.classList.remove('line-through','text-slate-400');
                        }
                    });
                }
            });
    }
    function onEdit() {
        var id=this.dataset.id, trip=this.dataset.trip, name=this.dataset.name;
        var row = document.querySelector('.lt-checklist-row[data-id="'+id+'"]');
        if (!row) return;
        var toggle = row.querySelector('.checklist-toggle');
        var wasChecked = toggle && toggle.querySelector('.checklist-icon')?.classList.contains('fa-solid');
        var inp = document.createElement('input');
        inp.type='text';
        inp.className='border border-slate-200 rounded px-2 py-1 text-sm flex-1';
        inp.value = name;
        inp.addEventListener('blur', saveEdit);
        inp.addEventListener('keydown', function(e){ if (e.key==='Enter') inp.blur(); if (e.key==='Escape') { inp.value=name; inp.blur(); } });
        toggle.replaceWith(inp);
        inp.focus();
        function saveEdit() {
            var newName = inp.value.trim();
            if (newName===name) {
                var wrap=document.createElement('button');
                wrap.className='checklist-toggle text-left flex-1 flex items-center gap-2';
                wrap.dataset.id=id; wrap.dataset.trip=trip;
                var iconCls = wasChecked ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle text-slate-300';
                var labelCls = wasChecked ? 'line-through text-slate-400' : '';
                wrap.innerHTML='<i class="checklist-icon fa-'+iconCls+' w-5"></i><span class="checklist-label '+labelCls+'">'+esc(name)+'</span>';
                row.replaceChild(wrap, inp);
                bindRow(row);
                return;
            }
            fetch('/live_trip/checklist_update.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:'id='+id+'&trip_plan_id='+trip+'&item_name='+encodeURIComponent(newName) })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (data.status==='ok' && data.item) {
                        var wrap=document.createElement('button');
                        wrap.className='checklist-toggle text-left flex-1 flex items-center gap-2';
                        wrap.dataset.id=id; wrap.dataset.trip=trip;
                        var c=data.item.checked;
                        var ic=c ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle text-slate-300';
                        var lc=c ? 'line-through text-slate-400' : '';
                        wrap.innerHTML='<i class="checklist-icon fa-'+ic+' w-5"></i><span class="checklist-label '+lc+'">'+esc(data.item.item_name||'')+'</span>';
                        row.replaceChild(wrap, inp);
                        row.querySelector('.lt-edit-btn').dataset.name = data.item.item_name||'';
                        bindRow(row);
                    }
                });
        }
    }
    function onDelete() {
        var id=this.dataset.id, trip=this.dataset.trip;
        var rows = document.querySelectorAll('.lt-checklist-row[data-id="'+id+'"]');
        fetch('/live_trip/checklist_delete.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:'id='+id+'&trip_plan_id='+trip })
            .then(function(r){ return r.json(); })
            .then(function(data){ if (data.status==='ok') rows.forEach(function(r){ r.remove(); }); });
    }

    document.querySelectorAll('.checklist-toggle').forEach(function(){});
    document.querySelectorAll('.lt-checklist-row').forEach(function(row){ bindRow(row); });

    if (typeof Sortable !== 'undefined') {
        var list = document.getElementById('checklist-list');
        if (list) {
            new Sortable(list, {
                animation: 150,
                handle: '.lt-checklist-drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    var ids = Array.from(list.querySelectorAll('.lt-checklist-row')).map(function(r) { return r.dataset.id; }).filter(Boolean);
                    if (ids.length === 0) return;
                    var fd = new FormData();
                    fd.append('trip_plan_id', tripId);
                    ids.forEach(function(id, i) { fd.append('order[]', id); });
                    fetch('/live_trip/checklist_reorder.php', { method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'} })
                        .then(function(r) { return r.json(); })
                        .then(function(data) { if (data.status !== 'ok') console.warn('Reorder failed'); });
                }
            });
        }
    }

    document.getElementById('checklist-add-form')?.addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(this);
        var inp = document.getElementById('checklist-item-input');
        if (!inp.value.trim()) return;
        fetch('/live_trip/checklist_store.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.status==='ok' && data.item) {
                    var list = document.getElementById('checklist-list');
                    var div = document.createElement('div');
                    div.innerHTML = rowHtml(data.item);
                    var row = div.firstElementChild;
                    list.appendChild(row);
                    bindRow(row);
                    inp.value = '';
                }
            });
    });

    var hash = (location.hash||'').replace('#','');
    var defaultTab = ['summary','info','expense','hotel','destination','transport','timeline','checklist'].indexOf(hash)>=0 ? hash : 'summary';
    document.querySelectorAll('.lt-tab').forEach(function(t){
        t.classList.toggle('active', t.dataset.tab===defaultTab);
    });
    document.querySelectorAll('.lt-tab-panel').forEach(function(p){
        p.classList.toggle('active', p.dataset.panel===defaultTab);
    });
    function switchTab(tab) {
        document.querySelectorAll('.lt-tab').forEach(function(x){ x.classList.toggle('active', x.dataset.tab===tab); });
        document.querySelectorAll('.lt-tab-panel').forEach(function(x){ x.classList.toggle('active', x.dataset.panel===tab); });
        location.hash = tab;
    }
    document.querySelectorAll('.lt-tab').forEach(function(t){
        t.addEventListener('click', function(){ switchTab(this.dataset.tab); });
    });
    document.querySelectorAll('.js-switch-tab').forEach(function(btn){
        btn.addEventListener('click', function(){ switchTab(this.dataset.tab); });
    });
    if (defaultTab && !location.hash) location.hash = defaultTab;
    window.switchTab = switchTab;

    function setTimelineView(view) {
        var panel = document.getElementById('panel-timeline');
        if (!panel) return;
        var cal = panel.querySelector('.lt-timeline-calendar');
        var list = panel.querySelector('.lt-timeline-list');
        if (cal) cal.classList.toggle('hidden', view !== 'calendar');
        if (list) list.classList.toggle('hidden', view === 'calendar');
        panel.querySelectorAll('.lt-timeline-view-btn').forEach(function(btn){
            btn.classList.toggle('is-active', btn.dataset.view === view);
        });
        try { localStorage.setItem('ltTimelineView', view); } catch (e) {}
    }
    var initialView = 'calendar';
    try {
        var saved = localStorage.getItem('ltTimelineView');
        if (saved === 'calendar' || saved === 'list') initialView = saved;
    } catch (e) {}
    setTimelineView(initialView);
    document.querySelectorAll('.lt-timeline-view-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            setTimelineView(this.dataset.view || 'calendar');
        });
    });

    function setSummaryTimelineView(view) {
        var root = document.querySelector('.lt-summary-timeline-calendar')?.closest('.p-4');
        if (!root) return;
        var cal = root.querySelector('.lt-summary-timeline-calendar');
        var list = root.querySelector('.lt-summary-timeline-list');
        if (cal) cal.classList.toggle('hidden', view !== 'calendar');
        if (list) list.classList.toggle('hidden', view === 'calendar');
        root.querySelectorAll('.lt-timeline-summary-view-btn').forEach(function(btn){
            btn.classList.toggle('is-active', btn.dataset.view === view);
        });
        try { localStorage.setItem('ltTimelineSummaryView', view); } catch (e) {}
    }
    var initialSummaryView = 'list';
    try {
        var savedSummary = localStorage.getItem('ltTimelineSummaryView');
        if (savedSummary === 'calendar' || savedSummary === 'list') initialSummaryView = savedSummary;
    } catch (e) {}
    setSummaryTimelineView(initialSummaryView);
    document.querySelectorAll('.lt-timeline-summary-view-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            setSummaryTimelineView(this.dataset.view || 'list');
        });
    });

    function openTimelineModalFromEvent(el) {
        var type = el.getAttribute('data-lt-type') || '';
        var id = parseInt(el.getAttribute('data-lt-id') || '0', 10) || 0;
        if (!type || !id) return;

        var modal = document.getElementById('lt-timeline-modal');
        var closeBtn = document.getElementById('lt-timeline-modal-close');
        var meta = document.getElementById('lt-timeline-modal-meta');
        var formTimeline = document.getElementById('lt-timeline-modal-form-timeline');
        var formTransport = document.getElementById('lt-timeline-modal-form-transport');
        if (!modal || !formTimeline || !formTransport) return;

        function show() {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        }
        function hide() {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
        closeBtn?.addEventListener('click', hide, { once: true });
        modal.addEventListener('click', function(e){ if (e.target === modal) hide(); }, { once: true });
        modal.querySelectorAll('[data-modal-cancel]').forEach(function(b){ b.addEventListener('click', hide, { once: true }); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hide(); }, { once: true });

        var date = el.getAttribute('data-lt-date') || el.getAttribute('data-lt-scheduled-date') || '';
        var time = el.getAttribute('data-lt-time') || '';
        var dur = parseInt(el.getAttribute('data-lt-duration') || '30', 10) || 30;
        var label = el.getAttribute('data-lt-label') || '';
        var memo = el.getAttribute('data-lt-memo') || '';

        if (meta) meta.textContent = (date ? (date + ' ') : '') + (time ? time : '') + (dur ? ('（' + dur + '分）') : '');

        formTimeline.classList.add('hidden');
        formTransport.classList.add('hidden');

        if (type === 'timeline') {
            formTimeline.classList.remove('hidden');
            (document.getElementById('lt-timeline-modal-timeline-id')).value = String(id);
            (document.getElementById('lt-timeline-modal-date')).value = date;
            (document.getElementById('lt-timeline-modal-start')).value = time;
            (document.getElementById('lt-timeline-modal-duration')).value = String(dur);
            (document.getElementById('lt-timeline-modal-label')).value = label;
            (document.getElementById('lt-timeline-modal-memo')).value = memo;

            // end_time を開始+dur でプリセット
            var endInput = document.getElementById('lt-timeline-modal-end');
            if (endInput && time && dur) {
                var m = /^(\d{1,2}):(\d{2})$/.exec(time);
                if (m) {
                    var s = parseInt(m[1], 10) * 60 + parseInt(m[2], 10);
                    var e = s + dur;
                    if (e > 0 && e <= 24*60) {
                        var hh = String(Math.floor(e/60)).padStart(2,'0');
                        var mm = String(e%60).padStart(2,'0');
                        endInput.value = hh + ':' + mm;
                    } else {
                        endInput.value = '';
                    }
                }
            }

            // 変更に応じて end_time / duration を扱いやすくする
            var startInput = document.getElementById('lt-timeline-modal-start');
            var durationInput = document.getElementById('lt-timeline-modal-duration');
            if (startInput && durationInput && endInput) {
                var recalcEnd = function() {
                    var t = (startInput.value || '').trim();
                    var d = parseInt((durationInput.value || '').trim(), 10);
                    if (!t || !d || d <= 0) return;
                    var m = /^(\d{1,2}):(\d{2})$/.exec(t);
                    if (!m) return;
                    var s = parseInt(m[1], 10) * 60 + parseInt(m[2], 10);
                    var e = s + d;
                    if (e > 0 && e <= 24*60) {
                        endInput.value = String(Math.floor(e/60)).padStart(2,'0') + ':' + String(e%60).padStart(2,'0');
                    }
                };
                startInput.oninput = recalcEnd;
                durationInput.oninput = recalcEnd;
                endInput.oninput = function() {
                    var t = (startInput.value || '').trim();
                    var e = (endInput.value || '').trim();
                    var m1 = /^(\d{1,2}):(\d{2})$/.exec(t);
                    var m2 = /^(\d{1,2}):(\d{2})$/.exec(e);
                    if (!m1 || !m2) return;
                    var sMin = parseInt(m1[1],10)*60 + parseInt(m1[2],10);
                    var eMin = parseInt(m2[1],10)*60 + parseInt(m2[2],10);
                    var diff = eMin - sMin;
                    if (diff > 0) durationInput.value = String(diff);
                };
            }
        } else if (type === 'transport') {
            formTransport.classList.remove('hidden');
            (document.getElementById('lt-timeline-modal-transport-id')).value = String(id);
            (document.getElementById('lt-timeline-modal-transport-start')).value = time;
            (document.getElementById('lt-timeline-modal-transport-date')).value = date;
            (document.getElementById('lt-timeline-modal-transport-type')).value = el.getAttribute('data-lt-transport-type') || '';
            (document.getElementById('lt-timeline-modal-route-memo')).value = el.getAttribute('data-lt-route-memo') || '';
            (document.getElementById('lt-timeline-modal-departure')).value = el.getAttribute('data-lt-departure') || '';
            (document.getElementById('lt-timeline-modal-arrival')).value = el.getAttribute('data-lt-arrival') || '';
            (document.getElementById('lt-timeline-modal-transport-duration')).value = el.getAttribute('data-lt-duration-min-transport') || '';
            (document.getElementById('lt-timeline-modal-amount')).value = el.getAttribute('data-lt-amount') || '';
            (document.getElementById('lt-timeline-modal-maps-link')).value = el.getAttribute('data-lt-maps-link') || '';

            var tStart = document.getElementById('lt-timeline-modal-transport-start');
            var tEnd = document.getElementById('lt-timeline-modal-transport-end');
            var tDurEdit = document.getElementById('lt-timeline-modal-transport-duration-edit');
            var tDurHidden = document.getElementById('lt-timeline-modal-transport-duration');
            var initDur = parseInt((el.getAttribute('data-lt-duration-min-transport') || '').trim(), 10);
            if (!initDur || initDur <= 0) initDur = 30;
            if (tDurEdit) tDurEdit.value = String(initDur);
            if (tDurHidden) tDurHidden.value = String(initDur);

            var recalcTransportEnd = function() {
                if (!tStart || !tEnd || !tDurEdit) return;
                var t = (tStart.value || '').trim();
                var d = parseInt((tDurEdit.value || '').trim(), 10);
                if (!t || !d || d <= 0) return;
                var m = /^(\d{1,2}):(\d{2})$/.exec(t);
                if (!m) return;
                var s = parseInt(m[1],10)*60 + parseInt(m[2],10);
                var e = s + d;
                if (e > 0 && e <= 24*60) {
                    tEnd.value = String(Math.floor(e/60)).padStart(2,'0') + ':' + String(e%60).padStart(2,'0');
                } else {
                    tEnd.value = '';
                }
                if (tDurHidden) tDurHidden.value = String(d);
            };
            var recalcTransportDur = function() {
                if (!tStart || !tEnd || !tDurEdit) return;
                var s = (tStart.value || '').trim();
                var e = (tEnd.value || '').trim();
                var m1 = /^(\d{1,2}):(\d{2})$/.exec(s);
                var m2 = /^(\d{1,2}):(\d{2})$/.exec(e);
                if (!m1 || !m2) return;
                var sMin = parseInt(m1[1],10)*60 + parseInt(m1[2],10);
                var eMin = parseInt(m2[1],10)*60 + parseInt(m2[2],10);
                var diff = eMin - sMin;
                if (diff > 0) {
                    tDurEdit.value = String(diff);
                    if (tDurHidden) tDurHidden.value = String(diff);
                }
            };
            tStart && (tStart.oninput = recalcTransportEnd);
            tDurEdit && (tDurEdit.oninput = recalcTransportEnd);
            tEnd && (tEnd.oninput = recalcTransportDur);
            recalcTransportEnd();
        } else {
            return;
        }
        show();
    }

    document.addEventListener('click', function(e) {
        var ev = e.target.closest('.lt-timeline-calendar .lt-event, .lt-summary-timeline-calendar .lt-event');
        if (ev) {
            // 削除/編集ボタンやフォーム操作は従来挙動優先
            if (e.target.closest('button') || e.target.closest('form') || e.target.closest('a')) return;
            openTimelineModalFromEvent(ev);
            return;
        }

        var editBtn = e.target.closest('.expense-edit-btn, .hotel-edit-btn, .destination-edit-btn, .transport-edit-btn, .timeline-edit-btn, .transport-timeline-edit-btn');
        if (editBtn) {
            var item = editBtn.closest('.expense-item, .hotel-item, .destination-item, .transport-item, .timeline-row, .lt-event');
            if (!item) return;
            var view = item.querySelector('.expense-view, .hotel-view, .destination-view, .transport-view, .timeline-view, .lt-event-view');
            var form = item.querySelector('.expense-edit-form, .hotel-edit-form, .destination-edit-form, .transport-edit-form, .timeline-edit-form, .transport-timeline-edit-form');
            if (view) view.style.display = 'none';
            if (form) { form.classList.remove('hidden'); }
        }
        var cancelBtn = e.target.closest('.expense-cancel-btn, .hotel-cancel-btn, .destination-cancel-btn, .transport-cancel-btn, .timeline-cancel-btn, .transport-timeline-cancel-btn');
        if (cancelBtn) {
            var form = cancelBtn.closest('.edit-form');
            if (!form) return;
            var item = form.closest('.expense-item, .hotel-item, .destination-item, .transport-item, .timeline-row, .lt-event');
            if (!item) return;
            var view = item.querySelector('.expense-view, .hotel-view, .destination-view, .transport-view, .timeline-view, .lt-event-view');
            if (view) view.style.display = '';
            form.classList.add('hidden');
        }
    });

    var mylistModal = document.getElementById('mylist-register-modal');
    var mylistModalBtn = document.getElementById('mylist-register-btn');
    var mylistModalClose = document.getElementById('mylist-modal-close');
    if (mylistModal && mylistModalBtn) {
        mylistModalBtn.addEventListener('click', function() {
            mylistModal.classList.remove('hidden');
            mylistModal.setAttribute('aria-hidden', 'false');
        });
        function closeMylistModal() {
            mylistModal.classList.add('hidden');
            mylistModal.setAttribute('aria-hidden', 'true');
        }
        mylistModalClose?.addEventListener('click', closeMylistModal);
        mylistModal.addEventListener('click', function(e) {
            if (e.target === mylistModal) closeMylistModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mylistModal && !mylistModal.classList.contains('hidden')) closeMylistModal();
        });
    }

    var applyLinkBtn = document.getElementById('transport-apply-link-btn');
    var linkInput = document.getElementById('transport-maps-link-input');
    var linkStatus = document.getElementById('transport-link-status');
    var hiddenMapsLink = document.getElementById('transport-maps-link');
    if (applyLinkBtn && linkInput && linkStatus && hiddenMapsLink) {
        applyLinkBtn.addEventListener('click', function() {
            var url = (linkInput.value || '').trim();
            if (!url) {
                linkStatus.textContent = 'URLを貼り付けてください';
                linkStatus.className = 'text-sm text-amber-600';
                return;
            }
            linkStatus.textContent = '反映中...';
            linkStatus.className = 'text-sm text-slate-500';
            fetch('/live_trip/api/resolve_maps_link.php?url=' + encodeURIComponent(url))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'ok') {
                        var dep = document.getElementById('transport-departure');
                        var arr = document.getElementById('transport-arrival');
                        var dur = document.getElementById('transport-duration-min');
                        var depDate = document.getElementById('transport-departure-date');
                        var depTime = document.getElementById('transport-scheduled-time');
                        if (dep && data.origin) dep.value = data.origin;
                        if (arr && data.destination) arr.value = data.destination;
                        if (dur && data.duration_min != null) dur.value = data.duration_min;
                        if (depDate && data.departure_date) depDate.value = data.departure_date;
                        if (depTime && data.departure_time) depTime.value = data.departure_time;
                        if (data.resolved_url) hiddenMapsLink.value = data.resolved_url;
                        linkStatus.textContent = '発・着・所要時間を反映しました。費用は手入力してください。';
                        linkStatus.className = 'text-sm text-emerald-600';
                    } else {
                        linkStatus.textContent = data.message || '反映に失敗しました';
                        linkStatus.className = 'text-sm text-red-600';
                    }
                })
                .catch(function() {
                    linkStatus.textContent = '取得に失敗しました';
                    linkStatus.className = 'text-sm text-red-600';
                });
        });
    }

    var googleMapsBtn = document.getElementById('transport-google-maps-btn');
    if (googleMapsBtn) {
        googleMapsBtn.addEventListener('click', function() {
            var dep = document.getElementById('transport-departure');
            var arr = document.getElementById('transport-arrival');
            if (!dep || !arr) return;
            // place_id はブラウザの Google Maps URL では解釈されないため、表示テキスト（住所・施設名）のみ渡す
            var origin = (dep.value || '').trim();
            var dest = (arr.value || '').trim();
            var url;
            if (origin && dest) {
                url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + '&destination=' + encodeURIComponent(dest);
                var depDateEl = document.getElementById('transport-departure-date');
                var depTimeEl = document.getElementById('transport-scheduled-time');
                var depDate = (depDateEl && depDateEl.value) ? depDateEl.value.trim() : '';
                var depTime = (depTimeEl && depTimeEl.value) ? depTimeEl.value.trim() : '';
                if (depDate || depTime) {
                    var ts = getDepartureTimestamp(depDate, depTime);
                    if (ts !== null) url += '&departure_time=' + ts;
                }
                window.open(url, '_blank', 'noopener,noreferrer');
            } else if (dest) {
                url = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(dest);
                window.open(url, '_blank', 'noopener,noreferrer');
            } else if (origin) {
                url = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(origin);
                window.open(url, '_blank', 'noopener,noreferrer');
            } else {
                window.open('https://www.google.com/maps', '_blank', 'noopener,noreferrer');
            }
        });
    }

    document.body.addEventListener('click', function(e) {
        var depSet = e.target && e.target.closest ? e.target.closest('.transport-quick-set-dep') : null;
        var arrSet = e.target && e.target.closest ? e.target.closest('.transport-quick-set-arr') : null;
        if (depSet && depSet.dataset.value !== undefined) {
            var depEl = document.getElementById('transport-departure');
            if (depEl) depEl.value = depSet.dataset.value;
            e.preventDefault();
            return;
        }
        if (arrSet && arrSet.dataset.value !== undefined) {
            var arrEl = document.getElementById('transport-arrival');
            if (arrEl) arrEl.value = arrSet.dataset.value;
            e.preventDefault();
            return;
        }
    });
    document.body.addEventListener('click', function(e) {
        var toggle = e.target && e.target.closest ? e.target.closest('#transport-quick-select-toggle') : null;
        if (!toggle) return;
        var body = document.getElementById('transport-quick-select-body');
        if (!body) return;
        e.preventDefault();
        e.stopPropagation();
        var hidden = body.classList.contains('hidden');
        body.classList.toggle('hidden', !hidden);
        var chevron = toggle.querySelector('.transport-quick-select-chevron');
        if (chevron) {
            chevron.classList.toggle('fa-chevron-down', hidden);
            chevron.classList.toggle('fa-chevron-up', !hidden);
        }
    });

    function getDepartureTimestamp(dateStr, timeStr) {
        var date = dateStr || '';
        var time = timeStr || '';
        if (!date && !time) return null;
        var y = '', m = '', d = '', h = 9, min = 0;
        if (date) {
            var parts = date.split('-');
            if (parts.length >= 3) { y = parts[0]; m = parts[1]; d = parts[2]; }
        }
        if (time) {
            var tParts = time.split(':');
            if (tParts.length >= 2) { h = parseInt(tParts[0], 10) || 9; min = parseInt(tParts[1], 10) || 0; }
        }
        if (!y || !m || !d) {
            var today = new Date();
            y = today.getFullYear();
            m = String(today.getMonth() + 1).padStart(2, '0');
            d = String(today.getDate()).padStart(2, '0');
        }
        var dt = new Date(y + '-' + m + '-' + d + 'T' + String(h).padStart(2, '0') + ':' + String(min).padStart(2, '0') + ':00+09:00');
        if (isNaN(dt.getTime())) return null;
        return Math.floor(dt.getTime() / 1000);
    }

    function attachPlacesAutocomplete(inputId, suggestionsId) {
        var input = document.getElementById(inputId);
        var container = document.getElementById(suggestionsId);
        if (!input || !container) return;
        var debounceTimer = null;
        input.addEventListener('input', function() {
            delete input.dataset.placeId;
            var q = (input.value || '').trim();
            clearTimeout(debounceTimer);
            if (q.length < 2) {
                container.classList.add('hidden');
                container.innerHTML = '';
                return;
            }
            debounceTimer = setTimeout(function() {
                fetch('/live_trip/api/places_autocomplete.php?input=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        container.innerHTML = '';
                        if (data.status !== 'ok' || !data.predictions || data.predictions.length === 0) {
                            container.classList.add('hidden');
                            return;
                        }
                        data.predictions.forEach(function(p) {
                            var el = document.createElement('button');
                            el.type = 'button';
                            el.className = 'block w-full text-left px-3 py-2 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-0';
                            el.textContent = p.description;
                            el.dataset.description = p.description;
                            el.dataset.placeId = p.place_id;
                            el.addEventListener('click', function() {
                                input.value = this.dataset.description || '';
                                input.dataset.placeId = this.dataset.placeId || '';
                                container.classList.add('hidden');
                                container.innerHTML = '';
                            });
                            container.appendChild(el);
                        });
                        container.classList.remove('hidden');
                    })
                    .catch(function() { container.classList.add('hidden'); });
            }, 300);
        });
        input.addEventListener('blur', function() {
            setTimeout(function() { container.classList.add('hidden'); }, 150);
        });
        input.addEventListener('focus', function() {
            if (container.children.length > 0) container.classList.remove('hidden');
        });
    }

    function attachPlacesAutocompleteForDestination(nameInputId, addressInputId, placeIdHiddenId, suggestionsId) {
        var nameInput = document.getElementById(nameInputId);
        var addressInput = document.getElementById(addressInputId);
        var placeIdHidden = document.getElementById(placeIdHiddenId);
        var container = document.getElementById(suggestionsId);
        if (!nameInput || !addressInput || !container) return;
        var debounceTimer = null;
        nameInput.addEventListener('input', function() {
            if (placeIdHidden) placeIdHidden.value = '';
            var q = (nameInput.value || '').trim();
            clearTimeout(debounceTimer);
            if (q.length < 2) {
                container.classList.add('hidden');
                container.innerHTML = '';
                return;
            }
            debounceTimer = setTimeout(function() {
                fetch('/live_trip/api/places_autocomplete.php?input=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        container.innerHTML = '';
                        if (data.status !== 'ok' || !data.predictions || data.predictions.length === 0) {
                            container.classList.add('hidden');
                            return;
                        }
                        data.predictions.forEach(function(p) {
                            var el = document.createElement('button');
                            el.type = 'button';
                            el.className = 'block w-full text-left px-3 py-2 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-0';
                            el.textContent = p.description;
                            el.dataset.description = p.description;
                            el.dataset.placeId = p.place_id || '';
                            el.addEventListener('click', function() {
                                var desc = this.dataset.description || '';
                                nameInput.value = desc;
                                addressInput.value = desc;
                                if (placeIdHidden) placeIdHidden.value = this.dataset.placeId || '';
                                container.classList.add('hidden');
                                container.innerHTML = '';
                            });
                            container.appendChild(el);
                        });
                        container.classList.remove('hidden');
                    })
                    .catch(function() { container.classList.add('hidden'); });
            }, 300);
        });
        nameInput.addEventListener('blur', function() {
            setTimeout(function() { container.classList.add('hidden'); }, 150);
        });
        nameInput.addEventListener('focus', function() {
            if (container.children.length > 0) container.classList.remove('hidden');
        });
    }
    attachPlacesAutocomplete('transport-departure', 'transport-departure-suggestions');
    attachPlacesAutocomplete('transport-arrival', 'transport-arrival-suggestions');
    attachPlacesAutocompleteForDestination('destination-name', 'destination-address', 'destination-place-id', 'destination-name-suggestions');
})();
</script>
<?php require_once __DIR__ . '/../../../components/flash_toast.php'; ?>
<script>
document.querySelectorAll('form[method="post"]:not(#checklist-add-form)').forEach(function(f) {
    f.addEventListener('submit', function() {
        f.querySelectorAll('button[type="submit"]').forEach(function(btn) {
            if (!btn.disabled) { btn.disabled = true; btn.dataset.origHtml = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>送信中...'; }
        });
    });
});
</script>
</body>
</html>
