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

// タイムライン用のカテゴリ判定
function shiori_get_timeline_category(array $item, ?array $ti, ?array $tl): string {
    if (($item['type'] ?? '') === 'transport' && $tl) {
        return 'transport';
    }
    if (($item['type'] ?? '') === 'timeline' && $ti) {
        $label = $ti['label'] ?? '';
        if (strpos($label, '開場') !== false || strpos($label, '開演') !== false) {
            return 'live';
        }
        if (strpos($label, 'チェックイン') !== false || strpos($label, 'ホテル') !== false || strpos($label, '宿') !== false) {
            return 'hotel';
        }
    }
    return 'default';
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
        .shiori-timeline--transport::before { background: #bfdbfe; }
        .shiori-timeline--live::before { background: var(--shiori-theme); }
        .shiori-timeline--hotel::before { background: #fed7aa; }
        .shiori-timeline-node { position: relative; }
        .shiori-timeline-node .shiori-node-icon {
            position: absolute; left: -1.5rem; top: 0.3rem;
            width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: #fff; border: 2px solid; z-index: 1;
        }
        .shiori-day-band {
            margin-left: -2rem;
            margin-right: -0.25rem;
            padding: 0.5rem 0.25rem 0.5rem 2rem;
            position: sticky;
            top: 3.25rem;
            z-index: 5;
        }
        .shiori-day-band-inner {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.25rem 0.75rem;
            display: block;
            width: calc(100% + 0.5rem);
            margin-left: -0.25rem;
            text-align: center;
        }
        .shiori-time-strong { font-size: 0.95rem; font-weight: 800; }
        .shiori-node-current { background-color: #ecfeff; border-color: #06b6d4; box-shadow: 0 0 0 1px rgba(8,145,178,.35); }
        .shiori-node-current .shiori-node-icon { box-shadow: 0 0 0 2px rgba(8,145,178,.25); }
        .shiori-node-current-badge { font-size: 10px; color: #0f766e; background: #ccfbf1; padding: 0.1rem 0.4rem; border-radius: 9999px; margin-left: 0.5rem; }
        .shiori-timeline-view-btn { border: 1px solid #e2e8f0; border-radius: 9999px; padding: 0.25rem 0.6rem; font-size: 11px; font-weight: 800; color: #475569; background: #fff; }
        .shiori-timeline-view-btn.is-active { background: var(--shiori-theme); border-color: var(--shiori-theme); color: #fff; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 pb-8">

<header class="shiori-header sticky top-0 z-10 text-white px-4 py-3 flex items-center justify-between shadow-md">
    <a href="/live_trip/show.php?id=<?= (int)$trip['id'] ?>" class="text-white/90 hover:text-white text-sm"><i class="fa-solid fa-arrow-left mr-1"></i>詳細へ</a>
    <h1 class="font-bold text-lg truncate max-w-[60%]"><?= htmlspecialchars($trip['event_name'] ?? '遠征') ?></h1>
    <span class="w-14"></span>
</header>

<div class="px-4 py-4 space-y-4 max-w-3xl mx-auto">
    <?php if (!empty($mergedTimeline)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2 mb-3">
            <p class="shiori-label">タイムライン</p>
            <div class="flex items-center gap-2">
                <button type="button" class="shiori-timeline-view-btn" data-view="calendar">日表示</button>
                <button type="button" class="shiori-timeline-view-btn" data-view="list">リスト</button>
            </div>
        </div>
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

        <div class="shiori-timeline-calendar hidden space-y-6">
            <?php if (empty($timelineByDay)): ?>
                <p class="text-slate-500 text-sm">タイムラインがありません。</p>
            <?php endif; ?>
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
                <div class="shiori-day">
                    <p class="text-xs font-bold text-slate-500 mb-2"><?= htmlspecialchars($dayLabel) ?></p>
                    <div class="grid grid-cols-[46px_1fr] gap-3">
                        <div class="text-[11px] text-slate-500 font-mono">
                            <?php for ($h = 6; $h <= 23; $h++): ?>
                                <div class="h-[60px] flex items-start justify-end pr-1"><?= sprintf('%02d:00', $h) ?></div>
                            <?php endfor; ?>
                        </div>
                        <div class="relative rounded-lg border border-slate-200 bg-white overflow-hidden" style="height: <?= (int)$laneHeight ?>px;">
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
                                $widthStyle = $cols === 2 ? 'width: calc(50% - 12px);' : 'right: 8px;';

                                $bg = $m['type'] === 'transport' ? 'background:#eff6ff; border-color:#bfdbfe;' : 'background:#ecfdf5; border-color: rgba(16,185,129,.35);';
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
                                <div class="shiori-cal-event absolute rounded-lg border shadow-sm p-2 text-xs text-slate-700 overflow-hidden cursor-pointer"
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
                                     style="top: <?= (int)$top ?>px; left: <?= (int)$left ?>px; <?= $widthStyle ?> height: <?= (int)$height ?>px; <?= $bg ?>">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-mono font-bold text-slate-600 shrink-0"><?= htmlspecialchars($m['time']) ?></span>
                                        <span class="font-bold truncate flex-1 min-w-0"><?= htmlspecialchars($label) ?></span>
                                        <span class="text-slate-400 font-normal shrink-0">(<?= (int)$dur ?>m)</span>
                                    </div>
                                    <?php if ($sub !== ''): ?><div class="text-[11px] text-slate-500 mt-1 truncate"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="shiori-timeline-list">
        <div class="shiori-timeline space-y-0 shiori-timeline--mixed">
        <?php
        $lastDate = '';
        $themeHex = $themePrimaryHex ?? '#10b981';
        $now = new \DateTimeImmutable('now');
        $currentKey = null;
        foreach ($mergedTimeline as $idx => $m) {
            $d = $m['date'] ?? '';
            $t = $m['time'] ?? '';
            if (!$d || !$t || $t === '99:99') {
                continue;
            }
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $d . ' ' . $t);
            if (!$dt) continue;
            $diff = $dt->getTimestamp() - $now->getTimestamp();
            $score = $diff >= 0 ? $diff : abs($diff) + 86400;
            if ($currentKey === null || $score < $currentKey['score']) {
                $currentKey = ['index' => $idx, 'score' => $score];
            }
        }

        $lastDate = '';
        foreach ($mergedTimeline as $index => $m):
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
        <div class="shiori-day-band">
            <span class="shiori-day-band-inner text-slate-700 font-bold text-xs"><?= htmlspecialchars($dayLabel) ?></span>
        </div>
        <?php endif;
            $ti = $m['type'] === 'timeline' ? $m['data'] : null;
            $tl = $m['type'] === 'transport' ? $m['data'] : null;
            $category = shiori_get_timeline_category($m, $ti, $tl);
            $iconClass = 'fa-clock';
            $nodeColor = '#64748b';
            if ($category === 'transport' && $tl):
                $tt = $tl['transport_type'] ?? '';
                if (preg_match('/新幹線|電車|在来線|列車/', $tt)) { $iconClass = 'fa-train'; $nodeColor = '#3b82f6'; }
                elseif (strpos($tt, '車') !== false) { $iconClass = 'fa-car'; $nodeColor = '#3b82f6'; }
                else { $iconClass = 'fa-bus'; $nodeColor = '#3b82f6'; }
            elseif ($category === 'live' && $ti):
                $label = $ti['label'] ?? '';
                if (strpos($label, '開場') !== false) { $iconClass = 'fa-door-open'; }
                elseif (strpos($label, '開演') !== false) { $iconClass = 'fa-star'; }
                $nodeColor = $themeHex;
            elseif ($category === 'hotel'):
                $iconClass = 'fa-bed';
                $nodeColor = '#fb923c';
            endif;
        ?>
        <?php $isCurrent = ($currentKey && $currentKey['index'] === $index); ?>
        <div class="shiori-timeline-node flex gap-3 py-2.5 border-b border-slate-200 last:border-0 shiori-timeline-node--<?= htmlspecialchars($category) ?><?= $isCurrent ? ' shiori-node-current' : '' ?>" style="--node-color: <?= htmlspecialchars($nodeColor) ?>">
            <span class="font-mono shiori-time shiori-time-strong w-12 shrink-0"><?= htmlspecialchars($m['time'] !== '99:99' ? $m['time'] : '') ?></span>
            <div class="min-w-0 flex-1 pl-1">
                <span class="shiori-node-icon" style="color: <?= htmlspecialchars($nodeColor) ?>; border-color: <?= htmlspecialchars($nodeColor) ?>"><i class="fa-solid <?= htmlspecialchars($iconClass) ?> text-xs"></i></span>
                <?php if ($m['type'] === 'timeline'): ?>
                <p class="font-medium text-slate-800">
                    <?= htmlspecialchars($ti['label']) ?>
                    <?php if ($isCurrent): ?><span class="shiori-node-current-badge">今ここ</span><?php endif; ?>
                </p>
                <?php if ($ti['memo']): ?><p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($ti['memo']) ?></p><?php endif; ?>
                <?php else: ?>
                <p class="font-medium text-slate-800"><?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?></p>
                <?php
                $routeMemo = trim($tl['route_memo'] ?? '');
                $hasRouteInMemo = $routeMemo !== '' && (strpos($routeMemo, '→') !== false || strpos($routeMemo, '駅') !== false);
                if (($tl['departure'] || $tl['arrival']) && !$hasRouteInMemo):
                ?>
                <div class="mt-1 border border-slate-200 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    <div class="font-semibold break-words"><?= htmlspecialchars($tl['departure']) ?></div>
                    <div class="flex items-center justify-center my-1 text-slate-400 text-xs">
                        <span>↓</span>
                    </div>
                    <div class="font-semibold break-words"><?= htmlspecialchars($tl['arrival']) ?></div>
                    <?php if (!empty($tl['duration_min'])): ?>
                    <div class="text-[11px] text-slate-400 mt-1 text-right"><?= (int)$tl['duration_min'] ?>分</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-2">イベント</p>
        <?php foreach ($trip['events'] ?? [] as $ev): 
            $evDate = $ev['event_date'] ?? '';
            $evPlace = $ev['event_place'] ?? $ev['hn_event_place'] ?? '';
            $dc = $eventDoorCurtain[$evDate] ?? [];
            $evMapsUrl = $evPlace ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($evPlace) : '#';
        ?>
        <div class="mb-4 last:mb-0 pb-4 last:pb-0 border-b border-slate-100 last:border-0">
            <p class="font-bold flex items-center justify-between gap-2">
                <span><?= htmlspecialchars($ev['event_name'] ?? '') ?> <span class="text-slate-500 font-normal text-sm"><?= htmlspecialchars($evDate) ?></span></span>
                <?php if ($evPlace && $evMapsUrl !== '#'): ?>
                <span class="flex items-center gap-1 text-xs">
                    <a href="<?= htmlspecialchars($evMapsUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                        <i class="fa-solid fa-map-location-dot text-xs"></i>
                    </a>
                    <button type="button" class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"
                            onclick="shioriCopyText('<?= htmlspecialchars($evPlace, ENT_QUOTES) ?>')">
                        <i class="fa-solid fa-copy text-xs"></i>
                    </button>
                </span>
                <?php endif; ?>
            </p>
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
            <div class="mt-1 flex items-center gap-2 text-xs">
                <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                    <i class="fa-solid fa-map-location-dot text-xs"></i>
                </a>
                <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"
                        onclick="shioriCopyText('<?= htmlspecialchars($h['hotel_name'] . ' ' . ($h['address'] ?? ''), ENT_QUOTES) ?>')">
                    <i class="fa-solid fa-copy text-xs"></i>
                </button>
            </div>
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
            <p class="font-bold flex items-center justify-between gap-2">
                <span><?= htmlspecialchars($d['name']) ?> <span class="text-slate-500 font-normal text-sm"><?= htmlspecialchars($dTypeLabel) ?></span></span>
                <?php if ($dMapsUrl !== '#'): ?>
                <span class="flex items-center gap-1 text-xs">
                    <a href="<?= htmlspecialchars($dMapsUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                        <i class="fa-solid fa-map-location-dot text-xs"></i>
                    </a>
                    <button type="button" class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"
                            onclick="shioriCopyText('<?= htmlspecialchars(($d['address'] ?? $d['name']), ENT_QUOTES) ?>')">
                        <i class="fa-solid fa-copy text-xs"></i>
                    </button>
                </span>
                <?php endif; ?>
            </p>
            <?php if ($dMapsUrl !== '#'): ?>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <a href="<?= htmlspecialchars($dMapsUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200" title="地図で開く">
                    <i class="fa-solid fa-map-location-dot text-xs"></i>
                </a>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode($d['address'] ?? $d['name']) ?>&travelmode=transit" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200" title="電車で案内">
                    <i class="fa-solid fa-train text-xs"></i>
                </a>
                <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"
                        onclick="shioriCopyText('<?= htmlspecialchars(($d['address'] ?? $d['name']), ENT_QUOTES) ?>')" title="住所をコピー">
                    <i class="fa-solid fa-copy text-xs"></i>
                </button>
            </div>
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
            <div class="mt-1 border border-slate-200 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                <div class="font-semibold break-words"><?= htmlspecialchars($t['departure']) ?></div>
                <div class="flex items-center justify-center my-1 text-slate-400 text-xs">
                    <span>↓</span>
                </div>
                <div class="font-semibold break-words"><?= htmlspecialchars($t['arrival']) ?></div>
                <?php if (!empty($t['duration_min'])): ?>
                <div class="text-[11px] text-slate-400 mt-1 text-right"><?= (int)$t['duration_min'] ?>分</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
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

<div id="shiori-timeline-modal" class="lt-modal-overlay hidden" aria-hidden="true">
    <div class="lt-modal m-4" role="dialog" aria-labelledby="shiori-timeline-modal-title">
        <div class="p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 id="shiori-timeline-modal-title" class="font-bold text-slate-700">予定</h3>
                <button type="button" id="shiori-timeline-modal-close" class="text-slate-400 hover:text-slate-600 p-1" aria-label="閉じる"><i class="fa-solid fa-times"></i></button>
            </div>
            <p id="shiori-timeline-modal-meta" class="text-xs text-slate-500 mb-3"></p>

            <form method="post" action="/live_trip/timeline_update.php" id="shiori-timeline-modal-form-timeline" class="space-y-3 hidden">
                <input type="hidden" name="id" id="shiori-modal-timeline-id" value="">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="timeline">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">日付</label>
                        <input type="date" name="scheduled_date" id="shiori-modal-date" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">開始</label>
                        <input type="time" name="scheduled_time" id="shiori-modal-start" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">終了</label>
                        <input type="time" name="end_time" id="shiori-modal-end" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" title="未指定なら30分扱い">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">所要(分)</label>
                        <input type="number" name="duration_min" id="shiori-modal-duration" min="1" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="30">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">項目</label>
                    <input type="text" name="label" id="shiori-modal-label" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">メモ</label>
                    <input type="text" name="memo" id="shiori-modal-memo" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50" data-modal-cancel>閉じる</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background: var(--shiori-theme);">保存</button>
                </div>
            </form>

            <form method="post" action="/live_trip/transport_update.php" id="shiori-timeline-modal-form-transport" class="space-y-3 hidden">
                <input type="hidden" name="id" id="shiori-modal-transport-id" value="">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="timeline">
                <input type="hidden" name="departure_date" id="shiori-modal-transport-date" value="">
                <input type="hidden" name="transport_type" id="shiori-modal-transport-type" value="">
                <input type="hidden" name="route_memo" id="shiori-modal-route-memo" value="">
                <input type="hidden" name="departure" id="shiori-modal-departure" value="">
                <input type="hidden" name="arrival" id="shiori-modal-arrival" value="">
                <input type="hidden" name="duration_min" id="shiori-modal-transport-duration" value="">
                <input type="hidden" name="amount" id="shiori-modal-amount" value="">
                <input type="hidden" name="maps_link" id="shiori-modal-maps-link" value="">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">出発</label>
                        <input type="time" name="scheduled_time" id="shiori-modal-transport-start" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">到着</label>
                        <input type="time" id="shiori-modal-transport-end" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" title="所要(分)から自動計算。直接入力も可">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">所要(分)</label>
                        <input type="number" id="shiori-modal-transport-duration-edit" min="1" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="30">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50" data-modal-cancel>閉じる</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background: var(--shiori-theme);">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var tripId = <?= (int)$trip['id'] ?>;
    function setTimelineView(view) {
        var cal = document.querySelector('.shiori-timeline-calendar');
        var list = document.querySelector('.shiori-timeline-list');
        if (cal) cal.classList.toggle('hidden', view !== 'calendar');
        if (list) list.classList.toggle('hidden', view === 'calendar');
        document.querySelectorAll('.shiori-timeline-view-btn').forEach(function(btn){
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
    document.querySelectorAll('.shiori-timeline-view-btn').forEach(function(btn){
        btn.addEventListener('click', function(){ setTimelineView(this.dataset.view || 'calendar'); });
    });

    function openShioriModalFromEvent(el) {
        var type = el.getAttribute('data-lt-type') || '';
        var id = parseInt(el.getAttribute('data-lt-id') || '0', 10) || 0;
        if (!type || !id) return;

        var modal = document.getElementById('shiori-timeline-modal');
        var closeBtn = document.getElementById('shiori-timeline-modal-close');
        var meta = document.getElementById('shiori-timeline-modal-meta');
        var formTimeline = document.getElementById('shiori-timeline-modal-form-timeline');
        var formTransport = document.getElementById('shiori-timeline-modal-form-transport');
        if (!modal || !formTimeline || !formTransport) return;

        function show() { modal.classList.remove('hidden'); modal.setAttribute('aria-hidden','false'); }
        function hide() { modal.classList.add('hidden'); modal.setAttribute('aria-hidden','true'); }
        closeBtn?.addEventListener('click', hide, { once:true });
        modal.addEventListener('click', function(e){ if (e.target === modal) hide(); }, { once:true });
        modal.querySelectorAll('[data-modal-cancel]').forEach(function(b){ b.addEventListener('click', hide, { once:true }); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hide(); }, { once:true });

        var date = el.getAttribute('data-lt-date') || '';
        var time = el.getAttribute('data-lt-time') || '';
        var dur = parseInt(el.getAttribute('data-lt-duration') || '30', 10) || 30;
        var label = el.getAttribute('data-lt-label') || '';
        var memoText = el.getAttribute('data-lt-memo') || '';
        if (meta) meta.textContent = (date ? (date + ' ') : '') + (time ? time : '') + (dur ? ('（' + dur + '分）') : '');

        formTimeline.classList.add('hidden');
        formTransport.classList.add('hidden');

        if (type === 'timeline') {
            formTimeline.classList.remove('hidden');
            document.getElementById('shiori-modal-timeline-id').value = String(id);
            document.getElementById('shiori-modal-date').value = date;
            document.getElementById('shiori-modal-start').value = time;
            document.getElementById('shiori-modal-duration').value = String(dur);
            document.getElementById('shiori-modal-label').value = label;
            document.getElementById('shiori-modal-memo').value = memoText;
            var endInput = document.getElementById('shiori-modal-end');
            if (endInput && time && dur) {
                var m = /^(\d{1,2}):(\d{2})$/.exec(time);
                if (m) {
                    var s = parseInt(m[1],10)*60 + parseInt(m[2],10);
                    var e = s + dur;
                    if (e > 0 && e <= 24*60) {
                        endInput.value = String(Math.floor(e/60)).padStart(2,'0') + ':' + String(e%60).padStart(2,'0');
                    } else endInput.value = '';
                }
            }

            var startInput = document.getElementById('shiori-modal-start');
            var durationInput = document.getElementById('shiori-modal-duration');
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
            document.getElementById('shiori-modal-transport-id').value = String(id);
            document.getElementById('shiori-modal-transport-start').value = time;
            document.getElementById('shiori-modal-transport-date').value = date;
            document.getElementById('shiori-modal-transport-type').value = el.getAttribute('data-lt-transport-type') || '';
            document.getElementById('shiori-modal-route-memo').value = el.getAttribute('data-lt-route-memo') || '';
            document.getElementById('shiori-modal-departure').value = el.getAttribute('data-lt-departure') || '';
            document.getElementById('shiori-modal-arrival').value = el.getAttribute('data-lt-arrival') || '';
            document.getElementById('shiori-modal-transport-duration').value = el.getAttribute('data-lt-duration-min-transport') || '';
            document.getElementById('shiori-modal-amount').value = el.getAttribute('data-lt-amount') || '';
            document.getElementById('shiori-modal-maps-link').value = el.getAttribute('data-lt-maps-link') || '';

            var tStart = document.getElementById('shiori-modal-transport-start');
            var tEnd = document.getElementById('shiori-modal-transport-end');
            var tDurEdit = document.getElementById('shiori-modal-transport-duration-edit');
            var tDurHidden = document.getElementById('shiori-modal-transport-duration');
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

    document.addEventListener('click', function(e){
        var ev = e.target.closest('.shiori-timeline-calendar .shiori-cal-event');
        if (!ev) return;
        if (e.target.closest('button') || e.target.closest('form') || e.target.closest('a')) return;
        openShioriModalFromEvent(ev);
    });

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

function shioriCopyText(text) {
    if (!navigator.clipboard || !text) return;
    navigator.clipboard.writeText(text).catch(function() {});
}
</script>
</body>
</html>
