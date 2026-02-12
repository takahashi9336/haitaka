<?php
/**
 * 共通サイドバー (日本語版)
 * 物理パス: haitaka/private/components/sidebar.php
 */
$uri = $_SERVER['REQUEST_URI'];
$isPortal = ($uri === '/' || strpos($uri, '/index.php') === 0);
$isTask   = strpos($uri, '/task_manager/') !== false;
$isHinata = strpos($uri, '/hinata/') !== false;

$user = $_SESSION['user'] ?? ['id_name' => 'ゲスト', 'role' => ''];
$initial = mb_substr($user['id_name'], 0, 1);

$activeClass = "bg-indigo-50 text-indigo-700 font-bold shadow-sm shadow-indigo-100/50";
$inactiveClass = "text-slate-500 hover:bg-slate-50 transition";
?>
<aside id="sidebar" class="sidebar bg-white border-r border-slate-200 flex flex-col shrink-0 z-50 transition-all duration-300">
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-2 overflow-hidden">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shrink-0 shadow-indigo-200 shadow-lg">
                <i class="fa-solid fa-layer-group text-sm"></i>
            </div>
            <span class="font-black text-lg text-slate-800 tracking-tighter logo-text truncate">MyPlatform</span>
        </div>
        <button id="sidebarToggle" class="hidden md:block text-slate-400 hover:text-indigo-600 p-1 transition-colors">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <button id="sidebarClose" class="md:hidden text-slate-400 p-2">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <a href="/index.php" class="nav-item flex items-center px-3 py-3 rounded-xl <?= $isPortal ? $activeClass : $inactiveClass ?>">
            <i class="fa-solid fa-house w-6 text-center"></i>
            <span class="nav-text ml-2 text-sm">ダッシュボード</span>
        </a>
        <a href="/task_manager/" class="nav-item flex items-center px-3 py-3 rounded-xl <?= $isTask ? $activeClass : $inactiveClass ?>">
            <i class="fa-solid fa-check-to-slot w-6 text-center"></i>
            <span class="nav-text ml-2 text-sm">タスク管理</span>
        </a>
        <a href="/note/" class="nav-item flex items-center px-3 py-3 rounded-xl <?= strpos($uri, '/note/') !== false ? $activeClass : $inactiveClass ?>">
            <i class="fa-solid fa-lightbulb w-6 text-center"></i>
            <span class="nav-text ml-2 text-sm">メモ</span>
        </a>
        <div class="space-y-1">
            <a href="/hinata/" class="nav-item flex items-center px-3 py-3 rounded-xl <?= $isHinata ? 'bg-sky-50 text-sky-600 font-bold' : $inactiveClass ?>">
                <i class="fa-solid fa-star w-6 text-center <?= $isHinata ? 'text-sky-500' : 'text-sky-400' ?>"></i>
                <span class="nav-text ml-2 text-sm">日向坂ポータル</span>
            </a>
            <?php if ($isHinata): ?>
            <div class="pl-8 space-y-1 nav-text">
                <a href="/hinata/members.php" class="block py-1.5 text-[11px] font-bold <?= strpos($uri, 'members.php') ? 'text-sky-500' : 'text-slate-400' ?> hover:text-sky-500 transition">メンバー帳</a>
                <a href="/hinata/events.php" class="block py-1.5 text-[11px] font-bold <?= strpos($uri, 'events.php') ? 'text-sky-500' : 'text-slate-400' ?> hover:text-sky-500 transition">イベント</a>
                <a href="/hinata/talk.php" class="block py-1.5 text-[11px] font-bold <?= strpos($uri, 'talk.php') ? 'text-sky-500' : 'text-slate-400' ?> hover:text-sky-500 transition">ミーグリネタ帳</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="p-4 border-t border-slate-100 bg-slate-50/50 shrink-0">
        <div class="flex items-center gap-3 px-1 overflow-hidden">
            <div class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold shrink-0 shadow-sm">
                <?= htmlspecialchars($initial) ?>
            </div>
            <div class="user-info text-sm truncate">
                <p class="font-bold text-slate-900 leading-none"><?= htmlspecialchars($user['id_name']) ?></p>
                <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-wider"><?= $user['role'] === 'admin' ? '管理者' : '一般ユーザー' ?></p>
            </div>
            <a href="/users_settings/" class="ml-auto text-slate-400 hover:text-indigo-600 p-2"><i class="fa-solid fa-gear"></i></a>
        </div>
    </div>
</aside>