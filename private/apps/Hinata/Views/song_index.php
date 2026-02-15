<?php
/**
 * 楽曲トップ View（リリース一覧・全曲一覧タブ）
 * 物理パス: haitaka/private/apps/Hinata/Views/song_index.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$tab = isset($_GET['tab']) && $_GET['tab'] === 'songs' ? 'songs' : 'releases';
// 画像下の短いラベル（TYPE-A、通常盤など）
$editionShort = ['type_a' => 'TYPE-A', 'type_b' => 'TYPE-B', 'type_c' => 'TYPE-C', 'type_d' => 'TYPE-D', 'normal' => '通常盤'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>楽曲 - Hinata Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .tab-btn.active { background: var(--hinata-theme); color: white; border-color: var(--hinata-theme); }
        .release-card:hover { border-color: var(--hinata-theme); }
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
                <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-music text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight">楽曲</h1>
            </div>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/hinata/release_admin.php" class="text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-gear mr-1"></i>リリース管理</a>
            <?php endif; ?>
        </header>

        <div class="bg-white border-b <?= $cardBorder ?> px-4 py-2 flex items-center shrink-0">
            <div class="flex p-1 bg-slate-100 rounded-xl">
                <a href="/hinata/songs.php" id="tab-releases" class="tab-btn px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider transition-all <?= $tab === 'releases' ? 'active' : '' ?>">リリース</a>
                <a href="/hinata/songs.php?tab=songs<?= isset($_GET['release_id']) ? '&release_id=' . (int)$_GET['release_id'] : '' ?>" id="tab-songs" class="tab-btn px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider transition-all <?= $tab === 'songs' ? 'active' : '' ?>">全曲</a>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scroll">
            <?php if ($tab === 'releases'): ?>
            <div class="p-4 md:p-8 max-w-4xl mx-auto">
                <?php if (empty($releases)): ?>
                <div class="text-center py-20 bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm">
                    <i class="fa-solid fa-compact-disc text-4xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400 font-bold">リリースがありません</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($releases as $rel): ?>
                    <a href="/hinata/release.php?id=<?= (int)$rel['id'] ?>" class="release-card block bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden transition-all hover:shadow-md">
                        <!-- タイトル帯：タイトルと右側にリリース日・収録曲数・表題センター -->
                        <div class="px-4 py-3 bg-slate-50 border-b <?= $cardBorder ?> flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <?php
                                $badgeClass = 'inline-block px-2 py-0.5 rounded text-[10px] font-black tracking-wider';
                                $badgeStyle = '';
                                if ($isThemeHex && !empty($themePrimary)) {
                                    $badgeStyle = 'background-color:' . htmlspecialchars($themePrimary) . '20;color:' . htmlspecialchars($themePrimary) . ';';
                                } else {
                                    $badgeClass .= ' bg-sky-100 text-sky-700';
                                }
                                ?>
                                <span class="<?= $badgeClass ?>"<?= $badgeStyle ? ' style="' . $badgeStyle . '"' : '' ?>><?= htmlspecialchars($releaseTypes[$rel['release_type']] ?? $rel['release_type']) ?></span>
                                <?php
                                $relNum = $rel['release_number'] ?? '';
                                $relTitle = $rel['title'] ?? '';
                                $titleDisplay = $relNum !== '' ? $relNum . ' 『' . $relTitle . '』' : $relTitle;
                                ?>
                                <h3 class="font-black text-slate-800 text-lg md:text-xl mt-1.5 truncate"><?= htmlspecialchars($titleDisplay) ?></h3>
                            </div>
                            <div class="text-[10px] text-slate-500 shrink-0 space-y-0.5">
                                <p>リリース日：<?= !empty($rel['release_date']) ? \Core\Utils\DateUtil::format($rel['release_date'], 'Y/m/d') : '—' ?></p>
                                <p>収録曲数：<?= (int)($rel['song_count'] ?? 0) ?>曲</p>
                                <p>表題センター：<?= !empty($rel['title_center']) ? htmlspecialchars(implode('、', $rel['title_center'])) : '—' ?></p>
                            </div>
                        </div>
                        <!-- エディション画像：画像があるものだけ表示し、下にラベル（もう少し大きく） -->
                        <div class="p-3 flex flex-wrap gap-4">
                            <?php
                            $editions = $rel['editions'] ?? [];
                            $editionsWithJacket = array_filter($editions, fn($e) => !empty($e['jacket_image_url']));
                            foreach ($editionsWithJacket as $ed):
                                $edKey = $ed['edition'] ?? '';
                                $edLabel = $editionShort[$edKey] ?? $editionLabels[$edKey] ?? $edKey;
                            ?>
                            <div class="shrink-0 flex flex-col items-center gap-1">
                                <div class="w-36 h-36 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center">
                                    <img src="<?= htmlspecialchars($ed['jacket_image_url']) ?>" alt="" class="w-full h-full object-cover">
                                </div>
                                <span class="text-[9px] font-bold text-slate-500"><?= htmlspecialchars($edLabel) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="p-4 md:p-8 max-w-5xl mx-auto space-y-6">
                <?php if (!empty($releaseIdFilter)): ?>
                <p class="text-xs text-slate-500 mb-3"><a href="/hinata/songs.php?tab=songs" class="underline">全曲表示に戻る</a></p>
                <?php endif; ?>
                <?php if (empty($songsByRelease)): ?>
                <div class="text-center py-20 bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm">
                    <i class="fa-solid fa-music text-4xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400 font-bold">楽曲がありません</p>
                </div>
                <?php else: ?>
                <?php foreach ($songsByRelease as $relGroup): ?>
                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <!-- 帯：type_a 画像 + リリース名 -->
                    <a href="/hinata/release.php?id=<?= (int)$relGroup['id'] ?>" class="flex items-center gap-3 px-4 py-3 bg-slate-50 border-b <?= $cardBorder ?> hover:bg-slate-100 transition">
                        <div class="w-12 h-12 rounded-lg bg-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                            <?php if (!empty($relGroup['jacket_url'])): ?>
                            <img src="<?= htmlspecialchars($relGroup['jacket_url']) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                            <i class="fa-solid fa-compact-disc text-xl text-slate-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[9px] font-black text-slate-400 tracking-wider"><?= htmlspecialchars($releaseTypes[$relGroup['release_type']] ?? $relGroup['release_type']) ?></span>
                            <?php
                            $rn = $relGroup['release_number'] ?? '';
                            $rt = $relGroup['title'] ?? '';
                            $bandTitle = $rn !== '' ? $rn . ' 『' . $rt . '』' : $rt;
                            ?>
                            <p class="font-black text-slate-800 text-sm truncate"><?= htmlspecialchars($bandTitle) ?></p>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-300 text-xs shrink-0"></i>
                    </a>
                    <!-- 一覧：曲リスト（メンバー帳のテーブル形式を参考） -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs md:text-sm">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 w-14">#</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500">曲名</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 w-24">種別</th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relGroup['songs'] as $s): ?>
                                <tr class="border-t <?= $cardBorder ?> hover:bg-slate-50/50 transition">
                                    <td class="px-3 py-2 text-slate-400 font-mono"><?= (int)($s['track_number'] ?? 0) ?></td>
                                    <td class="px-3 py-2">
                                        <a href="/hinata/song.php?id=<?= (int)$s['id'] ?>&from=songs" class="font-bold text-slate-800 hover:underline block truncate max-w-[280px]"><?= htmlspecialchars($s['title']) ?></a>
                                    </td>
                                    <td class="px-3 py-2 text-slate-500"><?= htmlspecialchars($trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? '') ?></td>
                                    <td class="px-3 py-2">
                                        <a href="/hinata/song.php?id=<?= (int)$s['id'] ?>&from=songs" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-chevron-right text-xs"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        });
    </script>
</body>
</html>
