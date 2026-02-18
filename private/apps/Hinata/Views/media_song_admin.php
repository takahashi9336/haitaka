<?php
/**
 * 動画・楽曲紐付け管理画面 View（管理者専用）
 * 物理パス: haitaka/private/apps/Hinata/Views/media_song_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>動画・楽曲紐付け管理 - 日向坂ポータル</title>
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
        .video-row {
            transition: background-color 0.15s ease-out, border-color 0.15s ease-out;
        }
        .video-row:hover {
            background: #f1f5f9; /* slate-100 相当 */
        }
        .video-row.selected {
            border-left: 4px solid var(--hinata-theme);
            background: #e0f2fe; /* sky-100 相当で明確にハイライト */
        }
        #linkedSongDisplay {
            min-height: 3rem; /* 紐付き有無で高さが変わらないように底上げ */
        }
        .btn-unlink-hidden {
            visibility: hidden; /* レイアウトは維持してボタンだけ非表示にする */
        }
        @media (max-width: 767px) {
            .media-song-wrap { flex-direction: column; }
            .media-song-wrap .video-list-section { flex: 0 0 auto; max-height: 45vh; min-height: 200px; display: flex; flex-direction: column; }
            .media-song-wrap .video-list-section #videoList { min-height: 0; }
            .media-song-wrap .song-panel-section { flex: 1 1 0; min-height: 280px; overflow: hidden; display: flex; flex-direction: column; }
            .media-song-wrap .song-panel-section #selectionPanel { min-height: 0; overflow: auto; -webkit-overflow-scrolling: touch; }
        }
        #toast {
            position: fixed; top: 1rem; left: 50%; transform: translateX(-50%) translateY(-120%);
            z-index: 9999; padding: 0.75rem 1.5rem; background: #0f766e; color: white;
            font-size: 0.875rem; font-weight: 700; border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(15, 118, 110, 0.4);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease-out;
            pointer-events: none;
            opacity: 0;
        }
        #toast.visible { transform: translateX(-50%) translateY(0); opacity: 1; }
        <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <div id="toast" role="status" aria-live="polite"></div>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-music text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画・楽曲紐付け管理</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                ポータルへ戻る
            </a>
        </header>

        <div class="media-song-wrap flex-1 flex flex-col md:flex-row min-h-0">
            <section class="video-list-section w-full md:w-96 shrink-0 border-r <?= $cardBorder ?> bg-white flex flex-col min-h-0">
                <div class="p-4 border-b <?= $cardBorder ?> space-y-3">
                    <input type="text" id="searchVideo" placeholder="動画を検索..." class="w-full h-10 px-4 border <?= $cardBorder ?> rounded-lg text-sm outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-200' ?>">
                    <select id="filterCategory" class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none bg-slate-50">
                        <option value="">カテゴリ: すべて</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-slate-600 hover:text-slate-800">
                        <input type="checkbox" id="filterUnlinkedOnly" class="rounded border-sky-200 text-sky-500 focus:ring-sky-300">
                        <span>未紐づけの動画のみ</span>
                    </label>
                    <button id="btnSearch" class="w-full h-10 bg-sky-500 text-white rounded-lg text-sm font-bold hover:bg-sky-600 transition">
                        <i class="fa-solid fa-search mr-2"></i>検索
                    </button>
                </div>
                <div id="videoList" class="flex-1 overflow-y-auto p-2">
                    <p class="text-slate-400 text-sm text-center py-8">検索で動画を表示</p>
                </div>
            </section>

            <section class="song-panel-section flex-1 flex flex-col min-w-0 min-h-0 bg-slate-50/50">
                <div id="noSelection" class="flex-1 flex items-center justify-center text-slate-400">
                    <div class="text-center">
                        <i class="fa-solid fa-video text-6xl mb-4 opacity-30"></i>
                        <p class="text-sm font-bold">左の一覧から動画を選択してください</p>
                    </div>
                </div>
                <div id="selectionPanel" class="hidden flex-1 flex flex-col overflow-hidden">
                    <div class="p-4 md:p-6 bg-white border-b border-sky-100 shrink-0">
                        <div class="flex gap-4 items-start">
                            <div id="selectedThumb" class="w-24 md:w-32 shrink-0 aspect-video rounded-lg overflow-hidden bg-slate-200">
                                <img id="selectedThumbImg" src="" alt="" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <span id="selectedCategory" class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-700 mb-1"></span>
                                <h2 id="selectedTitle" class="text-lg font-bold text-slate-800 truncate"></h2>
                                <p id="selectedDate" class="text-xs text-slate-400 mt-1"></p>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 md:p-6">
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-2">紐づいている楽曲</h3>
                            <div id="linkedSongDisplay" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-600 text-sm flex items-center justify-between">
                                <span id="linkedSongTitle">未紐付け</span>
                                <button id="btnUnlink" type="button" class="btn-unlink-hidden h-8 px-3 rounded-lg text-[11px] font-bold bg-slate-200 hover:bg-slate-300 text-slate-700 transition">
                                    紐付けを解除
                                </button>
                            </div>
                        </div>
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-2">楽曲を選択して紐づける</h3>
                            <div class="mb-3">
                                <input type="text" id="searchSong" placeholder="楽曲・リリース名で検索..." class="w-full h-10 px-4 border <?= $cardBorder ?> rounded-lg text-sm outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-200' ?>">
                            </div>
                            <div id="songsByRelease" class="space-y-4 border <?= $cardBorder ?> rounded-lg p-3 bg-white max-h-[50vh] overflow-y-auto">
                                <?php foreach ($releasesWithSongs as $rel): ?>
                                    <?php if (!empty($rel['songs'])): ?>
                                    <div class="release-group" data-release-title="<?= htmlspecialchars($rel['title'] ?? '') ?>" data-release-number="<?= htmlspecialchars($rel['release_number'] ?? '') ?>">
                                        <h4 class="text-xs font-black text-sky-600 tracking-wider mb-2 pb-1 border-b border-sky-100">
                                            <?= htmlspecialchars($rel['release_number'] ?? '') ?> <?= htmlspecialchars($rel['title'] ?? '') ?>
                                        </h4>
                                        <div class="space-y-1">
                                            <?php foreach ($rel['songs'] as $s): ?>
                                            <div class="song-row flex items-center justify-between gap-2 p-2 rounded-lg hover:bg-sky-50" data-song-title="<?= htmlspecialchars($s['title'] ?? '') ?>" data-track-type="<?= htmlspecialchars($trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? '') ?>">
                                                <span class="text-sm text-slate-800"><?= htmlspecialchars($s['title'] ?? '') ?>
                                                    <?php $tt = $trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? ''; if ($tt): ?>
                                                    <span class="text-slate-500 text-xs">(<?= htmlspecialchars($tt) ?>)</span>
                                                    <?php endif; ?>
                                                </span>
                                                <button type="button" class="btn-link-song shrink-0 h-8 px-3 rounded-lg text-xs font-bold bg-sky-500 hover:bg-sky-600 text-white transition" data-song-id="<?= (int)$s['id'] ?>">この楽曲に紐づける</button>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        const videoList = document.getElementById('videoList');
        const noSelection = document.getElementById('noSelection');
        const selectionPanel = document.getElementById('selectionPanel');
        const searchVideo = document.getElementById('searchVideo');
        const filterCategory = document.getElementById('filterCategory');
        const btnSearch = document.getElementById('btnSearch');
        const linkedSongDisplay = document.getElementById('linkedSongDisplay');
        const linkedSongTitle = document.getElementById('linkedSongTitle');
        const btnUnlink = document.getElementById('btnUnlink');
        const searchSong = document.getElementById('searchSong');
        const songsByRelease = document.getElementById('songsByRelease');
        const filterUnlinkedOnly = document.getElementById('filterUnlinkedOnly');

        let selectedMetaId = null;
        let linkedSong = null;
        const trackTypesDisplay = <?= json_encode($trackTypesDisplay ?? []) ?>;

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('visible');
            clearTimeout(showToast._tid);
            showToast._tid = setTimeout(() => toast.classList.remove('visible'), 2500);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        function getThumbnailUrl(v) {
            if (v.thumbnail_url) return v.thumbnail_url;
            if (v.platform === 'youtube' && v.media_key) return 'https://img.youtube.com/vi/' + v.media_key + '/mqdefault.jpg';
            return '';
        }

        async function loadVideos() {
            const params = new URLSearchParams({ q: searchVideo.value, category: filterCategory.value, limit: 200, link_type: 'song' });
            if (filterUnlinkedOnly && filterUnlinkedOnly.checked) params.set('unlinked_only', '1');
            const res = await fetch('/hinata/api/list_media_for_link.php?' + params);
            const json = await res.json();
            if (json.status !== 'success') {
                videoList.innerHTML = '<p class="text-red-500 text-sm text-center py-4">エラー: ' + (json.message || '') + '</p>';
                return;
            }
            if (!json.data || json.data.length === 0) {
                videoList.innerHTML = '<p class="text-slate-400 text-sm text-center py-8">該当する動画がありません</p>';
                return;
            }
            videoList.innerHTML = json.data.map(v => {
                const dataStr = JSON.stringify(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                const thumbUrl = getThumbnailUrl(v);
                return '<div class="video-row cursor-pointer px-3 py-2 rounded-lg flex items-center gap-3 border-b border-slate-50 transition" data-meta-id="' + v.meta_id + '" data-video="' + dataStr + '">' +
                    '<div class="w-12 shrink-0 aspect-video rounded overflow-hidden bg-slate-100"><img src="' + thumbUrl + '" alt="" class="w-full h-full object-cover" onerror="this.src=\'\'"></div>' +
                    '<div class="flex-1 min-w-0"><span class="text-xs font-bold text-sky-600">' + (v.category || '') + '</span><p class="text-sm font-bold text-slate-800 truncate">' + escapeHtml(v.title || '') + '</p></div></div>';
            }).join('');
            videoList.querySelectorAll('.video-row').forEach(row => { row.onclick = () => selectVideo(row); });
        }

        async function selectVideo(rowEl) {
            document.querySelectorAll('.video-row').forEach(r => r.classList.remove('selected'));
            rowEl.classList.add('selected');
            const dataStr = rowEl.getAttribute('data-video');
            const video = JSON.parse(dataStr ? dataStr.replace(/&quot;/g, '"').replace(/&amp;/g, '&') : '{}');
            selectedMetaId = video.meta_id;
            document.getElementById('selectedThumbImg').src = getThumbnailUrl(video) || '';
            document.getElementById('selectedCategory').textContent = video.category || '';
            document.getElementById('selectedTitle').textContent = video.title || '';
            const primaryDate = video.upload_date || video.release_date || '';
            document.getElementById('selectedDate').textContent = primaryDate ? new Date(primaryDate).toLocaleDateString('ja-JP') : '';
            noSelection.classList.add('hidden');
            selectionPanel.classList.remove('hidden');
            applySongFilter();
            await loadLinkedSong();
            if (window.innerWidth < 768) {
                setTimeout(() => selectionPanel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
            }
        }

        async function loadLinkedSong() {
            if (!selectedMetaId) return;
            const res = await fetch('/hinata/api/get_media_linked_song.php?meta_id=' + selectedMetaId);
            const json = await res.json();
            linkedSong = (json.status === 'success' && json.data) ? json.data : null;
            if (linkedSong) {
                const typeLabel = trackTypesDisplay[linkedSong.track_type] || linkedSong.track_type || '';
                linkedSongTitle.textContent = (linkedSong.title || '');
                if (typeLabel) {
                    linkedSongTitle.textContent += ' (' + typeLabel + ')';
                }
                btnUnlink.classList.remove('btn-unlink-hidden');
            } else {
                linkedSongTitle.textContent = '未紐付け';
                btnUnlink.classList.add('btn-unlink-hidden');
            }
        }

        function applySongFilter() {
            const q = (searchSong && searchSong.value) ? searchSong.value.trim().toLowerCase() : '';
            if (!songsByRelease) return;
            songsByRelease.querySelectorAll('.release-group').forEach(function(group) {
                const releaseTitle = (group.getAttribute('data-release-title') || '').toLowerCase();
                const releaseNumber = (group.getAttribute('data-release-number') || '').toLowerCase();
                let hasVisible = false;
                group.querySelectorAll('.song-row').forEach(function(row) {
                    const songTitle = (row.getAttribute('data-song-title') || '').toLowerCase();
                    const trackType = (row.getAttribute('data-track-type') || '').toLowerCase();
                    const match = !q || releaseTitle.includes(q) || releaseNumber.includes(q) || songTitle.includes(q) || trackType.includes(q);
                    row.style.display = match ? '' : 'none';
                    if (match) hasVisible = true;
                });
                group.style.display = hasVisible ? '' : 'none';
            });
        }

        if (searchSong) searchSong.addEventListener('input', applySongFilter);

        songsByRelease && songsByRelease.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-link-song');
            if (btn) saveLink(parseInt(btn.getAttribute('data-song-id'), 10));
        });

        async function saveLink(songId) {
            if (!selectedMetaId) return;
            try {
                const res = await fetch('/hinata/api/save_media_song_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meta_id: selectedMetaId, song_id: songId })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    showToast('紐付けました。');
                    loadLinkedSong();
                } else {
                    alert('エラー: ' + (json.message || ''));
                }
            } catch (e) {
                alert('通信エラー: ' + e.message);
            }
        }

        btnUnlink.onclick = async function() {
            if (!selectedMetaId) return;
            if (!confirm('この動画と楽曲の紐付けを解除しますか？')) return;
            try {
                const res = await fetch('/hinata/api/save_media_song_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meta_id: selectedMetaId, song_id: null })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    showToast('紐付けを解除しました。');
                    loadLinkedSong();
                } else {
                    alert('エラー: ' + (json.message || ''));
                }
            } catch (e) {
                alert('通信エラー: ' + e.message);
            }
        };

        btnSearch.onclick = loadVideos;
        searchVideo.onkeydown = (e) => { if (e.key === 'Enter') loadVideos(); };
        filterCategory.onchange = loadVideos;
        if (filterUnlinkedOnly) filterUnlinkedOnly.addEventListener('change', loadVideos);
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        loadVideos();
    </script>
</body>
</html>
