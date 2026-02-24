<?php
/**
 * 楽曲の参加メンバー編集 View（管理者専用）
 * 複数メンバーの列・ポジション・センター・備考をテーブル内で同時編集可能
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$songId = (int)$song['id'];
$allMembersJson = json_encode(array_map(function ($m) {
    return [
        'id' => (int)$m['id'],
        'name' => $m['name'],
        'image_url' => $m['image_url'] ?? null,
        'generation' => (int)($m['generation'] ?? 0),
        'is_active' => (int)($m['is_active'] ?? 1),
    ];
}, $allMembers));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>参加メンバー編集 - <?= htmlspecialchars($song['title']) ?> - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .formation-draggable {
            cursor: grab;
            transition: transform 0.1s ease-out, box-shadow 0.1s ease-out;
        }
        .formation-draggable:active {
            cursor: grabbing;
            transform: scale(0.97);
            box-shadow: 0 4px 10px rgba(15,23,42,0.2);
        }
        .formation-drop-row {
            min-height: 64px;
            border-radius: 0.75rem;
            border: 1px dashed #cbd5f5;
            padding: 0.25rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }
        .formation-drop-row.is-over {
            background: #eff6ff;
            border-color: #60a5fa;
        }
        .formation-member-chip {
            width: 80px;
            margin: 0.1rem;
            flex-shrink: 0;
        }
        .formation-member-chip img {
            width: 100%;
            height: 100%;
            border-radius: 0.75rem;
            object-fit: cover;
            object-position: top;
        }
        .formation-member-chip span {
            display: block;
            font-size: 0.6rem;
            text-align: center;
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .formation-chip-center img {
            box-shadow: 0 0 0 3px #fbbf24;
        }
        .formation-chip-center span {
            color: #b45309;
            font-weight: 700;
        }
        .formation-right-panel { overflow-x: auto; }
        .formation-right-panel::-webkit-scrollbar { height: 6px; }
        .formation-right-panel::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .member-panel-transition { transition: width 0.2s ease, min-width 0.2s ease; }
        .member-panel-collapsed { width: 5rem !important; min-width: 5rem !important; max-width: 5rem !important; }
        .member-panel-collapsed .formation-narrow [class*="flex-wrap"] { flex-direction: column !important; flex-wrap: nowrap !important; }
        .member-panel-collapsed .formation-narrow .mb-2 > p:first-child { display: none; }
        .member-panel-collapsed .formation-narrow .formation-member-chip { width: 56px; }
        .member-panel-collapsed .formation-narrow .formation-member-chip span { font-size: 0.5rem; }
        .member-panel-collapsed .formation-narrow .formation-member-chip > div:first-child { width: 2.5rem !important; height: 2.5rem !important; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <button onclick="App.goBack('/hinata/song.php?id=<?= $songId ?>')" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-users-cog text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight truncate max-w-[200px]">参加メンバー編集</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 tracking-wider">楽曲</p>
                    <h2 class="text-xl font-black text-slate-800 mt-0.5"><?= htmlspecialchars($song['title']) ?></h2>
                    <?php if (!empty($release)): ?>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($release['title']) ?>（<?= htmlspecialchars($releaseTypes[$release['release_type']] ?? $release['release_type']) ?>）</p>
                    <?php endif; ?>
                </section>

                <!-- タブ切り替え（フォーメーション / リスト編集） -->
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm">
                    <div class="px-5 py-2 flex gap-4">
                        <button type="button" id="tabFormation" class="px-3 py-2 text-xs font-bold border-b-2 border-sky-500 text-slate-800">
                            フォーメーション編集（D&amp;D）
                        </button>
                        <button type="button" id="tabList" class="px-3 py-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                            リスト編集
                        </button>
                    </div>
                </section>

                <!-- 参加メンバー一覧（テキストボックス等で複数メンバー・複数項目を同時編集） -->
                <section id="tabPanelList" class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden hidden">
                    <div class="px-5 py-3 border-b <?= $cardBorder ?> flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-700">参加メンバー一覧</h3>
                        <button type="button" id="btnSaveList" class="h-9 px-4 <?= $btnBgClass ?> text-white text-xs font-bold rounded-full"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>><i class="fa-solid fa-save mr-1"></i>保存</button>
                    </div>
                    <!-- メンバー追加フォーム（リスト編集タブ内） -->
                    <div class="px-5 pt-4 pb-2 border-b <?= $cardBorder ?>">
                        <h3 class="text-xs font-black text-slate-700 mb-2">メンバーを追加</h3>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-[140px]">
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">メンバー</label>
                                <select id="addMemberId" class="w-full h-10 border <?= $cardBorder ?> rounded-lg px-3 text-sm bg-white">
                                    <?php
                                    $members = $allMembers;
                                    require __DIR__ . '/partials/member_select_options.php';
                                    ?>
                                </select>
                            </div>
                            <div class="w-20">
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">列</label>
                                <select id="addRowNumber" class="w-full h-10 border <?= $cardBorder ?> rounded-lg px-2 text-sm bg-white">
                                    <option value="1">1（手前）</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5（奥）</option>
                                </select>
                            </div>
                            <div class="w-20">
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">ポジション</label>
                                <input type="number" id="addPosition" min="1" value="1" class="w-full h-10 border <?= $cardBorder ?> rounded-lg px-2 text-sm">
                            </div>
                            <label class="flex items-center gap-2 h-10 cursor-pointer">
                                <input type="checkbox" id="addIsCenter" class="rounded border-slate-300">
                                <span class="text-sm font-bold text-slate-600">センター</span>
                            </label>
                            <button type="button" id="btnAdd" class="h-10 px-4 <?= $btnBgClass ?> text-white text-sm font-bold rounded-lg shrink-0"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>><i class="fa-solid fa-plus mr-1"></i>追加</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 min-w-[100px]">メンバー</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 w-28">列</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 w-24">ポジション</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 w-20">センター</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 min-w-[120px]">備考</th>
                                    <th class="px-3 py-2 text-left font-bold text-slate-500 w-14"></th>
                                </tr>
                            </thead>
                            <tbody id="memberListBody">
                                <?php foreach ($members as $m): ?>
                                <tr class="member-row border-t <?= $cardBorder ?> hover:bg-slate-50/50 align-middle">
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="member_id" value="<?= (int)$m['member_id'] ?>">
                                        <span class="font-medium text-slate-800"><?= htmlspecialchars($m['name']) ?></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select name="row_number" class="w-full max-w-[100px] h-9 border <?= $cardBorder ?> rounded-lg px-2 text-sm bg-white">
                                            <option value=""<?= ($m['row_number'] ?? '') === '' ? ' selected' : '' ?>>—</option>
                                            <option value="1"<?= (int)($m['row_number'] ?? 0) === 1 ? ' selected' : '' ?>>1（手前）</option>
                                            <option value="2"<?= (int)($m['row_number'] ?? 0) === 2 ? ' selected' : '' ?>>2</option>
                                            <option value="3"<?= (int)($m['row_number'] ?? 0) === 3 ? ' selected' : '' ?>>3</option>
                                            <option value="4"<?= (int)($m['row_number'] ?? 0) === 4 ? ' selected' : '' ?>>4</option>
                                            <option value="5"<?= (int)($m['row_number'] ?? 0) === 5 ? ' selected' : '' ?>>5（奥）</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="position" min="1" value="<?= $m['position'] !== null && $m['position'] !== '' ? (int)$m['position'] : '' ?>" placeholder="—" class="w-full max-w-[70px] h-9 border <?= $cardBorder ?> rounded-lg px-2 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="is_center" value="1" class="rounded border-slate-300"<?= !empty($m['is_center']) ? ' checked' : '' ?>>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="part_description" value="<?= htmlspecialchars($m['part_description'] ?? '') ?>" placeholder="—" class="w-full min-w-[80px] max-w-[180px] h-9 border <?= $cardBorder ?> rounded-lg px-2 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <button type="button" class="remove-row text-red-500 hover:text-red-700 p-1" title="この行を削除"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p id="emptyMessage" class="p-5 text-slate-400 text-sm <?= empty($members) ? '' : 'hidden' ?>">参加メンバーがいません。上から追加してください。</p>
                </section>

        <!-- フォーメーション編集（ドラッグ＆ドロップ） -->
        <section id="tabPanelFormation" class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-black text-slate-700">フォーメーション編集（ドラッグ＆ドロップ）</h3>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-600 cursor-pointer">
                        <input type="checkbox" id="formationShowGraduates" class="rounded border-slate-300">
                        <span>卒業生も表示</span>
                    </label>
                    <button type="button" id="btnSaveFormation" class="h-9 px-4 <?= $btnBgClass ?> text-white text-xs font-bold rounded-full"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>><i class="fa-solid fa-save mr-1"></i>保存</button>
                </div>
            </div>
            <div class="md:flex gap-6">
                <!-- 左：メンバー（期別グループ）・縮小可 -->
                <div id="memberPanelWrapper" class="member-panel-transition md:w-1/3 mb-4 md:mb-0 shrink-0">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[11px] font-bold text-slate-500">メンバー</p>
                        <button type="button" id="btnToggleMemberPanel" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border <?= $cardBorder ?> text-slate-500 hover:bg-slate-100 text-[10px] font-bold transition" title="パネル幅を切り替え（縦1列に縮小）">
                            <i class="fa-solid fa-angles-left text-[10px]" id="btnToggleMemberPanelIcon"></i>
                            <span id="btnToggleMemberPanelLabel">縮小</span>
                        </button>
                    </div>
                    <div id="formationNonParticipants" class="formation-narrow h-80 md:h-[480px] overflow-y-auto overflow-x-auto custom-scroll border <?= $cardBorder ?> rounded-2xl p-3 bg-slate-50">
                        <!-- JSで生成 -->
                    </div>
                </div>

                <!-- 右：フォーメーション（3列が基本、必要時のみ4・5列を追加）・はみ出たら横スクロール -->
                <div class="formation-right-panel md:flex-1 min-w-0">
                    <p class="text-[11px] text-slate-500 mb-2">ドラッグして列に配置すると、row/position に反映されます。</p>
                    <div class="space-y-4">
                        <div id="formationRow5Wrapper" class="hidden">
                            <div class="border <?= $cardBorder ?> rounded-2xl px-4 py-4 min-w-0">
                                <div id="formationRow5" class="formation-drop-row custom-scroll flex items-center justify-center gap-1"></div>
                            </div>
                        </div>
                        <div id="btnAddRow5Wrapper" class="hidden">
                            <button type="button" id="btnAddRow5" class="w-full py-2 rounded-xl border border-dashed <?= $cardBorder ?> text-slate-400 hover:bg-slate-50 hover:text-slate-600 hover:border-sky-300 transition flex items-center justify-center gap-2 text-xs font-bold">
                                <i class="fa-solid fa-plus text-[10px]"></i>5列目を追加
                            </button>
                        </div>
                        <div id="formationRow4Wrapper" class="hidden">
                            <div class="border <?= $cardBorder ?> rounded-2xl px-4 py-4 min-w-0">
                                <div id="formationRow4" class="formation-drop-row custom-scroll flex items-center justify-center gap-1"></div>
                            </div>
                        </div>
                        <div id="btnAddRow4Wrapper">
                            <button type="button" id="btnAddRow4" class="w-full py-2 rounded-xl border border-dashed <?= $cardBorder ?> text-slate-400 hover:bg-slate-50 hover:text-slate-600 hover:border-sky-300 transition flex items-center justify-center gap-2 text-xs font-bold">
                                <i class="fa-solid fa-plus text-[10px]"></i>4列目を追加
                            </button>
                        </div>
                        <div class="border <?= $cardBorder ?> rounded-2xl px-4 py-4 min-w-0">
                            <div id="formationRow3" class="formation-drop-row custom-scroll flex items-center justify-center gap-1"></div>
                        </div>
                        <div class="border <?= $cardBorder ?> rounded-2xl px-4 py-4 min-w-0">
                            <div id="formationRow2" class="formation-drop-row custom-scroll flex items-center justify-center gap-1"></div>
                        </div>
                        <div class="border <?= $cardBorder ?> rounded-2xl px-4 py-4 min-w-0">
                            <div id="formationRow1" class="formation-drop-row custom-scroll flex items-center justify-center gap-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=3"></script>
    <script src="/assets/js/hinata-member-groups.js"></script>
    <script>
(function () {
    const songId = <?= (int)$songId ?>;
    const allMembers = <?= $allMembersJson ?>;
    const cardBorder = '<?= addslashes($cardBorder) ?>';

    const tbody = document.getElementById('memberListBody');
    const emptyMessage = document.getElementById('emptyMessage');
    const formationRow1 = document.getElementById('formationRow1');
    const formationRow2 = document.getElementById('formationRow2');
    const formationRow3 = document.getElementById('formationRow3');
    const formationRow4 = document.getElementById('formationRow4');
    const formationRow5 = document.getElementById('formationRow5');
    const formationRow4Wrapper = document.getElementById('formationRow4Wrapper');
    const formationRow5Wrapper = document.getElementById('formationRow5Wrapper');
    const btnAddRow4Wrapper = document.getElementById('btnAddRow4Wrapper');
    const btnAddRow5Wrapper = document.getElementById('btnAddRow5Wrapper');
    const formationShowGraduates = document.getElementById('formationShowGraduates');

    let expandedRow4 = false;
    let expandedRow5 = false;
    const formationNonParticipants = document.getElementById('formationNonParticipants');

    const tabFormation = document.getElementById('tabFormation');
    const tabList = document.getElementById('tabList');
    const tabPanelFormation = document.getElementById('tabPanelFormation');
    const tabPanelList = document.getElementById('tabPanelList');

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s == null ? '' : s;
        return div.innerHTML;
    }

    function getMemberIdsInTable() {
        return Array.from(tbody.querySelectorAll('input[name="member_id"]')).map(el => el.value);
    }

    function buildRowHtml(memberId, name, rowNumber, position, isCenter, partDescription) {
        const selEmpty = (!rowNumber || (rowNumber < 1 || rowNumber > 5)) ? ' selected' : '';
        return '<tr class="member-row border-t border-slate-100 hover:bg-slate-50/50 align-middle">' +
            '<td class="px-3 py-2"><input type="hidden" name="member_id" value="' + escapeHtml(String(memberId)) + '"><span class="font-medium text-slate-800">' + escapeHtml(name) + '</span></td>' +
            '<td class="px-3 py-2"><select name="row_number" class="w-full max-w-[100px] h-9 border ' + cardBorder + ' rounded-lg px-2 text-sm bg-white">' +
                '<option value=""' + selEmpty + '>—</option>' +
                '<option value="1"' + (rowNumber == 1 ? ' selected' : '') + '>1（手前）</option>' +
                '<option value="2"' + (rowNumber == 2 ? ' selected' : '') + '>2</option>' +
                '<option value="3"' + (rowNumber == 3 ? ' selected' : '') + '>3</option>' +
                '<option value="4"' + (rowNumber == 4 ? ' selected' : '') + '>4</option>' +
                '<option value="5"' + (rowNumber == 5 ? ' selected' : '') + '>5（奥）</option>' +
            '</select></td>' +
            '<td class="px-3 py-2"><input type="number" name="position" min="1" value="' + (position != null && position !== '' ? escapeHtml(String(position)) : '') + '" placeholder="—" class="w-full max-w-[70px] h-9 border ' + cardBorder + ' rounded-lg px-2 text-sm"></td>' +
            '<td class="px-3 py-2"><input type="checkbox" name="is_center" value="1" class="rounded border-slate-300"' + (isCenter ? ' checked' : '') + '></td>' +
            '<td class="px-3 py-2"><input type="text" name="part_description" value="' + escapeHtml(partDescription || '') + '" placeholder="—" class="w-full min-w-[80px] max-w-[180px] h-9 border ' + cardBorder + ' rounded-lg px-2 text-sm"></td>' +
            '<td class="px-3 py-2"><button type="button" class="remove-row text-red-500 hover:text-red-700 p-1" title="この行を削除"><i class="fa-solid fa-trash-can text-xs"></i></button></td>' +
            '</tr>';
    }

    function updateEmptyMessage() {
        emptyMessage.classList.toggle('hidden', tbody.querySelectorAll('tr.member-row').length > 0);
    }

    function getMemberById(memberId) {
        return allMembers.find(function (m) { return m.id === memberId; }) || null;
    }

    function setActiveTab(tab) {
        const isFormation = tab === 'formation';
        if (tabPanelFormation) tabPanelFormation.classList.toggle('hidden', !isFormation);
        if (tabPanelList) tabPanelList.classList.toggle('hidden', isFormation);
        if (tabFormation) {
            tabFormation.classList.toggle('border-sky-500', isFormation);
            tabFormation.classList.toggle('text-slate-800', isFormation);
            tabFormation.classList.toggle('text-slate-500', !isFormation);
        }
        if (tabList) {
            tabList.classList.toggle('border-sky-500', !isFormation);
            tabList.classList.toggle('text-slate-800', !isFormation);
            tabList.classList.toggle('text-slate-500', isFormation);
        }
    }

    function getFormationByMember() {
        const map = {};
        tbody.querySelectorAll('tr.member-row').forEach(function (tr) {
            const memberId = parseInt(tr.querySelector('input[name="member_id"]').value, 10);
            if (!memberId) return;
            const rowNumberEl = tr.querySelector('select[name="row_number"]');
            const positionEl = tr.querySelector('input[name="position"]');
            const centerEl = tr.querySelector('input[name="is_center"]');
            const rowNumber = rowNumberEl && rowNumberEl.value !== '' ? parseInt(rowNumberEl.value, 10) : null;
            const position = positionEl && positionEl.value !== '' ? parseInt(positionEl.value, 10) : null;
            // row_number が未設定（null）の場合はフォーメーション外扱いとし、
            // formationMap には含めない（非参加メンバーとして下部リストに出す）
            if (rowNumber !== null) {
                map[memberId] = {
                    rowNumber: rowNumber,
                    position: position,
                    isCenter: centerEl ? !!centerEl.checked : false
                };
            }
        });
        return map;
    }

    function getNextPositionForRow(rowNumber) {
        let maxPos = 0;
        tbody.querySelectorAll('tr.member-row').forEach(function (tr) {
            const rowNumberEl = tr.querySelector('select[name="row_number"]');
            const positionEl = tr.querySelector('input[name="position"]');
            const rn = rowNumberEl && rowNumberEl.value !== '' ? parseInt(rowNumberEl.value, 10) : null;
            if ((rn || null) === (rowNumber || null)) {
                const pos = positionEl && positionEl.value !== '' ? parseInt(positionEl.value, 10) : null;
                if (pos && pos > maxPos) maxPos = pos;
            }
        });
        return maxPos + 1;
    }

    // 各列ごとに position を 1,2,3,... となるよう振り直す
    function normalizePositions() {
        [1, 2, 3, 4, 5].forEach(function (rowNumber) {
            const rows = [];
            tbody.querySelectorAll('tr.member-row').forEach(function (tr) {
                const rowNumberEl = tr.querySelector('select[name="row_number"]');
                const positionEl = tr.querySelector('input[name="position"]');
                const rn = rowNumberEl && rowNumberEl.value !== '' ? parseInt(rowNumberEl.value, 10) : null;
                if (rn === rowNumber) {
                    const pos = positionEl && positionEl.value !== '' ? parseInt(positionEl.value, 10) : null;
                    rows.push({ tr: tr, positionEl: positionEl, pos: pos });
                }
            });
            rows.sort(function (a, b) {
                const pa = a.pos == null ? 9999 : a.pos;
                const pb = b.pos == null ? 9999 : b.pos;
                return pa - pb;
            });
            rows.forEach(function (item, idx) {
                if (item.positionEl) {
                    item.positionEl.value = String(idx + 1);
                }
            });
        });
    }

    function setMemberRow(memberId, name, rowNumber, position) {
        let tr = null;
        tbody.querySelectorAll('tr.member-row').forEach(function (row) {
            const mid = parseInt(row.querySelector('input[name="member_id"]').value, 10);
            if (mid === memberId) {
                tr = row;
            }
        });
        if (!tr) {
            // 新規行を末尾に追加
            tbody.insertAdjacentHTML('beforeend', buildRowHtml(memberId, name, rowNumber, position, false, ''));
            updateEmptyMessage();
            return;
        }
        const rowNumberEl = tr.querySelector('select[name="row_number"]');
        const positionEl = tr.querySelector('input[name="position"]');
        if (rowNumberEl) {
            rowNumberEl.value = rowNumber ? String(rowNumber) : '';
        }
        if (positionEl) {
            positionEl.value = position != null ? String(position) : '';
        }
    }

    function renderFormation() {
        const showGraduates = formationShowGraduates && formationShowGraduates.checked;
        const formationMap = getFormationByMember();

        // 列ごとの配置
        const rows = { 1: [], 2: [], 3: [], 4: [], 5: [] };
        Object.keys(formationMap).forEach(function (midStr) {
            const mid = parseInt(midStr, 10);
            const info = formationMap[mid];
            const rowKey = (info.rowNumber >= 1 && info.rowNumber <= 5) ? info.rowNumber : null;
            if (!rowKey) return;
            rows[rowKey].push({ memberId: mid, position: info.position || 0, isCenter: !!info.isCenter });
        });
        [1, 2, 3, 4, 5].forEach(function (key) {
            rows[key].sort(function (a, b) { return a.position - b.position; });
        });

        function renderRow(container, rowKey) {
            if (!container) return;
            container.innerHTML = '';
            (rows[rowKey] || []).forEach(function (item) {
                const member = allMembers.find(function (m) { return m.id === item.memberId; });
                if (!member) return;
                const chip = document.createElement('div');
                chip.className = 'formation-member-chip formation-draggable';
                chip.draggable = true;
                chip.setAttribute('data-member-id', String(member.id));

                if (item.isCenter) {
                    chip.classList.add('formation-chip-center');
                }

                const imgWrapper = document.createElement('div');
                imgWrapper.className = 'w-16 h-16 md:w-20 md:h-20 mx-auto rounded-lg overflow-hidden bg-slate-200 flex items-center justify-center text-[10px] text-slate-400';

                let src = member.image_url || '';
                if (src && !/^https?:\/\//.test(src) && src[0] !== '/') {
                    src = '/' + src;
                }
                if (src) {
                    const el = document.createElement('img');
                    el.src = src;
                    el.alt = '';
                    el.onerror = function () {
                        imgWrapper.innerHTML = '<i class="fa-solid fa-user text-slate-400 text-xl"></i>';
                        this.remove();
                    };
                    imgWrapper.appendChild(el);
                } else {
                    imgWrapper.innerHTML = '<i class="fa-solid fa-user text-slate-400 text-xl"></i>';
                }
                const nameEl = document.createElement('span');
                nameEl.textContent = member.name;
                chip.appendChild(imgWrapper);
                chip.appendChild(nameEl);
                container.appendChild(chip);
            });
        }

        renderRow(formationRow5, 5);
        renderRow(formationRow4, 4);
        renderRow(formationRow3, 3);
        renderRow(formationRow2, 2);
        renderRow(formationRow1, 1);

        // 4・5列の表示制御（メンバーがいる or ユーザーが追加した場合は表示）
        const showRow4 = (rows[4] && rows[4].length > 0) || expandedRow4;
        const showRow5 = (rows[5] && rows[5].length > 0) || expandedRow5;
        if (formationRow4Wrapper) formationRow4Wrapper.classList.toggle('hidden', !showRow4);
        if (formationRow5Wrapper) formationRow5Wrapper.classList.toggle('hidden', !showRow5);
        if (btnAddRow4Wrapper) btnAddRow4Wrapper.classList.toggle('hidden', showRow4);
        if (btnAddRow5Wrapper) btnAddRow5Wrapper.classList.toggle('hidden', !showRow4 || showRow5);

        // 非参加メンバー（期別・卒業生は下）
        if (formationNonParticipants) {
            formationNonParticipants.innerHTML = '';
            const inFormationIds = new Set(Object.keys(formationMap).map(function (mid) { return parseInt(mid, 10); }));
            const available = allMembers.filter(function (m) {
                if (!showGraduates && !m.is_active) return false;
                return !inFormationIds.has(m.id);
            });
            const grouped = HinataMemberGroups.group(available);
            if (grouped.order.length === 0 && grouped.graduates.length === 0) {
                const p = document.createElement('p');
                p.className = 'text-xs text-slate-400 px-1 py-0.5';
                p.textContent = '非参加メンバーはいません。';
                formationNonParticipants.appendChild(p);
            } else {
                function renderMemberChips(members) {
                    const row = document.createElement('div');
                    row.className = 'flex flex-wrap gap-0.5';
                    members.forEach(function (m) {
                        const chip = document.createElement('div');
                        chip.className = 'formation-member-chip formation-draggable';
                        chip.draggable = true;
                        chip.setAttribute('data-member-id', String(m.id));

                        const imgWrapper = document.createElement('div');
                        imgWrapper.className = 'w-16 h-16 md:w-20 md:h-20 mx-auto rounded-lg overflow-hidden bg-slate-200 flex items-center justify-center text-[10px] text-slate-400';

                        let src = m.image_url || '';
                        if (src && !/^https?:\/\//.test(src) && src[0] !== '/') {
                            src = '/' + src;
                        }
                        if (src) {
                            const el = document.createElement('img');
                            el.src = src;
                            el.alt = '';
                            el.onerror = function () {
                                imgWrapper.innerHTML = '<i class="fa-solid fa-user text-slate-400 text-xl"></i>';
                                this.remove();
                            };
                            imgWrapper.appendChild(el);
                        } else {
                            imgWrapper.innerHTML = '<i class="fa-solid fa-user text-slate-400 text-xl"></i>';
                        }
                        const nameEl = document.createElement('span');
                        nameEl.textContent = m.name;
                        chip.appendChild(imgWrapper);
                        chip.appendChild(nameEl);
                        row.appendChild(chip);
                    });
                    return row;
                }
                grouped.order.forEach(function (gen) {
                    const members = grouped.active[gen] || [];
                    if (members.length === 0) return;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-2';
                    const title = document.createElement('p');
                    title.className = 'text-[11px] font-bold text-slate-500 mb-1';
                    title.textContent = HinataMemberGroups.getGenLabel(gen);
                    wrapper.appendChild(title);
                    wrapper.appendChild(renderMemberChips(members));
                    formationNonParticipants.appendChild(wrapper);
                });
                if (grouped.graduates.length > 0) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-2 mt-2 pt-2 border-t border-slate-200';
                    const title = document.createElement('p');
                    title.className = 'text-[11px] font-bold text-slate-500 mb-1';
                    title.textContent = '卒業生';
                    wrapper.appendChild(title);
                    wrapper.appendChild(renderMemberChips(grouped.graduates));
                    formationNonParticipants.appendChild(wrapper);
                }
            }
        }
    }

    // D&D サポート
    let draggingMemberId = null;
    document.addEventListener('dragstart', function (e) {
        const chip = e.target.closest('.formation-draggable');
        if (!chip) return;
        const mid = chip.getAttribute('data-member-id');
        if (!mid) return;
        draggingMemberId = parseInt(mid, 10);
        e.dataTransfer && e.dataTransfer.setData('text/plain', mid);
    });
    document.addEventListener('dragend', function () {
        draggingMemberId = null;
        document.querySelectorAll('.formation-drop-row.is-over').forEach(function (el) {
            el.classList.remove('is-over');
        });
    });

    [formationRow1, formationRow2, formationRow3, formationRow4, formationRow5].forEach(function (rowEl, idx) {
        if (!rowEl) return;
        rowEl.addEventListener('dragover', function (e) {
            if (!draggingMemberId) return;
            e.preventDefault();
            rowEl.classList.add('is-over');
        });
        rowEl.addEventListener('dragleave', function () {
            rowEl.classList.remove('is-over');
        });
        rowEl.addEventListener('drop', function (e) {
            if (!draggingMemberId) return;
            e.preventDefault();
            rowEl.classList.remove('is-over');

            const member = getMemberById(draggingMemberId);
            if (!member) return;

            let rowNumber = null;
            if (rowEl === formationRow1) rowNumber = 1;
            else if (rowEl === formationRow2) rowNumber = 2;
            else if (rowEl === formationRow3) rowNumber = 3;
            else if (rowEl === formationRow4) rowNumber = 4;
            else if (rowEl === formationRow5) rowNumber = 5;
            else rowNumber = null; // その他

            // 既存の並び順を取得し、ドロップ位置に応じて左右に挿入
            const chips = Array.from(rowEl.querySelectorAll('.formation-member-chip'));
            const existingOrder = chips
                .map(function (chip) { return parseInt(chip.getAttribute('data-member-id') || '0', 10); })
                .filter(function (id) { return id && id !== draggingMemberId; });

            let insertIndex = existingOrder.length;
            if (chips.length > 0) {
                const dropX = e.clientX;
                let bestIndex = existingOrder.length;
                let minDist = Infinity;
                chips.forEach(function (chip, idx2) {
                    const midAttr = chip.getAttribute('data-member-id');
                    const mid = midAttr ? parseInt(midAttr, 10) : 0;
                    if (!mid || mid === draggingMemberId) return;
                    const rect = chip.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const dist = Math.abs(dropX - centerX);
                    if (dist < minDist) {
                        minDist = dist;
                        bestIndex = dropX < centerX ? idx2 : idx2 + 1;
                    }
                });
                insertIndex = bestIndex;
            }

            const newOrder = existingOrder.slice(0, insertIndex)
                .concat([draggingMemberId])
                .concat(existingOrder.slice(insertIndex));

            newOrder.forEach(function (mid, idx3) {
                const m = getMemberById(mid);
                if (!m) return;
                const pos = idx3 + 1;
                setMemberRow(m.id, m.name, rowNumber, pos);
            });

            renderFormation();
        });
    });

    if (formationShowGraduates) {
        formationShowGraduates.addEventListener('change', renderFormation);
    }

    document.getElementById('btnAddRow4')?.addEventListener('click', function () {
        expandedRow4 = true;
        renderFormation();
    });
    document.getElementById('btnAddRow5')?.addEventListener('click', function () {
        expandedRow5 = true;
        renderFormation();
    });

    document.getElementById('btnToggleMemberPanel')?.addEventListener('click', function () {
        var wrapper = document.getElementById('memberPanelWrapper');
        var icon = document.getElementById('btnToggleMemberPanelIcon');
        var label = document.getElementById('btnToggleMemberPanelLabel');
        if (!wrapper) return;
        wrapper.classList.toggle('member-panel-collapsed');
        var collapsed = wrapper.classList.contains('member-panel-collapsed');
        if (icon) icon.className = collapsed ? 'fa-solid fa-angles-right text-[10px]' : 'fa-solid fa-angles-left text-[10px]';
        if (label) label.textContent = collapsed ? '展開' : '縮小';
    });

    // 非参加メンバーエリアにドロップされた場合はフォーメーションから外す（テーブル行ごと削除＆position詰め）
    if (formationNonParticipants) {
        formationNonParticipants.addEventListener('dragover', function (e) {
            if (!draggingMemberId) return;
            e.preventDefault();
        });
        formationNonParticipants.addEventListener('drop', function (e) {
            if (!draggingMemberId) return;
            e.preventDefault();
            const member = getMemberById(draggingMemberId);
            if (!member) return;
            // 対応する行をテーブルから削除（完全に非参加扱いに戻す）
            tbody.querySelectorAll('tr.member-row').forEach(function (tr) {
                const mid = parseInt(tr.querySelector('input[name="member_id"]').value, 10);
                if (mid === member.id) {
                    tr.remove();
                }
            });
            // 残ったメンバーの position を 1,2,3,... に詰める
            normalizePositions();
            updateEmptyMessage();
            renderFormation();
        });
    }

    // フォーメーション側のメンバーをダブルクリックしたときにセンター ON/OFF をトグル
    document.addEventListener('dblclick', function (e) {
        const chip = e.target.closest('.formation-draggable');
        if (!chip) return;
        const rowEl = chip.closest('.formation-drop-row');
        if (!rowEl) return; // 左側メンバー欄でのダブルクリックは無視
        const midAttr = chip.getAttribute('data-member-id');
        if (!midAttr) return;
        const memberId = parseInt(midAttr, 10);
        if (!memberId) return;

        // 対応するテーブル行のセンターフラグをトグル
        tbody.querySelectorAll('tr.member-row').forEach(function (tr) {
            const mid = parseInt(tr.querySelector('input[name="member_id"]').value, 10);
            if (mid !== memberId) return;
            const centerEl = tr.querySelector('input[name="is_center"]');
            if (centerEl) {
                centerEl.checked = !centerEl.checked;
            }
        });

        // 再描画（センター色反映）
        renderFormation();
    });

    if (tabFormation && tabList) {
        tabFormation.addEventListener('click', function () { setActiveTab('formation'); });
        tabList.addEventListener('click', function () { setActiveTab('list'); });
    }

    document.getElementById('btnAdd').addEventListener('click', function () {
        const addSelect = document.getElementById('addMemberId');
        const memberId = addSelect.value;
        const name = addSelect.options[addSelect.selectedIndex] ? addSelect.options[addSelect.selectedIndex].text : '';
        if (!memberId || !name) {
            alert('メンバーを選択してください');
            return;
        }
        const ids = getMemberIdsInTable();
        if (ids.indexOf(memberId) !== -1) {
            alert('このメンバーは既に追加済みです');
            return;
        }
        const rowNumber = document.getElementById('addRowNumber').value;
        const position = document.getElementById('addPosition').value;
        const isCenter = document.getElementById('addIsCenter').checked;
        tbody.insertAdjacentHTML('beforeend', buildRowHtml(memberId, name, rowNumber ? parseInt(rowNumber, 10) : null, position || null, isCenter, ''));
        updateEmptyMessage();
        renderFormation();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (!btn) return;
        btn.closest('tr').remove();
        updateEmptyMessage();
        renderFormation();
    });

    function handleSave() {
        const rows = tbody.querySelectorAll('tr.member-row');
        const members = [];
        rows.forEach(function (tr) {
            const memberId = parseInt(tr.querySelector('input[name="member_id"]').value, 10);
            if (memberId <= 0) return;
            const rowNumberEl = tr.querySelector('select[name="row_number"]');
            const positionEl = tr.querySelector('input[name="position"]');
            const centerEl = tr.querySelector('input[name="is_center"]');
            const partEl = tr.querySelector('input[name="part_description"]');
            const rowNumber = rowNumberEl && rowNumberEl.value !== '' ? parseInt(rowNumberEl.value, 10) : null;
            const position = positionEl && positionEl.value !== '' ? parseInt(positionEl.value, 10) : null;
            members.push({
                member_id: memberId,
                is_center: centerEl ? centerEl.checked : false,
                row_number: rowNumber,
                position: position,
                part_description: partEl ? (partEl.value || null) : null
            });
        });

        const btnList = document.getElementById('btnSaveList');
        const btnFormation = document.getElementById('btnSaveFormation');
        if (btnList) btnList.disabled = true;
        if (btnFormation) btnFormation.disabled = true;
        fetch('/hinata/api/save_song_members.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ song_id: songId, members: members })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    alert('保存しました');
                } else {
                    alert('エラー: ' + (data.message || '保存に失敗しました'));
                }
            })
            .catch(function () { alert('通信エラー'); })
            .finally(function () {
                if (btnList) btnList.disabled = false;
                if (btnFormation) btnFormation.disabled = false;
            });
    }

    document.getElementById('btnSaveList')?.addEventListener('click', handleSave);
    document.getElementById('btnSaveFormation')?.addEventListener('click', handleSave);

    updateEmptyMessage();
    setActiveTab('formation');
    renderFormation();
})();
    </script>
</body>
</html>
