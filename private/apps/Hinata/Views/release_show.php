<?php
/**
 * リリース詳細 View（収録曲一覧）
 * 物理パス: haitaka/private/apps/Hinata/Views/release_show.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$mainJacket = null;
foreach ($release['editions'] ?? [] as $ed) {
    if (($ed['edition'] ?? '') === 'type_a' && !empty($ed['jacket_image_url'])) {
        $mainJacket = $ed['jacket_image_url'];
        break;
    }
}
if (!$mainJacket && !empty($release['editions'])) {
    $mainJacket = $release['editions'][0]['jacket_image_url'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($release['title']) ?> - リリース - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/songs.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-compact-disc text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight truncate max-w-[200px]"><?= htmlspecialchars($release['title']) ?></h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-2xl mx-auto space-y-6">
                <section class="flex gap-5 items-start bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <div class="w-16 h-16 shrink-0 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center">
                        <?php if ($mainJacket): ?>
                        <img src="<?= htmlspecialchars($mainJacket) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                        <i class="fa-solid fa-compact-disc text-2xl text-slate-300"></i>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-black text-slate-400 tracking-wider"><?= htmlspecialchars($releaseTypes[$release['release_type']] ?? $release['release_type']) ?></p>
                        <h2 class="text-xl font-black text-slate-800 mt-1"><?= htmlspecialchars($release['title']) ?></h2>
                        <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($release['release_number'] ?? '') ?>　<?= !empty($release['release_date']) ? \Core\Utils\DateUtil::format($release['release_date'], 'Y年n月d日') : '' ?></p>
                        <?php if (!empty($release['description'])): ?>
                        <p class="text-sm text-slate-600 mt-3 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($release['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b <?= $cardBorder ?> flex items-center justify-between flex-wrap gap-2">
                        <h3 class="text-[10px] font-black text-slate-400 tracking-wider">収録曲（<?= count($release['songs'] ?? []) ?> 曲）</h3>
                        <div class="flex items-center gap-2">
                            <?php if (($user['role'] ?? '') === 'admin'): ?>
                            <a href="/hinata/release_artist_photos.php?release_id=<?= (int)$release['id'] ?>" class="text-[10px] font-bold <?= $cardIconText ?> hover:opacity-80 transition">アーティスト写真</a>
                            <?php endif; ?>
                            <a href="/hinata/songs.php?tab=songs&release_id=<?= (int)$release['id'] ?>" class="text-[10px] font-bold <?= $cardIconText ?>"<?= isset($cardIconStyle) && $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>全曲一覧で見る</a>
                        </div>
                    </div>
                    <?php if (empty($release['songs'])): ?>
                    <p class="p-5 text-slate-400 text-sm">収録曲はありません</p>
                    <?php else: ?>
                    <ul class="divide-y <?= $cardBorder ?>">
                        <?php foreach ($release['songs'] as $s): ?>
                        <li>
                            <a href="/hinata/song.php?id=<?= (int)$s['id'] ?>&from=release&release_id=<?= (int)$release['id'] ?>" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition">
                                <span class="text-slate-400 text-xs w-6 font-mono"><?= (int)($s['track_number'] ?? 0) ?></span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($s['title']) ?></p>
                                    <p class="text-[10px] text-slate-500"><?= htmlspecialchars($trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? '') ?></p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>

                <p class="text-center"><a href="/hinata/songs.php" class="text-sm font-bold <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-arrow-left mr-1"></i>リリース一覧へ戻る</a></p>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        });
    </script>
</body>
</html>
