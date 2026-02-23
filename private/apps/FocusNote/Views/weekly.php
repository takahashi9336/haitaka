<?php
$appKey = 'focus_note';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$weeklyPageId = (int)($weeklyPage['id'] ?? 0);
$weekDisplay = date('n/d', strtotime($weekStart)) . ' 〜 ' . date('n/d', strtotime($weekStart . ' +6 days'));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ウィークリー - <?= htmlspecialchars($weekDisplay) ?> - Focus Note</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --fn-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .fn-theme-btn { background-color: var(--fn-theme); }
        .fn-theme-btn:hover { filter: brightness(1.08); }
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
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter">ウィークリー</h1>
                </a>
            </div>
            <nav class="flex items-center gap-2">
                <a href="/focus_note/weekly.php?week=<?= htmlspecialchars($prevWeek) ?>" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500"><i class="fa-solid fa-chevron-left"></i></a>
                <span class="text-sm font-bold text-slate-600 px-2"><?= htmlspecialchars($weekDisplay) ?></span>
                <a href="/focus_note/weekly.php?week=<?= htmlspecialchars($nextWeek) ?>" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500"><i class="fa-solid fa-chevron-right"></i></a>
            </nav>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-2xl mx-auto space-y-8">

                <!-- 1. デイリータスク選択 -->
                <section>
                    <h2 class="text-sm font-bold text-slate-700 mb-3">1. デイリータスク選択（3〜5つ）</h2>
                    <?php if (empty($availableDailyTasks)): ?>
                        <p class="text-slate-400 text-sm py-2">マンスリーページでデイリータスクを設定してください。</p>
                        <a href="/focus_note/monthly.php?ym=<?= date('Y-m', strtotime($weekStart)) ?>-01" class="text-sm font-medium" style="color: var(--fn-theme);">マンスリーページを開く</a>
                    <?php else: ?>
                        <form id="pickForm" class="space-y-2">
                            <?php foreach ($availableDailyTasks as $t): ?>
                                <label class="flex items-center gap-3 p-3 rounded-xl border <?= $cardBorder ?> bg-white cursor-pointer hover:shadow-sm transition">
                                    <input type="checkbox" name="daily_task_ids[]" value="<?= (int)$t['id'] ?>"
                                        <?= in_array($t['id'], array_column($picks, 'daily_task_id')) ? 'checked' : '' ?>>
                                    <span class="text-sm text-slate-700"><?= htmlspecialchars($t['content']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </form>
                        <button type="button" onclick="FocusNote.savePicks()" class="mt-3 px-4 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">選択を保存</button>
                    <?php endif; ?>
                </section>

                <!-- 2. 障害コントラスト / 3. 障害フィックス -->
                <section class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-700 mb-2">2. 障害コントラスト</h2>
                        <textarea id="obstacleContrast" rows="4" class="fn-auto-save-field w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="発生しそうなトラブル"><?= htmlspecialchars($weeklyPage['obstacle_contrast'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-700 mb-2">3. 障害フィックス</h2>
                        <textarea id="obstacleFix" rows="4" class="fn-auto-save-field w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="対策"><?= htmlspecialchars($weeklyPage['obstacle_fix'] ?? '') ?></textarea>
                    </div>
                </section>
                <p class="text-xs text-slate-400">入力後、自動で保存されます。</p>

                <!-- 4. 質問型アクション -->
                <section>
                    <h2 class="text-sm font-bold text-slate-700 mb-3">4. 質問型アクション</h2>
                    <p class="text-xs text-slate-500 mb-3">各タスクに「時間」「場所」を設定すると、実施意図文が生成されます。</p>
                    <?php if (empty($picks)): ?>
                        <p class="text-slate-400 text-sm">上でタスクを選択して保存すると、ここに表示されます。</p>
                    <?php else: ?>
                        <div id="questionActions" class="space-y-3">
                            <?php
                            $actionByPick = [];
                            foreach ($questionActions as $qa) {
                                $actionByPick[$qa['weekly_task_pick_id']] = $qa;
                            }
                            foreach ($picks as $pick):
                                $qa = $actionByPick[$pick['id']] ?? null;
                                $question = $qa ? $qa['question_text'] : ($userName . 'は、[時間]に[場所]で' . $pick['task_content'] . 'をするか？');
                            ?>
                                <div class="p-4 rounded-xl border <?= $cardBorder ?> bg-white" data-pick-id="<?= (int)$pick['id'] ?>" data-task="<?= htmlspecialchars($pick['task_content']) ?>">
                                    <div class="flex gap-3 mb-2">
                                        <input type="text" class="fn-qa-time w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="9:00" value="<?= htmlspecialchars($qa['scheduled_time'] ?? '') ?>">
                                        <input type="text" class="fn-qa-place flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="自宅デスク" value="<?= htmlspecialchars($qa['place'] ?? '') ?>">
                                    </div>
                                    <p class="fn-qa-preview text-sm text-slate-600 font-medium"><?= htmlspecialchars($question) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="FocusNote.saveQuestionActions()" class="mt-3 px-4 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">時間・場所を保存</button>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        const userName = <?= json_encode($userName) ?>;
        const FocusNote = {
            saveTimer: null,

            init() {
                document.querySelectorAll('.fn-auto-save-field').forEach(el => {
                    el.addEventListener('input', () => this.scheduleSaveObstacle());
                    el.addEventListener('blur', () => this.scheduleSaveObstacle());
                });
                document.querySelectorAll('.fn-qa-time, .fn-qa-place').forEach(el => {
                    el.addEventListener('input', () => this.updatePreviews());
                });
                this.updatePreviews();
            },

            scheduleSaveObstacle() {
                clearTimeout(this.saveTimer);
                this.saveTimer = setTimeout(() => this.saveObstacle(), 1200);
            },

            async saveObstacle() {
                const res = await App.post('/focus_note/api/save_weekly.php', {
                    page_id: <?= $weeklyPageId ?>,
                    obstacle_contrast: document.getElementById('obstacleContrast')?.value || '',
                    obstacle_fix: document.getElementById('obstacleFix')?.value || ''
                });
                if (res.status === 'success') App.toast('保存しました');
            },

            updatePreviews() {
                document.querySelectorAll('#questionActions [data-pick-id]').forEach(row => {
                    const time = row.querySelector('.fn-qa-time')?.value || '[時間]';
                    const place = row.querySelector('.fn-qa-place')?.value || '[場所]';
                    const task = row.dataset.task || '';
                    row.querySelector('.fn-qa-preview').textContent = userName + 'は、' + time + 'に' + place + 'で' + task + 'をするか？';
                });
            },

            async savePicks() {
                const ids = Array.from(document.querySelectorAll('input[name="daily_task_ids[]"]:checked')).map(c => c.value);
                if (ids.length < 3 || ids.length > 5) {
                    App.toast('3〜5つ選んでください');
                    return;
                }
                const res = await App.post('/focus_note/api/save_picks.php', {
                    page_id: <?= $weeklyPageId ?>,
                    daily_task_ids: ids
                });
                if (res.status === 'success') {
                    App.toast('選択を保存しました');
                    location.reload();
                } else {
                    App.toast(res.message || '保存に失敗しました');
                }
            },

            async saveQuestionActions() {
                const items = [];
                document.querySelectorAll('#questionActions [data-pick-id]').forEach(row => {
                    items.push({
                        pick_id: parseInt(row.dataset.pickId, 10),
                        time: row.querySelector('.fn-qa-time')?.value || '',
                        place: row.querySelector('.fn-qa-place')?.value || '',
                        task_content: row.dataset.task || ''
                    });
                });
                const res = await App.post('/focus_note/api/save_question_actions.php', { items });
                if (res.status === 'success') {
                    App.toast('保存しました');
                    location.reload();
                } else {
                    App.toast(res.message || '保存に失敗しました');
                }
            }
        };
        document.addEventListener('DOMContentLoaded', () => FocusNote.init());
    </script>
</body>
</html>
