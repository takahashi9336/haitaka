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
        #memberModal { opacity: 0; }
        #memberModal.active { 
            display: block !important; 
            overflow-y: auto; 
            -webkit-overflow-scrolling: touch;
            opacity: 1;
        }
        
        /* モーダル拡大アニメーション（クリック位置から中央へ移動） */
        @keyframes backdropFadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        @keyframes backdropFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }
        #memberModal.modal-opening {
            animation: backdropFadeIn 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        #memberModal.modal-opening .modal-content {
            animation: modalExpandFromPoint 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        #memberModal.modal-closing {
            animation: backdropFadeOut 0.3s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards;
        }
        #memberModal.modal-closing .modal-content {
            animation: modalShrinkToPoint 0.3s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards;
        }
        
        @keyframes modalExpandFromPoint {
            0% {
                transform: translate(var(--modal-translate-x, 0), var(--modal-translate-y, 0)) scale(0.3);
                opacity: 0;
            }
            100% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
        }
        @keyframes modalShrinkToPoint {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--modal-translate-x, 0), var(--modal-translate-y, 0)) scale(0.3);
                opacity: 0;
            }
        }

        /* 推し・気になるボタン用 */
        .fav-btn-base {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: all 0.18s ease-out;
        }
        .fav-btn-base:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
        }
        .fav-btn-inactive {
            background-color: rgba(15,23,42,0.65);
        }
        .fav-btn-active-star {
            background-color: #eab308; /* yellow-500 */
        }
        .fav-btn-active-heart {
            background-color: #ec4899; /* pink-500 */
        }
        .fav-icon-star {
            color: #fde68a; /* yellow-200 */
        }
        .fav-icon-heart {
            color: #fbcfe8; /* pink-200 */
        }
        .fav-icon-on {
            color: #ffffff;
        }
        @keyframes favorite-pop {
            0% { transform: scale(1); }
            40% { transform: scale(1.25); }
            100% { transform: scale(1); }
        }
        .favorite-pop {
            animation: favorite-pop 0.22s ease-out;
        }
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

    <!-- モーダル -->
    <div id="memberModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-slate-900/80 backdrop-blur-xl transition-all">
        <div class="relative w-full max-w-5xl mx-auto md:my-10 min-h-full flex items-center">
            <div class="modal-content bg-white w-full h-full md:h-auto md:max-h-[90vh] md:rounded-xl shadow-2xl relative flex flex-col md:flex-row md:overflow-hidden min-h-screen md:min-h-0">
                <button onclick="closeModal()" class="fixed md:absolute top-4 right-4 md:top-6 md:right-6 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-[110] hover:bg-white shadow-lg"><i class="fa-solid fa-xmark text-lg"></i></button>

                <div class="w-full md:w-[380px] shrink-0 border-r border-slate-50 flex flex-col bg-white">
                    <div id="modalHeader" class="h-64 md:h-72 relative shrink-0 flex flex-col justify-end p-8 text-white overflow-hidden bg-slate-200">
                        <img id="modalImg" src="" class="absolute inset-0 w-full h-full object-cover hidden">
                        <div class="relative z-10 text-white drop-shadow-md h-full flex flex-col justify-between">
                            <span id="modalGen" class="text-[10px] font-black opacity-90 tracking-wider bg-black/20 px-2 py-0.5 rounded self-start"></span>
                            <div class="flex items-end justify-between gap-3 mt-auto">
                                <h2 id="modalName" class="text-3xl md:text-4xl font-black leading-tight break-words"></h2>
                                <div id="favButtonBar" class="flex items-center gap-2">
                                    <button id="favStarBtn" class="fav-btn-base fav-btn-inactive" title="気になる">
                                        <i class="fa-regular fa-star fav-icon-star"></i>
                                    </button>
                                    <button id="favHeartBtn" class="fav-btn-base fav-btn-inactive" title="推し">
                                        <i class="fa-regular fa-heart fav-icon-heart"></i>
                                    </button>
                                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                                    <a id="adminEditBtn" href="#" class="fav-btn-base bg-sky-500 text-white shadow-lg hover:bg-sky-600 transition hidden" title="このメンバーを編集">
                                        <i class="fa-solid fa-user-pen text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modalImgThumbsWrap" class="hidden shrink-0 py-2 px-4 bg-white border-b border-slate-100 flex justify-center gap-2">
                        <div id="modalImgThumbs" class="flex gap-2 flex-wrap justify-center"></div>
                    </div>

                    <div class="pt-4 pb-8 px-8 space-y-6 overflow-y-auto custom-scroll">
                        <div id="modalInfoArea" class="hidden bg-sky-50/50 p-5 rounded-xl border border-sky-100/50">
                            <p class="text-[9px] font-black text-sky-400 mb-1 tracking-wider">紹介文</p>
                            <p id="modalInfo" class="text-sm font-medium text-slate-600 leading-relaxed whitespace-pre-wrap"></p>
                        </div>
                        <div id="penlightSection" class="hidden bg-white p-5 rounded-xl border border-slate-100/70">
                            <p class="text-[9px] font-black text-slate-400 mb-2 tracking-wider">サイリウムカラー</p>
                            <div class="flex items-center gap-3">
                                <div id="penlight1" class="flex-1 h-9 rounded-lg flex items-center justify-center text-[11px] font-bold shadow-sm border border-slate-100"></div>
                                <div id="penlight2" class="flex-1 h-9 rounded-lg flex items-center justify-center text-[11px] font-bold shadow-sm border border-slate-100"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">血液型</p><p id="modalBlood" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">身長</p><p id="modalHeight" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">生年月日</p><p id="modalBirth" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">出身地</p><p id="modalPlace" class="text-base font-black text-slate-700">--</p></div>
                        </div>
                        <div id="snsLinks" class="grid grid-cols-1 gap-2 pb-10 md:pb-0">
                            <a id="blogBtn" href="#" target="_blank" class="h-12 bg-sky-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-sky-600 border border-sky-100/50 hover:bg-sky-100 transition-all"><i class="fa-solid fa-blog"></i> 公式ブログ</a>
                            <a id="instaBtn" href="#" target="_blank" class="h-12 bg-pink-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-pink-600 border border-pink-100/50 hover:bg-pink-100 transition-all"><i class="fa-brands fa-instagram text-lg"></i> Instagram</a>
                        </div>
                    </div>
                </div>

                <div id="videoContainer" class="flex-1 bg-slate-50 p-6 md:p-12 flex flex-col justify-center pb-32 md:pb-12 overflow-y-auto">
                    <div id="videoArea" class="hidden w-full max-w-3xl mx-auto">
                        <p class="text-[10px] font-black text-slate-400 tracking-wider mb-4 text-center">紹介動画</p>
                        <div class="aspect-video w-full rounded-lg md:rounded-xl overflow-hidden bg-black shadow-2xl ring-4 md:ring-8 ring-white">
                            <iframe id="modalVideo" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                    <div id="noVideoMsg" class="text-center text-slate-300 py-20">
                        <i class="fa-brands fa-youtube text-6xl mb-4 opacity-20"></i>
                        <p class="text-[10px] font-bold tracking-wider">紹介動画はありません</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js"></script>
    <script>
        const IS_ADMIN = <?= (($user['role'] ?? '') === 'admin') ? 'true' : 'false' ?>;
        let currentMemberId = null;
        let currentFavoriteLevel = 0;
        const FAVORITE_LEVELS = {};
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
            card.onclick = (e) => showDetail(m.id, e);
            
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
            row.onclick = () => showDetail(m.id);
            
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

        function updateFavoriteUI(level) {
            currentFavoriteLevel = level || 0;
            const heartBtn = document.getElementById('favHeartBtn');
            const starBtn = document.getElementById('favStarBtn');
            const heartIcon = heartBtn.querySelector('i');
            const starIcon = starBtn.querySelector('i');

            // reset classes
            heartBtn.classList.remove('fav-btn-active-heart', 'fav-btn-inactive');
            starBtn.classList.remove('fav-btn-active-star', 'fav-btn-inactive');
            heartIcon.className = 'fa-regular fa-heart fav-icon-heart';
            starIcon.className = 'fa-regular fa-star fav-icon-star';

            if (currentFavoriteLevel >= 2) {
                // 推し：ハート＆星の両方ON
                heartBtn.classList.add('fav-btn-active-heart');
                starBtn.classList.add('fav-btn-active-star');
                heartIcon.className = 'fa-solid fa-heart fav-icon-on';
                starIcon.className = 'fa-solid fa-star fav-icon-on';
            } else if (currentFavoriteLevel === 1) {
                // 気になる：星のみON
                starBtn.classList.add('fav-btn-active-star');
                heartBtn.classList.add('fav-btn-inactive');
                starIcon.className = 'fa-solid fa-star fav-icon-on';
            } else {
                // 未登録
                heartBtn.classList.add('fav-btn-inactive');
                starBtn.classList.add('fav-btn-inactive');
            }
        }

        async function setFavoriteLevel(level) {
            if (!currentMemberId) return;
            const targetBtn = level === 2 ? document.getElementById('favHeartBtn')
                             : level === 1 ? document.getElementById('favStarBtn')
                             : null;
            const res = await fetch('/hinata/api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ member_id: currentMemberId, level })
            }).then(r => r.json());
            if (res.status === 'success') {
                const newLevel = res.level ?? level ?? 0;
                updateFavoriteUI(newLevel);
                if (targetBtn) {
                    targetBtn.classList.remove('favorite-pop');
                    // reflow
                    void targetBtn.offsetWidth;
                    targetBtn.classList.add('favorite-pop');
                }
            } else {
                alert('お気に入りの更新に失敗しました: ' + (res.message || ''));
            }
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('#favHeartBtn')) {
                e.stopPropagation();
                const next = currentFavoriteLevel === 2 ? 0 : 2;
                setFavoriteLevel(next);
            }
            if (e.target.closest('#favStarBtn')) {
                e.stopPropagation();
                let next = 1;
                if (currentFavoriteLevel === 1) next = 0;
                else if (currentFavoriteLevel === 2) next = 1; // 推し→気になる
                setFavoriteLevel(next);
            }
        });

        async function showDetail(id, sourceEvent) {
            const res = await fetch(`members.php?action=detail&id=${id}`).then(r => r.json());
            if (res.status !== 'success') return;
            const d = res.data;
            currentMemberId = d.id;

            const modal = document.getElementById('memberModal');
            
            // クリック位置から画面中央までの移動距離を計算
            if (sourceEvent && sourceEvent.currentTarget) {
                const rect = sourceEvent.currentTarget.getBoundingClientRect();
                const clickX = rect.left + rect.width / 2;
                const clickY = rect.top + rect.height / 2;
                const viewportCenterX = window.innerWidth / 2;
                const viewportCenterY = window.innerHeight / 2;
                
                // クリック位置から中央への移動距離（初期位置は逆方向）
                const translateX = clickX - viewportCenterX;
                const translateY = clickY - viewportCenterY;
                
                modal.style.setProperty('--modal-translate-x', `${translateX}px`);
                modal.style.setProperty('--modal-translate-y', `${translateY}px`);
            } else {
                modal.style.setProperty('--modal-translate-x', '0');
                modal.style.setProperty('--modal-translate-y', '0');
            }

            modal.classList.remove('modal-closing');
            modal.classList.add('active', 'modal-opening');
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
            
            // アニメーション終了後にクラスを削除
            setTimeout(() => {
                modal.classList.remove('modal-opening');
            }, 650);

            document.getElementById('modalName').innerText = d.name || '--';
            document.getElementById('modalGen').innerText = d.generation ? `${d.generation}期生` : '--';
            document.getElementById('modalBlood').innerText = d.blood_type ? `${d.blood_type}型` : '--';
            document.getElementById('modalHeight').innerHTML = d.height ? `${d.height} <span class="text-[10px] text-slate-400 font-bold">cm</span>` : '--';
            document.getElementById('modalBirth').innerText = d.birth_date ? d.birth_date.replace(/-/g, '/') : '--';
            document.getElementById('modalPlace').innerText = d.birth_place || '--';

            const mImg = document.getElementById('modalImg');
            const mThumbsWrap = document.getElementById('modalImgThumbsWrap');
            const mThumbs = document.getElementById('modalImgThumbs');
            const imgs = d.images && d.images.length ? d.images : (d.image_url ? [d.image_url] : []);
            if (imgs.length > 0) {
                mImg.src = '/assets/img/members/' + imgs[0] + IMG_CACHE_BUST;
                mImg.classList.remove('hidden');
                let selectedIdx = 0;
                const setMainImg = (idx) => {
                    mImg.src = '/assets/img/members/' + imgs[idx] + IMG_CACHE_BUST;
                };
                const updateThumbBorders = () => {
                    mThumbs.querySelectorAll('button').forEach((b, i) => {
                        const sel = i === selectedIdx;
                        b.classList.toggle('border-sky-500', sel);
                        b.classList.toggle('ring-2', sel);
                        b.classList.toggle('ring-sky-200', sel);
                        b.classList.toggle('border-slate-200', !sel);
                    });
                };
                if (imgs.length >= 2 && imgs.length <= 5) {
                    mThumbs.innerHTML = imgs.map((url, i) => 
                        '<button type="button" class="w-14 h-14 rounded-xl overflow-hidden border-2 border-slate-200 shrink-0 hover:border-sky-400 transition-all duration-300" data-idx="'+i+'"><img src="/assets/img/members/'+url+IMG_CACHE_BUST+'" class="w-full h-full object-cover" alt=""></button>'
                    ).join('');
                    mThumbsWrap.classList.remove('hidden');
                    updateThumbBorders();
                    mThumbs.querySelectorAll('button').forEach((btn, i) => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            selectedIdx = i;
                            setMainImg(selectedIdx);
                            updateThumbBorders();
                        });
                    });
                } else {
                    mThumbsWrap.classList.add('hidden');
                    mThumbs.innerHTML = '';
                }
            } else {
                mImg.classList.add('hidden');
                mThumbsWrap.classList.add('hidden');
                mThumbs.innerHTML = '';
            }

            if (d.member_info) { document.getElementById('modalInfo').innerText = d.member_info; document.getElementById('modalInfoArea').classList.remove('hidden'); } else { document.getElementById('modalInfoArea').classList.add('hidden'); }

            const penlightSection = document.getElementById('penlightSection');
            const c1 = d.color1 || null;
            const c2 = d.color2 || null;
            const n1 = d.color1_name || '';
            const n2 = d.color2_name || '';
            const getTextColor = (hex) => {
                if (!hex) return '#111827';
                const h = hex.replace('#', '');
                const r = parseInt(h.substring(0, 2), 16);
                const g = parseInt(h.substring(2, 4), 16);
                const b = parseInt(h.substring(4, 6), 16);
                const luminance = (0.299 * r + 0.587 * g + 0.114 * b);
                return luminance > 186 ? '#111827' : '#ffffff';
            };
            if (c1 || c2) {
                penlightSection.classList.remove('hidden');
                const p1 = document.getElementById('penlight1');
                const p2 = document.getElementById('penlight2');
                if (c1) {
                    p1.style.backgroundColor = c1;
                    p1.style.color = getTextColor(c1);
                    p1.textContent = n1 || c1;
                } else {
                    p1.style.backgroundColor = '#e5e7eb';
                    p1.style.color = '#111827';
                    p1.textContent = '未設定';
                }
                if (c2) {
                    p2.style.backgroundColor = c2;
                    p2.style.color = getTextColor(c2);
                    p2.textContent = n2 || c2;
                } else {
                    p2.style.backgroundColor = '#e5e7eb';
                    p2.style.color = '#111827';
                    p2.textContent = '未設定';
                }
            } else {
                penlightSection.classList.add('hidden');
            }

            const serverLevelRaw = typeof d.favorite_level !== 'undefined' && d.favorite_level !== null
                ? parseInt(d.favorite_level, 10)
                : 0;
            const serverLevel = Number.isNaN(serverLevelRaw) ? 0 : serverLevelRaw;
            FAVORITE_LEVELS[currentMemberId] = serverLevel;
            updateFavoriteUI(serverLevel);

            const adminBtn = document.getElementById('adminEditBtn');
            if (adminBtn) {
                if (IS_ADMIN && d.id) {
                    adminBtn.href = `/hinata/member_admin.php?member_id=${encodeURIComponent(d.id)}`;
                    adminBtn.classList.remove('hidden');
                } else {
                    adminBtn.classList.add('hidden');
                }
            }

            const setLink = (id, url) => { const el = document.getElementById(id); if(url){ el.href = url; el.classList.remove('hidden'); }else{ el.classList.add('hidden'); } };
            setLink('blogBtn', d.blog_url); setLink('instaBtn', d.insta_url);

            const video = document.getElementById('modalVideo');
            if (d.pv_video_key) {
                video.src = `https://www.youtube.com/embed/${d.pv_video_key}?rel=0`;
                document.getElementById('videoArea').classList.remove('hidden');
                document.getElementById('noVideoMsg').classList.add('hidden');
            } else {
                video.src = "";
                document.getElementById('videoArea').classList.add('hidden');
                document.getElementById('noVideoMsg').classList.remove('hidden');
            }
        }

        function closeModal() {
            const modal = document.getElementById('memberModal');
            modal.classList.remove('modal-opening');
            modal.classList.add('modal-closing');
            
            // アニメーション完了後にモーダルを非表示
            setTimeout(() => {
                modal.classList.remove('active', 'modal-closing');
                document.getElementById('modalVideo').src = "";
                document.body.style.overflow = '';
            }, 300);
        }
        document.getElementById('memberModal').onclick = (e) => { if (e.target.id === 'memberModal') closeModal(); };

        // 初期表示
        renderMembers();
    </script>
</body>
</html>