<?php
/**
 * 楽曲個別紹介 View
 * 物理パス: haitaka/private/apps/Hinata/Views/song_detail.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$trackLabel = $trackTypesDisplay[$song['track_type'] ?? ''] ?? $song['track_type'] ?? 'その他';
if (!isset($backUrl) || $backUrl === '') {
    $backUrl = 'songs.php';
}
$backUrl = htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($song['title']) ?> - 楽曲 - Hinata Portal</title>
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
                <a href="<?= $backUrl ?>" class="text-slate-400 p-2 inline-flex items-center justify-center" title="楽曲一覧へ戻る"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-music text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight truncate max-w-[180px]"><?= htmlspecialchars($song['title']) ?></h1>
            </div>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/hinata/song_member_edit.php?song_id=<?= (int)$song['id'] ?>" class="text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-users-cog mr-1"></i>参加メンバー編集</a>
            <?php endif; ?>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-2xl mx-auto space-y-6">
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 tracking-wider"><?= htmlspecialchars($trackLabel) ?></p>
                    <h2 class="text-xl font-black text-slate-800 mt-1"><?= htmlspecialchars($song['title']) ?></h2>
                    <?php if (!empty($song['title_kana'])): ?>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($song['title_kana']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($release)): ?>
                    <p class="mt-3 text-sm">
                        収録：<a href="/hinata/release.php?id=<?= (int)$release['id'] ?>" class="font-bold <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><?= htmlspecialchars($release['title']) ?></a>
                        <span class="text-slate-400">（<?= htmlspecialchars($releaseTypes[$release['release_type']] ?? $release['release_type']) ?>）</span>
                    </p>
                    <?php endif; ?>
                </section>

                <?php if (!empty($song['lyricist']) || !empty($song['composer']) || !empty($song['arranger']) || !empty($song['choreographer']) || !empty($song['mv_director'])): ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-[10px] font-black text-slate-400 tracking-wider mb-3">クレジット</h3>
                    <dl class="grid grid-cols-1 gap-2 text-sm">
                        <?php if (!empty($song['lyricist'])): ?><div class="flex"><dt class="w-20 shrink-0 text-slate-500">作詞</dt><dd><?= htmlspecialchars($song['lyricist']) ?></dd></div><?php endif; ?>
                        <?php if (!empty($song['composer'])): ?><div class="flex"><dt class="w-20 shrink-0 text-slate-500">作曲</dt><dd><?= htmlspecialchars($song['composer']) ?></dd></div><?php endif; ?>
                        <?php if (!empty($song['arranger'])): ?><div class="flex"><dt class="w-20 shrink-0 text-slate-500">編曲</dt><dd><?= htmlspecialchars($song['arranger']) ?></dd></div><?php endif; ?>
                        <?php if (!empty($song['choreographer'])): ?><div class="flex"><dt class="w-20 shrink-0 text-slate-500">振付</dt><dd><?= htmlspecialchars($song['choreographer']) ?></dd></div><?php endif; ?>
                        <?php if (!empty($song['mv_director'])): ?><div class="flex"><dt class="w-20 shrink-0 text-slate-500">MV監督</dt><dd><?= htmlspecialchars($song['mv_director']) ?></dd></div><?php endif; ?>
                    </dl>
                </section>
                <?php endif; ?>

                <?php if (!empty($mvEmbedUrl)): ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-[10px] font-black text-slate-400 tracking-wider mb-3">MV</h3>
                    <div class="aspect-video rounded-xl overflow-hidden bg-slate-100">
                        <iframe class="w-full h-full" src="<?= htmlspecialchars($mvEmbedUrl) ?>?rel=0" allowfullscreen></iframe>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty($song['members'])): ?>
                <?php
                $membersByGen = [];
                foreach ($song['members'] as $m) {
                    $gen = isset($m['generation']) && $m['generation'] !== '' && $m['generation'] !== null ? (int)$m['generation'] : 0;
                    if (!isset($membersByGen[$gen])) {
                        $membersByGen[$gen] = [];
                    }
                    $membersByGen[$gen][] = $m['name'];
                }
                ksort($membersByGen, SORT_NUMERIC);
                ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-[10px] font-black text-slate-400 tracking-wider mb-3">参加メンバー</h3>
                    <ul class="space-y-2">
                        <?php foreach ($membersByGen as $gen => $names): ?>
                        <li class="text-sm text-slate-700">
                            <span class="font-bold text-slate-500"><?= $gen > 0 ? (int)$gen . '期生' : '期別なし' ?></span>
                            <span class="text-slate-700">：<?= implode('、', array_map('htmlspecialchars', $names)) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <?php if ($showFormation && (!empty($centerMembers) || !empty($formation['row_1']) || !empty($formation['row_2']) || !empty($formation['row_3']) || !empty($formation['other']))): ?>
                <div class="md:relative md:left-1/2 md:right-1/2 md:-ml-[50vw] md:-mr-[50vw] md:w-screen">
                    <div class="md:max-w-4xl md:mx-auto md:px-4">
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-[10px] font-black text-slate-400 tracking-wider mb-3">フォーメーション</h3>
                    <?php if (!empty($centerMembers)): ?>
                    <p class="text-xs text-slate-500 mb-3">センター：<?= implode('、', array_map(function ($m) { return htmlspecialchars($m['name']); }, $centerMembers)) ?></p>
                    <?php endif; ?>
                    <?php
                    // 手前（一番下）が一列目になるよう、row_3→1列目、row_2→2列目、row_1→3列目で表示。列ラベルは表示しない。全メンバー同一サイズ（スマホ・PCとも固定）。
                    $formationOrder = ['row_3', 'row_2', 'row_1', 'other'];
                    ?>
                    <div class="flex flex-col items-center gap-4">
                        <?php foreach ($formationOrder as $key): ?>
                            <?php if (!empty($formation[$key])): ?>
                            <div class="flex flex-nowrap justify-center gap-1.5 md:gap-4 w-full formation-row">
                                <?php foreach ($formation[$key] as $m): ?>
                                <?php $memberImg = ($releaseMemberImageMap[$m['member_id']] ?? null) ?: ($m['image_url'] ?? null); ?>
                                <div class="formation-member-cell flex flex-col items-center flex-none w-7 md:w-20 cursor-pointer hover:opacity-90 transition-opacity" data-member-id="<?= (int)$m['member_id'] ?>" role="button" tabindex="0" title="<?= htmlspecialchars($m['name']) ?>">
                                    <div class="w-7 h-7 md:w-20 md:h-20 shrink-0 overflow-hidden rounded-lg <?= !empty($m['is_center']) ? 'ring-2 ring-amber-400' : '' ?>">
                                        <?php if (!empty($memberImg)): ?><img src="<?= htmlspecialchars($memberImg) ?>" alt="" class="w-full h-full object-cover object-top"><?php else: ?><div class="w-full h-full bg-slate-200 flex items-center justify-center"><i class="fa-solid fa-user text-slate-400 text-[10px] md:text-base"></i></div><?php endif; ?>
                                    </div>
                                    <span class="formation-name text-[8px] md:text-xs font-medium text-slate-600 mt-0.5 w-7 md:w-20 min-w-0 overflow-hidden text-center whitespace-nowrap <?= !empty($m['is_center']) ? 'text-amber-700' : '' ?>"><?= htmlspecialchars($m['name']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($song['memo'])): ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-[10px] font-black text-slate-400 tracking-wider mb-2">メモ</h3>
                    <p class="text-sm text-slate-600 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($song['memo'])) ?></p>
                </section>
                <?php endif; ?>

                <p class="text-center"><a href="<?= $backUrl ?>" class="text-sm font-bold <?= $cardIconText ?> inline-flex items-center justify-center gap-1 py-2 px-4 rounded-lg hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-arrow-left mr-1"></i>楽曲一覧へ戻る</a></p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/member_modal.php'; ?>

    <script src="/assets/js/hinata-member-modal.js"></script>
    <script>
        HinataMemberModal.init({
            detailApiUrl: '/hinata/members.php',
            imgCacheBust: '<?= time() ?>',
            isAdmin: <?= (($user['role'] ?? '') === 'admin') ? 'true' : 'false' ?>
        });

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        });

        document.querySelectorAll('.formation-member-cell').forEach(function (el) {
            var mid = el.getAttribute('data-member-id');
            if (!mid) return;
            el.addEventListener('click', function (e) {
                e.preventDefault();
                HinataMemberModal.open(mid, e);
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    HinataMemberModal.open(mid, e);
                }
            });
        });

        // フォーメーション名を枠に収まるようフォントサイズを縮小（改行・省略なし）
        document.querySelectorAll('.formation-name').forEach(function (el) {
            var maxPx = window.getComputedStyle(el).fontSize.match(/\d+/) ? parseInt(window.getComputedStyle(el).fontSize, 10) : 12;
            for (var px = maxPx; px >= 6; px -= 1) {
                el.style.fontSize = px + 'px';
                if (el.scrollWidth <= el.clientWidth) break;
            }
        });
    </script>
</body>
</html>
