<?php
/**
 * ポータルサイト ダッシュボード (日本語版)
 * 物理パス: haitaka/www/index.php
 */
require_once __DIR__ . '/../private/vendor/autoload.php';

use Core\Auth;
use App\TaskManager\Model\TaskModel;
use App\Hinata\Model\NetaModel;
use App\Hinata\Model\EventModel;

$auth = new Auth();
if (!$auth->check()) { header('Location: /login.php'); exit; }
$user = $_SESSION['user'];

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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - MyPlatform</title>
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
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
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
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider">ログインユーザー</span>
                    <span class="text-xs font-black <?= $cardDeco ?> <?= !$isThemeHex ? "text-{$themeTailwind}-600" : '' ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>><?= htmlspecialchars($user['id_name']) ?></span>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-5xl mx-auto">

                <!-- クイックメモ (Google Keep風) - メモ app テーマ -->
                <div class="mb-6">
                    <div class="bg-white rounded-xl border <?= $noteTheme['cardBorder'] ?> shadow-sm overflow-hidden">
                        <div class="p-4">
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
                            <div id="quickMemoActions" class="mt-3 flex items-center justify-between opacity-0 transition-opacity duration-200">
                                <div class="flex gap-2">
                                    <button onclick="QuickMemo.clearInput()" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50 rounded-lg transition">
                                        <i class="fa-solid fa-times mr-1"></i> キャンセル
                                    </button>
                                </div>
                                <button id="quickMemoSaveBtn" onclick="QuickMemo.save(event)" class="quick-memo-save-btn px-4 py-1.5 <?= !$noteTheme['isThemeHex'] ? $noteTheme['btnBgClass'] : '' ?> text-white text-xs font-bold rounded-lg transition shadow-sm">
                                    <i class="fa-solid fa-plus mr-1"></i> 保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0): ?>
                <div class="mb-6">
                    <a href="/hinata/events.php?event_id=<?= $nextEvent['id'] ?>" class="flex items-center gap-3 bg-white rounded-xl border <?= $hinataTheme['cardBorder'] ?> shadow-sm px-4 py-3 hover:shadow-md transition-all cursor-pointer <?= $hinataTheme['isThemeHex'] ? 'hover:border-[var(--hinata-box-theme)]' : 'hover:border-' . $hinataTheme['themeTailwind'] . '-200' ?>">
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
                                    $days = (int)$nextEvent['days_left'];
                                    $dateText = isset($nextEvent['event_date']) ? date('Y/m/d', strtotime($nextEvent['event_date'])) : '';
                                    if ($days === 0) {
                                        echo '本日開催';
                                    } elseif ($days === 1) {
                                        echo 'あと 1 日';
                                    } else {
                                        echo 'あと ' . $days . ' 日';
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
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($topTask)): ?>
                <?php
                    // 期限が当日以降かチェック
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
                    
                    $cardClass = $isUrgent ? 'bg-gradient-to-r from-red-50 to-orange-50 border-red-200' : 'bg-white ' . $taskTheme['cardBorder'];
                    $iconBg = $isUrgent ? 'bg-red-500' : $taskTheme['headerIconBg'];
                    $iconShadow = $isUrgent ? 'shadow-red-200' : $taskTheme['headerShadow'];
                    $labelColor = $isUrgent ? 'text-red-600' : ($taskTheme['cardIconText'] ?: 'text-indigo-600');
                    $iconStyle = $isUrgent ? '' : $taskTheme['headerIconStyle'];
                ?>
                <div class="mb-6">
                    <a href="/task_manager/?task_id=<?= $topTask['id'] ?>" class="flex items-center gap-3 <?= $cardClass ?> rounded-xl border shadow-sm px-4 py-3 <?= $isUrgent ? 'ring-2 ring-red-200 animate-pulse' : '' ?> hover:shadow-md transition-all cursor-pointer">
                        <div class="w-8 h-8 rounded-lg <?= $iconBg ?> text-white flex items-center justify-center shadow-md <?= $iconShadow ?>"<?= $iconStyle ? ' style="' . htmlspecialchars($iconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-<?= $isUrgent ? 'triangle-exclamation' : 'exclamation' ?> text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[9px] font-bold <?= $labelColor ?> tracking-wider mb-1"<?= !$isUrgent && $taskTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($taskTheme['cardDecoStyle']) . '"' : '' ?>>最優先タスク<?= $isUrgent ? ' 🔥' : '' ?></p>
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
                                <?php if (!empty($topTask['due_date'])): ?>
                                    <span class="<?= $isUrgent ? 'text-red-600 font-black' : '' ?>">
                                        <?= $urgentText ?: date('m/d', strtotime($topTask['due_date'])) . ' 期限' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border <?= $isUrgent ? 'border-red-200 text-red-600' : ($taskTheme['cardBorder'] . ' ' . $taskTheme['cardIconText']) ?>"<?= !$isUrgent && $taskTheme['isThemeHex'] ? ' style="border-color: ' . htmlspecialchars($taskTheme['themePrimary']) . '; color: ' . htmlspecialchars($taskTheme['themePrimary']) . '"' : '' ?>>
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- タスク管理 - タスク管理 app テーマ -->
                    <a href="/task_manager/" class="bg-white p-5 rounded-xl border <?= $taskTheme['cardBorder'] ?> shadow-sm flex flex-col justify-between h-44 hover:translate-y-[-2px] transition-all">
                        <div>
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 <?= $taskTheme['cardIconBg'] ?> <?= $taskTheme['cardIconText'] ?>"<?= $taskTheme['cardIconStyle'] ? ' style="' . htmlspecialchars($taskTheme['cardIconStyle']) . '"' : '' ?>>
                                <i class="fa-solid fa-list-check text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">タスク管理</h3>
                            <p class="text-slate-400 text-xs font-medium mt-1">現在の未完了タスク数</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-3xl font-black text-slate-800"><?= $activeTasksCount ?></span>
                            <span class="text-xs font-bold tracking-wider <?= $taskTheme['cardIconText'] ?>"<?= $taskTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($taskTheme['cardDecoStyle']) . '"' : '' ?>>開く <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>

                    <!-- 日向坂ポータル - 日向坂ポータル app テーマ -->
                    <a href="/hinata/" class="bg-white p-5 rounded-xl border <?= $hinataTheme['cardBorder'] ?> shadow-sm flex flex-col justify-between h-44 hover:translate-y-[-2px] transition-all">
                        <div>
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 <?= $hinataTheme['cardIconBg'] ?> <?= $hinataTheme['cardIconText'] ?>"<?= $hinataTheme['cardIconStyle'] ? ' style="' . htmlspecialchars($hinataTheme['cardIconStyle']) . '"' : '' ?>>
                                <i class="fa-solid fa-star text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">日向坂ポータル</h3>
                            <p class="text-slate-400 text-xs font-medium mt-1">保存済みのネタ数</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-3xl font-black text-slate-800"><?= $netaCount ?></span>
                            <span class="text-xs font-bold tracking-wider <?= $hinataTheme['cardIconText'] ?>"<?= $hinataTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($hinataTheme['cardDecoStyle']) . '"' : '' ?>>移動する <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>

                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <!-- 管理画面 - 管理画面 app テーマ（管理者のみ） -->
                    <a href="/admin/" class="bg-white p-5 rounded-xl border <?= $adminTheme['cardBorder'] ?> shadow-sm flex flex-col justify-between h-44 hover:translate-y-[-2px] transition-all">
                        <div>
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 <?= $adminTheme['cardIconBg'] ?> <?= $adminTheme['cardIconText'] ?>"<?= $adminTheme['cardIconStyle'] ? ' style="' . htmlspecialchars($adminTheme['cardIconStyle']) . '"' : '' ?>>
                                <i class="fa-solid fa-shield-halved text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">管理画面</h3>
                            <p class="text-slate-400 text-xs font-medium mt-1">ユーザー・アプリ・ロール管理</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-3xl font-black text-slate-800"><i class="fa-solid fa-gear text-2xl text-slate-400"></i></span>
                            <span class="text-xs font-bold tracking-wider <?= $adminTheme['cardIconText'] ?>"<?= $adminTheme['cardDecoStyle'] ? ' style="' . htmlspecialchars($adminTheme['cardDecoStyle']) . '"' : '' ?>>開く <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="/assets/js/core.js"></script>
    <script>
        function autoResizeTextarea(ta) {
            ta.style.height = 'auto';
            ta.style.height = Math.max(40, Math.min(ta.scrollHeight, 400)) + 'px';
        }

        const QuickMemo = {
            input: null,
            titleInput: null,
            actions: null,

            init() {
                this.input = document.getElementById('quickMemoInput');
                this.titleInput = document.getElementById('quickMemoTitle');
                this.actions = document.getElementById('quickMemoActions');
                
                if (this.input) {
                    this.input.addEventListener('focus', () => {
                        this.actions.classList.remove('opacity-0');
                        this.actions.classList.add('opacity-100');
                    });
                    this.input.addEventListener('input', () => autoResizeTextarea(this.input));
                    this.input.addEventListener('focus', () => autoResizeTextarea(this.input));
                }
            },

            async save(event) {
                const title = this.titleInput ? this.titleInput.value.trim() : '';
                const content = this.input.value.trim();
                
                if (!content) {
                    alert('メモの内容を入力してください');
                    return;
                }

                const btn = event ? event.target : document.getElementById('quickMemoSaveBtn');

                try {
                    const result = await App.post('/note/api/save.php', {
                        title: title,
                        content: content
                    });

                    console.log('API Response:', result); // デバッグ用

                    if (result && result.status === 'success') {
                        // 成功時は入力欄をクリア
                        this.clearInput();
                        
                        // 簡易フィードバック（テーマの保存ボタンは .quick-memo-save-btn、成功時は .saved で緑表示）
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
                        alert('エラー: ' + errorMsg + '\n\n詳細はコンソールを確認してください');
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('保存中にエラーが発生しました\n\n詳細: ' + error.message + '\n\nコンソールを確認してください');
                }
            },

            clearInput() {
                this.input.value = '';
                if (this.titleInput) this.titleInput.value = '';
                this.input.style.height = 'auto';
                this.input.blur();
                this.actions.classList.remove('opacity-100');
                this.actions.classList.add('opacity-0');
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            QuickMemo.init();
        });
    </script>
</body>
</html>