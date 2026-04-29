<?php
$appKey = 'live_trip';
require_once __DIR__ . '/../../../components/theme_from_session.php';
\Core\Database::connect();
$eventPlace = $trip['event_place'] ?? $trip['hn_event_place'] ?? '';
$eventPlaceAddress = '';
foreach (($trip['events'] ?? []) as $ev) {
    $addr = trim((string)($ev['hn_event_place_address'] ?? ''));
    if ($addr !== '') {
        $eventPlaceAddress = $addr;
        break;
    }
}
$eventPlaceForArea = $eventPlaceAddress !== '' ? $eventPlaceAddress : (string)$eventPlace;
$eventPlaceForMaps = $eventPlaceAddress !== '' ? $eventPlaceAddress : (string)$eventPlace;
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
$eventPlaceForMaps = $eventPlaceForMaps ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($eventPlaceForMaps) : '#';
$firstEv = $trip['events'][0] ?? null;
$destinationModel = new \App\LiveTrip\Model\DestinationModel();

// ヒーローボックス表示用（年月・日程・残日数）
$heroStartDate = '';
$heroEndDate = '';
if (!empty($eventDates)) {
    $heroStartDate = (string)($eventDates[0] ?? '');
    $heroEndDate = (string)($eventDates[count($eventDates) - 1] ?? '');
}
if ($heroStartDate === '' && !empty($trip['event_date'])) {
    if (preg_match('/\d{4}-\d{2}-\d{2}/', (string)$trip['event_date'], $m)) {
        $heroStartDate = (string)($m[0] ?? '');
    }
    if (preg_match_all('/\d{4}-\d{2}-\d{2}/', (string)$trip['event_date'], $mm) && !empty($mm[0])) {
        $heroEndDate = (string)($mm[0][count($mm[0]) - 1] ?? $heroStartDate);
    }
}
if ($heroEndDate === '') $heroEndDate = $heroStartDate;

$heroMonthLabel = '';
$heroDatePillText = '';
$heroRelativeText = '';
$heroRelativeDays = null;
try {
    if ($heroStartDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $heroStartDate)) {
        $dtStart = new \DateTimeImmutable($heroStartDate, new \DateTimeZone('Asia/Tokyo'));
        $heroMonthLabel = $dtStart->format('Y年n月');
        $dateText = $heroStartDate;
        $nightsDaysText = '';
        if ($heroEndDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $heroEndDate)) {
            $dtEnd = new \DateTimeImmutable($heroEndDate, new \DateTimeZone('Asia/Tokyo'));
            if ($dtEnd >= $dtStart) {
                $diffDays = (int)$dtStart->diff($dtEnd)->days;
                $days = $diffDays + 1;
                $nights = max(0, $days - 1);
                $endDisp = $dtEnd->format('m-d');
                $dateText .= ' 〜 ' . $endDisp;
                if ($days >= 2) {
                    $nightsDaysText = '（' . $nights . '泊' . $days . '日）';
                }
            }
        }
        $heroDatePillText = $dateText . $nightsDaysText;

        $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Tokyo'));
        $d = (int)$today->diff($dtStart)->days;
        if ($dtStart >= $today) {
            $heroRelativeDays = $d;
            $heroRelativeText = $d === 0 ? '今日' : ($d === 1 ? '明日' : ('あと ' . $d . '日'));
        } else {
            $heroRelativeDays = $d;
            $heroRelativeText = $d === 1 ? '1日前' : ($d . '日前');
        }
    }
} catch (\Throwable $e) {
    // 表示用なので握りつぶし
}

// Google Maps JavaScript API（キーはリファラ制限運用を前提）
$mapsJsApiKey = (string)($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');

// 「次の予定」（モバイルダッシュボード用）
$nextItem = null;
$nextNavLabel = '';
$nextNavDestination = '';
try {
    $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Tokyo'));
    $best = null;
    foreach ($mergedTimeline ?? [] as $m) {
        $d = (string)($m['date'] ?? '');
        $t = (string)($m['time'] ?? '');
        if ($d === '') continue;
        // 時刻未設定の予定も「次の予定」候補に含める（表示時刻は空のまま）
        $tForCalc = ($t !== '' && $t !== '99:99') ? $t : '12:00';
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $d . ' ' . $tForCalc, new \DateTimeZone('Asia/Tokyo'));
        if (!$dt) continue;
        $diff = $dt->getTimestamp() - $now->getTimestamp();
        // 未来優先。過去は「次の日」とみなす（当日利用向け）
        $score = $diff >= 0 ? $diff : (abs($diff) + 86400);
        if ($best === null || $score < $best['score']) {
            $best = ['score' => $score, 'm' => $m, 'dt' => $dt];
        }
    }
    if ($best) {
        $m = $best['m'];
        $nextItem = $m;
        if (($m['type'] ?? '') === 'timeline') {
            $ti = $m['data'] ?? [];
            $nextNavLabel = trim((string)($ti['location_label'] ?? '')) ?: trim((string)($ti['label'] ?? ''));
            $dest = trim((string)($ti['location_address'] ?? '')) ?: $nextNavLabel;
            $placeId = trim((string)($ti['place_id'] ?? ''));
            $lat = trim((string)($ti['latitude'] ?? ''));
            $lng = trim((string)($ti['longitude'] ?? ''));
            if ($placeId !== '') {
                $nextNavDestination = 'https://www.google.com/maps/dir/?api=1&destination_place_id=' . rawurlencode($placeId) . '&travelmode=transit';
            } elseif ($lat !== '' && $lng !== '') {
                $nextNavDestination = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($lat . ',' . $lng) . '&travelmode=transit';
            } elseif ($dest !== '') {
                $nextNavDestination = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($dest) . '&travelmode=transit';
            }
        }
        if ($nextNavDestination === '' && $eventPlace !== '') {
            $nextNavLabel = $nextNavLabel ?: '会場';
            $nextNavDestination = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($eventPlace) . '&travelmode=transit';
        }
    }
} catch (\Throwable $e) {
    // 表示用なので握りつぶし
}

// 共通Mapへ渡すデータ
$venue = null;
if ($firstEv) {
    $lat = trim((string)($firstEv['venue_latitude'] ?? ''));
    $lng = trim((string)($firstEv['venue_longitude'] ?? ''));
    if ($lat !== '' && $lng !== '') {
        $venue = [
            'id' => 'venue',
            'name' => (string)($firstEv['event_place'] ?? $firstEv['hn_event_place'] ?? $eventPlace ?? '会場'),
            'lat' => (float)$lat,
            'lng' => (float)$lng,
        ];
    }
}
// 会場座標が無い場合は、会場名から最低限の補完（当日利用のUX優先）
if ($venue === null && $eventPlace !== '' && $mapsJsApiKey !== '') {
    try {
        $geo = (new \App\LiveTrip\Service\MapsGeocodeService())->geocode($eventPlace);
        if ($geo && !empty($geo['latitude']) && !empty($geo['longitude'])) {
            $venue = [
                'id' => 'venue',
                'name' => (string)$eventPlace,
                'lat' => (float)$geo['latitude'],
                'lng' => (float)$geo['longitude'],
            ];
        }
    } catch (\Throwable $e) { /* noop */ }
}
$hotelsForMap = [];
foreach ($hotelStays ?? [] as $h) {
    $lat = trim((string)($h['latitude'] ?? ''));
    $lng = trim((string)($h['longitude'] ?? ''));
    if ($lat === '' || $lng === '') continue;
    $hotelsForMap[] = [
        'id' => (int)($h['id'] ?? 0),
        'name' => (string)($h['hotel_name'] ?? ''),
        'lat' => (float)$lat,
        'lng' => (float)$lng,
        'address' => (string)($h['address'] ?? ''),
        'place_id' => (string)($h['place_id'] ?? ''),
    ];
}
$destinationsForMap = [];
foreach ($destinations ?? [] as $d) {
    $lat = trim((string)($d['latitude'] ?? ''));
    $lng = trim((string)($d['longitude'] ?? ''));
    if ($lat === '' || $lng === '') continue;
    $destinationsForMap[] = [
        'id' => (int)($d['id'] ?? 0),
        'name' => (string)($d['name'] ?? ''),
        'lat' => (float)$lat,
        'lng' => (float)$lng,
        'address' => (string)($d['address'] ?? ''),
        'place_id' => (string)($d['place_id'] ?? ''),
        'type' => (string)($d['destination_type'] ?? 'other'),
    ];
}
$timelineForMap = [];
foreach ($mergedTimeline ?? [] as $m) {
    if (($m['type'] ?? '') !== 'timeline') continue;
    $ti = $m['data'] ?? [];
    $lat = trim((string)($ti['latitude'] ?? ''));
    $lng = trim((string)($ti['longitude'] ?? ''));
    if ($lat === '' || $lng === '') continue;
    $timelineForMap[] = [
        'id' => (int)($ti['id'] ?? 0),
        'label' => (string)($ti['label'] ?? ''),
        'scheduled_date' => (string)($ti['scheduled_date'] ?? ($m['date'] ?? '')),
        'scheduled_time' => (string)($ti['scheduled_time'] ?? ($m['time'] ?? '')),
        'lat' => (float)$lat,
        'lng' => (float)$lng,
        'place_id' => (string)($ti['place_id'] ?? ''),
        'location_label' => (string)($ti['location_label'] ?? ''),
        'location_address' => (string)($ti['location_address'] ?? ''),
    ];
}
$transportForMap = [];
foreach ($transportLegs ?? [] as $t) {
    $dep = trim((string)($t['departure'] ?? ''));
    $arr = trim((string)($t['arrival'] ?? ''));
    if ($dep === '' || $arr === '') continue;
    $transportForMap[] = [
        'id' => (int)($t['id'] ?? 0),
        'departure' => $dep,
        'arrival' => $arr,
        'departure_date' => (string)($t['departure_date'] ?? ''),
        'scheduled_time' => (string)($t['scheduled_time'] ?? ''),
        'transport_type' => (string)($t['transport_type'] ?? ''),
    ];
}
$ltMapData = [
    'tripId' => (int)($trip['id'] ?? 0),
    'venue' => $venue,
    'hotels' => $hotelsForMap,
    'destinations' => $destinationsForMap,
    'timeline' => $timelineForMap,
    'transportLegs' => $transportForMap,
    'theme' => (string)($themePrimaryHex ?? '#10b981'),
];

$hasVenueForMap = ($venue !== null);
$hasDestinationsForMap = !empty($destinationsForMap);
$hasTimelineForMap = !empty($timelineForMap);
$hasTransportForMap = !empty($transportForMap);

// #region agent log
try {
    // repo root: private/apps/LiveTrip/Views -> (Views -> LiveTrip -> apps -> private -> root)
    $debugLogPath = dirname(__DIR__, 4) . '/.cursor/debug-572306.log';
    $payload = [
        'sessionId' => '572306',
        'runId' => 'pre-fix',
        'hypothesisId' => 'H1',
        'location' => 'private/apps/LiveTrip/Views/show.php',
        'message' => 'ltMapDataPrepared',
        'data' => [
            'tripId' => (int)($trip['id'] ?? 0),
            'hasMapsJsApiKey' => ($mapsJsApiKey !== ''),
            'venue' => $venue ? ['has' => true, 'lat' => $venue['lat'], 'lng' => $venue['lng']] : ['has' => false],
            'counts' => [
                'hotels' => count($hotelsForMap),
                'destinations' => count($destinationsForMap),
                'timeline' => count($timelineForMap),
                'transportLegs' => count($transportForMap),
            ],
        ],
        'timestamp' => (int) floor(microtime(true) * 1000),
    ];
    @file_put_contents($debugLogPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
} catch (\Throwable $e) { }
// #endregion agent log
$tripTitle = trim((string)($trip['title'] ?? '')) ?: ((string)($trip['event_name'] ?? '遠征'));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tripTitle) ?> - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/live_trip/css/show.css">
    <?php
    // サマリの地図エリアは一旦非表示（API呼び出しも止める）
    $enableSummaryMap = false;
    // 目的地タブの埋め込み地図（会場 or 目的地に座標がある場合のみ）
    $enableDestinationMap = ($mapsJsApiKey !== '') && ($hasVenueForMap || $hasDestinationsForMap);
    ?>
    <?php if (($enableSummaryMap || $enableDestinationMap) && $mapsJsApiKey !== ''): ?>
    <script>
        // Google Maps JS API ready hook
        window.__ltMapsReady = false;
        window.ltMapsBootstrap = function() {
            window.__ltMapsReady = true;
            document.dispatchEvent(new Event('lt-maps-ready'));
        };
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($mapsJsApiKey) ?>&language=ja&region=JP&libraries=geometry&callback=ltMapsBootstrap"></script>
    <?php endif; ?>
    <style>
        :root {
            --brand-primary: #0E8A5F;
            --brand-primary-hover: #0B7350;
            --brand-primary-soft: #E6F4EE;
            --brand-primary-text: #0B5A3F;

            --gray-900: #111827;
            --gray-700: #374151;
            --gray-500: #6B7280;
            --gray-300: #D1D5DB;
            --gray-200: #E5E7EB;
            --gray-100: #F3F4F6;
            --gray-50: #F9FAFB;

            --danger: #DC2626;
            --danger-soft: #FEF2F2;
            --warn-soft: #FFFBEB;
            --warn-text: #92400E;

            /* 既存スタイル互換（ページ内は lt-theme を参照している） */
            --lt-theme: var(--brand-primary);
        }
        .lt-theme-btn { background-color: var(--brand-primary); }
        .lt-theme-btn:hover { background-color: var(--brand-primary-hover); }
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; background: var(--gray-50); color: var(--gray-900); }
        .sidebar { width: 240px; }
        @media (max-width: 768px) { .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; } .sidebar.mobile-open { transform: translateX(0); } }
        .section-card { break-inside: avoid; }

        /* スクロールバーでレイアウトが揺れないように */
        .lt-main-scroll { scrollbar-gutter: stable; }

        /* カード厚み・角丸を統一（モック寄せ） */
        .rounded-xl { border-radius: 14px !important; }
        .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.04) !important; }

        /* ピル（薄塗り） */
        .lt-pill-primary { background: var(--brand-primary-soft); color: var(--brand-primary-text); }
        /* 下線型タブ（モック寄せ） */
        .lt-tab { padding: 10px 14px; font-weight: 600; font-size: 0.875rem; color: var(--gray-500); border-bottom: 0; background: transparent; transition: color 0.15s; white-space: nowrap; }
        .lt-tab-inner { position: relative; display: inline-flex; align-items: center; gap: 6px; padding: 0 0 10px; }
        .lt-tab:hover { color: var(--gray-900); }
        .lt-tab.active { color: var(--gray-900); font-weight: 700; }
        .lt-tab.active .lt-tab-inner::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 2px;
            background: var(--brand-primary);
            border-radius: 0;
        }
        .lt-tab-panel { display: none; }
        .lt-tab-panel.active { display: block; }
        .lt-checklist-row { transition: background 0.1s; }
        .lt-checklist-row:hover .lt-edit-btn { opacity: 1; }
        .lt-edit-btn { opacity: 0.5; }
        .edit-form.hidden { display: none !important; }
        .lt-tab-bar { -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .lt-tab-bar::-webkit-scrollbar { display: none; }
        .lt-checklist-sortable .sortable-ghost { opacity: 0.4; }
        .lt-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; display: flex; align-items: center; justify-content: center; }
        .lt-modal-overlay.hidden { display: none; }
        .lt-modal { background: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: 28rem; width: 100%; max-height: 90vh; overflow-y: auto; }
        .lt-timeline-view-btn.is-active { background: var(--lt-theme); color: #fff; border-color: var(--lt-theme); }

        /* 持ち物チェック（チェックリスト）新UI */
        .lt-pack-row { height: 42px; }
        .lt-pack-row:hover { background: var(--gray-50); }
        .lt-pack-checkbox { width: 18px; height: 18px; border-radius: 6px; border: 1px solid #d1d5db; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; background: #fff; }
        .lt-pack-checkbox.is-checked { background: var(--lt-theme); border-color: var(--lt-theme); color: #fff; }

        /* サマリ：横幅の統一（タイトルカードと同じコンテナ幅） */
        .trip-detail-container { max-width: 1280px; margin: 0 auto; padding: 0 16px; width: 100%; }
        @media (min-width: 640px) { .trip-detail-container { padding: 0 24px; } }

        /* サマリ：KPI 3カードは親幅に追従 */
        .summary-kpi-grid { width: 100%; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        @media (max-width: 1024px) { .summary-kpi-grid { grid-template-columns: 1fr; } }

        /* サマリ：2カラム×縦積み（モック準拠） */
        .summary-grid { width: 100%; display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        .summary-grid > .left-column,
        .summary-grid > .right-column { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        @media (max-width: 1024px) { .summary-grid { grid-template-columns: 1fr; } }

        /* 行程プレビュー：時刻列を桁揃え */
        .lt-summary-timeline-list .font-mono { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="lt-main-scroll flex-1 flex flex-col min-w-0 overflow-auto overflow-x-hidden w-full">
    <header class="min-h-14 bg-white border-b border-slate-200 flex items-center gap-2 px-4 sm:px-6 py-3 shrink-0">
        <a href="/live_trip/" class="text-slate-500 hover:text-slate-700 shrink-0"><i class="fa-solid fa-arrow-left"></i></a>
        <p class="text-sm font-bold text-slate-600 truncate">遠征詳細</p>
    </header>

    <div class="trip-detail-container py-6">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm px-6 sm:px-7 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 font-bold">
                        遠征管理 <span class="text-slate-300 mx-1">›</span> <?= htmlspecialchars($heroMonthLabel !== '' ? $heroMonthLabel : '（年月未設定）') ?>
                    </p>
                    <h1 class="mt-1 text-lg sm:text-xl font-black text-slate-800 break-words">
                        <?= htmlspecialchars($tripTitle) ?>
                    </h1>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if ($heroDatePillText !== ''): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                                <i class="fa-solid fa-calendar-days text-slate-500"></i>
                                <span><?= htmlspecialchars($heroDatePillText) ?></span>
                            </span>
                        <?php endif; ?>

                        <?php if ($eventPlace): ?>
                            <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200">
                                <i class="fa-solid fa-location-dot text-slate-500"></i>
                                <span class="truncate max-w-[18rem] sm:max-w-none"><?= htmlspecialchars($eventPlace) ?></span>
                                <i class="fa-solid fa-external-link text-[10px] text-slate-400"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($heroRelativeText !== ''): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                                <?php if (is_int($heroRelativeDays) && str_starts_with($heroRelativeText, 'あと ')): ?>
                                    あと <span class="font-black" style="color: var(--lt-theme);"><?= (int)$heroRelativeDays ?></span>日
                                <?php else: ?>
                                    <?= htmlspecialchars($heroRelativeText) ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap justify-start md:justify-end">
                    <a href="/live_trip/shiori.php?id=<?= (int)$trip['id'] ?>" class="px-4 py-2 lt-theme-btn text-white rounded-lg text-sm font-bold whitespace-nowrap inline-flex items-center gap-2" target="_blank" rel="noopener">
                        <i class="fa-solid fa-book"></i>しおり
                    </a>
                    <a href="/live_trip/edit.php?id=<?= (int)$trip['id'] ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap">
                        編集
                    </a>
                    <form method="post" action="/live_trip/delete.php" onsubmit="return confirm('この遠征を削除しますか？');" class="inline">
                        <input type="hidden" name="id" value="<?= (int)$trip['id'] ?>">
                        <button type="submit" class="w-10 h-10 inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100" title="削除" aria-label="削除">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="trip-detail-container">
        <div class="lt-tab-bar flex items-center gap-1.5 border-b border-slate-200 px-0 overflow-x-auto shrink-0">
            <button type="button" class="lt-tab shrink-0" data-tab="summary"><span class="lt-tab-inner"><span aria-hidden="true">📋</span><span>サマリ</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="info"><span class="lt-tab-inner"><span aria-hidden="true">👥</span><span>参加情報</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="expense"><span class="lt-tab-inner"><span aria-hidden="true">💴</span><span>費用</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="hotel"><span class="lt-tab-inner"><span aria-hidden="true">🏨</span><span>宿泊</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="destination"><span class="lt-tab-inner"><span aria-hidden="true">📍</span><span>目的地</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="transport"><span class="lt-tab-inner"><span aria-hidden="true">🚇</span><span>移動</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="timeline"><span class="lt-tab-inner"><span aria-hidden="true">⏰</span><span>タイムライン</span></span></button>
            <button type="button" class="lt-tab shrink-0" data-tab="checklist"><span class="lt-tab-inner"><span aria-hidden="true">✅</span><span>チェックリスト</span></span></button>
        </div>
    </div>

    <?php
    /**
     * サマリ用「次の予定」表示データ
     * NOTE: サマリの地図エリアを非表示にしても動くよう、表示用の計算は共通領域で行う。
     */
    $placeLabelForDisplay = function(string $raw): string {
        $s = trim($raw);
        if ($s === '') return '';
        // Google Maps の検索クエリ由来（+連結等）を表示用に整形
        $s = str_replace('+', ' ', $s);
        $s = preg_replace('/〒\s*\d{3}-\d{4}\s*/u', '', $s) ?? $s;
        // 施設名, 住所… の形なら先頭（施設名）優先
        $parts = preg_split('/[、,]/u', $s);
        if (is_array($parts) && !empty($parts[0])) {
            $s = (string)$parts[0];
        }
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        if ($s === '') return '';
        // 長すぎる表示名は少しだけ丸める（省略はCSSで）
        if (mb_strlen($s) > 40) {
            $s = mb_substr($s, 0, 40);
        }
        return $s;
    };

    $nextBadgeText = '';
    if (is_int($heroRelativeDays) && $heroRelativeDays >= 0 && str_starts_with((string)$heroRelativeText, 'あと ')) {
        $nextBadgeText = 'あと ' . $heroRelativeDays . '日';
    }
    $nextTitle = '';
    $nextDateStr = '';
    $nextTimeStr = '';
    $nextDurationMin = null;
    $nextFrom = '';
    $nextTo = '';
    $nextMapUrl = '';
    $nextAmount = null;
    if (!empty($nextItem) && ($nextItem['type'] ?? '') === 'timeline') {
        $ti = $nextItem['data'] ?? [];
        $nextTitle = (string)($ti['label'] ?? '');
        $nextDateStr = (string)($ti['scheduled_date'] ?? ($nextItem['date'] ?? ''));
        $nextTimeStr = (string)($ti['scheduled_time'] ?? ($nextItem['time'] ?? ''));
        $nextDurationMin = isset($ti['duration_min']) ? (int)$ti['duration_min'] : (isset($nextItem['duration_min']) ? (int)$nextItem['duration_min'] : null);
        $nextFrom = trim((string)($ti['location_label'] ?? '')) ?: trim((string)($ti['location_address'] ?? ''));
        $nextTo = '';
        $nextMapUrl = $nextNavDestination ?: ($eventPlaceForMaps !== '#' ? $eventPlaceForMaps : '');
    } elseif (!empty($nextItem) && ($nextItem['type'] ?? '') === 'transport') {
        $tl = $nextItem['data'] ?? [];
        $nextTitle = trim((string)($tl['transport_type'] ?? '') . ' ' . (string)($tl['route_memo'] ?? ''));
        $nextDateStr = (string)($nextItem['date'] ?? ($tl['departure_date'] ?? ''));
        $nextTimeStr = (string)($nextItem['time'] ?? ($tl['scheduled_time'] ?? ''));
        $nextDurationMin = isset($tl['duration_min']) && $tl['duration_min'] !== '' ? (int)$tl['duration_min'] : null;
        $nextFrom = (string)($tl['departure'] ?? '');
        $nextTo = (string)($tl['arrival'] ?? '');
        // 表示は施設名だけ、リンクは従来どおり（maps_link / nextNavDestination）
        $nextMapUrl = (string)($tl['maps_link'] ?? '') ?: $nextNavDestination;
        $nextAmount = !empty($tl['amount']) ? (int)$tl['amount'] : null;
    }
    $nextDow = '';
    try {
        if ($nextDateStr !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextDateStr)) {
            $dti = new \DateTimeImmutable($nextDateStr, new \DateTimeZone('Asia/Tokyo'));
            $dow = ['日','月','火','水','木','金','土'][(int)$dti->format('w')] ?? '';
            $nextDow = $dow ? '（' . $dow . '）' : '';
        }
    } catch (\Throwable $e) { }
    $nextDurationText = '';
    if (is_int($nextDurationMin) && $nextDurationMin > 0) {
        $h = intdiv($nextDurationMin, 60);
        $m = $nextDurationMin % 60;
        $nextDurationText = ($h > 0 ? ($h . 'h') : '') . ($m > 0 ? ($m . 'm') : ($h > 0 ? '0m' : ''));
    }
    $nextFromDisplay = $placeLabelForDisplay((string)$nextFrom);
    $nextToDisplay = $placeLabelForDisplay((string)$nextTo);
    ?>

    <?php if (false): ?>
    <div class="px-4 sm:px-6 pt-4 sm:pt-5 max-w-6xl w-full">
        <div class="grid gap-3 sm:gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <?php
            // 旧：次の予定＋地図（現在は非表示）
            ?>
            <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-xl shadow-sm min-w-0 overflow-hidden" style="line-height: 1.6;">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold text-slate-500 inline-flex items-center gap-2">
                        <span aria-hidden="true">🚄</span><span>次の予定</span>
                    </p>
                    <?php if ($nextBadgeText !== ''): ?>
                        <span class="lt-pill-primary inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold">
                            <?= htmlspecialchars($nextBadgeText) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($nextTitle !== ''): ?>
                    <p class="mt-2 text-lg sm:text-[21px] font-bold text-slate-900 truncate"><?= htmlspecialchars($nextTitle) ?></p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 min-w-0 text-sm text-slate-700">
                        <span class="font-mono font-bold text-slate-700 min-w-0 truncate">
                            <?= htmlspecialchars($nextDateStr) ?><?= htmlspecialchars($nextDow) ?><?= $nextTimeStr !== '' ? ' ' . htmlspecialchars($nextTimeStr) : '' ?>
                        </span>
                        <?php if ($nextDurationText !== ''): ?>
                            <span class="text-slate-500 shrink-0">出発・所要 <?= htmlspecialchars($nextDurationText) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($nextFrom !== '' || $nextTo !== ''): ?>
                        <div class="mt-2 flex items-center gap-2 min-w-0 flex-wrap">
                            <span class="text-sm text-slate-600 min-w-0 truncate">
                                <?= htmlspecialchars($nextFromDisplay !== '' ? $nextFromDisplay : '（出発地未設定）') ?>
                            </span>
                            <?php if ($nextTo !== ''): ?>
                                <span class="text-slate-400 shrink-0">→</span>
                                <span class="text-sm text-slate-600 min-w-0 truncate">
                                    <?= htmlspecialchars($nextToDisplay !== '' ? $nextToDisplay : $nextTo) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mt-2 text-slate-500 text-sm">次の予定がありません。</p>
                <?php endif; ?>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if ($nextNavDestination !== ''): ?>
                        <a href="<?= htmlspecialchars($nextNavDestination) ?>" target="_blank" rel="noopener" class="lt-theme-btn text-white px-3 py-2 rounded-lg text-sm font-bold whitespace-nowrap inline-flex items-center gap-2">
                            <span aria-hidden="true">🧭</span><span>ナビ起動</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($nextMapUrl !== ''): ?>
                        <a href="<?= htmlspecialchars($nextMapUrl) ?>" target="_blank" rel="noopener" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap inline-flex items-center gap-2">
                            <span aria-hidden="true">🗺</span><span>地図で開く</span>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap inline-flex items-center gap-2 js-switch-tab" data-tab="hotel">
                        <span aria-hidden="true">🏨</span><span>宿泊詳細</span>
                    </button>
                    <?php if (is_int($nextAmount) && $nextAmount > 0): ?>
                        <button type="button" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap inline-flex items-center gap-2 js-switch-tab" data-tab="transport">
                            <span aria-hidden="true">💴</span><span><span class="font-black">¥<?= number_format($nextAmount) ?></span></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-0 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-w-0">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between gap-3">
                    <p class="text-xs font-bold text-slate-500">地図</p>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold <?= $hasVenueForMap ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-300 cursor-not-allowed' ?>"
                                data-lt-map-layer="venue"
                                <?= $hasVenueForMap ? '' : 'disabled title="会場の座標が未登録です"' ?>>会場</button>
                        <button type="button"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold <?= $hasDestinationsForMap ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-300 cursor-not-allowed' ?>"
                                data-lt-map-layer="destinations"
                                <?= $hasDestinationsForMap ? '' : 'disabled title="目的地に座標付きのスポットがありません"' ?>>目的地</button>
                        <button type="button"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold <?= $hasTimelineForMap ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-300 cursor-not-allowed' ?>"
                                data-lt-map-layer="timeline"
                                <?= $hasTimelineForMap ? '' : 'disabled title="タイムラインに場所付きの予定がありません"' ?>>予定</button>
                        <button type="button"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-bold <?= $hasTransportForMap ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-300 cursor-not-allowed' ?>"
                                data-lt-map-layer="transport"
                                <?= $hasTransportForMap ? '' : 'disabled title="移動に発/着が入っていません"' ?>>ルート</button>
                    </div>
                </div>
                <div id="ltMap" class="w-full" style="height: 320px;"></div>
                <?php if ($mapsJsApiKey === ''): ?>
                    <div class="p-4 text-sm text-slate-500 border-t border-slate-200">
                        Google Maps APIキーが未設定のため、地図は表示できません。
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="trip-detail-container py-3 sm:py-4 lt-tab-panels min-w-0">
        <div class="lt-tab-panel w-full" data-panel="summary" id="panel-summary">
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
            <?php
            // KPI 3カード（サマリタブのみ表示）
            $kpiTotalExpense = 0;
            foreach ($expenses ?? [] as $ex) { $kpiTotalExpense += (int)($ex['amount'] ?? 0); }
            $kpiTransportTotal = 0;
            foreach ($transportLegs ?? [] as $tl) { $kpiTransportTotal += (int)($tl['amount'] ?? 0); }
            $kpiHotelTotal = 0;
            foreach ($hotelStays ?? [] as $h) { $kpiHotelTotal += (int)($h['price'] ?? 0); }
            $kpiGrandTotal = $kpiTotalExpense + $kpiTransportTotal + $kpiHotelTotal;

            $kpiExpenseByCat = [];
            foreach ($expenses ?? [] as $ex) {
                $cat = (string)($ex['category'] ?? 'other');
                $kpiExpenseByCat[$cat] = ($kpiExpenseByCat[$cat] ?? 0) + (int)($ex['amount'] ?? 0);
            }
            arsort($kpiExpenseByCat);
            $kpiExpenseParts = [];
            if ($kpiTransportTotal > 0) $kpiExpenseParts[] = '交通 ¥' . number_format($kpiTransportTotal);
            if ($kpiHotelTotal > 0) $kpiExpenseParts[] = '宿 ¥' . number_format($kpiHotelTotal);
            foreach ($kpiExpenseByCat as $cat => $amt) {
                if ($amt <= 0) continue;
                $label = \App\LiveTrip\Model\ExpenseModel::$categories[$cat] ?? $cat;
                $kpiExpenseParts[] = $label . ' ¥' . number_format($amt);
                if (count($kpiExpenseParts) >= 4) break;
            }
            $kpiExpenseBreakdown = implode(' ／ ', $kpiExpenseParts);

            $kpiCheckTotal = count($checklistItems ?? []);
            $kpiCheckChecked = (int)array_sum(array_column($checklistItems ?? [], 'checked'));
            $kpiCheckPct = $kpiCheckTotal > 0 ? (int)round(($kpiCheckChecked / $kpiCheckTotal) * 100) : 0;
            if ($kpiCheckPct < 0) $kpiCheckPct = 0;
            if ($kpiCheckPct > 100) $kpiCheckPct = 100;

            $kpiParticipationDays = count($eventDates ?? []);
            if ($kpiParticipationDays <= 0 && $heroStartDate !== '' && $heroEndDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $heroStartDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $heroEndDate)) {
                try {
                    $ds = new \DateTimeImmutable($heroStartDate, new \DateTimeZone('Asia/Tokyo'));
                    $de = new \DateTimeImmutable($heroEndDate, new \DateTimeZone('Asia/Tokyo'));
                    if ($de >= $ds) $kpiParticipationDays = (int)$ds->diff($de)->days + 1;
                } catch (\Throwable $e) { }
            }
            $kpiTransportCount = count($transportLegs ?? []);
            $kpiHotelCount = count($hotelStays ?? []);
            $kpiDestinationCount = count($destinations ?? []);
            ?>

            <div class="summary-kpi-grid">
                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
                    <p class="text-sm text-slate-500 font-bold">費用合計</p>
                    <p class="mt-1 text-xl font-black text-slate-900">¥<?= number_format((int)$kpiGrandTotal) ?></p>
                    <p class="mt-2 text-[13px] text-slate-500 truncate" title="<?= htmlspecialchars($kpiExpenseBreakdown) ?>">
                        <?= htmlspecialchars($kpiExpenseBreakdown !== '' ? $kpiExpenseBreakdown : '内訳は未登録です') ?>
                    </p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
                    <p class="text-sm text-slate-500 font-bold">チェック進捗</p>
                    <p class="mt-1 text-slate-900">
                        <span class="text-xl font-black"><?= (int)$kpiCheckChecked ?></span>
                        <span class="text-lg text-slate-400 font-bold"> / <?= (int)$kpiCheckTotal ?></span>
                    </p>
                    <div class="mt-3 h-2 rounded-full bg-slate-200 overflow-hidden" role="progressbar" aria-valuenow="<?= (int)$kpiCheckPct ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full" style="width: <?= (int)$kpiCheckPct ?>%; background: var(--lt-theme);"></div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
                    <p class="text-sm text-slate-500 font-bold">参加日数 / 移動</p>
                    <p class="mt-1 text-slate-900">
                        <span class="text-xl font-black"><?= (int)$kpiParticipationDays ?>日</span>
                        <span class="text-lg font-black text-slate-900">・<?= (int)$kpiTransportCount ?>件</span>
                    </p>
                    <p class="mt-2 text-[13px] text-slate-500">
                        宿泊 <?= (int)$kpiHotelCount ?>件・目的地 <?= (int)$kpiDestinationCount ?>件
                    </p>
                </div>
            </div>

            <?php
            // サマリ上部：次の予定（左）＋持ち物チェック（右）
            $summaryClTotal = count($checklistItems ?? []);
            $summaryClChecked = (int)array_sum(array_column($checklistItems ?? [], 'checked'));
            $summaryClPct = $summaryClTotal > 0 ? (int)round(($summaryClChecked / $summaryClTotal) * 100) : 0;
            if ($summaryClPct < 0) $summaryClPct = 0;
            if ($summaryClPct > 100) $summaryClPct = 100;
            $summaryMyLists = [];
            try { $summaryMyLists = (new \App\LiveTrip\Model\MyListModel())->getListsForSelect(); } catch (\Throwable $e) { }
            $summarySetName = !empty($summaryMyLists) ? ($summaryMyLists[0]['list_name'] ?? '遠征基本セット') : '遠征基本セット';
            ?>

            <div class="summary-grid">
                <div class="left-column">
                    <!-- 左：次の予定 -->
                    <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-xl shadow-sm min-w-0 overflow-hidden" style="line-height: 1.6;">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-bold text-slate-500 inline-flex items-center gap-2">
                                <span aria-hidden="true">🚄</span><span>次の予定</span>
                            </p>
                            <?php if (!empty($nextBadgeText)): ?>
                                <span class="lt-pill-primary inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold">
                                    <?= htmlspecialchars($nextBadgeText) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($nextTitle)): ?>
                            <p class="mt-2 text-lg sm:text-[21px] font-bold text-slate-900 truncate"><?= htmlspecialchars($nextTitle) ?></p>
                            <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 min-w-0 text-sm text-slate-700">
                                <span class="font-mono font-bold text-slate-700 min-w-0 truncate">
                                    <?= htmlspecialchars((string)$nextDateStr) ?><?= htmlspecialchars((string)$nextDow) ?><?= !empty($nextTimeStr) ? ' ' . htmlspecialchars((string)$nextTimeStr) : '' ?>
                                </span>
                                <?php if (!empty($nextDurationText)): ?>
                                    <span class="text-slate-500 shrink-0">出発・所要 <?= htmlspecialchars($nextDurationText) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($nextFrom) || !empty($nextTo)): ?>
                                <div class="mt-2 flex items-center gap-2 min-w-0 flex-wrap">
                                    <span class="text-sm text-slate-600 min-w-0 truncate"><?= htmlspecialchars($nextFromDisplay !== '' ? $nextFromDisplay : '（出発地未設定）') ?></span>
                                    <?php if (!empty($nextTo)): ?>
                                        <span class="text-slate-400 shrink-0">→</span>
                                        <span class="text-sm text-slate-600 min-w-0 truncate"><?= htmlspecialchars($nextToDisplay !== '' ? $nextToDisplay : (string)$nextTo) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="mt-2 text-slate-500 text-sm">次の予定がありません。</p>
                        <?php endif; ?>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php if (!empty($nextNavDestination)): ?>
                                <a href="<?= htmlspecialchars($nextNavDestination) ?>" target="_blank" rel="noopener" class="lt-theme-btn text-white px-3 py-2 rounded-lg text-sm font-bold whitespace-nowrap inline-flex items-center gap-2">
                                    <span aria-hidden="true">🧭</span><span>ナビ起動</span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($nextMapUrl)): ?>
                                <a href="<?= htmlspecialchars($nextMapUrl) ?>" target="_blank" rel="noopener" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap inline-flex items-center gap-2">
                                    <span aria-hidden="true">🗺</span><span>地図で開く</span>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap inline-flex items-center gap-2 js-switch-tab" data-tab="hotel">
                                <span aria-hidden="true">🏨</span><span>宿泊詳細</span>
                            </button>
                            <?php if (!empty($nextAmount) && (int)$nextAmount > 0): ?>
                                <button type="button" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap inline-flex items-center gap-2 js-switch-tab" data-tab="transport">
                                    <span aria-hidden="true">💴</span><span><span class="font-black">¥<?= number_format((int)$nextAmount) ?></span></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- 左：行程プレビュー（次の予定の下に固定） -->
                    <?php if (!empty($mergedTimeline)): ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 min-w-0">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <p class="text-lg font-bold text-slate-800">行程プレビュー</p>
                            <div class="flex items-center gap-2 flex-wrap justify-end">
                                <button type="button" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold inline-flex items-center gap-2 js-switch-tab" data-tab="timeline">
                                    <i class="fa-solid fa-plus"></i><span>追加</span>
                                </button>
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

                    <div class="lt-summary-timeline-calendar hidden space-y-5 mt-4">
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
                                            $placeIdAttr = '';
                                            $latAttr = '';
                                            $lngAttr = '';
                                            $locLabelAttr = '';
                                            $locAddrAttr = '';
                                            if ($m['type'] === 'timeline') {
                                                $ti = $m['data'] ?? [];
                                                $itemId = (int)($ti['id'] ?? 0);
                                                $label = (string)($ti['label'] ?? '');
                                                $sub = (string)($ti['memo'] ?? '');
                                                $placeIdAttr = (string)($ti['place_id'] ?? '');
                                                $latAttr = (string)($ti['latitude'] ?? '');
                                                $lngAttr = (string)($ti['longitude'] ?? '');
                                                $locLabelAttr = (string)($ti['location_label'] ?? '');
                                                $locAddrAttr = (string)($ti['location_address'] ?? '');
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
                                                 data-lt-place-id="<?= htmlspecialchars($placeIdAttr, ENT_QUOTES) ?>"
                                                 data-lt-lat="<?= htmlspecialchars($latAttr, ENT_QUOTES) ?>"
                                                 data-lt-lng="<?= htmlspecialchars($lngAttr, ENT_QUOTES) ?>"
                                                 data-lt-location-label="<?= htmlspecialchars($locLabelAttr, ENT_QUOTES) ?>"
                                                 data-lt-location-address="<?= htmlspecialchars($locAddrAttr, ENT_QUOTES) ?>"
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
                    $dayIdx = 0;
                    $firstEv = $eventDates[0] ?? '';
                    $lastEv = $eventDates[count($eventDates) - 1] ?? '';
                    foreach ($mergedTimeline as $m):
                        $d = $m['date'] ?? '';
                        if ($d !== $lastDate):
                            $isFirstGroup = ($lastDate === '');
                            $lastDate = $d;
                            $dayIdx++;
                            $dow = '';
                            try {
                                if ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                                    $dd = new \DateTimeImmutable($d, new \DateTimeZone('Asia/Tokyo'));
                                    $dow = ['日','月','火','水','木','金','土'][(int)$dd->format('w')] ?? '';
                                    $dDisp = $dd->format('n/j') . ($dow ? '（' . $dow . '）' : '');
                                } else {
                                    $dDisp = $d;
                                }
                            } catch (\Throwable $e) { $dDisp = $d; }
                            $isEventDay = ($d !== '' && in_array($d, $eventDates ?? [], true));
                            $status = $isEventDay ? '当日' : (($firstEv !== '' && $d < $firstEv) ? '前日' : '終了');
                            $sub = 'day' . $dayIdx . '・' . ($isEventDay ? '公演当日' : ($status === '前日' ? '前日' : '終了'));
                    ?>
                    <div class="<?= $isFirstGroup ? 'mt-0' : 'mt-4' ?> mb-2">
                        <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-slate-100 bg-slate-50">
                            <div class="min-w-0 flex items-center gap-3">
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($dDisp) ?></span>
                                <span class="text-sm text-slate-500 truncate"><?= htmlspecialchars($sub) ?></span>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $status === '当日' ? 'lt-pill-primary' : 'bg-white border border-slate-200 text-slate-600' ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php
                    $time = (string)($m['time'] ?? '');
                    $time = $time !== '99:99' ? $time : '';
                    $durMin = (int)($m['duration_min'] ?? 0);
                    $metaText = $durMin > 0 ? ($durMin . '分') : '';
                    $emoji = '⏰';
                    $chipBg = 'bg-slate-100';
                    $chipText = 'text-slate-700';
                    $title = '';
                    $subText = '';
                    if (($m['type'] ?? '') === 'transport') {
                        $t = $m['data'] ?? [];
                        $emoji = '🚇';
                        $chipBg = 'bg-sky-50';
                        $chipText = 'text-sky-700';
                        $title = trim((string)($t['transport_type'] ?? '') . ' ' . (string)($t['route_memo'] ?? ''));
                        $dep = trim((string)($t['departure'] ?? ''));
                        $arr = trim((string)($t['arrival'] ?? ''));
                        $subText = trim(($dep !== '' || $arr !== '') ? ($dep . ($arr !== '' ? ' → ' . $arr : '')) : '');
                        if (!empty($t['duration_min'])) {
                            $subText = $subText !== '' ? ($subText . ' ／ ' . (int)$t['duration_min'] . '分') : ((int)$t['duration_min'] . '分');
                        }
                        $amt = (int)($t['amount'] ?? 0);
                        if ($amt > 0) $metaText = '¥' . number_format($amt);
                    } else {
                        $t = $m['data'] ?? [];
                        $label = (string)($t['label'] ?? '');
                        if (strpos($label, '開場') !== false || strpos($label, '開演') !== false) {
                            $emoji = '🎤';
                            $chipBg = 'bg-emerald-50';
                            $chipText = 'text-emerald-700';
                        } elseif (strpos($label, 'ホテル') !== false || strpos($label, 'チェックイン') !== false) {
                            $emoji = '🏨';
                            $chipBg = 'bg-rose-50';
                            $chipText = 'text-rose-700';
                        }
                        $title = $label;
                        $subText = trim((string)($t['memo'] ?? ''));
                        if ($subText === '') {
                            $loc = trim((string)($t['location_label'] ?? ''));
                            if ($loc !== '') $subText = $loc;
                        }
                    }
                    ?>
                    <div class="grid grid-cols-[80px_minmax(0,1fr)_100px] gap-3 py-2.5 border-b border-slate-100 last:border-0 hover:bg-slate-50 rounded-lg px-2">
                        <div class="font-mono font-bold text-slate-700 text-base"><?= htmlspecialchars($time) ?></div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-7 h-7 rounded-full <?= $chipBg ?> <?= $chipText ?> inline-flex items-center justify-center shrink-0"><?= htmlspecialchars($emoji) ?></span>
                                <span class="font-bold text-slate-800 truncate"><?= htmlspecialchars($title !== '' ? $title : '（未設定）') ?></span>
                            </div>
                            <?php if ($subText !== ''): ?>
                                <div class="text-sm text-slate-500 mt-0.5 truncate"><?= htmlspecialchars($subText) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-right text-sm text-slate-500 font-semibold"><?= htmlspecialchars($metaText) ?></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                    <?php else: ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 text-slate-500 text-sm">行程がありません。</div>
                    <?php endif; ?>
                </div>

                <div class="right-column">
                    <!-- 右：持ち物チェック（サマリ用コンパクト） -->
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 min-w-0 overflow-hidden">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-slate-800">持ち物チェック</p>
                                <p class="text-xs sm:text-sm text-slate-500 truncate"><?= htmlspecialchars((string)$summarySetName) ?> <?= (int)$summaryClChecked ?>/<?= (int)$summaryClTotal ?></p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap justify-end shrink-0">
                                <a href="/live_trip/my_list.php?redirect=<?= urlencode('/live_trip/show.php?id=' . (int)$trip['id'] . '#checklist') ?>" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap">マイリスト</a>
                                <button type="button" class="lt-theme-btn text-white px-3 py-2 rounded-lg text-sm font-bold whitespace-nowrap js-switch-tab" data-tab="checklist">
                                    <i class="fa-solid fa-plus mr-1"></i>追加
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="h-2 rounded-full bg-slate-200 overflow-hidden flex-1" role="progressbar" aria-valuenow="<?= (int)$summaryClPct ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full" style="width: <?= (int)$summaryClPct ?>%; background: var(--lt-theme);"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-500 w-10 text-right"><?= (int)$summaryClPct ?>%</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <?php foreach (array_slice(($checklistItems ?? []), 0, 8) as $ci): $isChecked = !empty($ci['checked']); ?>
                                <div class="lt-checklist-row lt-pack-row flex items-center gap-3 px-2 border-b border-slate-100 last:border-0" data-id="<?= (int)$ci['id'] ?>">
                                    <button type="button" class="checklist-toggle text-left flex-1 flex items-center gap-3 min-w-0 py-2" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>">
                                        <span class="lt-pack-checkbox<?= $isChecked ? ' is-checked' : '' ?>" aria-hidden="true"><?php if ($isChecked): ?><i class="fa-solid fa-check text-[11px]"></i><?php endif; ?></span>
                                        <span class="checklist-label text-[15px] text-slate-800 truncate"><?= htmlspecialchars($ci['item_name']) ?></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($summaryClTotal > 8): ?>
                                <button type="button" class="mt-2 text-sm font-bold lt-theme-link hover:underline js-switch-tab" data-tab="checklist">すべて見る</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 右：宿泊 -->
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
                        <p class="text-lg font-bold text-slate-800">宿泊</p>
                        <div class="mt-4 divide-y divide-slate-100">
                            <?php foreach ($hotelStays ?? [] as $h):
                                $mapsUrl = (new \App\LiveTrip\Model\HotelStayModel())->getGoogleMapsUrl($h);
                                $addr = trim((string)($h['address'] ?? ''));
                                $venueInfo = trim((string)($h['distance_from_venue'] ?? '')) . ($h['time_from_venue'] ? '・' . trim((string)$h['time_from_venue']) : '');
                                $in = trim((string)($h['check_in'] ?? ''));
                                $price = !empty($h['price']) ? (int)$h['price'] : null;
                            ?>
                            <div class="py-4 first:pt-0 last:pb-0">
                                <div class="flex gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-rose-50 text-rose-700 flex items-center justify-center shrink-0">🏨</div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-800 truncate">
                                            <?php if ($mapsUrl !== '#'): ?>
                                                <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="hover:underline"><?= htmlspecialchars((string)($h['hotel_name'] ?? '')) ?></a>
                                            <?php else: ?>
                                                <?= htmlspecialchars((string)($h['hotel_name'] ?? '')) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-[13px] text-slate-500 truncate">
                                            <?= htmlspecialchars($addr) ?><?= $venueInfo !== '' ? ' ／ 会場まで' . htmlspecialchars($venueInfo) : '' ?>
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <?php if ($in !== ''): ?>
                                                <span class="px-3 py-1 rounded-full border border-slate-200 text-xs font-bold text-slate-600 bg-white">📅 <?= htmlspecialchars($in) ?> IN</span>
                                            <?php endif; ?>
                                            <?php if (is_int($price)): ?>
                                                <span class="px-3 py-1 rounded-full border border-slate-200 text-xs font-bold text-slate-600 bg-white">💴 <span class="font-black">¥<?= number_format($price) ?></span></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($hotelStays ?? [])): ?>
                                <p class="text-sm text-slate-500">宿泊がありません。</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 右：目的地 -->
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg font-bold text-slate-800">目的地</p>
                            <span class="text-sm text-slate-500 font-bold"><?= (int)count($destinations ?? []) ?>件</span>
                        </div>
                        <div class="mt-4 divide-y divide-slate-100">
                            <?php foreach ($destinations ?? [] as $d):
                                $mapsUrl = $destinationModel->getGoogleMapsUrl($d);
                                $type = (string)($d['destination_type'] ?? 'other');
                                $typeIconMap = [
                                    'main' => '🎯',
                                    'collab' => '📍',
                                    'sightseeing' => '🗼',
                                    'other' => '📌',
                                ];
                                $typeChipMap = [
                                    'main' => 'bg-emerald-50 text-emerald-700',
                                    'collab' => 'bg-pink-50 text-pink-700',
                                    'sightseeing' => 'bg-indigo-50 text-indigo-700',
                                    'other' => 'bg-slate-100 text-slate-700',
                                ];
                                $emoji = $typeIconMap[$type] ?? '📌';
                                $chipBg = $typeChipMap[$type] ?? 'bg-slate-100 text-slate-700';
                                $date = trim((string)($d['visit_date'] ?? ''));
                                $time = trim((string)($d['visit_time'] ?? ''));
                                $addr = trim((string)($d['address'] ?? ''));
                                $sub = trim(($date !== '' ? $date : '') . ($time !== '' ? (' ' . $time . '〜') : '') . ($addr !== '' ? ('・' . $addr) : ''));
                            ?>
                            <div class="py-4 first:pt-0 last:pb-0 hover:bg-slate-50 rounded-lg px-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-full <?= $chipBg ?> flex items-center justify-center shrink-0"><?= htmlspecialchars($emoji) ?></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-800 truncate"><?= htmlspecialchars((string)($d['name'] ?? '')) ?> <span class="text-slate-500 font-semibold text-sm">（<?= htmlspecialchars(\App\LiveTrip\Model\DestinationModel::$types[$type] ?? 'その他') ?>）</span></div>
                                        <?php if ($sub !== ''): ?><div class="text-[13px] text-slate-500 truncate"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
                                    </div>
                                    <?php if ($mapsUrl !== '#'): ?>
                                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full border border-slate-200 bg-white hover:bg-slate-50 inline-flex items-center justify-center shrink-0" title="地図で開く" aria-label="地図で開く">🗺</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($destinations ?? [])): ?>
                                <p class="text-sm text-slate-500">目的地がありません。</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php /* サマリの「イベント」欄は最終版では非表示 */ ?>
                <?php /* 宿泊・目的地は上の右カラムへ移動 */ ?>
                <?php /* 移動は行程プレビューに集約 */ ?>
                <?php /* 持ち物チェックはチェックリストタブへ移設 */ ?>
                <?php if (empty($hotelStays) && empty($destinations) && empty($transportLegs) && empty($mergedTimeline) && empty($checklistItems)): ?>
                <p class="text-slate-500 text-sm">宿泊・目的地・移動・タイムライン・チェックリストを登録すると、ここに表示されます。各タブから追加してください。</p>
                <?php endif; ?>
        </div>
        </div>

        <div class="lt-tab-panel w-full" data-panel="info" id="panel-info">
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
                <?php else: ?>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl shadow-sm">
                    <p class="text-sm font-bold text-amber-800">イベントが未紐付です。</p>
                    <p class="text-sm text-amber-700 mt-1">「編集」からイベントを追加して、座席・感想を管理できます。</p>
                    <a href="/live_trip/edit.php?id=<?= (int)$trip['id'] ?>" class="inline-flex items-center gap-2 mt-3 px-3 py-2 text-sm rounded-lg bg-white border border-amber-300 text-amber-900 hover:bg-amber-100">
                        <i class="fa-solid fa-pen-to-square"></i><span>イベントを紐づける</span>
                    </a>
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

        <div class="lt-tab-panel w-full" data-panel="expense" id="panel-expense">
        <div class="space-y-4">
            <?php
            require_once __DIR__ . '/lib/expense_summary.php';
            $expense_summary = build_expense_summary((int)$trip['id']);
            $expense_compare = build_expense_compare((int)$trip['id'], (int)($_SESSION['user']['id'] ?? 0));
            if (!defined('APP_DEBUG')) {
                // 開発中のみ。必要なら本番前に削除する。
                define('APP_DEBUG', false);
            }
            // 期間ラベルは「イベント日（ヒーローの開始/終了）」を優先（移動/宿泊の前後日で膨らむのを防ぐ）
            $trip_period_label = '日数未設定';
            $days_inclusive = 0;
            if (!empty($heroStartDate) && !empty($heroEndDate)
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$heroStartDate)
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$heroEndDate)
            ) {
                try {
                    $start = new \DateTimeImmutable((string)$heroStartDate, new \DateTimeZone('Asia/Tokyo'));
                    $end = new \DateTimeImmutable((string)$heroEndDate, new \DateTimeZone('Asia/Tokyo'));
                    if ($end >= $start) $days_inclusive = (int)$start->diff($end)->days + 1;
                } catch (\Throwable $e) { $days_inclusive = 0; }
            }
            if ($days_inclusive <= 0) {
                $days_inclusive = (int)($expense_summary['days'] ?? 0);
            }
            if ($days_inclusive > 0) {
                $nights = max(0, $days_inclusive - 1);
                $trip_period_label = $nights > 0 ? sprintf('%d泊%d日', $nights, $days_inclusive) : '日帰り';
            }
            ?>

            <section class="expense-hero" aria-labelledby="expense-hero-title">
              <div class="expense-hero__head">
                <div class="expense-hero__lead">
                  <p class="expense-hero__eyebrow">遠征費用 合計</p>
                  <p class="expense-hero__total" id="expense-hero-title">
                    <span class="expense-hero__yen">¥</span><?= number_format((int)($expense_summary['total'] ?? 0)) ?>
                  </p>
                  <ul class="expense-hero__meta">
                    <li>📅 <?= htmlspecialchars($trip_period_label) ?></li>
                    <li>📊 1日あたり <strong>¥<?= number_format((int)($expense_summary['avg_per_day'] ?? 0)) ?></strong></li>
                    <li>🧾 <strong><?= (int)($expense_summary['count'] ?? 0) ?>件</strong>の支出</li>
                  </ul>
                </div>

                <div class="expense-hero__actions">
                  <a class="btn-ghost"
                     href="/live_trip/expense_export.php?trip_id=<?= (int)$trip['id'] ?>"
                     data-action="export-csv">⬇ CSV</a>
                  <button type="button" class="btn-primary" data-action="add-expense">＋ 支出を追加</button>
                </div>
              </div>

              <div class="expense-hero__bar" role="img" aria-label="カテゴリ別支出比率">
                <?php foreach (($expense_summary['categories'] ?? []) as $cat): ?>
                  <?php if ((int)($cat['amount'] ?? 0) <= 0) continue; ?>
                  <span
                    class="expense-hero__bar-seg cat-<?= htmlspecialchars((string)($cat['key'] ?? 'other')) ?>"
                    style="width: <?= round(((float)($cat['ratio'] ?? 0)) * 100, 2) ?>%"
                    title="<?= htmlspecialchars((string)($cat['label'] ?? '')) ?> ¥<?= number_format((int)($cat['amount'] ?? 0)) ?>"></span>
                <?php endforeach; ?>
              </div>

              <ul class="expense-hero__legend">
                <?php foreach (($expense_summary['categories'] ?? []) as $cat): ?>
                  <?php if ((int)($cat['amount'] ?? 0) <= 0) continue; ?>
                  <li>
                    <span class="dot cat-<?= htmlspecialchars((string)($cat['key'] ?? 'other')) ?>"></span>
                    <span class="legend-label"><?= htmlspecialchars((string)($cat['label'] ?? '')) ?></span>
                    <span class="legend-amount">¥<?= number_format((int)($cat['amount'] ?? 0)) ?></span>
                    <span class="legend-ratio"><?= round(((float)($cat['ratio'] ?? 0)) * 100) ?>%</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>

            <section class="expense-cats" aria-label="カテゴリ別の費用サマリ">
              <?php
                // 表示順を固定（チケット → 宿泊 → 交通 → グッズ）
                $cat_order = ['ticket','hotel','transport','goods'];
                $cat_icons = [
                  'ticket'    => '🎫',
                  'hotel'     => '🏨',
                  'transport' => '🚆',
                  'goods'     => '🛍️',
                ];
                $cat_map = [];
                foreach (($expense_summary['categories'] ?? []) as $c) { $cat_map[(string)($c['key'] ?? '')] = $c; }
              ?>
              <?php foreach ($cat_order as $key): ?>
                <?php if (empty($cat_map[$key])) continue; ?>
                <?php $c = $cat_map[$key]; ?>
                <?php if ((int)($c['amount'] ?? 0) <= 0) continue; ?>
                <article class="expense-cat cat-<?= htmlspecialchars($key) ?>">
                  <header class="expense-cat__head">
                    <span class="expense-cat__icon"><?= $cat_icons[$key] ?></span>
                    <span class="expense-cat__label"><?= htmlspecialchars((string)($c['label'] ?? '')) ?></span>
                    <span class="expense-cat__count"><?= (int)($c['count'] ?? 0) ?>件</span>
                  </header>
                  <p class="expense-cat__amount">¥<?= number_format((int)($c['amount'] ?? 0)) ?></p>
                  <div class="expense-cat__bar" aria-hidden="true">
                    <span style="width: <?= round(((float)($c['ratio'] ?? 0)) * 100, 2) ?>%"></span>
                  </div>
                  <p class="expense-cat__ratio">
                    全体の <strong><?= round(((float)($c['ratio'] ?? 0)) * 100) ?>%</strong>
                  </p>
                </article>
              <?php endforeach; ?>
            </section>

            <div class="expense-grid">
              <div class="expense-grid__main">
                <?php
                  $defaultOccurredOn = '';
                  if (!empty($heroStartDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$heroStartDate)) {
                    $defaultOccurredOn = (string)$heroStartDate;
                  } else {
                    $defaultOccurredOn = date('Y-m-d');
                  }
                ?>
                <section class="expense-quickadd" aria-label="支出をクイック追加">
                  <header class="expense-quickadd__head">
                    <h3 class="expense-quickadd__title">＋ 支出を追加</h3>
                    <p class="expense-quickadd__sub">手入力の費用をここから登録できます</p>
                  </header>

                  <form class="expense-quickadd__form"
                        method="post"
                        action="/live_trip/expense_store.php"
                        id="expense-quick-add-form">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="expense">

                    <label class="qa-field qa-field--cat">
                      <span class="qa-field__label">カテゴリ</span>
                      <select name="category" required>
                        <option value="ticket">🎫 チケット</option>
                        <option value="transport" selected>🚆 交通</option>
                        <option value="hotel">🏨 宿泊</option>
                        <option value="goods">🛍️ グッズ</option>
                        <option value="food">🍴 飲食</option>
                        <option value="other">📌 その他</option>
                      </select>
                    </label>

                    <label class="qa-field qa-field--date">
                      <span class="qa-field__label">日付</span>
                      <input type="date"
                             name="occurred_on"
                             value="<?= htmlspecialchars($defaultOccurredOn) ?>">
                    </label>

                    <label class="qa-field qa-field--amount">
                      <span class="qa-field__label">金額</span>
                      <span class="qa-field__prefix">¥</span>
                      <input type="number"
                             name="amount"
                             inputmode="numeric"
                             min="0"
                             step="1"
                             placeholder="0"
                             required>
                    </label>

                    <label class="qa-field qa-field--note">
                      <span class="qa-field__label">メモ</span>
                      <input type="text"
                             name="memo"
                             maxlength="120"
                             placeholder="例：1日目チケット代">
                    </label>

                    <button type="submit" class="qa-submit">追加</button>
                  </form>

                  <p class="expense-quickadd__hint">
                    💡 交通費は <a href="#transport">移動タブ</a>、宿泊費は <a href="#hotel">宿泊タブ</a> から登録すると自動でここに連携されます。
                  </p>
                </section>

                <section class="expense-items" aria-label="費用明細">
                  <header class="expense-items__head">
                    <h3 class="expense-items__title">明細</h3>
                    <p class="expense-items__count"><?= (int)($expense_summary['count'] ?? 0) ?>件</p>
                  </header>

              <?php
                // カテゴリ単位でグルーピング（表示順固定）
                $cat_order_items = ['ticket','hotel','transport','goods','food','other'];
                $cat_labels_items = [
                  'ticket'=>'チケット','hotel'=>'宿泊','transport'=>'交通',
                  'goods'=>'グッズ','food'=>'飲食','other'=>'その他',
                ];
                $cat_icons_items = [
                  'ticket'=>'🎫','hotel'=>'🏨','transport'=>'🚆',
                  'goods'=>'🛍️','food'=>'🍴','other'=>'📌',
                ];
                $grouped = [];
                foreach (($expense_summary['items'] ?? []) as $it) {
                  $k = (string)($it['category'] ?? 'other');
                  $grouped[$k][] = $it;
                }
              ?>

              <?php if (empty($expense_summary['items'] ?? [])): ?>
                <p class="text-slate-500 text-sm">費用タブ、宿泊タブ、または移動タブから追加してください。</p>
              <?php else: ?>
                <?php foreach ($cat_order_items as $key): ?>
                  <?php if (empty($grouped[$key])) continue; ?>
                  <?php
                    $rows = $grouped[$key];
                    $subtotal = array_sum(array_map(fn($r) => (int)($r['amount'] ?? 0), $rows));
                  ?>
                  <div class="expense-group cat-<?= htmlspecialchars($key) ?>">
                    <header class="expense-group__head">
                      <span class="expense-group__dot" aria-hidden="true"></span>
                      <span class="expense-group__icon"><?= $cat_icons_items[$key] ?? '📌' ?></span>
                      <span class="expense-group__label"><?= htmlspecialchars($cat_labels_items[$key] ?? $key) ?></span>
                      <span class="expense-group__count"><?= count($rows) ?>件</span>
                      <span class="expense-group__sub">¥<?= number_format((int)$subtotal) ?></span>
                    </header>

                    <ul class="expense-group__list">
                      <?php foreach ($rows as $it): ?>
                        <?php
                          $source = (string)($it['source'] ?? 'manual');
                          $label = trim((string)($it['label'] ?? ($it['title'] ?? '')));
                          $note = trim((string)($it['note'] ?? ($it['sub'] ?? '')));
                          $date = trim((string)($it['occurred_on'] ?? ($it['date'] ?? '')));
                          $time = trim((string)($it['time'] ?? ''));
                          if ($date !== '' && $note === '' && $source !== 'manual') {
                            $note = $date . ($time !== '' ? (' ' . $time) : '');
                          }
                          $amount = (int)($it['amount'] ?? 0);
                          $editUrl = trim((string)($it['edit_url'] ?? ''));
                          $id = (int)($it['id'] ?? 0);
                        ?>
                        <li class="expense-row expense-item source-<?= htmlspecialchars($source) ?>">
                          <div class="expense-view expense-row__main">
                            <p class="expense-row__label">
                              <?= htmlspecialchars($label !== '' ? $label : ($cat_labels_items[$key] ?? '（未設定）')) ?>
                              <?php if ($source !== 'manual'): ?>
                                <span class="expense-badge badge-auto" title="他タブから自動連携">
                                  <?= $source === 'transport' ? '🚆 移動' : '🏨 宿泊' ?> 連携
                                </span>
                              <?php endif; ?>
                            </p>
                            <?php if ($note !== ''): ?>
                              <p class="expense-row__note"><?= htmlspecialchars($note) ?></p>
                            <?php endif; ?>
                          </div>

                          <p class="expense-row__amount">¥<?= number_format($amount) ?></p>

                          <div class="expense-row__actions">
                            <?php if ($source === 'manual'): ?>
                              <button type="button" class="icon-btn expense-edit-btn" aria-label="編集" title="編集">✏️</button>
                              <form method="post" action="/live_trip/expense_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                <input type="hidden" name="tab" value="expense">
                                <button type="submit" class="icon-btn icon-btn--danger" aria-label="削除" title="削除">🗑</button>
                              </form>
                            <?php else: ?>
                              <?php if ($editUrl !== ''): ?>
                                <a class="icon-btn" href="<?= htmlspecialchars($editUrl) ?>" aria-label="元データを編集" title="元データを編集">↗</a>
                              <?php else: ?>
                                <span class="icon-btn is-disabled" aria-hidden="true">↗</span>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>

                          <?php if ($source === 'manual'): ?>
                            <form method="post" action="/live_trip/expense_update.php" class="expense-edit-form edit-form hidden flex flex-wrap gap-2 items-center p-2 bg-slate-50 rounded mt-2 w-full">
                              <input type="hidden" name="id" value="<?= $id ?>">
                              <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                              <input type="hidden" name="tab" value="expense">
                              <select name="category" class="border border-slate-200 rounded px-2 py-1 text-sm" required>
                                <?php foreach (\App\LiveTrip\Model\ExpenseModel::$categories as $k => $v): ?>
                                  <option value="<?= htmlspecialchars($k) ?>" <?= $key === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                <?php endforeach; ?>
                              </select>
                              <input type="number" name="amount" value="<?= $amount ?>" class="border border-slate-200 rounded px-2 py-1 w-24 text-sm" required>
                              <input type="text" name="memo" value="<?= htmlspecialchars($label) ?>" placeholder="メモ" class="border border-slate-200 rounded px-2 py-1 flex-1 min-w-24 text-sm">
                              <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                              <button type="button" class="expense-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                            </form>
                          <?php endif; ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
                </section>
              </div>

              <aside class="expense-grid__side">
                <section class="expense-donut" aria-label="カテゴリ別の構成比">
                  <header class="expense-donut__head">
                    <h3 class="expense-donut__title">カテゴリ構成比</h3>
                    <p class="expense-donut__sub">支出の内訳をビジュアルで確認</p>
                  </header>

                  <?php
                    $donut_size = 180;
                    $donut_radius = 60;
                    $donut_stroke = 22;
                    $donut_cx = $donut_size / 2;
                    $donut_cy = $donut_size / 2;
                    $donut_circ = 2 * pi() * $donut_radius;

                    $cat_order_donut = ['ticket','hotel','transport','goods','food','other'];
                    $cat_map_donut = [];
                    foreach (($expense_summary['categories'] ?? []) as $c) { $cat_map_donut[(string)($c['key'] ?? '')] = $c; }
                    $segments = [];
                    foreach ($cat_order_donut as $k) {
                      if (!empty($cat_map_donut[$k]) && (int)($cat_map_donut[$k]['amount'] ?? 0) > 0) {
                        $segments[] = $cat_map_donut[$k];
                      }
                    }

                    $offset = 0.0;
                    $cat_color_hex = [
                      'ticket'    => '#8B5CF6',
                      'hotel'     => '#EC4899',
                      'transport' => '#3B82F6',
                      'goods'     => '#F59E0B',
                      'food'      => '#10B981',
                      'other'     => '#9CA3AF',
                    ];
                  ?>

                  <div class="expense-donut__chart-wrap">
                    <svg class="expense-donut__chart"
                         viewBox="0 0 <?= (int)$donut_size ?> <?= (int)$donut_size ?>"
                         width="<?= (int)$donut_size ?>" height="<?= (int)$donut_size ?>"
                         role="img"
                         aria-label="カテゴリ別の支出構成比">
                      <circle cx="<?= (float)$donut_cx ?>" cy="<?= (float)$donut_cy ?>" r="<?= (float)$donut_radius ?>"
                              fill="none" stroke="#F3F4F6" stroke-width="<?= (int)$donut_stroke ?>"/>

                      <?php foreach ($segments as $seg): ?>
                        <?php
                          $ratio = (float)($seg['ratio'] ?? 0);
                          if ($ratio <= 0) continue;
                          $len = $donut_circ * $ratio;
                          $gap = max(0.0, $donut_circ - $len);
                          $key = (string)($seg['key'] ?? 'other');
                          $color = $cat_color_hex[$key] ?? '#9CA3AF';
                        ?>
                        <circle
                          cx="<?= (float)$donut_cx ?>" cy="<?= (float)$donut_cy ?>" r="<?= (float)$donut_radius ?>"
                          fill="none"
                          stroke="<?= htmlspecialchars($color) ?>"
                          stroke-width="<?= (int)$donut_stroke ?>"
                          stroke-dasharray="<?= $len ?> <?= $gap ?>"
                          stroke-dashoffset="<?= -$offset ?>"
                          transform="rotate(-90 <?= (float)$donut_cx ?> <?= (float)$donut_cy ?>)"
                          stroke-linecap="butt">
                          <title><?= htmlspecialchars((string)($seg['label'] ?? '')) ?> ¥<?= number_format((int)($seg['amount'] ?? 0)) ?> （<?= round($ratio * 100) ?>%）</title>
                        </circle>
                        <?php $offset += $len; ?>
                      <?php endforeach; ?>
                    </svg>

                    <div class="expense-donut__center">
                      <p class="expense-donut__center-eyebrow">合計</p>
                      <p class="expense-donut__center-total">¥<?= number_format((int)($expense_summary['total'] ?? 0)) ?></p>
                      <p class="expense-donut__center-count"><?= (int)($expense_summary['count'] ?? 0) ?>件</p>
                    </div>
                  </div>

                  <ul class="expense-donut__legend">
                    <?php foreach ($segments as $seg): ?>
                      <?php
                        $key = (string)($seg['key'] ?? 'other');
                        $ratio = (float)($seg['ratio'] ?? 0);
                      ?>
                      <li>
                        <span class="dot cat-<?= htmlspecialchars($key) ?>"></span>
                        <span class="legend-label"><?= htmlspecialchars((string)($seg['label'] ?? '')) ?></span>
                        <span class="legend-amount">¥<?= number_format((int)($seg['amount'] ?? 0)) ?></span>
                        <span class="legend-ratio"><?= round($ratio * 100) ?>%</span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </section>

                <section class="expense-daily" aria-label="日別の支出">
                  <header class="expense-daily__head">
                    <h3 class="expense-daily__title">日別の支出</h3>
                    <p class="expense-daily__sub">遠征期間中の日ごとの合計</p>
                  </header>

                  <?php
                    $daily = $expense_summary['daily'] ?? [];
                    $daily_actual_max = 0;
                    foreach ($daily as $d) {
                      if (($d['amount'] ?? 0) > $daily_actual_max) {
                        $daily_actual_max = (int)$d['amount'];
                      }
                    }
                    $daily_max = max($daily_actual_max, 1); // 0除算回避（バー高さ用）
                  ?>

                  <?php if (empty($daily)): ?>
                    <p class="expense-daily__empty">日別の支出データがありません。</p>
                  <?php else: ?>
                    <ul class="expense-daily__list">
                      <?php foreach ($daily as $d): ?>
                        <?php
                          $ratio = $d['amount'] / $daily_max;
                          $h = max(2, round($ratio * 100)); // %（2%は最小視認）
                          $ts = strtotime($d['date']);
                          $md = date('n/j', $ts);
                          $w  = ['日','月','火','水','木','金','土'][(int)date('w', $ts)];
                        ?>
                        <li class="expense-daily__item<?= $d['amount'] === 0 ? ' is-zero' : '' ?>">
                          <div class="expense-daily__bar-wrap" aria-hidden="true">
                            <span class="expense-daily__bar" style="height: <?= $h ?>%"></span>
                          </div>
                          <p class="expense-daily__date">
                            <span class="md"><?= $md ?></span>
                            <span class="dow">(<?= $w ?>)</span>
                          </p>
                          <p class="expense-daily__amount">
                            <?= $d['amount'] > 0 ? '¥'.number_format($d['amount']) : '—' ?>
                          </p>
                        </li>
                      <?php endforeach; ?>
                    </ul>

                    <p class="expense-daily__foot">
                      最大日 <strong>¥<?= number_format($daily_actual_max) ?></strong>
                      ／ 平均 <strong>¥<?= number_format((int)($expense_summary['avg_per_day'] ?? 0)) ?></strong>
                    </p>
                  <?php endif; ?>
                </section>

                <section class="expense-budget is-empty" aria-label="予算（準備中）">
                  <header class="expense-budget__head">
                    <div>
                      <h3 class="expense-budget__title">予算</h3>
                      <p class="expense-budget__sub">遠征の予算を設定して、超過リスクを早期に把握</p>
                    </div>
                    <span class="expense-budget__badge">準備中</span>
                  </header>

                  <div class="expense-budget__body">
                    <div class="expense-budget__row">
                      <span class="expense-budget__row-label">予算</span>
                      <span class="expense-budget__row-value is-muted">未設定</span>
                    </div>
                    <div class="expense-budget__row">
                      <span class="expense-budget__row-label">現在の支出</span>
                      <span class="expense-budget__row-value">¥<?= number_format((int)($expense_summary['total'] ?? 0)) ?></span>
                    </div>
                    <div class="expense-budget__row">
                      <span class="expense-budget__row-label">残り</span>
                      <span class="expense-budget__row-value is-muted">—</span>
                    </div>

                    <div class="expense-budget__bar" aria-hidden="true">
                      <span class="expense-budget__bar-fill" style="width: 0%"></span>
                    </div>
                    <p class="expense-budget__bar-caption">
                      <span class="expense-budget__bar-pct">0%</span>
                      <span class="expense-budget__bar-note">予算が設定されると進捗が表示されます</span>
                    </p>
                  </div>

                  <footer class="expense-budget__foot">
                    <button type="button" class="expense-budget__cta" disabled>
                      予算を設定（近日対応）
                    </button>
                  </footer>
                </section>

                <section class="expense-compare" aria-label="過去の遠征との比較">
                  <header class="expense-compare__head">
                    <h3 class="expense-compare__title">過去の遠征と比較</h3>
                    <p class="expense-compare__sub">
                      <?php if ((int)$expense_compare['sample_size'] === 1): ?>
                        過去 <strong>1件</strong> の遠征と比較
                      <?php elseif ((int)$expense_compare['sample_size'] > 1): ?>
                        過去 <strong><?= (int)$expense_compare['sample_size'] ?>件</strong> の中央値と比較
                      <?php else: ?>
                        まだ比較できる過去遠征がありません
                      <?php endif; ?>
                    </p>
                  </header>

                  <?php if ((int)$expense_compare['sample_size'] === 0): ?>
                    <p class="expense-compare__empty">
                      遠征を完了するたびに、こちらで自動的に比較データが蓄積されます。
                    </p>
                  <?php else: ?>
                    <ul class="expense-compare__list">
                      <?php
                        $rows = [
                          ['label'=>'合計支出',  'self'=>$expense_compare['self_total'],       'median'=>$expense_compare['median_total'],       'diff'=>$expense_compare['diff_total_pct'],       'fmt'=>'yen',   'mute_diff'=>false],
                          ['label'=>'1日あたり', 'self'=>$expense_compare['self_avg_per_day'], 'median'=>$expense_compare['median_avg_per_day'], 'diff'=>$expense_compare['diff_avg_per_day_pct'], 'fmt'=>'yen',   'mute_diff'=>false],
                          ['label'=>'件数',      'self'=>$expense_compare['self_count'],       'median'=>$expense_compare['median_count'],       'diff'=>$expense_compare['diff_count_pct'],       'fmt'=>'count', 'mute_diff'=>true],
                        ];
                        $fmt = function($v, $kind) {
                          if ($v === null) return '—';
                          return $kind === 'yen' ? '¥'.number_format((int)$v) : number_format((int)$v).'件';
                        };
                      ?>
                      <?php foreach ($rows as $r): ?>
                        <?php
                          $diff = $r['diff'];
                          $cls = 'is-flat';
                          $sign = '±';
                          if (empty($r['mute_diff']) && $diff !== null) {
                            if ($diff > 2)      { $cls = 'is-up';   $sign = '▲'; }
                            elseif ($diff < -2) { $cls = 'is-down'; $sign = '▼'; }
                          }
                        ?>
                        <li class="expense-compare__row">
                          <span class="expense-compare__label"><?= htmlspecialchars($r['label']) ?></span>
                          <div class="expense-compare__values">
                            <span class="expense-compare__self"><?= $fmt($r['self'], $r['fmt']) ?></span>
                            <span class="expense-compare__median">中央値 <?= $fmt($r['median'], $r['fmt']) ?></span>
                          </div>
                          <span class="expense-compare__diff <?= $cls ?>">
                            <?= $sign ?>
                            <?= $diff === null ? '—' : abs($diff).'%' ?>
                          </span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </section>
              </aside>
            </div>

            <?php if (defined('APP_DEBUG') && APP_DEBUG === true): ?>
              <pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto;">
<?= htmlspecialchars(json_encode($expense_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>
              </pre>
            <?php endif; ?>
        </div>
        </div>

        <div class="lt-tab-panel w-full" data-panel="hotel" id="panel-hotel">
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
                ?>
                <div id="hotel-<?= (int)$h['id'] ?>" class="hotel-item hotel-row p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
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
                        <?php /* Static Map は共通Mapへ統合 */ ?>
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

        <div class="lt-tab-panel w-full" data-panel="destination" id="panel-destination">
        <div class="space-y-4">
            <?php
            $destinationTypeLabelMap = \App\LiveTrip\Model\DestinationModel::$types;
            $destinationTypeIconMap = [
                'main' => '🎯',
                'collab' => '🛍️',
                'sightseeing' => '☀️',
                'other' => '📌',
            ];
            $hasVenueDestination = (!empty($eventPlaceForArea) && is_string($eventPlaceForArea) && trim($eventPlaceForArea) !== '');
            $destinationTotal = (int)count($destinations ?? []) + ($hasVenueDestination ? 1 : 0);
            $destinationCountsByType = [];
            $prefSet = [];
            $prefList = [
                '北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
                '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
                '新潟県','富山県','石川県','福井県','山梨県','長野県',
                '岐阜県','静岡県','愛知県','三重県',
                '滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県',
                '鳥取県','島根県','岡山県','広島県','山口県',
                '徳島県','香川県','愛媛県','高知県',
                '福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県',
            ];
            // 会場（イベント開催地）も目的地KPIに含める（編集不可・DB未登録）
            if ($hasVenueDestination) {
                $destinationCountsByType['venue'] = 1;
                $venueText = trim((string)$eventPlaceForArea);
                foreach ($prefList as $pref) {
                    if ($venueText !== '' && mb_strpos($venueText, $pref) !== false) {
                        $prefSet[$pref] = true;
                        break;
                    }
                }
            }

            foreach (($destinations ?? []) as $d) {
                $t = (string)($d['destination_type'] ?? 'other');
                if (!isset($destinationTypeLabelMap[$t])) $t = 'other';
                $destinationCountsByType[$t] = ($destinationCountsByType[$t] ?? 0) + 1;

                $addr = trim((string)($d['address'] ?? ''));
                foreach ($prefList as $pref) {
                    if ($addr !== '' && mb_strpos($addr, $pref) !== false) {
                        $prefSet[$pref] = true;
                        break;
                    }
                }
            }
            $prefNames = array_keys($prefSet);
            $prefShort = array_map(function($p) {
                return preg_replace('/(都|道|府|県)$/u', '', (string)$p);
            }, $prefNames);
            $destinationPrefText = '';
            if (!empty($prefShort)) {
                $destinationPrefText = '🗾 ' . implode('・', $prefShort);
            }

            $destinationFormAnchorId = 'destination-add';
            ?>

            <section class="bg-white border border-slate-200 rounded-xl shadow-sm px-5 sm:px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="expense-hero__eyebrow">📍 登録された目的地</p>
                        <div class="mt-2 flex items-end gap-2">
                            <div class="text-3xl font-black text-slate-900"><?= (int)$destinationTotal ?><span class="text-base font-bold text-slate-500 ml-1">件</span></div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <?php
                            $destinationTypeLabelMap['venue'] = '会場';
                            $destinationTypeIconMap['venue'] = '🎤';
                            $typeOrder = ['venue', 'main', 'collab', 'sightseeing', 'other'];
                            foreach ($typeOrder as $k):
                                $c = (int)($destinationCountsByType[$k] ?? 0);
                                if ($c <= 0) continue;
                                $label = (string)($destinationTypeLabelMap[$k] ?? 'その他');
                                $icon = (string)($destinationTypeIconMap[$k] ?? '📌');
                            ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                                    <span aria-hidden="true"><?= htmlspecialchars($icon) ?></span>
                                    <span><?= htmlspecialchars($label) ?> <?= (int)$c ?></span>
                                </span>
                            <?php endforeach; ?>
                            <?php if ($destinationTotal === 0): ?>
                                <span class="text-xs font-bold text-slate-500">まだ登録がありません</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 lg:max-w-[420px]">
                        <p class="expense-hero__eyebrow">エリア</p>
                        <p class="mt-2 text-base font-black text-slate-900 truncate">
                            <?= htmlspecialchars($destinationPrefText !== '' ? $destinationPrefText : '未設定') ?>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">住所から都道府県を抽出して表示します</p>
                    </div>

                    <div class="flex items-start gap-2 shrink-0 justify-start lg:justify-end">
                        <button type="button" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap" disabled title="準備中">
                            🗺️ 地図表示
                        </button>
                        <button type="button" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap inline-flex items-center gap-2" data-action="open-destination-add">
                            <i class="fa-solid fa-plus"></i><span>目的地を追加</span>
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-10 gap-4 items-start">
                <!-- 左：目的地（サマリ） -->
                <div class="lg:col-span-6 bg-white border border-slate-200 rounded-xl shadow-sm p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="expense-hero__eyebrow">目的地</p>
                        <span class="text-sm text-slate-500 font-bold"><?= (int)$destinationTotal ?>件</span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 items-stretch">
                        <?php
                            $destBadgeMap = [
                                'venue' => ['label' => '会場', 'emoji' => '🏟️', 'cls' => 'bg-slate-100 text-slate-700'],
                                'main' => ['label' => 'メイン', 'emoji' => '🎯', 'cls' => 'bg-emerald-50 text-emerald-700'],
                                'collab' => ['label' => 'コラボ店', 'emoji' => '🛍️', 'cls' => 'bg-pink-50 text-pink-700'],
                                'sightseeing' => ['label' => '観光', 'emoji' => '☀️', 'cls' => 'bg-indigo-50 text-indigo-700'],
                                'other' => ['label' => 'その他', 'emoji' => '📌', 'cls' => 'bg-slate-100 text-slate-700'],
                            ];
                        ?>

                        <?php if (!empty($eventPlace) && is_string($eventPlace)): ?>
                        <?php
                            $venueLabel = trim($eventPlace);
                            $venueAddress = $eventPlaceAddress !== '' ? $eventPlaceAddress : '';
                            $venueMapsUrl = $eventPlaceForMaps !== '#' ? $eventPlaceForMaps : '#';
                            $venueMapTarget = $venueAddress !== '' ? $venueAddress : $venueLabel;
                            $venueMapSearchUrl = $venueMapTarget !== '' ? ('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($venueMapTarget)) : '#';
                            $venueDirTarget = $venueAddress !== '' ? $venueAddress : $venueLabel;
                            $venueDirUrl = $venueDirTarget !== '' ? ('https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($venueDirTarget) . '&travelmode=transit') : '#';
                            $b = $destBadgeMap['venue'];
                        ?>
                        <div class="destination-item group relative p-4 rounded-xl border border-slate-200 bg-slate-50 shadow-sm h-full flex flex-col">
                            <div class="destination-view flex flex-col h-full">
                                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-black <?= htmlspecialchars($b['cls']) ?>">
                                    <span aria-hidden="true"><?= htmlspecialchars($b['emoji']) ?></span>
                                    <span><?= htmlspecialchars($b['label']) ?></span>
                                </div>
                                <div class="mt-2 text-sm font-black text-slate-800 truncate">
                                    <?php if ($venueMapsUrl !== '#'): ?>
                                        <a href="<?= htmlspecialchars($venueMapsUrl) ?>" target="_blank" rel="noopener" class="hover:underline text-slate-800">
                                            <?= htmlspecialchars($venueLabel) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($venueLabel) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($venueAddress !== ''): ?>
                                    <div class="mt-2 expense-hero__eyebrow truncate" title="<?= htmlspecialchars($venueAddress) ?>">📮<?= htmlspecialchars($venueAddress) ?></div>
                                <?php endif; ?>
                                <div class="mt-3 flex items-center gap-2 flex-wrap text-xs font-bold">
                                    <?php if ($venue !== null): ?>
                                        <button type="button"
                                                class="js-destination-map-focus inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50"
                                                data-lat="<?= htmlspecialchars((string)$venue['lat'], ENT_QUOTES) ?>"
                                                data-lng="<?= htmlspecialchars((string)$venue['lng'], ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars((string)$venueLabel, ENT_QUOTES) ?>">
                                            <span aria-hidden="true">🗺️</span><span>Map</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-400 cursor-not-allowed" title="会場の座標が未登録です">
                                            <span aria-hidden="true">🗺️</span><span>Map</span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($venueDirUrl !== '#'): ?>
                                        <a href="<?= htmlspecialchars($venueDirUrl) ?>" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                                            <span aria-hidden="true">🚇</span><span>電車</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-400 cursor-not-allowed" title="住所情報がありません">
                                            <span aria-hidden="true">🚇</span><span>電車</span>
                                        </span>
                                    <?php endif; ?>
                                    <span class="ml-auto text-slate-500">編集不可</span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($destinations ?? [] as $d):
                            $mapsUrl = $destinationModel->getGoogleMapsUrl($d);
                            $typeKey = (string)($d['destination_type'] ?? 'other');
                            if (!isset($destBadgeMap[$typeKey])) $typeKey = 'other';
                            $b = $destBadgeMap[$typeKey];
                            $addrText = trim((string)($d['address'] ?? ''));
                            $mapTarget = $addrText !== '' ? $addrText : trim((string)($d['name'] ?? ''));
                            $mapSearchUrl = $mapTarget !== '' ? ('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapTarget)) : '#';
                            $dirTarget = $addrText !== '' ? $addrText : trim((string)($d['name'] ?? ''));
                            $dirUrl = $dirTarget !== '' ? ('https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($dirTarget) . '&travelmode=transit') : '#';
                            $dLat = trim((string)($d['latitude'] ?? ''));
                            $dLng = trim((string)($d['longitude'] ?? ''));
                        ?>
                        <div class="destination-item group relative p-4 rounded-xl border border-slate-200 bg-white shadow-sm hover:border-slate-300 hover:shadow-md transition h-full flex flex-col">
                            <div class="destination-view flex flex-col h-full">
                                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-black <?= htmlspecialchars($b['cls']) ?>">
                                    <span aria-hidden="true"><?= htmlspecialchars($b['emoji']) ?></span>
                                    <span><?= htmlspecialchars($b['label']) ?></span>
                                </div>

                                <div class="mt-2 text-sm font-black text-slate-800 truncate">
                                    <?php if ($mapsUrl !== '#'): ?>
                                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="hover:underline text-slate-800">
                                            <?= htmlspecialchars((string)($d['name'] ?? '')) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)($d['name'] ?? '')) ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($addrText !== ''): ?>
                                    <div class="mt-2 expense-hero__eyebrow truncate" title="<?= htmlspecialchars($addrText) ?>">📮<?= htmlspecialchars($addrText) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($d['visit_date'])): ?>
                                    <div class="mt-2 text-xs font-bold text-slate-600">
                                        🗓️ <?= htmlspecialchars((string)$d['visit_date']) ?><?= !empty($d['visit_time']) ? ' ' . htmlspecialchars((string)$d['visit_time']) : '' ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3 flex items-center gap-2 flex-wrap text-xs font-bold">
                                    <?php if ($dLat !== '' && $dLng !== ''): ?>
                                        <button type="button"
                                                class="js-destination-map-focus inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50"
                                                data-lat="<?= htmlspecialchars($dLat, ENT_QUOTES) ?>"
                                                data-lng="<?= htmlspecialchars($dLng, ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars((string)($d['name'] ?? ''), ENT_QUOTES) ?>">
                                            <span aria-hidden="true">🗺️</span><span>Map</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-400 cursor-not-allowed" title="座標が未登録です（住所候補から選択すると登録されます）">
                                            <span aria-hidden="true">🗺️</span><span>Map</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($dirUrl !== '#'): ?>
                                        <a href="<?= htmlspecialchars($dirUrl) ?>" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                                            <span aria-hidden="true">🚇</span><span>電車</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-400 cursor-not-allowed" title="住所情報がありません">
                                            <span aria-hidden="true">🚇</span><span>電車</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Hover actions -->
                            <div class="absolute right-3 bottom-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    type="button"
                                    class="destination-edit-to-side w-9 h-9 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50"
                                    title="編集"
                                    aria-label="編集"
                                    data-id="<?= (int)$d['id'] ?>"
                                    data-name="<?= htmlspecialchars((string)($d['name'] ?? ''), ENT_QUOTES) ?>"
                                    data-address="<?= htmlspecialchars((string)($d['address'] ?? ''), ENT_QUOTES) ?>"
                                    data-destination-type="<?= htmlspecialchars((string)($d['destination_type'] ?? 'other'), ENT_QUOTES) ?>"
                                    data-visit-date="<?= htmlspecialchars((string)($d['visit_date'] ?? ''), ENT_QUOTES) ?>"
                                    data-visit-time="<?= htmlspecialchars((string)($d['visit_time'] ?? ''), ENT_QUOTES) ?>"
                                    data-memo="<?= htmlspecialchars((string)($d['memo'] ?? ''), ENT_QUOTES) ?>"
                                    data-latitude="<?= htmlspecialchars((string)($d['latitude'] ?? ''), ENT_QUOTES) ?>"
                                    data-longitude="<?= htmlspecialchars((string)($d['longitude'] ?? ''), ENT_QUOTES) ?>"
                                    data-place-id="<?= htmlspecialchars((string)($d['place_id'] ?? ''), ENT_QUOTES) ?>"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" action="/live_trip/destination_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                                    <input type="hidden" name="tab" value="destination">
                                    <button type="submit" class="w-9 h-9 inline-flex items-center justify-center rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50" title="削除" aria-label="削除">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php foreach ($hotelStays ?? [] as $h):
                            $hotelName = (string)($h['hotel_name'] ?? '');
                            $hotelAddr = trim((string)($h['address'] ?? ''));
                        ?>
                        <div class="destination-item group relative p-4 rounded-xl border border-slate-200 bg-white shadow-sm hover:border-slate-300 hover:shadow-md transition h-full flex flex-col">
                            <div class="destination-view flex flex-col h-full">
                                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-700">
                                    <span aria-hidden="true">🏨</span><span>宿泊</span>
                                </div>
                                <div class="mt-2 text-sm font-black text-slate-800 truncate">
                                    <?= htmlspecialchars($hotelName !== '' ? $hotelName : '（ホテル名未設定）') ?>
                                </div>
                                <?php if ($hotelAddr !== ''): ?>
                                    <div class="mt-2 expense-hero__eyebrow truncate" title="<?= htmlspecialchars($hotelAddr) ?>">📮<?= htmlspecialchars($hotelAddr) ?></div>
                                <?php endif; ?>
                                <div class="mt-auto"></div>
                            </div>

                            <!-- Hover actions: edit only -->
                            <div class="absolute right-3 bottom-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    type="button"
                                    class="js-hotel-edit-from-destination w-9 h-9 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50"
                                    title="宿泊を編集"
                                    aria-label="宿泊を編集"
                                    data-hotel-id="<?= (int)($h['id'] ?? 0) ?>"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($destinations ?? []) && empty($eventPlace)): ?>
                            <div class="col-span-full text-sm text-slate-500">目的地がありません。</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 右：目的地を追加 + 地図表示 -->
                <div class="lg:col-span-4 space-y-4" id="<?= htmlspecialchars($destinationFormAnchorId) ?>">
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 sm:p-6">
                    <button type="button" class="w-full flex items-center justify-between gap-3" id="destination-side-form-toggle" aria-expanded="true">
                        <div class="min-w-0 text-left">
                            <div class="text-base font-normal text-slate-900" id="destination-side-form-title">＋ 目的地を追加</div>
                            <div class="text-xs text-slate-500 mt-1 hidden" id="destination-side-form-subtitle"></div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="hidden text-xs font-black text-slate-600 hover:text-slate-800" id="destination-side-form-cancel">
                                <i class="fa-solid fa-xmark"></i> 編集をやめる
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400" id="destination-side-form-chevron" aria-hidden="true"></i>
                        </div>
                    </button>

                    <div class="mt-4" id="destination-side-form-body">
                        <div class="flex items-center gap-2 p-1 rounded-xl bg-slate-100 border border-slate-200">
                            <button type="button" class="dest-type-toggle flex-1 px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white" data-destination-type="main">🎯 メイン</button>
                            <button type="button" class="dest-type-toggle flex-1 px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white" data-destination-type="collab">🛍️ コラボ店</button>
                            <button type="button" class="dest-type-toggle flex-1 px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white" data-destination-type="sightseeing">☀️ 観光</button>
                            <button type="button" class="dest-type-toggle flex-1 px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:bg-white" data-destination-type="other">📌 その他</button>
                        </div>

                        <form method="post" action="/live_trip/destination_store.php" class="mt-4 space-y-3" id="destination-side-form">
                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                            <input type="hidden" name="tab" value="destination">
                            <input type="hidden" name="id" id="destination-id-hidden" value="">
                            <input type="hidden" name="destination_type" id="destination-type-hidden" value="main">
                            <input type="hidden" name="latitude" id="destination-latitude" value="">
                            <input type="hidden" name="longitude" id="destination-longitude" value="">
                            <input type="hidden" name="place_id" id="destination-place-id" value="">

                            <div class="relative">
                                <label class="block text-xs font-bold text-slate-500 mb-1">名前 *</label>
                                <input type="text" id="destination-name" name="name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="店名・施設名など（日本語入力で候補を選択すると住所に反映）" autocomplete="off">
                                <div id="destination-name-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">住所</label>
                                <input type="text" id="destination-address" name="address" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Google Maps用（名前で候補選択時に自動反映）">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">訪問予定日</label>
                                    <input type="date" name="visit_date" id="destination-visit-date" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">目安時刻</label>
                                    <input type="text" name="visit_time" id="destination-visit-time" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="例 10:00">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">メモ</label>
                                <input type="text" name="memo" id="destination-memo" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="営業時間・注意など">
                            </div>

                            <button type="submit" class="lt-theme-btn text-white w-full px-4 py-2 rounded-lg text-sm font-black" id="destination-side-form-submit">＋ 目的地を追加</button>
                        </form>
                    </div>

                    <script>
                    (function() {
                        const root = document.getElementById('<?= htmlspecialchars($destinationFormAnchorId) ?>');
                        if (!root) return;
                        const toggleBtn = root.querySelector('#destination-side-form-toggle');
                        const chevron = root.querySelector('#destination-side-form-chevron');
                        const body = root.querySelector('#destination-side-form-body');
                        const form = root.querySelector('#destination-side-form');
                        const titleEl = root.querySelector('#destination-side-form-title');
                        const subtitleEl = root.querySelector('#destination-side-form-subtitle');
                        const cancelBtn = root.querySelector('#destination-side-form-cancel');
                        const submitBtn = root.querySelector('#destination-side-form-submit');
                        const idHidden = root.querySelector('#destination-id-hidden');
                        const hidden = root.querySelector('#destination-type-hidden');
                        const btns = Array.from(root.querySelectorAll('.dest-type-toggle'));
                        function setType(t) {
                            if (!hidden) return;
                            hidden.value = t;
                            btns.forEach(b => {
                                const active = b.dataset.destinationType === t;
                                b.classList.toggle('bg-white', active);
                                b.classList.toggle('shadow-sm', active);
                                b.classList.toggle('text-slate-800', active);
                                b.classList.toggle('text-slate-600', !active);
                            });
                        }
                        btns.forEach(b => b.addEventListener('click', () => setType(b.dataset.destinationType)));
                        setType(hidden ? (hidden.value || 'main') : 'main');

                        function setExpanded(expanded) {
                            if (!toggleBtn || !body) return;
                            toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                            body.classList.toggle('hidden', !expanded);
                            if (chevron) {
                                chevron.classList.toggle('fa-chevron-down', expanded);
                                chevron.classList.toggle('fa-chevron-up', !expanded);
                            }
                        }
                        toggleBtn && toggleBtn.addEventListener('click', function(e) {
                            // cancel テキスト（span）クリック時は無視（別ハンドラで処理）
                            if (e.target && e.target.closest && e.target.closest('#destination-side-form-cancel')) return;
                            const cur = toggleBtn.getAttribute('aria-expanded') === 'true';
                            setExpanded(!cur);
                        });

                        function enterAddMode() {
                            if (!form) return;
                            form.action = '/live_trip/destination_store.php';
                            if (idHidden) idHidden.value = '';
                            if (titleEl) titleEl.textContent = '＋ 目的地を追加';
                            if (subtitleEl) { subtitleEl.classList.add('hidden'); subtitleEl.textContent = ''; }
                            if (cancelBtn) cancelBtn.classList.add('hidden');
                            if (submitBtn) submitBtn.textContent = '＋ 目的地を追加';
                        }

                        function enterEditMode(payload) {
                            if (!form) return;
                            form.action = '/live_trip/destination_update.php';
                            if (idHidden) idHidden.value = payload.id || '';
                            if (titleEl) titleEl.textContent = '✏️ 目的地を編集';
                            if (subtitleEl) {
                                subtitleEl.textContent = (payload.name || '') ? ('編集中: ' + (payload.name || '')) : '編集中';
                                subtitleEl.classList.remove('hidden');
                            }
                            if (cancelBtn) cancelBtn.classList.remove('hidden');
                            if (submitBtn) submitBtn.textContent = '保存';

                            const nameInput = root.querySelector('#destination-name');
                            const addrInput = root.querySelector('#destination-address');
                            const visitDate = root.querySelector('#destination-visit-date');
                            const visitTime = root.querySelector('#destination-visit-time');
                            const memo = root.querySelector('#destination-memo');
                            const placeId = root.querySelector('#destination-place-id');
                            const lat = root.querySelector('#destination-latitude');
                            const lng = root.querySelector('#destination-longitude');
                            if (nameInput) nameInput.value = payload.name || '';
                            if (addrInput) addrInput.value = payload.address || '';
                            if (visitDate) visitDate.value = payload.visitDate || '';
                            if (visitTime) visitTime.value = payload.visitTime || '';
                            if (memo) memo.value = payload.memo || '';
                            if (placeId) placeId.value = payload.placeId || '';
                            if (lat) lat.value = payload.latitude || '';
                            if (lng) lng.value = payload.longitude || '';
                            if (payload.destinationType) setType(payload.destinationType);
                        }

                        cancelBtn && cancelBtn.addEventListener('click', function() {
                            if (!form) return;
                            form.reset();
                            // reset() では hidden も初期値になるが、明示的に add の初期値に戻す
                            setType('main');
                            const placeId = root.querySelector('#destination-place-id');
                            const lat = root.querySelector('#destination-latitude');
                            const lng = root.querySelector('#destination-longitude');
                            if (placeId) placeId.value = '';
                            if (lat) lat.value = '';
                            if (lng) lng.value = '';
                            enterAddMode();
                        });

                        document.addEventListener('click', function(e) {
                            const btn = e.target.closest('.destination-edit-to-side');
                            if (!btn) return;
                            e.preventDefault();
                            const payload = {
                                id: btn.dataset.id || '',
                                name: btn.dataset.name || '',
                                address: btn.dataset.address || '',
                                destinationType: btn.dataset.destinationType || 'other',
                                visitDate: btn.dataset.visitDate || '',
                                visitTime: btn.dataset.visitTime || '',
                                memo: btn.dataset.memo || '',
                                latitude: btn.dataset.latitude || '',
                                longitude: btn.dataset.longitude || '',
                                placeId: btn.dataset.placeId || '',
                            };
                            enterEditMode(payload);
                            setExpanded(true);
                            // 右カードへスクロール
                            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });

                        function expandAndFocusName() {
                            setExpanded(true);
                            setTimeout(function() {
                                const nameInput = root.querySelector('#destination-name');
                                if (nameInput) nameInput.focus();
                            }, 50);
                        }

                        // KPI等の外部トリガー用
                        window.__ltDestinationSideForm = window.__ltDestinationSideForm || {};
                        window.__ltDestinationSideForm.expandAndFocus = expandAndFocusName;

                        // 初期は追加モード（縮小状態）
                        enterAddMode();
                        setExpanded(false);
                    })();
                    </script>

                    <script>
                    // KPIの「目的地を追加」押下で展開＆フォーカス
                    (function() {
                        function run() {
                            if (typeof window.switchTab === 'function') window.switchTab('destination');
                            if (window.__ltDestinationSideForm && typeof window.__ltDestinationSideForm.expandAndFocus === 'function') {
                                window.__ltDestinationSideForm.expandAndFocus();
                            }
                        }
                        document.addEventListener('click', function(e) {
                            var btn = e.target.closest('[data-action="open-destination-add"]');
                            if (!btn) return;
                            e.preventDefault();
                            setTimeout(run, 0);
                        });
                    })();
                    </script>

                    <script>
                    // 目的地サマリ内の宿泊「編集」→宿泊タブの該当行を編集状態で開く
                    (function() {
                        document.addEventListener('click', function(e) {
                            var btn = e.target.closest('.js-hotel-edit-from-destination');
                            if (!btn) return;
                            e.preventDefault();
                            var id = btn.dataset.hotelId || '';
                            if (!id) return;
                            // hashでホテルタブへ + 該当行へスクロール
                            location.hash = 'hotel-' + id;
                            setTimeout(function() {
                                var row = document.getElementById('hotel-' + id);
                                if (row && typeof row.scrollIntoView === 'function') {
                                    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }
                                var edit = row ? row.querySelector('.hotel-edit-btn') : null;
                                if (edit) edit.click();
                            }, 120);
                        });
                    })();
                    </script>

                    <script>
                    // Destination tab embedded map (below the form)
                    (function() {
                        function init() {
                            if (!window.google || !window.google.maps) return;
                            var el = document.getElementById('ltDestinationMap');
                            if (!el) return;
                            if (el.dataset.inited === '1') return;
                            el.dataset.inited = '1';

                            var venue = <?= json_encode($venue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                            var destinations = <?= json_encode(array_values($destinationsForMap ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                            function firstPoint() {
                                if (venue && typeof venue.lat === 'number' && typeof venue.lng === 'number') return { lat: venue.lat, lng: venue.lng, name: venue.name || '会場' };
                                if (Array.isArray(destinations) && destinations.length > 0) {
                                    var d = destinations[0];
                                    if (d && typeof d.lat === 'number' && typeof d.lng === 'number') return { lat: d.lat, lng: d.lng, name: d.name || '目的地' };
                                }
                                return { lat: 35.681236, lng: 139.767125, name: '地図' }; // fallback
                            }

                            var p0 = firstPoint();
                            var map = new google.maps.Map(el, {
                                center: { lat: p0.lat, lng: p0.lng },
                                zoom: 14,
                                mapTypeControl: false,
                                streetViewControl: false,
                                fullscreenControl: false
                            });
                            var marker = new google.maps.Marker({
                                map: map,
                                position: { lat: p0.lat, lng: p0.lng },
                                title: p0.name || ''
                            });
                            var info = new google.maps.InfoWindow({ content: '' });

                            function escapeHtml(s) {
                                return String(s).replace(/[&<>"']/g, function(ch) {
                                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]);
                                });
                            }
                            function focusPoint(lat, lng, name) {
                                if (!isFinite(lat) || !isFinite(lng)) return;
                                var pos = { lat: lat, lng: lng };
                                map.panTo(pos);
                                map.setZoom(Math.max(map.getZoom() || 14, 14));
                                marker.setPosition(pos);
                                marker.setTitle(name || '');
                                if (name) {
                                    info.setContent('<div style="font-weight:700;">' + escapeHtml(name) + '</div>');
                                    info.open({ map: map, anchor: marker });
                                }
                            }

                            document.addEventListener('click', function(e) {
                                var btn = e.target.closest('.js-destination-map-focus');
                                if (!btn) return;
                                e.preventDefault();
                                var lat = parseFloat(btn.dataset.lat || '');
                                var lng = parseFloat(btn.dataset.lng || '');
                                var name = btn.dataset.name || '';
                                if (!isFinite(lat) || !isFinite(lng)) {
                                    window.App && App.toast && App.toast('座標が未登録のため地図表示できません。', 3000);
                                    return;
                                }
                                focusPoint(lat, lng, name);
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            });
                        }

                        if (window.__ltMapsReady) {
                            init();
                        } else {
                            document.addEventListener('lt-maps-ready', init, { once: true });
                        }
                    })();
                    </script>
                </div><!-- /目的地追加カード -->

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="expense-hero__eyebrow">地図表示</p>
                        <p class="text-xs text-slate-500">Mapボタンでここに表示します</p>
                    </div>
                    <?php if (!$enableDestinationMap): ?>
                        <div class="mt-3 p-4 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-600">
                            <?php if ($mapsJsApiKey === ''): ?>
                                Google Maps APIキーが未設定のため、地図は表示できません。
                            <?php else: ?>
                                表示できる座標付きスポットがありません。（会場または目的地に座標が必要です）
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div id="ltDestinationMap" class="mt-3 w-full rounded-xl border border-slate-200 overflow-hidden" style="height: 280px;"></div>
                        <p class="mt-2 text-xs text-slate-500">
                            初期表示は会場を表示します（会場の座標が無い場合は、座標付きの目的地を表示します）。
                        </p>
                    <?php endif; ?>
                </div>
                </div><!-- /right column wrapper -->
            </section>
        </div>
        </div>

        <div class="lt-tab-panel w-full" data-panel="transport" id="panel-transport">
        <?php
        $transportCandidates = [];
        // 自宅（ユーザー別に保存）
        $homeAddress = trim((string)($homePlace['address'] ?? ''));
        if ($homeAddress !== '') {
            $transportCandidates[] = ['label' => '自宅', 'value' => $homeAddress];
        }
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
                <details class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <summary class="cursor-pointer text-sm font-bold text-slate-600">自宅を登録（ユーザー別）</summary>
                    <div class="mt-3">
                        <form method="post" action="/live_trip/home_place_store.php" class="space-y-2">
                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                            <input type="hidden" name="label" value="自宅">
                            <input type="hidden" name="place_id" id="home-place-id" value="<?= htmlspecialchars((string)($homePlace['place_id'] ?? '')) ?>">
                            <input type="hidden" name="latitude" id="home-lat" value="<?= htmlspecialchars((string)($homePlace['latitude'] ?? '')) ?>">
                            <input type="hidden" name="longitude" id="home-lng" value="<?= htmlspecialchars((string)($homePlace['longitude'] ?? '')) ?>">
                            <input type="hidden" name="address" id="home-address-hidden" value="<?= htmlspecialchars((string)($homePlace['address'] ?? '')) ?>">
                            <div class="relative">
                                <label class="block text-xs font-bold text-slate-500 mb-1">住所 / 施設名</label>
                                <input type="text" id="home-address-input" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" autocomplete="off"
                                       placeholder="例: 渋谷駅 / 東京都渋谷区..." value="<?= htmlspecialchars((string)($homePlace['address'] ?? '')) ?>">
                                <div id="home-address-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="submit" class="lt-theme-btn text-white px-3 py-2 rounded text-sm">自宅を保存</button>
                                <?php if (!empty($homePlace['address'])): ?>
                                    <span class="text-xs text-slate-500">現在: <?= htmlspecialchars((string)$homePlace['address']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500">「登録済みから選ぶ」に自宅が表示されます。</p>
                        </form>
                    </div>
                </details>
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
                <div id="transport-<?= (int)$t['id'] ?>" class="transport-item transport-row p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
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

        <div class="lt-tab-panel w-full" data-panel="timeline" id="panel-timeline">
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
                <div class="relative flex-1 min-w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">場所</label>
                    <input type="text" id="timeline-location" name="location_label" placeholder="会場、ホテル、駅、店名など" class="border border-slate-200 rounded px-3 py-2 text-sm w-full" autocomplete="off">
                    <div id="timeline-location-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                    <input type="hidden" id="timeline-location-address" name="location_address" value="">
                    <input type="hidden" id="timeline-place-id" name="place_id" value="">
                    <input type="hidden" id="timeline-lat" name="latitude" value="">
                    <input type="hidden" id="timeline-lng" name="longitude" value="">
                </div>
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
                                    $placeIdAttr = '';
                                    $latAttr = '';
                                    $lngAttr = '';
                                    $locLabelAttr = '';
                                    $locAddrAttr = '';
                                    if ($m['type'] === 'timeline') {
                                        $ti = $m['data'] ?? [];
                                        $label = (string)($ti['label'] ?? '');
                                        $sub = (string)($ti['memo'] ?? '');
                                        $placeIdAttr = (string)($ti['place_id'] ?? '');
                                        $latAttr = (string)($ti['latitude'] ?? '');
                                        $lngAttr = (string)($ti['longitude'] ?? '');
                                        $locLabelAttr = (string)($ti['location_label'] ?? '');
                                        $locAddrAttr = (string)($ti['location_address'] ?? '');
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
                                         data-lt-place-id="<?= htmlspecialchars($placeIdAttr, ENT_QUOTES) ?>"
                                         data-lt-lat="<?= htmlspecialchars($latAttr, ENT_QUOTES) ?>"
                                         data-lt-lng="<?= htmlspecialchars($lngAttr, ENT_QUOTES) ?>"
                                         data-lt-location-label="<?= htmlspecialchars($locLabelAttr, ENT_QUOTES) ?>"
                                         data-lt-location-address="<?= htmlspecialchars($locAddrAttr, ENT_QUOTES) ?>"
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
                                            <input type="hidden" name="place_id" value="<?= htmlspecialchars($ti['place_id'] ?? '') ?>">
                                            <input type="hidden" name="latitude" value="<?= htmlspecialchars($ti['latitude'] ?? '') ?>">
                                            <input type="hidden" name="longitude" value="<?= htmlspecialchars($ti['longitude'] ?? '') ?>">
                                            <input type="hidden" name="location_label" value="<?= htmlspecialchars($ti['location_label'] ?? '') ?>">
                                            <input type="hidden" name="location_address" value="<?= htmlspecialchars($ti['location_address'] ?? '') ?>">
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
            <div class="timeline-row py-2 border-b border-slate-100 <?= $m['type'] === 'timeline' ? 'timeline-item' : '' ?>"
                 data-lt-type="<?= htmlspecialchars((string)($m['type'] ?? '')) ?>"
                 data-lt-id="<?= (int)(($m['data']['id'] ?? 0)) ?>"
                 data-lt-date="<?= htmlspecialchars((string)($m['date'] ?? '')) ?>"
                 data-lt-time="<?= htmlspecialchars((string)($m['time'] ?? '')) ?>"
                 data-lt-place-id="<?= htmlspecialchars((string)(($m['type'] ?? '') === 'timeline' ? (($m['data']['place_id'] ?? '') ?: '') : ''), ENT_QUOTES) ?>"
                 data-lt-lat="<?= htmlspecialchars((string)(($m['type'] ?? '') === 'timeline' ? (($m['data']['latitude'] ?? '') ?: '') : ''), ENT_QUOTES) ?>"
                 data-lt-lng="<?= htmlspecialchars((string)(($m['type'] ?? '') === 'timeline' ? (($m['data']['longitude'] ?? '') ?: '') : ''), ENT_QUOTES) ?>"
                 data-lt-location-label="<?= htmlspecialchars((string)(($m['type'] ?? '') === 'timeline' ? (($m['data']['location_label'] ?? '') ?: '') : ''), ENT_QUOTES) ?>"
                 data-lt-location-address="<?= htmlspecialchars((string)(($m['type'] ?? '') === 'timeline' ? (($m['data']['location_address'] ?? '') ?: '') : ''), ENT_QUOTES) ?>">
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
                    <input type="hidden" name="place_id" value="<?= htmlspecialchars($ti['place_id'] ?? '') ?>">
                    <input type="hidden" name="latitude" value="<?= htmlspecialchars($ti['latitude'] ?? '') ?>">
                    <input type="hidden" name="longitude" value="<?= htmlspecialchars($ti['longitude'] ?? '') ?>">
                    <input type="hidden" name="location_label" value="<?= htmlspecialchars($ti['location_label'] ?? '') ?>">
                    <input type="hidden" name="location_address" value="<?= htmlspecialchars($ti['location_address'] ?? '') ?>">
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

        <div class="lt-tab-panel w-full" data-panel="checklist" id="panel-checklist">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-slate-400"></i> チェックリスト
            </h2>
            <?php
            $myLists = [];
            try { $myLists = (new \App\LiveTrip\Model\MyListModel())->getListsForSelect(); } catch (\Throwable $e) { }
            $clTotal = count($checklistItems ?? []);
            $clChecked = (int)array_sum(array_column($checklistItems ?? [], 'checked'));
            $clPct = $clTotal > 0 ? (int)round(($clChecked / $clTotal) * 100) : 0;
            if ($clPct < 0) $clPct = 0;
            if ($clPct > 100) $clPct = 100;
            $clSetName = !empty($myLists) ? ($myLists[0]['list_name'] ?? '遠征基本セット') : '遠征基本セット';
            ?>

            <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-slate-800">持ち物チェック</p>
                        <p class="text-xs sm:text-sm text-slate-500 truncate">
                            <?= htmlspecialchars((string)$clSetName) ?> <?= (int)$clChecked ?>/<?= (int)$clTotal ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap justify-start sm:justify-end">
                        <a href="/live_trip/my_list.php?redirect=<?= urlencode('/live_trip/show.php?id=' . (int)$trip['id'] . '#checklist') ?>" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 whitespace-nowrap">マイリスト</a>
                        <button type="button" id="checklist-open-add" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap inline-flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i>追加
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="h-2 rounded-full bg-slate-200 overflow-hidden flex-1" role="progressbar" aria-valuenow="<?= (int)$clPct ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full" style="width: <?= (int)$clPct ?>%; background: var(--lt-theme);"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-500 w-10 text-right"><?= (int)$clPct ?>%</span>
                    </div>
                </div>

                <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <summary class="cursor-pointer text-sm font-bold text-slate-600">マイリストを適用 / 登録</summary>
                    <div class="mt-3 space-y-3">
                        <form method="post" action="/live_trip/apply_mylist.php" class="flex flex-wrap gap-2">
                            <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                            <input type="hidden" name="tab" value="checklist">
                            <select name="my_list_id" class="border border-slate-200 rounded px-3 py-2 text-sm flex-1 min-w-0" required>
                                <option value="">選択</option>
                                <?php foreach ($myLists as $ml): ?>
                                    <option value="<?= (int)$ml['id'] ?>"><?= htmlspecialchars($ml['list_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-3 py-2 border border-slate-200 rounded text-sm hover:bg-slate-50 shrink-0">適用</button>
                        </form>
                        <button type="button" id="mylist-register-btn" class="text-sm font-bold lt-theme-link hover:underline">
                            <i class="fa-solid fa-cloud-arrow-up mr-1"></i>マイリスト登録
                        </button>
                    </div>
                </details>

                <form id="checklist-add-form" class="mt-4 hidden gap-2">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="text" name="item_name" id="checklist-item-input" placeholder="チケット確認、財布など" class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1" required>
                    <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap">追加</button>
                </form>

                <div class="mt-4">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-bold text-slate-600">セット: <?= htmlspecialchars((string)$clSetName) ?> <span class="text-slate-400 font-bold"><?= (int)$clChecked ?>/<?= (int)$clTotal ?></span></p>
                        <span class="text-xs font-bold text-slate-500"><?= (int)$clPct ?>%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full" style="width: <?= (int)$clPct ?>%; background: var(--lt-theme);"></div>
                    </div>
                </div>

                <div id="checklist-list" class="mt-4">
                    <?php foreach ($checklistItems ?? [] as $ci): $isChecked = !empty($ci['checked']); ?>
                        <div class="lt-checklist-row lt-pack-row flex items-center gap-3 px-2 border-b border-slate-100 last:border-0" data-id="<?= (int)$ci['id'] ?>">
                            <button type="button" class="checklist-toggle text-left flex-1 flex items-center gap-3 min-w-0 py-2" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>">
                                <span class="lt-pack-checkbox<?= $isChecked ? ' is-checked' : '' ?>" aria-hidden="true">
                                    <?php if ($isChecked): ?><i class="fa-solid fa-check text-[11px]"></i><?php endif; ?>
                                </span>
                                <span class="checklist-label text-[15px] text-slate-800 truncate"><?= htmlspecialchars($ci['item_name']) ?></span>
                            </button>
                            <button type="button" class="lt-edit-btn text-slate-500 text-sm hover:text-slate-700 px-2" title="編集" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>" data-name="<?= htmlspecialchars($ci['item_name']) ?>"><i class="fa-solid fa-pen text-xs"></i></button>
                            <button type="button" class="checklist-delete text-red-500 text-sm hover:text-red-600 px-2" title="削除" data-id="<?= (int)$ci['id'] ?>" data-trip="<?= (int)$trip['id'] ?>"><i class="fa-solid fa-trash-can"></i></button>
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
                    <input type="hidden" name="place_id" id="lt-timeline-modal-place-id" value="">
                    <input type="hidden" name="latitude" id="lt-timeline-modal-lat" value="">
                    <input type="hidden" name="longitude" id="lt-timeline-modal-lng" value="">
                    <input type="hidden" name="location_address" id="lt-timeline-modal-location-address" value="">
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
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 mb-1">場所</label>
                        <input type="text" name="location_label" id="lt-timeline-modal-location-label" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" autocomplete="off" placeholder="会場、ホテル、駅、店名など">
                        <div id="lt-timeline-modal-location-suggestions" class="hidden absolute left-0 right-0 top-full mt-0.5 z-20 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
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
    window.__LT_MAP_DATA__ = <?= json_encode($ltMapData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function esc(s) { var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function rowHtml(ci) {
        var checked = parseInt(ci.checked,10)||0;
        var cb = checked ? '<span class="lt-pack-checkbox is-checked"><i class="fa-solid fa-check text-[11px]"></i></span>' : '<span class="lt-pack-checkbox"></span>';
        return '<div class="lt-checklist-row lt-pack-row flex items-center gap-3 px-2 border-b border-slate-100 last:border-0" data-id="'+ci.id+'">'+
            '<button type="button" class="checklist-toggle text-left flex-1 flex items-center gap-3 min-w-0 py-2" data-id="'+ci.id+'" data-trip="'+tripId+'">'+
            cb+
            '<span class="checklist-label text-[15px] text-slate-800 truncate">'+esc(ci.item_name||'')+'</span></button>'+
            '<button type="button" class="lt-edit-btn text-slate-500 text-sm hover:text-slate-700 px-2" title="編集" data-id="'+ci.id+'" data-trip="'+tripId+'" data-name="'+esc(ci.item_name||'')+'"><i class="fa-solid fa-pen text-xs"></i></button>'+
            '<button type="button" class="checklist-delete text-red-500 text-sm hover:text-red-600 px-2" title="削除" data-id="'+ci.id+'" data-trip="'+tripId+'"><i class="fa-solid fa-trash-can"></i></button>'+
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
                        var label = row.querySelector('.checklist-label');
                        var cb = row.querySelector('.lt-pack-checkbox');
                        if (!cb || !label) return;
                        if (data.checked) {
                            cb.classList.add('is-checked');
                            cb.innerHTML = '<i class="fa-solid fa-check text-[11px]"></i>';
                        } else {
                            cb.classList.remove('is-checked');
                            cb.innerHTML = '';
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
        var wasChecked = !!(toggle && toggle.querySelector('.lt-pack-checkbox')?.classList.contains('is-checked'));
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
                wrap.className='checklist-toggle text-left flex-1 flex items-center gap-3 min-w-0 py-2';
                wrap.dataset.id=id; wrap.dataset.trip=trip;
                var cb = wasChecked ? '<span class="lt-pack-checkbox is-checked"><i class="fa-solid fa-check text-[11px]"></i></span>' : '<span class="lt-pack-checkbox"></span>';
                wrap.innerHTML=cb+'<span class="checklist-label text-[15px] text-slate-800 truncate">'+esc(name)+'</span>';
                row.replaceChild(wrap, inp);
                bindRow(row);
                return;
            }
            fetch('/live_trip/checklist_update.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:'id='+id+'&trip_plan_id='+trip+'&item_name='+encodeURIComponent(newName) })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (data.status==='ok' && data.item) {
                        var wrap=document.createElement('button');
                        wrap.className='checklist-toggle text-left flex-1 flex items-center gap-3 min-w-0 py-2';
                        wrap.dataset.id=id; wrap.dataset.trip=trip;
                        var c=data.item.checked;
                        var cb = c ? '<span class="lt-pack-checkbox is-checked"><i class="fa-solid fa-check text-[11px]"></i></span>' : '<span class="lt-pack-checkbox"></span>';
                        wrap.innerHTML=cb+'<span class="checklist-label text-[15px] text-slate-800 truncate">'+esc(data.item.item_name||'')+'</span>';
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

    // ドラッグ並び替えは今回はスコープ外（既存APIは残す）

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

    document.getElementById('checklist-open-add')?.addEventListener('click', function() {
        var f = document.getElementById('checklist-add-form');
        var inp = document.getElementById('checklist-item-input');
        if (!f) return;
        f.classList.remove('hidden');
        f.classList.add('flex');
        inp?.focus();
    });

    var tabIds = ['summary','info','expense','hotel','destination','transport','timeline','checklist'];
    function parseHashState() {
        var rawFrag = (location.hash || '').replace(/^#/, '');
        var tab = 'summary';
        var anchorSel = '';
        if (/^transport-\d+$/.test(rawFrag)) {
            tab = 'transport';
            anchorSel = '#' + rawFrag;
        } else if (/^hotel-\d+$/.test(rawFrag)) {
            tab = 'hotel';
            anchorSel = '#' + rawFrag;
        } else if (tabIds.indexOf(rawFrag) >= 0) {
            tab = rawFrag;
        }
        return { tab: tab, anchorSel: anchorSel };
    }
    function applyHashState() {
        var state = parseHashState();
        document.querySelectorAll('.lt-tab').forEach(function(t){
            t.classList.toggle('active', t.dataset.tab === state.tab);
        });
        document.querySelectorAll('.lt-tab-panel').forEach(function(p){
            p.classList.toggle('active', p.dataset.panel === state.tab);
        });
        if (state.anchorSel) {
            setTimeout(function() {
                var el = document.querySelector(state.anchorSel);
                if (el && typeof el.scrollIntoView === 'function') {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, 50);
        }
    }
    var initialState = parseHashState();
    var defaultTab = initialState.tab;
    applyHashState();
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
    window.addEventListener('hashchange', applyHashState);

    // 費用ヒーロー：追加ボタンからクイック追加フォームへスクロール
    (function() {
        var hero = document.querySelector('#panel-expense .expense-hero');
        if (!hero) return;

        hero.querySelector('[data-action="add-expense"]')?.addEventListener('click', function(){
            var form = document.getElementById('expense-quick-add-form');
            if (!form) return;
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            var amount = form.querySelector('input[name="amount"]');
            if (amount) {
                try { amount.focus({ preventScroll: true }); } catch (e) { amount.focus(); }
            }
        });
    })();

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
        // サマリはリスト表示固定（タイムラインタブに日表示を集約）
        view = 'list';
        var root = document.querySelector('.lt-summary-timeline-calendar')?.closest('.p-4');
        if (!root) return;
        var cal = root.querySelector('.lt-summary-timeline-calendar');
        var list = root.querySelector('.lt-summary-timeline-list');
        if (cal) cal.classList.toggle('hidden', view !== 'calendar');
        if (list) list.classList.toggle('hidden', view === 'calendar');
    }
    var initialSummaryView = 'list';
    setSummaryTimelineView(initialSummaryView);
    // サマリの表示モード切替UIは撤去（ボタンも描画しない）

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
            (document.getElementById('lt-timeline-modal-location-label')).value = el.getAttribute('data-lt-location-label') || '';
            (document.getElementById('lt-timeline-modal-location-address')).value = el.getAttribute('data-lt-location-address') || '';
            (document.getElementById('lt-timeline-modal-place-id')).value = el.getAttribute('data-lt-place-id') || '';
            (document.getElementById('lt-timeline-modal-lat')).value = el.getAttribute('data-lt-lat') || '';
            (document.getElementById('lt-timeline-modal-lng')).value = el.getAttribute('data-lt-lng') || '';

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

    function attachPlacesAutocompleteForTimeline(labelInputId, placeIdHiddenId, latHiddenId, lngHiddenId, addressHiddenId, suggestionsId) {
        var labelInput = document.getElementById(labelInputId);
        var placeIdHidden = document.getElementById(placeIdHiddenId);
        var latHidden = document.getElementById(latHiddenId);
        var lngHidden = document.getElementById(lngHiddenId);
        var addressHidden = document.getElementById(addressHiddenId);
        var container = document.getElementById(suggestionsId);
        if (!labelInput || !container) return;
        var debounceTimer = null;
        labelInput.addEventListener('input', function() {
            if (placeIdHidden) placeIdHidden.value = '';
            if (latHidden) latHidden.value = '';
            if (lngHidden) lngHidden.value = '';
            if (addressHidden) addressHidden.value = (labelInput.value || '').trim();
            var q = (labelInput.value || '').trim();
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
                                labelInput.value = desc;
                                if (placeIdHidden) placeIdHidden.value = this.dataset.placeId || '';
                                if (addressHidden) addressHidden.value = desc;
                                if (latHidden) latHidden.value = '';
                                if (lngHidden) lngHidden.value = '';
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
        labelInput.addEventListener('blur', function() {
            setTimeout(function() { container.classList.add('hidden'); }, 150);
        });
        labelInput.addEventListener('focus', function() {
            if (container.children.length > 0) container.classList.remove('hidden');
        });
    }
    attachPlacesAutocomplete('transport-departure', 'transport-departure-suggestions');
    attachPlacesAutocomplete('transport-arrival', 'transport-arrival-suggestions');
    attachPlacesAutocompleteForDestination('destination-name', 'destination-address', 'destination-place-id', 'destination-name-suggestions');
    attachPlacesAutocompleteForTimeline('timeline-location', 'timeline-place-id', 'timeline-lat', 'timeline-lng', 'timeline-location-address', 'timeline-location-suggestions');
    attachPlacesAutocompleteForTimeline('lt-timeline-modal-location-label', 'lt-timeline-modal-place-id', 'lt-timeline-modal-lat', 'lt-timeline-modal-lng', 'lt-timeline-modal-location-address', 'lt-timeline-modal-location-suggestions');
    // 自宅登録（住所入力→ place_id を保持。緯度経度は保存時に place_id から補完）
    (function() {
        var input = document.getElementById('home-address-input');
        var hiddenAddress = document.getElementById('home-address-hidden');
        if (input && hiddenAddress) {
            input.addEventListener('input', function() { hiddenAddress.value = (input.value || '').trim(); });
            if (!hiddenAddress.value) hiddenAddress.value = (input.value || '').trim();
        }
        attachPlacesAutocompleteForTimeline('home-address-input', 'home-place-id', 'home-lat', 'home-lng', 'home-address-hidden', 'home-address-suggestions');
    })();
})();
</script>
<?php if ($enableSummaryMap): ?>
<script src="/assets/js/live_trip_map.js" defer></script>
<?php endif; ?>
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
