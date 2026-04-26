<?php
/**
 * アー写一覧（閲覧）View
 * 物理パス: haitaka/private/apps/Hinata/Views/artist_photos_index.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$user = isset($user) ? $user : ($_SESSION['user'] ?? []);

use App\Hinata\Helper\MemberGroupHelper;

$tab = ($tab ?? 'releases') === 'members' ? 'members' : 'releases';
$releaseIdFilter = isset($releaseIdFilter) ? (int)$releaseIdFilter : null;
$memberIdFilter = isset($memberIdFilter) ? (int)$memberIdFilter : null;

$releaseTypes = $releaseTypes ?? [];
$groupNames = $groupNames ?? [
    'hinatazaka46' => '日向坂46',
    'hiragana_keyaki' => 'けやき坂46',
];

$releases = $releases ?? [];
$members = $members ?? [];
$rowsByRelease = $rowsByRelease ?? []; // [release_id => [member_id => image_url]]
$rowsByMember = $rowsByMember ?? [];   // [member_id => [release_id => image_url]]

$releaseMap = [];
foreach ($releases as $r) {
    $releaseMap[(int)$r['id']] = $r;
}

function memberAvatarUrl(array $member, ?string $overrideUrl): string {
    $url = $overrideUrl !== null && $overrideUrl !== '' ? $overrideUrl : ($member['image_url'] ?? '');
    if ($url === '' || $url === null) return '';
    // member.image_url は / から始まることもあるが、基本はファイル名（members/）運用
    return str_starts_with($url, '/') || str_starts_with($url, 'http') ? $url : ('/assets/img/members/' . $url);
}

/**
 * メンバー別一覧用：アー写があるリリースIDを発売日の新しい順に並べる
 *
 * @param array<int, array<string, mixed>> $releaseMap
 * @return int[]
 */
function artistPhotoSortedReleaseIdsForMember(int $mid, array $rowsByMember, array $releaseMap): array {
    $relMap = $rowsByMember[$mid] ?? [];
    $ids = [];
    foreach ($relMap as $rid => $url) {
        if (trim((string)$url) === '') {
            continue;
        }
        $ids[] = (int)$rid;
    }
    usort($ids, static function (int $a, int $b) use ($releaseMap): int {
        $ra = $releaseMap[$a] ?? null;
        $rb = $releaseMap[$b] ?? null;
        $da = $ra && !empty($ra['release_date']) ? strtotime((string)$ra['release_date']) : 0;
        $db = $rb && !empty($rb['release_date']) ? strtotime((string)$rb['release_date']) : 0;
        $cmp = $db <=> $da;
        if ($cmp !== 0) {
            return $cmp;
        }
        return $b <=> $a;
    });
    return $ids;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アーティスト写真 - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex ?? '') ?>; }
        .tab-btn.active { background: var(--hinata-theme); color: white; border-color: var(--hinata-theme); }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
        <div class="flex items-center gap-2">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
            <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-image text-sm"></i></div>
            <h1 class="font-black text-slate-700 text-lg tracking-tight">アーティスト写真</h1>
        </div>
        <a href="/hinata/release_admin.php" class="hidden md:inline-flex text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-gear mr-1"></i>リリース管理</a>
    </header>

    <div class="bg-white border-b <?= $cardBorder ?> px-4 py-2 flex items-center shrink-0">
        <div class="flex p-1 bg-slate-100 rounded-xl">
            <a href="/hinata/artist_photos.php?tab=releases" class="tab-btn px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider transition-all <?= $tab === 'releases' ? 'active' : '' ?>">リリース別</a>
            <a href="/hinata/artist_photos.php?tab=members" class="tab-btn px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider transition-all <?= $tab === 'members' ? 'active' : '' ?>">メンバー別</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
        <?php if ($tab === 'releases'): ?>
        <div class="max-w-6xl mx-auto space-y-4">
            <?php if (empty($releases)): ?>
                <div class="text-center py-20 bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm">
                    <i class="fa-solid fa-image text-4xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400 font-bold">アー写がありません</p>
                </div>
            <?php else: ?>
                <?php foreach ($releases as $rel): ?>
                    <?php
                    $rid = (int)$rel['id'];
                    $photoMap = $rowsByRelease[$rid] ?? [];
                    ?>
                    <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                        <div class="px-5 py-4 bg-slate-50 border-b <?= $cardBorder ?> flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[10px] font-black text-slate-400 tracking-wider"><?= htmlspecialchars($releaseTypes[$rel['release_type']] ?? $rel['release_type']) ?></span>
                                    <?php
                                    $gk = $rel['group_name'] ?? 'hinatazaka46';
                                    $gl = $groupNames[$gk] ?? $gk;
                                    ?>
                                    <span class="text-[9px] font-bold bg-orange-50 text-orange-800 px-2 py-0.5 rounded-full"><?= htmlspecialchars($gl) ?></span>
                                </div>
                                <h2 class="text-lg md:text-xl font-black text-slate-800 mt-1 truncate"><?= htmlspecialchars(($rel['release_number'] ?? '') !== '' ? ($rel['release_number'] . ' 『' . $rel['title'] . '』') : ($rel['title'] ?? '')) ?></h2>
                                <p class="text-xs text-slate-500 mt-1">発売日：<?= !empty($rel['release_date']) ? \Core\Utils\DateUtil::format($rel['release_date'], 'Y/m/d') : '—' ?></p>
                            </div>
                            <a href="/hinata/release.php?id=<?= $rid ?>" class="text-[10px] font-bold <?= $cardIconText ?> hover:opacity-80 shrink-0"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>リリース詳細 <i class="fa-solid fa-chevron-right"></i></a>
                        </div>

                        <?php
                        // 期別に分類（現役・卒業の別見出しは付けず、卒業生も所属期のブロックに含める）
                        $byGen = [];
                        foreach ($members as $m) {
                            $mid = (int)($m['id'] ?? 0);
                            if ($mid <= 0) {
                                continue;
                            }
                            $imgRaw = $photoMap[$mid] ?? '';
                            if (trim((string)$imgRaw) === '') {
                                continue;
                            }
                            $img = memberAvatarUrl($m, $imgRaw);
                            if ($img === '') {
                                continue;
                            }
                            $gk = ($mid === MemberGroupHelper::POKA_MEMBER_ID)
                                ? 'poka'
                                : (int)($m['generation'] ?? 0);
                            if (!isset($byGen[$gk])) {
                                $byGen[$gk] = [];
                            }
                            $byGen[$gk][] = ['m' => $m, 'img' => $img];
                        }
                        foreach ($byGen as $gk => &$genRows) {
                            usort($genRows, static function ($a, $b) {
                                $ka = (string)($a['m']['kana'] ?? $a['m']['name'] ?? '');
                                $kb = (string)($b['m']['kana'] ?? $b['m']['name'] ?? '');
                                return strcmp($ka, $kb);
                            });
                        }
                        unset($genRows);
                        $releasePhotoOrder = [];
                        if (isset($byGen[0])) {
                            $releasePhotoOrder[] = 0;
                        }
                        foreach ([1, 2, 3, 4, 5] as $g) {
                            if (isset($byGen[$g])) {
                                $releasePhotoOrder[] = $g;
                            }
                        }
                        if (isset($byGen['poka'])) {
                            $releasePhotoOrder[] = 'poka';
                        }
                        foreach (array_keys($byGen) as $k) {
                            if (!in_array($k, $releasePhotoOrder, true)) {
                                $releasePhotoOrder[] = $k;
                            }
                        }
                        $releaseHasArtistPhotos = $releasePhotoOrder !== [];
                        ?>
                        <div class="p-5 space-y-5">
                            <?php if (!$releaseHasArtistPhotos): ?>
                                <p class="text-center text-slate-400 text-sm font-bold py-8">このリリースに登録されたアー写がありません</p>
                            <?php else: ?>
                                <?php foreach ($releasePhotoOrder as $g): ?>
                                    <?php
                                    $visibleGen = $byGen[$g] ?? [];
                                    if ($visibleGen === []) {
                                        continue;
                                    }
                                    ?>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 mb-3 tracking-wider border-b border-slate-100 pb-1"><?= htmlspecialchars(MemberGroupHelper::getGenLabel($g)) ?></p>
                                        <div class="grid grid-cols-5 sm:grid-cols-7 md:grid-cols-9 gap-2">
                                            <?php foreach ($visibleGen as $row): ?>
                                                <?php
                                                $m = $row['m'];
                                                $img = $row['img'];
                                                $mid = (int)$m['id'];
                                                ?>
                                                <div class="text-center">
                                                    <button type="button"
                                                        class="ap-release-img w-full aspect-square rounded-lg bg-slate-100 overflow-hidden border border-slate-200 flex items-stretch justify-center p-0 cursor-zoom-in group"
                                                        data-src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                                                        aria-label="画像を拡大">
                                                        <img src="<?= htmlspecialchars($img) ?>" alt="" class="w-full h-full object-cover object-top pointer-events-none">
                                                    </button>
                                                    <button type="button"
                                                        class="ap-release-name mt-1 w-full text-[10px] font-bold text-slate-700 truncate hover:underline"
                                                        data-member-id="<?= $mid ?>">
                                                        <?= htmlspecialchars($m['name'] ?? '') ?>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php
        $membersForPhotoTab = array_values(array_filter($members, static function ($m) {
            return (int)($m['id'] ?? 0) !== MemberGroupHelper::POKA_MEMBER_ID;
        }));
        $membersByGen = [];
        foreach ($membersForPhotoTab as $m) {
            $gk = (int)($m['generation'] ?? 0);
            if (!isset($membersByGen[$gk])) {
                $membersByGen[$gk] = [];
            }
            $membersByGen[$gk][] = $m;
        }
        foreach ($membersByGen as $gk => &$genMembers) {
            usort($genMembers, static function ($a, $b) {
                return strcmp(
                    (string)($a['kana'] ?? $a['name'] ?? ''),
                    (string)($b['kana'] ?? $b['name'] ?? '')
                );
            });
        }
        unset($genMembers);
        $memberPhotoGenOrder = [];
        if (isset($membersByGen[0])) {
            $memberPhotoGenOrder[] = 0;
        }
        foreach ([1, 2, 3, 4, 5] as $g) {
            if (isset($membersByGen[$g])) {
                $memberPhotoGenOrder[] = $g;
            }
        }
        foreach (array_keys($membersByGen) as $k) {
            if (!in_array($k, $memberPhotoGenOrder, true)) {
                $memberPhotoGenOrder[] = $k;
            }
        }
        ?>
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b <?= $cardBorder ?> flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 tracking-wider">メンバー別</p>
                        <h2 class="text-lg font-black text-slate-800 mt-1">メンバーごとのアー写一覧</h2>
                    </div>
                    <div class="text-[10px] text-slate-500">画像で拡大 / タイトルでリリース詳細</div>
                </div>

                <div class="p-5 space-y-8">
                    <?php foreach ($memberPhotoGenOrder as $g): ?>
                        <?php
                        $groupMembers = $membersByGen[$g] ?? [];
                        if ($groupMembers === []) {
                            continue;
                        }
                        ?>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 mb-3 tracking-wider border-b border-slate-100 pb-1"><?= htmlspecialchars(MemberGroupHelper::getGenLabel($g)) ?></p>
                            <div class="divide-y <?= $cardBorder ?>">
                                <?php foreach ($groupMembers as $m): ?>
                                    <?php
                                    $mid = (int)$m['id'];
                                    $relIds = artistPhotoSortedReleaseIdsForMember($mid, $rowsByMember, $releaseMap);
                                    ?>
                                    <section id="member-photo-<?= $mid ?>" class="py-5 scroll-mt-4 first:pt-0 last:pb-0">
                                        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                                            <h3 class="text-base font-black text-slate-800"><?= htmlspecialchars($m['name'] ?? '') ?></h3>
                                            <button type="button"
                                                class="ap-member-detail text-[10px] font-bold text-slate-500 hover:text-slate-700 shrink-0"
                                                data-member-id="<?= $mid ?>">
                                                メンバー詳細
                                            </button>
                                        </div>
                                        <?php if ($relIds === []): ?>
                                            <p class="text-slate-400 text-sm font-bold py-4 text-center bg-slate-50/80 rounded-xl border border-slate-100">このメンバーのアー写が未登録です</p>
                                        <?php else: ?>
                                            <div class="grid grid-cols-5 sm:grid-cols-7 md:grid-cols-9 gap-2">
                                                <?php foreach ($relIds as $rid): ?>
                                                    <?php
                                                    $url = trim((string)(($rowsByMember[$mid] ?? [])[$rid] ?? ''));
                                                    if ($url === '') {
                                                        continue;
                                                    }
                                                    $rel = $releaseMap[$rid] ?? null;
                                                    $title = ($rel && !empty($rel['title'])) ? (string)$rel['title'] : ('release_id=' . $rid);
                                                    ?>
                                                    <div class="text-center">
                                                        <button type="button"
                                                            class="ap-member-img w-full aspect-square rounded-lg bg-slate-100 overflow-hidden border border-slate-200 flex items-stretch justify-center p-0 cursor-zoom-in"
                                                            data-src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                                                            aria-label="画像を拡大">
                                                            <img src="<?= htmlspecialchars($url) ?>" alt="" class="w-full h-full object-cover object-top pointer-events-none">
                                                        </button>
                                                        <a href="/hinata/release.php?id=<?= (int)$rid ?>"
                                                            class="ap-member-release-link mt-1 block w-full text-[10px] font-bold <?= $cardIconText ?> truncate hover:underline text-center"
                                                            <?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><?= htmlspecialchars($title) ?></a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- アー写のみ拡大（サイリウムページの artistModal に準拠） -->
<div id="artistPhotoImageModal" class="hidden fixed inset-0 z-[9999]">
    <div id="artistPhotoImageModalBg" class="absolute inset-0 bg-black/70"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="relative w-full max-w-md pointer-events-auto">
            <button id="artistPhotoImageModalClose" type="button" class="absolute -top-3 -right-3 w-10 h-10 rounded-full bg-white text-slate-600 shadow flex items-center justify-center hover:bg-slate-50 z-10">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="bg-white rounded-2xl overflow-hidden shadow-xl border border-slate-200">
                <img id="artistPhotoImageModalImg" src="" alt="アー写" class="w-full h-auto max-h-[85vh] object-contain object-top bg-slate-100">
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/member_modal.php'; ?>

<script src="/assets/js/core.js?v=2"></script>
<script src="/assets/js/hinata-member-modal.js?v=<?= time() ?>"></script>
<script>
const IS_ADMIN = <?= in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true) ? 'true' : 'false' ?>;
HinataMemberModal.init({ detailApiUrl: '/hinata/members.php', imgCacheBust: '<?= time() ?>', isAdmin: IS_ADMIN });

(function () {
    const lb = document.getElementById('artistPhotoImageModal');
    const lbBg = document.getElementById('artistPhotoImageModalBg');
    const lbImg = document.getElementById('artistPhotoImageModalImg');
    const lbClose = document.getElementById('artistPhotoImageModalClose');

    function openImageLightbox(src) {
        if (!lb || !lbImg || !src) return;
        lbImg.src = src;
        lb.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeImageLightbox() {
        if (!lb || !lbImg) return;
        lb.classList.add('hidden');
        lbImg.src = '';
        document.body.style.overflow = '';
    }

    lbBg && lbBg.addEventListener('click', closeImageLightbox);
    lbClose && lbClose.addEventListener('click', closeImageLightbox);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lb && !lb.classList.contains('hidden')) {
            closeImageLightbox();
        }
    });

    document.addEventListener('click', function (e) {
        const imgBtn = e.target.closest('.ap-release-img, .ap-member-img');
        if (imgBtn) {
            e.preventDefault();
            const src = imgBtn.getAttribute('data-src');
            if (src) openImageLightbox(src);
            return;
        }
        const detailBtn = e.target.closest('.ap-member-detail');
        if (detailBtn && detailBtn.dataset.memberId) {
            HinataMemberModal.open(detailBtn.dataset.memberId, e);
            return;
        }
        const nameBtn = e.target.closest('.ap-release-name');
        if (nameBtn) {
            const id = nameBtn.getAttribute('data-member-id');
            if (id) HinataMemberModal.open(id, e);
        }
    });
})();

document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.add('mobile-open');
});

<?php if ($tab === 'members' && $memberIdFilter): ?>
window.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('member-photo-<?= (int)$memberIdFilter ?>');
    if (el) el.scrollIntoView({ block: 'start', behavior: 'smooth' });
});
<?php endif; ?>
</script>
</body>
</html>

