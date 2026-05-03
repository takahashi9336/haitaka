<?php
/**
 * YouTube 集中視聴（ダッシュボード専用・サイドバー非掲載）
 * 環境変数 DASHBOARD_YOUTUBE_FOCUS_CHANNELS（カンマ区切り channel|video / channel|short）
 * 再生: 日向坂メディア一覧と同じ共通 video_modal
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;
use App\Dashboard\Service\YouTubeFocusChannelService;

$auth = new Auth();
if (!$auth->check()) {
    header('Location: /login.php');
    exit;
}

Database::connect();

$feedService = new YouTubeFocusChannelService();
$forceRefresh = (string)($_GET['refresh'] ?? '') === '1';
$feed = $feedService->getFeed($forceRefresh);
$view = (string)($_GET['view'] ?? 'all'); // all(default) | grouped

$appKey = 'dashboard';
require_once __DIR__ . '/../../private/components/theme_from_session.php';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube 集中視聴 - ダッシュボード</title>
    <?php require_once __DIR__ . '/../../private/components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .yt-line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between gap-3 px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-brands fa-youtube text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">YouTube 集中視聴</h1>
                    <p class="text-xs text-slate-400 truncate">指定チャンネルの最新のみ</p>
                </div>
            </div>
            <a href="/index.php" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 text-white text-[11px] font-bold shadow-sm hover:shadow-md active:scale-[0.98] transition whitespace-nowrap">
                <i class="fa-solid fa-house"></i>
                <span class="hidden sm:inline">ダッシュボード</span>
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-6xl mx-auto space-y-10">

                <?php if (!$feed['configured']): ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-900">
                        <p class="font-bold">チャンネルが未設定です</p>
                        <p class="text-xs mt-1 text-amber-800/90">サーバーの環境変数 <code class="bg-amber-100/80 px-1 rounded">DASHBOARD_YOUTUBE_FOCUS_CHANNELS</code> に、カンマ区切りで <code class="bg-amber-100/80 px-1 rounded">チャンネルID|video</code> または <code class="bg-amber-100/80 px-1 rounded">@ハンドル|short</code> の形式で指定してください。<code class="bg-amber-100/80 px-1 rounded">|video</code> は省略すると通常動画モードになります。</p>
                    </div>
                <?php elseif (!$feed['api_configured']): ?>
                    <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-sm text-rose-900">
                        <p class="font-bold">YouTube API キーがありません</p>
                        <p class="text-xs mt-1">環境変数 <code class="bg-rose-100/80 px-1 rounded">YOUTUBE_API_KEY</code> を設定してください。</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($feed['cached'])): ?>
                        <p class="text-[10px] text-slate-400"><i class="fa-solid fa-database mr-1"></i>直近の取得結果を表示しています（約30分キャッシュ）</p>
                    <?php endif; ?>

                    <div class="flex items-center justify-between gap-3">
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            <a href="/dashboard/youtube_focus.php?view=all<?= $forceRefresh ? '&refresh=1' : '' ?>"
                               class="px-3 py-2 rounded-lg text-xs font-bold transition <?= $view !== 'grouped' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                                投稿日時順
                            </a>
                            <a href="/dashboard/youtube_focus.php?view=grouped<?= $forceRefresh ? '&refresh=1' : '' ?>"
                               class="px-3 py-2 rounded-lg text-xs font-bold transition <?= $view === 'grouped' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                                チャンネル別
                            </a>
                        </div>
                        <?php if ($forceRefresh): ?>
                            <span class="text-[10px] text-slate-400">再取得しました</span>
                        <?php endif; ?>
                    </div>

                    <?php
                        $all = [];
                        foreach ($feed['channels'] as $ch) {
                            foreach (($ch['videos'] ?? []) as $v) {
                                $all[] = [
                                    'channel_title' => (string)($ch['channel_title'] ?: $ch['input_spec']),
                                    'mode_label' => (string)($ch['mode_label'] ?? ''),
                                    'mode' => (string)($ch['mode'] ?? 'video'),
                                    'video' => $v,
                                ];
                            }
                        }
                        usort($all, static function (array $a, array $b): int {
                            $at = strtotime((string)($a['video']['published_at'] ?? '')) ?: 0;
                            $bt = strtotime((string)($b['video']['published_at'] ?? '')) ?: 0;
                            return $bt <=> $at;
                        });
                        $allShorts = array_values(array_filter($all, static function (array $r): bool {
                            return ($r['mode'] ?? '') === 'short';
                        }));
                        $allRegular = array_values(array_filter($all, static function (array $r): bool {
                            return ($r['mode'] ?? '') !== 'short';
                        }));
                    ?>

                    <?php if ($view !== 'grouped'): ?>
                        <?php if (empty($all)): ?>
                            <p class="text-sm text-slate-500">表示できる動画がありません。</p>
                        <?php else: ?>
                            <div class="space-y-10">
                                <?php if (!empty($allShorts)): ?>
                                    <section aria-label="Shorts">
                                        <div class="flex flex-row flex-nowrap gap-4 overflow-x-auto pb-2 -mx-1 px-1 scroll-smooth [scrollbar-width:thin]">
                                            <?php foreach ($allShorts as $row):
                                                $v = $row['video'];
                                                $videoForModal = [
                                                    'platform' => 'youtube',
                                                    'media_key' => $v['video_id'] ?? '',
                                                    'title' => $v['title'] ?? '',
                                                    'upload_date' => $v['published_at'] ?? '',
                                                    'category' => $row['channel_title'] . ($row['mode_label'] !== '' ? ' / ' . $row['mode_label'] : ''),
                                                    'description' => '',
                                                ];
                                                $dataVideo = htmlspecialchars(json_encode($videoForModal, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                                $thumb = htmlspecialchars($v['thumbnail_url'] ?? '', ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <article
                                                    class="yt-focus-card group/card shrink-0 w-[min(42vw,11rem)] cursor-pointer rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                                    tabindex="0"
                                                    role="button"
                                                    data-video="<?= $dataVideo ?>"
                                                    onclick="youtubeFocusOpenModal(this, event)"
                                                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();youtubeFocusOpenModal(this,event);}"
                                                    aria-label="再生"
                                                >
                                                    <div class="relative aspect-[9/16] rounded-xl overflow-hidden bg-slate-200 shadow-sm ring-1 ring-slate-200/80">
                                                        <img src="<?= $thumb ?>" alt="" class="w-full h-full object-cover group-hover/card:scale-[1.02] transition-transform duration-200" loading="lazy" width="180" height="320">
                                                        <span class="absolute inset-0 flex items-center justify-center bg-black/25 opacity-0 group-hover/card:opacity-100 transition-opacity pointer-events-none">
                                                            <i class="fa-solid fa-circle-play text-white text-3xl drop-shadow-lg"></i>
                                                        </span>
                                                    </div>
                                                    <div class="mt-2 max-w-[min(42vw,11rem)]">
                                                        <p class="text-[10px] text-slate-500 font-semibold truncate">
                                                            <?= htmlspecialchars($row['channel_title']) ?>
                                                        </p>
                                                        <h3 class="text-xs font-semibold text-slate-900 yt-line-clamp-2 leading-snug mt-0.5 group-hover/card:text-red-600 transition-colors">
                                                            <?= htmlspecialchars($v['title'] ?? '') ?>
                                                        </h3>
                                                        <?php if (!empty($v['published_at'])): ?>
                                                            <p class="text-[10px] text-slate-400 mt-0.5">
                                                                <?= htmlspecialchars(date('Y/m/d H:i', strtotime((string) $v['published_at']))) ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>

                                <?php if (!empty($allRegular)): ?>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                        <?php foreach ($allRegular as $row):
                                            $v = $row['video'];
                                            $videoForModal = [
                                                'platform' => 'youtube',
                                                'media_key' => $v['video_id'] ?? '',
                                                'title' => $v['title'] ?? '',
                                                'upload_date' => $v['published_at'] ?? '',
                                                'category' => $row['channel_title'] . ($row['mode_label'] !== '' ? ' / ' . $row['mode_label'] : ''),
                                                'description' => '',
                                            ];
                                            $dataVideo = htmlspecialchars(json_encode($videoForModal, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                            $thumb = htmlspecialchars($v['thumbnail_url'] ?? '', ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <article
                                                class="yt-focus-card group/card cursor-pointer rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                                tabindex="0"
                                                role="button"
                                                data-video="<?= $dataVideo ?>"
                                                onclick="youtubeFocusOpenModal(this, event)"
                                                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();youtubeFocusOpenModal(this,event);}"
                                                aria-label="再生"
                                            >
                                                <div class="relative aspect-video rounded-xl overflow-hidden bg-slate-200 shadow-sm ring-1 ring-slate-200/80">
                                                    <img src="<?= $thumb ?>" alt="" class="w-full h-full object-cover group-hover/card:scale-[1.02] transition-transform duration-200" loading="lazy" width="320" height="180">
                                                    <span class="absolute inset-0 flex items-center justify-center bg-black/25 opacity-0 group-hover/card:opacity-100 transition-opacity pointer-events-none">
                                                        <i class="fa-solid fa-circle-play text-white text-4xl drop-shadow-lg"></i>
                                                    </span>
                                                </div>
                                                <div class="mt-2.5">
                                                    <p class="text-[11px] text-slate-500 font-semibold truncate">
                                                        <?= htmlspecialchars($row['channel_title']) ?>
                                                        <?php if ($row['mode_label'] !== ''): ?>
                                                            <span class="text-slate-400"> / <?= htmlspecialchars($row['mode_label']) ?></span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <h3 class="text-sm font-semibold text-slate-900 yt-line-clamp-2 leading-snug mt-1 group-hover/card:text-red-600 transition-colors">
                                                        <?= htmlspecialchars($v['title'] ?? '') ?>
                                                    </h3>
                                                    <?php if (!empty($v['published_at'])): ?>
                                                        <p class="text-[11px] text-slate-400 mt-1">
                                                            <?= htmlspecialchars(date('Y/m/d H:i', strtotime((string) $v['published_at']))) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($feed['channels'] as $ch): ?>
                        <section class="border-b border-slate-200/80 pb-10 last:border-0 last:pb-0">
                            <div class="flex flex-wrap items-baseline gap-2 gap-y-1 mb-4">
                                <h2 class="text-lg font-bold text-slate-900 tracking-tight">
                                    <?= htmlspecialchars($ch['channel_title'] ?: $ch['input_spec']) ?>
                                </h2>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full <?= $ch['mode'] === 'short' ? 'bg-pink-100 text-pink-700' : 'bg-slate-200 text-slate-600' ?>">
                                    <?= htmlspecialchars($ch['mode_label']) ?>
                                </span>
                            </div>

                            <?php if (!empty($ch['error']) && empty($ch['videos'])): ?>
                                <p class="text-sm text-amber-800"><?= htmlspecialchars($ch['error']) ?></p>
                            <?php elseif (!empty($ch['error'])): ?>
                                <p class="text-xs text-amber-700 mb-3"><?= htmlspecialchars($ch['error']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($ch['videos'])): ?>
                                <div class="flex flex-row flex-nowrap gap-4 overflow-x-auto pb-2 -mx-1 px-1 scroll-smooth [scrollbar-width:thin] md:grid md:grid-cols-3 md:overflow-visible md:mx-0 md:px-0 md:gap-5">
                                    <?php foreach ($ch['videos'] as $v):
                                        $videoForModal = [
                                            'platform' => 'youtube',
                                            'media_key' => $v['video_id'] ?? '',
                                            'title' => $v['title'] ?? '',
                                            'upload_date' => $v['published_at'] ?? '',
                                            'category' => $ch['mode_label'] ?? '',
                                            'description' => '',
                                        ];
                                        $dataVideo = htmlspecialchars(json_encode($videoForModal, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        $thumb = htmlspecialchars($v['thumbnail_url'] ?? '', ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <article
                                            class="yt-focus-card group/card shrink-0 w-[min(100%,17.5rem)] md:w-auto md:min-w-0 md:shrink cursor-pointer rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                            tabindex="0"
                                            role="button"
                                            data-video="<?= $dataVideo ?>"
                                            onclick="youtubeFocusOpenModal(this, event)"
                                            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();youtubeFocusOpenModal(this,event);}"
                                            aria-label="再生"
                                        >
                                            <div class="relative aspect-video rounded-xl overflow-hidden bg-slate-200 shadow-sm ring-1 ring-slate-200/80">
                                                <img src="<?= $thumb ?>" alt="" class="w-full h-full object-cover group-hover/card:scale-[1.02] transition-transform duration-200" loading="lazy" width="320" height="180">
                                                <span class="absolute inset-0 flex items-center justify-center bg-black/25 opacity-0 group-hover/card:opacity-100 transition-opacity pointer-events-none">
                                                    <i class="fa-solid fa-circle-play text-white text-4xl drop-shadow-lg"></i>
                                                </span>
                                            </div>
                                            <h3 class="text-sm font-semibold text-slate-900 yt-line-clamp-2 leading-snug mt-2.5 group-hover/card:text-red-600 transition-colors">
                                                <?= htmlspecialchars($v['title'] ?? '') ?>
                                            </h3>
                                            <?php if (!empty($v['published_at'])): ?>
                                                <p class="text-[11px] text-slate-400 mt-1">
                                                    <?= htmlspecialchars(date('Y/m/d H:i', strtotime((string) $v['published_at']))) ?>
                                                </p>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../../private/components/video_modal.php'; ?>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
    function youtubeFocusOpenModal(el, ev) {
        if (typeof openVideoModalWithData !== 'function') return;
        var raw = el.getAttribute('data-video');
        if (!raw) return;
        try {
            openVideoModalWithData(JSON.parse(raw), ev);
        } catch (e) {}
    }
    </script>
</body>
</html>
