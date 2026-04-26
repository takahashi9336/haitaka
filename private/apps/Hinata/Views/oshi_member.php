<?php
/**
 * メンバー個別ページ View
 * 物理パス: haitaka/private/apps/Hinata/Views/oshi_member.php
 */
function _oshiContrastColor(string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '#111827';
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 186 ? '#111827' : '#ffffff';
}

$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

use App\Hinata\Model\FavoriteModel;
use App\Hinata\Model\SongModel;

$levelLabel = FavoriteModel::LEVEL_LABELS[$oshiLevel] ?? '';
$trackTypeLabels = SongModel::TRACK_TYPES_DISPLAY;

$upcomingEvents = array_filter($memberEvents, fn($e) => (int)($e['days_left'] ?? -1) >= 0);
usort($upcomingEvents, function($a, $b) {
    return strcmp((string)($a['event_date'] ?? ''), (string)($b['event_date'] ?? ''));
});
$pastEvents = array_filter($memberEvents, fn($e) => (int)($e['days_left'] ?? -1) < 0);

$displayImage = $userProfileImage
    ? '/' . htmlspecialchars($userProfileImage)
    : ($member['image_url'] ? '/assets/img/members/' . htmlspecialchars($member['image_url']) : null);
$presetImage = $member['image_url'] ? '/assets/img/members/' . htmlspecialchars($member['image_url']) : null;

$memberAge = null;
if (!empty($member['birth_date'])) {
    $bd = new \DateTime($member['birth_date']);
    $now = new \DateTime();
    $memberAge = $bd->diff($now)->y;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($member['name']) ?> - メンバー詳細 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .rec-section { position: relative; }
        .rec-scroll-wrap { position: relative; overflow: hidden; }
        .rec-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 12px; padding-bottom: 8px; }
        .rec-scroll::-webkit-scrollbar { display: none; }
        .rec-card { flex: 0 0 220px; transition: transform 0.2s ease; cursor: pointer; }
        @media (min-width: 768px) { .rec-card { flex: 0 0 260px; } }
        .rec-card:hover { transform: translateY(-4px); }
        .rec-card-portrait { flex: 0 0 140px; transition: transform 0.2s ease; cursor: pointer; }
        @media (min-width: 768px) { .rec-card-portrait { flex: 0 0 160px; } }
        .rec-card-portrait:hover { transform: translateY(-4px); }
        .rec-arrow { position: absolute; top: 50%; transform: translateY(-50%); z-index: 5; width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; color: #475569; transition: all 0.2s; }
        .rec-arrow:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #1e293b; }
        .rec-arrow.left { left: -4px; }
        .rec-arrow.right { right: -4px; }
        .rec-arrow.hidden { display: none; }
        .photo-grid img { transition: transform 0.2s; cursor: pointer; }
        .photo-grid img:hover { transform: scale(1.03); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="<?= $oshiLevel > 0 ? '/hinata/' : '/hinata/members.php' ?>" class="text-slate-400 p-2 hover:text-slate-600 transition"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <h1 class="font-black text-slate-700 text-lg tracking-tight"><?= htmlspecialchars($member['name']) ?></h1>
                <?php if ($levelLabel): ?>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full
                    <?php if ($oshiLevel === 9): ?>bg-amber-100 text-amber-600
                    <?php elseif ($oshiLevel === 8): ?>bg-pink-100 text-pink-600
                    <?php elseif ($oshiLevel === 7): ?>bg-rose-100 text-rose-500
                    <?php elseif ($oshiLevel === 1): ?>bg-amber-50 text-amber-500
                    <?php endif; ?>
                "><?= $levelLabel ?></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll">
            <!-- ヒーロー画像エリア (プロフィール + リンク統合) -->
            <div class="relative h-56 md:h-72 bg-gradient-to-br from-slate-700 to-slate-900 overflow-hidden">
                <?php if ($displayImage): ?>
                <img src="<?= $displayImage ?>" class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70" alt="">
                <?php endif; ?>
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                <?php
                $hasLinks = !empty($member['insta_url']) || !empty($member['twitter_url']);
                if ($hasLinks): ?>
                <div class="absolute top-4 right-4 md:top-6 md:right-6 flex gap-2">
                    <?php if (!empty($member['twitter_url'])): ?>
                    <a href="<?= htmlspecialchars($member['twitter_url']) ?>" target="_blank" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-white hover:opacity-80 transition shadow" title="X"><i class="fa-brands fa-x-twitter text-sm"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="absolute bottom-0 left-0 right-0 p-6">
                    <div class="max-w-5xl mx-auto flex items-end gap-5">
                        <div class="relative group w-24 h-24 md:w-32 md:h-32 shrink-0">
                            <div class="w-full h-full rounded-xl overflow-hidden bg-white/20 shadow-2xl ring-4 ring-white/30">
                                <?php if ($displayImage): ?>
                                <img id="memberProfileImg" src="<?= $displayImage ?>" class="w-full h-full object-cover" alt="">
                                <?php else: ?>
                                <div id="memberProfileImg" class="w-full h-full flex items-center justify-center text-white/50"><i class="fa-solid fa-user text-4xl"></i></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($oshiLevel === 9): ?>
                            <div class="absolute -top-2 -right-2 w-8 h-8 md:w-9 md:h-9 rounded-full bg-amber-400 text-white shadow-lg flex items-center justify-center ring-4 ring-white/30" title="最推し">
                                <i class="fa-solid fa-crown text-sm md:text-base"></i>
                            </div>
                            <?php endif; ?>
                            <label class="absolute inset-0 rounded-xl flex items-center justify-center bg-black/0 group-hover:bg-black/40 cursor-pointer transition">
                                <span class="opacity-0 group-hover:opacity-100 transition text-white text-sm flex flex-col items-center gap-1">
                                    <i class="fa-solid fa-camera"></i>
                                    <span class="text-[9px] font-bold">変更</span>
                                </span>
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="MemberProfile.upload(this.files)">
                            </label>
                        </div>
                        <div class="text-white pb-1 flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-[10px] font-bold opacity-70"><?= htmlspecialchars($member['generation']) ?>期生</span>
                                <?php if ($member['color1']): ?>
                                <span class="w-3 h-3 rounded-full border border-white/40 shrink-0" style="background:<?= htmlspecialchars($member['color1']) ?>" title="<?= htmlspecialchars($member['color1_name'] ?? '') ?>"></span>
                                <?php endif; ?>
                                <?php if ($member['color2']): ?>
                                <span class="w-3 h-3 rounded-full border border-white/40 shrink-0" style="background:<?= htmlspecialchars($member['color2']) ?>" title="<?= htmlspecialchars($member['color2_name'] ?? '') ?>"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-2xl md:text-4xl font-black leading-tight"><?= htmlspecialchars($member['name']) ?></h2>
                            </div>
                            <p class="text-[10px] md:text-xs text-white/60 mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                <?php if ($member['birth_date']): ?><span><?= date('Y/m/d', strtotime($member['birth_date'])) ?><?php if ($memberAge !== null): ?> (<?= $memberAge ?>歳)<?php endif; ?></span><?php endif; ?>
                                <?php if ($member['blood_type']): ?><span><?= htmlspecialchars($member['blood_type']) ?>型</span><?php endif; ?>
                                <?php if ($member['height']): ?><span><?= htmlspecialchars($member['height']) ?>cm</span><?php endif; ?>
                                <?php if (!empty($member['birth_place'])): ?><span><?= htmlspecialchars($member['birth_place']) ?></span><?php endif; ?>
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php if (!empty($member['blog_url'])): ?>
                                <a href="<?= htmlspecialchars($member['blog_url']) ?>" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-bold transition shadow-sm">
                                    <i class="fa-solid fa-blog text-[11px]"></i>
                                    <span>公式ブログ</span>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($member['insta_url'])): ?>
                                <a href="<?= htmlspecialchars($member['insta_url']) ?>" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-bold transition shadow-sm">
                                    <i class="fa-brands fa-instagram text-[11px]"></i>
                                    <span>Instagram</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="max-w-5xl mx-auto space-y-8">

                    <!-- 推し活タイムライン -->
                    <section>
                        <div class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                            <div class="px-4 py-3 flex items-center gap-2 border-b <?= $cardBorder ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="w-4 h-4 text-orange-400">
                                    <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path>
                                </svg>
                                <h3 class="text-base font-semibold text-slate-700">タイムライン</h3>
                            </div>
                            <div class="px-2 py-2">
                                <div id="timelineContainer" class="space-y-0">
                                    <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-slate-300"></i></div>
                                </div>
                                <div id="timelineExpand" class="hidden mt-2 text-center">
                                    <button onclick="OshiTimeline.expand()" class="text-[10px] font-bold text-indigo-500 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-full transition inline-flex items-center gap-1"><i class="fa-solid fa-chevron-down text-[8px]"></i><span>もっと見る</span></button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 個人活動 -->
                    <?php if (!empty($memberActivities)): ?>
                    <section>
                        <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2 font-semibold text-slate-700">
                                    <i class="fa-solid fa-briefcase w-4 h-4 text-indigo-500"></i>
                                    <h3 class="text-base font-semibold text-slate-700">個人活動</h3>
                                </div>
                                <span class="text-[10px] text-slate-400"><?= count($memberActivities) ?> 件</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($memberActivities as $act):
                                    $catInfo = $activityCategories[$act['category']] ?? $activityCategories['other'];
                                ?>
                                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition group flex">
                                    <?php if (!empty($act['image_url'])): ?>
                                    <div class="w-24 md:w-28 shrink-0 bg-slate-100">
                                        <img src="/assets/img/activities/<?= htmlspecialchars($act['image_url']) ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0 p-4">
                                        <div class="flex items-center gap-1.5 mb-1.5">
                                            <span class="inline-flex items-center gap-1 text-[8px] font-bold px-1.5 py-0.5 rounded <?= $catInfo['pill'] ?>">
                                                <i class="<?= $catInfo['icon'] ?> text-[7px]"></i><?= htmlspecialchars($catInfo['label']) ?>
                                            </span>
                                            <?php if (!empty($act['start_date'])): ?>
                                            <span class="text-[9px] text-slate-400"><?= date('Y/m', strtotime($act['start_date'])) ?>〜<?= !empty($act['end_date']) ? date('Y/m', strtotime($act['end_date'])) : '' ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-700 leading-snug mb-1"><?= htmlspecialchars($act['title']) ?></h4>
                                        <?php if (!empty($act['description'])): ?>
                                        <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed mb-2"><?= htmlspecialchars($act['description']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($act['url'])): ?>
                                        <a href="<?= htmlspecialchars($act['url']) ?>" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 text-[10px] font-bold text-white px-3 py-1.5 rounded-full bg-indigo-500 hover:bg-indigo-600 transition shadow-sm">
                                            <i class="<?= $catInfo['icon'] ?> text-[9px]"></i>
                                            <?= htmlspecialchars($act['url_label'] ?: '詳しく見る') ?>
                                            <i class="fa-solid fa-arrow-right text-[8px]"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- 最新ブログ (画像カルーセル) -->
                    <?php if (!empty($memberBlogPosts)): ?>
                    <section class="rec-section">
                        <div class="bg-white rounded-xl border <?= $cardBorder ?> p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2 font-semibold text-slate-700">
                                    <i class="fa-solid fa-pen-fancy w-4 h-4 text-pink-500"></i>
                                    <h3 class="text-base">最新ブログ</h3>
                                </div>
                                <?php if (!empty($member['blog_url'])): ?>
                                <a href="<?= htmlspecialchars($member['blog_url']) ?>" target="_blank" rel="noopener" class="text-xs text-slate-500 hover:text-pink-500 transition">もっと見る →</a>
                                <?php else: ?>
                                <span class="text-xs text-slate-500"><?= count($memberBlogPosts) ?> 件</span>
                                <?php endif; ?>
                            </div>
                            <div class="rec-scroll-wrap">
                                <button class="rec-arrow left hidden" onclick="OshiRec.scroll('blogCards', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                                <div id="blogCards" class="rec-scroll">
                                    <?php foreach ($memberBlogPosts as $bp): ?>
                                    <div class="rec-card-portrait block relative group rounded-2xl overflow-hidden border <?= $cardBorder ?> bg-white shadow-sm">
                                        <a href="<?= htmlspecialchars($bp['detail_url']) ?>" target="_blank" rel="noopener" class="block">
                                            <div class="aspect-[3/4] overflow-hidden bg-slate-100 shadow-sm relative">
                                                <?php if ($bp['thumbnail_url']): ?>
                                                <img src="<?= htmlspecialchars($bp['thumbnail_url']) ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                                                <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-pen-fancy text-2xl"></i></div>
                                                <?php endif; ?>
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"></div>
                                            </div>
                                            <div class="bg-white px-3 py-2">
                                                <h4 class="text-sm font-semibold text-slate-800 line-clamp-1 leading-snug"><?= htmlspecialchars($bp['title'] ?: '(無題)') ?></h4>
                                                <p class="text-xs text-slate-500 truncate mt-0.5"><?= $bp['published_at'] ? date('m/d H:i', strtotime($bp['published_at'])) : '' ?></p>
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
                                <button class="rec-arrow right" onclick="OshiRec.scroll('blogCards', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- ニュース + スケジュール -->
                    <?php if (!empty($memberNews) || !empty($memberSchedule)): ?>
                    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if (!empty($memberNews)): ?>
                        <div>
                            <div class="bg-white rounded-xl border <?= $cardBorder ?> p-2">
                                <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3 px-2 pt-1">
                                    <i class="fa-solid fa-newspaper w-4 h-4 text-blue-500"></i>
                                    <h3 class="text-base">ニュース</h3>
                                </div>
                                <div class="bg-white rounded-xl overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <?php foreach ($memberNews as $news): ?>
                                        <a href="<?= htmlspecialchars($news['detail_url']) ?>" target="_blank" class="flex items-start gap-3 px-2 py-3 hover:bg-slate-50 transition group">
                                            <div class="shrink-0 text-center w-10 mt-0.5">
                                                <p class="text-xs font-black text-blue-500"><?= date('m/d', strtotime($news['published_date'])) ?></p>
                                                <?php if ($news['category']): ?>
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[7px] font-bold text-blue-500 bg-blue-50 mt-1 whitespace-nowrap"><?= htmlspecialchars($news['category']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-slate-700 line-clamp-2 leading-snug mt-0.5 group-hover:text-blue-600 transition"><?= htmlspecialchars($news['title']) ?></p>
                                            </div>
                                            <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 group-hover:text-slate-500 transition mt-1.5 shrink-0"></i>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($memberSchedule)): ?>
                        <div>
                            <div class="bg-white rounded-xl border <?= $cardBorder ?> p-2">
                                <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3 px-2 pt-1">
                                    <i class="fa-solid fa-calendar-day w-4 h-4 text-emerald-500"></i>
                                    <h3 class="text-base">スケジュール</h3>
                                </div>
                                <div class="bg-white rounded-xl overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <?php foreach ($memberSchedule as $sch): ?>
                                        <a href="<?= htmlspecialchars($sch['detail_url']) ?>" target="_blank" class="flex items-start gap-3 px-2 py-3 hover:bg-slate-50 transition group">
                                            <div class="shrink-0 text-center w-10 mt-0.5">
                                                <p class="text-xs font-black text-emerald-600"><?= date('m/d', strtotime($sch['schedule_date'])) ?></p>
                                                <?php if ($sch['time_text']): ?>
                                                <p class="text-[8px] text-slate-400"><?= htmlspecialchars($sch['time_text']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($sch['category']): ?>
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[7px] font-bold text-emerald-500 bg-emerald-50 mt-1 whitespace-nowrap"><?= htmlspecialchars($sch['category']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-slate-700 line-clamp-2 leading-snug mt-0.5 group-hover:text-emerald-600 transition"><?= htmlspecialchars($sch['title']) ?></p>
                                            </div>
                                            <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 group-hover:text-slate-500 transition mt-1.5 shrink-0"></i>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>

                    <!-- 動画 (タブ切替) -->
                    <?php
                    $videoTabs = [];
                    if (!empty($soloVideos))          $videoTabs[] = ['key' => 'solo',      'label' => 'ソロ',      'icon' => 'fa-solid fa-user text-blue-500',        'count' => count($soloVideos),          'videos' => $soloVideos];
                    if (!empty($youtubeGroupVideos))   $videoTabs[] = ['key' => 'youtube',   'label' => 'YouTube',   'icon' => 'fa-brands fa-youtube text-red-500',     'count' => count($youtubeGroupVideos),  'videos' => $youtubeGroupVideos];
                    if (!empty($instagramVideos))      $videoTabs[] = ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'fa-brands fa-instagram text-pink-500',  'count' => count($instagramVideos),     'videos' => $instagramVideos];
                    if (!empty($tiktokVideos))         $videoTabs[] = ['key' => 'tiktok',    'label' => 'TikTok',    'icon' => 'fa-brands fa-tiktok text-slate-700',    'count' => count($tiktokVideos),        'videos' => $tiktokVideos];
                    ?>
                    <?php if (!empty($videoTabs)): ?>
                    <section class="rec-section">
                        <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2 font-semibold text-slate-700">
                                    <i class="fa-solid fa-play-circle w-4 h-4 text-orange-500"></i>
                                    <h3 class="text-base">動画</h3>
                                </div>
                                <div class="flex gap-1 items-center">
                                    <?php foreach ($videoTabs as $ti => $vt): ?>
                                    <button class="vid-tab text-[10px] font-bold px-3 py-1 rounded-full transition <?= $ti === 0 ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' ?>"
                                            data-idx="<?= $ti ?>" onclick="switchVideoTab(<?= $ti ?>)">
                                        <?= $vt['label'] ?> <span class="opacity-60"><?= $vt['count'] ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php foreach ($videoTabs as $ti => $vt): ?>
                            <div class="vid-tab-panel" data-panel="<?= $ti ?>"<?= $ti > 0 ? ' style="display:none"' : '' ?>>
                                <div class="rec-scroll-wrap">
                                    <button class="rec-arrow left hidden" onclick="OshiRec.scroll('vidCards<?= $ti ?>', -1)"><i class="fa-solid fa-chevron-left text-sm"></i></button>
                                    <div id="vidCards<?= $ti ?>" class="rec-scroll">
                                        <?php foreach ($vt['videos'] as $v):
                                            $videoData = htmlspecialchars(json_encode([
                                                'media_key' => $v['media_key'], 'platform' => $v['platform'],
                                                'title' => $v['title'], 'category' => $v['category'],
                                                'upload_date' => $v['upload_date'], 'thumbnail_url' => $v['thumbnail_url'],
                                                'description' => $v['description'] ?? '', 'sub_key' => $v['sub_key'] ?? '',
                                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                            $thumb = $v['thumbnail_url'] ?: ($v['platform'] === 'youtube' ? "https://img.youtube.com/vi/{$v['media_key']}/mqdefault.jpg" : '');
                                            $pIcon = match($v['platform']) { 'instagram' => 'fa-brands fa-instagram', 'tiktok' => 'fa-brands fa-tiktok', default => 'fa-brands fa-youtube' };
                                            $pColor = match($v['platform']) { 'instagram' => 'bg-gradient-to-br from-purple-500 to-pink-500', 'tiktok' => 'bg-slate-800', default => 'bg-red-600' };
                                        ?>
                                        <div class="rec-card" data-video="<?= $videoData ?>" onclick="openOshiVideo(this, event)">
                                            <div class="aspect-square relative overflow-hidden rounded-xl bg-black shadow-sm mb-2 group">
                                                <?php if ($thumb): ?><img src="<?= htmlspecialchars($thumb) ?>" class="w-full h-full object-contain" loading="lazy" alt=""><?php else: ?><div class="w-full h-full flex items-center justify-center"><i class="<?= $pIcon ?> text-3xl text-slate-500"></i></div><?php endif; ?>
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-white/10 transition flex items-center justify-center"><i class="fa-solid fa-play text-white text-xl opacity-0 group-hover:opacity-100 transition drop-shadow-lg"></i></div>
                                                <span class="absolute top-1.5 left-1.5 w-5 h-5 <?= $pColor ?> rounded-full flex items-center justify-center shadow"><i class="<?= $pIcon ?> text-[10px] text-white"></i></span>
                                                <?php if (!empty($v['category'])): ?><span class="absolute top-1.5 right-1.5 text-[8px] font-bold bg-black/60 text-white px-1.5 py-0.5 rounded"><?= htmlspecialchars($v['category']) ?></span><?php endif; ?>
                                            </div>
                                            <h4 class="text-[11px] font-bold text-slate-700 line-clamp-2 leading-snug mb-0.5"><?= htmlspecialchars($v['title'] ?: $vt['label']) ?></h4>
                                            <p class="text-[10px] text-slate-400"><?= $v['upload_date'] ? date('Y/m/d', strtotime($v['upload_date'])) : '' ?></p>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="rec-arrow right" onclick="OshiRec.scroll('vidCards<?= $ti ?>', 1)"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="mt-3 text-right">
                                <a href="/hinata/media_list.php?member_id=<?= $member['id'] ?>" class="text-xs text-slate-500 hover:text-slate-700 transition">すべて見る →</a>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- 参加楽曲 -->
                    <?php if (!empty($memberSongs)): ?>
                    <section>
                        <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                            <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3">
                                <i class="fa-solid fa-music w-4 h-4 text-violet-500"></i>
                                <h3 class="text-base">参加楽曲 (<?= count($memberSongs) ?>曲)</h3>
                            </div>
                            <div class="bg-white rounded-xl overflow-hidden">
                                <div id="songMiniPlayer" class="hidden border-b border-slate-200 bg-slate-50/80">
                                    <div class="flex items-center gap-2 px-4 py-2">
                                        <p id="songMiniTitle" class="text-[11px] font-bold text-slate-700 flex-1 truncate"></p>
                                        <button onclick="SongPlayer.close()" class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 hover:bg-slate-300 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                    <div id="songMiniEmbed" class="px-4 pb-3"></div>
                                </div>
                                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                                    <?php foreach ($memberSongs as $s):
                                        $hasApple = !empty($s['apple_music_url']);
                                        $hasSpotify = !empty($s['spotify_url']);
                                        $hasStream = $hasApple || $hasSpotify;
                                    ?>
                                    <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition group">
                                        <a href="/hinata/song.php?id=<?= $s['id'] ?>" class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                                            <?php if ($s['is_center']): ?>
                                            <i class="fa-solid fa-crown text-amber-500 text-xs"></i>
                                            <?php else: ?>
                                            <i class="fa-solid fa-music text-violet-400 text-xs"></i>
                                            <?php endif; ?>
                                        </a>
                                        <a href="/hinata/song.php?id=<?= $s['id'] ?>" class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($s['title']) ?></p>
                                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($s['release_title']) ?> &middot; <?= $trackTypeLabels[$s['track_type']] ?? $s['track_type'] ?></p>
                                        </a>
                                        <?php if ($s['is_center']): ?>
                                        <span class="text-[8px] font-black bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full shrink-0">CENTER</span>
                                        <?php endif; ?>
                                        <?php if ($hasStream): ?>
                                        <div class="flex gap-1 shrink-0">
                                            <?php if ($hasApple): ?>
                                            <button onclick="SongPlayer.play('apple', '<?= htmlspecialchars(addslashes($s['apple_music_url'])) ?>', '<?= htmlspecialchars(addslashes($s['title'])) ?>')"
                                                    class="w-7 h-7 rounded-full bg-slate-100 hover:bg-pink-50 flex items-center justify-center transition text-slate-400 hover:text-pink-500" title="Apple Musicで再生">
                                                <i class="fa-brands fa-apple text-xs"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($hasSpotify): ?>
                                            <button onclick="SongPlayer.play('spotify', '<?= htmlspecialchars(addslashes($s['spotify_url'])) ?>', '<?= htmlspecialchars(addslashes($s['title'])) ?>')"
                                                    class="w-7 h-7 rounded-full bg-slate-100 hover:bg-emerald-50 flex items-center justify-center transition text-slate-400 hover:text-emerald-500" title="Spotifyで再生">
                                                <i class="fa-brands fa-spotify text-xs"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- イベント -->
                    <?php if (!empty($memberEvents)): ?>
                    <section>
                        <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                            <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3">
                                <i class="fa-solid fa-calendar w-4 h-4 text-sky-500"></i>
                                <h3 class="text-base">イベント</h3>
                            </div>
                            <?php if (!empty($upcomingEvents)): ?>
                            <div class="mb-4">
                                <p class="text-[10px] font-bold text-sky-500 mb-2">今後の予定</p>
                                <div class="space-y-2">
                                    <?php foreach ($upcomingEvents as $e): ?>
                                    <a href="/hinata/events.php?event_id=<?= (int)$e['id'] ?>" class="flex items-center gap-3 bg-white rounded-lg px-4 py-3 border border-slate-100 hover:bg-slate-50 transition group">
                                        <div class="w-10 text-center shrink-0">
                                            <p class="text-sm font-black text-sky-600"><?= date('m/d', strtotime($e['event_date'])) ?></p>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($e['event_name']) ?></p>
                                            <?php if ($e['event_place']): ?><p class="text-[10px] text-slate-400"><?= htmlspecialchars($e['event_place']) ?></p><?php endif; ?>
                                        </div>
                                        <span class="text-xs font-black text-sky-600">あと<?= $e['days_left'] ?>日</span>
                                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-slate-500 transition shrink-0"></i>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($pastEvents)): ?>
                            <details class="mt-3">
                                <summary class="text-[10px] font-bold text-slate-400 cursor-pointer hover:text-slate-600 transition flex items-center gap-1">
                                    <i class="fa-solid fa-chevron-right text-[8px] transition-transform"></i>過去のイベント (<?= count($pastEvents) ?>)
                                </summary>
                                <div class="bg-white rounded-xl overflow-hidden mt-2">
                                    <div class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                                        <?php foreach (array_slice($pastEvents, 0, 10) as $e): ?>
                                        <div class="flex items-center gap-3 px-4 py-2.5">
                                            <span class="text-[10px] font-bold text-slate-400 w-16 shrink-0"><?= date('Y/m/d', strtotime($e['event_date'])) ?></span>
                                            <span class="text-xs text-slate-600 truncate"><?= htmlspecialchars($e['event_name']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </details>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- マイフォト（推し設定時のみ表示） -->
                    <?php if ($oshiLevel > 0): ?>
                    <section>
                        <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                            <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3">
                                <i class="fa-solid fa-camera w-4 h-4 text-emerald-500"></i>
                                <h3 class="text-base">マイフォト</h3>
                            </div>
                            <?php if (!empty($oshiImages)): ?>
                            <div class="photo-grid grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                <?php foreach ($oshiImages as $img): ?>
                                <div class="relative aspect-square rounded-xl overflow-hidden bg-slate-100 shadow-sm group">
                                    <img src="/<?= htmlspecialchars($img['image_path']) ?>" class="w-full h-full object-cover cursor-zoom-in" alt="" loading="lazy"
                                         onclick="if(window.BlogImageZoom){ BlogImageZoom.open(this.src); }">
                                    <?php if ($img['caption']): ?>
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition">
                                        <p class="text-[10px] text-white"><?= htmlspecialchars($img['caption']) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <button onclick="OshiPhoto.deleteImage(<?= $img['id'] ?>)" class="absolute top-1.5 right-1.5 w-6 h-6 bg-red-500/80 text-white rounded-full flex items-center justify-center text-[10px] opacity-0 group-hover:opacity-100 transition hover:bg-red-600"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <div class="mt-4">
                                <label class="flex items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 transition">
                                    <i class="fa-solid fa-cloud-arrow-up text-slate-400 text-lg"></i>
                                    <span class="text-xs font-bold text-slate-500">画像をアップロード</span>
                                    <input type="file" id="photoUploadInput" accept="image/jpeg,image/png,image/webp" multiple class="hidden" onchange="OshiPhoto.upload(this.files)">
                                </label>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- ミーグリネタ -->
                    <section>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-black text-slate-500 tracking-wider"><i class="fa-solid fa-comment-dots text-sky-500 mr-2"></i>ミーグリネタ</h3>
                            <a href="/hinata/talk.php" class="text-[10px] font-bold text-slate-400 hover:text-slate-600 transition">ネタ帳を開く <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div id="netaInputArea" class="mb-4">
                            <div class="flex gap-2">
                                <input type="text" id="netaInput" placeholder="ネタを追加..." class="flex-1 text-sm border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-200 focus:border-sky-300 transition">
                                <button onclick="MemberNeta.add()" class="px-4 py-2.5 bg-sky-500 text-white rounded-lg text-xs font-bold hover:bg-sky-600 transition shadow-sm shrink-0"><i class="fa-solid fa-plus mr-1"></i>追加</button>
                            </div>
                        </div>
                        <?php if (!empty($memberNeta)): ?>
                        <div id="netaList" class="space-y-2">
                            <?php foreach ($memberNeta as $neta): ?>
                            <div class="neta-row flex items-start gap-3 bg-white rounded-xl border border-slate-200 shadow-sm p-4 group <?= $neta['status'] === 'done' ? 'opacity-50' : '' ?>" data-id="<?= $neta['id'] ?>">
                                <input type="checkbox" <?= $neta['status'] === 'done' ? 'checked' : '' ?>
                                       onchange="MemberNeta.toggleStatus(<?= $neta['id'] ?>, this.checked)"
                                       class="w-5 h-5 rounded border-slate-300 text-sky-500 mt-0.5 shrink-0 cursor-pointer">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-700 <?= $neta['status'] === 'done' ? 'line-through' : '' ?> neta-text"><?= htmlspecialchars($neta['content']) ?></p>
                                    <p class="text-[10px] text-slate-400 mt-1"><?= date('Y/m/d H:i', strtotime($neta['created_at'])) ?></p>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition shrink-0">
                                    <button onclick="MemberNeta.edit(<?= $neta['id'] ?>, this)" class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 hover:text-sky-500 hover:bg-sky-50 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-pen"></i></button>
                                    <button onclick="MemberNeta.remove(<?= $neta['id'] ?>)" class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div id="netaEmpty" class="bg-slate-50 rounded-xl border border-slate-100 p-6 text-center">
                            <p class="text-xs text-slate-400">まだネタがありません。上のフォームから追加しましょう。</p>
                        </div>
                        <?php endif; ?>
                    </section>

                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../../components/video_modal.php'; ?>
    <?php include __DIR__ . '/../../../components/blog_image_modal.php'; ?>

    <script>
    function openOshiVideo(cardEl, ev) {
        var dataStr = cardEl.getAttribute('data-video');
        if (!dataStr) return;
        try { openVideoModalWithData(JSON.parse(dataStr), ev); } catch(e) {}
    }

    var OshiRec = {
        scroll: function(cardsId, dir) {
            var el = document.getElementById(cardsId);
            if (!el) return;
            var card = el.querySelector('.rec-card');
            var cardW = card ? card.offsetWidth : 260;
            el.scrollBy({ left: dir * (cardW + 12) * 3, behavior: 'smooth' });
        },
        updateArrows: function(cardsId) {
            var el = document.getElementById(cardsId);
            if (!el) return;
            var wrap = el.closest('.rec-scroll-wrap');
            if (!wrap) return;
            var leftBtn = wrap.querySelector('.rec-arrow.left');
            var rightBtn = wrap.querySelector('.rec-arrow.right');
            if (!leftBtn || !rightBtn) return;
            var update = function() {
                leftBtn.classList.toggle('hidden', el.scrollLeft <= 4);
                rightBtn.classList.toggle('hidden', el.scrollLeft + el.clientWidth >= el.scrollWidth - 4);
            };
            el.addEventListener('scroll', update, { passive: true });
            update();
        },
        init: function() {
            var ids = ['blogCards'];
            for (var i = 0; i < 10; i++) {
                if (document.getElementById('vidCards' + i)) ids.push('vidCards' + i);
            }
            for (var j = 0; j < ids.length; j++) {
                if (document.getElementById(ids[j])) this.updateArrows(ids[j]);
            }
        }
    };

    function switchVideoTab(idx) {
        document.querySelectorAll('.vid-tab-panel').forEach(function(p) {
            p.style.display = parseInt(p.dataset.panel) === idx ? '' : 'none';
        });
        document.querySelectorAll('.vid-tab').forEach(function(btn) {
            var active = parseInt(btn.dataset.idx) === idx;
            btn.className = 'vid-tab text-[10px] font-bold px-3 py-1 rounded-full transition ' +
                (active ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200');
        });
        var cardsId = 'vidCards' + idx;
        if (document.getElementById(cardsId)) OshiRec.updateArrows(cardsId);
    }

    document.addEventListener('DOMContentLoaded', function() { OshiRec.init(); });

    var SongPlayer = {
        currentUrl: null,
        play: function(type, url, title) {
            if (!url) return;
            var player = document.getElementById('songMiniPlayer');
            var embed = document.getElementById('songMiniEmbed');
            var titleEl = document.getElementById('songMiniTitle');
            if (!player || !embed) return;

            if (this.currentUrl === url) {
                this.close();
                return;
            }
            this.currentUrl = url;

            titleEl.textContent = title;
            var iframe = '';
            if (type === 'apple') {
                var embedUrl = url.replace('music.apple.com', 'embed.music.apple.com');
                iframe = '<iframe src="' + embedUrl + '" height="175" frameborder="0" allow="autoplay *; encrypted-media *;" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation-by-user-activation" style="width:100%; border-radius:12px; overflow:hidden; background:transparent;"></iframe>';
            } else if (type === 'spotify') {
                var embedUrl = url.replace('open.spotify.com/', 'open.spotify.com/embed/');
                iframe = '<iframe src="' + embedUrl + '" height="152" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="width:100%; border-radius:12px;"></iframe>';
            }
            embed.innerHTML = iframe;
            player.classList.remove('hidden');
            player.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },
        close: function() {
            var player = document.getElementById('songMiniPlayer');
            var embed = document.getElementById('songMiniEmbed');
            if (player) player.classList.add('hidden');
            if (embed) embed.innerHTML = '';
            this.currentUrl = null;
        }
    };

    var MemberProfile = {
        memberId: <?= $member['id'] ?>,
        upload: function(files) {
            if (!files || files.length === 0) return;
            var fd = new FormData();
            fd.append('image', files[0]);
            fd.append('member_id', this.memberId);
            fetch('/hinata/api/save_member_profile_image.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.status === 'success') {
                        var img = document.getElementById('memberProfileImg');
                        if (img && img.tagName === 'IMG') {
                            img.src = '/' + res.image_path + '?t=' + Date.now();
                        } else {
                            location.reload();
                        }
                        var bgImg = img ? img.closest('.overflow-hidden') : null;
                        var heroBg = document.querySelector('.relative.h-56 img');
                        if (heroBg) heroBg.src = '/' + res.image_path + '?t=' + Date.now();
                    } else {
                        alert('エラー: ' + (res.message || ''));
                    }
                });
        }
    };

    var OshiPhoto = {
        memberId: <?= $member['id'] ?>,

        upload: function(files) {
            if (!files || files.length === 0) return;
            var self = this;
            Array.from(files).forEach(function(file) {
                var fd = new FormData();
                fd.append('image', file);
                fd.append('member_id', self.memberId);
                fetch('/hinata/api/oshi_image_upload.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.status === 'success') {
                            location.reload();
                        } else {
                            alert('アップロードエラー: ' + (res.message || ''));
                        }
                    });
            });
        },

        deleteImage: function(imageId) {
            if (!confirm('この画像を削除しますか？')) return;
            fetch('/hinata/api/oshi_image_delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: imageId })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') location.reload();
                else alert('削除エラー: ' + (res.message || ''));
            });
        }
    };

    var MemberNeta = {
        memberId: <?= $member['id'] ?>,

        add: function() {
            var input = document.getElementById('netaInput');
            var content = (input.value || '').trim();
            if (!content) return;
            fetch('/hinata/api/save_neta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ member_id: this.memberId, content: content })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') location.reload();
                else alert('保存エラー: ' + (res.message || ''));
            });
        },

        toggleStatus: function(id, checked) {
            fetch('/hinata/api/update_neta_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: checked ? 'done' : 'stock' })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') location.reload();
            });
        },

        edit: function(id, btn) {
            var row = btn.closest('.neta-row');
            if (!row) return;
            var textEl = row.querySelector('.neta-text');
            if (!textEl) return;
            var current = textEl.textContent;
            var newContent = prompt('ネタを編集', current);
            if (newContent === null || newContent.trim() === '' || newContent === current) return;
            fetch('/hinata/api/update_neta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, content: newContent.trim() })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') location.reload();
                else alert('更新エラー: ' + (res.message || ''));
            });
        },

        remove: function(id) {
            if (!confirm('このネタを削除しますか？')) return;
            fetch('/hinata/api/delete_neta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') location.reload();
                else alert('削除エラー: ' + (res.message || ''));
            });
        }
    };

    document.getElementById('netaInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); MemberNeta.add(); }
    });

    var OshiTimeline = {
        memberId: <?= $member['id'] ?>,
        allItems: [],
        visibleCount: 5,
        expanded: false,
        loading: false,

        typeConfig: {
            blog:     { icon: 'fa-solid fa-pen-fancy',   color: 'text-sky-600',     bg: 'bg-sky-500',     pill: 'bg-sky-100 text-sky-700',      label: 'ブログ' },
            news:     { icon: 'fa-solid fa-newspaper',    color: 'text-blue-600',    bg: 'bg-blue-500',    pill: 'bg-blue-100 text-blue-700',    label: 'ニュース' },
            schedule: { icon: 'fa-solid fa-calendar-day', color: 'text-emerald-600', bg: 'bg-emerald-500', pill: 'bg-emerald-100 text-emerald-700', label: 'スケジュール' },
            event:    { icon: 'fa-solid fa-flag',         color: 'text-amber-600',   bg: 'bg-amber-500',   pill: 'bg-amber-100 text-amber-700',  label: 'イベント' },
            video:    { icon: 'fa-solid fa-play',         color: 'text-red-600',     bg: 'bg-red-500',     pill: 'bg-red-100 text-red-700',      label: '動画' }
        },

        esc: function(s) {
            if (!s) return '';
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        },

        formatDate: function(dateStr) {
            if (!dateStr) return '';
            var d = new Date(dateStr);
            if (isNaN(d)) return dateStr.substring(0, 10);
            return (d.getMonth() + 1) + '/' + d.getDate();
        },

        parseVideoExtra: function(item) {
            if (item.type !== 'video' || !item.extra) return null;
            try {
                return typeof item.extra === 'string' ? JSON.parse(item.extra) : item.extra;
            } catch(e) { return null; }
        },

        renderItem: function(item, index) {
            var cfg = this.typeConfig[item.type] || this.typeConfig.news;
            var videoData = this.parseVideoExtra(item);

            var isClickable = (item.type === 'video' && videoData) || item.url;
            var wrapTag = isClickable ? 'a' : 'div';
            var clickAttr = '';
            if (item.type === 'video' && videoData) {
                clickAttr = ' href="javascript:void(0)" onclick="OshiTimeline.playVideo(' + index + ', event)" ';
            } else if (item.url) {
                clickAttr = ' href="' + this.esc(item.url) + '" target="_blank" ';
            }

            var isLast = (index === this.allItems.length - 1);

            var html = '<' + wrapTag + clickAttr + 'class="block rounded-xl pl-2 pr-3 py-2 transition ' + (isClickable ? 'hover:bg-slate-50 cursor-pointer' : '') + '">';
            html += '<div class="flex gap-4">';

            // 左: 丸 + 縦線
            html += '<div class="relative w-5 shrink-0 flex justify-center">';
            html += '<span class="mt-1.5 w-2.5 h-2.5 rounded-full ' + cfg.bg + '"></span>';
            if (!isLast) {
                // ○と接続しない（上下どちらの○にも届かせない）
                html += '<span class="absolute top-6 -bottom-2 w-px bg-slate-200/80"></span>';
            }
            html += '</div>';

            // 右: 日付 + バッジ / 内容
            html += '<div class="flex-1 min-w-0">';
            html += '<div class="flex items-center gap-2">';
            html += '<span class="text-[11px] font-bold text-slate-500 leading-tight">' + this.formatDate(item.event_date) + '</span>';
            html += '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold ' + cfg.pill + '">';
            html += '<i class="' + cfg.icon + ' text-[8px]"></i>' + cfg.label;
            html += '</span>';
            html += '</div>';

            html += '<div class="mt-0.5 flex items-start gap-3">';
            html += '<div class="flex-1 min-w-0">';
            html += '<p class="text-sm font-bold text-slate-800 leading-snug">' + this.esc(item.title || '(無題)') + '</p>';
            // サブ情報
            if (item.type === 'video' && videoData) {
                var pIcon = videoData.platform === 'youtube' ? 'fa-brands fa-youtube text-red-400' : videoData.platform === 'tiktok' ? 'fa-brands fa-tiktok text-slate-400' : 'fa-brands fa-instagram text-pink-400';
                if (videoData.category) {
                    html += '<p class="text-[10px] text-slate-400 mt-0.5"><i class="' + pIcon + ' mr-1"></i>' + this.esc(videoData.category) + '</p>';
                }
            }
            html += '</div>';

            // 右: サムネイル（動画とブログのみ）
            var thumb = item.thumbnail_url;
            if (!thumb && videoData && videoData.platform === 'youtube') {
                thumb = 'https://img.youtube.com/vi/' + videoData.media_key + '/default.jpg';
            }
            if (thumb) {
                html += '<div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 shrink-0 relative">';
                html += '<img src="' + this.esc(thumb) + '" class="w-full h-full object-cover" loading="lazy">';
                if (item.type === 'video') html += '<div class="absolute inset-0 flex items-center justify-center"><i class="fa-solid fa-play text-white text-[9px] drop-shadow"></i></div>';
                html += '</div>';
            }

            html += '</div>'; // mt-1 flex
            html += '</div>'; // right content
            html += '</div>'; // flex
            html += '</' + wrapTag + '>';
            return html;
        },

        playVideo: function(index, ev) {
            var item = this.allItems[index];
            if (!item) return;
            var videoData = this.parseVideoExtra(item);
            if (!videoData) return;
            openVideoModalWithData({
                media_key: videoData.media_key,
                platform: videoData.platform,
                sub_key: videoData.sub_key || '',
                title: item.title || '',
                category: videoData.category || '',
                upload_date: videoData.upload_date || item.event_date || '',
                thumbnail_url: item.thumbnail_url || '',
                description: videoData.description || ''
            }, ev);
        },

        render: function() {
            var container = document.getElementById('timelineContainer');
            var expandBtn = document.getElementById('timelineExpand');
            if (!this.allItems.length) {
                container.innerHTML = '<p class="text-xs text-slate-400 text-center py-4">タイムラインデータがありません</p>';
                expandBtn.classList.add('hidden');
                return;
            }
            var count = this.expanded ? this.allItems.length : Math.min(this.visibleCount, this.allItems.length);
            var html = '<div class="space-y-0">';
            for (var i = 0; i < count; i++) {
                html += this.renderItem(this.allItems[i], i);
            }
            html += '</div>';
            container.innerHTML = html;

            if (this.allItems.length > this.visibleCount) {
                expandBtn.classList.remove('hidden');
                var btnSpan = expandBtn.querySelector('span');
                var btnIcon = expandBtn.querySelector('i');
                if (this.expanded) {
                    btnSpan.textContent = '折りたたむ';
                    btnIcon.className = 'fa-solid fa-chevron-up text-[8px]';
                } else {
                    btnSpan.textContent = 'もっと見る（残り' + (this.allItems.length - this.visibleCount) + '件）';
                    btnIcon.className = 'fa-solid fa-chevron-down text-[8px]';
                }
            } else {
                expandBtn.classList.add('hidden');
            }
        },

        expand: function() {
            this.expanded = !this.expanded;
            this.render();
        },

        load: function() {
            if (this.loading) return;
            this.loading = true;
            var self = this;
            fetch('/hinata/api/get_oshi_timeline.php?member_id=' + this.memberId + '&offset=0&limit=30')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.loading = false;
                    if (res.status === 'success' && res.data) {
                        self.allItems = res.data;
                    }
                    self.render();
                })
                .catch(function() {
                    self.loading = false;
                    document.getElementById('timelineContainer').innerHTML = '<p class="text-xs text-slate-400 text-center py-4">読み込みに失敗しました</p>';
                });
        }
    };

    document.addEventListener('DOMContentLoaded', function() { OshiTimeline.load(); });

    document.getElementById('mobileMenuBtn').onclick = function() {
        document.getElementById('sidebar').classList.add('mobile-open');
    };
    </script>
</body>
</html>
