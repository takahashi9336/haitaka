<?php
/**
 * 初参戦ライブガイド 楽曲管理 View（管理者専用）
 * イベント別に候補曲を追加し、出る確度を選択
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ライブガイド楽曲管理 - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-16 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-music text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ライブガイド楽曲管理</h1>
            </div>
            <div class="flex gap-2">
                <a href="/hinata/live_guide.php" class="text-xs font-bold text-slate-500 hover:text-slate-700 px-4 py-2">初参戦ガイドを見る</a>
                <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>ポータルへ戻る</a>
            </div>
        </header>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full space-y-6">
            <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                <label class="block text-[10px] font-black text-slate-400 mb-2 tracking-wider">イベントを選択</label>
                <select id="eventSelect" class="w-full h-12 border border-slate-200 rounded-lg px-4 text-sm bg-white outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-200' ?>">
                    <option value="">-- イベントを選択 --</option>
                    <?php foreach ($events as $ev): ?>
                    <option value="<?= (int)$ev['id'] ?>"><?= htmlspecialchars($ev['event_date']) ?> <?= htmlspecialchars($ev['event_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </section>

            <div id="guideContent" class="hidden space-y-6">
                <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                    <h3 class="text-sm font-black text-slate-600 mb-4">登録済み候補曲</h3>
                    <div id="currentSongsList" class="space-y-4">
                        <?php foreach (['certain', 'high', 'possible'] as $lik): ?>
                        <div id="lik-group-<?= $lik ?>" data-likelihood="<?= $lik ?>">
                            <h4 class="text-[10px] font-black text-slate-400 mb-2"><?= htmlspecialchars($likelihoodLabels[$lik]) ?></h4>
                            <div class="space-y-2" id="songs-<?= $lik ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p id="noSongsMsg" class="text-slate-400 text-sm py-4 hidden">候補曲が登録されていません。下から楽曲を追加してください。</p>
                </section>

                <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                    <h3 class="text-sm font-black text-slate-600 mb-4">楽曲を追加</h3>
                    <div class="mb-4">
                        <input type="text" id="searchSong" placeholder="楽曲・リリース名で検索..." class="w-full h-10 px-4 border <?= $cardBorder ?> rounded-lg text-sm outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-200' ?>">
                    </div>
                    <div id="songsByRelease" class="space-y-4 max-h-80 overflow-y-auto">
                        <?php foreach ($releasesWithSongs as $rel): ?>
                        <div class="release-group" data-release-title="<?= htmlspecialchars($rel['title'] ?? '') ?>" data-release-number="<?= htmlspecialchars($rel['release_number'] ?? '') ?>">
                            <h4 class="text-xs font-black text-sky-600 tracking-wider mb-2"><?= htmlspecialchars($rel['release_number'] ?? '') ?> <?= htmlspecialchars($rel['title'] ?? '') ?></h4>
                            <div class="space-y-1">
                                <?php foreach ($rel['songs'] as $s): ?>
                                <div class="song-row flex items-center justify-between gap-2 p-2 rounded-lg hover:bg-sky-50" data-song-id="<?= (int)$s['id'] ?>" data-song-title="<?= htmlspecialchars($s['title'] ?? '') ?>">
                                    <span class="text-sm text-slate-800"><?= htmlspecialchars($s['title'] ?? '') ?></span>
                                    <div class="flex gap-1 shrink-0">
                                        <select class="add-likelihood border border-slate-200 rounded px-2 py-1 text-xs">
                                            <option value="certain">ほぼ確実</option>
                                            <option value="high">高確率</option>
                                            <option value="possible">可能性あり</option>
                                        </select>
                                        <button type="button" class="btn-add-song h-8 px-3 rounded-lg text-xs font-bold bg-sky-500 hover:bg-sky-600 text-white" data-song-id="<?= (int)$s['id'] ?>">追加</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button id="btnSave" class="px-6 py-3 bg-slate-800 text-white rounded-lg font-black text-sm shadow-lg hover:bg-slate-700 transition">
                        <i class="fa-solid fa-check mr-2"></i>保存
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const eventSelect = document.getElementById('eventSelect');
        const guideContent = document.getElementById('guideContent');
        const currentSongs = { certain: [], high: [], possible: [] };
        let currentEventId = 0;

        function applySongFilter() {
            const q = (document.getElementById('searchSong').value || '').trim().toLowerCase();
            document.querySelectorAll('.release-group').forEach(function(grp) {
                const rt = (grp.getAttribute('data-release-title') || '').toLowerCase();
                const rn = (grp.getAttribute('data-release-number') || '').toLowerCase();
                let visible = false;
                grp.querySelectorAll('.song-row').forEach(function(row) {
                    const st = (row.getAttribute('data-song-title') || '').toLowerCase();
                    const match = !q || rt.includes(q) || rn.includes(q) || st.includes(q);
                    row.style.display = match ? '' : 'none';
                    if (match) visible = true;
                });
                grp.style.display = visible ? '' : 'none';
            });
        }
        document.getElementById('searchSong').addEventListener('input', applySongFilter);

        function collectItems() {
            const items = [];
            ['certain', 'high', 'possible'].forEach(function(lik) {
                document.querySelectorAll('#songs-' + lik + ' [data-song-id]').forEach(function(row, i) {
                    const sel = row.querySelector('.lik-select');
                    const l = sel ? sel.value : lik;
                    items.push({
                        song_id: parseInt(row.getAttribute('data-song-id'), 10),
                        likelihood: l,
                        sort_order: items.length + 1
                    });
                });
            });
            return items;
        }

        async function loadGuide() {
            if (!currentEventId) return;
            try {
                const res = await fetch('/hinata/api/get_live_guide.php?event_id=' + currentEventId);
                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || '取得失敗');
                const data = json.data;
                currentSongs.certain = (data.songs_by_likelihood && data.songs_by_likelihood.certain) || [];
                currentSongs.high = (data.songs_by_likelihood && data.songs_by_likelihood.high) || [];
                currentSongs.possible = (data.songs_by_likelihood && data.songs_by_likelihood.possible) || [];
                renderCurrentSongs();
            } catch (e) {
                alert('読み込みエラー: ' + e.message);
            }
        }

        function renderCurrentSongs() {
            ['certain', 'high', 'possible'].forEach(function(lik) {
                const container = document.getElementById('songs-' + lik);
                container.innerHTML = '';
                const arr = currentSongs[lik] || [];
                arr.forEach(function(s) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-100';
                    div.setAttribute('data-song-id', s.song_id);
                    div.innerHTML = '<span class="text-sm font-bold text-slate-800">' + escapeHtml(s.song_title) + '</span>' +
                        '<span class="text-xs text-slate-500">' + (s.release_title || '') + '</span>' +
                        '<div class="flex gap-2 shrink-0">' +
                        '<select class="lik-select border border-slate-200 rounded px-2 py-1 text-xs">' +
                        '<option value="certain"' + (lik === 'certain' ? ' selected' : '') + '>ほぼ確実</option>' +
                        '<option value="high"' + (lik === 'high' ? ' selected' : '') + '>高確率</option>' +
                        '<option value="possible"' + (lik === 'possible' ? ' selected' : '') + '>可能性あり</option>' +
                        '</select>' +
                        '<button type="button" class="btn-remove text-slate-400 hover:text-red-500"><i class="fa-solid fa-times"></i></button>' +
                        '</div>';
                    container.appendChild(div);
                });
            });
            let total = currentSongs.certain.length + currentSongs.high.length + currentSongs.possible.length;
            document.getElementById('noSongsMsg').classList.toggle('hidden', total > 0);
            bindCurrentSongEvents();
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }

        function bindCurrentSongEvents() {
            document.querySelectorAll('.lik-select').forEach(function(sel) {
                sel.onchange = function() {
                    const row = sel.closest('[data-song-id]');
                    const songId = row.getAttribute('data-song-id');
                    const newLik = sel.value;
                    ['certain', 'high', 'possible'].forEach(function(lik) {
                        currentSongs[lik] = (currentSongs[lik] || []).filter(function(s) { return String(s.song_id) !== songId; });
                    });
                    const song = { song_id: parseInt(songId, 10), song_title: row.querySelector('span').textContent, release_title: '' };
                    currentSongs[newLik].push(song);
                    renderCurrentSongs();
                };
            });
            document.querySelectorAll('.btn-remove').forEach(function(btn) {
                btn.onclick = function() {
                    const row = btn.closest('[data-song-id]');
                    const songId = row.getAttribute('data-song-id');
                    ['certain', 'high', 'possible'].forEach(function(lik) {
                        currentSongs[lik] = (currentSongs[lik] || []).filter(function(s) { return String(s.song_id) !== songId; });
                    });
                    renderCurrentSongs();
                };
            });
        }

        document.querySelectorAll('.btn-add-song').forEach(function(btn) {
            btn.onclick = function() {
                const row = btn.closest('.song-row');
                const songId = parseInt(row.getAttribute('data-song-id'), 10);
                const title = row.getAttribute('data-song-title');
                const likSelect = row.querySelector('.add-likelihood');
                const lik = likSelect ? likSelect.value : 'possible';
                const exists = currentSongs.certain.some(s => s.song_id === songId) ||
                    currentSongs.high.some(s => s.song_id === songId) ||
                    currentSongs.possible.some(s => s.song_id === songId);
                if (exists) {
                    if (typeof App !== 'undefined' && App.toast) App.toast('既に追加済みです', 'info');
                    else alert('既に追加済みです');
                    return;
                }
                currentSongs[lik].push({ song_id: songId, song_title: title, release_title: '' });
                renderCurrentSongs();
            };
        });

        eventSelect.onchange = function() {
            currentEventId = parseInt(eventSelect.value, 10) || 0;
            guideContent.classList.toggle('hidden', !currentEventId);
            if (currentEventId) loadGuide();
        };

        document.getElementById('btnSave').onclick = async function() {
            if (!currentEventId) return;
            const items = collectItems();
            try {
                const res = await fetch('/hinata/api/save_event_guide_songs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: currentEventId, items: items })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    if (typeof App !== 'undefined' && App.toast) App.toast('保存しました', 'success');
                    else alert('保存しました');
                    loadGuide();
                } else {
                    alert('エラー: ' + (json.message || ''));
                }
            } catch (e) {
                alert('通信エラー: ' + e.message);
            }
        };

        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
