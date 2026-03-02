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

// 優先度×期日順でソートして最優先タスクを取得
$topTask = null;
if (!empty($activeTasks)) {
    usort($activeTasks, function($a, $b) {
        if ($a['priority'] !== $b['priority']) return $b['priority'] - $a['priority'];
        if (!isset($a['due_date']) && !isset($b['due_date'])) return 0;
        if (!isset($a['due_date'])) return 1;
        if (!isset($b['due_date'])) return -1;
        return strcmp($a['due_date'], $b['due_date']);
    });
    $topTask = $activeTasks[0];
}

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

// 翌日のイベントにミーグリ予定がある場合、スロット情報を取得
$tomorrowMeetGreetSlots = [];
try {
    if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] <= 1) {
        $mgModel = new MeetGreetModel();
        $tomorrowMeetGreetSlots = $mgModel->getSlotsByDate($nextEvent['event_date']);
    }
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('MeetGreet slots fetch error', $e);
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
        <?php if ($noteTheme['isThemeHex']): ?>
        .quick-memo-save-btn { background-color: var(--note-theme); }
        .quick-memo-save-btn:hover { filter: brightness(1.08); }
        .quick-memo-save-btn.saved { background-color: #22c55e !important; }
        <?php endif; ?>
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

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
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

                <?php if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0): ?>
                <?php $eventDays = (int)$nextEvent['days_left']; ?>
                <div class="mb-4">
                    <a href="/hinata/events.php?event_id=<?= $nextEvent['id'] ?>" class="block bg-white rounded-xl border <?= $hinataTheme['cardBorder'] ?> shadow-sm hover:shadow-md transition-all cursor-pointer active:scale-[0.99] <?= $hinataTheme['isThemeHex'] ? 'hover:border-[var(--hinata-box-theme)]' : 'hover:border-' . $hinataTheme['themeTailwind'] . '-200' ?> overflow-hidden">
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="w-8 h-8 rounded-lg text-white flex items-center justify-center shadow-md <?= $hinataTheme['headerIconBg'] ?> <?= $hinataTheme['headerShadow'] ?>"<?= $hinataTheme['headerIconStyle'] ? ' style="' . htmlspecialchars($hinataTheme['headerIconStyle']) . '"' : '' ?>>
                                <i class="fa-solid fa-calendar-day text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-[9px] font-bold tracking-wider mb-1 <?= $hinataTheme['cardDeco'] ?> <?= !$hinataTheme['isThemeHex'] ? "text-{$hinataTheme['themeTailwind']}-500" : '' ?>"<?= $hinataTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($hinataTheme['cardDecoStyle']) . '"' : '' ?>>日向坂 次のイベント</p>
                                <p class="text-sm font-bold text-slate-800 mb-0.5">
                                    <?= htmlspecialchars($nextEvent['event_name'] ?? '次のイベント') ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?php
                                        $dateText = isset($nextEvent['event_date']) ? date('Y/m/d', strtotime($nextEvent['event_date'])) : '';
                                        if ($eventDays === 0) {
                                            echo '本日開催';
                                        } elseif ($eventDays === 1) {
                                            echo 'あと 1 日';
                                        } else {
                                            echo 'あと ' . $eventDays . ' 日';
                                        }
                                        if ($dateText) {
                                            echo '（' . $dateText . '）';
                                        }
                                    ?>
                                </p>
                            </div>
                            <div class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border <?= $hinataTheme['cardBorder'] ?> <?= $hinataTheme['cardIconText'] ?>"<?= $hinataTheme['isThemeHex'] ? ' style="border-color: ' . htmlspecialchars($hinataTheme['themePrimary']) . '; color: ' . htmlspecialchars($hinataTheme['themePrimary']) . '"' : '' ?>>
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </div>
                        </div>
                        <?php if (!empty($tomorrowMeetGreetSlots) && $eventDays <= 1): ?>
                        <div class="border-t <?= $hinataTheme['cardBorder'] ?> px-4 py-2.5 bg-slate-50/50">
                            <p class="text-[9px] font-bold text-slate-400 tracking-wider mb-1.5"><i class="fa-solid fa-handshake mr-1"></i>ミーグリ予定</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($tomorrowMeetGreetSlots as $slot): ?>
                                <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-white border border-slate-200 text-xs">
                                    <?php if (!empty($slot['color1'])): ?>
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: <?= htmlspecialchars($slot['color1']) ?>"></span>
                                    <?php endif; ?>
                                    <span class="font-bold text-slate-700"><?= htmlspecialchars($slot['member_name'] ?? $slot['member_name_raw'] ?? '未定') ?></span>
                                    <span class="text-slate-400"><?= htmlspecialchars($slot['slot_name']) ?></span>
                                    <?php if ((int)($slot['ticket_count'] ?? 0) > 0): ?>
                                    <span class="text-[10px] font-bold text-slate-500"><?= $slot['ticket_count'] ?>枚</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($oshiBirthdays)): ?>
                <div class="mb-4">
                    <div class="bg-white rounded-xl border <?= $hinataTheme['cardBorder'] ?> shadow-sm px-4 py-3">
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

                <?php if (!empty($topTask)): ?>
                <?php
                    $isUrgent = false;
                    $urgentText = '';
                    if (!empty($topTask['due_date'])) {
                        $dueDate = strtotime($topTask['due_date']);
                        $today = strtotime(date('Y-m-d'));
                        $daysLeft = floor(($dueDate - $today) / (60 * 60 * 24));
                        
                        if ($daysLeft < 0) {
                            $isUrgent = true;
                            $urgentText = abs($daysLeft) . '日超過';
                        } elseif ($daysLeft === 0) {
                            $isUrgent = true;
                            $urgentText = '本日期限';
                        } elseif ($daysLeft === 1) {
                            $isUrgent = true;
                            $urgentText = '明日期限';
                        }
                    }
                    
                    $cardClass = $isUrgent ? 'bg-gradient-to-r from-red-50 to-orange-50 border-red-200 border-l-4 border-l-red-500' : 'bg-white ' . $taskTheme['cardBorder'];
                    $iconBg = $isUrgent ? 'bg-red-500' : $taskTheme['headerIconBg'];
                    $iconShadow = $isUrgent ? 'shadow-red-200' : $taskTheme['headerShadow'];
                    $labelColor = $isUrgent ? 'text-red-600' : ($taskTheme['cardIconText'] ?: 'text-indigo-600');
                    $iconStyle = $isUrgent ? '' : $taskTheme['headerIconStyle'];
                ?>
                <div class="mb-6">
                    <a href="/task_manager/?task_id=<?= $topTask['id'] ?>" class="flex items-center gap-3 <?= $cardClass ?> rounded-xl border shadow-sm px-4 py-3 hover:shadow-md transition-all cursor-pointer active:scale-[0.99]">
                        <div class="w-8 h-8 rounded-lg <?= $iconBg ?> text-white flex items-center justify-center shadow-md <?= $iconShadow ?>"<?= $iconStyle ? ' style="' . htmlspecialchars($iconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-<?= $isUrgent ? 'triangle-exclamation' : 'exclamation' ?> text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[9px] font-bold <?= $labelColor ?> tracking-wider mb-1"<?= !$isUrgent && $taskTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($taskTheme['cardDecoStyle']) . '"' : '' ?>>最優先タスク<?= $isUrgent ? ' — ' . $urgentText : '' ?></p>
                            <p class="text-sm font-bold text-slate-800 mb-0.5">
                                <?= htmlspecialchars($topTask['title']) ?>
                            </p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="text-[10px] font-black <?= $topTask['priority'] == 3 ? 'text-red-500' : 'text-orange-400' ?>">
                                    <?= str_repeat('!', $topTask['priority']) ?>
                                </span>
                                <?php if (!empty($topTask['category_name'])): ?>
                                    <span class="px-1.5 py-0.5 bg-slate-100 rounded text-[10px] font-bold"><?= htmlspecialchars($topTask['category_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($topTask['due_date']) && !$isUrgent): ?>
                                    <span><?= date('m/d', strtotime($topTask['due_date'])) ?> 期限</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border <?= $isUrgent ? 'border-red-200 text-red-600' : ($taskTheme['cardBorder'] . ' ' . $taskTheme['cardIconText']) ?>"<?= !$isUrgent && $taskTheme['isThemeHex'] ? ' style="border-color: ' . htmlspecialchars($taskTheme['themePrimary']) . '; color: ' . htmlspecialchars($taskTheme['themePrimary']) . '"' : '' ?>>
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </div>
                    </a>
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
                    ];
                    if (($user['role'] ?? '') === 'admin') {
                        $navCards[] = ['href' => '/admin/', 'icon' => 'fa-shield-halved', 'label' => '管理画面', 'sub' => '管理', 'theme' => $adminTheme,
                                       'value' => -1, 'empty_icon' => null, 'empty_text' => null];
                    }
                ?>

                <!-- モバイル: アイコンチックなグリッド -->
                <div class="grid <?= count($navCards) <= 3 ? 'grid-cols-3' : 'grid-cols-4' ?> gap-3 mb-6 md:hidden">
                    <?php foreach ($navCards as $nc): ?>
                    <a href="<?= $nc['href'] ?>" class="bg-white rounded-xl border <?= $nc['theme']['cardBorder'] ?> shadow-sm p-3 flex flex-col items-center gap-1.5 active:scale-95 transition-all">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center <?= $nc['theme']['cardIconBg'] ?> <?= $nc['theme']['cardIconText'] ?>"<?= $nc['theme']['cardIconStyle'] ? ' style="' . htmlspecialchars($nc['theme']['cardIconStyle']) . '"' : '' ?>>
                            <i class="fa-solid <?= $nc['icon'] ?> text-lg"></i>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600 text-center leading-tight"><?= $nc['label'] ?></span>
                        <?php if ($nc['value'] === 0 && $nc['empty_icon']): ?>
                            <span class="text-emerald-500 text-sm"><i class="fa-solid <?= $nc['empty_icon'] ?>"></i></span>
                        <?php elseif ($nc['value'] > 0): ?>
                            <span class="text-sm font-black text-slate-700 count-up" data-target="<?= $nc['value'] ?>">0</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- PC: 従来のカード表示 -->
                <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <?php foreach ($navCards as $nc): ?>
                    <a href="<?= $nc['href'] ?>" class="bg-white p-5 rounded-xl border <?= $nc['theme']['cardBorder'] ?> shadow-sm flex flex-col justify-between h-44 hover:translate-y-[-2px] active:scale-[0.98] transition-all">
                        <div>
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 <?= $nc['theme']['cardIconBg'] ?> <?= $nc['theme']['cardIconText'] ?>"<?= $nc['theme']['cardIconStyle'] ? ' style="' . htmlspecialchars($nc['theme']['cardIconStyle']) . '"' : '' ?>>
                                <i class="fa-solid <?= $nc['icon'] ?> text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base"><?= $nc['label'] ?></h3>
                            <p class="text-slate-400 text-xs font-medium mt-1"><?= $nc['sub'] ?></p>
                        </div>
                        <div class="flex items-end justify-between">
                            <?php if ($nc['value'] === 0 && $nc['empty_icon']): ?>
                                <span class="text-2xl text-emerald-500"><i class="fa-solid <?= $nc['empty_icon'] ?>"></i></span>
                            <?php elseif ($nc['value'] === 0 && $nc['empty_text']): ?>
                                <span class="text-sm text-slate-400"><?= $nc['empty_text'] ?></span>
                            <?php elseif ($nc['value'] > 0): ?>
                                <span class="text-3xl font-black text-slate-800 count-up" data-target="<?= $nc['value'] ?>">0</span>
                            <?php elseif ($nc['value'] === -1): ?>
                                <span class="text-3xl font-black text-slate-800"><i class="fa-solid fa-gear text-2xl text-slate-400"></i></span>
                            <?php endif; ?>
                            <span class="text-xs font-bold tracking-wider <?= $nc['theme']['cardIconText'] ?>"<?= $nc['theme']['cardDecoStyle'] ? ' style="' . htmlspecialchars($nc['theme']['cardDecoStyle']) . '"' : '' ?>>開く <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div id="quickMemoSection">
                    <div class="bg-white rounded-xl border <?= $noteTheme['cardBorder'] ?> shadow-sm overflow-hidden transition-all duration-300">
                        <div class="p-4">
                            <div id="quickMemoCollapsed" class="flex items-center gap-2 cursor-text">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center <?= $noteTheme['cardIconBg'] ?> <?= $noteTheme['cardIconText'] ?>"<?= $noteTheme['cardIconStyle'] ? ' style="' . htmlspecialchars($noteTheme['cardIconStyle']) . '"' : '' ?>>
                                    <i class="fa-solid fa-lightbulb text-xs"></i>
                                </div>
                                <span class="text-sm text-slate-400">メモを入力...</span>
                            </div>
                            <div id="quickMemoExpanded" class="hidden">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center <?= $noteTheme['cardIconBg'] ?> <?= $noteTheme['cardIconText'] ?>"<?= $noteTheme['cardIconStyle'] ? ' style="' . htmlspecialchars($noteTheme['cardIconStyle']) . '"' : '' ?>>
                                        <i class="fa-solid fa-lightbulb text-sm"></i>
                                    </div>
                                    <h2 class="text-sm font-bold text-slate-800">クイックメモ</h2>
                                </div>
                                <input type="text" id="quickMemoTitle" placeholder="タイトル（任意）"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 <?= $noteTheme['isThemeHex'] ? 'focus:ring-[var(--note-theme)]' : 'focus:ring-' . $noteTheme['themeTailwind'] . '-500' ?> focus:border-transparent text-sm font-medium mb-2">
                                <textarea 
                                    id="quickMemoInput" 
                                    placeholder="メモを入力..."
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 <?= $noteTheme['isThemeHex'] ? 'focus:ring-[var(--note-theme)]' : 'focus:ring-' . $noteTheme['themeTailwind'] . '-500' ?> focus:border-transparent resize-none overflow-hidden transition-all text-sm min-h-[2.5rem]"
                                    rows="1"
                                ></textarea>
                                <div id="quickMemoActions" class="mt-3 flex items-center justify-between">
                                    <button onclick="QuickMemo.collapse()" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50 rounded-lg transition">
                                        <i class="fa-solid fa-times mr-1"></i> 閉じる
                                    </button>
                                    <button id="quickMemoSaveBtn" onclick="QuickMemo.save(event)" class="quick-memo-save-btn px-4 py-1.5 <?= !$noteTheme['isThemeHex'] ? $noteTheme['btnBgClass'] : '' ?> text-white text-xs font-bold rounded-lg transition shadow-sm">
                                        <i class="fa-solid fa-plus mr-1"></i> 保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
    <script src="/assets/js/core.js?v=2"></script>
    <script>
        function autoResizeTextarea(ta) {
            ta.style.height = 'auto';
            ta.style.height = Math.max(40, Math.min(ta.scrollHeight, 400)) + 'px';
        }

        const QuickMemo = {
            input: null,
            titleInput: null,
            collapsedEl: null,
            expandedEl: null,

            init() {
                this.input = document.getElementById('quickMemoInput');
                this.titleInput = document.getElementById('quickMemoTitle');
                this.collapsedEl = document.getElementById('quickMemoCollapsed');
                this.expandedEl = document.getElementById('quickMemoExpanded');

                if (this.collapsedEl) {
                    this.collapsedEl.addEventListener('click', () => this.expand());
                }
                if (this.input) {
                    this.input.addEventListener('input', () => autoResizeTextarea(this.input));
                }

                document.addEventListener('keydown', (e) => {
                    if (e.ctrlKey && e.shiftKey && e.key === 'N') {
                        e.preventDefault();
                        this.expand();
                    }
                });
            },

            expand() {
                if (!this.collapsedEl || !this.expandedEl) return;
                this.collapsedEl.classList.add('hidden');
                this.expandedEl.classList.remove('hidden');
                setTimeout(() => {
                    if (this.input) this.input.focus();
                }, 50);
            },

            collapse() {
                if (!this.collapsedEl || !this.expandedEl) return;
                this.clearInput();
                this.expandedEl.classList.add('hidden');
                this.collapsedEl.classList.remove('hidden');
            },

            async save(event) {
                const title = this.titleInput ? this.titleInput.value.trim() : '';
                const content = this.input.value.trim();

                if (!content) {
                    App.toast('メモの内容を入力してください');
                    return;
                }

                const btn = event ? event.target.closest('button') : document.getElementById('quickMemoSaveBtn');

                try {
                    const result = await App.post('/note/api/save.php', {
                        title: title,
                        content: content
                    });

                    if (result && result.status === 'success') {
                        this.clearInput();
                        App.toast('メモを保存しました');

                        if (btn) {
                            const originalText = btn.innerHTML;
                            btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 保存しました';
                            btn.classList.add('saved');
                            setTimeout(() => {
                                btn.innerHTML = originalText;
                                btn.classList.remove('saved');
                            }, 2000);
                        }
                    } else {
                        console.error('API Error Response:', result);
                        const errorMsg = result && result.message ? result.message : '保存に失敗しました';
                        App.toast('エラー: ' + errorMsg);
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    App.toast('保存中にエラーが発生しました');
                }
            },

            clearInput() {
                if (this.input) {
                    this.input.value = '';
                    this.input.style.height = 'auto';
                    this.input.blur();
                }
                if (this.titleInput) this.titleInput.value = '';
            }
        };

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
            QuickMemo.init();
            animateCountUp();
        });
    </script>
</body>
</html>