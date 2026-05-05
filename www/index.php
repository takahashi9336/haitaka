<?php
/**
 * ポータルサイト ダッシュボード (日本語版)
 * 物理パス: haitaka/www/index.php
 */
require_once __DIR__ . '/../private/bootstrap.php';

use Core\Auth;
use Core\Database;
use App\TaskManager\Model\TaskModel;
use App\Hinata\Model\NetaModel;
use App\Hinata\Model\EventModel;
use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\FavoriteModel;

$auth = new Auth();
if (!$auth->check()) { header('Location: /login.php'); exit; }
$user = $_SESSION['user'];

// restricted ロールでダッシュボードが許可されていない場合は default_route へリダイレクト
if (($user['sidebar_mode'] ?? '') === 'restricted') {
    $hasDashboard = false;
    foreach ($user['apps'] ?? [] as $app) {
        if (($app['app_key'] ?? '') === 'dashboard') {
            $hasDashboard = true;
            break;
        }
    }
    if (!$hasDashboard) {
        header('Location: ' . ($user['default_route'] ?? '/hinata/'));
        exit;
    }
}

$taskModel = new TaskModel();
$activeTasks = $taskModel->getActiveTasks();
$activeTasksCount = count($activeTasks);

// 優先度（高い順）×期限（早い順）で並べ替え（ダッシュボード一覧・件数の共通順）
if (!empty($activeTasks)) {
    usort($activeTasks, function($a, $b) {
        if ($a['priority'] !== $b['priority']) return $b['priority'] - $a['priority'];
        if (!isset($a['due_date']) && !isset($b['due_date'])) return 0;
        if (!isset($a['due_date'])) return 1;
        if (!isset($b['due_date'])) return -1;
        return strcmp($a['due_date'], $b['due_date']);
    });
}
$dashboardTaskList = array_slice($activeTasks, 0, 5);

$netaModel = new NetaModel();
$netaCount = 0;
$groupedNeta = $netaModel->getGroupedNeta();
foreach($groupedNeta as $group) { $netaCount += count($group['items']); }

// 日向坂ポータルと同じ「次のイベント」情報
$eventModel = new EventModel();
$nextEvent = $eventModel->getNextEvent();

$appKey = 'dashboard';
require_once __DIR__ . '/../private/components/theme_from_session.php';

// ダッシュボード内の各ボックスはアプリ別テーマを使用
$noteTheme = getThemeVarsForApp('note');         // クイックメモ
$hinataTheme = getThemeVarsForApp('hinata');     // 日向坂 次のイベント、日向坂ポータル遷移ボックス
$taskTheme = getThemeVarsForApp('task_manager'); // 最優先タスク、タスク管理遷移ボックス
$adminTheme = getThemeVarsForApp('admin');       // 管理画面への遷移ボックス（管理者のみ）
$focusNoteTheme = getThemeVarsForApp('focus_note'); // Focus Note
$dashboardNavTheme = getThemeVarsForApp('dashboard'); // 記事トレ一覧カード（ダッシュボードアプリのテーマ）
$animeTheme = getThemeVarsForApp('anime');       // アニメ（未登録時は indigo フォールバック）
// アニメリンク表示: .env の ANIME_BETA_ID_NAMES に id_name が含まれるユーザーのみ（カンマ区切り）
$showAnimeLink = false;
$allowedAnimeIds = isset($_ENV['ANIME_BETA_ID_NAMES']) ? array_map('trim', explode(',', $_ENV['ANIME_BETA_ID_NAMES'])) : [];
if (!empty($allowedAnimeIds) && in_array($user['id_name'] ?? '', $allowedAnimeIds, true)) {
    $showAnimeLink = true;
}

$focusNoteActionCount = 0;
try {
    $weekStart = \App\FocusNote\Model\WeeklyPageModel::getWeekStart(date('Y-m-d'));
    $wpModel = new \App\FocusNote\Model\WeeklyPageModel();
    $wp = $wpModel->findByWeekStart($weekStart);
    if ($wp) {
        $qaModel = new \App\FocusNote\Model\QuestionActionModel();
        $actions = $qaModel->getActionsByWeeklyPageId((int) $wp['id']);
        $focusNoteActionCount = count(array_filter($actions, fn($a) => empty($a['done'])));
    }
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('Focus Note action count error', $e);
}

$articleTrainingCount = 0;
try {
    $pdoCount = Database::connect();
    $stmtAt = $pdoCount->prepare('SELECT COUNT(*) FROM dashboard_article_training WHERE user_id = ?');
    $stmtAt->execute([(int) ($user['id'] ?? 0)]);
    $articleTrainingCount = (int) $stmtAt->fetchColumn();
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('Article training count error', $e);
}

// 次のイベントに紐づく遠征・ミーグリ予定フォーカス用（ダッシュボードのボタン導線）
$dashboardNextEventTripId = null;
$dashboardNextEventMgFocusSlotId = null;
$dashboardNextEventMgFocusDate = null;
$dashboardNextEventChecklist = ['total' => 0, 'checked' => 0];
if (!empty($nextEvent['id'])) {
    $uidDash = (int) ($user['id'] ?? 0);
    $eidDash = (int) $nextEvent['id'];
    try {
        $dashboardNextEventTripId = (new \App\LiveTrip\Model\TripPlanModel())->findLatestTripIdForHinataEvent($uidDash, $eidDash);
    } catch (\Throwable $e) {
        \Core\Logger::errorWithContext('Dashboard next event trip lookup', $e);
    }
    $nextCat = (int) ($nextEvent['category'] ?? 0);
    if (in_array($nextCat, [2, 3], true)) {
        try {
            $mgDash = new MeetGreetModel();
            $dashMgSlots = $mgDash->getSlotsByEventId($eidDash);
            if (empty($dashMgSlots) && !empty($nextEvent['event_date'])) {
                $dashMgSlots = $mgDash->getSlotsByDate((string) $nextEvent['event_date']);
            }
            if (!empty($dashMgSlots[0]['id'])) {
                $dashboardNextEventMgFocusSlotId = (int) $dashMgSlots[0]['id'];
            }
            if (!empty($nextEvent['event_date'])) {
                $dashboardNextEventMgFocusDate = (string) $nextEvent['event_date'];
            }
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Dashboard next event MG focus lookup', $e);
        }
    }
    if ($dashboardNextEventTripId) {
        try {
            $clDash = new \App\LiveTrip\Model\ChecklistItemModel();
            foreach ($clDash->getByTripPlanId((int) $dashboardNextEventTripId) as $_clRow) {
                $dashboardNextEventChecklist['total']++;
                if (!empty($_clRow['checked'])) {
                    $dashboardNextEventChecklist['checked']++;
                }
            }
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Dashboard next event checklist counts', $e);
        }
    }
}

// 推しメンバーの誕生日（30日以内）
$oshiBirthdays = [];
try {
    $favModel = new FavoriteModel();
    $oshiMembers = $favModel->getOshiMembers();
    if (!empty($oshiMembers)) {
        $oshiMemberIds = array_column($oshiMembers, 'member_id');
        $ph = implode(',', array_fill(0, count($oshiMemberIds), '?'));
        $pdo = Database::connect();
        $sql = "SELECT m.id, m.name, m.birth_date, m.image_url,
                       f.level,
                       c1.color_code as color1, c2.color_code as color2,
                       CASE
                           WHEN DATE_FORMAT(m.birth_date, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')
                           THEN DATEDIFF(
                               CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(m.birth_date, '%m-%d')),
                               CURDATE()
                           )
                           ELSE DATEDIFF(
                               CONCAT(YEAR(CURDATE()) + 1, '-', DATE_FORMAT(m.birth_date, '%m-%d')),
                               CURDATE()
                           )
                       END AS days_until
                FROM hn_members m
                JOIN hn_favorites f ON f.member_id = m.id AND f.user_id = ?
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE m.id IN ($ph)
                  AND m.birth_date IS NOT NULL
                HAVING days_until <= 30
                ORDER BY days_until ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$_SESSION['user']['id']], $oshiMemberIds));
        $oshiBirthdays = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('Oshi birthday fetch error', $e);
}

// ダッシュボード記事ウィジェット（好奇心ブースト・AI関連・パレオな男）
$dashboardCuriosityItem = null;
$dashboardAiItem = null;
$dashboardPaleoItems = [];
try {
    $feedService = new \App\Dashboard\Service\DashboardFeedService();
    $dashboardCuriosityItem = $feedService->getCuriosityItem((int) ($user['id'] ?? 0));
    $dashboardAiItem = $feedService->getAiItem();
    $dashboardPaleoItems = $feedService->getPaleoItems();
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('Dashboard feed fetch error', $e);
}

$dashboardFeedRows = [];
if ($dashboardCuriosityItem !== null) {
    $dashboardFeedRows[] = ['kind' => 'curiosity', 'item' => $dashboardCuriosityItem];
}
if ($dashboardAiItem !== null) {
    $dashboardFeedRows[] = ['kind' => 'ai', 'item' => $dashboardAiItem];
}
foreach ($dashboardPaleoItems as $_paleoRow) {
    $dashboardFeedRows[] = ['kind' => 'paleo', 'item' => $_paleoRow];
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - MyPlatform</title>
    <?php require_once __DIR__ . '/../private/components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --dashboard-theme: <?= htmlspecialchars($themePrimaryHex) ?>;
            --note-theme: <?= htmlspecialchars($noteTheme['themePrimaryHex']) ?>;
            --hinata-box-theme: <?= htmlspecialchars($hinataTheme['themePrimaryHex']) ?>;
            --task-box-theme: <?= htmlspecialchars($taskTheme['themePrimaryHex']) ?>;
            --admin-box-theme: <?= htmlspecialchars($adminTheme['themePrimaryHex']) ?>;
        }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-home text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ダッシュボード</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden md:block text-[10px] text-slate-400 font-medium"><?= htmlspecialchars($user['id_name']) ?></span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-5xl mx-auto">

                <?php
                    $dow = ['日','月','火','水','木','金','土'];
                    $w = (int)date('w');
                    $dowColor = ($w === 0) ? 'text-red-400' : (($w === 6) ? 'text-blue-400' : 'text-slate-400');
                ?>
                <div class="mb-8">
                    <p class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">
                        <?= date('n') ?><span class="text-base md:text-lg font-bold text-slate-400 mx-0.5">月</span><?= date('j') ?><span class="text-base md:text-lg font-bold text-slate-400 mx-0.5">日</span><span class="text-base md:text-lg font-bold <?= $dowColor ?> ml-1"><?= $dow[$w] ?>曜日</span>
                    </p>
                    <p class="text-xs text-slate-400 mt-1"><?php if ($activeTasksCount > 0): ?>未完了タスク <span class="font-bold text-slate-500"><?= $activeTasksCount ?></span> 件<?php else: ?>タスクはすべて完了しています<?php endif; ?></p>
                </div>

                <?php
                    function dashboard_article_training_url(array $item): string {
                        $url = $item['url'] ?? '';
                        $title = $item['title'] ?? '';
                        $params = ['url' => $url];
                        if ($title !== '') {
                            $params['title'] = $title;
                        }
                        return '/dashboard/article_training.php?' . http_build_query($params);
                    }

                    /** RSS の pubDate 等を Asia/Tokyo で n/j(曜) に整形 */
                    function dashboard_format_feed_pub_date(string $raw): string {
                        $raw = trim($raw);
                        if ($raw === '') {
                            return '';
                        }
                        $tz = new \DateTimeZone('Asia/Tokyo');
                        try {
                            $dt = (new \DateTimeImmutable($raw))->setTimezone($tz);
                        } catch (\Throwable $e) {
                            $ts = strtotime($raw);
                            if ($ts === false) {
                                return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
                            }
                            $dt = (new \DateTimeImmutable('@' . $ts))->setTimezone($tz);
                        }
                        $dowJa = ['日', '月', '火', '水', '木', '金', '土'][(int) $dt->format('w')];
                        return $dt->format('n/j') . '(' . $dowJa . ')';
                    }

                    /** @param 'curiosity'|'ai'|'paleo' $kind */
                    function dashboard_feed_badge_label(string $kind): string {
                        return match ($kind) {
                            'curiosity' => '好',
                            'ai' => 'AI',
                            'paleo' => 'パ',
                            default => '・',
                        };
                    }
                ?>

                <?php if (!empty($dashboardFeedRows)): ?>
                <div class="mb-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
                        <div class="mb-3 pb-2 border-b border-slate-100 flex items-start justify-between gap-3">
                            <p class="text-sm font-black text-slate-800 tracking-tight shrink-0 flex items-center gap-1.5">
                                <span class="text-[1.05rem] leading-none select-none shrink-0" aria-hidden="true">📰</span>
                                <span>気になる記事</span>
                            </p>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider text-right leading-snug max-w-[70%]">今日の好奇心ブースト・AI関連・パレオな男</p>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            <?php foreach ($dashboardFeedRows as $row):
                                $kind = $row['kind'];
                                $it = $row['item'];
                                $badge = dashboard_feed_badge_label($kind);
                                $dateLine = !empty($it['pubDate']) ? dashboard_format_feed_pub_date((string) $it['pubDate']) : '';
                                $badgeTone = match ($kind) {
                                    'curiosity' => 'border-amber-200 bg-amber-50 text-amber-800',
                                    'ai' => 'border-violet-200 bg-violet-50 text-violet-800',
                                    'paleo' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                    default => 'border-slate-200 bg-slate-50 text-slate-600',
                                };
                            ?>
                            <li class="flex gap-3 py-3 first:pt-0">
                                <div class="shrink-0 w-9 h-9 min-w-[2.25rem] flex items-center justify-center rounded border text-[11px] font-black leading-none select-none <?= $badgeTone ?>" title="<?= $kind === 'curiosity' ? '好奇心ブースト' : ($kind === 'ai' ? 'AI関連' : 'パレオな男') ?>">
                                    <?= htmlspecialchars($badge) ?>
                                </div>
                                <div class="flex-1 min-w-0 flex flex-col gap-1.5">
                                    <a href="<?= htmlspecialchars($it['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-sm font-bold text-slate-800 hover:text-slate-600 hover:underline decoration-slate-400 underline-offset-2">
                                        <?= htmlspecialchars($it['title']) ?>
                                    </a>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <?php if ($dateLine !== ''): ?>
                                        <p class="text-xs text-slate-400"><?= $dateLine ?></p>
                                        <?php endif; ?>
                                        <a href="<?= htmlspecialchars(dashboard_article_training_url($it)) ?>" class="shrink-0 ml-auto inline-flex items-center gap-0.5 px-2.5 py-1 rounded border border-slate-300 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-50 active:scale-[0.98] transition shadow-sm">
                                            🧠トレーニング
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:gap-4">
                    <div class="min-w-0 flex flex-col gap-4 md:flex-1">
                        <div class="flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                            <p class="text-sm font-black text-slate-800 tracking-tight shrink-0 mb-4 flex items-center gap-1.5">
                                <span class="text-[1.05rem] leading-none select-none shrink-0" aria-hidden="true">🌅</span>
                                <span>次の日向坂イベント</span>
                            </p>
                            <?php if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0): ?>
                            <?php
                                $eventDays = (int) $nextEvent['days_left'];
                                $evTs = strtotime((string) ($nextEvent['event_date'] ?? ''));
                                $dateTextShort = $evTs ? date('Y/m/d', $evTs) : '';
                                $calMonthEn = $evTs ? strtoupper(date('M', $evTs)) : '';
                                $calDayNum = $evTs ? (int) date('j', $evTs) : 0;
                                $nextEvPlace = trim((string) ($nextEvent['event_place'] ?? ''));
                                $nextEvCat = (int) ($nextEvent['category'] ?? 0);
                                $showMgPlanBtn = in_array($nextEvCat, [2, 3], true);
                                $mgPlanHref = '/hinata/meetgreet.php';
                                if ($showMgPlanBtn) {
                                    if ($dashboardNextEventMgFocusSlotId) {
                                        $mgPlanHref = '/hinata/meetgreet.php?focus_slot_id=' . $dashboardNextEventMgFocusSlotId;
                                    } elseif ($dashboardNextEventMgFocusDate !== null && $dashboardNextEventMgFocusDate !== '') {
                                        $mgPlanHref = '/hinata/meetgreet.php?focus_event_date=' . rawurlencode($dashboardNextEventMgFocusDate);
                                    }
                                }
                                $clTotal = (int) ($dashboardNextEventChecklist['total'] ?? 0);
                                $clDone = (int) ($dashboardNextEventChecklist['checked'] ?? 0);
                                $clPct = $clTotal > 0 ? (int) round(100 * $clDone / $clTotal) : 0;
                            ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-100/80 p-4 flex flex-col shadow-inner">
                                <div class="flex gap-4 items-start">
                                    <?php if ($evTs): ?>
                                    <div class="w-[3.75rem] shrink-0 flex flex-col items-center justify-center rounded-lg border border-slate-200 bg-white py-2.5 px-1.5 shadow-sm">
                                        <span class="text-[9px] font-bold text-sky-600 uppercase tracking-wider leading-none"><?= htmlspecialchars($calMonthEn) ?></span>
                                        <span class="text-[1.35rem] font-black text-slate-900 tabular-nums leading-none mt-1"><?= $calDayNum ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0 flex flex-col pt-0.5">
                                        <p class="text-sm font-bold text-slate-900 leading-snug line-clamp-3">
                                            <?= htmlspecialchars($nextEvent['event_name'] ?? '次のイベント') ?>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                            <?php
                                                if ($eventDays === 0) {
                                                    echo '本日開催';
                                                } elseif ($eventDays === 1) {
                                                    echo 'あと 1 日';
                                                } else {
                                                    echo 'あと ' . $eventDays . ' 日';
                                                }
                                                if ($dateTextShort !== '') {
                                                    echo ' (' . $dateTextShort . ')';
                                                }
                                                if ($nextEvPlace !== '') {
                                                    echo ' ／ ' . htmlspecialchars($nextEvPlace);
                                                }
                                            ?>
                                        </p>
                                        <?php if ($clTotal > 0): ?>
                                        <div class="mt-2">
                                            <div class="h-2 rounded-full bg-slate-200/90 overflow-hidden">
                                                <div class="h-full rounded-full bg-sky-500 transition-all" style="width: <?= max(0, min(100, $clPct)) ?>%"></div>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-2 font-medium">準備チェック <?= $clDone ?>/<?= $clTotal ?> 完了</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-4 shrink-0">
                                <a href="/hinata/events.php?event_id=<?= (int) $nextEvent['id'] ?>" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                    🗓️イベント詳細へ
                                </a>
                                <?php if ($dashboardNextEventTripId): ?>
                                <a href="/live_trip/show.php?id=<?= (int) $dashboardNextEventTripId ?>" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                    ✈️遠征管理へ
                                </a>
                                <?php endif; ?>
                                <?php if ($showMgPlanBtn): ?>
                                <a href="<?= htmlspecialchars($mgPlanHref) ?>" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                    ミーグリ予定
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="flex min-h-[6rem] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-5 py-6 text-center text-sm text-slate-400 font-medium">
                                近日のイベントはありません
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="bg-white rounded-xl border <?= $noteTheme['cardBorder'] ?> shadow-sm p-5 flex flex-col">
                            <div class="flex items-start justify-between gap-2 shrink-0 mb-4">
                                <p class="text-sm font-black text-slate-800 tracking-tight flex items-center gap-1.5 min-w-0">
                                    <span class="text-[1.05rem] leading-none select-none shrink-0" aria-hidden="true">🗂️</span>
                                    <span class="truncate">各リストへ</span>
                                </p>
                                <a href="/note/" class="shrink-0 text-[10px] font-medium text-slate-400 hover:text-slate-600 whitespace-nowrap pt-0.5 transition">
                                    メモを開く <i class="fa-solid fa-arrow-right ml-0.5 text-[9px]" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm" href="/note/?tab=list&kind=todo">やること</a>
                                <a class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm" href="/note/?tab=list&kind=question">疑問・仮説</a>
                                <a class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm" href="/note/?tab=list&kind=first_time">はじめて</a>
                                <a class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm" href="/note/?tab=list&kind=fun">おもろかったこと</a>
                                <a class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm" href="/note/?tab=list&kind=book">書籍メモ</a>
                                <a class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm" href="/note/?tab=list&kind=generic_list">汎用リスト</a>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0 md:flex-1 md:self-start flex flex-col">
                        <div class="flex flex-col bg-white rounded-xl border <?= $taskTheme['cardBorder'] ?> shadow-sm p-5">
                            <div class="flex items-start justify-between gap-2 shrink-0 mb-4">
                                <p class="text-sm font-black text-slate-800 tracking-tight flex items-center gap-1.5 min-w-0">
                                    <span class="text-[1.05rem] leading-none select-none shrink-0" aria-hidden="true">📋</span>
                                    <span class="truncate">タスク一覧</span>
                                </p>
                                <?php if (!empty($dashboardTaskList)): ?>
                                <span class="text-[10px] font-medium text-slate-400 whitespace-nowrap pt-0.5">並べ替え：優先度×期限</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($dashboardTaskList)): ?>
                            <ul class="divide-y divide-slate-100 -mx-1">
                                <?php foreach ($dashboardTaskList as $t):
                                    $pri = (int) ($t['priority'] ?? 2);
                                    $priLabel = match ($pri) {
                                        3 => '高',
                                        2 => '中',
                                        1 => '低',
                                        default => '中',
                                    };
                                    $priBadgeClass = match ($pri) {
                                        3 => 'border-red-200 bg-red-50 text-red-700',
                                        2 => 'border-amber-200 bg-amber-50 text-amber-800',
                                        default => 'border-sky-200 bg-sky-50 text-sky-800',
                                    };
                                ?>
                                <li>
                                    <a href="/task_manager/?task_id=<?= (int) $t['id'] ?>" class="flex items-center gap-3 py-3 px-1 rounded-lg hover:bg-slate-50/80 transition-colors active:scale-[0.99]">
                                        <span class="w-4 h-4 shrink-0 rounded border border-slate-300 bg-white" aria-hidden="true"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-800 leading-snug line-clamp-2"><?= htmlspecialchars($t['title'] ?? '') ?></p>
                                            <?php if (!empty($t['category_name'])): ?>
                                            <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($t['category_name']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($t['due_date'])): ?>
                                            <p class="text-[10px] text-slate-400 mt-0.5"><?= date('n/j', strtotime($t['due_date'])) ?> 期限</p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-md border <?= $priBadgeClass ?>"><?= htmlspecialchars($priLabel) ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="/task_manager/" class="mt-3 text-center text-[11px] font-bold text-slate-500 hover:text-slate-800 transition shrink-0">
                                タスク管理へ
                            </a>
                            <?php else: ?>
                            <div class="flex min-h-[6rem] items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/60 px-4 py-5 text-center text-sm text-slate-400 font-medium">
                                未完了タスクはありません
                            </div>
                            <a href="/task_manager/" class="mt-3 text-center text-[11px] font-bold text-slate-500 hover:text-slate-800 transition shrink-0">
                                タスク管理へ
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    </div>
                </div>

                <?php if (!empty($oshiBirthdays)): ?>
                <div class="mb-4">
                    <div class="bg-white rounded-xl border <?= $hinataTheme['cardBorder'] ?> shadow-sm px-5 py-5">
                        <p class="text-[9px] font-bold tracking-wider mb-2 <?= $hinataTheme['cardDeco'] ?> <?= !$hinataTheme['isThemeHex'] ? "text-{$hinataTheme['themeTailwind']}-500" : '' ?>"<?= $hinataTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($hinataTheme['cardDecoStyle']) . '"' : '' ?>><i class="fa-solid fa-cake-candles mr-1"></i>推しの誕生日</p>
                        <div class="flex flex-col gap-2">
                            <?php foreach ($oshiBirthdays as $bday):
                                $bdayDays = (int)$bday['days_until'];
                                $bdayDate = date('n/j', strtotime(date('Y') . '-' . date('m-d', strtotime($bday['birth_date']))));
                                $levelLabel = FavoriteModel::LEVEL_LABELS[(int)$bday['level']] ?? '';
                            ?>
                            <a href="/hinata/member.php?id=<?= $bday['id'] ?>" class="flex items-center gap-3 hover:bg-slate-50 rounded-lg py-1 px-1 -mx-1 transition-colors">
                                <?php if (!empty($bday['color1'])): ?>
                                <span class="w-1 h-8 rounded-full shrink-0" style="background: linear-gradient(to bottom, <?= htmlspecialchars($bday['color1']) ?>, <?= htmlspecialchars($bday['color2'] ?? $bday['color1']) ?>)"></span>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($bday['name']) ?> <span class="text-[10px] font-medium text-slate-400 ml-1"><?= $levelLabel ?></span></p>
                                    <p class="text-xs text-slate-500"><?= $bdayDate ?>
                                        <?php if ($bdayDays === 0): ?>
                                            — <span class="font-black text-pink-500">今日が誕生日！</span>
                                        <?php elseif ($bdayDays === 1): ?>
                                            — <span class="font-bold text-pink-500">明日</span>
                                        <?php else: ?>
                                            — あと <?= $bdayDays ?> 日
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                    $navCards = [
                        ['href' => '/task_manager/', 'icon' => 'fa-list-check', 'label' => 'タスク管理', 'sub' => '未完了タスク', 'theme' => $taskTheme,
                         'value' => $activeTasksCount, 'empty_icon' => 'fa-circle-check', 'empty_text' => null],
                        ['href' => '/hinata/', 'icon' => 'fa-star', 'label' => '日向坂', 'sub' => '保存済みネタ', 'theme' => $hinataTheme,
                         'value' => $netaCount, 'empty_icon' => null, 'empty_text' => 'ネタなし'],
                        ['href' => '/focus_note/', 'icon' => 'fa-bolt', 'label' => '集中ノート', 'sub' => '未完了アクション', 'theme' => $focusNoteTheme,
                         'value' => $focusNoteActionCount, 'empty_icon' => 'fa-circle-check', 'empty_text' => null],
                        ['href' => '/dashboard/article_training_history.php', 'icon' => 'fa-clock-rotate-left', 'label' => '記事トレ一覧', 'sub' => '保存した記事', 'theme' => $dashboardNavTheme,
                         'value' => $articleTrainingCount, 'empty_icon' => null, 'empty_text' => 'まだなし'],
                    ];
                    if ($showAnimeLink) {
                        $navCards[] = ['href' => '/anime/', 'icon' => 'fa-tv', 'label' => 'アニメ', 'sub' => 'Annict', 'theme' => $animeTheme,
                                       'value' => -1, 'empty_icon' => null, 'empty_text' => null];
                    }
                    if (($user['role'] ?? '') === 'admin') {
                        $navCards[] = ['href' => '/dashboard/youtube_focus.php', 'icon' => 'fa-brands fa-youtube', 'label' => 'YouTube 集中', 'sub' => '指定チャンネルのみ', 'theme' => $adminTheme,
                                       'value' => -1, 'empty_icon' => null, 'empty_text' => null];
                        $navCards[] = ['href' => '/admin/text_files.php', 'icon' => 'fa-file-lines', 'label' => 'テキスト管理', 'sub' => 'txt / Markdown', 'theme' => $adminTheme,
                                       'value' => -1, 'empty_icon' => null, 'empty_text' => null];
                        $navCards[] = ['href' => '/admin/', 'icon' => 'fa-shield-halved', 'label' => '管理画面', 'sub' => '管理', 'theme' => $adminTheme,
                                       'value' => -1, 'empty_icon' => null, 'empty_text' => null];
                    }
                    $navCardIconClass = static function (array $nc): string {
                        $ic = $nc['icon'] ?? 'fa-link';
                        if (str_starts_with($ic, 'fa-brands ')) {
                            return $ic;
                        }
                        return 'fa-solid ' . $ic;
                    };
                ?>

                <!-- モバイル: 2列・縦型（読みやすいタイポ・十分なタップ領域） -->
                <div class="grid grid-cols-2 gap-3 mb-6 pb-20 md:hidden">
                    <?php foreach ($navCards as $nc):
                        $isYoutubeFocusCard = str_contains($nc['href'] ?? '', 'youtube_focus');
                    ?>
                    <a href="<?= $nc['href'] ?>" class="bg-white rounded-xl border <?= $nc['theme']['cardBorder'] ?> shadow-sm p-3.5 flex flex-col gap-2.5 active:scale-[0.98] transition-all min-w-0 <?= $isYoutubeFocusCard ? 'min-h-0' : 'min-h-[7.25rem]' ?>">
                        <div class="flex items-start gap-2.5 min-h-0">
                            <?php if ($isYoutubeFocusCard): ?>
                            <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center border border-red-100 bg-red-50" aria-hidden="true">
                                <i class="fa-brands fa-youtube text-sm text-red-600 leading-none"></i>
                            </div>
                            <?php else: ?>
                            <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center <?= $nc['theme']['cardIconBg'] ?> <?= $nc['theme']['cardIconText'] ?>"<?= $nc['theme']['cardIconStyle'] ? ' style="' . htmlspecialchars($nc['theme']['cardIconStyle']) . '"' : '' ?>>
                                <i class="<?= htmlspecialchars($navCardIconClass($nc)) ?> text-sm leading-none"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <span class="block text-sm font-bold text-slate-800 leading-snug"><?= htmlspecialchars($nc['label']) ?></span>
                                <span class="block text-[11px] font-medium text-slate-400 mt-1 leading-snug"><?= htmlspecialchars($nc['sub']) ?></span>
                            </div>
                        </div>
                        <?php if (!$isYoutubeFocusCard): ?>
                        <div class="mt-auto flex items-center min-h-[1.75rem] border-t border-slate-100 pt-2">
                            <?php if ($nc['value'] === 0 && $nc['empty_icon']): ?>
                                <span class="text-lg text-emerald-500"><i class="fa-solid <?= $nc['empty_icon'] ?>"></i></span>
                            <?php elseif ($nc['value'] === 0 && $nc['empty_text']): ?>
                                <span class="text-xs text-slate-400 leading-snug"><?= htmlspecialchars($nc['empty_text']) ?></span>
                            <?php elseif ($nc['value'] > 0): ?>
                                <span class="text-xl font-black text-slate-800 tabular-nums count-up leading-none" data-target="<?= $nc['value'] ?>">0</span>
                            <?php elseif ($nc['value'] === -1): ?>
                                <i class="fa-solid fa-gear text-base text-slate-400"></i>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- PC: コンパクトカード（列を増やして1枚あたりの横幅を抑制） -->
                <div class="hidden md:grid md:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
                    <?php foreach ($navCards as $nc):
                        $isYoutubeFocusCard = str_contains($nc['href'] ?? '', 'youtube_focus');
                    ?>
                    <a href="<?= $nc['href'] ?>" class="bg-white p-3.5 rounded-lg border <?= $nc['theme']['cardBorder'] ?> shadow-sm flex flex-col gap-2 hover:translate-y-[-1px] active:scale-[0.98] transition-all min-w-0">
                        <div class="flex items-start gap-2">
                            <?php if ($isYoutubeFocusCard): ?>
                            <div class="w-6 h-6 shrink-0 rounded-md flex items-center justify-center border border-red-100 bg-red-50" aria-hidden="true">
                                <i class="fa-brands fa-youtube text-xs text-red-600 leading-none"></i>
                            </div>
                            <?php else: ?>
                            <div class="w-6 h-6 rounded-md flex shrink-0 items-center justify-center <?= $nc['theme']['cardIconBg'] ?> <?= $nc['theme']['cardIconText'] ?>"<?= $nc['theme']['cardIconStyle'] ? ' style="' . htmlspecialchars($nc['theme']['cardIconStyle']) . '"' : '' ?>>
                                <i class="<?= htmlspecialchars($navCardIconClass($nc)) ?> text-xs leading-none"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <h3 class="font-bold text-slate-800 text-sm leading-snug"><?= htmlspecialchars($nc['label']) ?></h3>
                                <p class="text-slate-400 text-[11px] font-medium mt-0.5 leading-snug"><?= htmlspecialchars($nc['sub']) ?></p>
                            </div>
                        </div>
                        <?php if (!$isYoutubeFocusCard): ?>
                        <div class="flex items-center min-h-[1.5rem] border-t border-slate-100 pt-1.5">
                            <?php if ($nc['value'] === 0 && $nc['empty_icon']): ?>
                                <span class="text-lg text-emerald-500"><i class="fa-solid <?= $nc['empty_icon'] ?>"></i></span>
                            <?php elseif ($nc['value'] === 0 && $nc['empty_text']): ?>
                                <span class="text-xs text-slate-400"><?= htmlspecialchars($nc['empty_text']) ?></span>
                            <?php elseif ($nc['value'] > 0): ?>
                                <span class="text-xl font-black text-slate-800 count-up" data-target="<?= $nc['value'] ?>">0</span>
                            <?php elseif ($nc['value'] === -1): ?>
                                <i class="fa-solid fa-gear text-base text-slate-400"></i>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </main>
    <script src="/assets/js/core.js?v=2"></script>
    <script>
        function animateCountUp() {
            document.querySelectorAll('.count-up').forEach(el => {
                const target = parseInt(el.dataset.target, 10);
                if (isNaN(target) || target === 0) { el.textContent = '0'; return; }
                const duration = 600;
                const start = performance.now();
                function step(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const ease = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.round(ease * target);
                    if (progress < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            animateCountUp();
        });
    </script>
</body>
</html>