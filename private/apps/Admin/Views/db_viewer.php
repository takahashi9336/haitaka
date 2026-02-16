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
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
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
        .db-tab { cursor: pointer; }
        .db-tab.active { font-weight: 700; color: inherit; border-bottom: 2px solid currentColor; }
        .db-tab:not(.active) { color: #94a3b8; }
        .db-tab-content { display: none; }
        .db-tab-content.active { display: block; }
        .db-opt-disabled { opacity: 0.5; pointer-events: none; }
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

                <!-- ① テーブル選択ボックス（プルダウン＋クリアのみ） -->
                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-table-list text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">テーブルを選択</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">表示するテーブルを選んでください</p>
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
                </div>

                <?php if ($selectedTable && (!empty($columns) || !empty($tableStructure) || !empty($createSql))): ?>
                <!-- ② 抽出結果ボックス（タブ切り替え） -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden mb-6">
                    <!-- タブ -->
                    <div class="flex border-b border-slate-100 px-6 gap-6">
                        <span class="db-tab active py-4 text-sm" data-tab="data">データ</span>
                        <span class="db-tab py-4 text-sm" data-tab="structure">構造</span>
                        <span class="db-tab py-4 text-sm" data-tab="create">CREATE情報</span>
                    </div>

                    <!-- ダウンロード・コピー行（区切り・ヘッダー含む） -->
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center gap-4">
                        <div id="db_opt_group" class="flex items-center gap-4">
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
                        <label id="db_header_label" class="flex items-center gap-2 cursor-pointer text-sm text-slate-600">
                            <input type="checkbox" name="db_header" id="db_header" class="rounded border-slate-300 text-slate-600 focus:ring-slate-500">
                            ヘッダーあり
                        </label>
                        <div class="flex items-center gap-2 ml-auto">
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

                    <!-- タブコンテンツ：データ -->
                    <div id="tab_data" class="db-tab-content active">
                        <?php if (!empty($columns)): ?>
                        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-bold text-slate-800"><?= htmlspecialchars($selectedTable) ?> <span class="text-slate-400 font-normal text-sm">（<?= $totalCount ?? 0 ?> 件）</span></h3>
                            <?php $totalPages = max(1, (int)ceil(($totalCount ?? 0) / $rowsPerPage)); ?>
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
                        <?php else: ?>
                        <p class="px-6 py-8 text-center text-slate-400 text-sm">データがありません</p>
                        <?php endif; ?>
                    </div>

                    <!-- タブコンテンツ：構造 -->
                    <div id="tab_structure" class="db-tab-content">
                        <?php if (!empty($tableStructure)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-4 py-2 font-black text-[10px] text-slate-500 tracking-wider">カラム名</th>
                                        <th class="px-4 py-2 font-black text-[10px] text-slate-500 tracking-wider">型</th>
                                        <th class="px-4 py-2 font-black text-[10px] text-slate-500 tracking-wider">NULL</th>
                                        <th class="px-4 py-2 font-black text-[10px] text-slate-500 tracking-wider">キー</th>
                                        <th class="px-4 py-2 font-black text-[10px] text-slate-500 tracking-wider">デフォルト</th>
                                        <th class="px-4 py-2 font-black text-[10px] text-slate-500 tracking-wider">EXTRA</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php foreach ($tableStructure as $col): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-800"><?= htmlspecialchars($col['COLUMN_NAME'] ?? '') ?></td>
                                        <td class="px-4 py-2 font-mono text-xs text-slate-600"><?= htmlspecialchars($col['COLUMN_TYPE'] ?? '') ?></td>
                                        <td class="px-4 py-2 text-xs text-slate-600"><?= htmlspecialchars($col['IS_NULLABLE'] ?? '') ?></td>
                                        <td class="px-4 py-2 text-xs"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold <?= !empty($col['COLUMN_KEY']) ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' ?>"><?= htmlspecialchars($col['COLUMN_KEY'] ?: '—') ?></span></td>
                                        <td class="px-4 py-2 font-mono text-xs text-slate-600"><?= htmlspecialchars($col['COLUMN_DEFAULT'] ?? 'NULL') ?></td>
                                        <td class="px-4 py-2 font-mono text-xs text-slate-600"><?= htmlspecialchars($col['EXTRA'] ?? '') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="px-6 py-8 text-center text-slate-400 text-sm">構造情報がありません</p>
                        <?php endif; ?>
                    </div>

                    <!-- タブコンテンツ：CREATE情報 -->
                    <div id="tab_create" class="db-tab-content">
                        <?php if (!empty($createSql)): ?>
                        <pre id="db_create_sql" class="p-4 text-xs font-mono text-slate-700 bg-slate-50 overflow-x-auto whitespace-pre-wrap break-all m-0"><?= htmlspecialchars($createSql) ?></pre>
                        <?php else: ?>
                        <p class="px-6 py-8 text-center text-slate-400 text-sm">CREATE情報がありません</p>
                        <?php endif; ?>
                    </div>
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

        <?php if ($selectedTable): ?>
        (function() {
            var currentTab = 'data';
            var tableName = <?= json_encode($selectedTable) ?>;
            var dataCols = <?= json_encode(array_column($columns ?? [], 'COLUMN_NAME')) ?>;
            var dataRows = <?= json_encode($rows ?? []) ?>;
            var structureData = <?= json_encode(array_map(function($c) {
                return [
                    'COLUMN_NAME' => $c['COLUMN_NAME'] ?? '',
                    'COLUMN_TYPE' => $c['COLUMN_TYPE'] ?? '',
                    'IS_NULLABLE' => $c['IS_NULLABLE'] ?? '',
                    'COLUMN_KEY' => $c['COLUMN_KEY'] ?? '',
                    'COLUMN_DEFAULT' => $c['COLUMN_DEFAULT'] ?? 'NULL',
                    'EXTRA' => $c['EXTRA'] ?? '',
                ];
            }, $tableStructure ?? [])) ?>;
            var createSql = <?= json_encode($createSql ?? '') ?>;

            function showToast(msg) {
                if (typeof App !== 'undefined' && App.toast) App.toast(msg);
                else alert(msg);
            }

            // タブ切り替え
            document.querySelectorAll('.db-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var t = this.dataset.tab;
                    currentTab = t;
                    document.querySelectorAll('.db-tab').forEach(function(x) { x.classList.remove('active'); });
                    document.querySelectorAll('.db-tab-content').forEach(function(x) { x.classList.remove('active'); });
                    this.classList.add('active');
                    var content = document.getElementById('tab_' + t);
                    if (content) content.classList.add('active');

                    var optGroup = document.getElementById('db_opt_group');
                    var headerLabel = document.getElementById('db_header_label');
                    if (optGroup && headerLabel) {
                        if (t === 'create') {
                            optGroup.classList.add('db-opt-disabled');
                            headerLabel.classList.add('db-opt-disabled');
                        } else {
                            optGroup.classList.remove('db-opt-disabled');
                            headerLabel.classList.remove('db-opt-disabled');
                        }
                    }
                });
            });

            function getDelimiter() {
                var r = document.querySelector('input[name="db_delimiter"]:checked');
                return r && r.value === 'comma' ? ',' : '\t';
            }
            function getHeader() {
                return document.getElementById('db_header') && document.getElementById('db_header').checked;
            }

            // データCSV
            function buildDataCsv() {
                var delim = getDelimiter();
                var withHeader = getHeader();
                var escape = function(v) {
                    var s = String(v == null ? '' : v);
                    if (s.indexOf(delim) >= 0 || s.indexOf('"') >= 0 || s.indexOf('\n') >= 0 || s.indexOf('\r') >= 0)
                        return '"' + s.replace(/"/g, '""') + '"';
                    return s;
                };
                var lines = [];
                if (withHeader) lines.push(dataCols.map(escape).join(delim));
                dataRows.forEach(function(r) {
                    lines.push(dataCols.map(function(c) { return escape(r[c]); }).join(delim));
                });
                return lines.join('\r\n');
            }

            // 構造CSV
            function buildStructureCsv() {
                var delim = getDelimiter();
                var withHeader = getHeader();
                var cols = ['COLUMN_NAME','COLUMN_TYPE','IS_NULLABLE','COLUMN_KEY','COLUMN_DEFAULT','EXTRA'];
                var escape = function(v) {
                    var s = String(v == null ? '' : v);
                    if (s.indexOf(delim) >= 0 || s.indexOf('"') >= 0 || s.indexOf('\n') >= 0 || s.indexOf('\r') >= 0)
                        return '"' + s.replace(/"/g, '""') + '"';
                    return s;
                };
                var lines = [];
                if (withHeader) lines.push(cols.join(delim));
                structureData.forEach(function(r) {
                    lines.push(cols.map(function(c) { return escape(r[c]); }).join(delim));
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

                    var blob, filename, mime;
                    if (currentTab === 'create') {
                        blob = new Blob([createSql], { type: 'text/plain;charset=utf-8' });
                        filename = tableName + '.sql';
                    } else if (currentTab === 'structure') {
                        blob = new Blob(['\uFEFF' + buildStructureCsv()], { type: 'text/csv;charset=utf-8' });
                        filename = tableName + '_structure.csv';
                    } else {
                        blob = new Blob(['\uFEFF' + buildDataCsv()], { type: 'text/csv;charset=utf-8' });
                        filename = tableName + '.csv';
                    }
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = filename;
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
                    var text;
                    if (currentTab === 'create') {
                        text = createSql;
                    } else if (currentTab === 'structure') {
                        text = buildStructureCsv();
                    } else {
                        text = buildDataCsv();
                    }
                    navigator.clipboard.writeText(text).then(function() {
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
