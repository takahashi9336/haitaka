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
                    「曲」以外（MC/ブロック）は曲数に含めません。本編/アンコール/Wは「曲」行のみ有効です。
                </p>
            </div>
        </div>
    </div>
</main>

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

    function _songOptions(selectedId) {
        const idStr = String(selectedId || '');
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

        function bucketToOptgroups(bucket) {
            const groups = Array.from(bucket.values());
            // 直近リリースが上に来るよう、allSongs の順序（release_date DESC）を尊重して label の初出順で並ぶ想定
            let html = '';
            groups.forEach(g => {
                html += `<optgroup label="${_esc(g.label)}">`;
                g.songs.forEach(s => {
                    const sel = (String(s.id) === idStr) ? ' selected' : '';
                    html += `<option value="${s.id}"${sel}>${_esc(s.title)}</option>`;
                });
                html += `</optgroup>`;
            });
            return html;
        }

        let opts = '<option value="">-- 楽曲を選択 --</option>';
        if (singles.size) opts += bucketToOptgroups(singles);      // ① シングル帯（optgroup）
        if (others.size) opts += bucketToOptgroups(others);
        return opts;
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
            <span class="text-[10px] text-slate-400 w-5 text-right shrink-0 pt-2">${index + 1}</span>
            <select class="setlist-type-select w-24 border border-slate-200 rounded-lg px-2 py-1.5 text-xs bg-white shrink-0">
              <option value="song"${t === 'song' ? ' selected' : ''}>曲</option>
              <option value="mc"${t === 'mc' ? ' selected' : ''}>MC</option>
              <option value="block"${t === 'block' ? ' selected' : ''}>ブロック</option>
            </select>

            <div class="row-song flex flex-nowrap items-center gap-2 flex-1 min-w-0 overflow-x-auto pb-0.5">
              <select class="setlist-song-select flex-1 min-w-[12rem] border border-slate-200 rounded-lg px-2 py-1.5 text-xs bg-white min-h-[2.25rem]">${_songOptions(songId)}</select>
              <select class="setlist-encore-select w-[8.5rem] shrink-0 border border-slate-200 rounded-lg px-2 py-1.5 text-[10px] bg-white min-h-[2.25rem]">
                <option value="0"${encore === 0 ? ' selected' : ''}>本編</option>
                <option value="1"${encore === 1 ? ' selected' : ''}>アンコール</option>
                <option value="2"${encore === 2 ? ' selected' : ''}>Wアンコール</option>
              </select>
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

            <button type="button" class="btnRemove ml-auto text-slate-300 hover:text-red-400 text-xs shrink-0"><i class="fa-solid fa-xmark"></i></button>
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
        row.querySelector('.btnRemove').addEventListener('click', () => { row.remove(); renumber(); });
        row.querySelector('.setlist-type-select').addEventListener('change', () => updateRowVisibility(row));
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
            const num = r.querySelector('span');
            if (num) num.textContent = String(i + 1);
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

    function collect() {
        const items = [];
        document.querySelectorAll('#rows .setlist-row').forEach((row, i) => {
            const t = row.querySelector('.setlist-type-select').value;
            if (t === 'song') {
                const s = row.querySelector('.setlist-song-select');
                if (!s.value) return;
                const encoreSel = row.querySelector('.setlist-encore-select');
                const it = { entry_type: 'song', sort_order: i + 1, song_id: parseInt(s.value, 10), encore: normalizeEncore(encoreSel ? encoreSel.value : 0) };
                const mids = Array.from(row.querySelectorAll('.center-select-item'))
                    .map(sel => parseInt(sel.value || '0', 10))
                    .filter(v => v > 0);
                if (mids.length) it.center_member_ids = Array.from(new Set(mids)); // ③ 複数センター
                items.push(it);
            } else if (t === 'mc') {
                const label = (row.querySelector('.setlist-label-input').value || '').trim();
                items.push({ entry_type: 'mc', sort_order: i + 1, label: label || null });
            } else if (t === 'block') {
                const kind = row.querySelector('.setlist-block-kind').value;
                const label = (row.querySelector('.setlist-label-input').value || '').trim();
                items.push({ entry_type: 'block', sort_order: i + 1, block_kind: kind, label: label || null });
            }
        });
        return items;
    }

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

