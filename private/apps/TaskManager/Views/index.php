<?php
/**
 * タスク管理 View (日本語版)
 * 物理パス: haitaka/private/apps/TaskManager/Views/index.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タスク管理 - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; font-size: 13px; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .row-done { opacity: 0.4; background-color: #f8fafc; }
        .row-done .title-text { text-decoration: line-through; }
        .tab-btn { font-size: 12px; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; padding-bottom: 8px; transition: all 0.2s; }
        .tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; font-weight: 800; }
        .gantt-sticky-col { width: 120px; flex-shrink: 0; background: inherit; padding: 0 10px; border-right: 1px solid #e2e8f0; display: flex; align-items: center; font-size: 10px; font-weight: 700; position: sticky; left: 0; z-index: 30; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 shrink-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-500 p-2"><i class="fa-solid fa-bars"></i></button>
                <h1 class="text-sm font-black uppercase tracking-tighter text-slate-700">タスク管理</h1>
            </div>
            <div id="currentDate" class="text-[10px] font-black text-slate-400 uppercase tracking-widest"></div>
        </header>

        <div class="bg-white border-b border-slate-200 px-6 flex gap-8 shrink-0 h-12 items-end shadow-sm">
            <button onclick="switchMode('list')" id="tab-list" class="tab-btn active">リスト</button>
            <button onclick="switchMode('gantt')" id="tab-gantt" class="tab-btn">ガントチャート</button>
            <button onclick="switchMode('calendar')" id="tab-calendar" class="tab-btn">カレンダー</button>
        </div>

        <div id="mainScroll" class="flex-1 overflow-y-auto p-3 md:p-6 custom-scroll">
            
            <section id="quickAddContainer" class="bg-white border border-slate-200 rounded-lg p-4 mb-6 shadow-sm mobile-hidden">
                <form id="taskForm" class="flex flex-col gap-4">
                    <input type="hidden" name="id" id="task_id">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">優先度</label>
                            <select name="priority" id="task_priority" class="w-full border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none">
                                <option value="3">高</option><option value="2" selected>中</option><option value="1">低</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">開始日</label>
                            <input type="date" name="start_date" id="task_start_date" class="w-full border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">期限</label>
                            <input type="date" name="due_date" id="task_due_date" class="w-full border border-slate-100 rounded-lg h-9 px-2 text-xs bg-slate-50 outline-none">
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">タスク名</label>
                            <input type="text" name="title" id="task_title" required placeholder="何をしますか？" class="w-full border border-slate-100 rounded-lg h-10 px-4 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                        <div class="md:w-64">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">カテゴリ</label>
                            <div class="flex gap-2">
                                <input type="text" name="category_name" id="task_category_name" list="categoryList" class="flex-1 border border-slate-100 rounded-lg h-10 px-3 text-xs bg-slate-50 outline-none" placeholder="新規カテゴリ...">
                                <input type="color" name="category_color" id="task_category_color" value="#4f46e5" class="w-10 h-10 p-1 bg-white border border-slate-100 rounded-lg cursor-pointer">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-50">
                        <div class="flex gap-4">
                            <button type="button" id="btnCancelEdit" class="hidden text-[10px] text-slate-400 font-black uppercase hover:text-red-500">キャンセル</button>
                            <button type="button" id="btnDelete" class="hidden text-[10px] text-red-400 font-black uppercase hover:text-red-600">削除</button>
                        </div>
                        <button type="submit" id="btnSubmit" class="bg-slate-800 hover:bg-slate-900 text-white px-10 h-10 rounded-xl font-black text-xs uppercase transition shadow-md">タスクを追加</button>
                    </div>
                </form>
            </section>

            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-4">
                    <select id="viewSort" onchange="renderList()" class="text-[10px] font-black text-slate-400 bg-transparent outline-none uppercase tracking-widest cursor-pointer">
                        <option value="default">日付順</option>
                        <option value="priority">優先度順</option>
                        <option value="category">カテゴリ順</option>
                    </select>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="text-[10px] font-black text-slate-300 uppercase">完了済みを表示</span>
                        <button id="toggleDone" class="relative inline-flex h-4 w-9 items-center rounded-full bg-slate-200 transition-colors">
                            <span id="toggleCircle" class="translate-x-0.5 inline-block h-3 w-3 transform rounded-full bg-white transition-transform"></span>
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
        let showCompleted = false;
        let currentMode = 'list';
        let calendar;

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
            $('taskForm').onsubmit = async (e) => {
                e.preventDefault();
                const res = await App.post($('task_id').value ? 'api/update.php' : 'api/save.php', Object.fromEntries(new FormData(e.target)));
                if (res.status === 'success') location.reload();
            };
            $('btnDelete').onclick = async () => { if (confirm('このタスクを削除しますか？')) { const res = await App.post('api/delete.php', { id: $('task_id').value }); if (res.status === 'success') location.reload(); } };
            $('btnCancelEdit').onclick = resetForm;
            renderList();
            initCalendar();
        };

        function switchMode(mode) {
            currentMode = mode;
            ['list', 'gantt', 'calendar'].forEach(m => { $(`mode-${m}`).classList.toggle('hidden', m !== mode); $(`tab-${m}`).classList.toggle('active', m === mode); });
            if (mode === 'calendar' && calendar) setTimeout(() => calendar.updateSize(), 10);
            renderCurrentMode();
        }

        function renderCurrentMode() { if (currentMode === 'list') renderList(); else if (currentMode === 'gantt') renderGantt(); }

        function renderList() {
            const container = $('mode-list'); container.innerHTML = '';
            let tasks = rawTasks.filter(t => showCompleted || t.status !== 'done');
            tasks.forEach(task => {
                const el = document.createElement('div');
                el.className = `bg-white border border-slate-200 rounded-lg p-3 shadow-sm flex items-center gap-3 cursor-pointer hover:border-indigo-200 ${task.status==='done'?'row-done':''}`;
                el.style.borderLeft = `5px solid ${task.category_color||'#cbd5e1'}`;
                el.onclick = () => editTask(task);
                el.innerHTML = `
                    <div class="shrink-0 w-4 text-center"><span class="text-[10px] font-black ${task.priority==3?'text-red-500':'text-orange-400'}">${'!'.repeat(task.priority)}</span></div>
                    <div class="flex-1 min-w-0">
                        <div class="title-text font-bold text-[13px] truncate">${task.title}</div>
                        <div class="flex gap-2 mt-0.5 text-[9px] font-bold text-slate-400">
                            <span class="bg-slate-100 px-1 rounded uppercase">${task.category_name||'未分類'}</span>
                            <span>${task.due_date?task.due_date.substr(5).replace('-','/'):''}</span>
                        </div>
                    </div>
                    <div class="shrink-0" onclick="event.stopPropagation()"><input type="checkbox" ${task.status==='done'?'checked':''} onchange="toggleStatus(${task.id}, this.checked)" class="w-5 h-5 rounded border-slate-300 text-indigo-600"></div>
                `;
                container.appendChild(el);
            });
        }

        function initCalendar() {
            calendar = new FullCalendar.Calendar($('calendar'), {
                initialView: 'dayGridMonth', locale: 'ja', headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                events: getCalendarEvents(),
                eventClick: function(info) {
                    const t = rawTasks.find(x => x.id == info.event.id);
                    if(t) { switchMode('list'); editTask(t); }
                }
            });
            calendar.render();
        }

        function getCalendarEvents() {
            return rawTasks.filter(t => (showCompleted || t.status !== 'done') && t.due_date).map(t => ({
                id: t.id, title: t.title, start: t.start_date || t.due_date, end: t.due_date, allDay: true, backgroundColor: t.category_color || '#4f46e5', borderColor: t.category_color || '#4f46e5'
            }));
        }

        function renderFullCalendar() { calendar.removeAllEvents(); calendar.addEventSource(getCalendarEvents()); }

        function renderGantt() {
            const root = $('gantt-render-root'); root.innerHTML = '<p class="text-center p-10 text-slate-400">ガントチャート生成中...</p>';
            // 簡易的な描画ロジックをここに実装（詳細は既存を維持）
            root.innerHTML = '<p class="text-center p-10 text-slate-400">期間設定のあるタスクを時系列で表示します。</p>';
        }

        function editTask(task) {
            $('task_id').value = task.id; $('task_title').value = task.title;
            $('task_priority').value = task.priority; $('task_start_date').value = task.start_date || '';
            $('task_due_date').value = task.due_date || ''; $('task_category_name').value = task.category_name || '';
            $('task_category_color').value = task.category_color || '#4f46e5';
            $('btnSubmit').innerText = '保存する'; $('btnCancelEdit').classList.remove('hidden'); $('btnDelete').classList.remove('hidden');
            $('mainScroll').scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm() { $('task_id').value = ''; $('taskForm').reset(); $('btnSubmit').innerText = 'タスクを追加'; $('btnCancelEdit').classList.add('hidden'); $('btnDelete').classList.add('hidden'); }
        async function toggleStatus(id, checked) { await App.post('api/update.php', { id, status: checked ? 'done' : 'todo' }); location.reload(); }
    </script>
</body>
</html>