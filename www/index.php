<?php
/**
 * ポータルサイト ダッシュボード (日本語版)
 * 物理パス: haitaka/www/index.php
 */
require_once __DIR__ . '/../private/vendor/autoload.php';

use Core\Auth;
use App\TaskManager\Model\TaskModel;
use App\Hinata\Model\NetaModel;

$auth = new Auth();
if (!$auth->check()) { header('Location: /login.php'); exit; }
$user = $_SESSION['user'];

$taskModel = new TaskModel();
$activeTasksCount = count($taskModel->getActiveTasks());

$netaModel = new NetaModel();
$netaCount = 0;
$groupedNeta = $netaModel->getGroupedNeta();
foreach($groupedNeta as $group) { $netaCount += count($group['items']); }
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
                <header class="mb-10">
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">ダッシュボード</h1>
                    <p class="text-slate-400 font-medium mt-1">おかえりなさい、<?= htmlspecialchars($user['id_name']) ?> さん</p>
                </header>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- タスク管理 -->
                    <a href="/task_manager/" class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col justify-between h-56 hover:translate-y-[-4px] transition-all">
                        <div>
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4"><i class="fa-solid fa-list-check text-xl"></i></div>
                            <h3 class="font-bold text-slate-800 text-lg">タスク管理</h3>
                            <p class="text-slate-400 text-xs font-medium">現在の未完了タスク数</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-4xl font-black text-slate-800"><?= $activeTasksCount ?></span>
                            <span class="text-indigo-600 text-xs font-bold uppercase tracking-widest">開く <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>

                    <!-- 日向坂ポータル -->
                    <a href="/hinata/" class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col justify-between h-56 hover:translate-y-[-4px] transition-all">
                        <div>
                            <div class="w-12 h-12 bg-sky-50 text-sky-500 rounded-2xl flex items-center justify-center mb-4"><i class="fa-solid fa-star text-xl"></i></div>
                            <h3 class="font-bold text-slate-800 text-lg">日向坂ポータル</h3>
                            <p class="text-slate-400 text-xs font-medium">保存済みのネタ数</p>
                        </div>
                        <div class="flex items-end justify-between">
                            <span class="text-4xl font-black text-slate-800"><?= $netaCount ?></span>
                            <span class="text-sky-500 text-xs font-bold uppercase tracking-widest">移動する <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>
    <script src="/assets/js/core.js"></script>
</body>
</html>