<?php
/**
 * タスク管理 View（テーマ色はセッションの task_manager アプリから取得）
 */
$appKey = 'task_manager';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タスク管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
    <style>
        :root { --task-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .tab-btn.active { color: var(--task-theme); border-bottom-color: var(--task-theme); }
        .task-quick-add-btn { background-color: var(--task-theme); }
        .task-quick-add-btn:hover { filter: brightness(1.08); }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; font-size: 13px; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            #quickAddContainer { max-height: 0; overflow: hidden; padding: 0; margin-bottom: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
            #quickAddContainer.expanded { max-height: 600px; padding: 1rem; margin-bottom: 1.5rem; }
        }
        #quickAddToggle { display: none; }
        @media (max-width: 768px) { #quickAddToggle { display: flex; } }
        .row-done { opacity: 0.4; background-color: #f8fafc; }
        .row-done .title-text { text-decoration: line-through; }
        .tab-btn { font-size: 12px; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; padding-bottom: 8px; transition: all 0.2s; cursor: pointer; }
        .tab-btn.active { font-weight: 800; }

        /* Modal slide panel */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.2); backdrop-filter: blur(2px); z-index: 50; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
        .modal-overlay.open { opacity: 1; pointer-events: auto; }
        .modal-panel { position: fixed; right: 0; top: 0; height: 100%; width: 100%; max-width: 480px; background: white; box-shadow: -8px 0 30px rgba(0,0,0,0.1); z-index: 51; transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow-y: auto; }
        .modal-panel.open { transform: translateX(0); }

        /* Board columns */
        .board-col { min-height: 120px; }
        .board-card { transition: box-shadow 0.15s, transform 0.15s; }
        .board-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
        .sortable-ghost { opacity: 0.4; }
        .sortable-chosen { box-shadow: 0 8px 25px rgba(0,0,0,0.15); }

        /* Due date badges */
        .badge-overdue { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-today { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
        .badge-soon { background: #fefce8; color: #ca8a04; border: 1px solid #fef08a; }

        /* Stats progress */
        .stats-segment { transition: width 0.5s ease; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-list-check text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">タスク管理</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-end">
                    <span id="currentDate" class="text-[10px] font-bold text-slate-400 tracking-wider"></span>
                </div>
            </div>
        </header>

        <div class="bg-white border-b border-slate-200 px-6 flex gap-6 md:gap-8 shrink-0 h-12 items-end shadow-sm overflow-x-auto">
            <button onclick="switchMode('list')" id="tab-list" class="tab-btn active whitespace-nowrap">リスト</button>
            <button onclick="switchMode('board')" id="tab-board" class="tab-btn whitespace-nowrap">ボード</button>
            <button onclick="switchMode('gantt')" id="tab-gantt" class="tab-btn whitespace-nowrap">ガントチャート</button>
            <button onclick="switchMode('calendar')" id="tab-calendar" class="tab-btn whitespace-nowrap">カレンダー</button>
        </div>

        <div id="mainScroll" class="flex-1 overflow-y-auto p-3 md:p-6 custom-scroll relative">

            <!-- Mobile quick add toggle -->
            <button id="quickAddToggle" onclick="toggleQuickAdd()" class="w-full text-white px-4 py-3 rounded-xl font-black text-sm mb-4 shadow-lg transition-all flex items-center justify-center gap-2 task-quick-add-btn">
                <i class="fa-solid fa-plus"></i>
                <span id="toggleText">タスクを追加</span>
                <i id="toggleIcon" class="fa-solid fa-chevron-down ml-auto text-xs"></i>
            </button>

            <!-- Quick add form -->
            <section id="quickAddContainer" class="bg-white border border-slate-200 rounded-lg p-3 md:p-4 mb-6 shadow-sm">
                <form id="taskForm" class="flex flex-col md:flex-row md:items-end gap-2 md:gap-2">
                    <div class="md:w-20">
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">優先度</label>
                        <select name="priority" id="task_priority" class="w-full border border-slate-100 rounded-lg h-9 px-1.5 text-xs bg-slate-50 outline-none">
                            <option value="3">高</option><option value="2" selected>中</option><option value="1">低</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">カテゴリ</label>
                        <div class="flex gap-1">
                            <input type="text" name="category_name" id="task_category_name" list="categoryList" class="md:w-32 border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none min-w-0" placeholder="カテゴリ...">
                            <input type="color" name="category_color" id="task_category_color" value="#4f46e5" class="w-9 h-9 p-1 bg-white border border-slate-100 rounded-lg cursor-pointer shrink-0">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:flex gap-2">
                        <div class="md:w-32">
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">開始日</label>
                            <input type="date" name="start_date" id="task_start_date" class="w-full border border-slate-100 rounded-lg h-9 px-1.5 text-xs bg-slate-50 outline-none">
                        </div>
                        <div class="md:w-32">
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">期限</label>
                            <input type="date" name="due_date" id="task_due_date" class="w-full border border-slate-100 rounded-lg h-9 px-1.5 text-xs bg-slate-50 outline-none">
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">タスク名</label>
                        <input type="text" name="title" id="task_title" required placeholder="何をしますか？" class="w-full border border-slate-100 rounded-lg h-9 px-3 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" id="btnSubmit" class="bg-slate-800 hover:bg-slate-900 text-white h-9 px-5 rounded-lg font-black text-xs tracking-wider transition shadow-md whitespace-nowrap shrink-0">追加</button>
                    </div>
                </form>
            </section>

            <!-- Stats summary -->
            <div id="statsBar" class="bg-white border border-slate-200 rounded-lg p-3 mb-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] font-black tracking-wider">
                    <span class="text-slate-600"><span id="stat-total" class="text-base font-black text-slate-800">0</span> 件</span>
                    <span class="text-blue-500 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>未着手 <span id="stat-todo">0</span></span>
                    <span class="text-amber-500 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>進行中 <span id="stat-doing">0</span></span>
                    <span class="text-slate-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>保留 <span id="stat-pending">0</span></span>
                    <span class="text-green-500 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>完了 <span id="stat-done">0</span></span>
                    <span id="stat-overdue-wrap" class="text-red-500 flex items-center gap-1 hidden"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>超過 <span id="stat-overdue">0</span></span>
                </div>
                <div class="mt-2 bg-slate-100 rounded-full h-1.5 overflow-hidden flex">
                    <div id="stat-bar-done" class="stats-segment h-full bg-green-400" style="width:0"></div>
                    <div id="stat-bar-doing" class="stats-segment h-full bg-amber-400" style="width:0"></div>
                </div>
            </div>

            <!-- Search + Filter -->
            <div class="flex flex-col md:flex-row gap-3 mb-4">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="タスクを検索..." oninput="onSearchInput()" class="w-full pl-8 pr-3 h-9 border border-slate-200 rounded-lg text-xs bg-white outline-none focus:ring-2 focus:ring-indigo-100">
                </div>
                <div class="flex gap-2">
                    <select id="filterCategory" onchange="onFilterChange()" class="h-9 border border-slate-200 rounded-lg px-2 text-[10px] font-bold text-slate-500 bg-white outline-none">
                        <option value="">全カテゴリ</option>
                    </select>
                    <select id="filterPriority" onchange="onFilterChange()" class="h-9 border border-slate-200 rounded-lg px-2 text-[10px] font-bold text-slate-500 bg-white outline-none">
                        <option value="">全優先度</option>
                        <option value="3">高</option>
                        <option value="2">中</option>
                        <option value="1">低</option>
                    </select>
                </div>
            </div>

            <!-- Sort + toggles -->
            <div id="listControls" class="flex flex-wrap items-center gap-4 mb-4 px-1">
                <select id="viewSort" onchange="renderCurrentMode()" class="text-[10px] font-black text-slate-400 bg-transparent outline-none tracking-wider cursor-pointer">
                    <option value="default">優先度×期日順</option>
                    <option value="priority">優先度順</option>
                    <option value="category">カテゴリ順</option>
                </select>
                <label id="toggleDoneLabel" class="flex items-center gap-2 cursor-pointer">
                    <span class="text-[10px] font-black text-slate-300 tracking-wider">完了済みを表示</span>
                    <button id="toggleDone" class="relative inline-flex h-4 w-9 items-center rounded-full bg-slate-200 transition-colors">
                        <span id="toggleCircle" class="translate-x-0.5 inline-block h-3 w-3 transform rounded-full bg-white transition-transform"></span>
                    </button>
                </label>
                <label id="hinataEventToggleLabel" class="hidden items-center gap-2 cursor-pointer">
                    <span class="text-[10px] font-black text-sky-400 tracking-wider">日向坂イベント表示</span>
                    <button id="toggleHinataEvents" class="relative inline-flex h-4 w-9 items-center rounded-full bg-sky-500 transition-colors">
                        <span id="hinataCircle" class="translate-x-4 inline-block h-3 w-3 transform rounded-full bg-white transition-transform"></span>
                    </button>
                </label>
            </div>

            <!-- List mode -->
            <div id="mode-list" class="space-y-2"></div>

            <!-- Board mode (kanban) -->
            <div id="mode-board" class="hidden">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-blue-50/50 rounded-lg p-3 border border-blue-100">
                        <h3 class="text-[11px] font-black text-blue-600 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>未着手 <span id="board-count-todo" class="text-blue-300 font-bold">0</span>
                        </h3>
                        <div id="board-todo" class="board-col space-y-2" data-status="todo"></div>
                    </div>
                    <div class="bg-amber-50/50 rounded-lg p-3 border border-amber-100">
                        <h3 class="text-[11px] font-black text-amber-600 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>進行中 <span id="board-count-doing" class="text-amber-300 font-bold">0</span>
                        </h3>
                        <div id="board-doing" class="board-col space-y-2" data-status="doing"></div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <h3 class="text-[11px] font-black text-slate-500 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>保留 <span id="board-count-pending" class="text-slate-300 font-bold">0</span>
                        </h3>
                        <div id="board-pending" class="board-col space-y-2" data-status="pending"></div>
                    </div>
                    <div class="bg-green-50/50 rounded-lg p-3 border border-green-100">
                        <h3 class="text-[11px] font-black text-green-600 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>完了 <span id="board-count-done" class="text-green-300 font-bold">0</span>
                        </h3>
                        <div id="board-done" class="board-col space-y-2" data-status="done"></div>
                    </div>
                </div>
            </div>

            <!-- Gantt mode -->
            <div id="mode-gantt" class="hidden">
                <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                    <div class="overflow-x-auto" id="gantt-x-scroll">
                        <div class="inline-block min-w-full" id="gantt-render-root"></div>
                    </div>
                </div>
            </div>

            <!-- Calendar mode -->
            <div id="mode-calendar" class="hidden">
                <div id="calendar"></div>
            </div>
        </div>
    </main>

    <!-- Task detail/edit modal -->
    <div id="modalOverlay" class="modal-overlay" onclick="closeTaskModal()"></div>
    <div id="modalPanel" class="modal-panel">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-black text-slate-800">タスク詳細</h2>
                <button onclick="closeTaskModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-times text-slate-400"></i>
                </button>
            </div>
            <form id="modalForm" onsubmit="saveTaskFromModal(event)">
                <input type="hidden" id="modal_task_id">
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">タスク名</label>
                    <input type="text" id="modal_title" required class="w-full border border-slate-200 rounded-lg h-10 px-4 text-sm bg-white outline-none focus:ring-2 focus:ring-indigo-100 font-bold">
                </div>
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">説明</label>
                    <textarea id="modal_description" rows="3" class="w-full border border-slate-200 rounded-lg px-4 py-2 text-sm bg-white outline-none focus:ring-2 focus:ring-indigo-100 resize-none" placeholder="タスクの詳細を入力..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ステータス</label>
                        <select id="modal_status" class="w-full border border-slate-200 rounded-lg h-9 px-2 text-xs bg-white outline-none">
                            <option value="todo">未着手</option>
                            <option value="doing">進行中</option>
                            <option value="pending">保留</option>
                            <option value="done">完了</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">優先度</label>
                        <select id="modal_priority" class="w-full border border-slate-200 rounded-lg h-9 px-2 text-xs bg-white outline-none">
                            <option value="3">高</option>
                            <option value="2">中</option>
                            <option value="1">低</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">カテゴリ</label>
                    <div class="flex gap-2">
                        <input type="text" id="modal_category_name" list="categoryList" class="flex-1 border border-slate-200 rounded-lg h-9 px-3 text-xs bg-white outline-none" placeholder="カテゴリ名...">
                        <input type="color" id="modal_category_color" value="#4f46e5" class="w-9 h-9 p-1 bg-white border border-slate-200 rounded-lg cursor-pointer">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">開始日</label>
                        <input type="date" id="modal_start_date" class="w-full border border-slate-200 rounded-lg h-9 px-2 text-xs bg-white outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">期限</label>
                        <input type="date" id="modal_due_date" class="w-full border border-slate-200 rounded-lg h-9 px-2 text-xs bg-white outline-none">
                    </div>
                </div>
                <div id="modal_meta" class="text-[10px] text-slate-300 mb-6 flex gap-4"></div>
                <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                    <button type="button" onclick="deleteTaskFromModal()" class="text-[10px] text-red-400 font-black tracking-wider hover:text-red-600 transition">
                        <i class="fa-solid fa-trash-can mr-1"></i>削除
                    </button>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeTaskModal()" class="px-6 h-9 rounded-lg text-xs font-bold text-slate-500 hover:bg-slate-100 transition">キャンセル</button>
                        <button type="submit" class="px-8 h-9 rounded-lg text-xs font-black text-white bg-slate-800 hover:bg-slate-900 transition shadow-md">保存</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <datalist id="categoryList">
        <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['name']) ?>">
        <?php endforeach; ?>
    </datalist>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
    // ============================================================
    // Constants & State
    // ============================================================
    const $ = (id) => document.getElementById(id);
    const rawTasks = <?= json_encode($tasks) ?>;
    const hinataEvents = <?= json_encode($hinataEvents ?? []) ?>;

    const STATUS_MAP = {
        todo:    { label: '未着手', dot: 'bg-blue-500',  text: 'text-blue-600',  bg: 'bg-blue-50' },
        doing:   { label: '進行中', dot: 'bg-amber-500', text: 'text-amber-600', bg: 'bg-amber-50' },
        pending: { label: '保留',   dot: 'bg-slate-400', text: 'text-slate-500', bg: 'bg-slate-50' },
        done:    { label: '完了',   dot: 'bg-green-500', text: 'text-green-600', bg: 'bg-green-50' }
    };

    let showCompleted = false;
    let showHinataEvents = true;
    let currentMode = 'list';
    let calendar;
    let searchQuery = '';
    let boardSortables = [];

    // ============================================================
    // Utilities
    // ============================================================
    function getDueDateInfo(dueDate) {
        if (!dueDate) return { cls: '', badge: '', urgent: false };
        const days = App.calculateRemaining(dueDate);
        if (days < 0)  return { cls: 'badge-overdue', badge: Math.abs(days) + '日超過', urgent: true };
        if (days === 0) return { cls: 'badge-today',   badge: '本日期限', urgent: true };
        if (days <= 3)  return { cls: 'badge-soon',    badge: 'あと' + days + '日', urgent: false };
        return { cls: '', badge: '', urgent: false };
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getFilteredTasks() {
        let tasks = [...rawTasks];
        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            tasks = tasks.filter(t =>
                t.title.toLowerCase().includes(q) ||
                (t.description || '').toLowerCase().includes(q) ||
                (t.category_name || '').toLowerCase().includes(q)
            );
        }
        const fc = $('filterCategory').value;
        if (fc) tasks = tasks.filter(t => (t.category_name || '') === fc);
        const fp = $('filterPriority').value;
        if (fp) tasks = tasks.filter(t => t.priority == fp);
        if (currentMode !== 'board' && !showCompleted) {
            tasks = tasks.filter(t => t.status !== 'done');
        }
        return tasks;
    }

    function findTaskIndex(id) {
        return rawTasks.findIndex(t => t.id == id);
    }

    function updateRawTask(updatedTask) {
        const idx = findTaskIndex(updatedTask.id);
        if (idx !== -1) Object.assign(rawTasks[idx], updatedTask);
    }

    // ============================================================
    // Stats
    // ============================================================
    function renderStats() {
        const all = rawTasks;
        const total = all.length;
        const todo = all.filter(t => t.status === 'todo').length;
        const doing = all.filter(t => t.status === 'doing').length;
        const pending = all.filter(t => t.status === 'pending').length;
        const done = all.filter(t => t.status === 'done').length;
        const today = new Date().toISOString().split('T')[0];
        const overdue = all.filter(t => t.status !== 'done' && t.due_date && t.due_date < today).length;

        $('stat-total').textContent = total;
        $('stat-todo').textContent = todo;
        $('stat-doing').textContent = doing;
        $('stat-pending').textContent = pending;
        $('stat-done').textContent = done;
        $('stat-overdue').textContent = overdue;

        if (overdue > 0) {
            $('stat-overdue-wrap').classList.remove('hidden');
        } else {
            $('stat-overdue-wrap').classList.add('hidden');
        }

        const pctDone = total > 0 ? (done / total * 100) : 0;
        const pctDoing = total > 0 ? (doing / total * 100) : 0;
        $('stat-bar-done').style.width = pctDone + '%';
        $('stat-bar-doing').style.width = pctDoing + '%';
    }

    // ============================================================
    // Empty State
    // ============================================================
    function renderEmptyState(container, message) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 text-slate-300">
                <i class="fa-solid fa-clipboard-check text-4xl mb-3"></i>
                <p class="text-sm font-bold">${message || 'タスクはまだありません'}</p>
                <p class="text-xs mt-1 text-slate-300">上のフォームからタスクを追加しましょう</p>
            </div>`;
    }

    // ============================================================
    // List Rendering
    // ============================================================
    function createTaskElement(task) {
        const el = document.createElement('div');
        const due = getDueDateInfo(task.due_date);
        const st = STATUS_MAP[task.status] || STATUS_MAP.todo;
        const isDone = task.status === 'done';

        let bgClass = 'bg-white';
        let borderClass = 'border-slate-200';
        if (due.urgent && !isDone) {
            bgClass = task.due_date < new Date().toISOString().split('T')[0] ? 'bg-red-50/60' : 'bg-orange-50/60';
            borderClass = task.due_date < new Date().toISOString().split('T')[0] ? 'border-red-200' : 'border-orange-200';
        }

        el.className = `${bgClass} border ${borderClass} rounded-lg p-3 shadow-sm flex items-center gap-3 cursor-pointer hover:shadow-md transition-all ${isDone ? 'row-done' : ''}`;
        el.style.borderLeft = '5px solid ' + (task.category_color || '#cbd5e1');
        el.onclick = () => openTaskModal(task);

        const priColor = task.priority == 3 ? 'text-red-500' : (task.priority == 2 ? 'text-orange-400' : 'text-slate-300');
        const dueBadge = due.badge && !isDone ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-black ${due.cls}">${due.badge}</span>` : '';
        const dateText = task.due_date && !due.badge ? task.due_date.substr(5).replace('-', '/') : '';
        const statusBadge = task.status !== 'todo' && task.status !== 'done'
            ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold ${st.bg} ${st.text}">${st.label}</span>` : '';

        el.innerHTML = `
            <div class="shrink-0 w-4 text-center"><span class="text-[10px] font-black ${priColor}">${'!'.repeat(task.priority)}</span></div>
            <div class="flex-1 min-w-0">
                <div class="title-text font-bold text-[13px] truncate">${escapeHtml(task.title)}</div>
                <div class="flex flex-wrap gap-1.5 mt-0.5 text-[9px] font-bold text-slate-400 items-center">
                    <span class="bg-slate-100 px-1 rounded">${escapeHtml(task.category_name || '未分類')}</span>
                    ${statusBadge}
                    ${dueBadge}
                    ${dateText ? '<span>' + dateText + '</span>' : ''}
                </div>
            </div>
            <div class="shrink-0" onclick="event.stopPropagation()">
                <input type="checkbox" ${isDone ? 'checked' : ''} onchange="toggleStatus(${task.id}, this.checked)" class="w-5 h-5 rounded border-slate-300 text-indigo-600 cursor-pointer">
            </div>`;
        return el;
    }

    function renderList() {
        const container = $('mode-list');
        container.innerHTML = '';
        const tasks = getFilteredTasks();

        if (tasks.length === 0) {
            renderEmptyState(container, searchQuery ? '検索結果がありません' : 'タスクはまだありません');
            return;
        }

        const sortType = $('viewSort').value;
        if (sortType === 'category') {
            const sorted = sortTasks(tasks);
            const groups = {};
            sorted.forEach(t => {
                const cat = t.category_name || '未分類';
                if (!groups[cat]) groups[cat] = { color: t.category_color || '#cbd5e1', tasks: [] };
                groups[cat].tasks.push(t);
            });
            Object.keys(groups).forEach(cat => {
                const header = document.createElement('div');
                header.className = 'mt-6 mb-3 pb-2 border-b-2 border-slate-200';
                header.innerHTML = `<h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color:${groups[cat].color}"></span>
                    ${escapeHtml(cat)} <span class="text-xs text-slate-400 font-bold">(${groups[cat].tasks.length}件)</span>
                </h2>`;
                container.appendChild(header);
                groups[cat].tasks.forEach(t => container.appendChild(createTaskElement(t)));
            });
        } else {
            sortTasks(tasks).forEach(t => container.appendChild(createTaskElement(t)));
        }
    }

    function sortTasks(tasks) {
        const s = $('viewSort').value;
        const sorted = [...tasks];
        const cmpDue = (a, b) => {
            if (!a.due_date && !b.due_date) return 0;
            if (!a.due_date) return 1;
            if (!b.due_date) return -1;
            return a.due_date.localeCompare(b.due_date);
        };
        switch (s) {
            case 'priority':
                sorted.sort((a, b) => a.priority !== b.priority ? b.priority - a.priority : cmpDue(a, b));
                break;
            case 'category':
                sorted.sort((a, b) => {
                    const ca = a.category_name || '未分類', cb = b.category_name || '未分類';
                    if (ca !== cb) return ca.localeCompare(cb, 'ja');
                    return a.priority !== b.priority ? b.priority - a.priority : cmpDue(a, b);
                });
                break;
            default:
                sorted.sort((a, b) => a.priority !== b.priority ? b.priority - a.priority : cmpDue(a, b));
        }
        return sorted;
    }

    // ============================================================
    // Board (Kanban) Rendering
    // ============================================================
    function createBoardCard(task) {
        const el = document.createElement('div');
        el.className = 'board-card bg-white rounded-lg p-2.5 border border-slate-200 cursor-pointer text-[11px]';
        el.dataset.taskId = task.id;
        el.style.borderLeft = '4px solid ' + (task.category_color || '#cbd5e1');
        el.onclick = () => openTaskModal(task);

        const priColor = task.priority == 3 ? 'text-red-500' : (task.priority == 2 ? 'text-orange-400' : 'text-slate-300');
        const due = getDueDateInfo(task.due_date);
        const dueBadge = due.badge ? `<span class="px-1 py-0.5 rounded text-[8px] font-black ${due.cls}">${due.badge}</span>` : '';

        el.innerHTML = `
            <div class="font-bold truncate mb-1">${escapeHtml(task.title)}</div>
            <div class="flex flex-wrap gap-1 items-center">
                <span class="font-black ${priColor}">${'!'.repeat(task.priority)}</span>
                <span class="text-[9px] text-slate-400">${escapeHtml(task.category_name || '')}</span>
                ${dueBadge}
            </div>`;
        return el;
    }

    function renderBoard() {
        const tasks = getFilteredTasks();
        const cols = { todo: [], doing: [], pending: [], done: [] };
        tasks.forEach(t => { if (cols[t.status]) cols[t.status].push(t); else cols.todo.push(t); });

        ['todo', 'doing', 'pending', 'done'].forEach(status => {
            const container = $('board-' + status);
            container.innerHTML = '';
            cols[status].forEach(t => container.appendChild(createBoardCard(t)));
            $('board-count-' + status).textContent = cols[status].length;
        });

        initBoardSortable();
    }

    function initBoardSortable() {
        boardSortables.forEach(s => s.destroy());
        boardSortables = [];
        ['todo', 'doing', 'pending', 'done'].forEach(status => {
            const el = $('board-' + status);
            boardSortables.push(Sortable.create(el, {
                group: 'kanban',
                animation: 200,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function (evt) {
                    const taskId = evt.item.dataset.taskId;
                    const newStatus = evt.to.dataset.status;
                    if (!taskId || !newStatus) return;
                    updateTaskStatus(taskId, newStatus);
                }
            }));
        });
    }

    // ============================================================
    // Gantt Rendering
    // ============================================================
    function renderGantt() {
        const root = $('gantt-render-root');
        root.innerHTML = '';
        const tasks = getFilteredTasks().filter(t => t.start_date || t.due_date);

        if (tasks.length === 0) {
            root.innerHTML = '<p class="text-center p-10 text-slate-400">期間設定のあるタスクがありません</p>';
            return;
        }

        let minDate = null, maxDate = null;
        tasks.forEach(t => {
            const s = t.start_date || t.due_date, e = t.due_date || t.start_date;
            if (!minDate || s < minDate) minDate = s;
            if (!maxDate || e > maxDate) maxDate = e;
        });
        const today = new Date().toISOString().split('T')[0];
        if (!minDate || today < minDate) minDate = today;
        if (!maxDate || today > maxDate) maxDate = today;

        const startDate = new Date(minDate); startDate.setDate(startDate.getDate() - 3);
        const endDate = new Date(maxDate); endDate.setDate(endDate.getDate() + 7);
        const dates = [];
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) dates.push(new Date(d).toISOString().split('T')[0]);

        const headerRow = document.createElement('div');
        headerRow.className = 'flex border-b border-slate-200 bg-slate-50 sticky top-0 z-10';
        headerRow.innerHTML = `
            <div class="w-40 shrink-0 px-3 py-2 text-[10px] font-black text-slate-500 border-r border-slate-200 sticky left-0 z-20 bg-slate-50">タスク</div>
            <div class="flex">${dates.map(date => {
                const d = new Date(date), isToday = date === today;
                return `<div class="w-12 shrink-0 px-1 py-2 text-center text-[9px] font-bold ${isToday ? 'bg-yellow-100 text-yellow-900' : 'text-slate-400'} border-r border-slate-100">${d.getDate() === 1 ? (d.getMonth()+1)+'/' : ''}${d.getDate()}</div>`;
            }).join('')}</div>`;
        root.appendChild(headerRow);

        tasks.forEach(task => {
            const start = task.start_date || task.due_date, end = task.due_date || task.start_date;
            const si = dates.indexOf(start), ei = dates.indexOf(end);
            if (si === -1 || ei === -1) return;

            const row = document.createElement('div');
            row.className = 'flex border-b border-slate-100 hover:bg-slate-50 cursor-pointer';
            row.onclick = () => openTaskModal(task);

            const tc = document.createElement('div');
            tc.className = `w-40 shrink-0 px-3 py-3 text-[11px] font-bold text-slate-700 border-r border-slate-200 truncate ${task.status==='done'?'opacity-50 line-through':''} sticky left-0 z-10 bg-white`;
            tc.textContent = task.title;
            tc.style.borderLeft = '4px solid ' + (task.category_color || '#cbd5e1');
            row.appendChild(tc);

            const gc = document.createElement('div'); gc.className = 'flex relative';
            for (let i = 0; i < dates.length; i++) {
                const dc = document.createElement('div');
                dc.className = `w-12 shrink-0 border-r border-slate-100 ${dates[i] === today ? 'bg-yellow-50' : ''}`;
                gc.appendChild(dc);
            }

            const bw = (ei - si + 1) * 48;
            const bar = document.createElement('div');
            bar.className = 'absolute h-6 rounded flex items-center justify-center text-[9px] font-bold text-white px-2';
            Object.assign(bar.style, { left: (si*48+4)+'px', width: (bw-8)+'px', top: '6px', backgroundColor: task.category_color||'#4f46e5', opacity: task.status==='done'?'0.4':'0.9' });
            bar.textContent = (Math.ceil((new Date(end)-new Date(start))/(864e5))+1) + '日';
            gc.appendChild(bar);
            row.appendChild(gc);
            root.appendChild(row);
        });
    }

    // ============================================================
    // Calendar
    // ============================================================
    function initCalendar() {
        calendar = new FullCalendar.Calendar($('calendar'), {
            initialView: 'dayGridMonth', locale: 'ja',
            headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
            events: getCalendarEvents(),
            eventClick: function(info) {
                if (info.event.extendedProps.type === 'task') {
                    const t = rawTasks.find(x => x.id == info.event.extendedProps.taskId);
                    if (t) openTaskModal(t);
                } else if (info.event.extendedProps.type === 'hinata') {
                    App.toast('日向坂イベント: ' + info.event.title.replace('⭐ ', ''));
                }
            }
        });
        calendar.render();
    }

    function getCalendarEvents() {
        const tf = getFilteredTasks().filter(t => t.due_date);
        const taskEvents = tf.map(t => ({
            id: 'task-' + t.id, title: t.title,
            start: t.start_date || t.due_date, end: t.due_date, allDay: true,
            backgroundColor: t.category_color || '#4f46e5', borderColor: t.category_color || '#4f46e5',
            extendedProps: { type: 'task', taskId: t.id }
        }));
        const he = showHinataEvents ? hinataEvents.map(e => ({
            id: 'event-' + e.id, title: '⭐ ' + e.event_name, start: e.event_date, allDay: true,
            backgroundColor: '#0ea5e9', borderColor: '#0284c7', textColor: '#fff',
            extendedProps: { type: 'hinata', eventId: e.id }
        })) : [];
        return [...taskEvents, ...he];
    }

    function renderFullCalendar() { calendar.removeAllEvents(); calendar.addEventSource(getCalendarEvents()); }

    // ============================================================
    // Modal
    // ============================================================
    function openTaskModal(taskOrNull) {
        const task = taskOrNull ? rawTasks.find(t => t.id == taskOrNull.id) || taskOrNull : null;
        if (!task) return;
        $('modal_task_id').value = task.id;
        $('modal_title').value = task.title;
        $('modal_description').value = task.description || '';
        $('modal_status').value = task.status || 'todo';
        $('modal_priority').value = task.priority || 2;
        $('modal_category_name').value = task.category_name || '';
        $('modal_category_color').value = task.category_color || '#4f46e5';
        $('modal_start_date').value = task.start_date || '';
        $('modal_due_date').value = task.due_date || '';

        let meta = '';
        if (task.created_at) meta += '作成: ' + task.created_at.substring(0, 16);
        if (task.updated_at) meta += (meta ? '　' : '') + '更新: ' + task.updated_at.substring(0, 16);
        $('modal_meta').textContent = meta;

        $('modalOverlay').classList.add('open');
        $('modalPanel').classList.add('open');
    }

    function closeTaskModal() {
        $('modalOverlay').classList.remove('open');
        $('modalPanel').classList.remove('open');
    }

    async function saveTaskFromModal(e) {
        e.preventDefault();
        const id = $('modal_task_id').value;
        const data = {
            id: id,
            title: $('modal_title').value,
            description: $('modal_description').value,
            status: $('modal_status').value,
            priority: $('modal_priority').value,
            category_name: $('modal_category_name').value,
            category_color: $('modal_category_color').value,
            start_date: $('modal_start_date').value,
            due_date: $('modal_due_date').value
        };
        const res = await App.post('api/update.php', data);
        if (res.status === 'success' && res.task) {
            updateRawTask(res.task);
            renderStats();
            renderCurrentMode();
            if (calendar) renderFullCalendar();
            closeTaskModal();
            App.toast('タスクを更新しました');
        }
    }

    async function deleteTaskFromModal() {
        if (!confirm('このタスクを削除しますか？')) return;
        const id = $('modal_task_id').value;
        const res = await App.post('api/delete.php', { id });
        if (res.status === 'success') {
            const idx = findTaskIndex(id);
            if (idx !== -1) rawTasks.splice(idx, 1);
            renderStats();
            renderCurrentMode();
            if (calendar) renderFullCalendar();
            closeTaskModal();
            App.toast('タスクを削除しました');
        }
    }

    // ============================================================
    // Task CRUD
    // ============================================================
    async function saveNewTask(e) {
        e.preventDefault();
        const fd = Object.fromEntries(new FormData(e.target));
        const res = await App.post('api/save.php', fd);
        if (res.status === 'success' && res.task) {
            rawTasks.push(res.task);
            e.target.reset();
            $('task_category_color').value = '#4f46e5';
            populateFilterCategories();
            renderStats();
            renderCurrentMode();
            if (calendar) renderFullCalendar();
            App.toast('タスクを追加しました');
        }
    }

    async function toggleStatus(id, checked) {
        const newStatus = checked ? 'done' : 'todo';
        const res = await App.post('api/update.php', { id, status: newStatus });
        if (res.status === 'success' && res.task) {
            updateRawTask(res.task);
            renderStats();
            renderCurrentMode();
            if (calendar) renderFullCalendar();
            App.toast(checked ? '完了にしました' : '未完了に戻しました');
        }
    }

    async function updateTaskStatus(id, newStatus) {
        const res = await App.post('api/update.php', { id: parseInt(id), status: newStatus });
        if (res.status === 'success' && res.task) {
            updateRawTask(res.task);
            renderStats();
            if (calendar) renderFullCalendar();
            App.toast(STATUS_MAP[newStatus]?.label + ' に変更しました');
        }
    }

    // ============================================================
    // Quick Add Form
    // ============================================================
    function toggleQuickAdd() {
        const c = $('quickAddContainer'), icon = $('toggleIcon');
        const expanded = c.classList.toggle('expanded');
        icon.classList.toggle('fa-chevron-down', !expanded);
        icon.classList.toggle('fa-chevron-up', expanded);
    }

    function openQuickAdd() {
        if (window.innerWidth <= 768) {
            const c = $('quickAddContainer');
            if (!c.classList.contains('expanded')) {
                c.classList.add('expanded');
                $('toggleIcon').classList.remove('fa-chevron-down');
                $('toggleIcon').classList.add('fa-chevron-up');
            }
        }
        $('mainScroll').scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ============================================================
    // Mode Switching
    // ============================================================
    function switchMode(mode) {
        currentMode = mode;
        ['list', 'board', 'gantt', 'calendar'].forEach(m => {
            $('mode-' + m).classList.toggle('hidden', m !== mode);
            $('tab-' + m).classList.toggle('active', m === mode);
        });

        // list controls visibility
        const sortEl = $('viewSort');
        const doneLabel = $('toggleDoneLabel');
        const hinataLabel = $('hinataEventToggleLabel');
        sortEl.style.display = (mode === 'list') ? '' : 'none';
        doneLabel.style.display = (mode === 'board') ? 'none' : '';
        hinataLabel.classList.toggle('hidden', mode !== 'calendar');
        hinataLabel.classList.toggle('flex', mode === 'calendar');

        if (mode === 'calendar' && calendar) setTimeout(() => calendar.updateSize(), 10);
        renderCurrentMode();
    }

    function renderCurrentMode() {
        switch (currentMode) {
            case 'list': renderList(); break;
            case 'board': renderBoard(); break;
            case 'gantt': renderGantt(); break;
            case 'calendar': renderFullCalendar(); break;
        }
    }

    // ============================================================
    // Search & Filter
    // ============================================================
    function onSearchInput() {
        searchQuery = $('searchInput').value.trim();
        renderCurrentMode();
    }

    function onFilterChange() {
        renderCurrentMode();
    }

    function populateFilterCategories() {
        const sel = $('filterCategory');
        const current = sel.value;
        const cats = new Set();
        rawTasks.forEach(t => { if (t.category_name) cats.add(t.category_name); });
        sel.innerHTML = '<option value="">全カテゴリ</option>';
        [...cats].sort((a, b) => a.localeCompare(b, 'ja')).forEach(c => {
            sel.innerHTML += `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`;
        });
        sel.value = current;
    }

    // ============================================================
    // Init
    // ============================================================
    window.onload = () => {
        $('currentDate').innerText = new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit', weekday: 'short' });

        // Toggle: show completed
        $('toggleDone').onclick = function() {
            showCompleted = !showCompleted;
            this.classList.toggle('bg-indigo-600', showCompleted);
            this.classList.toggle('bg-slate-200', !showCompleted);
            $('toggleCircle').classList.toggle('translate-x-4', showCompleted);
            $('toggleCircle').classList.toggle('translate-x-0.5', !showCompleted);
            renderCurrentMode();
            if (calendar) renderFullCalendar();
        };

        // Toggle: hinata events
        const ht = $('toggleHinataEvents');
        if (ht) {
            ht.onclick = function() {
                showHinataEvents = !showHinataEvents;
                this.classList.toggle('bg-sky-500', showHinataEvents);
                this.classList.toggle('bg-slate-200', !showHinataEvents);
                $('hinataCircle').classList.toggle('translate-x-4', showHinataEvents);
                $('hinataCircle').classList.toggle('translate-x-0.5', !showHinataEvents);
                if (calendar) renderFullCalendar();
            };
        }

        // Quick add form
        $('taskForm').onsubmit = saveNewTask;

        // Populate category filter
        populateFilterCategories();

        // Initial render
        renderStats();
        renderList();
        initCalendar();

        // Auto-open task from URL param
        const taskId = new URLSearchParams(window.location.search).get('task_id');
        if (taskId) {
            const task = rawTasks.find(t => t.id == taskId);
            if (task) setTimeout(() => { openTaskModal(task); window.history.replaceState({}, '', '/task_manager/'); }, 300);
        }

        // Close modal on Escape
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTaskModal(); });
    };
    </script>
</body>
</html>
