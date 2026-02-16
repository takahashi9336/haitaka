<?php
/**
 * 楽曲の参加メンバー編集 View（管理者専用）
 * 複数メンバーの列・ポジション・センター・備考をテーブル内で同時編集可能
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$songId = (int)$song['id'];
$allMembersJson = json_encode(array_map(function ($m) {
    return ['id' => (int)$m['id'], 'name' => $m['name']];
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
        .custom-scroll::-webkit-scrollbar { width: 4px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/song.php?id=<?= $songId ?>" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-users-cog text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight truncate max-w-[200px]">参加メンバー編集</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-4xl mx-auto space-y-6">
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 tracking-wider">楽曲</p>
                    <h2 class="text-xl font-black text-slate-800 mt-0.5"><?= htmlspecialchars($song['title']) ?></h2>
                    <?php if (!empty($release)): ?>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($release['title']) ?>（<?= htmlspecialchars($releaseTypes[$release['release_type']] ?? $release['release_type']) ?>）</p>
                    <?php endif; ?>
                </section>

                <!-- 追加フォーム -->
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-sm font-black text-slate-700 mb-3">メンバーを追加</h3>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[140px]">
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">メンバー</label>
                            <select id="addMemberId" class="w-full h-10 border <?= $cardBorder ?> rounded-lg px-3 text-sm bg-white">
                                <option value="">選択</option>
                                <?php foreach ($allMembers as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w-20">
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">列</label>
                            <select id="addRowNumber" class="w-full h-10 border <?= $cardBorder ?> rounded-lg px-2 text-sm bg-white">
                                <option value="1">1（手前）</option>
                                <option value="2">2</option>
                                <option value="3">3（奥）</option>
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
                </section>

                <!-- 参加メンバー一覧（テキストボックス等で複数メンバー・複数項目を同時編集） -->
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b <?= $cardBorder ?> flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-700">参加メンバー一覧</h3>
                        <button type="button" id="btnSave" class="h-9 px-4 <?= $btnBgClass ?> text-white text-xs font-bold rounded-full"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>><i class="fa-solid fa-save mr-1"></i>保存</button>
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
                                            <option value="3"<?= (int)($m['row_number'] ?? 0) === 3 ? ' selected' : '' ?>>3（奥）</option>
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
            </div>
        </div>
    </main>

    <script>
(function () {
    const songId = <?= (int)$songId ?>;
    const allMembers = <?= $allMembersJson ?>;
    const cardBorder = '<?= addslashes($cardBorder) ?>';

    const tbody = document.getElementById('memberListBody');
    const emptyMessage = document.getElementById('emptyMessage');

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s == null ? '' : s;
        return div.innerHTML;
    }

    function getMemberIdsInTable() {
        return Array.from(tbody.querySelectorAll('input[name="member_id"]')).map(el => el.value);
    }

    function buildRowHtml(memberId, name, rowNumber, position, isCenter, partDescription) {
        const selEmpty = (!rowNumber || (rowNumber !== 1 && rowNumber !== 2 && rowNumber !== 3)) ? ' selected' : '';
        return '<tr class="member-row border-t border-slate-100 hover:bg-slate-50/50 align-middle">' +
            '<td class="px-3 py-2"><input type="hidden" name="member_id" value="' + escapeHtml(String(memberId)) + '"><span class="font-medium text-slate-800">' + escapeHtml(name) + '</span></td>' +
            '<td class="px-3 py-2"><select name="row_number" class="w-full max-w-[100px] h-9 border ' + cardBorder + ' rounded-lg px-2 text-sm bg-white">' +
                '<option value=""' + selEmpty + '>—</option>' +
                '<option value="1"' + (rowNumber == 1 ? ' selected' : '') + '>1（手前）</option>' +
                '<option value="2"' + (rowNumber == 2 ? ' selected' : '') + '>2</option>' +
                '<option value="3"' + (rowNumber == 3 ? ' selected' : '') + '>3（奥）</option>' +
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
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (!btn) return;
        btn.closest('tr').remove();
        updateEmptyMessage();
    });

    document.getElementById('btnSave').addEventListener('click', function () {
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

        const btn = document.getElementById('btnSave');
        btn.disabled = true;
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
            .finally(function () { btn.disabled = false; });
    });

    updateEmptyMessage();
})();
    </script>
</body>
</html>
