<?php
/**
 * 動画・メンバー紐付け管理画面 View（管理者専用）
 * 物理パス: haitaka/private/apps/Hinata/Views/media_member_admin.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>動画・メンバー紐付け管理 - 日向坂ポータル</title>
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
        .video-row:hover { background: #f0f9ff; }
        .video-row.selected { background: #e0f2fe; border-left: 4px solid #0ea5e9; }
        /* スマホ: 左右を均等に高さ確保し、メンバー選択エリアが操作できるようにする */
        @media (max-width: 767px) {
            .media-member-wrap { flex-direction: column; }
            .media-member-wrap .video-list-section {
                flex: 0 0 auto;
                max-height: 45vh;
                min-height: 200px;
                display: flex;
                flex-direction: column;
            }
            .media-member-wrap .video-list-section #videoList {
                min-height: 0;
            }
            .media-member-wrap .member-panel-section {
                flex: 1 1 0;
                min-height: 280px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            .media-member-wrap .member-panel-section #selectionPanel {
                min-height: 0;
                overflow: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body class="bg-[#f0f9ff] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-sky-100 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white shadow-lg shadow-sky-200">
                    <i class="fa-solid fa-link text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画・メンバー紐付け管理</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold text-sky-500 bg-sky-50 px-4 py-2 rounded-full hover:bg-sky-100 transition">
                ポータルへ戻る
            </a>
        </header>

        <div class="media-member-wrap flex-1 flex flex-col md:flex-row min-h-0">
            <!-- 左：動画一覧 -->
            <section class="video-list-section w-full md:w-96 shrink-0 border-r border-sky-100 bg-white flex flex-col min-h-0">
                <div class="p-4 border-b border-sky-100 space-y-3">
                    <input type="text" id="searchVideo" placeholder="動画を検索..." class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200">
                    <select id="filterCategory" class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none bg-slate-50">
                        <option value="">カテゴリ: すべて</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btnSearch" class="w-full h-10 bg-sky-500 text-white rounded-lg text-sm font-bold hover:bg-sky-600 transition">
                        <i class="fa-solid fa-search mr-2"></i>検索
                    </button>
                </div>
                <div id="videoList" class="flex-1 overflow-y-auto p-2">
                    <p class="text-slate-400 text-sm text-center py-8">検索で動画を表示</p>
                </div>
            </section>

            <!-- 右：選択した動画のメンバー紐付け -->
            <section class="member-panel-section flex-1 flex flex-col min-w-0 min-h-0 bg-slate-50/50">
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
                        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                            <h3 class="text-sm font-bold text-slate-700">出演メンバーを選択（チェックボックス）</h3>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border border-sky-200 bg-sky-50 text-xs font-bold text-sky-600 hover:bg-sky-100 transition">
                                    <input type="checkbox" id="selectAllActive" class="rounded border-sky-200 text-sky-500">
                                    現役メンバー一括選択
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border border-sky-100 text-xs font-bold text-slate-600 hover:bg-sky-50 transition">
                                    <input type="checkbox" id="toggleGraduates" checked class="rounded border-sky-200 text-sky-500">
                                    <span id="toggleGraduatesLabel">卒業生を表示</span>
                                </label>
                            </div>
                        </div>
                        <div id="memberCheckboxList" class="space-y-6 mb-6">
                            <!-- 期生別グルーピングで JavaScript で動的生成 -->
                        </div>
                        <div>
                            <button id="btnSave" class="h-10 px-6 bg-sky-500 text-white rounded-full text-sm font-bold hover:bg-sky-600 transition shadow-lg shadow-sky-200">
                                <i class="fa-solid fa-check mr-2"></i>保存
                            </button>
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
        const memberCheckboxList = document.getElementById('memberCheckboxList');
        const btnSave = document.getElementById('btnSave');

        let selectedMetaId = null;
        let showGraduates = true;
        let checkedMemberIds = new Set();
        const categoryLabels = <?= json_encode($categories) ?>;
        const allMembers = <?= json_encode(array_map(fn($m) => [
            'id' => (int)$m['id'],
            'name' => $m['name'],
            'kana' => $m['kana'] ?? '',
            'generation' => (int)($m['generation'] ?? 0),
            'is_active' => (int)($m['is_active'] ?? 1)
        ], $members)) ?>;

        // 検索
        async function loadVideos() {
            const params = new URLSearchParams({
                q: searchVideo.value,
                category: filterCategory.value,
                limit: 100
            });
            const res = await fetch(`/hinata/api/list_media_for_link.php?${params}`);
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
                return `
                <div class="video-row cursor-pointer px-3 py-2 rounded-lg flex items-center gap-3 border-b border-slate-50 transition" data-meta-id="${v.meta_id}" data-video="${dataStr}">
                    <div class="w-12 shrink-0 aspect-video rounded overflow-hidden bg-slate-100">
                        <img src="${v.thumbnail_url || ''}" alt="" class="w-full h-full object-cover" onerror="this.src=''">
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-sky-600">${v.category || ''}</span>
                        <p class="text-sm font-bold text-slate-800 truncate">${escapeHtml(v.title || '')}</p>
                    </div>
                </div>
            `;
            }).join('');
            videoList.querySelectorAll('.video-row').forEach(row => {
                row.onclick = () => selectVideo(row);
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        async function selectVideo(rowEl) {
            document.querySelectorAll('.video-row').forEach(r => r.classList.remove('selected'));
            rowEl.classList.add('selected');
            const dataStr = rowEl.getAttribute('data-video');
            const video = JSON.parse(dataStr ? dataStr.replace(/&quot;/g, '"').replace(/&amp;/g, '&') : '{}');
            selectedMetaId = video.meta_id;
            document.getElementById('selectedThumbImg').src = video.thumbnail_url || '';
            document.getElementById('selectedCategory').textContent = categoryLabels[video.category] || video.category || '';
            document.getElementById('selectedTitle').textContent = video.title || '';
            document.getElementById('selectedDate').textContent = video.release_date ? new Date(video.release_date).toLocaleDateString('ja-JP') : '';
            noSelection.classList.add('hidden');
            selectionPanel.classList.remove('hidden');
            await loadLinkedMembers();
            // スマホではメンバー選択エリアが見えるようにスクロール
            if (window.innerWidth < 768) {
                setTimeout(() => {
                    selectionPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }

        async function loadLinkedMembers() {
            if (!selectedMetaId) return;
            const res = await fetch(`/hinata/api/get_media_members.php?meta_id=${selectedMetaId}`);
            const json = await res.json();
            const linkedIds = (json.status === 'success' && json.data) ? json.data.map(m => m.id) : [];
            checkedMemberIds = new Set(linkedIds.map(Number));
            renderMemberCheckboxes();
        }

        document.getElementById('toggleGraduates').addEventListener('change', (e) => {
            showGraduates = e.target.checked;
            document.getElementById('toggleGraduatesLabel').textContent = showGraduates ? '卒業生を表示' : '卒業生を非表示';
            renderMemberCheckboxes();
        });

        document.getElementById('selectAllActive').addEventListener('change', (e) => {
            const activeIds = allMembers.filter(m => m.is_active).map(m => m.id);
            if (e.target.checked) {
                activeIds.forEach(id => checkedMemberIds.add(id));
            } else {
                activeIds.forEach(id => checkedMemberIds.delete(id));
            }
            memberCheckboxList.querySelectorAll('.member-cb-active').forEach(cb => {
                cb.checked = e.target.checked;
            });
            memberCheckboxList.querySelectorAll('.gen-all-cb').forEach(genCb => {
                const gen = genCb.getAttribute('data-gen');
                const groupCbs = memberCheckboxList.querySelectorAll(`.member-cb-gen-${gen}`);
                genCb.checked = Array.from(groupCbs).every(c => c.checked);
            });
        });

        function renderMemberCheckboxes() {
            const ids = checkedMemberIds;
            const membersToShow = showGraduates ? allMembers : allMembers.filter(m => m.is_active);
            const byGen = {};
            membersToShow.forEach(m => {
                const gen = m.generation || 0;
                if (!byGen[gen]) byGen[gen] = [];
                byGen[gen].push(m);
            });
            const genOrder = Object.keys(byGen).sort((a, b) => Number(a) - Number(b));
            if (genOrder.length === 0) {
                memberCheckboxList.innerHTML = '<p class="text-slate-400 text-sm py-4">メンバーが登録されていません</p>';
                return;
            }
            memberCheckboxList.innerHTML = genOrder.map(gen => {
                const members = (byGen[gen] || []).sort((a, b) => (a.kana || a.name).localeCompare(b.kana || b.name, 'ja'));
                const memberIds = members.map(m => m.id);
                const allChecked = memberIds.every(id => ids.has(id));
                const items = members.map(m => {
                    const checked = ids.has(m.id) ? ' checked' : '';
                    const gradBadge = !m.is_active ? ' <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">卒業</span>' : '';
                    const activeClass = m.is_active ? ' member-cb-active' : '';
                    return `
                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-sky-50 cursor-pointer transition">
                            <input type="checkbox" class="member-cb member-cb-gen-${gen}${activeClass} rounded border-sky-200 text-sky-500 focus:ring-sky-300" value="${m.id}" data-gen="${gen}" data-active="${m.is_active ? 1 : 0}"${checked}>
                            <span class="text-sm font-bold text-slate-800">${escapeHtml(m.name)}</span>${gradBadge}
                        </label>
                    `;
                }).join('');
                return `
                    <div class="gen-group" data-gen="${gen}">
                        <div class="flex items-center justify-between mb-2 pb-1 border-b border-sky-100">
                            <h4 class="text-xs font-black text-sky-600 tracking-wider">${gen}期生</h4>
                            <label class="flex items-center gap-2 cursor-pointer text-[11px] font-bold text-sky-600 hover:text-sky-700">
                                <input type="checkbox" class="gen-all-cb rounded border-sky-200 text-sky-500" data-gen="${gen}"${allChecked ? ' checked' : ''}>
                                期別でまとめて選択
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">${items}</div>
                    </div>
                `;
            }).join('');
            memberCheckboxList.querySelectorAll('.gen-all-cb').forEach(cb => {
                cb.addEventListener('change', () => {
                    const gen = cb.getAttribute('data-gen');
                    memberCheckboxList.querySelectorAll(`.member-cb-gen-${gen}`).forEach(mcb => {
                        mcb.checked = cb.checked;
                        const id = parseInt(mcb.value);
                        if (cb.checked) checkedMemberIds.add(id); else checkedMemberIds.delete(id);
                    });
                    updateSelectAllActiveState();
                });
            });
            memberCheckboxList.querySelectorAll('.member-cb').forEach(mcb => {
                mcb.addEventListener('change', () => {
                    const id = parseInt(mcb.value);
                    if (mcb.checked) checkedMemberIds.add(id); else checkedMemberIds.delete(id);
                    const gen = mcb.getAttribute('data-gen');
                    const groupCbs = memberCheckboxList.querySelectorAll(`.member-cb-gen-${gen}`);
                    const genAllCb = memberCheckboxList.querySelector(`.gen-all-cb[data-gen="${gen}"]`);
                    if (genAllCb) genAllCb.checked = Array.from(groupCbs).every(c => c.checked);
                    updateSelectAllActiveState();
                });
            });
            updateSelectAllActiveState();
        }

        function updateSelectAllActiveState() {
            const selectAllActive = document.getElementById('selectAllActive');
            if (!selectAllActive) return;
            const activeIds = allMembers.filter(m => m.is_active).map(m => m.id);
            const allActiveChecked = activeIds.length > 0 && activeIds.every(id => checkedMemberIds.has(id));
            selectAllActive.checked = allActiveChecked;
        }

        function getCheckedMemberIds() {
            return Array.from(checkedMemberIds);
        }

        btnSave.onclick = async () => {
            if (!selectedMetaId) return;
            btnSave.disabled = true;
            try {
                const memberIds = getCheckedMemberIds();
                const res = await fetch('/hinata/api/save_media_members.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meta_id: selectedMetaId, member_ids: memberIds })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert('保存しました');
                } else {
                    alert('エラー: ' + (json.message || ''));
                }
            } catch (e) {
                alert('通信エラー: ' + e.message);
            } finally {
                btnSave.disabled = false;
            }
        };

        btnSearch.onclick = loadVideos;
        searchVideo.onkeydown = (e) => { if (e.key === 'Enter') loadVideos(); };
        filterCategory.onchange = loadVideos;

        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };

        // 初期表示時に動画一覧を読み込み
        loadVideos();
    </script>
</body>
</html>
