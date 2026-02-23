<?php
$appKey = 'focus_note';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$pageId = (int)($page['id'] ?? 0);
$ymDisplay = date('Y年n月', strtotime($ym));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マンスリー - <?= htmlspecialchars($ymDisplay) ?> - Focus Note</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --fn-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .fn-theme-btn { background-color: var(--fn-theme); }
        .fn-theme-btn:hover { filter: brightness(1.08); }
        .fn-auto-save { font-size: 11px; color: #94a3b8; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) { .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; } .sidebar.mobile-open { transform: translateX(0); } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/focus_note/" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-bolt text-sm"></i>
                    </div>
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter">マンスリー</h1>
                </a>
            </div>
            <nav class="flex items-center gap-2">
                <a href="/focus_note/monthly.php?ym=<?= htmlspecialchars($prevMonth) ?>" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500"><i class="fa-solid fa-chevron-left"></i></a>
                <span class="text-sm font-bold text-slate-600 px-2"><?= htmlspecialchars($ymDisplay) ?></span>
                <a href="/focus_note/monthly.php?ym=<?= htmlspecialchars($nextMonth) ?>" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500"><i class="fa-solid fa-chevron-right"></i></a>
            </nav>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-2xl mx-auto">
                <p class="text-xs text-slate-400 mb-6">入力後、自動で保存されます。</p>

                <form id="monthlyForm" class="space-y-6">
                    <input type="hidden" name="page_id" value="<?= $pageId ?>">

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">1. ターゲット</label>
                        <p class="text-xs text-slate-500 mb-2">集中力が続かない作業のなかで最も重要なものを1つ</p>
                        <textarea name="target" rows="2" class="fn-auto-save-field w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="例: ブログ記事を週2本書く"><?= htmlspecialchars($page['target'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">2. 重要度チェック</label>
                        <p class="text-xs text-slate-500 mb-2">達成しなければならない理由で最も大事なもの</p>
                        <textarea name="importance_check" rows="2" class="fn-auto-save-field w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="例: 収入に直結する"><?= htmlspecialchars($page['importance_check'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">3. 具象イメージング</label>
                        <p class="text-xs text-slate-500 mb-2">ターゲットを具体的な映像イメージに変換</p>
                        <textarea name="concrete_imaging" rows="3" class="fn-auto-save-field w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="例: 画面に向かいキーボードを叩いている自分"><?= htmlspecialchars($page['concrete_imaging'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">4. リバースプランニング</label>
                        <p class="text-xs text-slate-500 mb-2">達成した未来からさかのぼり、短期目標を決める</p>
                        <textarea name="reverse_planning" rows="4" class="fn-auto-save-field w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="例: 月末に8本完成 → 週2本 → 今週は2本"><?= htmlspecialchars($page['reverse_planning'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">5. デイリータスク設定</label>
                        <p class="text-xs text-slate-500 mb-2">毎日やるべきタスクを1行ずつ</p>
                        <div id="dailyTasksContainer" class="space-y-2">
                            <?php foreach ($dailyTasks as $i => $t): ?>
                                <input type="text" name="daily_tasks[]" value="<?= htmlspecialchars($t['content']) ?>" class="fn-auto-save-field w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="タスク <?= $i + 1 ?>">
                            <?php endforeach; ?>
                            <?php if (empty($dailyTasks)): ?>
                                <input type="text" name="daily_tasks[]" class="fn-auto-save-field w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="タスク 1">
                                <input type="text" name="daily_tasks[]" class="fn-auto-save-field w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="タスク 2">
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="FocusNote.addTaskRow()" class="mt-2 text-sm text-slate-500 hover:opacity-80 font-medium" style="--fn-hover: var(--fn-theme);"><i class="fa-solid fa-plus mr-1"></i> 行を追加</button>
                    </div>
                </form>
                <p id="saveStatus" class="fn-auto-save mt-4"></p>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        const FocusNote = {
            saveTimer: null,

            init() {
                document.querySelectorAll('.fn-auto-save-field').forEach(el => {
                    el.addEventListener('input', () => this.scheduleSave());
                    el.addEventListener('blur', () => this.scheduleSave());
                });
            },

            scheduleSave() {
                clearTimeout(this.saveTimer);
                this.saveTimer = setTimeout(() => this.save(), 1500);
            },

            async save() {
                const form = document.getElementById('monthlyForm');
                const fd = new FormData(form);
                const data = {
                    page_id: parseInt(fd.get('page_id') || 0, 10),
                    target: fd.get('target') || '',
                    importance_check: fd.get('importance_check') || '',
                    concrete_imaging: fd.get('concrete_imaging') || '',
                    reverse_planning: fd.get('reverse_planning') || '',
                    daily_tasks: fd.getAll('daily_tasks[]').filter(s => s.trim() !== '')
                };
                const res = await App.post('/focus_note/api/save_monthly.php', data);
                const status = document.getElementById('saveStatus');
                if (res.status === 'success') {
                    status.textContent = '保存しました';
                    status.classList.remove('text-amber-500');
                    status.classList.add('text-emerald-500');
                } else {
                    status.textContent = res.message || '保存に失敗しました';
                    status.classList.add('text-amber-500');
                }
            },

            addTaskRow() {
                const container = document.getElementById('dailyTasksContainer');
                const n = container.querySelectorAll('input').length + 1;
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'daily_tasks[]';
                input.className = 'fn-auto-save-field w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]';
                input.placeholder = 'タスク ' + n;
                input.addEventListener('input', () => FocusNote.scheduleSave());
                input.addEventListener('blur', () => FocusNote.scheduleSave());
                container.appendChild(input);
            }
        };
        document.addEventListener('DOMContentLoaded', () => FocusNote.init());
    </script>
</body>
</html>
