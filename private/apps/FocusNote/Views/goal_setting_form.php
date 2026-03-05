<?php
/**
 * 目標・行動目標設定フォーム（WOOP / MAC / If-Then）
 */
$appKey = 'focus_note';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$goal = $goal ?? null;
$actionGoals = $actionGoals ?? [];
$ifThenRules = $ifThenRules ?? [];

$goalId = $goal ? (int) $goal['id'] : 0;

// 空の行を1つずつ用意（新規追加用）
$actionGoals = array_values($actionGoals);
if (empty($actionGoals)) {
    $actionGoals = [['id' => 0, 'content' => '', 'measurement' => '', 'is_process_goal' => 1]];
}
$ifThenRules = array_values($ifThenRules);
if (empty($ifThenRules)) {
    $ifThenRules = [['id' => 0, 'if_condition' => '', 'then_action' => '']];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>目標・行動目標を設定 - Focus Note - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --fn-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .fn-theme-btn { background-color: var(--fn-theme); }
        .fn-theme-btn:hover { filter: brightness(1.08); }
        .fn-theme-link { color: var(--fn-theme); }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/focus_note/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-bullseye text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">目標・行動目標を設定</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-3xl mx-auto space-y-8">
                <p class="text-sm text-slate-500">
                    <a href="/focus_note/goal_setting.php" class="fn-theme-link hover:underline">目標設定の考え方</a>に基づいて、WOOP・行動目標・If-Thenルールを設定します。
                </p>

                <!-- 1. WOOP -->
                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">1</span>
                        WOOP（願望・成果・障害・計画）
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Wish（願望）</label>
                            <textarea id="woopWish" rows="2" class="w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="仕事で達成したいこと"><?= htmlspecialchars($goal['wish'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Outcome（成果イメージ）</label>
                            <textarea id="woopOutcome" rows="2" class="w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="達成時の最高のメリット"><?= htmlspecialchars($goal['outcome'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Obstacle（障害）</label>
                            <textarea id="woopObstacle" rows="2" class="w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="達成を阻む内面の障害"><?= htmlspecialchars($goal['obstacle'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Plan（計画）</label>
                            <textarea id="woopPlan" rows="2" class="w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="障害が起きた時の対策（If-Then等）"><?= htmlspecialchars($goal['plan'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Being（ありたい姿）</label>
                            <textarea id="woopBeing" rows="2" class="w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]" placeholder="抽象度の高い目的"><?= htmlspecialchars($goal['being'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="button" onclick="GoalForm.saveWoop()" class="mt-4 px-4 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">WOOPを保存</button>
                </section>

                <!-- 2. 行動目標（MAC） -->
                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">2</span>
                        行動目標（MAC原則）
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">Measurable（測定可能）・Actionable（行動可能）・Competent（能力向上）に沿った行動目標を設定します。</p>
                    <div id="actionGoalsContainer" class="space-y-3">
                        <?php foreach ($actionGoals as $i => $ag): ?>
                        <div class="fn-action-row flex flex-col sm:flex-row gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                            <input type="text" class="fn-action-content flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="行動内容（例：毎日3件新規に電話する）" value="<?= htmlspecialchars($ag['content'] ?? '') ?>">
                            <input type="text" class="fn-action-measurement w-full sm:w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="測定方法" value="<?= htmlspecialchars($ag['measurement'] ?? '') ?>">
                            <label class="flex items-center gap-2 shrink-0 text-sm">
                                <input type="checkbox" class="fn-action-process rounded" <?= !empty($ag['is_process_goal']) ? 'checked' : '' ?>>
                                <span>プロセス目標</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="GoalForm.addActionRow()" class="mt-2 text-sm fn-theme-link font-medium hover:underline">+ 行を追加</button>
                    <button type="button" onclick="GoalForm.saveActions()" class="mt-4 ml-4 px-4 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">行動目標を保存</button>
                </section>

                <!-- 3. If-Then ルール -->
                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">3</span>
                        If-Then ルール
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">「もしAならBする」の形式でルール化します。</p>
                    <div id="ifThenContainer" class="space-y-3">
                        <?php foreach ($ifThenRules as $r): ?>
                        <div class="fn-ifthen-row flex flex-col sm:flex-row gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                            <input type="text" class="fn-ifthen-if flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="If: 条件（例：メールを開いたら）" value="<?= htmlspecialchars($r['if_condition'] ?? '') ?>">
                            <input type="text" class="fn-ifthen-then flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Then: 行動（例：その場で返信する）" value="<?= htmlspecialchars($r['then_action'] ?? '') ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="GoalForm.addIfThenRow()" class="mt-2 text-sm fn-theme-link font-medium hover:underline">+ 行を追加</button>
                    <button type="button" onclick="GoalForm.saveIfThen()" class="mt-4 ml-4 px-4 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">If-Thenを保存</button>
                </section>

                <?php if ($goalId > 0): ?>
                <section class="pt-4 border-t border-slate-100">
                    <button type="button" onclick="GoalForm.deleteGoal()" class="px-4 py-2 rounded-xl border border-red-200 text-red-600 text-sm font-bold hover:bg-red-50 transition">目標を削除</button>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        const goalId = <?= $goalId ?>;
        const GoalForm = {
            async saveWoop() {
                const res = await App.post('/focus_note/api/goal_save.php', {
                    goal_id: goalId,
                    wish: document.getElementById('woopWish')?.value || '',
                    outcome: document.getElementById('woopOutcome')?.value || '',
                    obstacle: document.getElementById('woopObstacle')?.value || '',
                    plan: document.getElementById('woopPlan')?.value || '',
                    being: document.getElementById('woopBeing')?.value || ''
                });
                if (res.status === 'success') {
                    App.toast('WOOPを保存しました');
                    if (res.goal_id && goalId === 0) location.reload();
                } else {
                    App.toast(res.message || '保存に失敗しました');
                }
            },
            collectActions() {
                const items = [];
                document.querySelectorAll('#actionGoalsContainer .fn-action-row').forEach(row => {
                    const content = row.querySelector('.fn-action-content')?.value?.trim() || '';
                    if (!content) return;
                    items.push({
                        content: content,
                        measurement: row.querySelector('.fn-action-measurement')?.value?.trim() || '',
                        is_process_goal: row.querySelector('.fn-action-process')?.checked ? 1 : 0
                    });
                });
                return items;
            },
            async saveActions() {
                if (goalId <= 0) {
                    App.toast('先にWOOPを保存してください');
                    return;
                }
                const items = this.collectActions();
                const res = await App.post('/focus_note/api/action_goals_save.php', { goal_id: goalId, items });
                if (res.status === 'success') {
                    App.toast('行動目標を保存しました');
                } else {
                    App.toast(res.message || '保存に失敗しました');
                }
            },
            addActionRow() {
                const tpl = document.querySelector('#actionGoalsContainer .fn-action-row');
                if (!tpl) return;
                const div = document.createElement('div');
                div.className = 'fn-action-row flex flex-col sm:flex-row gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50';
                div.innerHTML = '<input type="text" class="fn-action-content flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="行動内容">' +
                    '<input type="text" class="fn-action-measurement w-full sm:w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="測定方法">' +
                    '<label class="flex items-center gap-2 shrink-0 text-sm"><input type="checkbox" class="fn-action-process rounded" checked><span>プロセス目標</span></label>';
                document.getElementById('actionGoalsContainer').appendChild(div);
            },
            collectIfThen() {
                const items = [];
                document.querySelectorAll('#ifThenContainer .fn-ifthen-row').forEach(row => {
                    const ifCond = row.querySelector('.fn-ifthen-if')?.value?.trim() || '';
                    const thenAct = row.querySelector('.fn-ifthen-then')?.value?.trim() || '';
                    if (!ifCond && !thenAct) return;
                    items.push({ if_condition: ifCond, then_action: thenAct });
                });
                return items;
            },
            async saveIfThen() {
                if (goalId <= 0) {
                    App.toast('先にWOOPを保存してください');
                    return;
                }
                const items = this.collectIfThen();
                const res = await App.post('/focus_note/api/if_then_rules_save.php', { goal_id: goalId, items });
                if (res.status === 'success') {
                    App.toast('If-Thenを保存しました');
                } else {
                    App.toast(res.message || '保存に失敗しました');
                }
            },
            addIfThenRow() {
                const div = document.createElement('div');
                div.className = 'fn-ifthen-row flex flex-col sm:flex-row gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50';
                div.innerHTML = '<input type="text" class="fn-ifthen-if flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="If: 条件">' +
                    '<input type="text" class="fn-ifthen-then flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Then: 行動">';
                document.getElementById('ifThenContainer').appendChild(div);
            },
            async deleteGoal() {
                if (!confirm('この目標を削除しますか？紐づく行動目標・If-Thenルールも削除されます。')) return;
                const res = await App.post('/focus_note/api/goal_delete.php', { goal_id: goalId });
                if (res.status === 'success') {
                    App.toast('削除しました');
                    location.href = '/focus_note/goal_setting_form.php';
                } else {
                    App.toast(res.message || '削除に失敗しました');
                }
            }
        };
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
