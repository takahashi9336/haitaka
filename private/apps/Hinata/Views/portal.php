<?php
/**
 * 日向坂ポータル View（推しエリア・YTカルーセル・リリース情報付き）
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

use App\Hinata\Model\FavoriteModel;

$oshiByLevel = [];
foreach ($oshiSummary as $o) {
    $oshiByLevel[(int)$o['level']] = $o;
}
$hasOshi = !empty($oshiSummary);
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
                        <div class="flex flex-col md:flex-row">
                            <!-- メインエリア（選択中の推し） -->
                            <div id="oshiMainArea" class="oshi-main-area flex-1 p-6">
                                <?php
                                $mainOshi = $oshiByLevel[9] ?? $oshiByLevel[8] ?? $oshiByLevel[7] ?? null;
                                if ($mainOshi):
                                    $mainLevel = (int)$mainOshi['level'];
                                    $mainLabel = FavoriteModel::LEVEL_LABELS[$mainLevel] ?? '';
                                ?>
                                <div class="flex gap-5">
                                    <div class="w-28 h-28 md:w-36 md:h-36 rounded-xl overflow-hidden bg-slate-100 shrink-0 shadow-md">
                                        <?php if ($mainOshi['image_url']): ?>
                                        <img id="oshiMainImg" src="/assets/img/members/<?= htmlspecialchars($mainOshi['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                        <?php else: ?>
                                        <div id="oshiMainImg" class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-4xl"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span id="oshiMainLabel" class="inline-block text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full mb-2"><?= $mainLabel ?></span>
                                        <h3 id="oshiMainName" class="text-xl md:text-2xl font-black text-slate-800 mb-1"><?= htmlspecialchars($mainOshi['name']) ?></h3>
                                        <p id="oshiMainGen" class="text-xs text-slate-400 mb-3"><?= htmlspecialchars($mainOshi['generation']) ?>期生</p>
                                        <div id="oshiMainDetails" class="space-y-2 text-xs">
                                            <?php if (!empty($mainOshi['blog_url'])): ?>
                                            <a id="oshiMainBlog" href="<?= htmlspecialchars($mainOshi['blog_url']) ?>" target="_blank" class="flex items-center gap-2 text-sky-600 hover:text-sky-700"><i class="fa-solid fa-blog w-4"></i>公式ブログ</a>
                                            <?php endif; ?>
                                            <?php if (!empty($mainOshi['insta_url'])): ?>
                                            <a id="oshiMainInsta" href="<?= htmlspecialchars($mainOshi['insta_url']) ?>" target="_blank" class="flex items-center gap-2 text-pink-600 hover:text-pink-700"><i class="fa-brands fa-instagram w-4"></i>Instagram</a>
                                            <?php endif; ?>
                                            <?php if (!empty($mainOshi['latest_video'])): ?>
                                            <a id="oshiMainVideo" href="https://www.youtube.com/watch?v=<?= htmlspecialchars($mainOshi['latest_video']['media_key']) ?>" target="_blank" class="flex items-center gap-2 text-red-600 hover:text-red-700"><i class="fa-brands fa-youtube w-4"></i><span class="truncate"><?= htmlspecialchars(mb_strimwidth($mainOshi['latest_video']['video_title'], 0, 30, '...')) ?></span></a>
                                            <?php endif; ?>
                                            <?php if (!empty($mainOshi['next_event'])): ?>
                                            <div id="oshiMainEvent" class="flex items-center gap-2 text-slate-600"><i class="fa-solid fa-calendar w-4 text-slate-400"></i><?= htmlspecialchars($mainOshi['next_event']['event_name']) ?> (<?= htmlspecialchars($mainOshi['next_event']['event_date']) ?>)</div>
                                            <?php endif; ?>
                                            <?php if ($mainOshi['song_count'] > 0): ?>
                                            <div id="oshiMainSongs" class="flex items-center gap-2 text-slate-600"><i class="fa-solid fa-music w-4 text-slate-400"></i>参加楽曲 <?= $mainOshi['song_count'] ?> 曲</div>
                                            <?php endif; ?>
                                        </div>
                                        <a id="oshiMainLink" href="/hinata/member.php?id=<?= $mainOshi['member_id'] ?>" class="inline-flex items-center gap-1.5 mt-4 px-4 py-1.5 rounded-lg text-xs font-black text-white bg-amber-500 hover:bg-amber-600 shadow-sm hover:shadow transition-all"><i class="fa-solid fa-arrow-right text-[10px]"></i>推し個別ページへ</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- サブエリア（2推し・3推し） -->
                            <div class="w-full md:w-40 border-t md:border-t-0 md:border-l border-slate-100 p-3 flex md:flex-col gap-3 justify-center">
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
                                <div class="oshi-sub-card flex md:flex-col items-center gap-2 md:gap-1 p-2 rounded-lg <?= $isMain ? 'bg-amber-50 shadow-sm' : 'hover:bg-slate-50' ?>"
                                     data-level="<?= $ss['level'] ?>"
                                     onclick="OshiPortal.switchMain(<?= $ss['level'] ?>)">
                                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full overflow-hidden bg-slate-100 shrink-0 <?= $isMain ? 'ring-2 ring-amber-400' : '' ?>">
                                        <?php if ($so['image_url']): ?>
                                        <img src="/assets/img/members/<?= htmlspecialchars($so['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                        <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-sm"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[8px] font-black text-<?= $ss['colorClass'] ?>-500"><?= $ss['label'] ?></p>
                                        <p class="text-[10px] font-bold text-slate-700"><?= htmlspecialchars($so['name']) ?></p>
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

                <!-- YouTube最新動画カルーセル -->
                <section class="mb-10 space-y-6">
                    <?php
                    $channels = [
                        ['id' => 'UCOB24f8lQBCnVqPZXOkVpOg', 'name' => '日向坂ちゃんねる', 'icon' => 'fa-brands fa-youtube', 'color' => 'red'],
                        ['id' => 'UCR0V48DJyWbwEAdxLL5FjxA', 'name' => '日向坂46公式チャンネル', 'icon' => 'fa-solid fa-play-circle', 'color' => 'sky'],
                    ];
                    foreach ($channels as $ci => $ch):
                    ?>
                    <div class="yt-section">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="<?= $ch['icon'] ?> text-<?= $ch['color'] ?>-500"></i>
                            <h2 class="text-sm font-bold text-slate-700"><?= $ch['name'] ?></h2>
                            <span class="text-[10px] text-slate-400 ml-auto">最新動画</span>
                        </div>
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

                <!-- 最新リリース情報 -->
                <?php if ($latestRelease): ?>
                <section class="mb-10">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-compact-disc text-violet-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">最新リリース</h2>
                    </div>
                    <a href="/hinata/release.php?id=<?= $latestRelease['id'] ?>" class="block bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5 hover:shadow-md transition group">
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
                </section>
                <?php endif; ?>

                <!-- アプリ一覧 -->
                <section class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-grip text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-500 tracking-wider">アプリ</h2>
                </div>
                <div class="grid grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- ミーグリネタ帳 -->
                    <a href="/hinata/talk.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-book-open text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-comment-dots text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ミーグリネタ帳</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メンバーとの会話ネタや、ミーグリのレポを記録・管理します。</p>
                        </div>
                    </a>

                    <!-- ミーグリ予定 -->
                    <a href="/hinata/meetgreet.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-ticket text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-ticket text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ミーグリ予定</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ミーグリの予定管理とレポを記録します。</p>
                        </div>
                    </a>

                    <!-- イベント -->
                    <a href="/hinata/events.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-calendar-days text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-calendar-check text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">イベント</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ライブやミーグリ、発売日などの重要日程を確認します。</p>
                        </div>
                    </a>

                    <!-- メンバー帳 -->
                    <a href="/hinata/members.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-users text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-address-card text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">メンバー帳</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メンバーのプロフィール、サイリウムカラーなどをチェックします。</p>
                        </div>
                    </a>

                    <!-- 楽曲 -->
                    <a href="/hinata/songs.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-music text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-music text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">楽曲</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">リリース一覧・全曲一覧・楽曲の紹介を確認します。</p>
                        </div>
                    </a>

                    <!-- 動画一覧 -->
                    <a href="/hinata/media_list.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-video text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-play-circle text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画一覧</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">登録されたすべての動画を閲覧します。</p>
                        </div>
                    </a>

                    <?php if (in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                    <a href="/hinata/release_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-compact-disc text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-compact-disc text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">リリース管理</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">シングル・アルバム情報を管理します。</p>
                        </div>
                    </a>
                    <a href="/hinata/media_member_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-link text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-link text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画・メンバー紐付け</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画に出演メンバーを紐づけます。</p>
                        </div>
                    </a>
                    <a href="/hinata/media_song_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-music text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-music text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画・楽曲紐付け</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画（MV等）と楽曲を紐づけます。</p>
                        </div>
                    </a>
                    <a href="/hinata/media_settings_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-sliders text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-sliders text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画設定</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画のカテゴリなどを変更します。</p>
                        </div>
                    </a>
                    <a href="/hinata/media_register.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>"><i class="fa-solid fa-circle-plus text-6xl"></i></div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>"><i class="fa-solid fa-circle-plus text-2xl md:text-base"></i></div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">メディア登録</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">YouTube検索やURL貼り付けで動画を登録します。</p>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
                </section>
            </div>
        </div>
    </main>

    <script>
    // 推しエリア切り替え
    var OshiPortal = {
        data: <?= json_encode($oshiByLevel, JSON_UNESCAPED_UNICODE) ?>,
        switchMain: function(level) {
            var d = this.data[level];
            if (!d) return;
            var label = {9:'最推し',8:'2推し',7:'3推し'}[level] || '';
            var el = function(id) { return document.getElementById(id); };
            var mainImg = el('oshiMainImg');
            if (mainImg && mainImg.tagName === 'IMG' && d.image_url) {
                mainImg.src = '/assets/img/members/' + d.image_url;
            }
            if (el('oshiMainLabel')) el('oshiMainLabel').textContent = label;
            if (el('oshiMainName')) el('oshiMainName').textContent = d.name || '';
            if (el('oshiMainGen')) el('oshiMainGen').textContent = (d.generation || '') + '期生';
            if (el('oshiMainLink')) el('oshiMainLink').href = '/hinata/member.php?id=' + d.member_id;

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
        esc: function(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; },
        renderCard: function(v) {
            var thumb = v.thumbnail_url || ('https://img.youtube.com/vi/' + v.video_id + '/mqdefault.jpg');
            var title = this.esc(v.title || '');
            var date = v.published_at ? v.published_at.substring(0, 10) : '';
            return '<a href="https://www.youtube.com/watch?v=' + v.video_id + '" target="_blank" class="yt-card block">' +
                '<div class="aspect-video rounded-lg overflow-hidden bg-slate-200 mb-2 shadow-sm relative group">' +
                '<img src="' + thumb + '" class="w-full h-full object-cover" loading="lazy" alt="">' +
                '<div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center"><i class="fa-solid fa-play text-white text-xl opacity-0 group-hover:opacity-100 transition"></i></div>' +
                '</div>' +
                '<h3 class="text-xs font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5">' + title + '</h3>' +
                '<p class="text-[10px] text-slate-400">' + date + '</p></a>';
        },
        loadChannel: function(idx) {
            var cardsId = 'ytCards' + idx;
            var loadingId = 'ytLoading' + idx;
            var cardsEl = document.getElementById(cardsId);
            var loadingEl = document.getElementById(loadingId);
            if (!cardsEl) return;
            var channelId = cardsEl.dataset.channelId;
            fetch('/hinata/api/youtube_latest.php?channel_id=' + encodeURIComponent(channelId))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.status === 'success' && res.data && res.data.length > 0) {
                        var self = YtCarousel;
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

    document.addEventListener('DOMContentLoaded', function() {
        YtCarousel.init();
    });

    document.getElementById('mobileMenuBtn').onclick = function() {
        document.getElementById('sidebar').classList.add('mobile-open');
    };
    </script>
</body>
</html>
