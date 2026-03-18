<?php
$appKey = 'dashboard';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$animeTheme = getThemeVarsForApp('anime');
if (empty($animeTheme['themePrimaryHex'])) {
    $animeTheme['themePrimaryHex'] = '#0ea5e9';
    $animeTheme['cardIconBg'] = 'bg-sky-50';
    $animeTheme['cardIconText'] = 'text-sky-500';
    $animeTheme['cardBorder'] = 'border-slate-100';
}
$themeHex = $animeTheme['themePrimaryHex'] ?? '#0ea5e9';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>今期のアニメ - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --anime-theme: <?= htmlspecialchars($themeHex) ?>; }
        .anime-theme-btn { background-color: var(--anime-theme); }
        .anime-theme-btn:hover { filter: brightness(1.08); }
        @media (max-width: 768px) {
            body { background-color: #f8fafc; }
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 relative">
    <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-6 shrink-0 z-10">
        <div class="flex items-center gap-3">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-sky-500 text-white shadow-lg">
                <i class="fa-solid fa-calendar"></i>
            </div>
            <div>
                <h1 class="font-black text-slate-700 text-lg tracking-tighter">今期のアニメ</h1>
                <p class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($season ?? '') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 sm:gap-3 text-xs">
            <a href="/anime/" class="px-3 py-2 border border-slate-200 rounded-lg text-slate-500 font-bold hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left mr-1"></i>ダッシュボードに戻る
            </a>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto px-3 py-4 sm:px-4 md:p-6 lg:p-10">
        <div class="max-w-6xl mx-auto">
            <?php if (!empty($errorMessage)): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($works)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center text-slate-500">
                    <i class="fa-solid fa-tv text-4xl mb-3 text-slate-300"></i>
                    <p class="font-bold">今期のアニメが見つかりませんでした。</p>
                    <p class="text-sm mt-2">Annict側のデータ更新状況やフィルタ条件をご確認ください。</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <?php foreach ($works as $w):
                        $id = (int)($w['id'] ?? 0);
                        $title = $w['title'] ?? '';
                        $img = $w['images']['recommended_url']
                            ?? ($w['images']['facebook']['og_image_url'] ?? ($w['image_url'] ?? ''));
                        if ($img) {
                            if (stripos($img, 'http://') === 0) {
                                $img = 'https://' . substr($img, strlen('http://'));
                            } elseif (strpos($img, '//') === 0) {
                                $img = 'https:' . $img;
                            }
                        }
                        $seasonText = $w['season_name_text'] ?? $w['season_name'] ?? '';
                        $episodes = $w['episodes_count'] ?? null;
                        $userStatus = $w['user_status'] ?? '';
                    ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden flex flex-col cursor-pointer group"
                         data-anime-id="<?= $id ?>">
                        <div class="aspect-[16/9] bg-slate-100 overflow-hidden">
                            <?php if ($img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" referrerpolicy="no-referrer">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fa-solid fa-tv text-slate-300 text-2xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 flex-1 flex flex-col">
                            <p class="text-xs font-bold text-slate-800 line-clamp-2 mb-1"><?= htmlspecialchars($title) ?></p>
                            <div class="text-[10px] text-slate-400 mb-2">
                                <?php if ($seasonText): ?>
                                    <span><?= htmlspecialchars($seasonText) ?></span>
                                <?php endif; ?>
                                <?php if ($episodes): ?>
                                    <span><?= $seasonText ? '・' : '' ?><?= (int)$episodes ?>話</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-auto flex items-center justify-between gap-2">
                                <?php
                                $labelMap = ['wanna_watch' => '見たい', 'watching' => '見てる', 'watched' => '見た'];
                                if (!empty($userStatus) && isset($labelMap[$userStatus])): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 bg-sky-50 px-2 py-1 rounded-full">
                                        <i class="fa-solid fa-check text-[9px]"></i><?= $labelMap[$userStatus] ?>済
                                    </span>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-300">未登録</span>
                                <?php endif; ?>
                                <a href="/anime/detail.php?id=<?= $id ?>" class="text-[11px] text-sky-500 font-bold hover:text-sky-600 shrink-0">
                                    詳細
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="text-xs text-slate-400 mt-6">アニメ作品データ提供: <a href="https://annict.com" target="_blank" rel="noopener" class="underline hover:text-slate-600">Annict</a></p>
        </div>
    </div>
</main>

<script src="/assets/js/core.js?v=2"></script>
<?php require_once __DIR__ . '/_anime_search_shared.php'; ?>
<script>
    (function() {
        const cards = document.querySelectorAll('[data-anime-id]');
        const works = <?= json_encode($works ?? []) ?>;
        const map = {};
        for (let i = 0; i < works.length; i++) {
            const w = works[i];
            if (w && w.id) map[String(w.id)] = w;
        }
        function openPreview(id) {
            const key = String(id);
            const w = map[key];
            if (w && window.AnimePreview && typeof AnimePreview.open === 'function') {
                AnimePreview.open(w);
            } else {
                window.location.href = '/anime/detail.php?id=' + encodeURIComponent(id);
            }
        }
        cards.forEach(function(card) {
            card.addEventListener('click', function() {
                const id = card.getAttribute('data-anime-id');
                if (id) openPreview(id);
            });
        });
    })();
</script>
</body>
</html>

