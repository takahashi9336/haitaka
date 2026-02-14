<?php
/**
 * DBビューワ View（管理者専用・参照のみ）Admin 配下
 */
$uri = $_SERVER['REQUEST_URI'];
$isDbViewer = (strpos($uri, '/db_viewer') !== false);
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBビューワ - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        <?php if ($isThemeHex): ?>
        .admin-focus:focus { --tw-ring-color: var(--admin-theme); }
        <?php endif; ?>
    </style>
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
        .db-table th, .db-table td { white-space: nowrap; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-database text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">DBビューワ</h1>
            </div>
            <p class="text-[10px] font-bold text-slate-400 tracking-wider">参照専用（管理者）</p>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-6xl mx-auto w-full">
                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-table-list text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">テーブルを選択</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">表示するテーブルを選んでください（最大<?= (int)$rowsPerPage ?>件／ページ）</p>
                        </div>
                    </div>
                    <form method="get" action="/db_viewer/" class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[200px]">
                            <select name="table" onchange="this.form.submit()" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 <?= $isThemeHex ? 'admin-focus' : 'focus:ring-' . $themeTailwind . '-100' ?> outline-none transition-all">
                                <option value="">— テーブルを選択 —</option>
                                <?php foreach ($tables as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $selectedTable === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($selectedTable): ?>
                        <a href="/db_viewer/" class="text-slate-500 hover:text-slate-700 text-sm font-bold">クリア</a>
                        <?php endif; ?>
                    </form>
                    <?php if ($selectedTable && !empty($columns)): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-slate-500 font-bold">区切り文字</span>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-600">
                                <input type="radio" name="db_delimiter" value="tab" checked class="rounded-full border-slate-300 text-slate-600 focus:ring-slate-500">
                                タブ
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-600">
                                <input type="radio" name="db_delimiter" value="comma" class="rounded-full border-slate-300 text-slate-600 focus:ring-slate-500">
                                カンマ
                            </label>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-600">
                            <input type="checkbox" name="db_header" id="db_header" class="rounded border-slate-300 text-slate-600 focus:ring-slate-500">
                            ヘッダーあり
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="db_download_btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-download text-xs"></i>
                                <span class="db_btn_text">ダウンロード</span>
                            </button>
                            <button type="button" id="db_copy_btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                <i class="fa-solid fa-copy text-xs"></i>
                                コピー
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($selectedTable && !empty($columns)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-bold text-slate-800"><?= htmlspecialchars($selectedTable) ?> <span class="text-slate-400 font-normal text-sm">（<?= $totalCount ?> 件）</span></h3>
                        <?php $totalPages = max(1, (int)ceil($totalCount / $rowsPerPage)); ?>
                        <?php if ($totalPages > 1): ?>
                        <nav class="flex items-center gap-2">
                            <?php if ($page > 1): ?>
                            <a href="?table=<?= rawurlencode($selectedTable) ?>&page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">前へ</a>
                            <?php endif; ?>
                            <span class="text-xs text-slate-500 font-bold"><?= $page ?> / <?= $totalPages ?></span>
                            <?php if ($page < $totalPages): ?>
                            <a href="?table=<?= rawurlencode($selectedTable) ?>&page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">次へ</a>
                            <?php endif; ?>
                        </nav>
                        <?php endif; ?>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="db-table w-full text-left text-sm min-w-[600px]">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <?php foreach ($columns as $col): ?>
                                    <th class="px-4 py-3 font-black text-[10px] text-slate-500 tracking-wider" title="<?= htmlspecialchars($col['DATA_TYPE']) ?>"><?= htmlspecialchars($col['COLUMN_NAME']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($rows as $row): ?>
                                <tr class="hover:bg-slate-50/50">
                                    <?php foreach ($columns as $col): ?>
                                    <td class="px-4 py-2 text-slate-700 text-xs font-medium" title="<?= htmlspecialchars((string)($row[$col['COLUMN_NAME']] ?? '')) ?>"><?= htmlspecialchars(mb_strimwidth((string)($row[$col['COLUMN_NAME']] ?? ''), 0, 50)) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (empty($rows)): ?>
                    <p class="px-6 py-8 text-center text-slate-400 text-sm">データがありません</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn') && (document.getElementById('mobileMenuBtn').onclick = function() {
            document.getElementById('sidebar').classList.add('mobile-open');
        });

        <?php if ($selectedTable && !empty($columns) && isset($rows)): ?>
        (function() {
            var data = {
                columns: <?= json_encode(array_column($columns, 'COLUMN_NAME')) ?>,
                rows: <?= json_encode($rows) ?>
            };
            function showToast(msg) {
                var el = document.getElementById('app-toast');
                if (typeof App !== 'undefined' && App.toast) {
                    App.toast(msg);
                } else if (el) {
                    el.textContent = msg;
                    el.style.opacity = '1';
                    setTimeout(function() { el.style.opacity = '0'; }, 2500);
                }
            }
            function getDelimiter() {
                var r = document.querySelector('input[name="db_delimiter"]:checked');
                return r && r.value === 'comma' ? ',' : '\t';
            }
            function getHeader() {
                return document.getElementById('db_header') && document.getElementById('db_header').checked;
            }
            function buildCsv() {
                var colNames = data.columns;
                var rows = data.rows;
                var delim = getDelimiter();
                var withHeader = getHeader();
                var escape = function(v) {
                    var s = String(v == null ? '' : v);
                    if (s.indexOf(delim) >= 0 || s.indexOf('"') >= 0 || s.indexOf('\n') >= 0 || s.indexOf('\r') >= 0)
                        return '"' + s.replace(/"/g, '""') + '"';
                    return s;
                };
                var lines = [];
                if (withHeader) lines.push(colNames.map(escape).join(delim));
                rows.forEach(function(r) {
                    lines.push(colNames.map(function(c) { return escape(r[c]); }).join(delim));
                });
                return lines.join('\r\n');
            }
            var downloadBtn = document.getElementById('db_download_btn');
            if (downloadBtn) {
                downloadBtn.onclick = function() {
                    var btn = this;
                    var icon = btn.querySelector('i');
                    var text = btn.querySelector('.db_btn_text');
                    btn.disabled = true;
                    if (icon) icon.className = 'fa-solid fa-spinner fa-spin text-xs';
                    if (text) text.textContent = '処理中...';
                    var csv = buildCsv();
                    var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = (document.querySelector('select[name="table"]').value || 'table') + '.csv';
                    a.click();
                    URL.revokeObjectURL(a.href);
                    setTimeout(function() {
                        btn.disabled = false;
                        if (icon) icon.className = 'fa-solid fa-download text-xs';
                        if (text) text.textContent = 'ダウンロード';
                        showToast('ダウンロードを実行しました。');
                    }, 100);
                };
            }
            var copyBtn = document.getElementById('db_copy_btn');
            if (copyBtn) {
                copyBtn.onclick = function() {
                    var csv = buildCsv();
                    navigator.clipboard.writeText(csv).then(function() {
                        showToast('コピーしました。');
                    }).catch(function() {
                        showToast('コピーに失敗しました。');
                    });
                };
            }
        })();
        <?php endif; ?>
    </script>
</body>
</html>
