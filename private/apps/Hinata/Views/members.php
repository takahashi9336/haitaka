<?php
/**
 * メンバー帳 View
 * 物理パス: haitaka/private/apps/Hinata/Views/members.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>メンバー帳 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .filter-btn.active { background-color: var(--hinata-theme); color: white; border-color: var(--hinata-theme); }
        .member-card:hover { border-color: var(--hinata-theme); }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .member-card { transition: all 0.3s ease; }
        .member-card:hover { transform: translateY(-2px); }
        .portrait-img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-users text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight">メンバー帳</h1>
            </div>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/hinata/member_admin.php" class="text-[10px] font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-3 py-1.5 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-user-gear mr-1"></i>管理</a>
            <?php endif; ?>
        </header>

        <div class="bg-white border-b <?= $cardBorder ?> px-4 py-3 flex flex-wrap gap-3 items-center justify-between shrink-0">
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                <button onclick="filterGen('all')" class="filter-btn active px-6 py-1.5 rounded-full border border-slate-100 text-[10px] font-black tracking-wider transition-all">全員</button>
                <?php foreach([1,2,3,4,5] as $g): ?>
                    <button onclick="filterGen(<?= $g ?>)" class="filter-btn px-6 py-1.5 rounded-full border border-slate-100 text-[10px] font-black tracking-wider transition-all"><?= $g ?>期生</button>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1">
                    <select id="sortSelect" onchange="changeSortOrder()" class="px-3 py-1.5 rounded-full border border-slate-200 text-[10px] font-black text-slate-600 bg-white cursor-pointer outline-none">
                        <option value="generation">期生順</option>
                        <option value="name">名前順</option>
                        <option value="birthdate">生年月日順</option>
                        <option value="height">身長順</option>
                    </select>
                    <button id="sortOrderBtn" onclick="toggleSortOrder()" class="w-8 h-8 rounded-full border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 transition flex items-center justify-center" title="昇順・降順切り替え">
                        <i class="fa-solid fa-arrow-up-short-wide text-sm"></i>
                    </button>
                </div>
                <div class="flex bg-slate-100 rounded-full p-1 text-[10px] font-black">
                    <button id="viewCardBtn" class="px-3 py-1 rounded-full bg-white shadow text-slate-700" onclick="setViewMode('card')"><i class="fa-solid fa-border-all mr-1"></i>カード</button>
                    <button id="viewListBtn" class="px-3 py-1 rounded-full text-slate-500" onclick="setViewMode('list')"><i class="fa-solid fa-list mr-1"></i>一覧</button>
                </div>
                <button id="toggleGradBtn" class="px-3 py-1.5 rounded-full border border-slate-200 text-[10px] font-black text-slate-500 bg-white" onclick="toggleGraduates()">
                    卒業メンバー非表示
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scroll pb-24">
            <div id="memberGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 max-w-6xl mx-auto">
                <!-- JavaScriptで動的に生成 -->
            </div>

            <div id="memberList" class="hidden max-w-5xl mx-auto mt-4">
                <div class="overflow-x-auto bg-white rounded-xl border border-sky-50 shadow-sm">
                    <table class="min-w-full text-xs md:text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-3 py-2 text-left font-bold text-slate-500">SNS</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-500 w-12">画像</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-500">名前</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-500">期</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-500 whitespace-nowrap">サイリウム</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JavaScriptで動的に生成 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/member_modal.php'; ?>

    <script src="/assets/js/core.js"></script>
    <script src="/assets/js/hinata-member-modal.js"></script>
    <script>
        const IS_ADMIN = <?= (($user['role'] ?? '') === 'admin') ? 'true' : 'false' ?>;
        HinataMemberModal.init({ detailApiUrl: '/hinata/members.php', imgCacheBust: '<?= time() ?>', isAdmin: IS_ADMIN });
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        let currentGen = 'all';
        let viewMode = 'card';
        let showGraduates = false;
        let currentSortOrder = 'generation';
        let isAscending = true; // true: 昇順, false: 降順
        
        // メンバーデータをJavaScript配列として保持
        const membersData = <?= json_encode($members, JSON_UNESCAPED_UNICODE) ?>;
        const IMG_CACHE_BUST = '?v=<?= time() ?>';

        function changeSortOrder() {
            const select = document.getElementById('sortSelect');
            currentSortOrder = select.value;
            renderMembers();
        }
        
        function toggleSortOrder() {
            isAscending = !isAscending;
            const btn = document.getElementById('sortOrderBtn');
            const icon = btn.querySelector('i');
            
            if (isAscending) {
                icon.className = 'fa-solid fa-arrow-up-short-wide text-sm';
                btn.title = '昇順';
            } else {
                icon.className = 'fa-solid fa-arrow-down-wide-short text-sm';
                btn.title = '降順';
            }
            
            renderMembers();
        }
        
        function sortMembers(members) {
            const sorted = [...members];
            const direction = isAscending ? 1 : -1;
            
            switch(currentSortOrder) {
                case 'name':
                    sorted.sort((a, b) => {
                        const result = (a.kana || a.name).localeCompare(b.kana || b.name, 'ja');
                        return result * direction;
                    });
                    break;
                case 'birthdate':
                    sorted.sort((a, b) => {
                        if (!a.birth_date && !b.birth_date) return 0;
                        if (!a.birth_date) return 1;
                        if (!b.birth_date) return -1;
                        const result = a.birth_date.localeCompare(b.birth_date);
                        return result * direction;
                    });
                    break;
                case 'height':
                    sorted.sort((a, b) => {
                        const ha = parseFloat(a.height) || 0;
                        const hb = parseFloat(b.height) || 0;
                        const result = ha - hb;
                        return result * direction;
                    });
                    break;
                case 'generation':
                default:
                    sorted.sort((a, b) => {
                        // 現役優先（昇順・降順に関わらず維持）
                        if (a.is_active !== b.is_active) return b.is_active - a.is_active;
                        // 期生順
                        if (a.generation !== b.generation) {
                            const result = a.generation - b.generation;
                            return result * direction;
                        }
                        // かな順
                        const result = (a.kana || a.name).localeCompare(b.kana || b.name, 'ja');
                        return result * direction;
                    });
                    break;
            }
            
            return sorted;
        }
        
        function renderMembers() {
            const filtered = membersData.filter(m => {
                if (!showGraduates && !m.is_active) return false;
                if (currentGen === 'all') return true;
                return String(m.generation) === String(currentGen);
            });
            
            const sorted = sortMembers(filtered);
            
            // カード表示の再レンダリング
            const gridContainer = document.getElementById('memberGrid');
            gridContainer.innerHTML = '';
            
            if (currentSortOrder === 'generation') {
                // 期生順の場合はグルーピング表示
                const generations = {};
                sorted.forEach(m => {
                    if (!generations[m.generation]) {
                        generations[m.generation] = [];
                    }
                    generations[m.generation].push(m);
                });
                
                // 期生グループの順序を昇順・降順で制御
                const genKeys = Object.keys(generations).sort((a, b) => {
                    return isAscending ? (a - b) : (b - a);
                });
                
                genKeys.forEach(gen => {
                    // 期生ヘッダー
                    const genHeader = document.createElement('div');
                    genHeader.className = 'col-span-full mt-6 mb-3 pb-2 border-b-2 border-sky-200';
                    genHeader.innerHTML = `<h2 class="text-lg font-black text-sky-600">${gen}期生</h2>`;
                    gridContainer.appendChild(genHeader);
                    
                    // メンバーカード
                    generations[gen].forEach(m => {
                        gridContainer.appendChild(createMemberCard(m));
                    });
                });
            } else {
                // その他のソート順は通常表示
                sorted.forEach(m => {
                    gridContainer.appendChild(createMemberCard(m));
                });
            }
            
            // リスト表示の再レンダリング
            renderListView(sorted);
        }
        
        function createMemberCard(m) {
            const card = document.createElement('div');
            card.className = 'member-card bg-white rounded-xl p-4 shadow-sm border border-sky-50/50 cursor-pointer flex flex-col items-center text-center relative overflow-hidden';
            card.dataset.gen = m.generation;
            card.dataset.active = m.is_active ? '1' : '0';
            card.onclick = (e) => HinataMemberModal.open(m.id, e);
            
            const colorStrip = document.createElement('div');
            colorStrip.className = 'absolute top-0 left-0 w-full h-1.5';
            colorStrip.style.background = `linear-gradient(to right, ${m.color1 || '#ccc'}, ${m.color2 || '#ddd'})`;
            card.appendChild(colorStrip);
            
            const imageContainer = document.createElement('div');
            imageContainer.className = 'w-full aspect-square rounded-xl bg-sky-50 mb-4 shadow-inner overflow-hidden flex items-center justify-center';
            if (m.image_url) {
                const img = document.createElement('img');
                img.src = `/assets/img/members/${m.image_url}${IMG_CACHE_BUST}`;
                img.className = 'portrait-img';
                imageContainer.appendChild(img);
            } else {
                const initial = document.createElement('span');
                initial.className = 'text-4xl font-black text-sky-200';
                initial.textContent = m.name.substring(0, 1);
                imageContainer.appendChild(initial);
            }
            card.appendChild(imageContainer);
            
            const info = document.createElement('div');
            info.className = 'space-y-1';
            info.innerHTML = `
                <span class="text-[9px] font-black text-sky-400 tracking-wider">${m.generation}期生</span>
                <h3 class="font-black text-slate-800 text-base">${m.name}</h3>
                ${!m.is_active ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">卒業</span>' : ''}
            `;
            card.appendChild(info);
            
            return card;
        }
        
        function renderListView(members) {
            const tbody = document.querySelector('#memberList tbody');
            // グループヘッダー行を削除
            tbody.querySelectorAll('tr').forEach(tr => tr.remove());
            
            if (currentSortOrder === 'generation') {
                // 期生順の場合はグルーピング表示
                const generations = {};
                members.forEach(m => {
                    if (!generations[m.generation]) {
                        generations[m.generation] = [];
                    }
                    generations[m.generation].push(m);
                });
                
                // 期生グループの順序を昇順・降順で制御
                const genKeys = Object.keys(generations).sort((a, b) => {
                    return isAscending ? (a - b) : (b - a);
                });
                
                genKeys.forEach(gen => {
                    // グループヘッダー行
                    const headerRow = document.createElement('tr');
                    headerRow.className = 'border-t border-slate-100 bg-slate-50/60';
                    headerRow.innerHTML = `<td colspan="5" class="px-3 py-2 text-[11px] font-bold text-slate-500 tracking-wider">${gen}期生</td>`;
                    tbody.appendChild(headerRow);
                    
                    // メンバー行
                    generations[gen].forEach(m => {
                        tbody.appendChild(createMemberRow(m));
                    });
                });
            } else {
                members.forEach(m => {
                    tbody.appendChild(createMemberRow(m));
                });
            }
        }
        
        function createMemberRow(m) {
            const row = document.createElement('tr');
            row.className = 'member-row hover:bg-sky-50/50 border-t border-slate-100 cursor-pointer';
            row.dataset.gen = m.generation;
            row.dataset.active = m.is_active ? '1' : '0';
            row.onclick = (e) => HinataMemberModal.open(m.id, e);
            
            const penlightCell = (code, name) => {
                if (!code && !name) {
                    return '<span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-[11px] font-bold text-slate-500 whitespace-nowrap">未設定</span>';
                }
                const label = name || code;
                const hex = code ? code.replace('#', '') : '';
                return `<span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap" style="background-color: ${code}; color: ${getTextColorForBg(hex)};">${label}</span>`;
            };
            
            const createSnsIcon = (url, icon, title, activeColor, bgColor) => {
                if (url) {
                    return `<a href="${url}" target="_blank" onclick="event.stopPropagation()" class="w-7 h-7 rounded-full flex items-center justify-center ${bgColor} ${activeColor} hover:brightness-110 text-xs" title="${title}"><i class="${icon}"></i></a>`;
                } else {
                    return `<span class="w-7 h-7 rounded-full flex items-center justify-center bg-slate-50 text-slate-300 text-xs" title="${title}"><i class="${icon}"></i></span>`;
                }
            };
            
            row.innerHTML = `
                <td class="px-3 py-2">
                    <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                        ${createSnsIcon(m.blog_url, 'fa-solid fa-blog', 'ブログ', 'text-sky-600', 'bg-sky-50')}
                        ${createSnsIcon(m.insta_url, 'fa-brands fa-instagram', 'Instagram', 'text-pink-600', 'bg-pink-50')}
                        ${createSnsIcon(m.pv_video_key ? 'https://www.youtube.com/watch?v='+m.pv_video_key : null, 'fa-brands fa-youtube', '個人PV', 'text-red-600', 'bg-red-50')}
                    </div>
                </td>
                <td class="px-3 py-2">
                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-100 flex items-center justify-center">
                        ${m.image_url ? '<img src="/assets/img/members/'+m.image_url+IMG_CACHE_BUST+'" class="w-full h-full object-cover">' : '<span class="text-[11px] font-black text-slate-400">'+m.name.substring(0,1)+'</span>'}
                    </div>
                </td>
                <td class="px-3 py-2 font-bold text-slate-800 whitespace-nowrap">
                    ${m.name}
                    ${!m.is_active ? '<span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">卒業</span>' : ''}
                </td>
                <td class="px-3 py-2 text-slate-600 whitespace-nowrap">${m.generation}期</td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-1 flex-wrap">
                        ${penlightCell(m.color1, m.color1_name)}
                        <span class="text-[11px] text-slate-400 font-bold mx-1">×</span>
                        ${penlightCell(m.color2, m.color2_name)}
                    </div>
                </td>
            `;
            
            return row;
        }
        
        function getTextColorForBg(hex) {
            if (!hex) return '#111827';
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b);
            return luminance > 186 ? '#111827' : '#ffffff';
        }
        
        function applyFilters() {
            renderMembers();
        }

        function filterGen(gen) {
            currentGen = gen;
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            if (event && event.target) {
                event.target.classList.add('active');
            }
            applyFilters();
        }

        function setViewMode(mode) {
            viewMode = mode;
            const grid = document.getElementById('memberGrid');
            const list = document.getElementById('memberList');
            const cardBtn = document.getElementById('viewCardBtn');
            const listBtn = document.getElementById('viewListBtn');
            if (mode === 'card') {
                grid.classList.remove('hidden');
                list.classList.add('hidden');
                cardBtn.classList.add('bg-white', 'shadow', 'text-slate-700');
                listBtn.classList.remove('bg-white', 'shadow', 'text-slate-700');
                listBtn.classList.add('text-slate-500');
            } else {
                grid.classList.add('hidden');
                list.classList.remove('hidden');
                listBtn.classList.add('bg-white', 'shadow', 'text-slate-700');
                cardBtn.classList.remove('bg-white', 'shadow', 'text-slate-700');
                cardBtn.classList.add('text-slate-500');
            }
            applyFilters();
        }

        function toggleGraduates() {
            showGraduates = !showGraduates;
            const btn = document.getElementById('toggleGradBtn');
            if (showGraduates) {
                btn.textContent = '卒業メンバー表示中';
                btn.classList.remove('text-slate-500', 'bg-white');
                btn.classList.add('text-sky-600', 'bg-sky-50', 'border-sky-200');
            } else {
                btn.textContent = '卒業メンバー非表示';
                btn.classList.remove('text-sky-600', 'bg-sky-50', 'border-sky-200');
                btn.classList.add('text-slate-500', 'bg-white', 'border-slate-200');
            }
            applyFilters();
        }

        // 初期表示
        renderMembers();
    </script>
</body>
</html>