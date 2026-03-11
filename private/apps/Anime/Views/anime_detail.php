<?php
$appKey = 'dashboard';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$imageUrl = $work['images']['recommended_url'] ?? $work['images']['facebook']['og_image_url'] ?? null;
$statusKind = $work['status']['kind'] ?? '';
$statusLabels = ['wanna_watch' => '見たい', 'watching' => '見てる', 'watched' => '見た', 'on_hold' => '中断', 'stop_watching' => '中止'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($work['title'] ?? '作品詳細') ?> - アニメ - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-16 bg-white border-b border-slate-100 flex items-center px-6 shrink-0">
            <div class="flex items-center gap-3">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
            <a href="/anime/" class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-sky-500 text-white shadow">
                    <i class="fa-solid fa-tv text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl">アニメ</h1>
            </a>
            </div>
        </header>

        <div class="flex-1 px-3 py-4 sm:px-4 md:p-8">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="shrink-0 w-full md:w-48">
                        <?php if ($imageUrl): ?>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="w-full rounded-xl shadow-lg aspect-[2/3] object-cover">
                        <?php else: ?>
                        <div class="w-full aspect-[2/3] rounded-xl bg-slate-200 flex items-center justify-center">
                            <i class="fa-solid fa-tv text-5xl text-slate-400"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-black text-slate-800 mb-2"><?= htmlspecialchars($work['title'] ?? '') ?></h2>
                        <?php if (!empty($work['title_kana'])): ?>
                        <p class="text-slate-500 text-sm mb-4"><?= htmlspecialchars($work['title_kana']) ?></p>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php if (!empty($work['media_text'])): ?>
                            <span class="px-2 py-1 rounded bg-sky-100 text-sky-700 text-xs font-bold"><?= htmlspecialchars($work['media_text']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($work['season_name_text'])): ?>
                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs font-bold"><?= htmlspecialchars($work['season_name_text']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($work['episodes_count'])): ?>
                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-xs"><?= $work['episodes_count'] ?>話</span>
                            <?php endif; ?>
                            <?php if ($statusKind && isset($statusLabels[$statusKind])): ?>
                            <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs font-bold"><?= htmlspecialchars($statusLabels[$statusKind]) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($work['official_site_url'])): ?>
                        <a href="<?= htmlspecialchars($work['official_site_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sky-600 text-sm font-bold hover:underline">
                            <i class="fa-solid fa-external-link"></i> 公式サイト
                        </a>
                        <?php endif; ?>

                        <div class="mt-6" id="statusSection">
                            <p class="text-xs font-bold text-slate-400 mb-2">ステータスを変更</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($statusLabels as $k => $lbl): ?>
                                <button type="button" onclick="setStatus('<?= $k ?>', this)" data-kind="<?= $k ?>"
                                        class="px-3 py-1.5 rounded-lg text-sm font-bold transition <?= $statusKind === $k ? 'bg-sky-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    <?= htmlspecialchars($lbl) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400 mt-8">アニメ作品データ提供: <a href="https://annict.com" target="_blank" rel="noopener" class="underline">Annict</a></p>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const workId = <?= (int)($work['id'] ?? 0) ?>;
        async function setStatus(kind, btn) {
            const btns = document.querySelectorAll('#statusSection button[data-kind]');
            btns.forEach(b => b.className = 'px-3 py-1.5 rounded-lg text-sm font-bold transition bg-slate-100 text-slate-600 hover:bg-slate-200');
            btn.className = 'px-3 py-1.5 rounded-lg text-sm font-bold transition bg-sky-500 text-white';
            try {
                const res = await fetch('/anime/api/set_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ work_id: workId, kind: kind })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    App.toast('ステータスを更新しました');
                } else {
                    App.toast(json.message || '更新に失敗しました');
                }
            } catch (e) {
                App.toast('エラーが発生しました');
            }
        }
    </script>
</body>
</html>
