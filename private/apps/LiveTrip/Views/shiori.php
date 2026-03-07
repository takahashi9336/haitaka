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
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .shiori-block { font-size: 15px; }
        .shiori-label { font-size: 11px; color: #64748b; font-weight: 700; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 pb-8">

<header class="sticky top-0 z-10 bg-emerald-600 text-white px-4 py-3 flex items-center justify-between shadow-md">
    <a href="/live_trip/show.php?id=<?= (int)$trip['id'] ?>" class="text-white/90 hover:text-white text-sm"><i class="fa-solid fa-arrow-left mr-1"></i>詳細へ</a>
    <h1 class="font-bold text-lg truncate max-w-[60%]"><?= htmlspecialchars($trip['event_name'] ?? '遠征') ?></h1>
    <span class="w-14"></span>
</header>

<div class="px-4 py-4 space-y-4 max-w-lg mx-auto">
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-1">イベント</p>
        <p class="font-bold"><?= htmlspecialchars($trip['event_date'] ?? '') ?></p>
        <?php if ($eventPlace): ?>
        <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="text-emerald-600 font-medium text-sm mt-1 inline-flex items-center gap-1">
            <?= htmlspecialchars($eventPlace) ?> <i class="fa-solid fa-external-link text-xs"></i>
        </a>
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
            <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="text-emerald-600 text-sm">地図で開く</a>
            <?php endif; ?>
            <?php if ($h['reservation_no']): ?><p class="text-sm mt-1">予約番号: <strong><?= htmlspecialchars($h['reservation_no']) ?></strong></p><?php endif; ?>
            <?php if ($h['check_in']): ?><p class="text-sm">チェックイン: <?= htmlspecialchars($h['check_in']) ?></p><?php endif; ?>
            <?php if ($h['check_out']): ?><p class="text-sm">チェックアウト: <?= htmlspecialchars($h['check_out']) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($transportLegs)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">移動</p>
        <?php foreach ($transportLegs as $t): ?>
        <div class="mb-3 last:mb-0">
            <p class="text-xs text-slate-500"><?= htmlspecialchars(\App\LiveTrip\Model\TransportLegModel::$directions[$t['direction']] ?? $t['direction']) ?></p>
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
        <p class="shiori-label <?= $isFirstGroup ? 'mt-0' : 'mt-3' ?> mb-2"><?= htmlspecialchars($dayLabel) ?></p>
        <?php endif; ?>
        <div class="flex gap-3 py-2 border-b border-slate-100 last:border-0">
            <span class="font-mono font-bold text-emerald-600 w-14 shrink-0"><?= htmlspecialchars($m['time'] !== '99:99' ? $m['time'] : '') ?></span>
            <div>
                <?php if ($m['type'] === 'timeline'): $ti = $m['data']; ?>
                <p class="font-medium"><?= htmlspecialchars($ti['label']) ?></p>
                <?php if ($ti['memo']): ?><p class="text-sm text-slate-500"><?= htmlspecialchars($ti['memo']) ?></p><?php endif; ?>
                <?php else: $tl = $m['data']; $dir = \App\LiveTrip\Model\TransportLegModel::$directions[$tl['direction']] ?? $tl['direction']; ?>
                <p class="font-medium"><?= htmlspecialchars($dir) ?>: <?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?></p>
                <?php if ($tl['departure'] || $tl['arrival']): ?><p class="text-sm text-slate-500"><?= htmlspecialchars($tl['departure']) ?> → <?= htmlspecialchars($tl['arrival']) ?></p><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($checklistItems)): ?>
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <p class="shiori-label mb-3">チェックリスト</p>
        <?php foreach ($checklistItems as $ci): ?>
        <div class="flex items-center gap-2 py-2">
            <i class="fa-<?= $ci['checked'] ? 'solid fa-circle-check text-emerald-500' : 'regular fa-circle' ?> text-slate-300 w-5"></i>
            <span class="<?= $ci['checked'] ? 'line-through text-slate-400' : '' ?>"><?= htmlspecialchars($ci['item_name']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($hotelStays) && empty($transportLegs) && empty($mergedTimeline) && empty($checklistItems)): ?>
    <p class="text-slate-500 text-center py-8">ホテル・移動・タイムライン・チェックリストを登録すると、ここに表示されます。</p>
    <?php endif; ?>
</div>

</body>
</html>
