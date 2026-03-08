<?php
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
        $summaryCheckTotal = count($checklistItems ?? []);
        $summaryCheckChecked = array_sum(array_column($checklistItems ?? [], 'checked'));
        $summaryNextActions = [];
        if (empty($hotelStays)) $summaryNextActions[] = ['label' => '宿泊を追加', 'tab' => 'hotel'];
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
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-1">イベント</p>
                    <p class="font-bold"><?= htmlspecialchars($trip['event_date'] ?? '') ?></p>
                    <?php if ($eventPlace): ?>
                    <a href="<?= htmlspecialchars($eventPlaceForMaps) ?>" target="_blank" rel="noopener" class="text-sky-600 font-medium text-sm mt-1 inline-flex items-center gap-1"><?= htmlspecialchars($eventPlace) ?> <i class="fa-solid fa-external-link text-xs"></i></a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($hotelStays)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">宿泊</p>
                    <?php foreach ($hotelStays as $h):
                        $mapsUrl = (new \App\LiveTrip\Model\HotelStayModel())->getGoogleMapsUrl($h);
                    ?>
                    <div class="mb-3 last:mb-0">
                        <p class="font-bold"><?= htmlspecialchars($h['hotel_name']) ?></p>
                        <?php if ($mapsUrl !== '#'): ?><a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="text-sky-600 text-sm">地図で開く</a><?php endif; ?>
                        <?php if ($h['reservation_no']): ?><p class="text-sm">予約番号: <?= htmlspecialchars($h['reservation_no']) ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($transportLegs)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">移動</p>
                    <?php foreach ($transportLegs as $t): ?>
                    <div class="mb-2">
                        <p class="text-xs text-slate-500"><?= htmlspecialchars(\App\LiveTrip\Model\TransportLegModel::$directions[$t['direction']] ?? $t['direction']) ?></p>
                        <p><?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?></p>
                        <?php if ($t['departure'] || $t['arrival']): ?><p class="text-sm text-slate-600"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?><?= $t['duration_min'] ? ' ('.$t['duration_min'].'分)' : '' ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($mergedTimeline)): ?>
                <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <p class="text-xs font-bold text-slate-500 mb-3">タイムライン</p>
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
                            <?php else: $t = $m['data']; $dir = \App\LiveTrip\Model\TransportLegModel::$directions[$t['direction']] ?? $t['direction']; ?>
                            <p class="font-medium"><i class="fa-solid fa-train text-slate-400 mr-1"></i><?= htmlspecialchars($dir) ?>: <?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?></p>
                            <?php if ($t['departure'] || $t['arrival']): ?><p class="text-sm text-slate-500"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?></p><?php endif; ?>
                            <?php endif; ?>
                        </div>
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
                <?php if (empty($hotelStays) && empty($transportLegs) && empty($mergedTimeline) && empty($checklistItems)): ?>
                <p class="text-slate-500 text-sm">宿泊・移動・タイムライン・チェックリストを登録すると、ここに表示されます。各タブから追加してください。</p>
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
                                <?= htmlspecialchars(\App\LiveTrip\Model\TransportLegModel::$directions[$tl['direction']] ?? $tl['direction']) ?>: <?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?>
                                <?php if ($tl['departure'] || $tl['arrival']): ?><span class="text-slate-500">(<?= htmlspecialchars($tl['departure'] ?? '') ?>→<?= htmlspecialchars($tl['arrival'] ?? '') ?>)</span><?php endif; ?>
                            </span>
                            <span class="font-medium">¥<?= number_format((int)$tl['amount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <p class="pt-2 font-bold text-slate-700">小計: ¥<?= number_format($totalTransport) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($totalExpense > 0 || $totalTransport > 0): ?>
                <p class="pt-2 font-bold text-slate-700 border-t border-slate-200 mt-2">合計: ¥<?= number_format($totalExpense + $totalTransport) ?></p>
                <?php endif; ?>
                <?php if ($totalExpense === 0 && $totalTransport === 0): ?>
                <p class="text-slate-500 text-sm">費用タブまたは移動タブから追加してください。</p>
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
                <div class="hotel-item p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div class="hotel-view">
                        <div class="flex justify-between">
                            <h3 class="font-bold">
                                <?php if ($mapsUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener" class="text-sky-600 hover:underline"><?= htmlspecialchars($h['hotel_name']) ?> <i class="fa-solid fa-external-link text-xs"></i></a>
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

        <div class="lt-tab-panel max-w-4xl" data-panel="transport" id="panel-transport">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-train text-slate-400"></i> 移動
            </h2>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <p class="text-sm text-slate-600 mb-3">移動を追加する際に交通費も登録できます。費用タブで集計を確認できます。</p>
                <form method="post" action="/live_trip/transport_store.php" class="space-y-3">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="transport">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">往路/復路</label>
                        <select name="direction" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                            <option value="outbound">往路</option>
                            <option value="return">復路</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">出発日</label>
                        <input type="date" name="departure_date" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="未入力=往路前日/復路翌日" title="未入力時は往路=前日・復路=翌日">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">出発時刻</label>
                        <input type="text" name="scheduled_time" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="08:00（タイムラインに表示）">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">交通機関</label>
                        <input type="text" name="transport_type" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="新幹線、在来線など">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">経路メモ</label>
                    <input type="text" name="route_memo" class="w-full border border-slate-200 rounded px-3 py-2 text-sm" placeholder="東京→名古屋 のぞみ、名古屋→会場 在来線">
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">発</label>
                        <input type="text" name="departure" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="発着駅・空港">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">着</label>
                        <input type="text" name="arrival" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">所要時間(分)</label>
                        <input type="number" name="duration_min" class="w-full border border-slate-200 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">交通費(円)</label>
                        <input type="number" name="transport_amount" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="費用も登録">
                    </div>
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
                            <span class="text-xs font-bold text-slate-500"><?= htmlspecialchars(\App\LiveTrip\Model\TransportLegModel::$directions[$t['direction']] ?? $t['direction']) ?></span>
                            <p class="text-slate-700"><?= htmlspecialchars($t['transport_type'] ?? '') ?> <?= htmlspecialchars($t['route_memo'] ?? '') ?><?= !empty($t['amount']) ? ' ¥' . number_format($t['amount']) : '' ?></p>
                            <?php if ($t['departure'] || $t['arrival']): ?>
                            <p class="text-sm text-slate-500"><?= htmlspecialchars($t['departure']) ?> → <?= htmlspecialchars($t['arrival']) ?><?= $t['duration_min'] ? ' (' . $t['duration_min'] . '分)' : '' ?></p>
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
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">往路/復路</label><select name="direction" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"><option value="outbound" <?= ($t['direction'] ?? '') === 'outbound' ? 'selected' : '' ?>>往路</option><option value="return" <?= ($t['direction'] ?? '') === 'return' ? 'selected' : '' ?>>復路</option></select></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">出発日</label><input type="date" name="departure_date" value="<?= htmlspecialchars($t['departure_date'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">出発時刻</label><input type="text" name="scheduled_time" value="<?= htmlspecialchars($t['scheduled_time'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="08:00"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">交通機関</label><input type="text" name="transport_type" value="<?= htmlspecialchars($t['transport_type'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-1">経路メモ</label><input type="text" name="route_memo" value="<?= htmlspecialchars($t['route_memo'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                        <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">発</label><input type="text" name="departure" value="<?= htmlspecialchars($t['departure'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">着</label><input type="text" name="arrival" value="<?= htmlspecialchars($t['arrival'] ?? '') ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">所要時間(分)</label><input type="number" name="duration_min" value="<?= (int)($t['duration_min'] ?? 0) ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">交通費(円)</label><input type="number" name="amount" value="<?= (int)($t['amount'] ?? 0) ?>" class="w-full border border-slate-200 rounded px-2 py-1 text-sm" placeholder="0"></div>
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
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-clock text-slate-400"></i> タイムライン
            </h2>
            <form method="post" action="/live_trip/timeline_store.php" class="flex flex-wrap gap-2 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="hidden" name="tab" value="timeline">
                <div><label class="block text-xs font-bold text-slate-500 mb-1">日付</label><input type="date" name="scheduled_date" class="border border-slate-200 rounded px-3 py-2 text-sm w-36" title="未入力時は当日"></div>
                <input type="text" name="label" placeholder="開場、開演など" class="border border-slate-200 rounded px-3 py-2 text-sm w-32" required>
                <input type="text" name="scheduled_time" placeholder="18:00" class="border border-slate-200 rounded px-3 py-2 text-sm w-20">
                <input type="text" name="memo" placeholder="メモ" class="border border-slate-200 rounded px-3 py-2 text-sm flex-1 min-w-24">
                <button type="submit" class="lt-theme-btn text-white px-3 py-2 rounded text-sm">追加</button>
            </form>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
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
                        <?php else: $tl = $m['data']; $dir = \App\LiveTrip\Model\TransportLegModel::$directions[$tl['direction']] ?? $tl['direction']; ?>
                        <i class="fa-solid fa-train text-slate-400 mr-1"></i><?= htmlspecialchars($dir) ?>: <?= htmlspecialchars($tl['transport_type'] ?? '') ?> <?= htmlspecialchars($tl['route_memo'] ?? '') ?><?= $tl['departure'] || $tl['arrival'] ? ' (' . htmlspecialchars($tl['departure'] ?? '') . '→' . htmlspecialchars($tl['arrival'] ?? '') . ')' : '' ?>
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
                    <input type="text" name="scheduled_time" value="<?= htmlspecialchars($ti['scheduled_time'] ?? '') ?>" placeholder="18:00" class="border border-slate-200 rounded px-2 py-1 text-sm w-20">
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
                    <input type="hidden" name="direction" value="<?= htmlspecialchars($tl['direction'] ?? 'outbound') ?>">
                    <input type="hidden" name="transport_type" value="<?= htmlspecialchars($tl['transport_type'] ?? '') ?>">
                    <input type="hidden" name="route_memo" value="<?= htmlspecialchars($tl['route_memo'] ?? '') ?>">
                    <input type="hidden" name="departure" value="<?= htmlspecialchars($tl['departure'] ?? '') ?>">
                    <input type="hidden" name="arrival" value="<?= htmlspecialchars($tl['arrival'] ?? '') ?>">
                    <input type="hidden" name="duration_min" value="<?= (int)($tl['duration_min'] ?? 0) ?>">
                    <input type="hidden" name="amount" value="<?= (int)($tl['amount'] ?? 0) ?>">
                    <label class="text-sm font-bold text-slate-600">出発時刻:</label>
                    <input type="text" name="scheduled_time" value="<?= htmlspecialchars($tl['scheduled_time'] ?? '') ?>" placeholder="08:00" class="border border-slate-200 rounded px-2 py-1 text-sm w-20">
                    <button type="submit" class="lt-theme-btn text-white px-3 py-1 rounded text-sm">保存</button>
                    <button type="button" class="transport-timeline-cancel-btn px-3 py-1 border border-slate-200 rounded text-sm">キャンセル</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        </div>

        <div class="lt-tab-panel max-w-4xl" data-panel="checklist" id="panel-checklist">
        <div class="space-y-4">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-slate-400"></i> チェックリスト
            </h2>
            <form id="checklist-add-form" class="flex gap-2 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                <input type="text" name="item_name" id="checklist-item-input" placeholder="チケット確認、財布など" class="border border-slate-200 rounded px-3 py-2 text-sm flex-1" required>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded text-sm">追加</button>
            </form>
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <p class="text-xs font-bold text-slate-500 mb-2">マイリストから追加</p>
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
            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                <p class="text-xs font-bold text-slate-500 mb-2">チェックリストをマイリストに登録</p>
                <p class="text-xs text-slate-500 mb-2">画面で追加した項目をマイリストテンプレートとして保存</p>
                <form method="post" action="/live_trip/save_checklist_to_mylist.php" class="space-y-2">
                    <input type="hidden" name="trip_plan_id" value="<?= (int)$trip['id'] ?>">
                    <input type="hidden" name="tab" value="checklist">
                    <div class="flex flex-wrap gap-2 items-end">
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs text-slate-500 mb-0.5">新規リスト名</label>
                            <input type="text" name="list_name" placeholder="例: 遠征基本セット" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                        </div>
                        <button type="submit" name="action" value="new" class="lt-theme-btn text-white px-3 py-2 rounded text-sm font-bold shrink-0">新規作成</button>
                    </div>
                    <?php if (!empty($myLists)): ?>
                    <div class="flex flex-wrap gap-2 items-end pt-1 border-t border-emerald-200">
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs text-slate-500 mb-0.5">既存リストに追加</label>
                            <select name="my_list_id" class="w-full border border-slate-200 rounded px-3 py-2 text-sm">
                                <option value="">選択</option>
                                <?php foreach ($myLists as $ml): ?>
                                <option value="<?= (int)$ml['id'] ?>"><?= htmlspecialchars($ml['list_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="action" value="add" class="px-3 py-2 border border-slate-200 rounded text-sm hover:bg-slate-50 shrink-0">追加</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <p class="text-xs text-slate-500"><a href="/live_trip/my_list.php" class="lt-theme-link hover:underline">持ち物マイリストを管理</a></p>
            <div id="checklist-list" class="lt-checklist-sortable p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
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
</main>
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
        if (!confirm('削除しますか？')) return;
        var row = document.querySelector('.lt-checklist-row[data-id="'+id+'"]');
        fetch('/live_trip/checklist_delete.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:'id='+id+'&trip_plan_id='+trip })
            .then(function(r){ return r.json(); })
            .then(function(data){ if (data.status==='ok' && row) row.remove(); });
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
    var defaultTab = ['summary','info','expense','hotel','transport','timeline','checklist'].indexOf(hash)>=0 ? hash : 'summary';
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

    document.addEventListener('click', function(e) {
        var editBtn = e.target.closest('.expense-edit-btn, .hotel-edit-btn, .transport-edit-btn, .timeline-edit-btn, .transport-timeline-edit-btn');
        if (editBtn) {
            var item = editBtn.closest('.expense-item, .hotel-item, .transport-item, .timeline-row');
            if (!item) return;
            var view = item.querySelector('.expense-view, .hotel-view, .transport-view, .timeline-view');
            var form = item.querySelector('.expense-edit-form, .hotel-edit-form, .transport-edit-form, .timeline-edit-form, .transport-timeline-edit-form');
            if (view) view.style.display = 'none';
            if (form) { form.classList.remove('hidden'); }
        }
        var cancelBtn = e.target.closest('.expense-cancel-btn, .hotel-cancel-btn, .transport-cancel-btn, .timeline-cancel-btn, .transport-timeline-cancel-btn');
        if (cancelBtn) {
            var form = cancelBtn.closest('.edit-form');
            if (!form) return;
            var item = form.closest('.expense-item, .hotel-item, .transport-item, .timeline-row');
            if (!item) return;
            var view = item.querySelector('.expense-view, .hotel-view, .transport-view, .timeline-view');
            if (view) view.style.display = '';
            form.classList.add('hidden');
        }
    });
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
