<?php
/**
 * ペンライトカラー表（初心者向け）View
 * 物理パス: haitaka/private/apps/Hinata/Views/penlight.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$membersJson = json_encode($members ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$backUrl = '/hinata/index.php';
if (isset($_GET['from']) && $_GET['from'] === 'live_guide' && !empty($_GET['event_id'])) {
    $backUrl = '/hinata/live_guide.php?event_id=' . (int)$_GET['event_id'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ペンライトカラー表 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .filter-btn.active { background-color: var(--hinata-theme); color: white; border-color: var(--hinata-theme); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .penlight-chip {
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
            max-width: 100%;
        }
        /* スマホで横スクロールを発生させない */
        table.penlight-table { width: 100%; table-layout: fixed; }
        table.penlight-table td { overflow: hidden; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
        <div class="flex items-center gap-2 min-w-0">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
            <a href="<?= htmlspecialchars($backUrl) ?>" onclick="event.preventDefault();App.goBack('<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>');" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-lightbulb text-sm"></i>
            </div>
            <h1 class="font-black text-slate-700 text-lg tracking-tight truncate">ペンライトカラー表</h1>
        </div>
        <span class="text-[10px] font-black text-slate-400 tracking-wider hidden sm:inline">初心者向け</span>
    </header>

    <div class="bg-white border-b <?= $cardBorder ?> px-4 py-3 space-y-3 shrink-0">
        <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                <button class="filter-btn active px-5 py-1.5 rounded-full border border-slate-200 text-[10px] font-black tracking-wider transition" data-gen="all">全員</button>
                <?php foreach ([1,2,3,4,5] as $g): ?>
                    <button class="filter-btn px-5 py-1.5 rounded-full border border-slate-200 text-[10px] font-black tracking-wider transition" data-gen="<?= $g ?>"><?= $g ?>期</button>
                <?php endforeach; ?>
            </div>
            <button id="toggleGradBtn" class="px-3 py-1.5 rounded-full border border-slate-200 text-[10px] font-black text-slate-500 bg-white">
                卒業メンバー非表示
            </button>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            <div class="flex-1 relative">
                <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 text-sm"></i>
                <input id="searchInput" type="text" class="w-full h-11 pl-9 pr-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-sky-200 bg-white"
                       placeholder="名前で検索（例：こさか / 小坂）">
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-8 pb-24">
        <div id="resultSummary" class="max-w-5xl mx-auto mb-3 text-[10px] font-black text-slate-400 tracking-wider"></div>

        <section class="max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-4">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                <h2 class="text-[10px] font-black text-slate-400 tracking-wider">公式ペンライト発光色（①〜⑮）</h2>
            </div>
            <div class="p-4 md:p-6">
                <img
                        src="/assets/img/hinata/penlight_colors_1to15.png"
                        alt="日向坂46 公式ペンライト発光色一覧（①〜⑮）"
                        class="w-full h-auto rounded-xl border border-slate-100"
                        loading="lazy">
            </div>
        </section>

        <div class="max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                <h2 class="text-[10px] font-black text-slate-400 tracking-wider">氏名 / カラー1 / カラー2</h2>
            </div>
            <div>
                <table class="penlight-table w-full text-sm">
                    <thead class="sr-only">
                        <tr><th>氏名</th><th>カラー1</th><th>カラー2</th></tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div id="empty" class="hidden max-w-5xl mx-auto mt-4 bg-white rounded-2xl border <?= $cardBorder ?> p-6 text-center text-slate-400">
            <i class="fa-solid fa-face-sad-tear text-3xl mb-3"></i>
            <p class="font-bold">該当するメンバーが見つかりません</p>
            <p class="text-xs mt-1">検索条件を変えてみてください</p>
        </div>
    </div>
</main>

<!-- アー写拡大モーダル -->
<div id="artistModal" class="hidden fixed inset-0 z-[9999]">
    <div id="artistModalBg" class="absolute inset-0 bg-black/70"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md">
            <button id="artistModalClose" type="button" class="absolute -top-3 -right-3 w-10 h-10 rounded-full bg-white text-slate-600 shadow flex items-center justify-center hover:bg-slate-50">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="bg-white rounded-2xl overflow-hidden shadow-xl border border-slate-200">
                <img id="artistModalImg" src="" alt="アー写" class="w-full h-auto object-contain bg-slate-100">
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/core.js?v=2"></script>
<script>
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.add('mobile-open');
    });

    const MEMBERS = <?= $membersJson ?>;

    let currentGen = 'all';
    let hideGraduates = true;
    let query = '';

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function getTextColor(bgHex) {
        const hex = (bgHex || '').replace('#', '').trim();
        if (hex.length !== 6) return '#0f172a'; // slate-900
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        // perceived luminance
        const lum = (0.299 * r + 0.587 * g + 0.114 * b);
        return lum < 150 ? '#ffffff' : '#0f172a';
    }

    function render() {
        const q = (query || '').trim().toLowerCase();
        let rows = MEMBERS.slice();

        if (hideGraduates) rows = rows.filter(m => parseInt(m.is_active || 0, 10) === 1);
        if (currentGen !== 'all') rows = rows.filter(m => String(m.generation || '') === String(currentGen));
        if (q) rows = rows.filter(m => String(m.name || '').toLowerCase().includes(q) || String(m.kana || '').toLowerCase().includes(q));

        // 並び：期→かな（画像のイメージに合わせる）
        rows.sort((a, b) => {
            const ga = parseInt(a.generation || 0, 10);
            const gb = parseInt(b.generation || 0, 10);
            if (ga !== gb) return ga - gb;
            return String(a.kana || '').localeCompare(String(b.kana || ''), 'ja');
        });

        const tbody = document.getElementById('tableBody');
        const empty = document.getElementById('empty');
        const summary = document.getElementById('resultSummary');

        summary.textContent = `表示：${rows.length}人 / 全${MEMBERS.length}人`;

        if (!rows.length) {
            tbody.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');

        let lastGen = null;
        let html = '';

        rows.forEach(m => {
            const genNum = m.generation ? parseInt(m.generation, 10) : null;
            const genLabel = genNum ? `${genNum}期生` : '';
            if (currentGen === 'all' && genLabel && genLabel !== lastGen) {
                lastGen = genLabel;
                html += `
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-[11px] font-black text-slate-600 bg-slate-50/80 border-t border-slate-200">
                            ${esc(genLabel)}
                        </td>
                    </tr>
                `;
            }

            const c1 = m.color1 || '';
            const c2 = m.color2 || '';
            const n1 = m.color1_name || '';
            const n2 = m.color2_name || '';
            const active = parseInt(m.is_active || 0, 10) === 1;

            const makeColorTd = (colorCode, colorName, fallbackLabel) => {
                const code = (colorCode || '').trim();
                const name = (colorName || fallbackLabel || '').trim();
                const bg = code !== '' ? code : '#e2e8f0';
                const fg = getTextColor(bg);
                const label = name || '未設定';
                return `
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="penlight-chip px-3 py-2 sm:px-4 sm:py-3 font-black inline-flex items-center justify-center text-center w-full text-xs sm:text-sm leading-snug overflow-x-auto no-scrollbar" style="background:${esc(bg)};color:${esc(fg)};-webkit-overflow-scrolling:touch;">
                            <span class="whitespace-nowrap">${esc(label)}</span>
                        </div>
                    </td>
                `;
            };

            const artistImg = m.latest_single_artist_image || '';
            const artistImgHtml = artistImg
                ? `<img src="${esc(artistImg)}" alt="" class="penlight-artist-img w-28 h-28 rounded-xl object-cover object-top border border-slate-200 shrink-0 bg-white cursor-zoom-in" data-modal-src="${esc(artistImg)}" onerror="this.style.display='none'">`
                : '';

            html += `
                <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-4 py-3 align-top">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap min-w-0">
                                <span class="font-black text-slate-900 truncate text-sm">${esc(m.name || '')}</span>
                                ${active ? '' : '<span class="text-[10px] font-black text-slate-400">(卒業)</span>'}
                            </div>
                            ${artistImgHtml}
                        </div>
                    </td>
                    ${makeColorTd(c1, n1, '')}
                    ${makeColorTd(c2 || c1, n2 || (n1 ? '（同色）' : ''), '')}
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentGen = btn.dataset.gen || 'all';
            render();
        });
    });

    document.getElementById('toggleGradBtn')?.addEventListener('click', () => {
        hideGraduates = !hideGraduates;
        document.getElementById('toggleGradBtn').textContent = hideGraduates ? '卒業メンバー非表示' : '卒業メンバー表示中';
        render();
    });

    document.getElementById('searchInput')?.addEventListener('input', (e) => {
        query = e.target.value || '';
        render();
    });

    // --- アー写拡大モーダル ---
    (function() {
        const modal = document.getElementById('artistModal');
        const modalBg = document.getElementById('artistModalBg');
        const modalImg = document.getElementById('artistModalImg');
        const btnClose = document.getElementById('artistModalClose');

        function open(src) {
            if (!modal || !modalImg || !src) return;
            modalImg.src = src;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            if (!modal || !modalImg) return;
            modal.classList.add('hidden');
            modalImg.src = '';
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function(e) {
            const t = e.target;
            if (t && t.classList && t.classList.contains('penlight-artist-img')) {
                const src = t.getAttribute('data-modal-src') || t.getAttribute('src');
                open(src);
            }
        });
        modalBg?.addEventListener('click', close);
        btnClose?.addEventListener('click', close);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) close();
        });
    })();

    render();
</script>
</body>
</html>

