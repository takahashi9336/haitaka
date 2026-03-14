<?php
$appKey = 'dashboard';
require_once __DIR__ . '/../../../components/theme_from_session.php';

/**
 * WorkModel と同じロジックで使用する画像 URL を取得
 */
$getAppImageUrl = function (array $work): ?string {
    if (!empty($work['images']['recommended_url'])) return $work['images']['recommended_url'];
    if (!empty($work['images']['facebook']['og_image_url'])) return $work['images']['facebook']['og_image_url'];
    return null;
};

/**
 * 作品から全画像 URL をソース名付きで収集
 */
$collectImageUrls = function (array $work): array {
    $list = [];
    if (!empty($work['images']['recommended_url'])) {
        $list[] = ['name' => 'recommended_url', 'url' => $work['images']['recommended_url']];
    }
    if (!empty($work['images']['facebook']['og_image_url'])) {
        $list[] = ['name' => 'facebook.og_image_url', 'url' => $work['images']['facebook']['og_image_url']];
    }
    $tw = $work['images']['twitter'] ?? [];
    $twKeys = ['mini_avatar_url', 'normal_avatar_url', 'bigger_avatar_url', 'original_avatar_url', 'image_url'];
    foreach ($twKeys as $k) {
        if (!empty($tw[$k])) {
            $list[] = ['name' => 'twitter.' . $k, 'url' => $tw[$k]];
        }
    }
    return $list;
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annict API 検証 - アニメ - MyPlatform</title>
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
                <span class="text-slate-400 font-bold text-sm">/ 検証</span>
            </div>
        </header>

        <div class="flex-1 px-3 py-4 sm:px-4 md:p-8">
            <div class="max-w-5xl mx-auto">
                <h2 class="text-lg font-bold text-slate-700 mb-4">Annict API レスポンス検証</h2>

                <form method="get" action="" class="flex flex-wrap gap-3 mb-6 p-4 bg-white rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2">
                        <label for="id" class="text-sm font-bold text-slate-600">作品ID</label>
                        <input type="text" id="id" name="id" value="<?= htmlspecialchars($id) ?>"
                               placeholder="例: 12345" class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-32">
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="q" class="text-sm font-bold text-slate-600">タイトル</label>
                        <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>"
                               placeholder="検索" class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-48">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-bold hover:bg-sky-600">
                        <i class="fa-solid fa-magnifying-glass mr-1"></i>取得
                    </button>
                    <a href="/anime/verify.php" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-200">クリア</a>
                </form>

                <?php if ($error): ?>
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <?php if ($rawResponse !== null && empty($error)): ?>
                <div class="space-y-6">
                    <details class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <summary class="px-4 py-3 font-bold text-slate-700 cursor-pointer hover:bg-slate-50">生 JSON レスポンス（クリックで展開）</summary>
                        <pre class="p-4 bg-slate-50 text-xs overflow-x-auto max-h-96 overflow-y-auto"><?= htmlspecialchars(json_encode($rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </details>

                    <?php foreach ($rawResponse['works'] ?? [] as $work): ?>
                    <?php
                    $appUrl = $getAppImageUrl($work);
                    $imageList = $collectImageUrls($work);
                    ?>
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                            <h3 class="font-bold text-slate-800"><?= htmlspecialchars($work['title'] ?? '(無題)') ?></h3>
                            <p class="text-xs text-slate-500 mt-1">Annict ID: <?= htmlspecialchars((string)($work['id'] ?? '')) ?></p>
                        </div>
                        <div class="p-4">
                            <?php if (empty($imageList)): ?>
                            <p class="text-slate-500 text-sm">画像 URL がありません</p>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200">
                                            <th class="text-left py-2 px-2 font-bold text-slate-600">ソース</th>
                                            <th class="text-left py-2 px-2 font-bold text-slate-600">URL</th>
                                            <th class="text-left py-2 px-2 font-bold text-slate-600 w-32">プレビュー</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($imageList as $item): ?>
                                        <?php $isAppUsed = ($appUrl !== null && $item['url'] === $appUrl); ?>
                                        <tr class="border-b border-slate-100 <?= $isAppUsed ? 'bg-sky-50' : '' ?>">
                                            <td class="py-2 px-2 align-top">
                                                <code class="text-xs"><?= htmlspecialchars($item['name']) ?></code>
                                                <?php if ($isAppUsed): ?>
                                                <span class="ml-1 px-1.5 py-0.5 bg-sky-500 text-white text-[10px] font-bold rounded">当アプリで使用</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2 px-2 align-top break-all text-xs text-slate-600"><?= htmlspecialchars($item['url']) ?></td>
                                            <td class="py-2 px-2 align-top">
                                                <img src="<?= htmlspecialchars($item['url']) ?>" alt="" class="max-w-24 max-h-24 object-contain border border-slate-200 rounded" loading="lazy"
                                                     onerror="this.parentNode.innerHTML='<span class=\'text-red-500 text-xs\'>読込失敗</span>'">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($rawResponse === null && $error === null && $id === '' && $q === ''): ?>
                <p class="text-slate-500 text-sm">作品ID または タイトル を入力して「取得」をクリックしてください。</p>
                <?php endif; ?>

                <p class="text-[10px] text-slate-400 mt-8">データ提供: <a href="https://annict.com" target="_blank" rel="noopener" class="underline">Annict</a></p>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('mobile-open');
        });
    </script>
</body>
</html>
