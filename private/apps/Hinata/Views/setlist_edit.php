<?php
/**
 * LIVEセットリスト編集 View（admin / hinata_admin）
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$eventId = (int)($event['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セットリスト編集 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .setlist-row { position: relative; }
        .setlist-row.drag-over { outline: 2px dashed #38bdf8; outline-offset: -2px; border-radius: 0.5rem; }
        .setlist-row.dragging { opacity: 0.4; }
        .drag-handle { cursor: grab; touch-action: none; }
        .drag-handle:active { cursor: grabbing; }

        /* 曲コンボボックス（テキスト検索 + 部分一致絞り込み） */
        .song-combo { position: relative; }
        .song-combo-list {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            width: 100%;
            max-height: 260px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 8px 24px -6px rgba(15,23,42,0.2);
            z-index: 30;
            padding: 4px;
        }
        .song-combo-list.hidden { display: none; }
        .song-grp-label {
            font-size: 9px; font-weight: 800; color: #94a3b8;
            padding: 6px 8px 2px;
        }
        .song-opt {
            padding: 6px 8px; font-size: 12px; border-radius: 6px;
            cursor: pointer; color: #334155;
        }
        .song-opt:hover, .song-opt.active { background: #e0f2fe; color: #0369a1; }
        .song-opt.hidden, .song-grp.hidden { display: none; }

        /* 行間インサーター（ホバー/フォーカスで＋出現） */
        .row-inserter {
            position: absolute;
            left: 0; right: 0;
            bottom: -13px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 2px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.12s;
            z-index: 5;
        }
        .setlist-row:hover > .row-inserter,
        .setlist-row:focus-within > .row-inserter,
        .row-inserter:hover {
            opacity: 1;
            pointer-events: auto;
        }
        .row-inserter::before {
            content: '';
            position: absolute;
            left: 30px; right: 8px; top: 50%;
            height: 2px;
            background: linear-gradient(90deg, #7dd3fc, #7dd3fc 85%, transparent);
            transform: translateY(-50%);
            border-radius: 2px;
        }
        .row-inserter-btn {
            position: relative;
            width: 22px; height: 22px;
            border-radius: 9999px;
            background: var(--hinata-theme, #0ea5e9);
            color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 10px; border: none; cursor: pointer;
            box-shadow: 0 2px 6px rgba(2,132,199,0.35);
            transition: transform 0.1s;
        }
        .row-inserter-btn:hover { transform: scale(1.12); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>
<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <header class="h-14 bg-white border-b border-slate-100 flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
        <div class="flex items-center gap-2 min-w-0">
            <a href="/hinata/setlist.php?event_id=<?= $eventId ?>" class="text-slate-400 hover:text-slate-600 p-2"><i class="fa-solid fa-chevron-left"></i></a>
            <div class="min-w-0">
                <div class="text-[10px] font-black text-slate-400 tracking-wider truncate">セットリスト編集</div>
                <div class="text-sm font-black text-slate-800 truncate"><?= htmlspecialchars($event['event_name'] ?? '') ?></div>
            </div>
        </div>
        <div class="shrink-0 flex items-center gap-2">
            <button id="btnImport" type="button" class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-2 rounded-lg hover:bg-slate-200 transition">
                <i class="fa-solid fa-file-import mr-1"></i>一括インポート
            </button>
            <button id="btnAddRow" type="button" class="text-xs font-bold text-sky-600 bg-sky-50 px-3 py-2 rounded-lg hover:bg-sky-100 transition">
                <i class="fa-solid fa-plus mr-1"></i>追加
            </button>
            <button id="btnSave" type="button" class="text-xs font-bold text-white px-3 py-2 rounded-lg hover:opacity-90 transition" style="background:var(--hinata-theme)">
                <i class="fa-solid fa-check mr-1"></i>保存
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-7xl mx-auto space-y-4">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div id="rows" class="space-y-2"></div>
                <p class="text-[10px] text-slate-400 mt-3">
                    「曲」以外（MC/ブロック）は曲数に含めません。本編/アンコール/Wの区分は曲・MC・ブロックのいずれにも設定できます。
                </p>
            </div>
        </div>
    </div>
</main>

<!-- 一括インポート モーダル -->
<div id="importModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/40" data-import-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="text-sm font-black text-slate-800"><i class="fa-solid fa-file-import mr-2 text-sky-500"></i>セットリスト一括インポート</div>
                <button type="button" class="text-slate-400 hover:text-slate-600 p-1" data-import-close aria-label="閉じる"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-5 overflow-y-auto space-y-3">
                <div class="text-xs text-slate-500 leading-relaxed bg-slate-50 rounded-lg p-3">
                    タブ区切り・1行1エントリ。列: <b class="text-slate-700">タイプ / 内容 / センター / 区分</b><br>
                    ・タイプ: 曲 / MC / ブロック（省略時は「曲」）<br>
                    ・内容: 曲名（ブロックは 告知/ダンスセッション/セッション/その他 または自由名）<br>
                    ・センター: 「、」区切りで複数可（ダブルセンター対応・曲のみ）<br>
                    ・区分: 本編 / アンコール / W（省略時は本編）<br>
                    曲名は<b class="text-slate-700">完全一致優先＋部分一致</b>で自動照合。未一致の曲は未選択の行として追加されます。
                </div>
                <textarea id="importTextarea" rows="10" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white" style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace" placeholder="ここにTSVを貼り付け（例: 曲［タブ］月と星が踊るMidnight［タブ］坂井新奈［タブ］本編）"></textarea>
                <div id="importReport" class="hidden text-xs rounded-lg border p-3 max-h-40 overflow-y-auto"></div>
            </div>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 shrink-0">
                <button type="button" class="text-xs font-bold text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-100" data-import-close>キャンセル</button>
                <button id="btnImportApply" type="button" class="text-xs font-bold text-white px-4 py-2 rounded-lg hover:opacity-90 transition" style="background:var(--hinata-theme)"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>解析して反映（置き換え）</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/core.js?v=2"></script>
<script>
    const eventId = <?= json_encode($eventId, JSON_UNESCAPED_UNICODE) ?>;
    const existing = <?= json_encode($setlist ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const allSongs = <?= json_encode($allSongs ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const allMembers = <?= json_encode($allMembers ?? [], JSON_UNESCAPED_UNICODE) ?>;

    const blockKindOptions = {
        announcement: '告知',
        dance_session: 'ダンスセッション',
        session_other: 'セッション',
        other: 'その他'
    };

    function _esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML; }
    const generationLabels = {
        1: '1期生',
        2: '2期生',
        3: '3期生',
        4: '4期生',
        5: '5期生',
        99: 'その他'
    };

    function _sortedMembers() {
        return (allMembers || []).slice().sort((a, b) => {
            const ga = parseInt(a.generation || 0, 10);
            const gb = parseInt(b.generation || 0, 10);
            if (ga !== gb) return ga - gb;
            const ka = (a.kana || a.name || '').toString();
            const kb = (b.kana || b.name || '').toString();
            return ka.localeCompare(kb, 'ja');
        });
    }

    function _centerMemberOptionsMulti(selectedIds) {
        const selected = new Set((selectedIds || []).map(v => String(v)));
        const byGen = new Map();
        _sortedMembers().forEach(m => {
            const g = parseInt(m.generation || 99, 10) || 99;
            if (!byGen.has(g)) byGen.set(g, []);
            byGen.get(g).push(m);
        });
        const gens = Array.from(byGen.keys()).sort((a, b) => a - b);
        let html = '';
        gens.forEach(g => {
            const label = generationLabels[g] || `${g}期生`;
            html += `<optgroup label="${_esc(label)}">`;
            byGen.get(g).forEach(m => {
                const sel = selected.has(String(m.id)) ? ' selected' : '';
                html += `<option value="${m.id}"${sel}>${_esc(m.name)}</option>`;
            });
            html += `</optgroup>`;
        });
        return html;
    }

    function _centerSelectHtml(selectedId) {
        const opts = `<option value="">-- 未設定 --</option>` + _centerMemberOptionsMulti(selectedId ? [selectedId] : []);
        return `<select class="center-select-item w-48 border border-slate-200 rounded-lg px-2 py-1.5 text-xs bg-white">${opts}</select>`;
    }

    function _songById(id) {
        const t = String(id || '');
        if (!t) return null;
        return (allSongs || []).find(x => String(x.id) === t) || null;
    }
    function _songTitleById(id) {
        const s = _songById(id);
        return s ? String(s.title || '') : '';
    }

    // 曲コンボボックスのドロップダウン一覧（リリース単位でグループ化）
    function _songComboListHtml() {
        const singles = new Map();  // release_id -> { label, songs: [] }
        const others = new Map();

        (allSongs || []).forEach(s => {
            const releaseId = String(s.release_id || '');
            const isSingle = String(s.release_type || '').toLowerCase() === 'single';
            const n = (s.release_number !== null && s.release_number !== undefined) ? String(s.release_number) : '';
            const releaseLabel = isSingle
                ? `シングル ${n ? (n + ' ') : ''}${(s.release_title || '').toString()}`
                : `${(s.release_title || '').toString()}`;
            const bucket = isSingle ? singles : others;
            if (!bucket.has(releaseId)) bucket.set(releaseId, { label: releaseLabel, songs: [] });
            bucket.get(releaseId).songs.push(s);
        });

        function bucketHtml(bucket) {
            let html = '';
            Array.from(bucket.values()).forEach(g => {
                html += `<div class="song-grp"><div class="song-grp-label">${_esc(g.label)}</div>`;
                g.songs.forEach(s => {
                    const search = `${(s.title || '')} ${(s.release_title || '')}`.toLowerCase();
                    html += `<div class="song-opt" data-id="${s.id}" data-title="${_esc(s.title)}" data-search="${_esc(search)}">${_esc(s.title)}</div>`;
                });
                html += `</div>`;
            });
            return html;
        }

        let html = '';
        if (singles.size) html += bucketHtml(singles);
        if (others.size) html += bucketHtml(others);
        return html;
    }
    function _blockKindOptions(selected) {
        let opts = '';
        Object.keys(blockKindOptions).forEach(k => { opts += `<option value="${_esc(k)}"${k === selected ? ' selected' : ''}>${_esc(blockKindOptions[k])}</option>`; });
        return opts;
    }

    function normalizeEncore(v) {
        const n = parseInt(v, 10);
        if (n === 2) return 2;
        if (n === 1) return 1;
        return 0;
    }

    function rowHtml(index, item) {
        item = item || {};
        const t = item.entry_type || 'song';
        const songId = item.song_id || '';
        const encore = normalizeEncore(item.encore);
        const label = item.label || '';
        const blockKind = item.block_kind || 'session_other';
        const centerIds = Array.isArray(item.center_member_ids) ? item.center_member_ids
            : (item.center_member_id ? [item.center_member_id] : []);
        const centerIdsNorm = (centerIds || []).map(v => parseInt(v, 10)).filter(v => v > 0);
        if (!centerIdsNorm.length) centerIdsNorm.push(null);
        const centerFirst = centerIdsNorm[0] || null;
        const centerRest = centerIdsNorm.slice(1);

        return `
        <div class="setlist-row flex flex-col gap-2 p-2 bg-slate-50 rounded-lg" data-index="${index}">
          <div class="flex flex-wrap items-start gap-2">
            <span class="drag-handle text-[10px] text-slate-400 w-5 text-right shrink-0 pt-2 select-none" draggable="true" title="ドラッグで移動"><i class="fa-solid fa-grip-vertical text-[8px] text-slate-300 mr-0.5"></i>${index + 1}</span>
            <select class="setlist-type-select w-24 border border-slate-200 rounded-lg px-2 py-1.5 text-xs bg-white shrink-0">
              <option value="song"${t === 'song' ? ' selected' : ''}>曲</option>
              <option value="mc"${t === 'mc' ? ' selected' : ''}>MC</option>
              <option value="block"${t === 'block' ? ' selected' : ''}>ブロック</option>
            </select>

            <select class="setlist-encore-select w-[7.5rem] shrink-0 border border-slate-200 rounded-lg px-2 py-1.5 text-[10px] bg-white min-h-[2.25rem]" title="セクション（本編／アンコール）">
              <option value="0"${encore === 0 ? ' selected' : ''}>本編</option>
              <option value="1"${encore === 1 ? ' selected' : ''}>アンコール</option>
              <option value="2"${encore === 2 ? ' selected' : ''}>Wアンコール</option>
            </select>

            <div class="row-song flex flex-wrap items-center gap-2 flex-1 min-w-0 pb-0.5">
              <div class="song-combo flex-1 min-w-[12rem]">
                <input type="hidden" class="setlist-song-id" value="${songId}">
                <input type="text" class="setlist-song-search w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs bg-white min-h-[2.25rem]" placeholder="曲名で検索・選択" value="${_esc(_songTitleById(songId))}" autocomplete="off">
                <div class="song-combo-list hidden">${_songComboListHtml()}</div>
              </div>
              <div class="setlist-centers flex flex-col gap-1 shrink-0">
                <div class="center-row flex items-center gap-1">
                  ${_centerSelectHtml(centerFirst)}
                  <button type="button" class="btnAddCenter text-[10px] font-bold text-sky-600 bg-sky-50 px-2 py-1 rounded-lg hover:bg-sky-100 transition">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>
                ${centerRest.map(mid => `
                  <div class="center-row flex items-center gap-1">
                    ${_centerSelectHtml(mid)}
                    <button type="button" class="btnRemoveCenter text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg hover:bg-slate-50 transition">
                      <i class="fa-solid fa-minus"></i>
                    </button>
                  </div>
                `).join('')}
              </div>
            </div>

            <div class="row-mc hidden flex-1 min-w-[16rem]">
              <input type="text" class="setlist-label-input w-full border border-slate-200 rounded-lg px-2 py-2 text-xs bg-white" placeholder="MC（任意のラベル）" value="${_esc(label)}">
            </div>

            <div class="row-block hidden flex flex-wrap items-center gap-2 flex-1 min-w-[16rem]">
              <select class="setlist-block-kind w-40 border border-slate-200 rounded-lg px-2 py-2 text-xs bg-white">${_blockKindOptions(blockKind)}</select>
              <input type="text" class="setlist-label-input flex-1 min-w-[12rem] border border-slate-200 rounded-lg px-2 py-2 text-xs bg-white" placeholder="告知/セッション名など（任意）" value="${_esc(label)}">
            </div>

            <button type="button" class="btn-remove ml-auto text-slate-300 hover:text-red-400 text-xs shrink-0" title="削除"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="row-inserter">
            <button type="button" class="row-inserter-btn" title="ここに行を挿入"><i class="fa-solid fa-plus"></i></button>
          </div>
        </div>`;
    }

    function updateRowVisibility(row) {
        const tSel = row.querySelector('.setlist-type-select');
        const t = tSel ? tSel.value : 'song';
        row.querySelector('.row-song').classList.toggle('hidden', t !== 'song');
        row.querySelector('.row-mc').classList.toggle('hidden', t !== 'mc');
        row.querySelector('.row-block').classList.toggle('hidden', t !== 'block');
    }

    function bindRow(row) {
        row.querySelector('.btn-remove').addEventListener('click', () => { row.remove(); renumber(); });
        row.querySelector('.setlist-type-select').addEventListener('change', () => updateRowVisibility(row));

        const inserterBtn = row.querySelector('.row-inserter-btn');
        if (inserterBtn) {
            inserterBtn.addEventListener('click', () => insertRowAfter(row, { entry_type: 'song' }));
        }

        const combo = row.querySelector('.song-combo');
        if (combo) bindSongCombo(combo);

        const handle = row.querySelector('.drag-handle');
        if (handle) {
            handle.addEventListener('dragstart', handleDragStart);
            handle.addEventListener('dragend', handleDragEnd);
        }
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('drop', handleDrop);

        const btnAddCenter = row.querySelector('.btnAddCenter');
        if (btnAddCenter) {
            btnAddCenter.addEventListener('click', () => {
                const wrap = row.querySelector('.setlist-centers');
                if (!wrap) return;
                const div = document.createElement('div');
                div.className = 'center-row flex items-center gap-1';
                div.innerHTML = `
                  ${_centerSelectHtml(null)}
                  <button type="button" class="btnRemoveCenter text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-minus"></i>
                  </button>
                `;
                wrap.appendChild(div);
                const rm = div.querySelector('.btnRemoveCenter');
                if (rm) rm.addEventListener('click', () => div.remove());
            });
        }
        row.querySelectorAll('.btnRemoveCenter').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('.center-row')?.remove());
        });
        updateRowVisibility(row);
    }

    function renumber() {
        document.querySelectorAll('#rows .setlist-row').forEach((r, i) => {
            const handle = r.querySelector('.drag-handle');
            if (handle) handle.innerHTML = '<i class="fa-solid fa-grip-vertical text-[8px] text-slate-300 mr-0.5"></i>' + String(i + 1);
        });
    }

    function addRow(item) {
        const rows = document.getElementById('rows');
        const idx = rows.children.length;
        const wrap = document.createElement('div');
        wrap.innerHTML = rowHtml(idx, item);
        const row = wrap.firstElementChild;
        rows.appendChild(row);
        bindRow(row);
        renumber();
    }

    function bindSongCombo(combo) {
        const hidden = combo.querySelector('.setlist-song-id');
        const input = combo.querySelector('.setlist-song-search');
        const list = combo.querySelector('.song-combo-list');
        if (!hidden || !input || !list) return;

        function filter(q) {
            const query = (q || '').trim().toLowerCase();
            list.querySelectorAll('.song-opt').forEach(opt => {
                const hay = opt.dataset.search || '';
                opt.classList.toggle('hidden', query !== '' && !hay.includes(query));
            });
            list.querySelectorAll('.song-grp').forEach(grp => {
                grp.classList.toggle('hidden', !grp.querySelector('.song-opt:not(.hidden)'));
            });
        }
        function open() { filter(input.value); list.classList.remove('hidden'); }
        function close() { list.classList.add('hidden'); }
        // 表示テキストと hidden の song_id を整合させる
        function reconcile() {
            const val = input.value.trim();
            if (val === '') { hidden.value = ''; return; }
            const exact = (allSongs || []).find(x => String(x.title || '').trim() === val);
            if (exact) { hidden.value = String(exact.id); input.value = exact.title || ''; return; }
            const cur = _songById(hidden.value);
            if (cur) { input.value = cur.title || ''; } else { input.value = ''; hidden.value = ''; }
        }

        input.addEventListener('focus', open);
        input.addEventListener('click', open);
        input.addEventListener('input', () => {
            filter(input.value);
            list.classList.remove('hidden');
            if (input.value.trim() === '') hidden.value = '';
        });
        input.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
        input.addEventListener('blur', () => { setTimeout(() => { reconcile(); close(); }, 150); });
        // mousedown + preventDefault で blur より先に選択を確定
        list.addEventListener('mousedown', (e) => {
            const opt = e.target.closest('.song-opt');
            if (!opt) return;
            e.preventDefault();
            hidden.value = opt.dataset.id || '';
            input.value = opt.dataset.title || '';
            close();
        });
    }

    function insertRowAfter(refRow, item) {
        const rows = document.getElementById('rows');
        const idx = rows.children.length;
        const wrap = document.createElement('div');
        wrap.innerHTML = rowHtml(idx, item);
        const row = wrap.firstElementChild;
        const next = refRow.nextElementSibling;
        if (next) {
            rows.insertBefore(row, next);
        } else {
            rows.appendChild(row);
        }
        bindRow(row);
        renumber();
    }

    let dragSrcRow = null;
    function handleDragStart(e) {
        dragSrcRow = this.closest('.setlist-row');
        if (dragSrcRow) dragSrcRow.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    }
    function handleDragEnd() {
        if (dragSrcRow) dragSrcRow.classList.remove('dragging');
        document.querySelectorAll('#rows .setlist-row').forEach(r => r.classList.remove('drag-over'));
        dragSrcRow = null;
    }
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (this !== dragSrcRow) {
            document.querySelectorAll('#rows .setlist-row').forEach(r => r.classList.remove('drag-over'));
            this.classList.add('drag-over');
        }
    }
    function handleDrop(e) {
        e.preventDefault();
        if (!dragSrcRow || dragSrcRow === this) return;
        const rows = document.getElementById('rows');
        const allRows = Array.from(rows.children);
        const srcIdx = allRows.indexOf(dragSrcRow);
        const dstIdx = allRows.indexOf(this);
        if (srcIdx < dstIdx) {
            rows.insertBefore(dragSrcRow, this.nextElementSibling);
        } else {
            rows.insertBefore(dragSrcRow, this);
        }
        this.classList.remove('drag-over');
        renumber();
    }

    function collect() {
        const items = [];
        document.querySelectorAll('#rows .setlist-row').forEach((row, i) => {
            const t = row.querySelector('.setlist-type-select').value;
            const encoreSel = row.querySelector('.setlist-encore-select');
            const encore = normalizeEncore(encoreSel ? encoreSel.value : 0);
            if (t === 'song') {
                const s = row.querySelector('.setlist-song-id');
                if (!s.value) return;
                const it = { entry_type: 'song', sort_order: i + 1, song_id: parseInt(s.value, 10), encore: encore };
                const mids = Array.from(row.querySelectorAll('.center-select-item'))
                    .map(sel => parseInt(sel.value || '0', 10))
                    .filter(v => v > 0);
                if (mids.length) it.center_member_ids = Array.from(new Set(mids)); // ③ 複数センター
                items.push(it);
            } else if (t === 'mc') {
                const labelEl = row.querySelector('.row-mc .setlist-label-input');
                const label = ((labelEl ? labelEl.value : '') || '').trim();
                items.push({ entry_type: 'mc', sort_order: i + 1, encore: encore, label: label || null });
            } else if (t === 'block') {
                const kind = row.querySelector('.setlist-block-kind').value;
                const labelEl = row.querySelector('.row-block .setlist-label-input');
                const label = ((labelEl ? labelEl.value : '') || '').trim();
                items.push({ entry_type: 'block', sort_order: i + 1, encore: encore, block_kind: kind, label: label || null });
            }
        });
        return items;
    }

    // ---- 一括インポート（TSV） ----
    function _importNormType(raw) {
        const s = String(raw || '').trim();
        const low = s.toLowerCase();
        if (low === 'mc' || s === 'ＭＣ' || s === 'エムシー') return 'mc';
        if (s === 'ブロック' || low === 'block' || s === 'ﾌﾞﾛｯｸ') return 'block';
        if (s === '曲' || s === '楽曲' || low === 'song' || s === '♪') return 'song';
        return null;
    }
    function _importNormSection(raw) {
        const s = String(raw || '').trim();
        const low = s.toLowerCase();
        if (s === '') return 0;
        if (s.indexOf('ダブル') !== -1 || low === 'w' || s === 'ｗ' || low === 'wアンコール' || low === 'w encore' || low === 'wencore' || s === '2') return 2;
        if (s.indexOf('アンコール') !== -1 || low === 'en' || low === 'encore' || s === '1') return 1;
        return 0; // 本編 / 0 / 空
    }
    function _importMatchSong(title) {
        const q = String(title || '').trim();
        if (!q) return { id: null, reason: 'empty' };
        const ql = q.toLowerCase();
        const exact = (allSongs || []).filter(s => String(s.title || '').trim() === q);
        if (exact.length === 1) return { id: parseInt(exact[0].id, 10) };
        if (exact.length > 1) return { id: null, reason: 'ambiguous' };
        const partial = (allSongs || []).filter(s => String(s.title || '').toLowerCase().indexOf(ql) !== -1);
        if (partial.length === 1) return { id: parseInt(partial[0].id, 10) };
        if (partial.length > 1) return { id: null, reason: 'ambiguous' };
        return { id: null, reason: 'notfound' };
    }
    function _importMatchMember(name) {
        const q = String(name || '').trim();
        if (!q) return null;
        const exact = (allMembers || []).find(m => String(m.name || '').trim() === q);
        if (exact) return parseInt(exact.id, 10);
        const partial = (allMembers || []).filter(m => String(m.name || '').indexOf(q) !== -1);
        if (partial.length === 1) return parseInt(partial[0].id, 10);
        return null; // 見つからない/複数該当
    }
    function _importBlockFromContent(content) {
        const c = String(content || '').trim();
        const map = { '告知': 'announcement', 'ダンスセッション': 'dance_session', 'セッション': 'session_other', 'その他': 'other' };
        if (map[c]) return { kind: map[c], label: '' };
        if (['announcement', 'dance_session', 'session_other', 'other'].indexOf(c) !== -1) return { kind: c, label: '' };
        return { kind: 'session_other', label: c }; // 自由名
    }

    function parseSetlistTsv(text) {
        const lines = String(text || '').replace(/\r\n?/g, '\n').split('\n');
        const items = [];
        const errors = [];
        lines.forEach((raw, i) => {
            const lineNo = i + 1;
            if (String(raw).trim() === '') return;
            const cols = String(raw).replace(/\s+$/, '').split('\t');
            const col0 = (cols[0] || '').trim();
            let type = _importNormType(col0);
            let content, centersRaw, sectionRaw;
            if (type === null) {
                // タイプ列が無い＝1列目を曲名扱い（曲名のみ形式にもフォールバック対応）
                type = 'song';
                content = col0;
                centersRaw = (cols[1] || '');
                sectionRaw = (cols[2] || '');
            } else {
                content = (cols[1] || '').trim();
                centersRaw = (cols[2] || '');
                sectionRaw = (cols[3] || '');
            }
            const encore = _importNormSection(sectionRaw);

            if (type === 'song') {
                const item = { entry_type: 'song', encore: encore };
                const centerIds = [];
                String(centersRaw || '').split(/[、,\/]/).map(x => x.trim()).filter(Boolean).forEach(nm => {
                    const mid = _importMatchMember(nm);
                    if (mid) centerIds.push(mid);
                    else errors.push(`${lineNo}行目: センター「${nm}」が見つからず無視しました`);
                });
                if (centerIds.length) item.center_member_ids = Array.from(new Set(centerIds));
                const m = _importMatchSong(content);
                if (m.id) {
                    item.song_id = m.id;
                } else {
                    item._rawTitle = content;
                    const why = m.reason === 'ambiguous' ? '複数該当' : (m.reason === 'empty' ? '曲名が空' : '見つからず');
                    errors.push(`${lineNo}行目: 曲「${content}」が${why}、未選択の行として追加しました`);
                }
                items.push(item);
            } else if (type === 'mc') {
                items.push({ entry_type: 'mc', encore: encore, label: content || null });
            } else if (type === 'block') {
                const bk = _importBlockFromContent(content);
                items.push({ entry_type: 'block', encore: encore, block_kind: bk.kind, label: bk.label || null });
            }
        });
        return { items, errors };
    }

    function applyImportedItems(items) {
        const rows = document.getElementById('rows');
        rows.innerHTML = ''; // 置き換え
        items.forEach(it => {
            addRow(it);
            if (it.entry_type === 'song' && !it.song_id && it._rawTitle) {
                const last = rows.lastElementChild;
                const inp = last && last.querySelector('.setlist-song-search');
                if (inp) inp.value = it._rawTitle;
            }
        });
        renumber();
    }

    (function initSetlistImport() {
        const modal = document.getElementById('importModal');
        const openBtn = document.getElementById('btnImport');
        const applyBtn = document.getElementById('btnImportApply');
        const ta = document.getElementById('importTextarea');
        const report = document.getElementById('importReport');
        if (!modal || !openBtn || !applyBtn || !ta || !report) return;

        function open() {
            modal.classList.remove('hidden');
            report.classList.add('hidden');
            report.innerHTML = '';
            setTimeout(() => ta.focus(), 50);
        }
        function close() { modal.classList.add('hidden'); }

        openBtn.addEventListener('click', open);
        modal.querySelectorAll('[data-import-close]').forEach(el => el.addEventListener('click', close));
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

        applyBtn.addEventListener('click', () => {
            const parsed = parseSetlistTsv(ta.value);
            if (!parsed.items.length) {
                report.className = 'text-xs rounded-lg border p-3 border-red-200 bg-red-50 text-red-700';
                report.classList.remove('hidden');
                report.innerHTML = '有効な行がありません。フォーマットを確認してください。';
                return;
            }
            applyImportedItems(parsed.items);
            let html = `<div class="font-bold text-slate-700 mb-1">${parsed.items.length} 行をフォームに反映しました（既存を置き換え）。</div>`;
            if (parsed.errors.length) {
                html += `<div class="font-bold text-amber-700 mt-1">確認 (${parsed.errors.length} 件):</div>`
                    + '<ul class="list-disc pl-4 mt-1 space-y-0.5">'
                    + parsed.errors.map(e => `<li>${_esc(e)}</li>`).join('')
                    + '</ul>';
                report.className = 'text-xs rounded-lg border p-3 border-amber-200 bg-amber-50 text-amber-800';
                report.classList.remove('hidden');
                report.innerHTML = html;
                if (window.App && App.toast) App.toast('反映しました（要確認あり）', 2500);
            } else {
                report.className = 'text-xs rounded-lg border p-3 border-emerald-200 bg-emerald-50 text-emerald-700';
                report.classList.remove('hidden');
                report.innerHTML = html;
                if (window.App && App.toast) App.toast('反映しました', 2000);
                setTimeout(close, 900);
            }
        });
    })();

    document.getElementById('btnAddRow').addEventListener('click', () => addRow({ entry_type: 'song' }));
    document.getElementById('btnSave').addEventListener('click', () => {
        const items = collect();
        const shadow = collectShadowNarration();
        const jobs = [];
        if (shadow) {
            jobs.push(
                App.post('/hinata/api/save_event_shadow_narration.php', shadow).then((res) => {
                    if (!res || res.status !== 'success') throw new Error((res && res.message) ? res.message : '影ナレ保存エラー');
                })
            );
        }
        jobs.push(
            App.post('/hinata/api/save_setlist.php', { event_id: eventId, items }).then((res) => {
                if (!res || res.status !== 'success') throw new Error((res && res.message) ? res.message : 'セットリスト保存エラー');
            })
        );
        Promise.all(jobs)
            .then(() => {
                App.toast('保存しました', 2500);
                window.location.href = '/hinata/setlist.php?event_id=' + eventId;
            })
            .catch((e) => {
                App.toast(e && e.message ? e.message : '通信エラー', 2500);
            });
    });

    if (existing.length) existing.forEach(it => addRow(it));
    else addRow({ entry_type: 'song' });

    // ---- ④ 影ナレ編集（イベントに1つ、複数メンバー）----
    (function initShadowNarrationUi() {
        const box = document.createElement('div');
        box.className = 'bg-white rounded-2xl border border-slate-100 shadow-sm p-4';
        box.innerHTML = `
            <div class="flex items-center gap-2 mb-3">
                <i class="fa-solid fa-microphone-lines text-slate-500"></i>
                <div class="text-xs font-black text-slate-600 tracking-wider">影ナレ</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <div class="text-[10px] font-bold text-slate-500 mb-1">メンバー（複数選択）</div>
                    <select id="shadowMemberSelect" multiple size="8" class="w-full border border-slate-200 rounded-xl px-2 py-2 text-xs bg-white">
                        ${_centerMemberOptionsMulti([])}
                    </select>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-500 mb-1">メモ（任意）</div>
                    <textarea id="shadowMemo" rows="6" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs bg-white" placeholder="例）開演直後：○○、△△"></textarea>
                </div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">影ナレはイベントに1回のみ紐づきます。</p>
        `;
        const root = document.querySelector('.max-w-7xl');
        const card = root ? root.querySelector('.bg-white.rounded-2xl') : null;
        if (card && card.parentElement) card.parentElement.insertBefore(box, card);

        fetch('/hinata/api/get_event_shadow_narration.php?event_id=' + eventId)
            .then(r => r.json())
            .then(res => {
                if (!res || res.status !== 'success') return;
                const mids = (res.data && res.data.member_ids) ? res.data.member_ids : [];
                const memo = (res.data && res.data.memo) ? res.data.memo : '';
                const sel = document.getElementById('shadowMemberSelect');
                if (sel && Array.isArray(mids)) {
                    const set = new Set(mids.map(v => String(v)));
                    Array.from(sel.options).forEach(o => { o.selected = set.has(String(o.value)); });
                }
                const ta = document.getElementById('shadowMemo');
                if (ta) ta.value = memo || '';
            });
    })();

    function collectShadowNarration() {
        const sel = document.getElementById('shadowMemberSelect');
        const memoEl = document.getElementById('shadowMemo');
        if (!sel) return null;
        const memberIds = Array.from(sel.selectedOptions || []).map(o => parseInt(o.value, 10)).filter(v => v > 0);
        const memo = memoEl ? (memoEl.value || '').trim() : '';
        // 何も触っていない場合も「保存で同期」したいので、常に送る
        return { event_id: eventId, member_ids: memberIds, memo: memo || null };
    }
</script>
</body>
</html>

