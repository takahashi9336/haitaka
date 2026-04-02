<?php
/** @var array $entries */
/** @var array $stats */
/** @var array $user */
$appKey = 'sense_lab';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sense Lab - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 relative">
    <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
        <div class="flex items-center gap-3">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
            </div>
            <h1 class="font-black text-slate-700 text-xl tracking-tighter">Sense Lab</h1>
        </div>
        <a href="/sense_lab/new.php" class="inline-flex items-center gap-2 px-4 h-10 rounded-xl text-xs font-black tracking-wider text-white shadow-md transition <?= $btnBgClass ?>"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
            <i class="fa-solid fa-plus"></i> 新規スクラップ
        </a>
    </header>

    <div class="flex-1 overflow-y-auto p-6 md:p-10">
        <div class="max-w-5xl mx-auto">
            <section class="mb-6">
                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-4 md:p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-chart-simple text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-800">集計</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">カテゴリ別の件数（軽い分析）</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-bold">
                        <span class="inline-flex items-center rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-slate-600">
                            合計 <span class="ml-1 text-slate-900 font-black"><?= htmlspecialchars((string)($stats['total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span> 件
                        </span>
                        <?php foreach (($stats['by_category'] ?? []) as $row): ?>
                            <span class="inline-flex items-center rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-slate-600">
                                <?= htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') ?>
                                <span class="ml-2 text-slate-900 font-black"><?= htmlspecialchars((string)$row['count'], ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <div class="flex items-end justify-between mb-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-700 tracking-wider">スクラップ一覧</h2>
                        <p class="text-[10px] font-bold text-slate-400 tracking-wider mt-1">「いいな」を画像＋理由でストック</p>
                    </div>
                </div>

                <?php if (!$entries): ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6 md:p-8 text-slate-400">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-note-sticky text-2xl <?= $cardDeco ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>></i>
                            <div>
                                <p class="text-sm font-bold text-slate-600">まだスクラップがありません</p>
                                <p class="text-xs mt-1 text-slate-400">「新規スクラップ」から最初の1件を登録してみましょう。</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4 md:grid-cols-2">
                        <?php foreach ($entries as $entry): ?>
                            <article class="bg-white rounded-xl shadow-sm border <?= $cardBorder ?> overflow-hidden flex flex-col">
                                <?php if (!empty($entry['image_path'])): ?>
                                    <a href="/sense_lab/show.php?id=<?= (int)$entry['id'] ?>">
                                        <img src="<?= htmlspecialchars($entry['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""
                                             class="w-full h-44 object-cover">
                                    </a>
                                <?php endif; ?>
                                <div class="p-4 flex-1 flex flex-col">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 text-[10px] font-black tracking-wider px-2.5 py-1">
                                            <?= htmlspecialchars($entry['category'] ?? 'other', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 tracking-wider">
                                            <?= htmlspecialchars(substr((string)$entry['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <h3 class="font-black text-slate-800 text-sm mb-2">
                                        <a href="/sense_lab/show.php?id=<?= (int)$entry['id'] ?>" class="hover:underline">
                                            <?= htmlspecialchars($entry['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </h3>
                                    <ul class="text-xs text-slate-600 space-y-1 mb-4">
                                        <?php if (!empty($entry['reason_1'])): ?><li>・<?= htmlspecialchars($entry['reason_1'], ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
                                        <?php if (!empty($entry['reason_2'])): ?><li>・<?= htmlspecialchars($entry['reason_2'], ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
                                        <?php if (!empty($entry['reason_3'])): ?><li>・<?= htmlspecialchars($entry['reason_3'], ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
                                    </ul>
                                    <div class="mt-auto flex items-center justify-end gap-3 text-[10px] font-black tracking-wider">
                                        <a href="/sense_lab/edit.php?id=<?= (int)$entry['id'] ?>" class="text-slate-500 hover:text-slate-800 transition">編集</a>
                                        <form action="/sense_lab/delete.php" method="post" onsubmit="return confirm('削除してよろしいですか？');">
                                            <input type="hidden" name="id" value="<?= (int)$entry['id'] ?>">
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 transition">削除</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-700 tracking-wider">クイックスクラップ</h2>
                        <p class="text-[10px] font-bold text-slate-400 tracking-wider mt-1">どこからでもメモした「センスのタネ」</p>
                    </div>
                    <p class="text-[10px] text-slate-400 font-bold">
                        <?= isset($quickEntries) ? count($quickEntries) : 0 ?> 件
                    </p>
                </div>
                <?php $quickList = is_array($quickEntries ?? null) ? $quickEntries : []; ?>
                <?php if (empty($quickList)): ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-4 text-xs text-slate-400">
                        まだクイックスクラップはありません。右下の <span class="<?= $cardDeco ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>><i class="fa-solid fa-wand-magic-sparkles"></i></span> ボタンから、今見ているページの「いいな」を1行だけ残してみてください。
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm divide-y divide-slate-100">
                        <?php foreach ($quickList as $q): ?>
                            <article class="p-3 md:p-4 text-xs text-slate-700">
                                <div class="flex items-center justify-between mb-1 gap-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <?php if (!empty($q['category_hint'])): ?>
                                            <span class="inline-flex items-center rounded-full bg-violet-50 text-violet-600 px-2 py-0.5 text-[10px] font-bold">
                                                <?= htmlspecialchars($q['category_hint'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($q['app_key'])): ?>
                                            <span class="text-[10px] text-slate-400 font-bold truncate">
                                                <?= htmlspecialchars($q['app_key'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-bold shrink-0">
                                        <?= htmlspecialchars(substr((string)$q['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <?php if (!empty($q['page_title'])): ?>
                                    <p class="text-[11px] font-bold text-slate-700 mb-1 truncate">
                                        <?= htmlspecialchars($q['page_title'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex gap-3">
                                    <?php if (!empty($q['image_path'])): ?>
                                        <a href="/sense_lab/quick_edit.php?id=<?= (int)$q['id'] ?>" class="shrink-0">
                                            <img src="<?= htmlspecialchars($q['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""
                                                 class="w-16 h-16 rounded-lg object-cover border border-slate-200 bg-slate-50">
                                        </a>
                                    <?php endif; ?>
                                    <div class="min-w-0 flex-1">
                                        <p class="whitespace-pre-wrap leading-relaxed"><?= nl2br(htmlspecialchars($q['note'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        <div class="mt-2 flex items-center justify-end gap-3 text-[10px] font-black tracking-wider">
                                            <a href="/sense_lab/quick_edit.php?id=<?= (int)$q['id'] ?>" class="text-slate-500 hover:text-slate-800 transition">編集</a>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($q['source_url'])): ?>
                                    <p class="mt-1 text-[10px] text-slate-400 truncate">
                                        <?= htmlspecialchars($q['source_url'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<script src="/assets/js/core.js?v=2"></script>
<script>
    const btn = document.getElementById('mobileMenuBtn');
    if (btn) btn.onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
</script>
</body>
</html>

