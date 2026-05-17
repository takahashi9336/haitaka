<?php
/**
 * Health: トレーニング実施履歴 View
 */
$appKey = 'health_training_history';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング実施履歴 - Health</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        input:focus, select:focus { outline: none; }
        details.hiit-log summary { cursor: pointer; list-style: none; }
        details.hiit-log summary::-webkit-details-marker { display: none; }
    </style>
    <?php if ($isThemeHex): ?>
    <style>
        input[type="number"], input[type="date"], select { accent-color: var(--health-theme); }
    </style>
    <?php endif; ?>
    <style>:root { --health-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/health/training_menu.php" class="text-slate-400 hover:text-slate-600 transition shrink-0"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-clipboard-check text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">トレーニング実施履歴</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-10">
            <div class="max-w-4xl mx-auto space-y-8">
                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h2 class="text-sm font-black text-slate-600 mb-4">HIIT実施を後から追加</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="flex flex-col md:col-span-2">
                            <label class="text-xs font-black text-slate-500 mb-1 ml-1">実施日</label>
                            <input type="date" id="logDate" class="border p-2 rounded-md border-slate-300 h-[42px] w-full">
                        </div>
                        <div class="flex flex-col justify-end">
                            <button id="addLogBtn" class="<?= $btnBgClass ?> text-white p-2 rounded-md transition h-[42px] font-bold w-full"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                                HIITを追加
                            </button>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-3">直近3ヶ月分を表示。追加時は現在の登録メニューがスナップショットとして保存されます。</p>
                </div>

                <div class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden overflow-x-auto">
                    <h2 class="text-sm font-black text-slate-600 p-4 pb-0">記録一覧</h2>
                    <table class="w-full text-left border-collapse mt-2 min-w-[560px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="p-4 text-sm font-black text-slate-600 w-32">実施日</th>
                                <th class="p-4 text-sm font-black text-slate-600">内容</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-20">種目数</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-28">合計時間</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-16 text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody id="logList" class="divide-y divide-slate-200"></tbody>
                    </table>
                    <div id="logEmptyMsg" class="p-8 text-center text-slate-400 hidden">履歴がありません</div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        let logs = [];

        function esc(s) {
            return (s || '').toString()
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        }

        function splitDurationSec(total) {
            const sec = Math.max(0, parseInt(total, 10) || 0);
            return { min: Math.floor(sec / 60), sec: sec % 60 };
        }

        function formatDurationLabel(sec) {
            const d = splitDurationSec(sec);
            if (d.min > 0 && d.sec > 0) return d.min + '\u5206' + d.sec + '\u79d2';
            if (d.min > 0) return d.min + '\u5206';
            return d.sec + '\u79d2';
        }

        function todayStr() {
            const t = new Date();
            const y = t.getFullYear();
            const m = String(t.getMonth() + 1).padStart(2, '0');
            const d = String(t.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        function parseSnapshot(raw) {
            if (!raw) return [];
            if (Array.isArray(raw)) return raw;
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function renderSnapshotTable(snapshot) {
            if (!snapshot.length) {
                return '<p class="text-xs text-slate-400 p-2">\u7a2e\u76ee\u306a\u3057</p>';
            }
            const rows = snapshot.map(item => {
                const name = esc(item.name);
                const reps = parseInt(item.reps, 10) || 0;
                const dur = esc(formatDurationLabel(item.duration_sec ?? 0));
                return '<tr class="border-b border-slate-100 last:border-none">' +
                    '<td class="py-1.5 pr-3 text-xs font-bold text-slate-700">' + name + '</td>' +
                    '<td class="py-1.5 pr-3 text-xs text-slate-600">' + reps + '</td>' +
                    '<td class="py-1.5 text-xs text-slate-600 whitespace-nowrap">' + dur + '</td>' +
                    '</tr>';
            }).join('');
            return '<table class="w-full mt-2 text-left">' +
                '<thead><tr class="text-[10px] text-slate-400">' +
                '<th class="pb-1 font-black">\u30e1\u30cb\u30e5\u30fc</th>' +
                '<th class="pb-1 font-black w-16">\u56de\u6570</th>' +
                '<th class="pb-1 font-black w-20">\u6642\u9593</th>' +
                '</tr></thead><tbody>' + rows + '</tbody></table>';
        }

        async function loadLogs() {
            const res = await App.post('/health/api/training_log_list.php', {});
            if (res && res.status === 'success') {
                logs = res.items || [];
                renderLogs();
            } else {
                App.toast((res && res.message) ? res.message : '\u8aad\u307f\u8fbc\u307f\u306b\u5931\u6557\u3057\u307e\u3057\u305f');
            }
        }

        async function addLog() {
            const performedAt = document.getElementById('logDate').value;
            if (!performedAt) {
                App.toast('\u5b9f\u65bd\u65e5\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044');
                return;
            }
            const res = await App.post('/health/api/training_log_create_hiit.php', { performed_at: performedAt });
            if (res && res.status === 'success') {
                App.toast('HIIT\u3092\u8ffd\u52a0\u3057\u307e\u3057\u305f');
                await loadLogs();
            } else {
                App.toast((res && res.message) ? res.message : '\u8ffd\u52a0\u306b\u5931\u6557\u3057\u307e\u3057\u305f');
            }
        }

        async function deleteLog(id) {
            const ok = confirm('\u3053\u306e\u5c65\u6b74\u3092\u524a\u9664\u3057\u3066\u3088\u308d\u3057\u3044\u3067\u3059\u304b\uff1f');
            if (!ok) return;
            const res = await App.post('/health/api/training_log_delete.php', { id });
            if (res && res.status === 'success') {
                await loadLogs();
            } else {
                App.toast((res && res.message) ? res.message : '\u524a\u9664\u306b\u5931\u6557\u3057\u307e\u3057\u305f');
            }
        }

        function renderLogs() {
            const list = document.getElementById('logList');
            const emptyMsg = document.getElementById('logEmptyMsg');

            if (!logs || logs.length === 0) {
                list.innerHTML = '';
                emptyMsg.classList.remove('hidden');
                return;
            }
            emptyMsg.classList.add('hidden');

            list.innerHTML = logs.map(row => {
                const date = esc(row.performed_at);
                const kind = row.log_kind || 'exercise';
                const reps = parseInt(row.reps, 10) || 0;
                const durLabel = esc(formatDurationLabel(row.duration_sec ?? 0));
                const delBtn = '<button type="button" data-del-log-id="' + row.id + '" class="text-slate-300 hover:text-red-500 transition-colors">' +
                    '<i class="fa-solid fa-trash-can"></i></button>';

                if (kind === 'hiit') {
                    const snapshot = parseSnapshot(row.menu_snapshot);
                    const inner = renderSnapshotTable(snapshot);
                    return '<tr class="border-b border-slate-100">' +
                        '<td class="p-4 text-sm font-bold text-slate-700 whitespace-nowrap align-top">' + date + '</td>' +
                        '<td class="p-4 align-top" colspan="3">' +
                        '<details class="hiit-log">' +
                        '<summary class="text-sm font-black text-slate-800 flex items-center gap-2 flex-wrap">' +
                        '<span class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-xs font-black">HIIT</span>' +
                        '<span class="text-slate-500 font-medium text-xs">' + reps + '\u7a2e\u76ee \u00b7 ' + durLabel + '</span>' +
                        '<i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>' +
                        '</summary>' + inner + '</details></td>' +
                        '<td class="p-4 text-center align-top">' + delBtn + '</td></tr>';
                }

                const name = esc(row.menu_name);
                return '<tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-none">' +
                    '<td class="p-4 text-sm font-bold text-slate-700 whitespace-nowrap">' + date + '</td>' +
                    '<td class="p-4 text-sm text-slate-600">' + name + '</td>' +
                    '<td class="p-4 text-sm text-slate-600">' + reps + '</td>' +
                    '<td class="p-4 text-sm text-slate-600 whitespace-nowrap">' + durLabel + '</td>' +
                    '<td class="p-4 text-center">' + delBtn + '</td></tr>';
            }).join('');

            list.querySelectorAll('button[data-del-log-id]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    await deleteLog(parseInt(btn.dataset.delLogId, 10));
                });
            });
        }

        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        document.getElementById('addLogBtn').onclick = addLog;

        window.addEventListener('DOMContentLoaded', async () => {
            document.getElementById('logDate').value = todayStr();
            await loadLogs();
        });
    </script>
</body>
</html>
