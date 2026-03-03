<?php
/**
 * 日向坂ユーティリティFAB
 * 右下に丸型アイコン（最推し画像）、フォーカス/タッチで放射状にピル型＋吹き出し尾ボタン展開（極座標配置・展開アニメーション）
 * 1.ホーム(90°左) 2.ネタ登録(45°左上) 3.推し(直上)
 */
$favModel = new \App\Hinata\Model\FavoriteModel();
$oshiMembers = $favModel->getOshiMembers();
$topOshi = $oshiMembers[0] ?? null;

function hinataFabImgSrc(?string $imageUrl): string {
    if (!$imageUrl) return '';
    return str_starts_with($imageUrl, '/') ? htmlspecialchars($imageUrl) : '/assets/img/members/' . htmlspecialchars($imageUrl);
}
$topOshiSrc = $topOshi ? hinataFabImgSrc($topOshi['image_url'] ?? null) : '';
$topOshiUrl = $topOshi ? '/hinata/member.php?id=' . (int)$topOshi['member_id'] : null;
?>
<script src="/assets/js/core.js?v=2"></script>
<div id="hinata-utility-fab" class="fixed right-6 bottom-6 z-[9000]" aria-label="ユーティリティメニュー">
    <div id="hinataFabOverlay" class="fixed inset-0 bg-black/20 z-[8999] hidden" aria-hidden="true"></div>
    <div class="relative z-[9100]">
        <!-- ピル型＋吹き出し尾（画像アイコン方向に尾）・展開アニメーション -->
        <div id="hinataFabArc" class="absolute transition-opacity duration-200" data-state="closed" aria-hidden="true">
            <!-- 1.ホーム：90°左（尾は右＝FAB向き） -->
            <a href="/hinata/" class="hinata-fab-pill hinata-fab-home absolute flex items-center gap-2 pl-3 pr-4 py-2 rounded-full bg-sky-50 shadow-lg border-2 border-sky-200 text-sky-600 no-underline hover:scale-105 hover:bg-sky-100 transition-all whitespace-nowrap" title="日向坂ポータル">
                <i class="fa-solid fa-house text-lg shrink-0"></i>
                <span class="text-xs font-bold">ホーム</span>
            </a>
            <!-- 2.ネタ登録：左上45°（尾は右下＝FAB向き） -->
            <button type="button" id="hinataFabNeta" class="hinata-fab-pill hinata-fab-neta absolute flex items-center gap-2 pl-3 pr-4 py-2 rounded-full bg-sky-50 shadow-lg border-2 border-sky-200 text-sky-600 hover:scale-105 hover:bg-sky-100 transition-all whitespace-nowrap" data-action="neta" title="ネタ登録">
                <i class="fa-solid fa-pen-to-square text-lg shrink-0"></i>
                <span class="text-xs font-bold">ネタ</span>
            </button>
            <!-- 3.推し：直上（尾は下＝FAB向き） -->
            <?php if ($topOshiUrl): ?>
            <a href="<?= htmlspecialchars($topOshiUrl) ?>" class="hinata-fab-pill hinata-fab-oshi absolute flex items-center gap-2 pl-3 pr-4 py-2 rounded-full bg-sky-50 shadow-lg border-2 border-sky-200 text-sky-600 font-bold hover:scale-105 hover:bg-sky-100 transition-all no-underline whitespace-nowrap" title="推し">
                <span class="text-base shrink-0">推</span>
                <span class="text-xs font-bold">推し</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- メインFABボタン（画像アイコン・80px） -->
        <button type="button" id="hinataFabBtn" class="w-20 h-20 rounded-full overflow-hidden bg-slate-200 shadow-lg border-2 border-white focus:outline-none focus:ring-2 focus:ring-sky-400 flex items-center justify-center shrink-0" aria-expanded="false" aria-haspopup="true">
            <?php if ($topOshiSrc): ?>
                <img src="<?= $topOshiSrc ?>" alt="最推し" class="w-full h-full object-cover">
            <?php else: ?>
                <i class="fa-solid fa-lightbulb text-slate-600 text-3xl"></i>
            <?php endif; ?>
        </button>
    </div>

    <!-- ミーグリネタ登録パネル -->
    <div id="hinataFabNetaPanel" class="fixed right-6 bottom-24 left-4 md:left-auto md:max-w-sm hidden bg-white rounded-xl shadow-xl border border-slate-200 p-4 z-[9101]">
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-bold text-slate-500 tracking-wider">ミーグリネタ登録</span>
            <button type="button" id="hinataFabNetaClose" class="text-slate-400 hover:text-slate-600 p-1" aria-label="閉じる">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="hinataFabNetaForm" class="space-y-3">
            <select name="member_id" id="hinataFabMemberId" required class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2 focus:ring-sky-200 focus:border-sky-300">
                <option value="">メンバーを選択</option>
            </select>
            <textarea name="content" id="hinataFabContent" required placeholder="何を話す？" rows="3" class="w-full border border-slate-200 rounded-lg p-3 text-sm outline-none focus:ring-2 focus:ring-sky-200 focus:border-slate-300 resize-none"></textarea>
            <button type="submit" class="w-full bg-sky-500 text-white h-10 rounded-lg font-bold text-sm hover:bg-sky-600 transition">
                ネタを追加
            </button>
        </form>
    </div>
</div>

<style>
/* 極座標配置: FAB中心を原点に 90°左・45°左上・直上 */
#hinataFabArc {
    --fab-radius: 72px;
    width: 200px;
    height: 200px;
    left: -60px;
    top: -60px;
    overflow: visible;
    pointer-events: none;
}
#hinataFabArc[data-state="closed"] {
    opacity: 0;
}
#hinataFabArc[data-state="open"] {
    opacity: 1;
}
#hinataFabArc[data-state="open"] > * {
    pointer-events: auto;
}
#hinataFabArc .hinata-fab-pill {
    -webkit-tap-highlight-color: transparent;
    position: absolute;
    left: 50%;
    top: 50%;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease-out, background-color 0.15s, box-shadow 0.15s;
}
/* 閉じている時: 中心から縮小・非表示 */
#hinataFabArc[data-state="closed"] .hinata-fab-pill {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0);
}
/* 開いている時: 展開アニメーション（順次表示） */
#hinataFabArc[data-state="open"] .hinata-fab-home {
    opacity: 1;
    transform: translate(calc(-50% - var(--fab-radius)), -50%) scale(1);
    transition-delay: 0ms;
}
#hinataFabArc[data-state="open"] .hinata-fab-neta {
    opacity: 1;
    transform: translate(calc(-50% - 51px), calc(-50% - 51px)) scale(1);
    transition-delay: 50ms;
}
#hinataFabArc[data-state="open"] .hinata-fab-oshi {
    opacity: 1;
    transform: translate(-50%, calc(-50% - var(--fab-radius))) scale(1);
    transition-delay: 100ms;
}

/* ピル型ボタンの吹き出し尾（画像アイコン＝FAB方向を指す） */
#hinataFabArc .hinata-fab-pill::after {
    content: '';
    position: absolute;
    width: 0;
    height: 0;
    border: 6px solid transparent;
}
/* ホーム：尾が右向き（FAB＝画像アイコン方向） */
.hinata-fab-home::after {
    left: 100%;
    top: 50%;
    margin-top: -6px;
    border-left-color: #e0f2fe;
    border-right: none;
}
/* ネタ：尾が右下向き（FAB方向） */
.hinata-fab-neta::after {
    left: 100%;
    top: 100%;
    margin-left: -2px;
    margin-top: -2px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent;
    border-top-color: #e0f2fe;
    border-left-color: #e0f2fe;
}
/* 推し：尾が下向き（FAB方向） */
.hinata-fab-oshi::after {
    left: 50%;
    top: 100%;
    margin-left: -6px;
    border-top-color: #e0f2fe;
    border-bottom: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('hinataFabBtn');
    const arc = document.getElementById('hinataFabArc');
    const netaBtn = document.getElementById('hinataFabNeta');
    const netaPanel = document.getElementById('hinataFabNetaPanel');
    const netaClose = document.getElementById('hinataFabNetaClose');
    const overlay = document.getElementById('hinataFabOverlay');
    const netaForm = document.getElementById('hinataFabNetaForm');
    const memberSelect = document.getElementById('hinataFabMemberId');

    if (!btn || !arc) return;

    function toggleMenu() {
        const isOpen = arc.getAttribute('data-state') === 'open';
        arc.setAttribute('data-state', isOpen ? 'closed' : 'open');
        arc.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        if (isOpen) overlay.classList.add('hidden');
        else overlay.classList.remove('hidden');
    }

    function closeAll() {
        arc.setAttribute('data-state', 'closed');
        arc.setAttribute('aria-hidden', 'true');
        netaPanel.classList.add('hidden');
        overlay.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (netaPanel.classList.contains('hidden')) {
            toggleMenu();
        } else {
            closeAll();
        }
    });

    overlay.addEventListener('click', closeAll);

    if (netaBtn) {
        netaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            arc.setAttribute('data-state', 'closed');
            arc.setAttribute('aria-hidden', 'true');
            overlay.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
            openNetaPanel();
        });
    }

    function openNetaPanel() {
        netaPanel.classList.remove('hidden');
        overlay.classList.remove('hidden');
        if (memberSelect && memberSelect.options.length <= 1) loadMembers();
    }

    netaClose.addEventListener('click', closeAll);

    async function loadMembers() {
        if (!memberSelect) return;
        try {
            const res = await fetch('/hinata/api/get_members_for_select.php');
            const data = await res.json();
            if (data.status !== 'success' || !data.members) return;
            while (memberSelect.options.length > 1) memberSelect.remove(1);
            data.members.forEach(function(m) {
                const opt = document.createElement('option');
                opt.value = m.id;
                const prefix = m.favorite_level >= 2 ? '\u2764 ' : (m.favorite_level === 1 ? '\u2B50 ' : '');
                opt.textContent = prefix + m.name;
                memberSelect.appendChild(opt);
            });
        } catch (err) {
            console.error('Failed to load members:', err);
        }
    }

    netaForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const memberId = memberSelect.value;
        const content = document.getElementById('hinataFabContent').value.trim();
        if (!memberId || !content) return;
        const res = await App.post('/hinata/api/save_neta.php', { member_id: memberId, content: content });
        if (res.status === 'success') {
            App.toast('ネタを追加しました');
            document.getElementById('hinataFabContent').value = '';
            closeAll();
        } else {
            App.toast('エラー: ' + (res.message || '保存に失敗しました'));
        }
    });
});
</script>
