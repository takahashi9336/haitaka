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
    <style>
        :root { --task-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .tab-btn.active { color: var(--task-theme); border-bottom-color: var(--task-theme); }
        .task-pull-refresh { color: var(--task-theme); }
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
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
            #quickAddContainer { max-height: 0; overflow: hidden; padding: 0; margin-bottom: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
            #quickAddContainer.expanded { max-height: 600px; padding: 1rem; margin-bottom: 1.5rem; }
        }
        #quickAddToggle { display: none; }
        @media (max-width: 768px) {
            #quickAddToggle { display: flex; }
        }
        .row-done { opacity: 0.4; background-color: #f8fafc; }
        .row-done .title-text { text-decoration: line-through; }
        .tab-btn { font-size: 12px; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; padding-bottom: 8px; transition: all 0.2s; }
        .tab-btn.active { font-weight: 800; }
        .gantt-sticky-col { width: 120px; flex-shrink: 0; background: inherit; padding: 0 10px; border-right: 1px solid #e2e8f0; display: flex; align-items: center; font-size: 10px; font-weight: 700; position: sticky; left: 0; z-index: 30; }
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

        <div class="bg-white border-b border-slate-200 px-6 flex gap-8 shrink-0 h-12 items-end shadow-sm">
            <button onclick="switchMode('list')" id="tab-list" class="tab-btn active">リスト</button>
            <button onclick="switchMode('gantt')" id="tab-gantt" class="tab-btn">ガントチャート</button>
            <button onclick="switchMode('calendar')" id="tab-calendar" class="tab-btn">カレンダー</button>
        </div>

        <div id="mainScroll" class="flex-1 overflow-y-auto p-3 md:p-6 custom-scroll relative">
            
            <!-- Pull-to-Refresh インジケーター（モバイルのみ） -->
            <div id="pullToRefresh" class="md:hidden absolute top-0 left-0 right-0 flex flex-col items-center justify-center transition-all duration-300 pointer-events-none task-pull-refresh" style="height: 60px; transform: translateY(-60px); opacity: 0;">
                <i id="pullIcon" class="fa-solid fa-arrow-down text-2xl mb-1 transition-transform duration-300"></i>
                <span id="pullText" class="text-xs font-bold">引っ張って更新</span>
            </div>
            
            <!-- モバイル用タスク追加ボタン -->
            <button id="quickAddToggle" onclick="toggleQuickAdd()" class="w-full text-white px-4 py-3 rounded-xl font-black text-sm mb-4 shadow-lg transition-all flex items-center justify-center gap-2 task-quick-add-btn">
                <i class="fa-solid fa-plus"></i>
                <span id="toggleText">タスクを追加</span>
                <i id="toggleIcon" class="fa-solid fa-chevron-down ml-auto text-xs"></i>
            </button>
            
            <section id="quickAddContainer" class="bg-white border border-slate-200 rounded-lg p-4 mb-6 shadow-sm">
                <form id="taskForm" class="flex flex-col gap-4">
                    <input type="hidden" name="id" id="task_id">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">優先度</label>
                            <select name="priority" id="task_priority" class="w-full border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none">
                                <option value="3">高</option><option value="2" selected>中</option><option value="1">低</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">開始日</label>
                            <input type="date" name="start_date" id="task_start_date" class="w-full border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">期限</label>
                            <input type="date" name="due_date" id="task_due_date" class="w-full border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none">
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">タスク名</label>
                            <input type="text" name="title" id="task_title" required placeholder="何をしますか？" class="w-full border border-slate-100 rounded-lg h-10 px-4 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                        <div class="md:w-64">
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">カテゴリ</label>
                            <div class="flex gap-2">
                                <input type="text" name="category_name" id="task_category_name" list="categoryList" class="flex-1 border border-slate-100 rounded-lg h-10 px-3 text-xs bg-slate-50 outline-none" placeholder="新規カテゴリ...">
                                <input type="color" name="category_color" id="task_category_color" value="#4f46e5" class="w-10 h-10 p-1 bg-white border border-slate-100 rounded-lg cursor-pointer">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-50">
                        <div class="flex gap-4">
                            <button type="button" id="btnCancelEdit" class="hidden text-[10px] text-slate-400 font-black tracking-wider hover:text-red-500">キャンセル</button>
                            <button type="button" id="btnDelete" class="hidden text-[10px] text-red-400 font-black tracking-wider hover:text-red-600">削除</button>
                        </div>
                        <button type="submit" id="btnSubmit" class="bg-slate-800 hover:bg-slate-900 text-white px-10 h-10 rounded-xl font-black text-xs tracking-wider transition shadow-md">タスクを追加</button>
                    </div>
                </form>
            </section>

            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-4">
                    <select id="viewSort" onchange="renderList()" class="text-[10px] font-black text-slate-400 bg-transparent outline-none tracking-wider cursor-pointer">
                        <option value="default">優先度×期日順</option>
                        <option value="priority">優先度順</option>
                        <option value="category">カテゴリ順</option>
                    </select>
                    <label class="flex items-center gap-2 cursor-pointer">
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
            </div>

            <div id="mode-list" class="space-y-2"></div>
            <div id="mode-gantt" class="hidden">
                <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                    <div class="overflow-x-auto" id="gantt-x-scroll">
                        <div class="inline-block min-w-full" id="gantt-render-root"></div>
                    </div>
                </div>
            </div>
            <div id="mode-calendar" class="hidden">
                <div id="calendar"></div>
            </div>
        </div>
    </main>

    <datalist id="categoryList">
        <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['name']) ?>">
        <?php endforeach; ?>
    </datalist>

    <script src="/assets/js/core.js"></script>
    <script>
        const $ = (id) => document.getElementById(id);
        const rawTasks = <?php echo json_encode($tasks); ?>;
        const hinataEvents = <?php echo json_encode($hinataEvents ?? []); ?>;
        let showCompleted = false;
        let showHinataEvents = true;
        let currentMode = 'list';
        let calendar;

        function toggleQuickAdd() {
            const container = $('quickAddContainer');
            const icon = $('toggleIcon');
            const expanded = container.classList.toggle('expanded');
            
            if (expanded) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        
        function openQuickAdd() {
            const container = $('quickAddContainer');
            const icon = $('toggleIcon');
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // モバイル: アコーディオンを展開
                if (!container.classList.contains('expanded')) {
                    container.classList.add('expanded');
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
            
            // タスク追加欄にスクロール
            $('mainScroll').scrollTo({ top: 0, behavior: 'smooth' });
        }

        window.onload = () => {
            $('currentDate').innerText = new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit', weekday: 'short' });
            $('toggleDone').onclick = function() {
                showCompleted = !showCompleted;
                this.classList.toggle('bg-indigo-600', showCompleted);
                this.classList.toggle('bg-slate-200', !showCompleted);
                $('toggleCircle').classList.toggle('translate-x-4', showCompleted);
                $('toggleCircle').classList.toggle('translate-x-0.5', !showCompleted);
                renderCurrentMode();
                if (calendar) renderFullCalendar();
            };
            
            const hinataToggle = $('toggleHinataEvents');
            if (hinataToggle) {
                hinataToggle.onclick = function() {
                    showHinataEvents = !showHinataEvents;
                    this.classList.toggle('bg-sky-500', showHinataEvents);
                    this.classList.toggle('bg-slate-200', !showHinataEvents);
                    $('hinataCircle').classList.toggle('translate-x-4', showHinataEvents);
                    $('hinataCircle').classList.toggle('translate-x-0.5', !showHinataEvents);
                    if (calendar) renderFullCalendar();
                };
            }
            $('taskForm').onsubmit = async (e) => {
                e.preventDefault();
                const res = await App.post($('task_id').value ? 'api/update.php' : 'api/save.php', Object.fromEntries(new FormData(e.target)));
                if (res.status === 'success') location.reload();
            };
            $('btnDelete').onclick = async () => { if (confirm('このタスクを削除しますか？')) { const res = await App.post('api/delete.php', { id: $('task_id').value }); if (res.status === 'success') location.reload(); } };
            $('btnCancelEdit').onclick = resetForm;
            renderList();
            initCalendar();
            
            // URLパラメータからタスクIDを取得して自動選択
            const urlParams = new URLSearchParams(window.location.search);
            const taskId = urlParams.get('task_id');
            if (taskId) {
                const task = rawTasks.find(t => t.id == taskId);
                if (task) {
                    setTimeout(() => {
                        editTask(task);
                        // URLパラメータをクリア
                        window.history.replaceState({}, '', '/task_manager/');
                    }, 300);
                }
            }
        };

        function switchMode(mode) {
            currentMode = mode;
            ['list', 'gantt', 'calendar'].forEach(m => { $(`mode-${m}`).classList.toggle('hidden', m !== mode); $(`tab-${m}`).classList.toggle('active', m === mode); });
            
            // カレンダーモードの時のみ日向坂イベントトグルを表示
            const hinataToggle = $('hinataEventToggleLabel');
            if (hinataToggle) {
                if (mode === 'calendar') {
                    hinataToggle.classList.remove('hidden');
                    hinataToggle.classList.add('flex');
                } else {
                    hinataToggle.classList.add('hidden');
                    hinataToggle.classList.remove('flex');
                }
            }
            
            if (mode === 'calendar' && calendar) setTimeout(() => calendar.updateSize(), 10);
            renderCurrentMode();
        }

        function renderCurrentMode() { if (currentMode === 'list') renderList(); else if (currentMode === 'gantt') renderGantt(); }

        function sortTasks(tasks) {
            const sortType = $('viewSort').value;
            const sorted = [...tasks];
            
            switch(sortType) {
                case 'priority':
                    // 優先度順（高→低）、同じ優先度なら期日順
                    sorted.sort((a, b) => {
                        if (a.priority !== b.priority) return b.priority - a.priority;
                        if (!a.due_date && !b.due_date) return 0;
                        if (!a.due_date) return 1;
                        if (!b.due_date) return -1;
                        return a.due_date.localeCompare(b.due_date);
                    });
                    break;
                case 'category':
                    // カテゴリ順、同じカテゴリなら優先度×期日順
                    sorted.sort((a, b) => {
                        const catA = a.category_name || '未分類';
                        const catB = b.category_name || '未分類';
                        if (catA !== catB) return catA.localeCompare(catB, 'ja');
                        if (a.priority !== b.priority) return b.priority - a.priority;
                        if (!a.due_date && !b.due_date) return 0;
                        if (!a.due_date) return 1;
                        if (!b.due_date) return -1;
                        return a.due_date.localeCompare(b.due_date);
                    });
                    break;
                case 'default':
                default:
                    // デフォルト: 優先度×期日順（優先度高→低、期日早→遅）
                    sorted.sort((a, b) => {
                        if (a.priority !== b.priority) return b.priority - a.priority;
                        if (!a.due_date && !b.due_date) return 0;
                        if (!a.due_date) return 1;
                        if (!b.due_date) return -1;
                        return a.due_date.localeCompare(b.due_date);
                    });
                    break;
            }
            
            return sorted;
        }
        
        function createTaskElement(task) {
            const el = document.createElement('div');
            const isSelected = $('task_id').value == task.id;
            el.className = `${isSelected ? 'bg-yellow-50' : 'bg-white'} border ${isSelected ? 'border-yellow-300' : 'border-slate-200'} rounded-lg p-3 shadow-sm flex items-center gap-3 cursor-pointer hover:border-indigo-200 ${task.status==='done'?'row-done':''}`;
            el.style.borderLeft = `5px solid ${task.category_color||'#cbd5e1'}`;
            el.onclick = () => editTask(task);
            el.ondblclick = (e) => {
                e.stopPropagation();
                openQuickAdd();
            };
            el.innerHTML = `
                <div class="shrink-0 w-4 text-center"><span class="text-[10px] font-black ${task.priority==3?'text-red-500':'text-orange-400'}">${'!'.repeat(task.priority)}</span></div>
                <div class="flex-1 min-w-0">
                    <div class="title-text font-bold text-[13px] truncate">${task.title}</div>
                    <div class="flex gap-2 mt-0.5 text-[9px] font-bold text-slate-400">
                        <span class="bg-slate-100 px-1 rounded">${task.category_name||'未分類'}</span>
                        <span>${task.due_date?task.due_date.substr(5).replace('-','/'):''}</span>
                    </div>
                </div>
                <div class="shrink-0" onclick="event.stopPropagation()"><input type="checkbox" ${task.status==='done'?'checked':''} onchange="toggleStatus(${task.id}, this.checked)" class="w-5 h-5 rounded border-slate-300 text-indigo-600"></div>
            `;
            return el;
        }
        
        function renderList() {
            const container = $('mode-list');
            container.innerHTML = '';
            
            let tasks = rawTasks.filter(t => showCompleted || t.status !== 'done');
            const sortType = $('viewSort').value;
            
            if (sortType === 'category') {
                // カテゴリ順の場合はグルーピング表示
                const sorted = sortTasks(tasks);
                const categories = {};
                
                sorted.forEach(task => {
                    const catName = task.category_name || '未分類';
                    if (!categories[catName]) {
                        categories[catName] = {
                            color: task.category_color || '#cbd5e1',
                            tasks: []
                        };
                    }
                    categories[catName].tasks.push(task);
                });
                
                Object.keys(categories).forEach(catName => {
                    // カテゴリヘッダー
                    const header = document.createElement('div');
                    header.className = 'mt-6 mb-3 pb-2 border-b-2 border-slate-200';
                    header.innerHTML = `<h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background-color: ${categories[catName].color}"></span>
                        ${catName} <span class="text-xs text-slate-400 font-bold">(${categories[catName].tasks.length}件)</span>
                    </h2>`;
                    container.appendChild(header);
                    
                    // タスク
                    categories[catName].tasks.forEach(task => {
                        container.appendChild(createTaskElement(task));
                    });
                });
            } else {
                // その他のソート順は通常表示
                const sorted = sortTasks(tasks);
                sorted.forEach(task => {
                    container.appendChild(createTaskElement(task));
                });
            }
        }

        function initCalendar() {
            calendar = new FullCalendar.Calendar($('calendar'), {
                initialView: 'dayGridMonth', locale: 'ja', headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                events: getCalendarEvents(),
                eventClick: function(info) {
                    if (info.event.extendedProps.type === 'task') {
                        const t = rawTasks.find(x => x.id == info.event.extendedProps.taskId);
                        if(t) { switchMode('list'); editTask(t); }
                    } else if (info.event.extendedProps.type === 'hinata') {
                        // 日向坂イベントの場合は何もしない（または詳細表示）
                        alert('日向坂イベント: ' + info.event.title.replace('⭐ ', ''));
                    }
                }
            });
            calendar.render();
        }

        function getCalendarEvents() {
            const taskEvents = rawTasks.filter(t => (showCompleted || t.status !== 'done') && t.due_date).map(t => ({
                id: 'task-' + t.id,
                title: t.title,
                start: t.start_date || t.due_date,
                end: t.due_date,
                allDay: true,
                backgroundColor: t.category_color || '#4f46e5',
                borderColor: t.category_color || '#4f46e5',
                extendedProps: { type: 'task', taskId: t.id }
            }));
            
            const hinataEventsForCal = showHinataEvents ? hinataEvents.map(e => ({
                id: 'event-' + e.id,
                title: '⭐ ' + e.event_name,
                start: e.event_date,
                allDay: true,
                backgroundColor: '#0ea5e9',
                borderColor: '#0284c7',
                textColor: '#ffffff',
                extendedProps: { type: 'hinata', eventId: e.id }
            })) : [];
            
            return [...taskEvents, ...hinataEventsForCal];
        }

        function renderFullCalendar() { calendar.removeAllEvents(); calendar.addEventSource(getCalendarEvents()); }

        function renderGantt() {
            const root = $('gantt-render-root');
            root.innerHTML = '';
            
            // 期間が設定されているタスクを取得
            const tasks = rawTasks.filter(t => {
                return (showCompleted || t.status !== 'done') && (t.start_date || t.due_date);
            });
            
            if (tasks.length === 0) {
                root.innerHTML = '<p class="text-center p-10 text-slate-400">期間設定のあるタスクがありません</p>';
                return;
            }
            
            // 日付範囲を計算（過去日付にも対応）
            let minDate = null;
            let maxDate = null;
            
            tasks.forEach(t => {
                const start = t.start_date || t.due_date;
                const end = t.due_date || t.start_date;
                
                if (!minDate || start < minDate) minDate = start;
                if (!maxDate || end > maxDate) maxDate = end;
            });
            
            // 今日の日付も考慮
            const today = new Date().toISOString().split('T')[0];
            if (!minDate || today < minDate) minDate = today;
            if (!maxDate || today > maxDate) maxDate = today;
            
            // 表示範囲を少し広げる
            const startDate = new Date(minDate);
            startDate.setDate(startDate.getDate() - 3);
            const endDate = new Date(maxDate);
            endDate.setDate(endDate.getDate() + 7);
            
            // 日付配列を生成
            const dates = [];
            for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
                dates.push(new Date(d).toISOString().split('T')[0]);
            }
            
            // ヘッダー行を作成
            const headerRow = document.createElement('div');
            headerRow.className = 'flex border-b border-slate-200 bg-slate-50 sticky top-0 z-10';
            headerRow.innerHTML = `
                <div class="w-40 shrink-0 px-3 py-2 text-[10px] font-black text-slate-500 border-r border-slate-200 sticky left-0 z-20 bg-slate-50">タスク</div>
                <div class="flex">
                    ${dates.map(date => {
                        const d = new Date(date);
                        const isToday = date === today;
                        const month = d.getMonth() + 1;
                        const day = d.getDate();
                        return `<div class="w-12 shrink-0 px-1 py-2 text-center text-[9px] font-bold ${isToday ? 'bg-yellow-100 text-yellow-900' : 'text-slate-400'} border-r border-slate-100">
                            ${day === 1 ? month + '/' : ''}${day}
                        </div>`;
                    }).join('')}
                </div>
            `;
            root.appendChild(headerRow);
            
            // タスク行を作成
            tasks.forEach(task => {
                const start = task.start_date || task.due_date;
                const end = task.due_date || task.start_date;
                const startIdx = dates.indexOf(start);
                const endIdx = dates.indexOf(end);
                
                if (startIdx === -1 || endIdx === -1) return;
                
                const row = document.createElement('div');
                row.className = 'flex border-b border-slate-100 hover:bg-slate-50 cursor-pointer';
                row.onclick = () => editTask(task);
                
                const taskCell = document.createElement('div');
                taskCell.className = `w-40 shrink-0 px-3 py-3 text-[11px] font-bold text-slate-700 border-r border-slate-200 truncate ${task.status==='done'?'opacity-50 line-through':''} sticky left-0 z-10 bg-white`;
                taskCell.textContent = task.title;
                taskCell.style.borderLeft = `4px solid ${task.category_color || '#cbd5e1'}`;
                row.appendChild(taskCell);
                
                const ganttCell = document.createElement('div');
                ganttCell.className = 'flex relative';
                
                // 各日付のセルを作成（今日の列に黄色の背景）
                for (let i = 0; i < dates.length; i++) {
                    const dateCell = document.createElement('div');
                    const isToday = dates[i] === today;
                    dateCell.className = `w-12 shrink-0 border-r border-slate-100 ${isToday ? 'bg-yellow-50' : ''}`;
                    ganttCell.appendChild(dateCell);
                }
                
                // バー
                const barWidth = (endIdx - startIdx + 1) * 48; // 48px = w-12
                const bar = document.createElement('div');
                bar.className = 'absolute h-6 rounded flex items-center justify-center text-[9px] font-bold text-white px-2';
                bar.style.left = `${startIdx * 48 + 4}px`;
                bar.style.width = `${barWidth - 8}px`;
                bar.style.top = '6px';
                bar.style.backgroundColor = task.category_color || '#4f46e5';
                bar.style.opacity = task.status === 'done' ? '0.4' : '0.9';
                
                const duration = Math.ceil((new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24)) + 1;
                bar.textContent = `${duration}日`;
                
                ganttCell.appendChild(bar);
                
                row.appendChild(ganttCell);
                root.appendChild(row);
            });
        }

        function editTask(task) {
            $('task_id').value = task.id; $('task_title').value = task.title;
            $('task_priority').value = task.priority; $('task_start_date').value = task.start_date || '';
            $('task_due_date').value = task.due_date || ''; $('task_category_name').value = task.category_name || '';
            $('task_category_color').value = task.category_color || '#4f46e5';
            $('btnSubmit').innerText = '保存する'; $('btnCancelEdit').classList.remove('hidden'); $('btnDelete').classList.remove('hidden');
            $('mainScroll').scrollTo({ top: 0, behavior: 'smooth' });
            renderCurrentMode(); // 選択状態を反映するためリスト再描画
        }

        function resetForm() { $('task_id').value = ''; $('taskForm').reset(); $('btnSubmit').innerText = 'タスクを追加'; $('btnCancelEdit').classList.add('hidden'); $('btnDelete').classList.add('hidden'); renderCurrentMode(); }
        async function toggleStatus(id, checked) { await App.post('api/update.php', { id, status: checked ? 'done' : 'todo' }); location.reload(); }
        
        // Pull-to-Refresh 機能（モバイルのみ）
        (function() {
            if (window.innerWidth > 768) return; // デスクトップでは無効
            
            const mainScroll = $('mainScroll');
            const pullIndicator = $('pullToRefresh');
            const pullIcon = $('pullIcon');
            const pullText = $('pullText');
            
            let startY = 0;
            let currentY = 0;
            let pulling = false;
            const threshold = 80; // リロードするための閾値（ピクセル）
            
            mainScroll.addEventListener('touchstart', (e) => {
                if (mainScroll.scrollTop === 0) {
                    startY = e.touches[0].clientY;
                    pulling = true;
                }
            }, { passive: true });
            
            mainScroll.addEventListener('touchmove', (e) => {
                if (!pulling) return;
                
                currentY = e.touches[0].clientY;
                const pullDistance = currentY - startY;
                
                if (pullDistance > 0 && mainScroll.scrollTop === 0) {
                    const distance = Math.min(pullDistance, threshold * 1.5);
                    const progress = Math.min(distance / threshold, 1);
                    
                    // インジケーターの表示と位置
                    pullIndicator.style.transform = `translateY(${distance - 60}px)`;
                    pullIndicator.style.opacity = progress;
                    
                    // アイコンの回転
                    pullIcon.style.transform = `rotate(${progress * 180}deg)`;
                    
                    // テキストの変更
                    if (distance >= threshold) {
                        pullText.textContent = '離して更新';
                        pullIcon.classList.remove('fa-arrow-down');
                        pullIcon.classList.add('fa-rotate-right');
                    } else {
                        pullText.textContent = '引っ張って更新';
                        pullIcon.classList.remove('fa-rotate-right');
                        pullIcon.classList.add('fa-arrow-down');
                    }
                }
            }, { passive: true });
            
            mainScroll.addEventListener('touchend', (e) => {
                if (!pulling) return;
                
                const pullDistance = currentY - startY;
                
                if (pullDistance >= threshold && mainScroll.scrollTop === 0) {
                    // リロード実行
                    pullText.textContent = '更新中...';
                    pullIcon.classList.remove('fa-arrow-down', 'fa-rotate-right');
                    pullIcon.classList.add('fa-spinner', 'fa-spin');
                    
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                } else {
                    // リセット
                    pullIndicator.style.transform = 'translateY(-60px)';
                    pullIndicator.style.opacity = '0';
                    pullIcon.style.transform = 'rotate(0deg)';
                }
                
                pulling = false;
                startY = 0;
                currentY = 0;
            }, { passive: true });
        })();
    </script>
</body>
</html>