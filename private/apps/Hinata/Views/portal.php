<?php
/**
 * 日向坂ポータル View（推しエリア・YTカルーセル・リリース情報付き）
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

use App\Hinata\Model\FavoriteModel;
use App\Hinata\Model\ReleaseModel;

$oshiByLevel = [];
foreach ($oshiSummary as $o) {
    $oshiByLevel[(int)$o['level']] = $o;
}
$hasOshi = !empty($oshiSummary);

function oshiImgSrc(?string $imageUrl): string {
    if (!$imageUrl) return '';
    return str_starts_with($imageUrl, '/') ? htmlspecialchars($imageUrl) : '/assets/img/members/' . htmlspecialchars($imageUrl);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日向坂ポータル - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if ($isThemeHex): ?>
    <style>
        .hinata-portal-card .card-icon { <?= $cardIconStyle ?> }
        .hinata-portal-card:hover .card-icon { <?= $cardIconHoverStyle ?> }
        .hinata-portal-card .card-deco { <?= $cardDecoStyle ?> }
        .hinata-next-event-icon { background-color: <?= htmlspecialchars($themePrimary) ?>; color: #fff; }
        .hinata-next-event-label { color: <?= htmlspecialchars($themePrimary) ?>; }
        .hinata-user-name { color: <?= htmlspecialchars($themePrimary) ?>; }
    </style>
    <?php endif; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .app-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .app-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgb(0 0 0 / 0.08); }

        /* 推しエリア */
        .oshi-main-area { transition: all 0.35s ease; }
        .oshi-sub-card { transition: all 0.2s; cursor: pointer; }
        .oshi-sub-card:hover { transform: scale(1.03); }
        .oshi-sub-card.active { ring: 2px; box-shadow: 0 0 0 2px #f59e0b; }

        /* YTカルーセル */
        .yt-section { position: relative; }
        .yt-scroll-wrap { position: relative; overflow: hidden; }
        .yt-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .yt-scroll::-webkit-scrollbar { display: none; }
        .yt-card { flex: 0 0 220px; transition: transform 0.2s ease; cursor: pointer; }
        @media (min-width: 768px) { .yt-card { flex: 0 0 260px; } }
        .yt-card:hover { transform: translateY(-4px); }
        .yt-arrow { position: absolute; top: 50%; transform: translateY(-50%); z-index: 5; width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; color: #475569; transition: all 0.2s; }
        .yt-arrow:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #1e293b; }
        .yt-arrow.left { left: -4px; }
        .yt-arrow.right { right: -4px; }
        .yt-arrow.hidden { display: none; }
        /* TikTokカルーセル（ショート動画用縦長カード） */
        .tk-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .tk-scroll::-webkit-scrollbar { display: none; }
        .tk-card { flex: 0 0 140px; transition: transform 0.2s ease; cursor: pointer; }
        @media (min-width: 768px) { .tk-card { flex: 0 0 160px; } }
        .tk-card:hover { transform: translateY(-4px); }
        .blog-card { flex: 0 0 130px; transition: transform 0.2s ease; }
        @media (min-width: 768px) { .blog-card { flex: 0 0 150px; } }
        .blog-card:hover { transform: translateY(-3px); }
        /* 誕生日横スクロール */
        .bd-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .bd-scroll::-webkit-scrollbar { display: none; }
        @keyframes skeletonPulse { 0%,100% { opacity: 0.4; } 50% { opacity: 0.7; } }
        .skeleton-card { animation: skeletonPulse 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-sun text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">日向坂ポータル</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider">ログインユーザー</span>
                    <span class="text-xs font-black hinata-user-name <?= !$isThemeHex ? "text-{$themeTailwind}-500" : '' ?>"><?= htmlspecialchars($user['id_name'] ?? 'ゲスト') ?></span>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scroll">
            <div class="max-w-5xl mx-auto">
                <?php if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0): ?>
                <div class="mb-8 flex items-center">
                    <div class="flex items-center gap-4 bg-white rounded-xl border <?= $cardBorder ?> shadow-sm px-5 py-4 w-full md:w-auto">
                        <div class="w-10 h-10 rounded-lg text-white flex items-center justify-center shadow-md hinata-next-event-icon <?= $headerIconBg ?>">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-bold tracking-wider mb-1 hinata-next-event-label <?= !$isThemeHex ? "text-{$themeTailwind}-500" : '' ?>">次のイベント</p>
                            <p class="text-sm font-bold text-slate-800 mb-0.5"><?= htmlspecialchars($nextEvent['event_name'] ?? '次のイベント') ?></p>
                            <p class="text-xs text-slate-500">
                                <?php
                                    $days = (int)$nextEvent['days_left'];
                                    $dateText = !empty($nextEvent['event_date']) ? \Core\Utils\DateUtil::format($nextEvent['event_date'], 'Y/m/d') : '';
                                    if ($days === 0) echo '本日開催';
                                    elseif ($days === 1) echo 'あと 1 日';
                                    else echo 'あと ' . $days . ' 日';
                                    if ($dateText) echo '（' . $dateText . '）';
                                ?>
                            </p>
                        </div>
                        <a href="/hinata/events.php" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border <?= $cardBorder ?> <?= $cardIconText ?> hover:opacity-80 transition"<?= $isThemeHex ? ' style="color: ' . htmlspecialchars($themePrimary) . ';"' : '' ?>>
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($upcomingBirthdays)): ?>
                <div class="mb-8">
                    <?php
                    $bdToday = array_filter($upcomingBirthdays, fn($b) => (int)$b['days_until'] === 0);
                    $bdUpcoming = array_filter($upcomingBirthdays, fn($b) => (int)$b['days_until'] > 0);
                    ?>
                    <?php if (!empty($bdToday)): ?>
                    <div class="bg-gradient-to-r from-pink-50 to-rose-50 rounded-xl border border-pink-200 shadow-sm px-5 py-4 mb-3 relative overflow-hidden">
                        <div class="absolute top-0 right-0 opacity-10 text-6xl p-3 text-pink-300"><i class="fa-solid fa-cake-candles"></i></div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-pink-500 text-white flex items-center justify-center shadow-sm"><i class="fa-solid fa-birthday-cake text-sm"></i></div>
                            <h3 class="text-sm font-black text-pink-700 tracking-tight">Happy Birthday!</h3>
                        </div>
                        <div class="flex items-center gap-4">
                            <?php foreach ($bdToday as $bd): ?>
                            <?php
                            $bdImg = $bd['first_image'] ?: ($bd['image_url'] ?? null);
                            $bdAge = $bd['birth_date'] ? (date('Y') - (int)date('Y', strtotime($bd['birth_date']))) : null;
                            ?>
                            <a href="/hinata/member.php?id=<?= (int)$bd['id'] ?>" class="flex items-center gap-3 hover:opacity-80 transition">
                                <div class="w-14 h-14 rounded-full overflow-hidden bg-pink-100 shrink-0 ring-2 ring-pink-300 shadow-md">
                                    <?php if ($bdImg): ?>
                                    <img src="/assets/img/members/<?= htmlspecialchars($bdImg) ?>" class="w-full h-full object-cover" alt="">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-pink-300"><i class="fa-solid fa-user text-xl"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-base font-black text-pink-800"><?= htmlspecialchars($bd['name']) ?></p>
                                    <p class="text-xs text-pink-600"><?= $bd['generation'] ?>期生<?= $bdAge ? " &middot; {$bdAge}歳" : '' ?></p>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($bdUpcoming)): ?>
                    <div class="bd-scroll">
                        <?php foreach ($bdUpcoming as $bd): ?>
                        <?php
                        $bdImg = $bd['first_image'] ?: ($bd['image_url'] ?? null);
                        $bdDate = $bd['birth_date'] ? date('Y/m/d', strtotime($bd['birth_date'])) : '';
                        ?>
                        <a href="/hinata/member.php?id=<?= (int)$bd['id'] ?>" class="flex items-center gap-3 bg-white rounded-xl border <?= $cardBorder ?> shadow-sm px-4 py-3 shrink-0 hover:shadow-md hover:border-pink-200 transition">
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 shrink-0" style="<?= !empty($bd['color1']) ? 'box-shadow: 0 0 0 2px ' . htmlspecialchars($bd['color1']) : '' ?>">
                                <?php if ($bdImg): ?>
                                <img src="/assets/img/members/<?= htmlspecialchars($bdImg) ?>" class="w-full h-full object-cover" alt="">
                                <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-sm"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($bd['name']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= $bdDate ?></p>
                                <p class="text-[10px] text-pink-500 font-bold">あと<?= (int)$bd['days_until'] ?>日</p>
                            </div>
                            <i class="fa-solid fa-cake-candles text-pink-300 text-xs ml-auto shrink-0"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($todayMeetGreetSlots)): ?>
                <div class="mb-8">
                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl border border-amber-200 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-amber-100">
                            <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shadow-sm">
                                <i class="fa-solid fa-handshake text-sm"></i>
                            </div>
                            <h3 class="text-sm font-black text-amber-700 tracking-tight">本日のミーグリ予定</h3>
                            <span class="text-[10px] font-bold text-amber-500 bg-amber-100 px-2 py-0.5 rounded-full"><?= count($todayMeetGreetSlots) ?> 枠</span>
                            <a href="/hinata/meetgreet.php" class="ml-auto text-[10px] font-bold text-amber-500 hover:text-amber-700 transition">詳細 <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div class="divide-y divide-amber-100/80">
                            <?php foreach ($todayMeetGreetSlots as $slot): ?>
                            <div class="flex items-center gap-4 px-5 py-3">
                                <?php if (!empty($slot['color1'])): ?>
                                <div class="w-8 h-8 rounded-full shrink-0 border-2 border-white shadow-sm" style="background: linear-gradient(135deg, <?= htmlspecialchars($slot['color1']) ?>, <?= htmlspecialchars($slot['color2'] ?: $slot['color1']) ?>);"></div>
                                <?php else: ?>
                                <div class="w-8 h-8 rounded-full shrink-0 bg-slate-200 flex items-center justify-center"><i class="fa-solid fa-user text-slate-400 text-xs"></i></div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($slot['member_name'] ?? $slot['member_name_raw'] ?? '未設定') ?></p>
                                    <p class="text-[10px] text-slate-500">
                                        <?= htmlspecialchars($slot['slot_name']) ?>
                                        <?php if ($slot['start_time']): ?>
                                        &middot; <?= substr($slot['start_time'], 0, 5) ?><?php if ($slot['end_time']): ?>〜<?= substr($slot['end_time'], 0, 5) ?><?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ((int)$slot['ticket_count'] > 0): ?>
                                        &middot; <?= $slot['ticket_count'] ?> 枚
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if (!empty($slot['report'])): ?>
                                <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-check mr-0.5"></i>レポ済</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 推し情報エリア -->
                <section class="mb-10">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-black text-slate-500 tracking-wider"><i class="fa-solid fa-crown text-amber-500 mr-2"></i>あなたの推し</h2>
                        <a href="/hinata/oshi_settings.php" class="text-[10px] font-bold text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-gear mr-1"></i>推し設定</a>
                    </div>
                    <?php if ($hasOshi): ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                        <div class="flex flex-col md:flex-row md:items-stretch">
                            <!-- メインエリア（選択中の推し） -->
                            <div id="oshiMainArea" class="oshi-main-area flex-1 p-5">
                                <?php
                                $mainOshi = $oshiByLevel[9] ?? $oshiByLevel[8] ?? $oshiByLevel[7] ?? null;
                                if ($mainOshi):
                                    $mainLevel = (int)$mainOshi['level'];
                                    $mainLabel = FavoriteModel::LEVEL_LABELS[$mainLevel] ?? '';
                                ?>
                                <div class="flex flex-col md:flex-row gap-4 md:gap-5 h-full">
                                    <div class="w-36 h-36 md:w-44 md:h-auto rounded-xl overflow-hidden bg-slate-100 shrink-0 shadow-md mx-auto md:mx-0 md:self-stretch">
                                        <?php if ($mainOshi['image_url']): ?>
                                        <img id="oshiMainImg" src="<?= oshiImgSrc($mainOshi['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                        <?php else: ?>
                                        <div id="oshiMainImg" class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-4xl"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0 flex flex-col">
                                        <div class="flex-1">
                                            <span id="oshiMainLabel" class="inline-block text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full mb-1"><?= $mainLabel ?></span>
                                            <h3 id="oshiMainName" class="text-xl md:text-2xl font-black text-slate-800 mb-0.5"><?= htmlspecialchars($mainOshi['name']) ?></h3>
                                            <p id="oshiMainGen" class="text-[11px] text-slate-400 mb-2"><?= htmlspecialchars($mainOshi['generation']) ?>期生</p>
                                            <div id="oshiMainDetails" class="space-y-1.5 text-xs">
                                                <a id="oshiMainBlog" href="<?= !empty($mainOshi['blog_url']) ? htmlspecialchars($mainOshi['blog_url']) : '#' ?>" target="_blank" class="flex items-center gap-2 text-sky-600 hover:text-sky-700<?= empty($mainOshi['blog_url']) ? ' hidden' : '' ?>"><i class="fa-solid fa-blog w-4"></i>公式ブログ</a>
                                                <a id="oshiMainInsta" href="<?= !empty($mainOshi['insta_url']) ? htmlspecialchars($mainOshi['insta_url']) : '#' ?>" target="_blank" class="flex items-center gap-2 text-pink-600 hover:text-pink-700<?= empty($mainOshi['insta_url']) ? ' hidden' : '' ?>"><i class="fa-brands fa-instagram w-4"></i>Instagram</a>
                                                <?php if (!empty($mainOshi['next_event'])): ?>
                                                <div id="oshiMainEvent" class="flex items-center gap-2 text-slate-600"><i class="fa-solid fa-calendar w-4 text-slate-400"></i><?= htmlspecialchars($mainOshi['next_event']['event_name']) ?> (<?= htmlspecialchars($mainOshi['next_event']['event_date']) ?>)</div>
                                                <?php endif; ?>
                                                <?php if ($mainOshi['song_count'] > 0): ?>
                                                <div id="oshiMainSongs" class="flex items-center gap-2 text-slate-600"><i class="fa-solid fa-music w-4 text-slate-400"></i>参加楽曲 <?= $mainOshi['song_count'] ?> 曲</div>
                                                <?php endif; ?>
                                                <?php
                                                $mainNewItems = $oshiLatestItemByMember[$mainOshi['member_id']] ?? [];
                                                $hasAnyNewItems = !empty($oshiLatestItemByMember);
                                                ?>
                                                <?php if ($hasAnyNewItems): ?>
                                                <div id="oshiMainNewItemWrap" class="pt-2<?= empty($mainNewItems) ? ' hidden' : '' ?>">
                                                    <p class="text-sm font-black text-slate-600 mb-1.5"><i class="fa-solid fa-bell text-amber-500 mr-1"></i>推しの新着</p>
                                                    <div id="oshiMainNewItemContent" class="space-y-1.5">
                                                        <?php foreach ($mainNewItems as $ni):
                                                            $niType = $ni['type'] ?? '';
                                                            $niIcon = ['blog' => 'fa-blog text-sky-500', 'news' => 'fa-newspaper text-rose-500', 'schedule' => 'fa-calendar text-violet-500', 'event' => 'fa-calendar text-violet-500', 'video' => 'fa-play text-red-500'][$niType] ?? 'fa-circle-info text-slate-400';
                                                            $niDate = !empty($ni['event_date']) ? date('n/j', strtotime($ni['event_date'])) : '';
                                                            $niUrl = !empty($ni['url']) ? $ni['url'] : null;
                                                        ?>
                                                        <div class="flex items-start gap-2 text-sm min-w-0 rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 hover:border-amber-300 hover:bg-amber-50/50 transition">
                                                            <span class="text-xs text-slate-400 shrink-0"><?= $niDate ?></span>
                                                            <i class="fa-solid <?= $niIcon ?> w-4 shrink-0 mt-0.5"></i>
                                                            <?php if ($niUrl): ?>
                                                            <a href="<?= htmlspecialchars($niUrl) ?>" target="_blank" rel="noopener" class="flex-1 min-w-0 text-slate-700 hover:text-amber-600 line-clamp-2 break-words" title="<?= htmlspecialchars($ni['title'] ?? '') ?>"><?= htmlspecialchars($ni['title'] ?? '') ?></a>
                                                            <?php else: ?>
                                                            <span class="flex-1 min-w-0 text-slate-700 line-clamp-2 break-words" title="<?= htmlspecialchars($ni['title'] ?? '') ?>"><?= htmlspecialchars($ni['title'] ?? '') ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a id="oshiMainLink" href="/hinata/member.php?id=<?= $mainOshi['member_id'] ?>" class="inline-flex items-center justify-center gap-2 mt-3 w-full px-5 py-2.5 rounded-full text-sm font-black text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-md hover:shadow-lg transition-all"><i class="fa-solid fa-arrow-right text-xs"></i>推し個別ページへ</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- サブエリア（2推し・3推し） -->
                            <div class="w-full md:w-36 border-t md:border-t-0 md:border-l border-slate-100 p-2 flex md:flex-col gap-1 justify-center">
                                <?php
                                $subSlots = [
                                    ['level' => 9, 'label' => '最推し', 'colorClass' => 'amber'],
                                    ['level' => 8, 'label' => '2推し', 'colorClass' => 'pink'],
                                    ['level' => 7, 'label' => '3推し', 'colorClass' => 'rose'],
                                ];
                                foreach ($subSlots as $idx => $ss):
                                    $so = $oshiByLevel[$ss['level']] ?? null;
                                    if (!$so) continue;
                                    $isMain = ($mainOshi && (int)$mainOshi['level'] === $ss['level']);
                                ?>
                                <div class="oshi-sub-card flex-1 flex md:flex-col items-center justify-center gap-1 p-2 rounded-lg cursor-pointer <?= $isMain ? 'bg-amber-50 shadow-sm' : 'hover:bg-slate-50' ?>"
                                     data-level="<?= $ss['level'] ?>"
                                     onclick="OshiPortal.switchMain(<?= $ss['level'] ?>)">
                                    <div class="w-11 h-11 md:w-12 md:h-12 rounded-full overflow-hidden bg-slate-100 shrink-0 <?= $isMain ? 'ring-2 ring-amber-400' : '' ?>">
                                        <?php if ($so['image_url']): ?>
                                        <img src="<?= oshiImgSrc($so['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                        <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-sm"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[8px] font-black text-<?= $ss['colorClass'] ?>-500"><?= $ss['label'] ?></p>
                                        <p class="text-[10px] font-bold text-slate-700 leading-tight"><?= htmlspecialchars($so['name']) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-8 text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-amber-50 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-heart text-amber-400 text-2xl"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700 mb-2">推しを設定しましょう！</p>
                        <p class="text-xs text-slate-400 mb-4">推しを設定すると、ここにメンバーの情報が表示されます。</p>
                        <a href="/hinata/oshi_settings.php" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg text-xs font-bold hover:bg-amber-600 transition shadow-sm"><i class="fa-solid fa-gear"></i>推し設定へ</a>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if (!empty($todayInHistory)): ?>
                <section class="mb-10">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">今日は何の日</h2>
                        <span class="text-[10px] text-slate-400 ml-1"><?= date('n月j日') ?></span>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($todayInHistory as $hist): ?>
                        <div class="flex items-center gap-3 bg-white rounded-xl border <?= $cardBorder ?> shadow-sm px-4 py-3">
                            <?php if ($hist['type'] === 'release'): ?>
                            <div class="w-8 h-8 rounded-lg bg-violet-100 text-violet-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-compact-disc text-sm"></i></div>
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-calendar-star text-sm"></i></div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($hist['title']) ?></p>
                                <p class="text-[10px] text-slate-400">
                                    <?= (int)$hist['years_ago'] ?>年前
                                    <?php if ($hist['type'] === 'release'): ?>
                                    &middot; <?= htmlspecialchars(ReleaseModel::RELEASE_TYPES[$hist['release_type']] ?? '') ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($hist['type'] === 'release'): ?>
                            <a href="/hinata/release.php?id=<?= (int)$hist['id'] ?>" class="text-[10px] font-bold <?= $cardIconText ?> hover:opacity-80 transition shrink-0"<?= $isThemeHex ? ' style="color:' . htmlspecialchars($themePrimary) . '"' : '' ?>>詳細</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- 最新リリース情報 -->
                <?php if ($latestRelease):
                    $releaseIsNew = !empty($latestRelease['release_date'])
                        && (strtotime($latestRelease['release_date']) >= strtotime('-90 days'));
                ?>
                <section class="mb-10">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-compact-disc text-violet-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">最新リリース</h2>
                        <?php if ($releaseIsNew): ?>
                        <span class="text-[10px] font-black text-white bg-red-500 px-2.5 py-0.5 rounded-full shadow-sm shadow-red-200 animate-pulse">NEW</span>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                        <a href="/hinata/release.php?id=<?= $latestRelease['id'] ?>" class="block p-5 hover:bg-slate-50/50 transition group">
                            <div class="flex items-start gap-5">
                                <div class="flex-1 min-w-0">
                                    <span class="text-[10px] font-bold text-violet-500 bg-violet-50 px-2 py-0.5 rounded-full"><?= htmlspecialchars($latestRelease['release_type_label']) ?></span>
                                    <h3 class="text-lg font-black text-slate-800 mt-1 mb-1 truncate"><?= htmlspecialchars($latestRelease['title']) ?></h3>
                                    <p class="text-xs text-slate-400">
                                        <?= $latestRelease['release_date'] ? date('Y/m/d', strtotime($latestRelease['release_date'])) : '' ?>
                                        <?php if ($latestRelease['song_count']): ?> &middot; <?= $latestRelease['song_count'] ?> 曲収録<?php endif; ?>
                                    </p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-500 transition hidden md:block mt-2"></i>
                            </div>
                            <?php
                            $jackets = [];
                            if (!empty($latestRelease['editions'])) {
                                foreach ($latestRelease['editions'] as $ed) {
                                    if (!empty($ed['jacket_image_url'])) {
                                        $jackets[] = $ed;
                                    }
                                }
                            }
                            if (!empty($jackets)):
                            ?>
                            <div class="flex gap-3 mt-4 overflow-x-auto pb-1" style="scrollbar-width: none; -webkit-overflow-scrolling: touch;">
                                <?php foreach ($jackets as $jk): ?>
                                <div class="shrink-0 w-28 md:w-32">
                                    <div class="aspect-square rounded-lg overflow-hidden shadow-md group-hover:shadow-lg transition bg-slate-100">
                                        <img src="<?= htmlspecialchars($jk['jacket_image_url']) ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                    </div>
                                    <p class="text-[9px] text-slate-400 mt-1 text-center truncate"><?= htmlspecialchars(\App\Hinata\Model\ReleaseEditionModel::EDITIONS[$jk['edition']] ?? $jk['edition']) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php elseif ($latestRelease['jacket_url']): ?>
                            <div class="mt-4">
                                <div class="w-28 md:w-32 aspect-square rounded-lg overflow-hidden shadow-md group-hover:shadow-lg transition bg-slate-100">
                                    <img src="<?= htmlspecialchars($latestRelease['jacket_url']) ?>" class="w-full h-full object-cover" alt="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </a>
                        <?php if (!empty($latestRelease['mvs'])): ?>
                        <div class="border-t border-slate-100 px-5 py-4">
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fa-solid fa-play-circle text-violet-400 text-sm"></i>
                                <span class="text-[11px] font-bold text-slate-600">ミュージックビデオ</span>
                                <span class="text-[9px] text-slate-400"><?= count($latestRelease['mvs']) ?> 本</span>
                            </div>
                            <div class="yt-scroll-wrap">
                                <button class="yt-arrow left hidden" onclick="YtCarousel.scroll('releaseMvCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                                <div id="releaseMvCards" class="yt-scroll">
                                    <?php foreach ($latestRelease['mvs'] as $mv): ?>
                                    <div class="yt-card cursor-pointer" onclick="playReleaseMv('<?= htmlspecialchars($mv['media_key']) ?>', '<?= htmlspecialchars(addslashes($mv['song_title'])) ?>', event)">
                                        <div class="aspect-video rounded-lg overflow-hidden bg-slate-200 mb-1.5 shadow-sm relative group">
                                            <img src="<?= htmlspecialchars($mv['thumbnail_url'] ?: 'https://img.youtube.com/vi/' . $mv['media_key'] . '/mqdefault.jpg') ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center">
                                                <i class="fa-solid fa-play text-white text-xl opacity-60 group-hover:opacity-100 transition drop-shadow-lg"></i>
                                            </div>
                                        </div>
                                        <h3 class="text-xs font-bold text-slate-700 line-clamp-2 leading-snug"><?= htmlspecialchars($mv['song_title']) ?></h3>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="yt-arrow right" onclick="YtCarousel.scroll('releaseMvCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($latestRelease['songs'])): ?>
                        <div class="border-t border-slate-100">
                            <div class="flex items-center gap-2 px-5 pt-4 pb-2">
                                <i class="fa-solid fa-list-music text-violet-400 text-sm"></i>
                                <span class="text-[11px] font-bold text-slate-600">収録曲</span>
                                <span class="text-[9px] text-slate-400"><?= count($latestRelease['songs']) ?> 曲</span>
                            </div>
                            <div id="releaseTrackMiniPlayer" class="hidden border-b border-slate-100 bg-slate-50/80">
                                <div class="flex items-center gap-2 px-5 py-2">
                                    <p id="releaseTrackTitle" class="text-[11px] font-bold text-slate-700 flex-1 truncate"></p>
                                    <button onclick="ReleasePlayer.close()" class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 hover:bg-slate-300 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <div id="releaseTrackEmbed" class="px-5 pb-3"></div>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                <?php
                                $trackTypeLabelsPortal = \App\Hinata\Model\SongModel::TRACK_TYPES_DISPLAY;
                                foreach ($latestRelease['songs'] as $rs):
                                    $rsHasApple = !empty($rs['apple_music_url']);
                                    $rsHasSpotify = !empty($rs['spotify_url']);
                                    $rsHasStream = $rsHasApple || $rsHasSpotify;
                                ?>
                                <li class="flex items-center gap-2.5 px-5 py-2.5 hover:bg-slate-50/50 transition">
                                    <span class="text-slate-300 text-[10px] font-mono w-4 text-right shrink-0"><?= (int)($rs['track_number'] ?? 0) ?></span>
                                    <a href="/hinata/song.php?id=<?= (int)$rs['id'] ?>" class="flex-1 min-w-0">
                                        <p class="text-[12px] font-bold text-slate-700 truncate"><?= htmlspecialchars($rs['title']) ?></p>
                                        <p class="text-[9px] text-slate-400"><?= htmlspecialchars($trackTypeLabelsPortal[$rs['track_type'] ?? ''] ?? $rs['track_type'] ?? '') ?></p>
                                    </a>
                                    <?php if ($rsHasStream): ?>
                                    <div class="flex gap-1 shrink-0">
                                        <?php if ($rsHasApple): ?>
                                        <button onclick="ReleasePlayer.play('apple','<?= htmlspecialchars(addslashes($rs['apple_music_url'])) ?>','<?= htmlspecialchars(addslashes($rs['title'])) ?>')"
                                                class="w-6 h-6 rounded-full bg-slate-100 hover:bg-pink-50 flex items-center justify-center transition text-slate-400 hover:text-pink-500" title="Apple Music">
                                            <i class="fa-brands fa-apple text-[10px]"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($rsHasSpotify): ?>
                                        <button onclick="ReleasePlayer.play('spotify','<?= htmlspecialchars(addslashes($rs['spotify_url'])) ?>','<?= htmlspecialchars(addslashes($rs['title'])) ?>')"
                                                class="w-6 h-6 rounded-full bg-slate-100 hover:bg-emerald-50 flex items-center justify-center transition text-slate-400 hover:text-emerald-500" title="Spotify">
                                            <i class="fa-brands fa-spotify text-[10px]"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- 最新ブログ（全メンバー対象） -->
                <?php if (!empty($latestBlogPosts)): ?>
                <section class="mb-10">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-pen-fancy text-pink-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">最新ブログ</h2>
                        <span class="text-[10px] text-slate-400 ml-auto"><?= count($latestBlogPosts) ?> 件</span>
                    </div>
                    <div class="yt-scroll-wrap">
                        <button class="yt-arrow left hidden" onclick="YtCarousel.scroll('blogCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="blogCards" class="yt-scroll">
                            <?php foreach ($latestBlogPosts as $bp): ?>
                            <a href="<?= htmlspecialchars($bp['detail_url']) ?>" target="_blank" class="blog-card block">
                                <div class="aspect-[3/4] rounded-lg overflow-hidden bg-slate-100 mb-1.5 shadow-sm relative">
                                    <?php if ($bp['thumbnail_url']): ?>
                                    <img src="<?= htmlspecialchars($bp['thumbnail_url']) ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-pen-fancy text-2xl"></i></div>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-[11px] font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5"><?= htmlspecialchars($bp['title'] ?: '(無題)') ?></h3>
                                <p class="text-[9px] text-slate-400 truncate"><?= htmlspecialchars($bp['member_name'] ?? '') ?> <?= $bp['published_at'] ? date('m/d H:i', strtotime($bp['published_at'])) : '' ?></p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <button class="yt-arrow right" onclick="YtCarousel.scroll('blogCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </section>
                <?php endif; ?>

                <!-- YouTube (タブ切替) -->
                <section class="mb-10">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-brands fa-youtube text-red-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">YouTube</h2>
                        <div class="flex gap-1 ml-auto">
                            <button class="yt-tab text-[10px] font-bold px-3 py-1 rounded-full bg-red-500 text-white" data-idx="0" onclick="switchYtTab(0)">ちゃんねる</button>
                            <button class="yt-tab text-[10px] font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition" data-idx="1" onclick="switchYtTab(1)">公式チャンネル</button>
                        </div>
                    </div>
                    <?php
                    $channels = [
                        ['id' => 'UCOB24f8lQBCnVqPZXOkVpOg', 'name' => '日向坂ちゃんねる'],
                        ['id' => 'UCR0V48DJyWbwEAdxLL5FjxA', 'name' => '日向坂46公式チャンネル'],
                    ];
                    foreach ($channels as $ci => $ch):
                    ?>
                    <div class="yt-tab-panel" data-panel="<?= $ci ?>"<?= $ci > 0 ? ' style="display:none"' : '' ?>>
                        <div class="yt-scroll-wrap">
                            <button class="yt-arrow left hidden" onclick="YtCarousel.scroll('ytCards<?= $ci ?>', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                            <div id="ytLoading<?= $ci ?>" class="yt-scroll">
                                <?php for ($i = 0; $i < 8; $i++): ?>
                                <div class="yt-card skeleton-card">
                                    <div class="aspect-video bg-slate-200 rounded-lg mb-2"></div>
                                    <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                    <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <div id="ytCards<?= $ci ?>" class="yt-scroll hidden" data-channel-id="<?= $ch['id'] ?>"></div>
                            <button class="yt-arrow right" onclick="YtCarousel.scroll('ytCards<?= $ci ?>', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </section>

                <!-- TikTok -->
                <section class="mb-10">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-brands fa-tiktok text-slate-800"></i>
                        <h2 class="text-sm font-bold text-slate-700">TikTok</h2>
                    </div>
                    <div class="yt-scroll-wrap">
                        <button class="yt-arrow left hidden" onclick="TkCarousel.scroll('tkCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                        <div id="tkLoading" class="tk-scroll">
                            <?php for ($i = 0; $i < 8; $i++): ?>
                            <div class="tk-card skeleton-card">
                                <div class="aspect-[9/16] bg-slate-200 rounded-lg mb-2"></div>
                                <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div id="tkCards" class="tk-scroll hidden"></div>
                        <button class="yt-arrow right" onclick="TkCarousel.scroll('tkCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                    </div>
                </section>

                <!-- X 公式 (インライン) -->
                <div class="flex flex-col gap-2 mb-10">
                    <div class="flex items-center gap-3 bg-white rounded-lg border <?= $cardBorder ?> shadow-sm px-4 py-3">
                        <i class="fa-brands fa-x-twitter text-lg text-slate-600"></i>
                        <span class="text-xs font-bold text-slate-700">日向坂46</span>
                        <span class="text-[10px] text-slate-400">@hinatazaka46</span>
                        <a href="https://x.com/hinatazaka46" target="_blank" rel="noopener"
                           class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 text-white rounded-full text-[10px] font-bold hover:bg-slate-700 transition">
                            <i class="fa-brands fa-x-twitter text-[8px]"></i>ポストを見る
                        </a>
                    </div>
                    <div class="flex items-center gap-3 bg-white rounded-lg border <?= $cardBorder ?> shadow-sm px-4 py-3">
                        <i class="fa-brands fa-instagram text-lg text-slate-600"></i>
                        <span class="text-xs font-bold text-slate-700">日向坂46</span>
                        <span class="text-[10px] text-slate-400">@hinatazaka46</span>
                        <a href="https://www.instagram.com/hinatazaka46/" target="_blank" rel="noopener"
                           class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-pink-500 via-red-500 to-yellow-500 text-white rounded-full text-[10px] font-bold hover:brightness-110 transition">
                            <i class="fa-brands fa-instagram text-[8px]"></i>Instagramを見る
                        </a>
                    </div>
                </div>

                <!-- アプリ -->
                <section class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-grip text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-500 tracking-wider">アプリ</h2>
                </div>
                <div class="grid grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <a href="/hinata/talk.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-book-open text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-comment-dots text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ミーグリネタ帳</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メンバーとの会話ネタや、ミーグリのレポを記録・管理します。</p>
                        </div>
                    </a>
                    <a href="/hinata/meetgreet.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-ticket text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-ticket text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ミーグリ予定</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ミーグリの予定管理とレポを記録します。</p>
                        </div>
                    </a>
                    <a href="/hinata/events.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-calendar-days text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-calendar-check text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">イベント</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ライブやミーグリ、発売日などの重要日程を確認します。</p>
                        </div>
                    </a>
                    <a href="/hinata/members.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-users text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-address-card text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">メンバー帳</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メンバーのプロフィール、サイリウムカラーなどをチェックします。</p>
                        </div>
                    </a>
                    <a href="/hinata/songs.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-music text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-music text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">楽曲</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">リリース一覧・全曲一覧・楽曲の紹介を確認します。</p>
                        </div>
                    </a>
                    <a href="/hinata/media_list.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-video text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-play-circle text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画一覧</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">登録されたすべての動画を閲覧します。</p>
                        </div>
                    </a>
                </div>
                <?php if (in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                <details class="mt-4">
                    <summary class="text-[10px] font-bold text-slate-400 cursor-pointer hover:text-slate-600 transition flex items-center gap-1"><i class="fa-solid fa-wrench text-[8px]"></i>管理ツール</summary>
                    <div class="grid grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mt-3">
                        <a href="/hinata/release_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                            <div class="relative z-10 flex flex-col items-center md:block">
                                <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-compact-disc text-2xl md:text-base"></i></div>
                                <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">リリース管理</h3>
                                <p class="hidden md:block text-sm text-slate-400 leading-relaxed">シングル・アルバム情報を管理します。</p>
                            </div>
                        </a>
                        <a href="/hinata/media_member_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                            <div class="relative z-10 flex flex-col items-center md:block">
                                <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-link text-2xl md:text-base"></i></div>
                                <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画・メンバー紐付け</h3>
                                <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画に出演メンバーを紐づけます。</p>
                            </div>
                        </a>
                        <a href="/hinata/media_song_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                            <div class="relative z-10 flex flex-col items-center md:block">
                                <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-music text-2xl md:text-base"></i></div>
                                <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画・楽曲紐付け</h3>
                                <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画（MV等）と楽曲を紐づけます。</p>
                            </div>
                        </a>
                        <a href="/hinata/media_settings_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                            <div class="relative z-10 flex flex-col items-center md:block">
                                <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-sliders text-2xl md:text-base"></i></div>
                                <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画設定</h3>
                                <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画のカテゴリなどを変更します。</p>
                            </div>
                        </a>
                        <a href="/hinata/media_register.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                            <div class="relative z-10 flex flex-col items-center md:block">
                                <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-circle-plus text-2xl md:text-base"></i></div>
                                <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">メディア登録</h3>
                                <p class="hidden md:block text-sm text-slate-400 leading-relaxed">YouTube検索やURL貼り付けで動画を登録します。</p>
                            </div>
                        </a>
                    </div>
                </details>
                <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../../components/video_modal.php'; ?>

    <script>
    // 推しエリア切り替え
    var OshiPortal = {
        data: <?= json_encode($oshiByLevel, JSON_UNESCAPED_UNICODE) ?>,
        newItemsByMember: <?= json_encode($oshiLatestItemByMember ?? [], JSON_UNESCAPED_UNICODE) ?>,
        imgSrc: function(url) {
            if (!url) return '';
            return url.charAt(0) === '/' ? url : '/assets/img/members/' + url;
        },
        switchMain: function(level) {
            var d = this.data[level];
            if (!d) return;
            var label = {9:'最推し',8:'2推し',7:'3推し'}[level] || '';
            var el = function(id) { return document.getElementById(id); };
            var mainImg = el('oshiMainImg');
            if (mainImg && mainImg.tagName === 'IMG' && d.image_url) {
                mainImg.src = this.imgSrc(d.image_url);
            }
            if (el('oshiMainLabel')) el('oshiMainLabel').textContent = label;
            if (el('oshiMainName')) el('oshiMainName').textContent = d.name || '';
            if (el('oshiMainGen')) el('oshiMainGen').textContent = (d.generation || '') + '期生';
            if (el('oshiMainLink')) el('oshiMainLink').href = '/hinata/member.php?id=' + d.member_id;

            var blogEl = el('oshiMainBlog');
            if (blogEl) {
                if (d.blog_url) { blogEl.href = d.blog_url; blogEl.classList.remove('hidden'); }
                else { blogEl.classList.add('hidden'); }
            }
            var instaEl = el('oshiMainInsta');
            if (instaEl) {
                if (d.insta_url) { instaEl.href = d.insta_url; instaEl.classList.remove('hidden'); }
                else { instaEl.classList.add('hidden'); }
            }

            document.querySelectorAll('.oshi-sub-card').forEach(function(card) {
                var cardLevel = parseInt(card.dataset.level);
                card.classList.toggle('bg-amber-50', cardLevel === level);
                card.classList.toggle('shadow-sm', cardLevel === level);
                var img = card.querySelector('.rounded-full');
                if (img) {
                    img.classList.toggle('ring-2', cardLevel === level);
                    img.classList.toggle('ring-amber-400', cardLevel === level);
                }
            });

            var wrap = el('oshiMainNewItemWrap');
            var content = el('oshiMainNewItemContent');
            if (wrap && content) {
                var items = this.newItemsByMember[d.member_id] || this.newItemsByMember[String(d.member_id)];
                if (!Array.isArray(items)) items = items ? [items] : [];
                var icons = {blog:'fa-blog text-sky-500',news:'fa-newspaper text-rose-500',schedule:'fa-calendar text-violet-500',event:'fa-calendar text-violet-500',video:'fa-play text-red-500'};
                var esc = function(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; };
                if (items.length > 0) {
                    wrap.classList.remove('hidden');
                    var html = '';
                    items.forEach(function(ni) {
                        var ic = icons[ni.type] || 'fa-circle-info text-slate-400';
                        var title = ni.title || '';
                        var link = ni.url ? '<a href="'+esc(ni.url)+'" target="_blank" rel="noopener" class="flex-1 min-w-0 text-slate-700 hover:text-amber-600 line-clamp-2 break-words" title="'+esc(title)+'">'+esc(title)+'</a>' : '<span class="flex-1 min-w-0 text-slate-700 line-clamp-2 break-words" title="'+esc(title)+'">'+esc(title)+'</span>';
                        var m = (ni.event_date || '').match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
                        var date = m ? parseInt(m[2],10) + '/' + parseInt(m[3],10) : '';
                        html += '<div class="flex items-start gap-2 text-sm min-w-0 rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 hover:border-amber-300 hover:bg-amber-50/50 transition"><span class="text-xs text-slate-400 shrink-0">'+esc(date)+'</span><i class="fa-solid '+ic+' w-4 shrink-0 mt-0.5"></i>'+link+'</div>';
                    });
                    content.innerHTML = html;
                } else {
                    wrap.classList.add('hidden');
                }
            }
        }
    };

    // YouTubeカルーセル
    var YtCarousel = {
        scroll: function(cardsId, dir) {
            var el = document.getElementById(cardsId);
            if (!el) return;
            var cardW = el.querySelector('.yt-card') ? el.querySelector('.yt-card').offsetWidth : 260;
            el.scrollBy({ left: dir * (cardW + 12) * 2, behavior: 'smooth' });
        },
        updateArrows: function(cardsId) {
            var el = document.getElementById(cardsId);
            if (!el) return;
            var wrap = el.closest('.yt-scroll-wrap');
            if (!wrap) return;
            var leftBtn = wrap.querySelector('.yt-arrow.left');
            var rightBtn = wrap.querySelector('.yt-arrow.right');
            if (!leftBtn || !rightBtn) return;
            var update = function() {
                leftBtn.classList.toggle('hidden', el.scrollLeft <= 4);
                rightBtn.classList.toggle('hidden', el.scrollLeft + el.clientWidth >= el.scrollWidth - 4);
            };
            el.addEventListener('scroll', update, { passive: true });
            update();
        },
        videos: [],
        esc: function(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; },
        renderCard: function(v) {
            var thumb = v.thumbnail_url || ('https://img.youtube.com/vi/' + v.video_id + '/mqdefault.jpg');
            var title = this.esc(v.title || '');
            var date = v.published_at ? v.published_at.substring(0, 10) : '';
            var idx = this.videos.length;
            this.videos.push({
                media_key: v.video_id, platform: 'youtube', title: v.title || '',
                category: '', upload_date: v.published_at || '', thumbnail_url: thumb, description: '', sub_key: ''
            });
            return '<div class="yt-card" onclick="openPortalVideo(' + idx + ', event)">' +
                '<div class="aspect-video rounded-lg overflow-hidden bg-slate-200 mb-2 shadow-sm relative group">' +
                '<img src="' + thumb + '" class="w-full h-full object-cover" loading="lazy" alt="">' +
                '<div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center"><i class="fa-solid fa-play text-white text-xl opacity-0 group-hover:opacity-100 transition drop-shadow-lg"></i></div>' +
                '</div>' +
                '<h3 class="text-xs font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5">' + title + '</h3>' +
                '<p class="text-[10px] text-slate-400">' + date + '</p></div>';
        },
        loadChannel: function(idx) {
            var cardsId = 'ytCards' + idx;
            var loadingId = 'ytLoading' + idx;
            var cardsEl = document.getElementById(cardsId);
            var loadingEl = document.getElementById(loadingId);
            if (!cardsEl) return;
            var channelId = cardsEl.dataset.channelId;
            var self = YtCarousel;
            fetch('/hinata/api/youtube_latest.php?channel_id=' + encodeURIComponent(channelId))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.status === 'success' && res.data && res.data.length > 0) {
                        cardsEl.innerHTML = res.data.map(function(v) { return self.renderCard(v); }).join('');
                        if (loadingEl) loadingEl.classList.add('hidden');
                        cardsEl.classList.remove('hidden');
                        self.updateArrows(cardsId);
                    } else {
                        if (loadingEl) loadingEl.innerHTML = '<p class="text-xs text-slate-400 py-4">動画を取得できませんでした</p>';
                    }
                })
                .catch(function() {
                    if (loadingEl) loadingEl.innerHTML = '<p class="text-xs text-slate-400 py-4">動画の読み込みに失敗しました</p>';
                });
        },
        init: function() {
            this.loadChannel(0);
            this.loadChannel(1);
        }
    };

    // TikTokカルーセル
    var TkCarousel = {
        videos: [],
        esc: function(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; },
        scroll: function(cardsId, dir) {
            var el = document.getElementById(cardsId);
            if (!el) return;
            var cardW = el.querySelector('.tk-card') ? el.querySelector('.tk-card').offsetWidth : 160;
            el.scrollBy({ left: dir * (cardW + 12) * 2, behavior: 'smooth' });
        },
        updateArrows: function(cardsId) {
            var el = document.getElementById(cardsId);
            if (!el) return;
            var wrap = el.closest('.yt-scroll-wrap');
            if (!wrap) return;
            var leftBtn = wrap.querySelector('.yt-arrow.left');
            var rightBtn = wrap.querySelector('.yt-arrow.right');
            if (!leftBtn || !rightBtn) return;
            var update = function() {
                leftBtn.classList.toggle('hidden', el.scrollLeft <= 4);
                rightBtn.classList.toggle('hidden', el.scrollLeft + el.clientWidth >= el.scrollWidth - 4);
            };
            el.addEventListener('scroll', update, { passive: true });
            update();
        },
        renderCard: function(v) {
            var thumb = v.thumbnail_url || '';
            var title = this.esc(v.title || '');
            var date = v.upload_date ? v.upload_date.substring(0, 10) : '';
            var idx = YtCarousel.videos.length;
            YtCarousel.videos.push({
                media_key: v.media_key, platform: 'tiktok', title: v.title || '',
                category: '', upload_date: v.upload_date || '', thumbnail_url: thumb, description: v.description || '', sub_key: v.sub_key || ''
            });
            var thumbHtml = thumb
                ? '<img src="' + thumb + '" class="w-full h-full object-cover" loading="lazy" alt="">'
                : '<div class="w-full h-full flex items-center justify-center"><i class="fa-brands fa-tiktok text-3xl text-slate-300"></i></div>';
            return '<div class="tk-card" onclick="openPortalVideo(' + idx + ', event)">' +
                '<div class="aspect-[9/16] rounded-lg overflow-hidden bg-slate-200 mb-2 shadow-sm relative group">' +
                thumbHtml +
                '<div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center"><i class="fa-solid fa-play text-white text-xl opacity-0 group-hover:opacity-100 transition drop-shadow-lg"></i></div>' +
                '</div>' +
                '<h3 class="text-xs font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5">' + title + '</h3>' +
                '<p class="text-[10px] text-slate-400">' + date + '</p></div>';
        },
        load: function() {
            var cardsEl = document.getElementById('tkCards');
            var loadingEl = document.getElementById('tkLoading');
            if (!cardsEl) return;
            var self = this;
            fetch('/hinata/api/tiktok_latest.php')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.status === 'success' && res.data && res.data.length > 0) {
                        cardsEl.innerHTML = res.data.map(function(v) { return self.renderCard(v); }).join('');
                        if (loadingEl) loadingEl.classList.add('hidden');
                        cardsEl.classList.remove('hidden');
                        self.updateArrows('tkCards');
                    } else {
                        if (loadingEl) loadingEl.innerHTML = '<p class="text-xs text-slate-400 py-4">動画を取得できませんでした</p>';
                    }
                })
                .catch(function() {
                    if (loadingEl) loadingEl.innerHTML = '<p class="text-xs text-slate-400 py-4">動画の読み込みに失敗しました</p>';
                });
        },
        init: function() {
            this.load();
        }
    };

    function openPortalVideo(idx, ev) {
        var video = YtCarousel.videos[idx];
        if (!video) return;
        openVideoModalWithData(video, ev);
    }

    function playReleaseMv(mediaKey, title, ev) {
        openVideoModalWithData({
            media_key: mediaKey, platform: 'youtube', title: title,
            category: 'MV', upload_date: '', thumbnail_url: '', description: '', sub_key: ''
        }, ev);
    }

    var ReleasePlayer = {
        currentUrl: null,
        play: function(type, url, title) {
            if (!url) return;
            var player = document.getElementById('releaseTrackMiniPlayer');
            var embed = document.getElementById('releaseTrackEmbed');
            var titleEl = document.getElementById('releaseTrackTitle');
            if (!player || !embed) return;
            if (this.currentUrl === url) { this.close(); return; }
            this.currentUrl = url;
            titleEl.textContent = title;
            var iframe = '';
            if (type === 'apple') {
                var eu = url.replace('music.apple.com', 'embed.music.apple.com');
                iframe = '<iframe src="' + eu + '" height="175" frameborder="0" allow="autoplay *; encrypted-media *;" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation-by-user-activation" style="width:100%;border-radius:12px;overflow:hidden;background:transparent;"></iframe>';
            } else if (type === 'spotify') {
                var eu = url.replace('open.spotify.com/', 'open.spotify.com/embed/');
                iframe = '<iframe src="' + eu + '" height="152" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="width:100%;border-radius:12px;"></iframe>';
            }
            embed.innerHTML = iframe;
            player.classList.remove('hidden');
            player.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },
        close: function() {
            var player = document.getElementById('releaseTrackMiniPlayer');
            var embed = document.getElementById('releaseTrackEmbed');
            if (player) player.classList.add('hidden');
            if (embed) embed.innerHTML = '';
            this.currentUrl = null;
        }
    };

    function switchYtTab(idx) {
        document.querySelectorAll('.yt-tab-panel').forEach(function(p) {
            p.style.display = parseInt(p.dataset.panel) === idx ? '' : 'none';
        });
        document.querySelectorAll('.yt-tab').forEach(function(btn) {
            var active = parseInt(btn.dataset.idx) === idx;
            btn.className = 'yt-tab text-[10px] font-bold px-3 py-1 rounded-full transition ' +
                (active ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200');
        });
        requestAnimationFrame(function() {
            YtCarousel.updateArrows('ytCards' + idx);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        YtCarousel.init();
        TkCarousel.init();
        YtCarousel.updateArrows('blogCards');
        YtCarousel.updateArrows('releaseMvCards');
    });

    document.getElementById('mobileMenuBtn').onclick = function() {
        document.getElementById('sidebar').classList.add('mobile-open');
    };
    </script>
</body>
</html>
