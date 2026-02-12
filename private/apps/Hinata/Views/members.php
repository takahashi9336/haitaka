<?php
/**
 * メンバー帳 View
 * 物理パス: haitaka/private/apps/Hinata/Views/members.php
 */
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .filter-btn.active { background-color: #7cc7e8; color: white; border-color: #7cc7e8; }
        .member-card { transition: all 0.3s ease; }
        .member-card:hover { transform: translateY(-4px); border-color: #7cc7e8; }
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
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 bg-[#f8fbff]">
        <header class="h-14 bg-white border-b border-sky-100 flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <h1 class="font-black text-slate-700 text-lg uppercase tracking-tight">メンバー帳</h1>
            </div>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/hinata/member_admin.php" class="text-[10px] font-bold text-sky-500 bg-sky-50 px-3 py-1.5 rounded-full"><i class="fa-solid fa-user-gear mr-1"></i>管理</a>
            <?php endif; ?>
        </header>

        <div class="bg-white border-b border-sky-50 px-4 py-3 flex flex-wrap gap-3 items-center justify-between shrink-0">
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                <button onclick="filterGen('all')" class="filter-btn active px-6 py-1.5 rounded-full border border-slate-100 text-[10px] font-black uppercase transition-all">全員</button>
                <?php foreach([1,2,3,4,5] as $g): ?>
                    <button onclick="filterGen(<?= $g ?>)" class="filter-btn px-6 py-1.5 rounded-full border border-slate-100 text-[10px] font-black uppercase transition-all"><?= $g ?>期生</button>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center gap-2">
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
                <?php foreach ($members as $m): ?>
                <div class="member-card bg-white rounded-[2.5rem] p-4 shadow-sm border border-sky-50/50 cursor-pointer flex flex-col items-center text-center relative overflow-hidden" 
                     data-gen="<?= $m['generation'] ?>" data-active="<?= $m['is_active'] ?>" onclick="showDetail(<?= $m['id'] ?>, event)">
                    <div class="absolute top-0 left-0 w-full h-1.5" style="background: linear-gradient(to right, <?= $m['color1'] ?: '#ccc' ?>, <?= $m['color2'] ?: '#ddd' ?>);"></div>
                    <div class="w-full aspect-square rounded-[2rem] bg-sky-50 mb-4 shadow-inner overflow-hidden flex items-center justify-center">
                        <?php if(!empty($m['image_url'])): ?>
                            <img src="/assets/img/members/<?= $m['image_url'] ?>" class="portrait-img">
                        <?php else: ?>
                            <span class="text-4xl font-black text-sky-200"><?= mb_substr($m['name'], 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[9px] font-black text-sky-400 uppercase tracking-widest"><?= $m['generation'] ?>期生</span>
                        <h3 class="font-black text-slate-800 text-base"><?= htmlspecialchars($m['name']) ?></h3>
                        <?php if(!$m['is_active']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">卒業</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="memberList" class="hidden max-w-5xl mx-auto mt-4">
                <div class="overflow-x-auto bg-white rounded-3xl border border-sky-50 shadow-sm">
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
                            <?php
                            $currentGroup = null;
                            $penlightCell = function (?string $code, ?string $name): string {
                                if (empty($code) && empty($name)) {
                                    return '<span class="inline-flex items-center px-2 py-1 rounded-2xl bg-slate-100 text-[11px] font-bold text-slate-500 whitespace-nowrap">未設定</span>';
                                }
                                $label = htmlspecialchars($name ?: $code, ENT_QUOTES, 'UTF-8');
                                $hex = ltrim((string)$code, '#');
                                if (strlen($hex) === 6) {
                                    $r = hexdec(substr($hex, 0, 2));
                                    $g = hexdec(substr($hex, 2, 2));
                                    $b = hexdec(substr($hex, 4, 2));
                                    $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                                    $textColor = $lum > 186 ? '#111827' : '#ffffff';
                                } else {
                                    $textColor = '#111827';
                                }
                                $bg = $code ?: '#e5e7eb';
                                return '<span class="inline-flex items-center px-2 py-1 rounded-2xl text-[11px] font-bold whitespace-nowrap" style="background-color:'
                                    . htmlspecialchars($bg, ENT_QUOTES, 'UTF-8')
                                    . ';color:' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';">'
                                    . $label . '</span>';
                            };
                            ?>
                            <?php foreach ($members as $m): ?>
                            <?php
                                $group = $m['is_active'] ? 'active' : 'graduated';
                                if ($group !== $currentGroup):
                                    $currentGroup = $group;
                            ?>
                            <tr class="border-t border-slate-100 bg-slate-50/60">
                                <td colspan="5" class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-[0.2em]">
                                    <?= $group === 'active' ? '現役メンバー' : '卒業メンバー' ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr class="member-row border-t border-slate-50 hover:bg-sky-50/60 cursor-pointer"
                                data-gen="<?= $m['generation'] ?>" data-active="<?= $m['is_active'] ?>" onclick="showDetail(<?= $m['id'] ?>, event)">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                                        <?php $hasBlog = !empty($m['blog_url']); ?>
                                        <?php $hasInsta = !empty($m['insta_url']); ?>
                                        <?php $hasPv = !empty($m['pv_video_key']); ?>
                                        <?php if ($hasBlog): ?>
                                            <a href="<?= htmlspecialchars($m['blog_url']) ?>" target="_blank" class="w-7 h-7 rounded-full flex items-center justify-center bg-sky-50 text-sky-600 hover:bg-sky-100 text-xs" title="ブログ">
                                                <i class="fa-solid fa-blog"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="w-7 h-7 rounded-full flex items-center justify-center bg-slate-50 text-slate-300 text-xs" title="ブログ">
                                                <i class="fa-solid fa-blog"></i>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($hasInsta): ?>
                                            <a href="<?= htmlspecialchars($m['insta_url']) ?>" target="_blank" class="w-7 h-7 rounded-full flex items-center justify-center bg-pink-50 text-pink-600 hover:bg-pink-100 text-xs" title="Instagram">
                                                <i class="fa-brands fa-instagram"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="w-7 h-7 rounded-full flex items-center justify-center bg-slate-50 text-slate-300 text-xs" title="Instagram">
                                                <i class="fa-brands fa-instagram"></i>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($hasPv): ?>
                                            <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($m['pv_video_key']) ?>" target="_blank" class="w-7 h-7 rounded-full flex items-center justify-center bg-red-50 text-red-600 hover:bg-red-100 text-xs" title="個人PV">
                                                <i class="fa-brands fa-youtube"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="w-7 h-7 rounded-full flex items-center justify-center bg-slate-50 text-slate-300 text-xs" title="個人PV">
                                                <i class="fa-brands fa-youtube"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-100 flex items-center justify-center">
                                        <?php if (!empty($m['image_url'])): ?>
                                            <img src="/assets/img/members/<?= $m['image_url'] ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="text-[11px] font-black text-slate-400"><?= htmlspecialchars(mb_substr($m['name'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2 font-bold text-slate-800 whitespace-nowrap"><?= htmlspecialchars($m['name']) ?></td>
                                <td class="px-3 py-2 text-slate-600 whitespace-nowrap"><?= $m['generation'] ?>期</td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <?= $penlightCell($m['color1'] ?? null, $m['color1_name'] ?? null) ?>
                                        <span class="text-[11px] text-slate-400 font-bold mx-1">×</span>
                                        <?= $penlightCell($m['color2'] ?? null, $m['color2_name'] ?? null) ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- モーダル -->
    <div id="memberModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-slate-900/80 backdrop-blur-xl transition-all">
        <div class="relative w-full max-w-5xl mx-auto md:my-10 min-h-full flex items-center">
            <div class="modal-content bg-white w-full h-full md:h-auto md:max-h-[90vh] md:rounded-[3rem] shadow-2xl relative flex flex-col md:flex-row md:overflow-hidden min-h-screen md:min-h-0">
                <button onclick="closeModal()" class="fixed md:absolute top-4 right-4 md:top-6 md:right-6 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-[110] hover:bg-white shadow-lg"><i class="fa-solid fa-xmark text-lg"></i></button>

                <div class="w-full md:w-[380px] shrink-0 border-r border-slate-50 flex flex-col bg-white">
                    <div id="modalHeader" class="h-64 md:h-72 relative shrink-0 flex flex-col justify-end p-8 text-white overflow-hidden bg-slate-200">
                        <img id="modalImg" src="" class="absolute inset-0 w-full h-full object-cover hidden">
                        <div class="relative z-10 text-white drop-shadow-md h-full flex flex-col justify-between">
                            <span id="modalGen" class="text-[10px] font-black opacity-90 uppercase tracking-widest bg-black/20 px-2 py-0.5 rounded self-start"></span>
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

                    <div class="p-8 space-y-8 overflow-y-auto custom-scroll">
                        <div id="modalInfoArea" class="hidden bg-sky-50/50 p-5 rounded-3xl border border-sky-100/50">
                            <p class="text-[9px] font-black text-sky-400 uppercase mb-1 tracking-widest">紹介文</p>
                            <p id="modalInfo" class="text-sm font-medium text-slate-600 leading-relaxed whitespace-pre-wrap"></p>
                        </div>
                        <div id="penlightSection" class="hidden bg-white p-5 rounded-3xl border border-slate-100/70">
                            <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-widest">サイリウムカラー</p>
                            <div class="flex items-center gap-3">
                                <div id="penlight1" class="flex-1 h-9 rounded-2xl flex items-center justify-center text-[11px] font-bold shadow-sm border border-slate-100"></div>
                                <div id="penlight2" class="flex-1 h-9 rounded-2xl flex items-center justify-center text-[11px] font-bold shadow-sm border border-slate-100"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">血液型</p><p id="modalBlood" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">身長</p><p id="modalHeight" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">生年月日</p><p id="modalBirth" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">出身地</p><p id="modalPlace" class="text-base font-black text-slate-700">--</p></div>
                        </div>
                        <div id="snsLinks" class="grid grid-cols-1 gap-2 pb-10 md:pb-0">
                            <a id="blogBtn" href="#" target="_blank" class="h-12 bg-sky-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-sky-600 border border-sky-100/50 hover:bg-sky-100 transition-all"><i class="fa-solid fa-blog"></i> 公式ブログ</a>
                            <a id="instaBtn" href="#" target="_blank" class="h-12 bg-pink-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-pink-600 border border-pink-100/50 hover:bg-pink-100 transition-all"><i class="fa-brands fa-instagram text-lg"></i> Instagram</a>
                        </div>
                    </div>
                </div>

                <div id="videoContainer" class="flex-1 bg-slate-50 p-6 md:p-12 flex flex-col justify-center pb-32 md:pb-12 overflow-y-auto">
                    <div id="videoArea" class="hidden w-full max-w-3xl mx-auto">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 text-center">紹介動画</p>
                        <div class="aspect-video w-full rounded-[1.5rem] md:rounded-[2.5rem] overflow-hidden bg-black shadow-2xl ring-4 md:ring-8 ring-white">
                            <iframe id="modalVideo" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                    <div id="noVideoMsg" class="text-center text-slate-300 py-20">
                        <i class="fa-brands fa-youtube text-6xl mb-4 opacity-20"></i>
                        <p class="text-[10px] font-bold uppercase tracking-widest">紹介動画はありません</p>
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

        function applyFilters() {
            const cards = document.querySelectorAll('.member-card');
            const rows = document.querySelectorAll('.member-row');
            const shouldShow = (el) => {
                const gen = el.dataset.gen;
                const isActive = el.dataset.active === '1';
                if (!showGraduates && !isActive) return false;
                if (currentGen === 'all') return true;
                return String(gen) === String(currentGen);
            };
            cards.forEach(card => {
                card.style.display = shouldShow(card) ? 'flex' : 'none';
            });
            rows.forEach(row => {
                row.style.display = shouldShow(row) ? '' : 'none';
            });
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
            if (d.image_url) { mImg.src = '/assets/img/members/' + d.image_url; mImg.classList.remove('hidden'); } else { mImg.classList.add('hidden'); }

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

        // 初期状態：カード表示・卒業メンバー非表示でフィルタ適用
        applyFilters();
    </script>
</body>
</html>