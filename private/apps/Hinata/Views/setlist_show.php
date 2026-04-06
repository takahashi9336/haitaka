<?php
/**
 * LIVEセットリスト表示 View
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$eventId = (int)($event['id'] ?? 0);
$isAdmin = in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true);
$songCount = 0;
foreach ($setlist as $it) {
    $t = $it['entry_type'] ?? 'song';
    if ($t === 'song') $songCount++;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セットリスト - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>
<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <header class="h-14 bg-white border-b border-slate-100 flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
        <div class="flex items-center gap-2 min-w-0">
            <a href="/hinata/events.php" class="text-slate-400 hover:text-slate-600 p-2"><i class="fa-solid fa-chevron-left"></i></a>
            <div class="min-w-0">
                <div class="text-[10px] font-black text-slate-400 tracking-wider truncate">セットリスト</div>
                <div class="text-sm font-black text-slate-800 truncate"><?= htmlspecialchars($event['event_name'] ?? 'イベント') ?></div>
            </div>
        </div>
        <div class="shrink-0 flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400"><?= (int)$songCount ?>曲</span>
            <?php if ($isAdmin): ?>
                <a href="/hinata/setlist_edit.php?event_id=<?= $eventId ?>" class="text-xs font-bold text-white px-3 py-2 rounded-lg hover:opacity-90 transition" style="background:var(--hinata-theme)">
                    <i class="fa-solid fa-pen mr-1"></i>編集
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-3xl mx-auto space-y-4">
            <p class="text-xs text-slate-500">
                <i class="fa-solid fa-calendar mr-1 text-slate-300"></i><?= htmlspecialchars($event['event_date'] ?? '') ?>
                <?php if (!empty($event['event_place'])): ?>
                    <span class="ml-2"><i class="fa-solid fa-location-dot mr-1 text-slate-300"></i><?= htmlspecialchars($event['event_place']) ?></span>
                <?php endif; ?>
            </p>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-indigo-50 flex items-center gap-2">
            <i class="fa-solid fa-list-ol text-indigo-500 text-sm"></i>
            <span class="text-xs font-black text-indigo-700 tracking-wider">セットリスト</span>
        </div>
        <?php if (empty($setlist)): ?>
            <div class="p-4 text-sm text-slate-500">未登録です。</div>
        <?php else: ?>
            <ol class="divide-y divide-slate-50">
                <?php $lastEncore = false; $i = 0; ?>
                <?php foreach ($setlist as $row): $i++; ?>
                    <?php $t = $row['entry_type'] ?? 'song'; $isSong = ($t === 'song'); ?>
                    <?php if ($isSong && !empty($row['encore']) && !$lastEncore): ?>
                        <li class="px-4 py-2 bg-slate-50 text-center">
                            <span class="text-[10px] font-black text-slate-400 tracking-wider">ENCORE</span>
                        </li>
                    <?php endif; ?>
                    <?php if ($isSong) $lastEncore = !empty($row['encore']); ?>

                    <li class="px-4 py-3 flex items-center gap-3">
                        <span class="text-xs text-slate-400 w-6 text-right font-mono"><?= $i ?></span>
                        <?php if ($isSong): ?>
                            <a href="/hinata/song.php?id=<?= (int)$row['song_id'] ?>&from=setlist&event_id=<?= $eventId ?>" class="flex-1 min-w-0 font-bold text-slate-800 hover:text-sky-600 transition truncate">
                                <?= htmlspecialchars($row['song_title'] ?? '') ?>
                            </a>
                            <?php
                                $centerNames = [];
                                if (!empty($row['center_members']) && is_array($row['center_members'])) {
                                    foreach ($row['center_members'] as $cm) {
                                        if (!empty($cm['name'])) $centerNames[] = (string)$cm['name'];
                                    }
                                } elseif (!empty($row['center_member_name'])) {
                                    $centerNames[] = (string)$row['center_member_name'];
                                }
                            ?>
                            <?php if (!empty($centerNames)): ?>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full shrink-0">
                                    C:<?= htmlspecialchars(implode('、', $centerNames)) ?>
                                </span>
                            <?php endif; ?>
                            <span class="text-[10px] text-slate-400 shrink-0 truncate max-w-[9rem]"><?= htmlspecialchars($row['release_title'] ?? '') ?></span>
                        <?php else: ?>
                            <?php
                                $label = trim((string)($row['label'] ?? ''));
                                $kind = trim((string)($row['block_kind'] ?? ''));
                                $kindText = ($t === 'mc') ? 'MC' : ($kind !== '' ? $kind : 'BLOCK');
                                if ($label === '') $label = $kindText;
                            ?>
                            <span class="flex-1 min-w-0 font-bold text-slate-700 truncate"><?= htmlspecialchars($label) ?></span>
                            <span class="text-[10px] text-slate-400 shrink-0"><?= htmlspecialchars($kindText) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 flex items-center gap-2">
            <i class="fa-solid fa-microphone-lines text-slate-500 text-sm"></i>
            <span class="text-xs font-black text-slate-600 tracking-wider">影ナレ</span>
        </div>
        <div class="p-4 text-sm text-slate-700">
            <?php if (!empty($shadowMembers)): ?>
                <?= htmlspecialchars(implode('、', array_map(fn($m) => $m['name'], $shadowMembers))) ?>
            <?php else: ?>
                <span class="text-slate-400">未登録</span>
            <?php endif; ?>
            <?php if (!empty($shadow['memo'])): ?>
                <div class="text-xs text-slate-500 mt-2 whitespace-pre-wrap"><?= htmlspecialchars($shadow['memo']) ?></div>
            <?php endif; ?>
        </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

