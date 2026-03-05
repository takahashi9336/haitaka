<?php
$appKey = 'focus_note';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$userName = $user['id_name'] ?? '私';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Focus Note - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --fn-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .fn-theme-btn { background-color: var(--fn-theme); }
        .fn-theme-btn:hover { filter: brightness(1.08); }
        .fn-theme-link { color: var(--fn-theme); }
        .fn-action-check:checked + .fn-action-label { text-decoration: line-through; color: #94a3b8; }
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
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-bolt text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">Focus Note</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-2xl mx-auto">

                <!-- 今日のアクション（最上部） -->
                <section class="mb-8">
                    <h2 class="text-sm font-bold text-slate-500 tracking-wider mb-4">今週のアクション</h2>
                    <?php if (empty($todayActions)): ?>
                        <p class="text-slate-400 text-sm py-4">ウィークリーページでタスクを選択すると、ここに表示されます。</p>
                        <a href="<?= htmlspecialchars($weeklyLink) ?>" class="inline-flex items-center gap-2 text-sm fn-theme-link font-medium hover:underline">
                            <i class="fa-solid fa-calendar-week"></i> ウィークリーページを開く
                        </a>
                    <?php else: ?>
                        <ul class="space-y-2">
                            <?php foreach ($todayActions as $action): ?>
                                <li class="flex items-start gap-3 p-3 rounded-xl bg-white border <?= $cardBorder ?> hover:shadow-sm transition">
                                    <label class="flex items-start gap-3 cursor-pointer flex-1 min-w-0">
                                        <input type="checkbox" class="fn-action-check mt-1.5 w-5 h-5 rounded border-slate-300 fn-theme-focus"
                                            data-id="<?= (int)$action['id'] ?>"
                                            <?= !empty($action['done']) ? 'checked' : '' ?>
                                            onchange="FocusNote.toggleDone(<?= (int)$action['id'] ?>, this)">
                                        <span class="fn-action-label text-sm <?= !empty($action['done']) ? 'line-through text-slate-400' : 'text-slate-700' ?>">
                                            <?= htmlspecialchars($action['question_text'] ?: $action['task_content']) ?>
                                        </span>
                                    </label>
                                    <?php if (!empty($action['done']) && !empty($action['actual_duration_min'])): ?>
                                        <span class="text-xs text-slate-400 shrink-0"><?= (int)$action['actual_duration_min'] ?>分</span>
                                    <?php elseif (empty($action['done'])): ?>
                                        <button type="button" onclick="FocusNote.showDuration(<?= (int)$action['id'] ?>)" class="text-xs text-slate-400 hover:fn-theme-link shrink-0" title="所要時間を記録">時間</button>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="text-xs text-slate-400 mt-3">チェックで完了。所要時間は任意で記録できます。</p>
                    <?php endif; ?>
                </section>

                <!-- ナビゲーション（控えめ） -->
                <nav class="flex flex-wrap gap-3">
                    <a href="<?= htmlspecialchars($monthlyLink) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border <?= $cardBorder ?> bg-white hover:shadow-sm text-sm font-medium text-slate-600 transition">
                        <i class="fa-solid fa-calendar-alt fn-theme-link"></i> マンスリー（<?= date('Y年n月', strtotime($yearMonth)) ?>）
                    </a>
                    <a href="<?= htmlspecialchars($weeklyLink) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border <?= $cardBorder ?> bg-white hover:shadow-sm text-sm font-medium text-slate-600 transition">
                        <i class="fa-solid fa-calendar-week fn-theme-link"></i> ウィークリー（<?= date('n/d', strtotime($weekStart)) ?>〜）
                    </a>
                    <a href="/focus_note/goal_setting.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border <?= $cardBorder ?> bg-white hover:shadow-sm text-sm font-medium text-slate-600 transition">
                        <i class="fa-solid fa-bullseye fn-theme-link"></i> 目標設定の考え方
                    </a>
                    <a href="/focus_note/goal_setting_form.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border <?= $cardBorder ?> bg-white hover:shadow-sm text-sm font-medium text-slate-600 transition">
                        <i class="fa-solid fa-pen fn-theme-link"></i> 目標・行動目標を設定
                    </a>
                </nav>
            </div>
        </div>
    </main>

    <!-- 所要時間入力モーダル -->
    <div id="durationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4">
            <p class="text-sm font-bold text-slate-700 mb-3">所要時間（分）</p>
            <input type="number" id="durationInput" min="1" max="480" placeholder="例: 30" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-lg">
            <div class="flex gap-3 mt-4">
                <button type="button" onclick="FocusNote.closeDuration()" class="flex-1 py-2 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium">キャンセル</button>
                <button type="button" onclick="FocusNote.saveDuration()" class="flex-1 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">記録</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js"></script>
    <script>
        const FocusNote = {
            _durationActionId: null,
            _durationCheckbox: null,

            async toggleDone(id, checkbox) {
                const res = await App.post('/focus_note/api/toggle_done.php', { id });
                if (res.status === 'success') {
                    App.toast(res.done ? '完了にしました' : '未完了に戻しました');
                    location.reload();
                }
            },

            showDuration(id) {
                this._durationActionId = id;
                document.getElementById('durationModal').style.display = 'flex';
                document.getElementById('durationInput').value = '';
                document.getElementById('durationInput').focus();
            },

            closeDuration() {
                document.getElementById('durationModal').style.display = 'none';
                this._durationActionId = null;
            },

            async saveDuration() {
                const input = document.getElementById('durationInput');
                const min = parseInt(input.value, 10);
                if (!this._durationActionId || !min || min < 1) {
                    App.toast('1分以上を入力してください');
                    return;
                }
                const res = await App.post('/focus_note/api/save_duration.php', {
                    id: this._durationActionId,
                    duration_min: min
                });
                if (res.status === 'success') {
                    this.closeDuration();
                    location.reload();
                } else {
                    App.toast(res.message || '保存に失敗しました');
                }
            }
        };
    </script>
</body>
</html>
