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

$releaseIsNew = !empty($latestRelease['release_date'])
    && (strtotime($latestRelease['release_date']) >= strtotime('-60 days'));

// admin限定プレビューモード（新UIの段階確認用）
$isAdmin = in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true);
// 本運用: 新デザイン（見た目）は常時有効化
$isMockUi = true;

// 本運用: レイアウトv2は常時有効化（段階反映フラグは廃止）
$isLayoutV2 = true;

function oshiImgSrc(?string $imageUrl): string {
    if (!$imageUrl) return '';
    return str_starts_with($imageUrl, '/') ? htmlspecialchars($imageUrl) : '/assets/img/members/' . htmlspecialchars($imageUrl);
}

function portalTopicBadge(array $t): array {
    $url = (string)($t['url'] ?? '');
    $topicType = (string)($t['topic_type'] ?? '');

    $label = 'TOPICS';
    if ($url && preg_match('~line\\.me|line\\.naver\\.jp~i', $url)) {
        $label = 'LINE';
    } elseif ($topicType === 'news') {
        $label = 'INFO';
    }

    if ($label === 'LINE') return ['label' => 'LINE', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'];
    if ($label === 'INFO') return ['label' => 'INFO', 'bg' => 'bg-sky-100', 'text' => 'text-sky-700'];
    return ['label' => 'TOPICS', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
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
        .app-card:hover { transform: translateY(-2px); }

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
        /* YouTube: グリッド（2行）＋横スクロール */
        .yt-grid-scroll {
            display: grid;
            grid-template-rows: repeat(2, minmax(0, 1fr));
            grid-auto-flow: column;
            /* 初期表示の先頭4件（2列×2行）がエリア内に収まる */
            grid-auto-columns: calc((100% - 12px) / 2);
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 8px;
        }
        @media (min-width: 768px) {
            /* PCは少しだけ広めにして見やすく */
            .yt-grid-scroll { grid-auto-columns: calc((100% - 12px) / 2); }
        }
        .yt-grid-scroll::-webkit-scrollbar { display: none; }
        .yt-arrow { position: absolute; top: 50%; transform: translateY(-50%); z-index: 30; width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; color: #475569; transition: all 0.2s; }
        .yt-arrow:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #1e293b; }
        .yt-arrow.left { left: -4px; }
        .yt-arrow.right { right: -4px; }
        .yt-arrow.hidden { display: none; }
        /* TikTokカルーセル（ショート動画用縦長カード） */
        .tk-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .tk-scroll::-webkit-scrollbar { display: none; }
        .tk-card { flex: 0 0 120px; transition: transform 0.2s ease; cursor: pointer; }
        @media (min-width: 768px) { .tk-card { flex: 0 0 140px; } }
        .tk-card:hover { transform: translateY(-4px); }
        /* TikTok: グリッド（2行×初期3列）＋横スクロール */
        .tk-grid-scroll {
            display: grid;
            grid-template-rows: repeat(2, minmax(0, 1fr));
            grid-auto-flow: column;
            grid-auto-columns: calc((100% - 24px) / 3);
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 8px;
        }
        .tk-grid-scroll::-webkit-scrollbar { display: none; }
        .tk-grid-scroll .tk-card { flex: none; width: 100%; }
        .blog-card { flex: 0 0 130px; transition: transform 0.2s ease; }
        @media (min-width: 768px) { .blog-card { flex: 0 0 150px; } }
        .blog-card:hover { transform: translateY(-3px); }
        /* 誕生日横スクロール */
        .bd-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .bd-scroll::-webkit-scrollbar { display: none; }
        @keyframes skeletonPulse { 0%,100% { opacity: 0.4; } 50% { opacity: 0.7; } }
        .skeleton-card { animation: skeletonPulse 1.5s ease-in-out infinite; }
        .oshi-mirror-btn { position: relative; overflow: hidden; }
        .oshi-mirror-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            animation: mirror-sweep 2s ease-in-out infinite;
        }
        @keyframes mirror-sweep {
            0%,100% { transform: translateY(-100%); opacity: 0; }
            50% { transform: translateY(100%); opacity: 1; }
        }
        .release-expand-content { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .release-accordion.expanded .release-expand-content { max-height: 3000px; }
        .release-chevron { transition: transform 0.3s ease; }
        .release-accordion.expanded .release-chevron { transform: rotate(180deg); }

        /* ポータルUI（本番スコープ：hinata-portal） */
        .hinata-portal { --mock-accent: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .hinata-portal .mock-mesh {
            background-image:
                radial-gradient(at 20% 10%, rgba(255, 225, 199, 0.95) 0px, transparent 45%),
                radial-gradient(at 80% 0%, rgba(205, 230, 255, 0.95) 0px, transparent 40%),
                radial-gradient(at 50% 40%, rgba(255, 243, 220, 0.95) 0px, transparent 50%);
        }
        .hinata-portal .mock-glass {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .hinata-portal .mock-card {
            border-radius: 24px;
            border-color: rgba(255,255,255,0.65) !important;
            box-shadow: 0 10px 30px -12px rgba(0,0,0,0.18);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .hinata-portal .mock-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 45px -18px rgba(0,0,0,0.22);
        }
        .hinata-portal .mock-card-static {
            border-radius: 24px;
            border-color: rgba(255,255,255,0.65) !important;
            box-shadow: 0 10px 30px -12px rgba(0,0,0,0.18);
            transition: box-shadow .2s ease, border-color .2s ease;
        }
        .hinata-portal .mock-card-static:hover {
            box-shadow: 0 10px 30px -12px rgba(0,0,0,0.18);
        }
        .hinata-portal .mock-pop-item {
            position: relative;
            will-change: transform;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .hinata-portal .mock-pop-item:hover,
        .hinata-portal .mock-pop-item:focus,
        .hinata-portal .mock-pop-item:focus-visible {
            transform: translateY(-4px);
            box-shadow: 0 18px 45px -18px rgba(0,0,0,0.22);
            z-index: 2;
        }
        .hinata-portal .mock-scroll-row {
            scrollbar-width: none;
        }
        .hinata-portal .mock-scroll-row::-webkit-scrollbar {
            display: none;
        }
        .hinata-portal .mock-media-card {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.65);
            box-shadow: 0 10px 26px -14px rgba(0,0,0,0.25);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .hinata-portal .mock-media-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 38px -18px rgba(0,0,0,0.28);
        }
        .hinata-portal .mock-header-search {
            background: rgba(241, 245, 249, 0.65);
            border: 1px solid rgba(226, 232, 240, 0.85);
        }
        .hinata-portal .hinata-portal-card {
            border-radius: 24px;
            border-color: rgba(255,255,255,0.65) !important;
            box-shadow: 0 12px 32px -18px rgba(0,0,0,0.22);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .hinata-portal .hinata-portal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 48px -22px rgba(0,0,0,0.26);
        }
        .hinata-portal .hinata-portal-card .card-icon {
            border-radius: 18px;
            background: rgba(255,255,255,0.72) !important;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.85), 0 10px 24px -18px rgba(0,0,0,0.35);
            color: var(--mock-accent) !important;
        }
        .hinata-portal .hinata-portal-card:hover .card-icon {
            background: rgba(255,255,255,0.80) !important;
            color: var(--mock-accent) !important;
        }
        .hinata-portal .hinata-portal-card .card-deco {
            opacity: 0.08;
        }
        /* アプリカードは「影なし＋少し浮く」を維持 */
        .hinata-portal .app-card.hinata-portal-card { box-shadow: none; }
        .hinata-portal .app-card.hinata-portal-card:hover { box-shadow: none; }

        /* 次のイベント（モック寄せ） */
        .next-event-box {
            background: linear-gradient(135deg, rgba(255, 245, 246, 0.95), rgba(255, 239, 227, 0.92));
            border: 1px solid rgba(255, 255, 255, 0.72);
            box-shadow: none;
        }
        .v2-nextEvent { align-self: stretch; }
        .v2-nextEvent .next-event-box {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        /* TOPICS: 次のイベントと高さを揃える（同一行でストレッチ） */
        .v2-topics { align-self: stretch; }
        .v2-topics > div {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        /* 狭幅でもTOPICS外枠が小さく見えないよう最低高さを確保 */
        .v2-topics > div { min-height: 280px; }
        @media (min-width: 768px) {
            /* TOPICS/次のイベントは最低高さも揃える */
            .v2-topics > div,
            .v2-nextEvent .next-event-box {
                min-height: 300px;
            }
        }
        .next-event-bar {
            height: 8px;
            border-radius: 999px;
            background: rgba(255,255,255,0.85);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.8);
            overflow: hidden;
        }
        .next-event-bar > span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(249, 115, 22, 0.85), rgba(236, 72, 153, 0.85));
        }

        /* Layout v2: 画面上の配置（DOMは極力維持してCSSで並び替え） */
        .v2-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            min-width: 0;
        }
        /* 外枠カード間の余白は v2-grid の gap に統一（mb-* の混在を無効化） */
        .v2-grid > * { margin: 0 !important; }
        /* SPは推し→TOPICS→次のイベントの順に固定（DOM順の影響を排除） */
        .v2-oshi { grid-column: 1 / -1; grid-row: 1; }
        .v2-topics { grid-column: 1 / -1; grid-row: 2; }
        .v2-nextEvent { grid-column: 1 / -1; grid-row: 3; }
        /* SPで誕生日が最新リリースより上に来ないよう順序固定 */
        .v2-release { grid-column: 1 / -1; grid-row: 4; }
        .v2-birthday { grid-column: 1 / -1; grid-row: 5; }
        @media (min-width: 768px) {
            .v2-grid {
                grid-template-columns: repeat(10, minmax(0, 1fr));
                gap: 12px;
                align-items: start;
            }
        }
        .v2-grid > * {
            min-width: 0;
        }
        @media (min-width: 768px) {
            .v2-oshi { grid-column: 1 / -1; grid-row: 1; }
            .v2-topics { grid-column: 1 / 8; grid-row: 2; }
            .v2-nextEvent { grid-column: 8 / -1; grid-row: 2; }
            .v2-deadlines { grid-column: 1 / -1; grid-row: 3; }
            .v2-announcements { grid-column: 1 / -1; grid-row: 4; }
            .v2-release { grid-column: 1 / 8; grid-row: 5; }
            .v2-birthday { grid-column: 8 / -1; grid-row: 5; }
            .v2-meetgreet { grid-column: 1 / -1; grid-row: 6; }
            .v2-todayInHistory { grid-column: 1 / -1; grid-row: 7; }
            .v2-blog { grid-column: 1 / -1; grid-row: 8; }
            .v2-youtube { grid-column: 1 / 6; grid-row: 9; }
            .v2-tiktok { grid-column: 6 / -1; grid-row: 9; }
            .v2-apps { grid-column: 1 / -1; grid-row: 10; }
            .v2-sns { grid-column: 1 / -1; grid-row: 11; }
        }
        .v2-release, .v2-birthday { align-self: stretch; }
        .v2-youtube, .v2-tiktok { align-self: stretch; }

        /* 誕生日：1000px未満は下に回す（リリースを横一杯に） */
        @media (min-width: 768px) and (max-width: 999px) {
            .v2-release { grid-column: 1 / -1; grid-row: 5; }
            .v2-birthday { grid-column: 1 / -1; grid-row: 6; }
            .v2-meetgreet { grid-row: 7; }
            .v2-todayInHistory { grid-row: 8; }
            .v2-blog { grid-row: 9; }
            .v2-youtube { grid-column: 1 / 6; grid-row: 10; }
            .v2-tiktok { grid-column: 6 / -1; grid-row: 10; }
            .v2-apps { grid-row: 11; }
            .v2-sns { grid-row: 12; }
        }

        /* Layout v2: アプリカードのアイコン配色（モック寄せ） */
        .app-icon-surface {
            background: rgba(255,255,255,0.70);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.85), 0 10px 24px -18px rgba(0,0,0,0.35);
        }
        .app-icon-text { color: var(--mock-accent); }
        .app-icon-grad-sky { background: linear-gradient(135deg, rgba(186,230,253,0.9), rgba(147,197,253,0.9)); }
        .app-icon-grad-pink { background: linear-gradient(135deg, rgba(252,231,243,0.9), rgba(253,164,175,0.9)); }
        .app-icon-grad-amber { background: linear-gradient(135deg, rgba(254,243,199,0.9), rgba(253,186,116,0.9)); }
        .app-icon-grad-violet { background: linear-gradient(135deg, rgba(237,233,254,0.9), rgba(196,181,253,0.9)); }
        .app-icon-grad-emerald { background: linear-gradient(135deg, rgba(209,250,229,0.9), rgba(110,231,183,0.9)); }
        .app-icon-grad-rose { background: linear-gradient(135deg, rgba(255,228,230,0.9), rgba(253,164,175,0.9)); }
        .app-icon-grad-slate { background: linear-gradient(135deg, rgba(226,232,240,0.9), rgba(203,213,225,0.9)); }

        /* 推し：次の出演/参加楽曲/最新ブログ（狭幅は必要幅で折り返し） */
        .oshi-kpi { flex: 0 1 220px; min-width: 180px; max-width: 100%; }
        .oshi-kpi-song { flex: 0 0 90px; min-width: 90px; }
        @media (max-width: 420px) { .oshi-kpi:not(.oshi-kpi-song) { flex-basis: 100%; min-width: 0; } }

        /* 最新ブログの下に「最新ニュース」「新着動画」を配置（常時） */
        .oshi-kpi-wrap #oshiLatestBlogLink { flex: 0 1 360px; min-width: 200px; max-width: 100%; }
        .oshi-kpi-wrap #oshiLatestNewsLink { flex: 1 1 100%; flex-basis: 100%; min-width: 0; max-width: 100%; }
        .oshi-kpi-wrap #oshiLatestVideoLink { flex: 1 1 100%; flex-basis: 100%; min-width: 0; max-width: 100%; }

        /* SP相当：参加楽曲を「次の出演」の右へ、最新ブログは次段へ */
        @media (max-width: 640px) {
            .oshi-kpi-wrap { align-items: stretch; }
            .oshi-kpi-wrap #oshiNextAppearanceCard { order: 1; flex: 1 1 auto; min-width: 0; }
            .oshi-kpi-wrap .oshi-kpi-song { order: 2; flex: 0 0 90px; min-width: 90px; }
            .oshi-kpi-wrap #oshiLatestBlogLink { order: 3; flex-basis: 100%; min-width: 0; }
            .oshi-kpi-wrap #oshiLatestNewsLink { order: 4; flex-basis: 100%; min-width: 0; }
            .oshi-kpi-wrap #oshiLatestVideoLink { order: 5; flex-basis: 100%; min-width: 0; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?> hinata-portal"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

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
            <div class="hidden md:flex items-center gap-2 px-3 py-2 rounded-full w-96 mock-header-search">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input id="portalSearchInput" class="bg-transparent outline-none flex-1 text-sm text-slate-600 placeholder:text-slate-400"
                       placeholder="メンバー・楽曲・ブログを検索…" autocomplete="off">
                <span class="text-[10px] font-bold text-slate-400 bg-white/70 border border-white/60 rounded-full px-2 py-0.5">⌘K</span>
            </div>
            <div class="flex items-center gap-4"></div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-6 custom-scroll">
            <div class="max-w-5xl mx-auto v2-grid">
                <?php if (!empty($topics) || !empty($announcements) || !empty($upcomingDeadlines)): ?>
                <section class="mb-3 v2-topics">
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 font-semibold text-slate-700">
                                <i class="fa-solid fa-bullhorn w-4 h-4 text-orange-500"></i>
                                <h2 class="text-sm">TOPICS</h2>
                            </div>
                            <a href="/hinata/portal_info.php#topics" class="text-xs text-slate-500 hover:text-orange-500 transition">すべて見る →</a>
                            <?php if ($isAdmin): ?>
                            <a href="/hinata/portal_info_admin.php#topics" class="ml-3 text-xs text-slate-500 hover:text-orange-500 transition">管理 <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($topics)): ?>
                        <div class="flex-1 min-h-0">
                            <div class="sr-only">TOPICS</div>
                            <div class="yt-scroll-wrap h-full">
                                <button type="button" class="yt-arrow left hidden" onclick="TopicCarousel.scroll('topicCards', -1, event)" onmousedown="event.preventDefault();event.stopPropagation();"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                                <div id="topicCards" class="topic-scroll flex gap-3 overflow-x-auto items-stretch h-full pb-1 mock-scroll-row" style="scrollbar-width: none; -webkit-overflow-scrolling: touch;">
                                    <?php foreach ($topics as $t): $tb = portalTopicBadge($t); ?>
                                    <?php
                                    $topBg = 'bg-gradient-to-br from-orange-200 to-rose-200';
                                    if (($tb['label'] ?? '') === 'INFO') $topBg = 'bg-gradient-to-br from-sky-200 to-indigo-200';
                                    if (($tb['label'] ?? '') === 'LINE') $topBg = 'bg-gradient-to-br from-emerald-200 to-lime-200';
                                    $img = (string)($t['image_url'] ?? '');
                                    $imgSrc = $img ? (str_starts_with($img, '/') || str_starts_with($img, 'http') ? htmlspecialchars($img) : '/assets/' . htmlspecialchars($img)) : '';
                                    ?>
                                    <a href="<?= !empty($t['url']) ? htmlspecialchars($t['url']) : '#' ?>"
                                       class="topic-card-v2 shrink-0 h-full aspect-[256/145] relative overflow-hidden rounded-xl border <?= $cardBorder ?> bg-slate-100 shadow-sm hover:shadow-md transition-all"
                                       <?= !empty($t['url']) ? ' target="_blank" rel="noopener"' : '' ?>>
                                        <?php if ($imgSrc): ?>
                                            <img src="<?= $imgSrc ?>" class="absolute inset-0 w-full h-full object-cover" loading="lazy" alt="">
                                        <?php else: ?>
                                            <div class="absolute inset-0 <?= $topBg ?>"></div>
                                        <?php endif; ?>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-black/0"></div>
                                        <span class="absolute top-2 left-2 z-10 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black <?= $tb['bg'] ?> <?= $tb['text'] ?> border border-white/60"><?= htmlspecialchars($tb['label']) ?></span>
                                        <div class="absolute inset-x-0 bottom-0 p-2 z-10">
                                            <p class="text-[14px] md:text-sm font-black text-white line-clamp-1 drop-shadow"><?= htmlspecialchars($t['title'] ?? '') ?></p>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="yt-arrow right" onclick="TopicCarousel.scroll('topicCards', 1, event)" onmousedown="event.preventDefault();event.stopPropagation();"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($announcements)): ?>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fa-solid fa-bell <?= $cardIconText ?>"<?= $isThemeHex ? ' style="color:' . htmlspecialchars($themePrimary) . '"' : '' ?>></i>
                                <h3 class="text-[11px] font-black text-slate-600">お知らせ</h3>
                            </div>
                            <div class="flex gap-3 overflow-x-auto overflow-y-visible pt-1 pb-2 mock-scroll-row" style="scrollbar-width: none; -webkit-overflow-scrolling: touch;">
                                <?php foreach ($announcements as $a): ?>
                                <a href="<?= !empty($a['url']) ? htmlspecialchars($a['url']) : '#' ?>"
                                   class="shrink-0 h-24 md:h-28 aspect-[256/145] relative overflow-hidden rounded-xl border <?= $cardBorder ?> bg-slate-100 shadow-sm hover:shadow-md transition focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300/70"
                                   <?= !empty($a['url']) ? ' target="_blank" rel="noopener"' : '' ?>>
                                    <?php
                                    $aImg = !empty($a['image_url'])
                                        ? (str_starts_with($a['image_url'], '/') || str_starts_with($a['image_url'], 'http')
                                            ? htmlspecialchars($a['image_url'])
                                            : '/assets/' . htmlspecialchars($a['image_url']))
                                        : '';
                                    ?>
                                    <?php if ($aImg): ?>
                                        <img src="<?= $aImg ?>" class="absolute inset-0 w-full h-full object-cover" loading="lazy" alt="">
                                    <?php else: ?>
                                        <div class="absolute inset-0 bg-gradient-to-br from-slate-200 to-slate-100"></div>
                                        <div class="absolute inset-0 flex items-center justify-center text-slate-300">
                                            <i class="fa-solid fa-newspaper text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-black/0"></div>
                                    <span class="absolute top-2 left-2 z-10 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-white/85 text-slate-700 border border-white/60">INFO</span>
                                    <div class="absolute inset-x-0 bottom-0 p-2 z-10">
                                        <p class="text-[14px] md:text-sm font-black text-white line-clamp-1 drop-shadow"><?= htmlspecialchars($a['title']) ?></p>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($upcomingDeadlines)): ?>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fa-solid fa-hourglass-end text-rose-500"></i>
                                <h3 class="text-[11px] font-black text-slate-600">応募締め切り</h3>
                                <a href="/hinata/calendar.php" class="ml-auto text-[10px] font-bold <?= $cardIconText ?> hover:opacity-80" title="iCal/Googleカレンダーに登録"><i class="fa-solid fa-calendar-plus mr-0.5"></i></a>
                                <a href="/hinata/events.php" class="text-[10px] font-bold <?= $cardIconText ?> hover:opacity-80">一覧 <i class="fa-solid fa-chevron-right"></i></a>
                            </div>
                            <div class="space-y-2">
                                <?php foreach ($upcomingDeadlines as $dl): ?>
                                <a href="<?= !empty($dl['application_url']) ? htmlspecialchars($dl['application_url']) : '/hinata/events.php' ?>"
                                   class="h-24 md:h-28 aspect-[256/145] rounded-xl border <?= $cardBorder ?> bg-slate-100 shadow-sm hover:shadow-md transition relative overflow-hidden flex items-stretch"
                                   <?= !empty($dl['application_url']) ? ' target="_blank" rel="noopener"' : '' ?>>
                                    <div class="absolute inset-0 bg-gradient-to-br from-rose-200/80 to-amber-200/70"></div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-black/0"></div>
                                    <span class="absolute top-2 left-2 z-10 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500/90 text-white border border-white/60">締切</span>
                                    <div class="relative z-10 flex-1 p-2.5 flex flex-col justify-end min-w-0">
                                        <p class="text-[14px] md:text-sm font-black text-white line-clamp-1 drop-shadow">
                                            <?= htmlspecialchars($dl['event_name']) ?><?= !empty($dl['round_name']) ? ' ' . htmlspecialchars($dl['round_name']) : '' ?>
                                        </p>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php
                $hasNextEvent = !empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0;
                $hasBdBanner = !empty($upcomingBirthdays);
                if ($hasNextEvent): $days = (int)$nextEvent['days_left']; $dateText = !empty($nextEvent['event_date']) ? \Core\Utils\DateUtil::format($nextEvent['event_date'], 'Y/m/d') : ''; ?>
                <section class="mb-3 v2-nextEvent">
                    <?php
                    $totalWindowDays = 60;
                    $clampedDays = max(0, min($totalWindowDays, $days));
                    $progress = (int)round((1 - ($clampedDays / $totalWindowDays)) * 100);
                    $progress = max(0, min(100, $progress));
                    $daysText = ($days === 0) ? '今日' : ('あと ' . $days . ' 日');

                    $gcalUrl = '';
                    try {
                        $eventNameG = (string)($nextEvent['event_name'] ?? '次のイベント');
                        $eventDateRawG = (string)($nextEvent['event_date'] ?? '');
                        if ($eventDateRawG) {
                            $start = new \DateTimeImmutable($eventDateRawG);
                            $end = $start->modify('+1 day');
                            $dates = $start->format('Ymd') . '/' . $end->format('Ymd');
                            $details = (string)($nextEvent['event_info'] ?? '');
                            $place = (string)($nextEvent['event_place'] ?? '');
                            $gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                                . '&text=' . rawurlencode($eventNameG)
                                . '&dates=' . rawurlencode($dates)
                                . ($details !== '' ? '&details=' . rawurlencode($details) : '')
                                . ($place !== '' ? '&location=' . rawurlencode($place) : '');
                        }
                    } catch (\Throwable $e) {
                        $gcalUrl = '';
                    }
                    ?>
                    <div class="rounded-3xl p-5 next-event-box cursor-pointer"
                         role="button"
                         tabindex="0"
                         onclick="location.href='/hinata/events.php?event_id=<?= (int)$nextEvent['id'] ?>'"
                         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();location.href='/hinata/events.php?event_id=<?= (int)$nextEvent['id'] ?>'}">
                        <div class="flex items-center gap-2 font-semibold mb-3 text-slate-700">
                            <i class="fa-solid fa-calendar-days w-4 h-4 text-pink-500"></i>
                            <span>次のイベント</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-slate-500"><?= $dateText ? htmlspecialchars($dateText) : '　' ?></div>
                                <div class="text-lg font-bold mt-1 text-slate-800 truncate"><?= htmlspecialchars($nextEvent['event_name'] ?? '次のイベント') ?></div>
                            </div>
                            <!-- サイドバー非表示（md未満）のタイミングで、バーを日付/イベント名の右側へ -->
                            <div class="w-[clamp(96px,18vw,140px)] md:hidden pt-0.5 shrink-0">
                                <div class="next-event-bar h-[6px]">
                                    <span style="width: <?= $progress ?>%"></span>
                                </div>
                                <div class="flex justify-between mt-1 text-[10px]">
                                    <span class="text-slate-500">今日</span>
                                    <span class="font-bold text-orange-600"><?= htmlspecialchars($daysText) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="relative mt-4 flex-1 hidden md:block">
                            <div class="next-event-bar">
                                <span style="width: <?= $progress ?>%"></span>
                            </div>
                            <div class="flex justify-between mt-2 text-xs">
                                <span class="text-slate-500">今日</span>
                                <span class="font-bold text-orange-600"><?= htmlspecialchars($daysText) ?></span>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-2">
                            <a href="/hinata/calendar.php"
                               onclick="event.stopPropagation()"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full text-white font-black text-sm shadow-md transition active:scale-[0.99]<?= $isMockUi ? ' mock-pop-item' : '' ?>"
                               style="background: linear-gradient(90deg, rgba(249,115,22,0.92), rgba(236,72,153,0.92));">
                                <i class="fa-solid fa-calendar-plus w-4 h-4"></i> iCalに追加
                            </a>
                            <?php if ($gcalUrl): ?>
                            <a href="<?= htmlspecialchars($gcalUrl) ?>"
                               onclick="event.stopPropagation()"
                               target="_blank"
                               rel="noopener"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-white/85 border border-white/70 text-slate-700 font-black text-sm shadow-sm hover:bg-white transition active:scale-[0.99]<?= $isMockUi ? ' mock-pop-item' : '' ?>">
                                <i class="fa-brands fa-google w-4 h-4 text-slate-500"></i> Googleカレンダー
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty($todayMeetGreetSlots)): ?>
                <div class="mb-3 v2-meetgreet">
                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl border border-amber-200 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-amber-100">
                            <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shadow-sm">
                                <i class="fa-solid fa-handshake text-sm"></i>
                            </div>
                            <h3 class="text-sm font-black text-amber-700 tracking-tight">本日のミーグリ予定</h3>
                            <span class="text-[10px] font-bold text-amber-500 bg-amber-100 px-2 py-0.5 rounded-full"><?= count($todayMeetGreetSlots) ?> 枠</span>
                            <a href="/hinata/talk.php" class="ml-auto text-[10px] font-bold text-amber-500 hover:text-amber-700 transition">ネタを確認 <i class="fa-solid fa-arrow-right"></i></a>
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
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <?php if (!empty($slot['report'])): ?>
                                    <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"><i class="fa-solid fa-check mr-0.5"></i>メモ</span>
                                    <?php endif; ?>
                                    <a href="/hinata/meetgreet_report.php?slot_id=<?= (int)$slot['id'] ?>" class="text-[9px] font-bold text-amber-600 bg-amber-100 hover:bg-amber-200 px-2 py-0.5 rounded-full transition">
                                        <i class="fa-solid fa-pen-to-square mr-0.5"></i>レポ
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 推し情報エリア -->
                <section class="mb-4 v2-oshi">
                    <?php if ($hasOshi): ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden<?= $isMockUi ? ' mock-mesh rounded-[28px] border-white/70 relative' : '' ?> relative">
                        <a href="/hinata/oshi_settings.php"
                           class="absolute right-4 bottom-4 text-[10px] font-bold text-slate-400 hover:text-slate-600 transition z-10">
                            推し設定 →
                        </a>
                        <div class="flex flex-col md:flex-row md:items-stretch">
                            <!-- メインエリア（選択中の推し） -->
                            <div id="oshiMainArea" class="oshi-main-area flex-1 p-5">
                                <?php
                                $mainOshi = $oshiByLevel[9] ?? $oshiByLevel[8] ?? $oshiByLevel[7] ?? null;
                                if ($mainOshi):
                                    $mainLevel = (int)$mainOshi['level'];
                                    $mainLabel = FavoriteModel::LEVEL_LABELS[$mainLevel] ?? '';
                                ?>
                                <div class="flex flex-col md:flex-row gap-4 md:gap-5 h-full" data-current-level="<?= (int)$mainLevel ?>">
                                    <div class="flex-1 min-w-0 flex flex-col">
                                        <div class="flex-1">
                                            <!-- 狭幅：画像を名前の横へ -->
                                            <div class="flex items-start gap-3 mb-2">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-1.5 md:gap-2 mb-1 flex-nowrap">
                                                        <span class="inline-flex items-center gap-1.5 text-[9px] md:text-[10px] font-black text-amber-700 bg-white/80 border border-white/70 px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">
                                                            <i id="oshiMainCrown" class="fa-solid fa-crown text-[10px]<?= ($mainLabel === '最推し') ? '' : ' hidden' ?>"></i>
                                                            <span id="oshiMainLabel"><?= $mainLabel ?></span>
                                                        </span>
                                                        <span id="oshiMainGen" class="inline-flex items-center text-[9px] md:text-[10px] font-black text-slate-600 bg-white/70 border border-white/60 px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">
                                                            <?= htmlspecialchars($mainOshi['generation']) ?>期生
                                                        </span>
                                                    </div>
                                                    <h3 id="oshiMainName" class="text-xl md:text-3xl font-black text-slate-800 mb-0.5 truncate"><?= htmlspecialchars($mainOshi['name']) ?></h3>
                                                </div>
                                                <div class="md:hidden flex items-start gap-2 shrink-0">
                                                    <div class="w-28 aspect-square rounded-2xl overflow-hidden bg-slate-100 shadow-md">
                                                        <?php if ($mainOshi['image_url']): ?>
                                                        <img id="oshiMainImgMobile" src="<?= oshiImgSrc($mainOshi['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                                        <?php else: ?>
                                                        <div id="oshiMainImgMobile" class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-3xl"></i></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex flex-col gap-2 pt-1">
                                                        <?php
                                                        $subSlotsV2m = [
                                                            ['level' => 9, 'label' => '最推し'],
                                                            ['level' => 8, 'label' => '2推し'],
                                                            ['level' => 7, 'label' => '3推し'],
                                                        ];
                                                        foreach ($subSlotsV2m as $ssm):
                                                            $som = $oshiByLevel[$ssm['level']] ?? null;
                                                            if (!$som) continue;
                                                            $badgeBg = [9 => 'bg-amber-500', 8 => 'bg-pink-500', 7 => 'bg-sky-500'][(int)$ssm['level']] ?? 'bg-slate-600';
                                                        ?>
                                                        <div class="oshi-sub-card cursor-pointer mock-glass w-[56px] h-[56px] rounded-2xl border border-white/60 shadow-lg p-1"
                                                             data-level="<?= (int)$ssm['level'] ?>" onclick="OshiPortal.switchMain(<?= (int)$ssm['level'] ?>)">
                                                            <div class="relative w-full h-full overflow-visible">
                                                                <span class="absolute -top-2 -left-2 z-10 px-2 py-0.5 rounded-full text-[10px] font-black text-white shadow-lg <?= $badgeBg ?>"><?= htmlspecialchars($ssm['label']) ?></span>
                                                                <div class="w-full h-full rounded-xl overflow-hidden bg-slate-100">
                                                                    <?php if (!empty($som['image_url'])): ?>
                                                                        <img src="<?= oshiImgSrc($som['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                                                    <?php else: ?>
                                                                        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-lg"></i></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!$isLayoutV2): ?>
                                            <div id="oshiMainDetails" class="space-y-1.5 text-xs">
                                                <a id="oshiMainBlog" href="<?= !empty($mainOshi['blog_url']) ? htmlspecialchars($mainOshi['blog_url']) : '#' ?>" target="_blank" class="flex items-center gap-2 text-sky-600 hover:text-sky-700<?= empty($mainOshi['blog_url']) ? ' hidden' : '' ?>"><i class="fa-solid fa-blog w-4"></i>公式ブログ</a>
                                                <a id="oshiMainInsta" href="<?= !empty($mainOshi['insta_url']) ? htmlspecialchars($mainOshi['insta_url']) : '#' ?>" target="_blank" class="flex items-center gap-2 text-pink-600 hover:text-pink-700<?= empty($mainOshi['insta_url']) ? ' hidden' : '' ?>"><i class="fa-brands fa-instagram w-4"></i>Instagram</a>
                                                <div id="oshiMainEvent" class="flex items-center gap-2 text-slate-600"<?= empty($mainOshi['next_event']) ? ' style="display:none"' : '' ?>><?php if (!empty($mainOshi['next_event'])): ?><i class="fa-solid fa-calendar w-4 text-slate-400"></i><?= htmlspecialchars($mainOshi['next_event']['event_name']) ?> (<?= htmlspecialchars($mainOshi['next_event']['event_date']) ?>)<?php endif; ?></div>
                                                <div id="oshiMainSongs" class="flex items-center gap-2 text-slate-600"<?= $mainOshi['song_count'] <= 0 ? ' style="display:none"' : '' ?>><?php if ($mainOshi['song_count'] > 0): ?><i class="fa-solid fa-music w-4 text-slate-400"></i>参加楽曲 <?= $mainOshi['song_count'] ?> 曲<?php endif; ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $mainLatestBlog = $oshiLatestBlogByMember[$mainOshi['member_id']] ?? null;
                                        ?>
                                        <?php if ($isMockUi): ?>
                                        <?php
                                            $mockNextEventName = !empty($mainOshi['next_event']['event_name']) ? (string)$mainOshi['next_event']['event_name'] : null;
                                            $mockNextEventDate = !empty($mainOshi['next_event']['event_date']) ? (string)$mainOshi['next_event']['event_date'] : null;
                                            $mockSongCount = (int)($mainOshi['song_count'] ?? 0);
                                            $mockLatestBlogTitle = null;
                                            $mockLatestBlogUrl = null;
                                            $mockLatestBlogDateText = null;
                                            if (is_array($mainLatestBlog)) {
                                                if (!empty($mainLatestBlog['title'])) {
                                                    $mockLatestBlogTitle = (string)$mainLatestBlog['title'];
                                                }
                                                if (!empty($mainLatestBlog['detail_url'])) {
                                                    $mockLatestBlogUrl = (string)$mainLatestBlog['detail_url'];
                                                }
                                                $tsBlog = !empty($mainLatestBlog['published_at']) ? strtotime((string)$mainLatestBlog['published_at']) : false;
                                                if ($tsBlog !== false) {
                                                    $mockLatestBlogDateText = date('n/j H:i', $tsBlog);
                                                }
                                            }

                                            // 最新ニュース（カテゴリ=media、推し固有）
                                            $mockLatestNewsTitle = null;
                                            $mockLatestNewsUrl = null;
                                            $mockLatestNewsDateText = null;
                                            $mainLatestNews = $oshiLatestNewsByMember[$mainOshi['member_id']] ?? null;
                                            if (!empty($mainLatestNews) && is_array($mainLatestNews)) {
                                                $mockLatestNewsTitle = !empty($mainLatestNews['title']) ? (string)$mainLatestNews['title'] : null;
                                                $mockLatestNewsUrl = !empty($mainLatestNews['detail_url']) ? (string)$mainLatestNews['detail_url'] : null;
                                                $tsNews = !empty($mainLatestNews['published_date']) ? strtotime((string)$mainLatestNews['published_date']) : false;
                                                if ($tsNews !== false) {
                                                    $mockLatestNewsDateText = date('n/j', $tsNews);
                                                }
                                            }

                                            // 新着動画（プラットフォーム問わず最新1件、推し固有）
                                            $mockLatestVideoTitle = null;
                                            $mockLatestVideoUrl = null;
                                            $mockLatestVideoThumb = null;
                                            $mockLatestVideoPlatform = null;
                                            $mainLatestVideo = $oshiLatestVideoByMember[$mainOshi['member_id']] ?? null;
                                            if (!empty($mainLatestVideo) && is_array($mainLatestVideo)) {
                                                $mockLatestVideoTitle = !empty($mainLatestVideo['title']) ? (string)$mainLatestVideo['title'] : null;
                                                $mockLatestVideoThumb = !empty($mainLatestVideo['thumbnail_url']) ? (string)$mainLatestVideo['thumbnail_url'] : null;
                                                $mockLatestVideoPlatform = !empty($mainLatestVideo['platform']) ? strtolower((string)$mainLatestVideo['platform']) : null;
                                                $mk = !empty($mainLatestVideo['media_key']) ? (string)$mainLatestVideo['media_key'] : null;
                                                $sk = !empty($mainLatestVideo['sub_key']) ? (string)$mainLatestVideo['sub_key'] : null;
                                                if ($mockLatestVideoPlatform === 'youtube' && $mk) {
                                                    $mockLatestVideoUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($mk);
                                                } elseif ($mockLatestVideoPlatform === 'tiktok' && $mk) {
                                                    if (is_string($sk) && str_starts_with($sk, '@') && ctype_digit($mk)) {
                                                        $mockLatestVideoUrl = 'https://www.tiktok.com/' . rawurlencode($sk) . '/video/' . rawurlencode($mk);
                                                    } elseif (ctype_digit($mk)) {
                                                        $mockLatestVideoUrl = 'https://www.tiktok.com/@tiktok/video/' . rawurlencode($mk);
                                                    } else {
                                                        $mockLatestVideoUrl = 'https://vm.tiktok.com/' . rawurlencode($mk) . '/';
                                                    }
                                                } elseif ($mockLatestVideoPlatform === 'instagram' && $mk) {
                                                    $mockLatestVideoUrl = 'https://www.instagram.com/reel/' . rawurlencode($mk) . '/';
                                                }
                                            }
                                            $mockNextEventDaysLeft = null;
                                            if (!empty($mainOshi['next_event']['event_date'])) {
                                                $ts = strtotime((string)$mainOshi['next_event']['event_date']);
                                                if ($ts !== false) {
                                                    $today = new DateTimeImmutable('today');
                                                    $due = (new DateTimeImmutable())->setTimestamp($ts)->setTime(0, 0, 0);
                                                    $diffDays = (int)ceil(($due->getTimestamp() - $today->getTimestamp()) / 86400);
                                                    $mockNextEventDaysLeft = $diffDays;
                                                }
                                            }
                                        ?>
                                        <div class="mt-3 flex flex-wrap gap-2.5 oshi-kpi-wrap">
                                            <div id="oshiNextAppearanceCard"
                                                 class="oshi-kpi mock-glass rounded-2xl border border-white/60 px-4 py-3<?= !empty($mainOshi['next_event']['event_id']) ? ' cursor-pointer hover:bg-white/70 hover:shadow-md hover:-translate-y-0.5 transition' : '' ?>"
                                                 <?= !empty($mainOshi['next_event']['event_id']) ? ' role="button" tabindex="0" onclick="location.href=\'/hinata/events.php?event_id=' . (int)$mainOshi['next_event']['event_id'] . '\'" onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();location.href=\'/hinata/events.php?event_id=' . (int)$mainOshi['next_event']['event_id'] . '\'}"' : '' ?>>
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="text-[10px] font-bold text-slate-500">次の出演</div>
                                                    <div id="oshiNextAppearanceMeta" class="text-[11px] text-slate-500">
                                                        <?php if ($mockNextEventDaysLeft !== null && $mockNextEventDaysLeft >= 0): ?>
                                                            <span class="font-black text-orange-600">あと <?= (int)$mockNextEventDaysLeft ?> 日</span>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($mockNextEventDate ?: '—') ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div id="oshiNextAppearanceName" class="text-sm font-black text-slate-800 line-clamp-1 mt-1"><?= htmlspecialchars($mockNextEventName ?: '未登録') ?></div>
                                            </div>
                                            <div class="oshi-kpi oshi-kpi-song mock-glass rounded-2xl border border-white/60 px-4 py-3">
                                                <div class="text-[10px] font-bold text-slate-500">参加楽曲</div>
                                                <div id="oshiSongCount" class="text-sm font-black text-slate-800 line-clamp-1 mt-1"><?= $mockSongCount > 0 ? htmlspecialchars((string)$mockSongCount) . ' 曲' : '—' ?></div>
                                            </div>
                                            <?php if ($mockLatestBlogUrl): ?>
                                            <a id="oshiLatestBlogLink"
                                               href="<?= htmlspecialchars($mockLatestBlogUrl) ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="oshi-kpi mock-glass rounded-2xl border border-white/60 px-4 py-3 block cursor-pointer hover:bg-white/70 hover:shadow-md hover:-translate-y-0.5 transition">
                                            <?php else: ?>
                                            <div id="oshiLatestBlogLink" class="oshi-kpi mock-glass rounded-2xl border border-white/60 px-4 py-3">
                                            <?php endif; ?>
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="text-[10px] font-bold text-slate-500">最新ブログ</div>
                                                    <div id="oshiLatestBlogDate" class="text-[11px] text-slate-500 whitespace-nowrap"><?= htmlspecialchars($mockLatestBlogDateText ?: '　') ?></div>
                                                </div>
                                                <div id="oshiLatestBlogTitle" class="text-sm font-black text-slate-800 line-clamp-1 mt-1"><?= htmlspecialchars($mockLatestBlogTitle ?: '—') ?></div>
                                            <?php if ($mockLatestBlogUrl): ?>
                                            </a>
                                            <?php else: ?>
                                            </div>
                                            <?php endif; ?>

                                            <a id="oshiLatestNewsLink"
                                               href="<?= !empty($mockLatestNewsUrl) ? htmlspecialchars($mockLatestNewsUrl) : '#' ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="oshi-kpi mock-glass rounded-2xl border border-white/60 px-4 py-3 block cursor-pointer hover:bg-white/70 hover:shadow-md hover:-translate-y-0.5 transition w-full min-w-0 overflow-hidden<?= (!empty($mockLatestNewsTitle) && !empty($mockLatestNewsUrl)) ? '' : ' hidden' ?>">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="text-[10px] font-bold text-slate-500">最新ニュース</div>
                                                    <div id="oshiLatestNewsDate" class="text-[11px] text-slate-500 whitespace-nowrap"><?= htmlspecialchars($mockLatestNewsDateText ?: '　') ?></div>
                                                </div>
                                                <div id="oshiLatestNewsTitle" class="text-sm font-black text-slate-800 line-clamp-2 mt-1 leading-snug min-w-0"><?= htmlspecialchars($mockLatestNewsTitle ?: '—') ?></div>
                                            </a>

                                            <a id="oshiLatestVideoLink"
                                               href="<?= !empty($mockLatestVideoUrl) ? htmlspecialchars($mockLatestVideoUrl) : '#' ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="oshi-kpi mock-glass rounded-2xl border border-white/60 px-4 py-3 block cursor-pointer hover:bg-white/70 hover:shadow-md hover:-translate-y-0.5 transition w-full min-w-0 overflow-hidden<?= (!empty($mockLatestVideoTitle) && !empty($mockLatestVideoUrl)) ? '' : ' hidden' ?>">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="text-[10px] font-bold text-slate-500">新着動画</div>
                                                    <div id="oshiLatestVideoPlatform" class="text-[11px] text-slate-500 whitespace-nowrap">
                                                        <?php if ($mockLatestVideoPlatform === 'youtube'): ?>
                                                            <i class="fa-brands fa-youtube text-red-500"></i>
                                                        <?php elseif ($mockLatestVideoPlatform === 'tiktok'): ?>
                                                            <i class="fa-brands fa-tiktok text-slate-700"></i>
                                                        <?php elseif ($mockLatestVideoPlatform === 'instagram'): ?>
                                                            <i class="fa-brands fa-instagram text-pink-600"></i>
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-play text-slate-500"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1 min-w-0 overflow-hidden">
                                                    <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden shrink-0">
                                                        <?php if (!empty($mockLatestVideoThumb)): ?>
                                                            <img id="oshiLatestVideoThumb" src="<?= htmlspecialchars($mockLatestVideoThumb) ?>" alt="" class="w-full h-full object-cover" loading="lazy" />
                                                        <?php else: ?>
                                                            <img id="oshiLatestVideoThumb" src="" alt="" class="w-full h-full object-cover hidden" loading="lazy" />
                                                        <?php endif; ?>
                                                    </div>
                                                <div id="oshiLatestVideoTitle" class="text-sm font-black text-slate-800 line-clamp-2 leading-snug min-w-0 flex-1"><?= htmlspecialchars($mockLatestVideoTitle ?: '—') ?></div>
                                                </div>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($isLayoutV2): ?>
                                        <div class="flex flex-wrap gap-2 pt-3">
                                            <a id="oshiMainMemberBtn" href="/hinata/member.php?id=<?= $mainOshi['member_id'] ?>" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl font-black text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-md hover:shadow-lg transition active:scale-95">
                                                <i class="fa-solid fa-heart"></i>推し個別ページへ
                                            </a>
                                            <a id="oshiMainBlogBtn" href="<?= !empty($mainOshi['blog_url']) ? htmlspecialchars($mainOshi['blog_url']) : '#' ?>" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl font-black border border-slate-200/70 bg-white/80 hover:bg-white shadow-sm hover:shadow-md transition<?= empty($mainOshi['blog_url']) ? ' hidden' : '' ?>">
                                                <i class="fa-solid fa-blog"></i>公式ブログ
                                            </a>
                                            <a id="oshiMainInstaBtn" href="<?= !empty($mainOshi['insta_url']) ? htmlspecialchars($mainOshi['insta_url']) : '#' ?>" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl font-black border border-slate-200/70 bg-white/80 hover:bg-white shadow-sm hover:shadow-md transition<?= empty($mainOshi['insta_url']) ? ' hidden' : '' ?>">
                                                <i class="fa-brands fa-instagram"></i>Instagram
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <a id="oshiMainLink" href="/hinata/member.php?id=<?= $mainOshi['member_id'] ?>" class="oshi-mirror-btn inline-flex items-center justify-center gap-2 mt-3 w-full px-5 py-2.5 rounded-full text-sm font-black text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-md hover:shadow-lg transition-all"><i class="fa-solid fa-arrow-right text-xs"></i>推し個別ページへ</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-row md:hidden gap-3 items-center pt-3 hidden">
                                        <div class="w-24 h-24 rounded-xl overflow-hidden bg-slate-100 shrink-0 shadow-md" style="min-width: 96px;">
                                            <?php if ($mainOshi['image_url']): ?>
                                            <img id="oshiMainImgSp" src="<?= oshiImgSrc($mainOshi['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                            <?php else: ?>
                                            <div id="oshiMainImgSp" class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-3xl"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <span id="oshiMainLabelSp" class="inline-block text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full w-fit"><?= FavoriteModel::LEVEL_LABELS[$mainLevel] ?? '' ?></span>
                                            <h3 id="oshiMainNameSp" class="text-base font-black text-slate-800"><?= htmlspecialchars($mainOshi['name']) ?></h3>
                                            <p id="oshiMainGenSp" class="text-[11px] text-slate-400"><?= htmlspecialchars($mainOshi['generation']) ?>期生</p>
                                        </div>
                                    </div>
                                    <div class="hidden md:flex flex-col gap-3 md:gap-4 shrink-0<?= $isLayoutV2 ? ' order-last' : '' ?>">
                                        <div class="<?= $isLayoutV2 ? 'w-44 aspect-square' : 'w-24 h-24 md:w-44 md:h-auto md:self-stretch' ?> rounded-2xl overflow-hidden bg-slate-100 shadow-md">
                                            <?php if ($mainOshi['image_url']): ?>
                                            <img id="oshiMainImg" src="<?= oshiImgSrc($mainOshi['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                            <?php else: ?>
                                            <div id="oshiMainImg" class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-3xl md:text-4xl"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($isLayoutV2): ?>
                                        <div class="flex gap-2 justify-end">
                                            <?php
                                                $subSlotsV2 = [
                                                    ['level' => 9, 'label' => '最推し', 'colorClass' => 'amber'],
                                                    ['level' => 8, 'label' => '2推し', 'colorClass' => 'pink'],
                                                    ['level' => 7, 'label' => '3推し', 'colorClass' => 'rose'],
                                                ];
                                                foreach ($subSlotsV2 as $idx2 => $ss2):
                                                $so2 = $oshiByLevel[$ss2['level']] ?? null;
                                                if (!$so2) continue;
                                                $mockBadgeBg2 = [9 => 'bg-amber-500', 8 => 'bg-pink-500', 7 => 'bg-sky-500'][(int)$ss2['level']] ?? 'bg-slate-600';
                                            ?>
                                            <div class="oshi-sub-card cursor-pointer mock-glass w-[72px] h-[72px] rounded-2xl border border-white/60 shadow-lg p-1"
                                                 data-level="<?= (int)$ss2['level'] ?>" onclick="OshiPortal.switchMain(<?= (int)$ss2['level'] ?>)">
                                                <div class="relative w-full h-full overflow-visible">
                                                    <span class="absolute -top-2 -left-2 z-10 px-2 py-0.5 rounded-full text-[10px] font-black text-white shadow-lg <?= $mockBadgeBg2 ?>"><?= htmlspecialchars($ss2['label']) ?></span>
                                                    <div class="w-full h-full rounded-xl overflow-hidden bg-slate-100">
                                                        <?php if (!empty($so2['image_url'])): ?>
                                                            <img src="<?= oshiImgSrc($so2['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                                        <?php else: ?>
                                                            <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-lg"></i></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- サブエリア（2推し・3推し） -->
                            <div class="w-full md:w-36 border-t md:border-t-0 md:border-l border-slate-100 p-2 flex md:flex-col gap-1 justify-center<?= ($isMockUi && !$isLayoutV2) ? ' md:absolute md:top-4 md:right-4 md:w-auto md:border-0 md:p-0 md:bg-transparent md:gap-2 md:items-end' : '' ?><?= $isLayoutV2 ? ' hidden' : '' ?>">
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
                                    $subCardBaseClass = $isMockUi
                                        ? 'mock-glass md:w-[80px] md:h-[80px] md:flex-none md:rounded-2xl md:border md:border-white/60 md:shadow-lg md:hover:shadow-xl md:transition md:p-1'
                                        : '';
                                    $subCardStateClass = $isMockUi
                                        ? ($isMain ? ' md:ring-2 md:ring-amber-400' : ' md:hover:scale-[1.02]')
                                        : ($isMain ? 'bg-amber-50 shadow-sm' : 'hover:bg-slate-50');
                                    $mockBadgeBg = [9 => 'bg-amber-500', 8 => 'bg-pink-500', 7 => 'bg-sky-500'][(int)$ss['level']] ?? 'bg-slate-600';
                                ?>
                                <div class="oshi-sub-card flex-1 flex md:flex-col items-center justify-center gap-1 p-2 rounded-lg cursor-pointer <?= $subCardBaseClass ?> <?= $subCardStateClass ?>"
                                     data-level="<?= $ss['level'] ?>"
                                     onclick="OshiPortal.switchMain(<?= $ss['level'] ?>)">
                                    <?php if ($isMockUi): ?>
                                        <div class="relative w-full h-full overflow-visible">
                                            <span class="absolute -top-2 -left-2 z-10 px-2 py-0.5 rounded-full text-[10px] font-black text-white shadow-lg <?= $mockBadgeBg ?>"><?= htmlspecialchars($ss['label']) ?></span>
                                            <div class="w-full h-full rounded-xl overflow-hidden bg-slate-100">
                                                <?php if ($so['image_url']): ?>
                                                    <img src="<?= oshiImgSrc($so['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-lg"></i></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-11 h-11 md:w-12 md:h-12 rounded-full overflow-hidden bg-slate-100 shrink-0">
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
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-8 text-center<?= $isMockUi ? ' mock-card' : '' ?>">
                        <div class="w-16 h-16 mx-auto rounded-full bg-amber-50 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-heart text-amber-400 text-2xl"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700 mb-2">推しを設定しましょう！</p>
                        <p class="text-xs text-slate-400 mb-3">推しを設定すると、ここにメンバーの情報が表示されます。</p>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if ($hasBdBanner): $bdToday = array_filter($upcomingBirthdays, fn($b) => (int)$b['days_until'] === 0); $bdUpcoming = array_filter($upcomingBirthdays, fn($b) => (int)$b['days_until'] > 0); ?>
                <section class="v2-birthday">
                    <?php
                    $bdList = array_values(array_merge($bdToday, $bdUpcoming));
                    $bdMain = $bdList[0] ?? null;
                    $bdMainImg = $bdMain ? ($bdMain['first_image'] ?: ($bdMain['image_url'] ?? null)) : null;
                    $bdMainDate = ($bdMain && $bdMain['birth_date']) ? date('m/d', strtotime($bdMain['birth_date'])) : '';
                    $bdMainIsToday = $bdMain ? ((int)$bdMain['days_until'] === 0) : false;
                    $bdMainText = $bdMain ? ($bdMainIsToday ? '本日！' : ($bdMainDate . ' ・ あと ' . (int)$bdMain['days_until'] . ' 日')) : '';
                    ?>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5 h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 font-semibold text-slate-700">
                                <i class="fa-solid fa-birthday-cake w-4 h-4 text-pink-500"></i>
                                <span>誕生日</span>
                            </div>
                        </div>

                        <?php if ($bdMain): ?>
                        <a href="/hinata/member.php?id=<?= (int)$bdMain['id'] ?>" class="flex items-center gap-4 hover:bg-slate-50/50 hover:shadow-sm hover:-translate-y-0.5 transition rounded-2xl p-3 -mx-3">
                            <div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 shadow-sm"
                                 style="background: linear-gradient(135deg, rgba(253, 230, 138, 0.9), rgba(196, 181, 253, 0.9));">
                                <?php if ($bdMainImg): ?>
                                <img src="/assets/img/members/<?= htmlspecialchars($bdMainImg) ?>" class="w-full h-full object-cover" alt="">
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-lg font-black text-slate-800 truncate"><?= htmlspecialchars($bdMain['name']) ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($bdMainText) ?></p>
                            </div>
                        </a>
                        <?php endif; ?>

                        <div class="mt-4 bg-rose-50/60 rounded-2xl px-4 py-3 text-sm font-bold text-rose-700 flex items-center gap-2 mt-auto">
                            <span class="w-6 h-6 rounded-full bg-white/80 flex items-center justify-center"><i class="fa-solid fa-gift text-rose-500 text-sm"></i></span>
                            <span>お祝いメッセージを準備しましょう！</span>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty($todayInHistory)): ?>
                <section class="mb-4 v2-todayInHistory">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">今日は何の日</h2>
                        <span class="text-[10px] text-slate-400 ml-1"><?= date('n月j日') ?></span>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($todayInHistory as $hist): ?>
                        <div class="flex items-center gap-3 bg-white rounded-xl border <?= $cardBorder ?> shadow-sm px-4 py-3<?= $isMockUi ? ' mock-card' : '' ?>">
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
                <?php ob_start(); ?>
                <?php if ($latestRelease): ?>
                <section class="v2-release" id="releaseSection">
                    <div id="releaseAccordion" class="release-accordion bg-white rounded-xl border <?= $cardBorder ?> overflow-hidden h-full" data-release-id="<?= (int)$latestRelease['id'] ?>">
                        <?php
                        $jackets = [];
                        if (!empty($latestRelease['editions'])) {
                            foreach ($latestRelease['editions'] as $ed) {
                                if (!empty($ed['jacket_image_url'])) {
                                    $jackets[] = $ed;
                                }
                            }
                        }
                        $releaseNumber = (string)($latestRelease['release_number'] ?? '');
                        $typeEn = ['single' => 'Single', 'album' => 'Album', 'digital' => 'Digital', 'ep' => 'EP', 'best' => 'Best'][$latestRelease['release_type'] ?? ''] ?? '';
                        $releaseNoText = trim($releaseNumber . ' ' . $typeEn);
                        $mainJacket = $latestRelease['jacket_url'] ?? '';
                        if (!empty($jackets)) {
                            $prefer = null;
                            foreach ($jackets as $ed) {
                                $lab = strtolower((string)($ed['edition'] ?? ''));
                                if (str_contains($lab, 'type_a') || str_contains($lab, 'type-a') || str_contains($lab, 'a')) { $prefer = $ed; break; }
                            }
                            $mainJacket = $prefer['jacket_image_url'] ?? $jackets[0]['jacket_image_url'];
                        }
                        ?>

                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2 font-semibold text-slate-700">
                                    <i class="fa-solid fa-compact-disc w-4 h-4 text-violet-500"></i>
                                    <span>最新リリース</span>
                                    <?php if ($releaseIsNew): ?>
                                    <span class="text-[10px] font-black text-white bg-red-500 px-2.5 py-0.5 rounded-full shadow-sm shadow-red-200 animate-pulse">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <a href="/hinata/release.php?id=<?= (int)$latestRelease['id'] ?>" class="text-xs text-slate-500 hover:text-violet-500 transition">詳細 →</a>
                            </div>

                            <div class="flex flex-row gap-4 sm:gap-5 items-start">
                                <div class="shrink-0 w-28 sm:w-32">
                                    <button type="button"
                                            class="aspect-square w-full rounded-2xl overflow-hidden shadow-md bg-slate-100 block cursor-zoom-in"
                                            onclick="if(window.BlogImageZoom){ var img=document.getElementById('releaseMainJacket'); if(img && img.src){ BlogImageZoom.open(img.src); } }">
                                        <?php if ($mainJacket): ?>
                                        <img id="releaseMainJacket" src="<?= htmlspecialchars($mainJacket) ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                        <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-compact-disc text-3xl"></i></div>
                                        <?php endif; ?>
                                    </button>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="text-[10px] font-bold text-violet-500 bg-violet-50 px-2 py-0.5 rounded-full"><?= htmlspecialchars($releaseNoText ?: ($latestRelease['release_type_label'] ?? '')) ?></span>
                                    </div>

                                    <h3 class="text-2xl font-black text-slate-800 leading-tight truncate"><?= htmlspecialchars($latestRelease['title']) ?></h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <?= $latestRelease['release_date'] ? date('Y/m/d', strtotime($latestRelease['release_date'])) : '' ?>
                                        <?php if ($latestRelease['release_date']): ?> Release<?php endif; ?>
                                        <?php if ($latestRelease['song_count']): ?> &middot; <?= $latestRelease['song_count'] ?> 曲収録<?php endif; ?>
                                    </p>

                                    <?php if (!empty($jackets)): ?>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <?php foreach ($jackets as $jk): ?>
                                            <?php
                                            $edKey = (string)($jk['edition'] ?? '');
                                            $edLabel = (string)(\App\Hinata\Model\ReleaseEditionModel::EDITIONS[$edKey] ?? $edKey);
                                            $edLabel = preg_replace('/^初回限定\s*/u', '', $edLabel);
                                            ?>
                                            <button type="button"
                                                    class="release-edition-btn text-[10px] font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition"
                                                    data-jacket-url="<?= htmlspecialchars($jk['jacket_image_url']) ?>"><?= htmlspecialchars($edLabel) ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                        <button type="button" id="releaseTracksBtn"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-white border <?= $cardBorder ?> text-slate-700 text-sm font-black hover:bg-slate-50 transition w-full sm:w-auto">
                                            <i class="fa-solid fa-list-music"></i> 収録曲を見る
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="release-expand-content" id="releaseExpandContent">
                        <?php if (!empty($latestRelease['mvs'])): ?>
                        <div class="border-t border-slate-100 px-5 py-4">
                            <div class="flex items-center gap-2 mb-2">
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
                        <div class="border-t border-slate-100" id="releaseTracksSection">
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
                                    <a href="/hinata/song.php?id=<?= (int)$rs['id'] ?>" class="flex-1 min-w-0 release-song-link" data-release-id="<?= (int)$latestRelease['id'] ?>">
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
                    </div>
                </section>
                <?php endif; ?>
                <?php $releaseHtml = ob_get_clean(); ?>
                <?php if ($releaseIsNew) echo $releaseHtml; ?>

                <!-- 最新ブログ（全メンバー対象） -->
                <?php if (!empty($latestBlogPosts)): ?>
                <section class="mb-4 v2-blog">
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 font-semibold text-slate-700">
                                <i class="fa-solid fa-pen-fancy w-4 h-4 text-pink-500"></i>
                                <h2 class="text-sm">最新ブログ</h2>
                            </div>
                            <a href="https://www.hinatazaka46.com/s/official/diary/member?ima=0000" target="_blank" rel="noopener" class="text-xs text-slate-500 hover:text-pink-500 transition">もっと見る →</a>
                        </div>
                        <div class="yt-scroll-wrap">
                            <button class="yt-arrow left hidden" onclick="YtCarousel.scroll('blogCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                            <div id="blogCards" class="yt-scroll<?= $isMockUi ? ' mock-scroll-row' : '' ?>">
                                <?php foreach ($latestBlogPosts as $bp): ?>
                                <div class="blog-card block relative group rounded-2xl overflow-hidden border <?= $cardBorder ?> bg-white shadow-sm<?= $isMockUi ? ' mock-media-card' : '' ?>">
                                    <a href="<?= htmlspecialchars($bp['detail_url']) ?>" target="_blank" class="block">
                                        <div class="aspect-[3/4] overflow-hidden bg-slate-100 shadow-sm relative">
                                            <?php if ($bp['thumbnail_url']): ?>
                                            <img src="<?= htmlspecialchars($bp['thumbnail_url']) ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                            <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-pen-fancy text-2xl"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="bg-white px-3 py-2">
                                            <h3 class="text-sm font-semibold text-slate-800 line-clamp-1 leading-snug"><?= htmlspecialchars($bp['title'] ?: '(無題)') ?></h3>
                                            <p class="text-xs text-slate-500 truncate mt-0.5"><?= htmlspecialchars($bp['member_name'] ?? '') ?> <?= $bp['published_at'] ? date('m/d H:i', strtotime($bp['published_at'])) : '' ?></p>
                                        </div>
                                    </a>
                                    <?php if ($bp['thumbnail_url']): ?>
                                    <button type="button" class="blog-download-btn absolute bottom-14 right-2 w-7 h-7 rounded-full bg-black/50 flex items-center justify-center text-white hover:bg-black/70 border-0 cursor-pointer z-10" data-article-id="<?= (int)($bp['article_id'] ?? 0) ?>" data-post-id="<?= (int)($bp['id'] ?? 0) ?>" data-title="<?= htmlspecialchars($bp['title'] ?? '(無題)', ENT_QUOTES, 'UTF-8') ?>" title="画像を保存">
                                        <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z"/></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="yt-arrow right" onclick="YtCarousel.scroll('blogCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- YouTube (タブ切替) -->
                <section class="mb-4 v2-youtube">
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5 h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 font-semibold text-slate-700">
                                <i class="fa-brands fa-youtube w-4 h-4 text-red-500"></i>
                                <h2 class="text-sm">YouTube</h2>
                            </div>
                            <div class="flex gap-1 items-center">
                                <button class="yt-tab text-[10px] font-bold px-3 py-1 rounded-full bg-red-500 text-white" data-idx="0" onclick="switchYtTab(0)">ちゃんねる</button>
                                <button class="yt-tab text-[10px] font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition" data-idx="1" onclick="switchYtTab(1)">公式</button>
                                <a href="/hinata/media_list.php?platform=youtube" class="text-xs text-slate-500 hover:text-red-500 transition ml-2">もっと見る →</a>
                            </div>
                        </div>
                    <?php
                    $channels = [
                        ['id' => 'UCOB24f8lQBCnVqPZXOkVpOg', 'name' => '日向坂ちゃんねる'],
                        ['id' => 'UCR0V48DJyWbwEAdxLL5FjxA', 'name' => '日向坂46公式チャンネル'],
                    ];
                    foreach ($channels as $ci => $ch):
                    ?>
                    <div class="yt-tab-panel flex-1 min-h-0 flex flex-col" data-panel="<?= $ci ?>"<?= $ci > 0 ? ' style="display:none"' : '' ?>>
                        <div class="yt-scroll-wrap flex-1">
                            <button class="yt-arrow left hidden" onclick="YtCarousel.scroll('ytCards<?= $ci ?>', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                            <div id="ytLoading<?= $ci ?>" class="yt-grid-scroll<?= $isMockUi ? ' mock-scroll-row' : '' ?>">
                                <?php for ($i = 0; $i < 8; $i++): ?>
                                <div class="yt-card skeleton-card<?= $isMockUi ? ' mock-media-card' : '' ?>">
                                    <div class="aspect-video bg-slate-200 rounded-lg mb-2"></div>
                                    <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                    <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <div id="ytCards<?= $ci ?>" class="yt-grid-scroll hidden<?= $isMockUi ? ' mock-scroll-row' : '' ?>" data-channel-id="<?= $ch['id'] ?>"></div>
                            <button class="yt-arrow right" onclick="YtCarousel.scroll('ytCards<?= $ci ?>', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </section>

                <!-- TikTok -->
                <section class="mb-4 v2-tiktok">
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5 h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 font-semibold text-slate-700">
                                <i class="fa-brands fa-tiktok w-4 h-4 text-slate-800"></i>
                                <h2 class="text-sm">TikTok</h2>
                            </div>
                            <a href="/hinata/media_list.php?platform=tiktok" class="text-xs text-slate-500 hover:text-slate-800 transition">もっと見る →</a>
                        </div>
                        <div class="yt-scroll-wrap flex-1">
                            <button class="yt-arrow left hidden" onclick="TkCarousel.scroll('tkCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                            <div id="tkLoading" class="tk-grid-scroll<?= $isMockUi ? ' mock-scroll-row' : '' ?>">
                            <?php for ($i = 0; $i < 8; $i++): ?>
                            <div class="tk-card skeleton-card<?= $isMockUi ? ' mock-media-card' : '' ?>">
                                <div class="aspect-[3/4] bg-slate-200 rounded-lg mb-2"></div>
                                <div class="h-3 bg-slate-200 rounded w-3/4 mb-1"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                            </div>
                            <?php endfor; ?>
                            </div>
                            <div id="tkCards" class="tk-grid-scroll hidden<?= $isMockUi ? ' mock-scroll-row' : '' ?>"></div>
                            <button class="yt-arrow right" onclick="TkCarousel.scroll('tkCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                        </div>
                    </div>
                </section>

                <!-- SNSリンクエリア -->
                <section class="mb-4 v2-sns">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-share-nodes text-slate-500"></i>
                        <h2 class="text-sm font-bold text-slate-700">公式リンク</h2>
                    </div>
                    <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5">
                        <div class="flex flex-wrap items-stretch gap-3">
                            <a href="https://www.hinatazaka46.com/s/official/" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-slate-50 hover:bg-slate-100 transition-all shrink-0 overflow-hidden" title="日向坂公式サイト">
                                <div class="w-10 h-10 flex items-center justify-center shrink-0"><img src="/assets/img/hinata/hinata-logo.svg" alt="" class="max-w-full max-h-full object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span class="w-10 h-10 items-center justify-center text-slate-600 hidden"><i class="fa-solid fa-globe text-xl"></i></span></div>
                                <span class="text-[9px] font-bold text-slate-600 text-center leading-tight px-1">公式サイト</span>
                            </a>
                            <a href="https://x.com/hinatazaka46" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-slate-800 hover:bg-slate-700 text-white transition-all shrink-0" title="日向坂公式X">
                                <i class="fa-brands fa-x-twitter text-2xl"></i>
                                <span class="text-[9px] font-bold text-center leading-tight px-1">X</span>
                            </a>
                            <a href="https://www.instagram.com/hinatazaka46/" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-gradient-to-r from-pink-500 via-red-500 to-yellow-500 text-white hover:brightness-110 transition-all shrink-0" title="日向坂公式Instagram">
                                <i class="fa-brands fa-instagram text-2xl"></i>
                                <span class="text-[9px] font-bold text-center leading-tight px-1">Instagram</span>
                            </a>
                            <a href="https://www.youtube.com/@46officialyoutubechannel99" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-red-600 hover:bg-red-700 text-white transition-all shrink-0" title="日向坂公式YouTube">
                                <i class="fa-brands fa-youtube text-2xl"></i>
                                <span class="text-[9px] font-bold text-center leading-tight px-1">公式YT</span>
                            </a>
                            <a href="https://www.youtube.com/@hinatazakachannel" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-red-600 hover:bg-red-700 text-white transition-all shrink-0" title="日向坂ちゃんねる">
                                <i class="fa-brands fa-youtube text-2xl"></i>
                                <span class="text-[9px] font-bold text-center leading-tight px-1">ちゃんねる</span>
                            </a>
                            <a href="https://www.tiktok.com/@hinatazakanews" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-slate-800 hover:bg-slate-700 text-white transition-all shrink-0" title="日向坂公式TikTok">
                                <i class="fa-brands fa-tiktok text-2xl"></i>
                                <span class="text-[9px] font-bold text-center leading-tight px-1">TikTok</span>
                            </a>
                            <a href="https://store.plusmember.jp/hinatazaka46/" target="_blank" rel="noopener" class="sns-link-card flex flex-col items-center justify-center gap-1.5 w-20 h-20 rounded-xl border <?= $cardBorder ?> bg-amber-500 hover:bg-amber-600 text-white transition-all shrink-0" title="日向坂OFFICIAL GOODS STORE">
                                <i class="fa-solid fa-bag-shopping text-2xl"></i>
                                <span class="text-[9px] font-bold text-center leading-tight px-1">GOODS</span>
                            </a>
                        </div>
                    </div>
                </section>

                <?php if (!$releaseIsNew) echo $releaseHtml; ?>

                <!-- アプリ -->
                <section class="mb-4 v2-apps">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-grip text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-500 tracking-wider">アプリ</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4">
                    <a href="/hinata/talk.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-sky">
                                <i class="fa-solid fa-comment-dots text-sky-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">ミーグリネタ帳</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">会話ネタを記録</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/meetgreet.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-pink">
                                <i class="fa-solid fa-ticket text-rose-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">ミーグリ予定</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">予定とレポを管理</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/meetgreet_report.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-amber">
                                <i class="fa-solid fa-pen-to-square text-amber-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">レポ登録</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">チャット形式で記録</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/events.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-violet">
                                <i class="fa-solid fa-calendar-check text-violet-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">イベント</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">重要日程を確認</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/members.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-emerald">
                                <i class="fa-solid fa-address-card text-emerald-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">メンバー帳</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">プロフィール一覧</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/songs.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-violet">
                                <i class="fa-solid fa-music text-indigo-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">楽曲</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">リリース・全曲</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/artist_photos.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-amber">
                                <i class="fa-solid fa-image text-amber-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">アー写</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">リリース別に閲覧</p>
                            </div>
                        </div>
                    </a>
                    <a href="/hinata/media_list.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-rose">
                                <i class="fa-solid fa-play-circle text-rose-700"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 leading-tight">動画一覧</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">登録動画を閲覧</p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php if (in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                <details class="mt-4">
                    <summary class="text-[10px] font-bold text-slate-400 cursor-pointer hover:text-slate-600 transition flex items-center gap-1"><i class="fa-solid fa-wrench text-[8px]"></i>管理ツール</summary>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mt-3">
                        <a href="/hinata/release_admin.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-violet">
                                    <i class="fa-solid fa-compact-disc text-violet-700"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 leading-tight">リリース管理</h3>
                                    <p class="text-xs text-slate-500 mt-1 truncate">シングル/アルバム</p>
                                </div>
                            </div>
                        </a>
                        <a href="/hinata/portal_info_admin.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-amber">
                                    <i class="fa-solid fa-newspaper text-amber-700"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 leading-tight">ポータル情報管理</h3>
                                    <p class="text-xs text-slate-500 mt-1 truncate">TOPICS/お知らせ</p>
                                </div>
                            </div>
                        </a>
                        <a href="/hinata/media_member_admin.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-sky">
                                    <i class="fa-solid fa-link text-sky-700"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 leading-tight">動画・メンバー紐付け</h3>
                                    <p class="text-xs text-slate-500 mt-1 truncate">出演メンバー設定</p>
                                </div>
                            </div>
                        </a>
                        <a href="/hinata/media_song_admin.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-emerald">
                                    <i class="fa-solid fa-music text-emerald-700"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 leading-tight">動画・楽曲紐付け</h3>
                                    <p class="text-xs text-slate-500 mt-1 truncate">MV/楽曲の紐付け</p>
                                </div>
                            </div>
                        </a>
                        <a href="/hinata/media_settings_admin.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-slate">
                                    <i class="fa-solid fa-sliders text-slate-700"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 leading-tight">動画設定</h3>
                                    <p class="text-xs text-slate-500 mt-1 truncate">カテゴリ等を変更</p>
                                </div>
                            </div>
                        </a>
                        <a href="/hinata/media_register.php" class="app-card hinata-portal-card group bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden p-4 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center app-icon-grad-rose">
                                    <i class="fa-solid fa-circle-plus text-rose-700"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 leading-tight">メディア登録</h3>
                                    <p class="text-xs text-slate-500 mt-1 truncate">URL/検索で追加</p>
                                </div>
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
    <?php include __DIR__ . '/../../../components/blog_image_modal.php'; ?>

    <?php if ($isAdmin): ?>
    <!-- 管理者向けショートカット -->
    <a href="/hinata/portal_info_admin.php"
       class="fixed right-6 bottom-6 w-14 h-14 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-2xl flex items-center justify-center z-30 transition active:scale-95"
       title="ポータル情報管理">
        <i class="fa-solid fa-plus text-white text-xl"></i>
    </a>
    <?php endif; ?>

    <script>
    // 推しエリア切り替え
    var OshiPortal = {
        data: <?= json_encode($oshiByLevel, JSON_UNESCAPED_UNICODE) ?>,
        latestBlogByMember: <?= json_encode($oshiLatestBlogByMember ?? [], JSON_UNESCAPED_UNICODE) ?>,
        latestNewsByMember: <?= json_encode($oshiLatestNewsByMember ?? [], JSON_UNESCAPED_UNICODE) ?>,
        latestVideoByMember: <?= json_encode($oshiLatestVideoByMember ?? [], JSON_UNESCAPED_UNICODE) ?>,
        imgSrc: function(url) {
            if (!url) return '';
            return url.charAt(0) === '/' ? url : '/assets/img/members/' + url;
        },
        switchMain: function(level) {
            var d = this.data[level];
            if (!d) return;
            var label = {9:'最推し',8:'2推し',7:'3推し'}[level] || '';
            var el = function(id) { return document.getElementById(id); };
            var setImg = function(id) {
                var node = el(id);
                if (!node) return;
                var url = d.image_url ? OshiPortal.imgSrc(d.image_url) : '';
                if (node.tagName === 'IMG') {
                    if (url) node.src = url;
                    return;
                }
                if (url) {
                    var img = document.createElement('img');
                    img.id = id;
                    img.src = url;
                    img.className = 'w-full h-full object-cover';
                    img.alt = '';
                    node.replaceWith(img);
                }
            };
            var mainImg = el('oshiMainImg');
            if (mainImg && mainImg.tagName === 'IMG' && d.image_url) {
                mainImg.src = this.imgSrc(d.image_url);
            }
            var mainImgSp = el('oshiMainImgSp');
            if (mainImgSp && mainImgSp.tagName === 'IMG' && d.image_url) {
                mainImgSp.src = this.imgSrc(d.image_url);
            }
            setImg('oshiMainImgMobile');
            if (el('oshiMainLabel')) el('oshiMainLabel').textContent = label;
            if (el('oshiMainName')) el('oshiMainName').textContent = d.name || '';
            if (el('oshiMainGen')) el('oshiMainGen').textContent = (d.generation || '') + '期生';
            var crown = el('oshiMainCrown');
            if (crown) crown.classList.toggle('hidden', label !== '最推し');
            if (el('oshiMainLink')) el('oshiMainLink').href = '/hinata/member.php?id=' + d.member_id;
            if (el('oshiMainMemberBtn')) el('oshiMainMemberBtn').href = '/hinata/member.php?id=' + d.member_id;
            var labelSp = el('oshiMainLabelSp'), nameSp = el('oshiMainNameSp'), genSp = el('oshiMainGenSp'), blogSp = el('oshiMainBlogSp'), songsSp = el('oshiMainSongsSp');
            if (labelSp) labelSp.textContent = label;
            if (nameSp) nameSp.textContent = d.name || '';
            if (genSp) genSp.textContent = (d.generation || '') + '期生';
            if (blogSp) { if (d.blog_url) { blogSp.href = d.blog_url; blogSp.classList.remove('hidden'); } else { blogSp.classList.add('hidden'); } }
            if (songsSp) { if (d.song_count > 0) { songsSp.innerHTML = '<i class="fa-solid fa-music w-3 text-slate-400"></i>参加楽曲 ' + d.song_count + ' 曲'; songsSp.classList.remove('hidden'); } else { songsSp.classList.add('hidden'); } }

            var blogEl = el('oshiMainBlog');
            if (blogEl) {
                if (d.blog_url) { blogEl.href = d.blog_url; blogEl.classList.remove('hidden'); }
                else { blogEl.classList.add('hidden'); }
            }
            var blogBtn = el('oshiMainBlogBtn');
            if (blogBtn) {
                if (d.blog_url) { blogBtn.href = d.blog_url; blogBtn.classList.remove('hidden'); }
                else { blogBtn.classList.add('hidden'); }
            }
            var instaEl = el('oshiMainInsta');
            if (instaEl) {
                if (d.insta_url) { instaEl.href = d.insta_url; instaEl.classList.remove('hidden'); }
                else { instaEl.classList.add('hidden'); }
            }
            var instaBtn = el('oshiMainInstaBtn');
            if (instaBtn) {
                if (d.insta_url) { instaBtn.href = d.insta_url; instaBtn.classList.remove('hidden'); }
                else { instaBtn.classList.add('hidden'); }
            }

            // layout-v2: 上部「最新ブログ」ブロックへブログタイトルを表示
            var latestBlogEl = el('oshiLatestBlogTitle');
            var latestBlogLinkEl = el('oshiLatestBlogLink');
            var latestBlogDateEl = el('oshiLatestBlogDate');
            if (latestBlogEl) {
                var blogItem = this.latestBlogByMember[d.member_id] || this.latestBlogByMember[String(d.member_id)];
                latestBlogEl.textContent = blogItem && blogItem.title ? blogItem.title : '—';
                if (latestBlogDateEl) {
                    var dt = blogItem && blogItem.published_at ? String(blogItem.published_at) : '';
                    var m = dt.match(/(\d{4})-(\d{1,2})-(\d{1,2})[ T](\d{1,2}):(\d{2})/);
                    latestBlogDateEl.textContent = m ? (parseInt(m[2],10) + '/' + parseInt(m[3],10) + ' ' + m[4] + ':' + m[5]) : '　';
                }
                if (latestBlogLinkEl) {
                    var url = blogItem && blogItem.detail_url ? blogItem.detail_url : '';
                    if (latestBlogLinkEl.tagName === 'A') {
                        if (url) { latestBlogLinkEl.href = url; latestBlogLinkEl.classList.remove('pointer-events-none', 'opacity-60'); }
                        else { latestBlogLinkEl.href = '#'; latestBlogLinkEl.classList.add('pointer-events-none', 'opacity-60'); }
                    } else {
                        // 旧DOM（div）の場合は何もしない
                    }
                }
            }

            // 推し固有: 最新ニュース（カテゴリ=media）
            var latestNewsLinkEl = el('oshiLatestNewsLink');
            var latestNewsTitleEl = el('oshiLatestNewsTitle');
            var latestNewsDateEl = el('oshiLatestNewsDate');
            if (latestNewsLinkEl && latestNewsTitleEl) {
                var newsItem = this.latestNewsByMember[d.member_id] || this.latestNewsByMember[String(d.member_id)];
                var newsTitle = newsItem && newsItem.title ? String(newsItem.title) : '';
                var newsUrl = newsItem && newsItem.detail_url ? String(newsItem.detail_url) : '';
                latestNewsTitleEl.textContent = newsTitle || '—';
                if (latestNewsDateEl) {
                    var ndt = newsItem && newsItem.published_date ? String(newsItem.published_date) : '';
                    var nm = ndt.match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
                    latestNewsDateEl.textContent = nm ? (parseInt(nm[2],10) + '/' + parseInt(nm[3],10)) : '　';
                }
                if (newsUrl) {
                    latestNewsLinkEl.href = newsUrl;
                    latestNewsLinkEl.classList.remove('hidden', 'pointer-events-none', 'opacity-60');
                } else {
                    latestNewsLinkEl.href = '#';
                    latestNewsLinkEl.classList.add('pointer-events-none', 'opacity-60');
                    // 要件: 無い場合は欄ごと非表示
                    latestNewsLinkEl.classList.add('hidden');
                }
            }

            // 推し固有: 新着動画（最新1件）
            var latestVideoLinkEl = el('oshiLatestVideoLink');
            var latestVideoTitleEl = el('oshiLatestVideoTitle');
            var latestVideoThumbEl = el('oshiLatestVideoThumb');
            var latestVideoPlatformEl = el('oshiLatestVideoPlatform');
            if (latestVideoLinkEl && latestVideoTitleEl) {
                var v = this.latestVideoByMember[d.member_id] || this.latestVideoByMember[String(d.member_id)];
                var platform = v && v.platform ? String(v.platform).toLowerCase() : '';
                var mk = v && v.media_key ? String(v.media_key) : '';
                var sk = v && v.sub_key ? String(v.sub_key) : '';
                var vtitle = v && v.title ? String(v.title) : '';
                var thumb = v && v.thumbnail_url ? String(v.thumbnail_url) : '';

                var url = '';
                if (platform === 'youtube' && mk) {
                    url = 'https://www.youtube.com/watch?v=' + encodeURIComponent(mk);
                } else if (platform === 'tiktok' && mk) {
                    if (sk && sk.charAt(0) === '@' && /^\d+$/.test(mk)) {
                        url = 'https://www.tiktok.com/' + encodeURIComponent(sk) + '/video/' + encodeURIComponent(mk);
                    } else if (/^\d+$/.test(mk)) {
                        url = 'https://www.tiktok.com/@tiktok/video/' + encodeURIComponent(mk);
                    } else {
                        url = 'https://vm.tiktok.com/' + encodeURIComponent(mk) + '/';
                    }
                } else if (platform === 'instagram' && mk) {
                    url = 'https://www.instagram.com/reel/' + encodeURIComponent(mk) + '/';
                }

                latestVideoTitleEl.textContent = vtitle || '—';
                if (latestVideoThumbEl && thumb) {
                    latestVideoThumbEl.src = thumb;
                    latestVideoThumbEl.classList.remove('hidden');
                } else if (latestVideoThumbEl) {
                    latestVideoThumbEl.classList.add('hidden');
                }
                if (latestVideoPlatformEl) {
                    var ic = '<i class="fa-solid fa-play text-slate-500"></i>';
                    if (platform === 'youtube') ic = '<i class="fa-brands fa-youtube text-red-500"></i>';
                    if (platform === 'tiktok') ic = '<i class="fa-brands fa-tiktok text-slate-700"></i>';
                    if (platform === 'instagram') ic = '<i class="fa-brands fa-instagram text-pink-600"></i>';
                    latestVideoPlatformEl.innerHTML = ic;
                }

                if (url) {
                    latestVideoLinkEl.href = url;
                    latestVideoLinkEl.classList.remove('hidden', 'pointer-events-none', 'opacity-60');
                    latestVideoLinkEl.onclick = function(ev) {
                        ev.preventDefault();
                        openVideoModalWithData({
                            media_key: mk,
                            platform: platform,
                            title: vtitle || '',
                            category: v && v.category ? String(v.category) : '',
                            upload_date: v && v.upload_date ? String(v.upload_date) : '',
                            thumbnail_url: thumb || '',
                            description: v && v.description ? String(v.description) : '',
                            sub_key: sk || ''
                        }, ev);
                    };
                } else {
                    latestVideoLinkEl.href = '#';
                    latestVideoLinkEl.classList.add('hidden');
                    latestVideoLinkEl.onclick = null;
                }
            }

            var nextAp = el('oshiNextAppearanceCard');
            var nextApName = el('oshiNextAppearanceName');
            var nextApMeta = el('oshiNextAppearanceMeta');
            if (nextAp) {
                var eid = d.next_event && (d.next_event.event_id || d.next_event.id) ? (d.next_event.event_id || d.next_event.id) : null;
                if (nextApName) nextApName.textContent = (d.next_event && d.next_event.event_name) ? d.next_event.event_name : '未登録';
                if (nextApMeta) {
                    if (d.next_event && typeof d.next_event.days_left !== 'undefined' && d.next_event.days_left !== null && parseInt(d.next_event.days_left,10) >= 0) {
                        nextApMeta.innerHTML = '<span class="font-black text-orange-600">あと ' + parseInt(d.next_event.days_left,10) + ' 日</span>';
                    } else if (d.next_event && d.next_event.event_date) {
                        nextApMeta.textContent = String(d.next_event.event_date);
                    } else {
                        nextApMeta.textContent = '—';
                    }
                }
                if (eid) {
                    nextAp.onclick = function(){ location.href = '/hinata/events.php?event_id=' + eid; };
                    nextAp.onkeydown = function(ev){ if(ev.key==='Enter'||ev.key===' '){ ev.preventDefault(); location.href = '/hinata/events.php?event_id=' + eid; } };
                    nextAp.classList.add('cursor-pointer');
                } else {
                    nextAp.onclick = null;
                    nextAp.onkeydown = null;
                    nextAp.classList.remove('cursor-pointer');
                }
            }

            var songCountEl = el('oshiSongCount');
            if (songCountEl) {
                songCountEl.textContent = (d.song_count && parseInt(d.song_count,10) > 0) ? (parseInt(d.song_count,10) + ' 曲') : '—';
            }

            var eventEl = el('oshiMainEvent');
            if (eventEl) {
                if (d.next_event) {
                    eventEl.innerHTML = '<i class="fa-solid fa-calendar w-4 text-slate-400"></i>' +
                        (function(s){ var e=document.createElement('span'); e.textContent=s; return e.innerHTML; })(d.next_event.event_name) +
                        ' (' + (function(s){ var e=document.createElement('span'); e.textContent=s; return e.innerHTML; })(d.next_event.event_date) + ')';
                    eventEl.style.display = '';
                } else {
                    eventEl.style.display = 'none';
                }
            }

            var songsEl = el('oshiMainSongs');
            if (songsEl) {
                if (d.song_count > 0) {
                    songsEl.innerHTML = '<i class="fa-solid fa-music w-4 text-slate-400"></i>参加楽曲 ' + d.song_count + ' 曲';
                    songsEl.style.display = '';
                } else {
                    songsEl.style.display = 'none';
                }
            }

            var isMockUi = document.body && document.body.classList.contains('hinata-portal');
            document.querySelectorAll('.oshi-sub-card').forEach(function(card) {
                var cardLevel = parseInt(card.dataset.level);
                // 選択中の推しはミニタイルに出さない
                card.style.display = (cardLevel === level) ? 'none' : '';

                if (isMockUi) {
                    // mock時はガラス感を維持（背景クラスを上書きしない）
                    card.classList.toggle('md:ring-2', cardLevel === level);
                    card.classList.toggle('md:ring-amber-400', cardLevel === level);
                    return;
                }

                // 既存UI時の選択表現
                card.classList.toggle('bg-amber-50', cardLevel === level);
                card.classList.toggle('shadow-sm', cardLevel === level);
                var img = card.querySelector('.rounded-full');
                if (img) {
                    img.classList.toggle('ring-2', cardLevel === level);
                    img.classList.toggle('ring-amber-400', cardLevel === level);
                }
            });

            // 推しの新着エリアは廃止
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
            var extraCls = (document.body && document.body.classList.contains('hinata-portal')) ? ' mock-media-card' : '';
            return '<div class="yt-card' + extraCls + ' rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-sm" onclick="openPortalVideo(' + idx + ', event)">' +
                '<div class="aspect-video overflow-hidden bg-slate-200 shadow-sm relative group">' +
                '<img src="' + thumb + '" class="w-full h-full object-cover" loading="lazy" alt="">' +
                '<div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center"><i class="fa-solid fa-play text-white text-xl opacity-0 group-hover:opacity-100 transition drop-shadow-lg"></i></div>' +
                '</div>' +
                '<div class="bg-white px-3 py-2">' +
                '<h3 class="text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">' + title + '</h3>' +
                '<p class="text-xs text-slate-500 mt-0.5">' + date + '</p>' +
                '</div></div>';
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
                        var list = res.data.slice(0, 16);
                        cardsEl.innerHTML = list.map(function(v) { return self.renderCard(v); }).join('');
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

    // TOPICSカルーセル（左右ボタン）
    var TopicCarousel = {
        scroll: function(cardsId, dir, evt) {
            if (evt && evt.preventDefault) evt.preventDefault();
            if (evt && evt.stopPropagation) evt.stopPropagation();
            if (evt && evt.stopImmediatePropagation) evt.stopImmediatePropagation();
            var el = document.getElementById(cardsId);
            if (!el) return;
            var cardW = el.querySelector('.topic-card-v2') ? el.querySelector('.topic-card-v2').offsetWidth : 360;
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
        init: function() {
            this.updateArrows('topicCards');
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
            var idx = YtCarousel.videos.length;
            YtCarousel.videos.push({
                media_key: v.media_key, platform: 'tiktok', title: v.title || '',
                category: '', upload_date: v.upload_date || '', thumbnail_url: thumb, description: v.description || '', sub_key: v.sub_key || ''
            });
            var thumbHtml = thumb
                ? '<img src="' + thumb + '" class="w-full h-full object-cover" loading="lazy" alt="">'
                : '<div class="w-full h-full flex items-center justify-center"><i class="fa-brands fa-tiktok text-3xl text-slate-300"></i></div>';
            var extraCls = (document.body && document.body.classList.contains('hinata-portal')) ? ' mock-media-card' : '';
            return '<div class="tk-card' + extraCls + ' rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-sm" onclick="openPortalVideo(' + idx + ', event)">' +
                '<div class="aspect-[3/4] overflow-hidden bg-slate-200 shadow-sm relative group">' +
                thumbHtml +
                '<div class="absolute inset-x-0 bottom-0 p-2">' +
                '<div class="inline-flex max-w-full px-2 py-1 rounded-full bg-black/55 text-white text-[10px] font-semibold truncate">' + title + '</div>' +
                '</div>' +
                '<div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center"><i class="fa-solid fa-play text-white text-xl opacity-0 group-hover:opacity-100 transition drop-shadow-lg"></i></div>' +
                '</div></div>';
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
                        var list = res.data.slice(0, 18);
                        cardsEl.innerHTML = list.map(function(v) { return self.renderCard(v); }).join('');
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

    var ReleaseAccordion = {
        key: function(rid) { return 'releaseExpand_' + rid; },
        scrollKey: 'releaseScrollPos',
        toggle: function() {
            var el = document.getElementById('releaseAccordion');
            if (!el) return;
            var expanded = el.classList.toggle('expanded');
            var rid = el.getAttribute('data-release-id');
            if (rid) {
                try { sessionStorage.setItem(this.key(rid), expanded ? '1' : '0'); } catch(e) {}
            }
        },
        expand: function() {
            var el = document.getElementById('releaseAccordion');
            if (!el) return;
            el.classList.add('expanded');
            var rid = el.getAttribute('data-release-id');
            if (rid) {
                try { sessionStorage.setItem(this.key(rid), '1'); } catch(e) {}
            }
        },
        saveScrollAndExpand: function(rid) {
            try {
                var el = document.getElementById('releaseAccordion');
                if (el) el.classList.add('expanded');
                var main = document.querySelector('.flex-1.overflow-y-auto.custom-scroll') || document.documentElement;
                sessionStorage.setItem(this.scrollKey, String(main.scrollTop || window.scrollY || 0));
                if (rid) sessionStorage.setItem(this.key(rid), '1');
            } catch(e) {}
        },
        restore: function() {
            // 本UIでは初期は常に折りたたみ（収録曲ボタンでのみ展開）
            return;
        }
    };
    var tracksBtn = document.getElementById('releaseTracksBtn');
    if (tracksBtn) tracksBtn.addEventListener('click', function() {
        ReleaseAccordion.expand();
    });

    // ジャケット切替（Type/Edition）
    document.querySelectorAll('.release-edition-btn').forEach(function(btn, idx) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = btn.dataset && btn.dataset.jacketUrl ? btn.dataset.jacketUrl : '';
            var img = document.getElementById('releaseMainJacket');
            if (img && url) img.src = url;
            document.querySelectorAll('.release-edition-btn').forEach(function(b){
                b.classList.toggle('bg-slate-900', b === btn);
                b.classList.toggle('text-white', b === btn);
                if (b !== btn) { b.classList.add('bg-slate-100'); b.classList.remove('text-white'); b.classList.add('text-slate-600'); b.classList.remove('bg-slate-900'); }
            });
        });
        if (idx === 0) {
            // 初期状態で先頭をアクティブに
            btn.classList.add('bg-slate-900', 'text-white');
            btn.classList.remove('bg-slate-100', 'text-slate-600');
        }
    });
    document.querySelectorAll('.release-song-link').forEach(function(a) {
        a.addEventListener('click', function() {
            ReleaseAccordion.saveScrollAndExpand(this.getAttribute('data-release-id'));
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        YtCarousel.init();
        TkCarousel.init();
        TopicCarousel.init();
        YtCarousel.updateArrows('blogCards');
        var mvEl = document.getElementById('releaseMvCards');
        if (mvEl) YtCarousel.updateArrows('releaseMvCards');
        ReleaseAccordion.restore();

        // 初期表示時も「選択中の推しミニタイル非表示」を反映
        try {
            var mainWrap = document.querySelector('#oshiMainArea [data-current-level]');
            if (mainWrap && mainWrap.dataset && mainWrap.dataset.currentLevel) {
                OshiPortal.switchMain(parseInt(mainWrap.dataset.currentLevel, 10));
            }
        } catch (e) {}

        // UI Mock: 簡易検索（カード類を絞り込み）
        try {
            if (!(document.body && document.body.classList.contains('hinata-portal'))) return;
            var input = document.getElementById('portalSearchInput');
            if (!input) return;

            var searchableSelectors = [
                '.topic-card',
                'a[href^="/hinata/"]',
                '.blog-card',
                '.yt-card',
                '.tk-card',
                '.sns-link-card',
                '.app-card.hinata-portal-card',
            ];

            var norm = function(s) {
                return (s || '').toString().toLowerCase().replace(/\s+/g, ' ').trim();
            };

            var getSearchText = function(el) {
                if (!el) return '';
                if (el.dataset && el.dataset.searchText) return el.dataset.searchText;
                var t = el.getAttribute('aria-label') || el.getAttribute('title') || '';
                t += ' ' + (el.textContent || '');
                t = norm(t);
                if (el.dataset) el.dataset.searchText = t;
                return t;
            };

            var collect = function() {
                var els = [];
                searchableSelectors.forEach(function(sel) {
                    document.querySelectorAll(sel).forEach(function(el) { els.push(el); });
                });
                // 重複除去
                return Array.from(new Set(els));
            };

            var items = collect();
            var refreshCache = function() {
                items = collect();
                items.forEach(function(el) { getSearchText(el); });
            };

            // 動的ロード後にも効くように軽く再収集
            setTimeout(refreshCache, 1200);
            setTimeout(refreshCache, 2500);

            var apply = function(q) {
                q = norm(q);
                if (!q) {
                    items.forEach(function(el) { el.style.display = ''; });
                    return;
                }
                items.forEach(function(el) {
                    var hit = getSearchText(el).includes(q);
                    el.style.display = hit ? '' : 'none';
                });
            };

            var t = null;
            input.addEventListener('input', function() {
                if (t) clearTimeout(t);
                var v = input.value;
                t = setTimeout(function() { apply(v); }, 80);
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    input.value = '';
                    apply('');
                    input.blur();
                }
            });
        } catch (e) {}
    });

    document.getElementById('mobileMenuBtn').onclick = function() {
        document.getElementById('sidebar').classList.add('mobile-open');
    };
    </script>
</body>
</html>
