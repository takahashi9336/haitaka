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
$activeTasksCount = count($taskModel->getActiveTasks());

$netaModel = new NetaModel();
$netaCount = 0;
$groupedNeta = $netaModel->getGroupedNeta();
foreach($groupedNeta as $group) { $netaCount += count($group['items']); }

// 日向坂ポータルと同じ「次のイベント」情報
$eventModel = new EventModel();
$nextEvent = $eventModel->getNextEvent();
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
<body class="bg-[#f8fafc] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white border-b border-slate-100 flex items-center justify-between px-6 shrink-0 md:hidden">
            <button id="mobileMenuBtn" class="text-slate-400 p-2"><i class="fa-solid fa-bars text-xl"></i></button>
            <h1 class="font-black text-slate-800 tracking-tighter">MyPlatform</h1>
            <div class="w-8"></div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-5xl mx-auto">
                <header class="mb-6">
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">ダッシュボード</h1>
                    <p class="text-slate-400 font-medium mt-1">おかえりなさい、<?= htmlspecialchars($user['id_name']) ?> さん</p>
                </header>

                <!-- クイックメモ (Google Keep風) -->
                <div class="mb-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <i class="fa-solid fa-lightbulb text-sm"></i>
                                </div>
                                <h2 class="text-sm font-bold text-slate-800">クイックメモ</h2>
                            </div>
                            <textarea 
                                id="quickMemoInput" 
                                placeholder="メモを入力... (タイトルは自動生成されます)"
                                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none transition-all text-sm"
                                rows="3"
                            ></textarea>
                            <div id="quickMemoActions" class="mt-3 flex items-center justify-between opacity-0 transition-opacity duration-200">
                                <div class="flex gap-2">
                                    <button onclick="QuickMemo.clearInput()" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50 rounded-lg transition">
                                        <i class="fa-solid fa-times mr-1"></i> キャンセル
                                    </button>
                                </div>
                                <button id="quickMemoSaveBtn" onclick="QuickMemo.save(event)" class="px-4 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-lg hover:bg-amber-600 transition shadow-sm">
                                    <i class="fa-solid fa-plus mr-1"></i> 保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0): ?>
                <div class="mb-6">
                    <div class="flex items-center gap-3 bg-white rounded-xl border border-sky-100 shadow-sm px-4 py-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-500 text-white flex items-center justify-center shadow-md">
                            <i class="fa-solid fa-calendar-day text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[9px] font-bold text-sky-500 uppercase tracking-[0.2em] mb-1">Hinata Next Event</p>
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
                        <a href="/hinata/events.php" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border border-sky-100 text-sky-500 hover:bg-sky-50 transition">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- タスク管理 -->
                    <a href="/task_manager/" class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between h-44 hover:translate-y-[-2px] transition-all">
                        <div>
                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center mb-3">
                                <i class="fa-solid fa-list-check text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">タスク管理</h3>
                            <p class="text-slate-400 text-xs font-medium mt-1">現在の未完了タスク数</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-3xl font-black text-slate-800"><?= $activeTasksCount ?></span>
                            <span class="text-indigo-600 text-xs font-bold uppercase tracking-widest">開く <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>

                    <!-- 日向坂ポータル -->
                    <a href="/hinata/" class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between h-44 hover:translate-y-[-2px] transition-all">
                        <div>
                            <div class="w-10 h-10 bg-sky-50 text-sky-500 rounded-lg flex items-center justify-center mb-3">
                                <i class="fa-solid fa-star text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">日向坂ポータル</h3>
                            <p class="text-slate-400 text-xs font-medium mt-1">保存済みのネタ数</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-3xl font-black text-slate-800"><?= $netaCount ?></span>
                            <span class="text-sky-500 text-xs font-bold uppercase tracking-widest">移動する <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>
    <script src="/assets/js/core.js"></script>
    <script>
        const QuickMemo = {
            input: null,
            actions: null,

            init() {
                this.input = document.getElementById('quickMemoInput');
                this.actions = document.getElementById('quickMemoActions');
                
                if (this.input) {
                    // フォーカス時にアクションボタンを表示
                    this.input.addEventListener('focus', () => {
                        this.actions.classList.remove('opacity-0');
                        this.actions.classList.add('opacity-100');
                    });
                }
            },

            async save(event) {
                const content = this.input.value.trim();
                
                if (!content) {
                    alert('メモの内容を入力してください');
                    return;
                }

                // ボタン要素を取得
                const btn = event ? event.target : document.getElementById('quickMemoSaveBtn');

                try {
                    const result = await App.post('/note/api/save.php', {
                        content: content
                    });

                    console.log('API Response:', result); // デバッグ用

                    if (result && result.status === 'success') {
                        // 成功時は入力欄をクリア
                        this.clearInput();
                        
                        // 簡易フィードバック
                        if (btn) {
                            const originalText = btn.innerHTML;
                            btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 保存しました';
                            btn.classList.add('bg-green-500');
                            btn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                            
                            setTimeout(() => {
                                btn.innerHTML = originalText;
                                btn.classList.remove('bg-green-500');
                                btn.classList.add('bg-amber-500', 'hover:bg-amber-600');
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