<?php
/**
 * 動画設定管理画面 View（カテゴリ変更など）（管理者専用）
 * 物理パス: haitaka/private/apps/Hinata/Views/media_settings_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>動画設定管理 - 日向坂ポータル</title>
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
        .video-row:hover { background: #f1f5f9; }
        .video-row.selected {
            border-left: 4px solid var(--hinata-theme);
            background: #e0f2fe;
        }
        #toast {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%) translateY(-120%);
            z-index: 9999;
            padding: 0.75rem 1.5rem;
            background: #0f766e;
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
            border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(15, 118, 110, 0.4);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease-out;
            pointer-events: none;
            opacity: 0;
        }
        #toast.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex ?? '#0ea5e9') ?>; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?? '' ?>"<?= ($bodyStyle ?? '') ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <div id="toast" role="status" aria-live="polite"></div>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?? 'border-slate-100' ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?? 'bg-sky-500' ?> <?= $headerShadow ?? '' ?>"<?= ($headerIconStyle ?? '') ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-sliders text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画設定管理</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?? 'text-sky-600' ?> <?= $cardIconBg ?? 'bg-sky-50' ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= ($cardIconStyle ?? '') ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                ポータルへ戻る
            </a>
        </header>

        <div class="flex-1 flex flex-col md:flex-row min-h-0">
            <!-- 左：動画一覧 -->
            <section class="video-list-section w-full md:w-96 shrink-0 border-r <?= $cardBorder ?? 'border-slate-100' ?> bg-white flex flex-col min-h-0">
                <div class="p-4 border-b <?= $cardBorder ?? 'border-slate-100' ?> space-y-3">
                    <input type="text" id="searchVideo" placeholder="動画を検索..." class="w-full h-10 px-4 border <?= $cardBorder ?? 'border-slate-200' ?> rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200">
                    <select id="filterCategory" class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none bg-slate-50">
                        <option value="">カテゴリ: すべて</option>
                        <option value="__unset__">カテゴリ: 未設定</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btnSearch" class="w-full h-10 bg-sky-500 text-white rounded-lg text-sm font-bold hover:bg-sky-600 transition">
                        <i class="fa-solid fa-search mr-2"></i>検索
                    </button>
                </div>
                <div id="videoList" class="flex-1 overflow-y-auto p-2">
                    <p class="text-slate-400 text-sm text-center py-8">読み込み中...</p>
                </div>
            </section>

            <!-- 右：選択した動画の設定 -->
            <section class="settings-panel-section flex-1 flex flex-col min-w-0 min-h-0 bg-slate-50/50">
                <div id="noSelection" class="flex-1 flex items-center justify-center text-slate-400">
                    <div class="text-center">
                        <i class="fa-solid fa-sliders text-6xl mb-4 opacity-30"></i>
                        <p class="text-sm font-bold">左の一覧から動画を選択してください</p>
                        <p class="text-xs mt-2">カテゴリの変更などができます</p>
                    </div>
                </div>
                <div id="settingsPanel" class="hidden flex-1 flex flex-col overflow-hidden">
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
                        <h3 class="text-sm font-bold text-slate-700 mb-3">動画の設定</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2">カテゴリ</label>
                                <select id="editCategory" class="w-full max-w-xs h-10 px-4 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200 bg-white">
                                    <option value="">（未設定）</option>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2">アップロード日</label>
                                <input type="date" id="editUploadDate" class="w-full max-w-xs h-10 px-4 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200 bg-white">
                            </div>
                        </div>
                        <div class="mt-6">
                            <label class="block text-xs font-bold text-slate-600 mb-2">サムネイル画像</label>
                            <div class="flex items-center gap-4">
                                <div id="thumbPreviewWrap" class="w-32 aspect-video rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                    <img id="thumbPreview" src="" alt="" class="w-full h-full object-cover hidden">
                                    <div id="thumbPlaceholder" class="w-full h-full flex items-center justify-center text-slate-400 text-[10px]">No Image</div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="inline-flex items-center gap-2 h-9 px-4 bg-purple-500 text-white rounded-lg text-xs font-bold hover:bg-purple-600 transition cursor-pointer">
                                        <i class="fa-solid fa-upload"></i> 画像をアップロード
                                        <input type="file" id="thumbFileInput" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
                                    </label>
                                    <p id="thumbUploadStatus" class="text-[10px] text-slate-400"></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center gap-3">
                            <button id="btnSave" class="h-10 px-6 bg-sky-500 text-white rounded-full text-sm font-bold hover:bg-sky-600 transition shadow-lg shadow-sky-200">
                                <i class="fa-solid fa-check mr-2"></i>保存
                            </button>
                            <button id="btnDelete" class="h-10 px-5 bg-white border border-red-200 text-red-500 rounded-full text-sm font-bold hover:bg-red-50 hover:border-red-300 transition">
                                <i class="fa-solid fa-trash mr-1"></i>削除
                            </button>
                        </div>

                        <hr class="my-8 border-slate-200">

                        <h3 class="text-sm font-bold text-slate-700 mb-3">カテゴリ管理</h3>
                        <div class="space-y-3">
                            <div class="flex gap-2">
                                <input type="text" id="newCategoryName" placeholder="新規カテゴリ名" class="flex-1 h-9 px-3 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200">
                                <button id="btnAddCategory" class="h-9 px-4 bg-emerald-500 text-white rounded-lg text-sm font-bold hover:bg-emerald-600 transition shrink-0">
                                    <i class="fa-solid fa-plus mr-1"></i>追加
                                </button>
                            </div>
                            <ul id="categoryList" class="space-y-1.5">
                                <?php foreach ($categories as $key => $label): ?>
                                <li class="flex items-center gap-2 group" data-name="<?= htmlspecialchars($key) ?>">
                                    <span class="category-name flex-1 text-sm font-bold text-slate-700"><?= htmlspecialchars($label) ?></span>
                                    <button type="button" class="btnRenameCategory opacity-0 group-hover:opacity-100 h-7 px-2 text-xs font-bold text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded transition" title="名称変更">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        const videoList = document.getElementById('videoList');
        const noSelection = document.getElementById('noSelection');
        const settingsPanel = document.getElementById('settingsPanel');
        const searchVideo = document.getElementById('searchVideo');
        const filterCategory = document.getElementById('filterCategory');
        const btnSearch = document.getElementById('btnSearch');
        const editCategory = document.getElementById('editCategory');
        const btnSave = document.getElementById('btnSave');

        let selectedMetaId = null;
        let selectedVideo = null;

        function showToast(msg) {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.classList.add('visible');
            setTimeout(() => el.classList.remove('visible'), 2000);
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
            const params = new URLSearchParams({ q: searchVideo.value, category: filterCategory.value, limit: 200, link_type: 'settings' });
            const res = await fetch(`/hinata/api/list_media_for_link.php?${params}`);
            const json = await res.json();
            if (json.status !== 'success') {
                videoList.innerHTML = '<p class="text-red-500 text-sm text-center py-4">エラー: ' + escapeHtml(json.message || '') + '</p>';
                return;
            }
            if (!json.data || json.data.length === 0) {
                videoList.innerHTML = '<p class="text-slate-400 text-sm text-center py-8">該当する動画がありません</p>';
                return;
            }
            videoList.innerHTML = json.data.map(v => {
                const dataStr = JSON.stringify(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                const thumbUrl = getThumbnailUrl(v);
                return `<div class="video-row cursor-pointer px-3 py-2 rounded-lg flex items-center gap-3 border-b border-slate-50 transition" data-meta-id="${v.meta_id}" data-video="${dataStr}">
                    <div class="w-12 shrink-0 aspect-video rounded overflow-hidden bg-slate-100">
                        <img src="${thumbUrl}" alt="" class="w-full h-full object-cover" onerror="this.src=''">
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-sky-600">${v.category || '（未設定）'}</span>
                        <p class="text-sm font-bold text-slate-800 truncate">${escapeHtml(v.title || '')}</p>
                    </div>
                </div>`;
            }).join('');
            videoList.querySelectorAll('.video-row').forEach(row => {
                row.onclick = () => selectVideo(row);
            });
        }

        function selectVideo(rowEl) {
            document.querySelectorAll('.video-row').forEach(r => r.classList.remove('selected'));
            rowEl.classList.add('selected');
            const dataStr = rowEl.getAttribute('data-video');
            const video = JSON.parse(dataStr ? dataStr.replace(/&quot;/g, '"').replace(/&amp;/g, '&') : '{}');
            selectedMetaId = video.meta_id;
            selectedVideo = video;

            document.getElementById('selectedThumbImg').src = getThumbnailUrl(video) || '';
            document.getElementById('selectedCategory').textContent = video.category || '（未設定）';
            document.getElementById('selectedTitle').textContent = video.title || '';
            const primaryDate = video.upload_date || video.release_date || '';
            document.getElementById('selectedDate').textContent = primaryDate ? new Date(primaryDate).toLocaleDateString('ja-JP') : '';
            editCategory.value = video.category || '';

            const ud = video.upload_date || '';
            document.getElementById('editUploadDate').value = ud ? ud.substring(0, 10) : '';

            const thumbUrl = getThumbnailUrl(video);
            const thumbPreview = document.getElementById('thumbPreview');
            const thumbPlaceholder = document.getElementById('thumbPlaceholder');
            if (thumbUrl) {
                thumbPreview.src = thumbUrl;
                thumbPreview.classList.remove('hidden');
                thumbPlaceholder.classList.add('hidden');
            } else {
                thumbPreview.classList.add('hidden');
                thumbPlaceholder.classList.remove('hidden');
            }
            document.getElementById('thumbUploadStatus').textContent = '';
            document.getElementById('thumbFileInput').value = '';

            noSelection.classList.add('hidden');
            settingsPanel.classList.remove('hidden');
        }

        async function saveSettings() {
            if (!selectedMetaId) return;
            const category = editCategory.value;
            const uploadDate = document.getElementById('editUploadDate').value || null;
            const res = await fetch('/hinata/api/update_media_metadata.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    meta_id: selectedMetaId,
                    asset_id: selectedVideo?.asset_id || null,
                    category: category,
                    upload_date: uploadDate,
                })
            });
            const json = await res.json();
            if (json.status !== 'success') {
                showToast('エラー: ' + (json.message || '保存に失敗しました'));
                return;
            }
            showToast('保存しました');
            selectedVideo.category = category || null;
            if (uploadDate) selectedVideo.upload_date = uploadDate;
            const row = videoList.querySelector(`[data-meta-id="${selectedMetaId}"]`);
            if (row) {
                const span = row.querySelector('.text-xs');
                if (span) span.textContent = category || '（未設定）';
                row.setAttribute('data-video', JSON.stringify(selectedVideo).replace(/&/g, '&amp;').replace(/"/g, '&quot;'));
            }
            document.getElementById('selectedCategory').textContent = category || '（未設定）';
            const dateDisplay = uploadDate ? new Date(uploadDate).toLocaleDateString('ja-JP') : '';
            document.getElementById('selectedDate').textContent = dateDisplay;
        }

        async function deleteMedia() {
            if (!selectedMetaId) return;
            if (!confirm(`「${selectedVideo?.title || ''}」を削除しますか？\nメンバー紐付け・楽曲紐付けも同時に削除されます。`)) return;

            const res = await fetch('/hinata/api/delete_media.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ meta_id: selectedMetaId })
            });
            const json = await res.json();
            if (json.status !== 'success') {
                showToast('エラー: ' + (json.message || '削除に失敗しました'));
                return;
            }
            showToast('削除しました');
            const row = videoList.querySelector(`[data-meta-id="${selectedMetaId}"]`);
            if (row) row.remove();
            selectedMetaId = null;
            selectedVideo = null;
            settingsPanel.classList.add('hidden');
            noSelection.classList.remove('hidden');
        }

        btnSearch.addEventListener('click', loadVideos);
        searchVideo.addEventListener('keydown', (e) => { if (e.key === 'Enter') loadVideos(); });
        filterCategory.addEventListener('change', loadVideos);
        btnSave.addEventListener('click', saveSettings);
        document.getElementById('btnDelete').addEventListener('click', deleteMedia);

        loadVideos();

        // カテゴリ管理
        const newCategoryName = document.getElementById('newCategoryName');
        const btnAddCategory = document.getElementById('btnAddCategory');
        const categoryList = document.getElementById('categoryList');

        async function refreshCategoryDropdowns() {
            const res = await fetch('/hinata/api/list_media_categories.php');
            const json = await res.json();
            if (json.status !== 'success') return [];
            const list = json.data || [];
            const opts = ['<option value="">カテゴリ: すべて</option>', '<option value="__unset__">カテゴリ: 未設定</option>'];
            list.forEach(n => { opts.push(`<option value="${escapeHtml(n)}">${escapeHtml(n)}</option>`); });
            filterCategory.innerHTML = opts.join('');
            const editOpts = ['<option value="">（未設定）</option>'];
            list.forEach(n => { editOpts.push(`<option value="${escapeHtml(n)}">${escapeHtml(n)}</option>`); });
            editCategory.innerHTML = editOpts.join('');
            return list;
        }

        function renderCategoryList(list) {
            categoryList.innerHTML = list.map(n => `
                <li class="flex items-center gap-2 group" data-name="${escapeHtml(n)}">
                    <span class="category-name flex-1 text-sm font-bold text-slate-700">${escapeHtml(n)}</span>
                    <button type="button" class="btnRenameCategory opacity-0 group-hover:opacity-100 h-7 px-2 text-xs font-bold text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded transition" title="名称変更">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                </li>
            `).join('');
            categoryList.querySelectorAll('.btnRenameCategory').forEach(btn => {
                btn.onclick = () => startRename(btn.closest('li'));
            });
        }

        async function startRename(liEl) {
            const oldName = liEl.getAttribute('data-name');
            const span = liEl.querySelector('.category-name');
            const input = document.createElement('input');
            input.type = 'text';
            input.value = oldName;
            input.className = 'flex-1 h-7 px-2 border border-sky-300 rounded text-sm outline-none focus:ring-2 focus:ring-sky-200';
            const save = async () => {
                const newName = input.value.trim();
                input.remove();
                liEl.insertBefore(span, liEl.firstChild);
                span.textContent = oldName;
                if (newName === '' || newName === oldName) return;
                const res = await fetch('/hinata/api/rename_media_category.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ old_name: oldName, new_name: newName })
                });
                const json = await res.json();
                if (json.status !== 'success') {
                    showToast('エラー: ' + (json.message || ''));
                    return;
                }
                showToast('カテゴリ名を変更しました');
                const list = await refreshCategoryDropdowns();
                renderCategoryList(list);
                if (selectedVideo && selectedVideo.category === oldName) {
                    selectedVideo.category = newName;
                    editCategory.value = newName;
                    document.getElementById('selectedCategory').textContent = newName;
                }
            };
            span.replaceWith(input);
            input.focus();
            input.select();
            input.onblur = save;
            input.onkeydown = (e) => {
                if (e.key === 'Enter') { input.onblur = null; save(); }
                if (e.key === 'Escape') { input.onblur = null; input.replaceWith(span); span.textContent = oldName; }
            };
        }

        btnAddCategory.addEventListener('click', async () => {
            const name = newCategoryName.value.trim();
            if (!name) {
                showToast('カテゴリ名を入力してください');
                return;
            }
            const res = await fetch('/hinata/api/create_media_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            const json = await res.json();
            if (json.status !== 'success') {
                showToast('エラー: ' + (json.message || ''));
                return;
            }
            showToast('カテゴリを追加しました');
            newCategoryName.value = '';
            const list = await refreshCategoryDropdowns();
            renderCategoryList(list);
        });
        newCategoryName.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') btnAddCategory.click();
        });

        categoryList.querySelectorAll('.btnRenameCategory').forEach(btn => {
            btn.onclick = () => startRename(btn.closest('li'));
        });

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.querySelector('.sidebar')?.classList.toggle('mobile-open');
        });

        document.getElementById('thumbFileInput').addEventListener('change', async function() {
            if (!this.files.length || !selectedVideo) return;
            const file = this.files[0];
            if (file.size > 5 * 1024 * 1024) { showToast('5MB以下の画像を選択してください'); this.value = ''; return; }

            const statusEl = document.getElementById('thumbUploadStatus');
            statusEl.textContent = 'アップロード中...';
            statusEl.className = 'text-[10px] text-sky-500 font-bold';

            const formData = new FormData();
            formData.append('asset_id', selectedVideo.asset_id);
            formData.append('file', file);

            try {
                const res = await fetch('/hinata/api/upload_thumbnail.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') {
                    const newUrl = json.thumbnail_url;
                    document.getElementById('thumbPreview').src = newUrl;
                    document.getElementById('thumbPreview').classList.remove('hidden');
                    document.getElementById('thumbPlaceholder').classList.add('hidden');
                    document.getElementById('selectedThumbImg').src = newUrl;
                    selectedVideo.thumbnail_url = newUrl;
                    const row = videoList.querySelector('[data-meta-id="' + selectedMetaId + '"]');
                    if (row) {
                        const img = row.querySelector('img');
                        if (img) img.src = newUrl;
                    }
                    statusEl.textContent = 'アップロード完了';
                    statusEl.className = 'text-[10px] text-emerald-600 font-bold';
                    showToast('サムネイルを更新しました');
                } else {
                    statusEl.textContent = json.message || 'エラーが発生しました';
                    statusEl.className = 'text-[10px] text-red-500 font-bold';
                }
            } catch (e) {
                statusEl.textContent = '通信エラー: ' + e.message;
                statusEl.className = 'text-[10px] text-red-500 font-bold';
            }
            this.value = '';
        });
    </script>
</body>
</html>
