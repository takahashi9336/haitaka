<?php
/**
 * ミーグリ予定：1日分のアコーディオンカード
 * 前提: $date, $slots, $isOpen, $today, $dayNames, $reportCounts
 * 任意: $meetGreetOshiMemberIds int[]（推し部バッジ用）
 */
require_once __DIR__ . '/../inc/meetgreet_member_pill_style.inc.php';

$dt = new DateTime($date);
$dow = $dayNames[(int)$dt->format('w')];
$dateLabel = $dt->format('Y年n月j日') . "（{$dow}）";
$isPast = $date < $today;
$totalTickets = 0;
$linkedEventName = null;
$linkedCategory = null;
$oshiMemberSet = array_fill_keys($meetGreetOshiMemberIds ?? [], true);
$oshiSlotCount = 0;
foreach ($slots as $s) {
    $totalTickets += (int)($s['ticket_count'] ?? 0);
    if (!empty($s['linked_event_name'])) {
        $linkedEventName = $s['linked_event_name'];
    }
    if ($linkedCategory === null && isset($s['linked_event_category']) && $s['linked_event_category'] !== null && $s['linked_event_category'] !== '') {
        $linkedCategory = (int)$s['linked_event_category'];
    }
    $mid = (int)($s['member_id'] ?? 0);
    if ($mid > 0 && isset($oshiMemberSet[$mid])) {
        $oshiSlotCount++;
    }
}

$daysUntilCard = null;
if (!$isPast) {
    $t0 = new DateTime($today);
    $t1 = new DateTime($date);
    $daysUntilCard = (int)floor(($t1->getTimestamp() - $t0->getTimestamp()) / 86400);
    if ($daysUntilCard < 0) {
        $daysUntilCard = 0;
    }
}

$eventTypeBadge = null;
if ($linkedCategory === 3) {
    $eventTypeBadge = ['label' => 'リアルミーグリ', 'icon' => 'fa-users', 'class' => 'bg-amber-50 text-amber-800 border border-amber-200'];
} elseif ($linkedCategory === 2) {
    $eventTypeBadge = ['label' => 'オンラインミーグリ', 'icon' => 'fa-video', 'class' => 'bg-sky-50 text-sky-700 border border-sky-200'];
} elseif ($linkedEventName) {
    $eventTypeBadge = ['label' => 'ミーグリ', 'icon' => 'fa-ticket', 'class' => 'bg-slate-50 text-slate-700 border border-slate-200'];
}
?>
                    <div class="bg-white border border-slate-200 border-l-4 border-l-sky-500 rounded-xl overflow-hidden shadow-sm" data-date-group="<?= htmlspecialchars($date) ?>" data-mg-date="<?= htmlspecialchars($date) ?>">
                        <div class="accordion-trigger grid grid-cols-[minmax(0,1fr)_auto] grid-rows-[auto_auto] gap-x-2 gap-y-3 px-5 py-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:grid-rows-1 sm:items-start sm:gap-x-4 <?= $isPast ? 'opacity-60' : '' ?>"
                             onclick="MG.toggleDate('<?= htmlspecialchars($date) ?>')">
                            <div class="col-start-1 row-start-1 min-w-0 flex flex-col gap-0.5 self-start">
                                <span class="text-base font-bold text-slate-800 leading-snug break-words"><?= htmlspecialchars($dateLabel) ?></span>
                                <?php if (!$isPast && $daysUntilCard !== null): ?>
                                    <span class="text-xs font-bold text-slate-500">あと <?= (int)$daysUntilCard ?> 日</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-start-2 row-start-1 flex items-center justify-end gap-2 self-start shrink-0 sm:col-start-3 sm:row-start-1">
                                <?php if ($eventTypeBadge): ?>
                                    <span class="inline-flex max-w-[9.5rem] items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold leading-tight sm:max-w-[11rem] sm:px-2.5 sm:py-1 sm:text-xs <?= htmlspecialchars($eventTypeBadge['class']) ?>">
                                        <i class="fa-solid <?= htmlspecialchars($eventTypeBadge['icon']) ?> shrink-0 text-[10px] sm:text-[11px]"></i>
                                        <span class="min-w-0 break-words text-left"><?= htmlspecialchars($eventTypeBadge['label']) ?></span>
                                    </span>
                                <?php endif; ?>
                                <i id="icon-<?= htmlspecialchars($date) ?>" class="fa-solid fa-chevron-down shrink-0 text-slate-300 text-sm transition-transform duration-300 <?= $isOpen ? 'rotate-180' : '' ?>"></i>
                            </div>
                            <div class="col-span-2 row-start-2 flex min-w-0 flex-wrap items-center gap-2 sm:col-span-1 sm:col-start-2 sm:row-start-1 sm:self-center">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-800 whitespace-nowrap">
                                    <i class="fa-solid fa-list text-[11px] opacity-90"></i><?= count($slots) ?>部
                                </span>
                                <?php if ($totalTickets > 0): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 whitespace-nowrap">
                                        <i class="fa-solid fa-ticket text-[11px] opacity-80"></i><?= $totalTickets ?>枚
                                    </span>
                                <?php endif; ?>
                                <?php if ($oshiSlotCount > 0): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 whitespace-nowrap">
                                        <i class="fa-solid fa-heart text-[11px] opacity-90"></i>推し<?= (int)$oshiSlotCount ?>部
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="content-<?= htmlspecialchars($date) ?>" class="border-t border-slate-100 <?= $isOpen ? '' : 'hidden' ?>">
                            <?php if (!$linkedEventName): ?>
                            <div class="px-5 py-2.5 bg-amber-50 border-b border-amber-100 flex items-center gap-2" id="link-bar-<?= htmlspecialchars($date) ?>">
                                <button type="button" onclick="MG.showLinkSelect('<?= htmlspecialchars($date) ?>')" class="text-xs font-bold text-amber-600 hover:text-amber-800 transition" id="link-btn-<?= htmlspecialchars($date) ?>">
                                    <i class="fa-solid fa-link mr-1"></i>イベントを紐づける
                                </button>
                                <div id="link-select-wrap-<?= htmlspecialchars($date) ?>" class="hidden flex-1 flex items-center gap-2">
                                    <select id="link-select-<?= htmlspecialchars($date) ?>" class="flex-1 text-sm border border-amber-300 rounded px-2 py-1 bg-white">
                                        <option value="">-- イベント選択 --</option>
                                    </select>
                                    <button type="button" onclick="MG.submitLink('<?= htmlspecialchars($date) ?>')" class="text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 rounded px-3 py-1 transition">紐づけ</button>
                                    <button type="button" onclick="MG.cancelLink('<?= htmlspecialchars($date) ?>')" class="text-xs text-slate-400 hover:text-slate-600 transition">キャンセル</button>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="grid grid-cols-12 gap-2 px-5 py-2.5 bg-slate-50 text-xs font-bold text-slate-400 tracking-wider">
                                <div class="col-span-3">時間帯</div>
                                <div class="col-span-4">メンバー</div>
                                <div class="col-span-2 text-center">枚数</div>
                                <div class="col-span-3 text-right">操作</div>
                            </div>

                            <?php foreach ($slots as $slot): ?>
                            <div class="slot-row border-t border-slate-50" data-slot-id="<?= (int)$slot['id'] ?>">
                                <div class="grid grid-cols-12 gap-2 px-5 py-3 items-center">
                                    <div class="col-span-3">
                                        <div class="text-sm font-bold text-slate-800"><?= htmlspecialchars($slot['slot_name']) ?></div>
                                        <?php if ($slot['start_time'] && $slot['end_time']): ?>
                                            <div class="text-xs text-slate-500 mt-0.5"><?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-span-4">
                                        <?php
                                        $displayName = $slot['member_name'] ?? $slot['member_name_raw'] ?? '不明';
                                        $nameColor = hinata_meetgreet_member_text_color(
                                            isset($slot['color1']) && (string)$slot['color1'] !== '' ? (string)$slot['color1'] : null
                                        );
                                        $midSlot = (int)($slot['member_id'] ?? 0);
                                        $isOshiRow = $midSlot > 0 && isset($oshiMemberSet[$midSlot]);
                                        ?>
                                        <div class="inline-flex min-w-0 max-w-full flex-wrap items-center gap-2">
                                            <span class="text-base font-bold leading-snug" style="color: <?= htmlspecialchars($nameColor, ENT_QUOTES, 'UTF-8') ?>;"><?= htmlspecialchars($displayName) ?></span>
                                            <?php if ($isOshiRow): ?>
                                                <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-black bg-pink-100 text-pink-700">推し</span>
                                            <?php endif; ?>
                                            <?php if (!$slot['member_id'] && $slot['member_name_raw']): ?>
                                                <span class="text-xs text-amber-600 font-bold">未マッチ</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-span-2 text-center">
                                        <span class="text-base font-black text-slate-800 tabular-nums"><?= (int)($slot['ticket_count'] ?? 0) ?></span><span class="text-sm font-bold text-slate-500 ml-1">枚</span>
                                    </div>
                                    <div class="col-span-3 flex items-center justify-end gap-2">
                                        <?php
                                            $rc = $reportCounts[(int)$slot['id']] ?? 0;
                                            $hasMemo = !empty($slot['report']);
                                            $hasAny = $rc > 0 || $hasMemo;
                                            $label = '';
                                            if ($rc > 0 && $hasMemo) {
                                                $label = "レポ {$rc}件+メモ";
                                            } elseif ($rc > 0) {
                                                $label = "レポ {$rc}件";
                                            } elseif ($hasMemo) {
                                                $label = 'メモあり';
                                            } else {
                                                $label = 'レポを書く';
                                            }
                                        ?>
                                        <a href="/hinata/meetgreet_report.php?slot_id=<?= (int)$slot['id'] ?>" class="text-xs font-bold px-2.5 py-1.5 rounded-md transition <?= $hasAny ? 'text-white' : 'text-slate-500 bg-slate-100 hover:bg-slate-200' ?>"
                                           <?= $hasAny ? 'style="background: var(--mg-theme);"' : '' ?>>
                                            <i class="fa-solid fa-<?= $hasAny ? 'pen-to-square' : 'comments' ?> mr-0.5"></i><?= $label ?>
                                        </a>
                                        <button type="button" onclick="MG.deleteSlot(<?= (int)$slot['id'] ?>)" class="text-slate-300 hover:text-red-400 p-1 transition" title="削除">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="px-5 py-2.5 border-t border-slate-50 flex justify-end">
                                <button type="button" onclick="MG.deleteDate('<?= htmlspecialchars($date) ?>')" class="text-xs text-slate-400 hover:text-red-400 font-bold transition">
                                    <i class="fa-solid fa-trash-can mr-1"></i>この日の予定を全て削除
                                </button>
                            </div>
                        </div>
                    </div>
