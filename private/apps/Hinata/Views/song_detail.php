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

// メイン表示用の関連動画（初期表示）は、YouTube動画を優先して決定
$mainVideo = null;
if (!empty($videosByCategory ?? []) && !empty($categoryOrder ?? [])) {
    foreach ($categoryOrder as $cat) {
        foreach ($videosByCategory[$cat] as $v) {
            if (($v['platform'] ?? '') === 'youtube' && !empty($v['media_key'])) {
                $v['_category'] = $cat;
                $mainVideo = $v;
                break 2;
            }
        }
    }
    // YouTube が無い場合は最初の動画を採用
    if ($mainVideo === null) {
        foreach ($categoryOrder as $cat) {
            $videos = $videosByCategory[$cat] ?? [];
            if (!empty($videos)) {
                $v = $videos[0];
                $v['_category'] = $cat;
                $mainVideo = $v;
                break;
            }
        }
    }
}

$mainEmbedUrl = null;
$mainCategoryLabel = '';
$mainTitle = '';
if ($mainVideo !== null && !empty($mainVideo['media_key']) && ($mainVideo['platform'] ?? '') === 'youtube') {
    $mainEmbedUrl = 'https://www.youtube.com/embed/' . $mainVideo['media_key'];
    $mainCategoryLabel = ($mainVideo['_category'] ?? '') !== '' ? $mainVideo['_category'] : '動画';
    $mainTitle = $mainVideo['title'] ?? '';
}
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
        .video-desc {
            max-height: 3.6em;
            overflow: hidden;
        }
        .video-desc.expanded {
            max-height: 12em; /* 展開時も最大高さを制限（約10行分） */
            overflow-y: auto;
        }
        .song-detail-section {
            position: static;
            z-index: auto;
        }
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
            <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
            <a href="/hinata/song_member_edit.php?song_id=<?= (int)$song['id'] ?>" class="text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-users-cog mr-1"></i>参加メンバー編集</a>
            <?php endif; ?>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-5xl mx-auto space-y-6">
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5 song-detail-section">
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
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5 song-detail-section">
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

                <?php if (!empty($mainEmbedUrl)): ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5 song-detail-section">
                    <h3 id="mainVideoCategory" class="text-[10px] font-black text-slate-400 tracking-wider mb-1"><?= htmlspecialchars($mainCategoryLabel) ?></h3>
                    <div class="aspect-video rounded-xl overflow-hidden bg-slate-100">
                        <iframe id="mainVideoIframe" class="w-full h-full" src="<?= htmlspecialchars($mainEmbedUrl) ?>?rel=0" allowfullscreen></iframe>
                    </div>
                    <?php if ($mainTitle !== ''): ?>
                    <p id="mainVideoTitle" class="mt-2 text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($mainTitle) ?></p>
                    <?php else: ?>
                    <p id="mainVideoTitle" class="mt-2 text-sm font-bold text-slate-800 truncate"></p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if (!empty($videosByCategory)): ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5 song-detail-section">
                    <h3 class="text-[10px] font-black text-slate-400 tracking-wider mb-3">関連動画</h3>
                    <div class="space-y-4">
                        <?php foreach ($categoryOrder as $cat): ?>
                            <?php $videos = $videosByCategory[$cat] ?? []; if (empty($videos)) continue; ?>
                            <div>
                                <h4 class="text-xs font-bold text-slate-500 mb-2"><?= htmlspecialchars($cat !== '' ? $cat : 'その他') ?></h4>
                                <div class="grid grid-cols-1 gap-3">
                                    <?php foreach ($videos as $v): ?>
                                    <?php
                                        $platform = $v['platform'] ?? '';
                                        $mediaKey = $v['media_key'] ?? '';
                                        $thumb = $v['thumbnail_url'] ?? '';
                                        if ($thumb === '' && $platform === 'youtube' && $mediaKey !== '') {
                                            $thumb = 'https://img.youtube.com/vi/' . rawurlencode($mediaKey) . '/mqdefault.jpg';
                                        }
                                        $embedUrl = ($platform === 'youtube' && $mediaKey !== '') ? 'https://www.youtube.com/embed/' . $mediaKey : '';
                                        $catLabel = $cat !== '' ? $cat : 'その他';
                                        static $relatedIndex = 0;
                                        $idx = $relatedIndex++;
                                    ?>
                                    <div
                                            class="flex gap-3 items-start group text-left w-full cursor-pointer related-video-item"
                                            data-embed-url="<?= htmlspecialchars($embedUrl) ?>"
                                            data-category="<?= htmlspecialchars($catLabel) ?>"
                                            data-title="<?= htmlspecialchars($v['title'] ?? '') ?>"
                                            data-index="<?= $idx ?>"
                                            role="button"
                                            tabindex="0">
                                        <div class="w-28 aspect-video rounded-lg overflow-hidden bg-slate-100 shrink-0 related-video-click-target">
                                            <?php if ($thumb !== ''): ?>
                                                <img src="<?= htmlspecialchars($thumb) ?>" alt="" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs bg-slate-200">NO IMAGE</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-slate-800 mb-0.5 group-hover:underline related-video-click-target"><?= htmlspecialchars($v['title'] ?? '') ?></p>
                                            <?php if (!empty($v['description'])): ?>
                                            <p class="text-[11px] text-slate-600 whitespace-pre-line leading-snug video-desc" data-index="<?= $idx ?>"><?= htmlspecialchars($v['description']) ?></p>
                                            <button type="button" class="video-more-btn mt-0.5 text-[11px] text-sky-600" data-index="<?= $idx ?>">...もっと見る</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                    <dl class="grid grid-cols-1 gap-2 text-sm">
                        <?php foreach ($membersByGen as $gen => $names): ?>
                        <div class="flex">
                            <dt class="w-20 shrink-0 text-slate-500"><?= $gen > 0 ? (int)$gen . '期生' : '期別なし' ?></dt>
                            <dd><?= implode('、', array_map('htmlspecialchars', $names)) ?></dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
                <?php endif; ?>

                <?php if ($showFormation && (!empty($centerMembers) || !empty($formation['row_1']) || !empty($formation['row_2']) || !empty($formation['row_3']) || !empty($formation['other']))): ?>
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
                            <div class="flex flex-nowrap justify-center gap-2 md:gap-5 w-full formation-row">
                                <?php foreach ($formation[$key] as $m): ?>
                                <?php $memberImg = ($releaseMemberImageMap[$m['member_id']] ?? null) ?: ($m['image_url'] ?? null); ?>
                                <div class="formation-member-cell flex flex-col items-center flex-none w-9 md:w-24 cursor-pointer hover:opacity-90 transition-opacity" data-member-id="<?= (int)$m['member_id'] ?>" role="button" tabindex="0" title="<?= htmlspecialchars($m['name']) ?>">
                                    <div class="w-9 h-9 md:w-24 md:h-24 shrink-0 overflow-hidden rounded-lg <?= !empty($m['is_center']) ? 'ring-2 ring-amber-400' : '' ?>">
                                        <?php if (!empty($memberImg)): ?><img src="<?= htmlspecialchars($memberImg) ?>" alt="" class="w-full h-full object-cover object-top"><?php else: ?><div class="w-full h-full bg-slate-200 flex items-center justify-center"><i class="fa-solid fa-user text-slate-400 text-[10px] md:text-base"></i></div><?php endif; ?>
                                    </div>
                                    <span class="formation-name text-[9px] md:text-sm font-medium text-slate-600 mt-0.5 w-9 md:w-24 min-w-0 overflow-hidden text-center whitespace-nowrap <?= !empty($m['is_center']) ? 'text-amber-700' : '' ?>"><?= htmlspecialchars($m['name']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
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

    <script src="/assets/js/hinata-member-modal.js?v=<?= time() ?>"></script>
    <script>
        HinataMemberModal.init({
            detailApiUrl: '/hinata/members.php',
            imgCacheBust: '<?= time() ?>',
            isAdmin: <?= in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true) ? 'true' : 'false' ?>
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

        // 関連動画: 「...もっと見る」で本文展開
        (function () {
            var descList = document.querySelectorAll('.video-desc');
            descList.forEach(function (p) {
                var idx = p.getAttribute('data-index');
                if (!idx) return;
                var btn = document.querySelector('.video-more-btn[data-index="' + idx + '"]');
                if (!btn) return;
                // もともと全文が収まっている場合はボタン非表示
                if (p.scrollHeight <= p.clientHeight + 1) {
                    btn.style.display = 'none';
                    return;
                }
                btn.addEventListener('click', function () {
                    var expanded = p.classList.toggle('expanded');
                    btn.textContent = expanded ? '閉じる' : '...もっと見る';
                });
            });
        })();

        // 関連動画をクリックしたとき、上部の動画エリアを差し替え
        (function () {
            var iframe = document.getElementById('mainVideoIframe');
            var catEl = document.getElementById('mainVideoCategory');
            var titleEl = document.getElementById('mainVideoTitle');
            if (!iframe || !catEl || !titleEl) return;
            document.querySelectorAll('.related-video-item').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    // 「...もっと見る」クリック時はここでは処理しない
                    if (e.target.closest('.video-more-btn')) {
                        return;
                    }
                    // サムネ画像 or タイトル行以外をクリックした場合も何もしない
                    if (!e.target.closest('.related-video-click-target')) {
                        return;
                    }
                    var embed = btn.getAttribute('data-embed-url');
                    if (!embed) return;
                    var cat = btn.getAttribute('data-category') || '';
                    var title = btn.getAttribute('data-title') || '';
                    iframe.src = embed + '?rel=0';
                    catEl.textContent = cat || '動画';
                    titleEl.textContent = title;
                    // 上部動画までスクロール（モバイル用）
                    iframe.closest('section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        })();

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
