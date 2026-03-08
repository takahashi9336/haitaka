<?php
/**
 * 管理者向けユーティリティFAB
 * 右下に丸型アイコン、クリックで放射状にピル型ボタン展開
 * 1.改善事項を追加 2.対応管理へ
 * Admin 権限のみ表示（HinataAdmin は不可）
 * $adminFabOverHinata: true のとき Hinata FAB の上に配置
 */
$adminFabOverHinata = $adminFabOverHinata ?? false;
?>
<script src="/assets/js/core.js?v=2"></script>
<div id="admin-utility-fab" class="fixed right-6 z-[9000] <?= $adminFabOverHinata ? 'admin-fab-position' : 'admin-fab-alone' ?>" aria-label="管理者ユーティリティメニュー">
    <div id="adminFabOverlay" class="fixed inset-0 z-[8999] hidden transition-opacity duration-200" aria-hidden="true"></div>
    <div class="relative z-[9100]">
        <div id="adminFabArc" class="absolute transition-opacity duration-200" data-state="closed" aria-hidden="true">
            <button type="button" id="adminFabAddBtn" class="admin-fab-pill admin-fab-add absolute flex items-center gap-2 pl-3 pr-4 py-2 rounded-full bg-slate-50 shadow-lg border-2 border-slate-200 text-slate-600 hover:scale-105 hover:bg-slate-100 transition-all whitespace-nowrap" title="改善事項を追加">
                <i class="fa-solid fa-plus text-lg shrink-0"></i>
                <span class="text-xs font-bold">改善事項を追加</span>
            </button>
            <a href="/admin/improvement_list.php" class="admin-fab-pill admin-fab-list absolute flex items-center gap-2 pl-3 pr-4 py-2 rounded-full bg-slate-50 shadow-lg border-2 border-slate-200 text-slate-600 no-underline hover:scale-105 hover:bg-slate-100 transition-all whitespace-nowrap" title="対応管理">
                <i class="fa-solid fa-list-check text-lg shrink-0"></i>
                <span class="text-xs font-bold">対応管理へ</span>
            </a>
        </div>

        <button type="button" id="adminFabBtn" class="w-16 h-16 rounded-full overflow-hidden bg-slate-700 shadow-lg border-2 border-white focus:outline-none focus:ring-2 focus:ring-slate-400 flex items-center justify-center shrink-0" aria-expanded="false" aria-haspopup="true" title="管理者ユーティリティ">
            <i class="fa-solid fa-wrench text-white text-2xl"></i>
        </button>
    </div>

    <div id="adminFabAddPanel" class="admin-add-panel fixed right-6 bottom-2 left-4 md:left-auto md:max-w-sm bg-white rounded-xl border border-slate-200 p-3 z-[9101] transition-all duration-200 ease-out" data-state="closed" aria-hidden="true">
        <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-bold text-slate-500 tracking-wider">改善事項を追加</span>
            <button type="button" id="adminFabAddClose" class="text-slate-400 hover:text-slate-600 p-1" aria-label="閉じる">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="adminFabAddForm" class="space-y-2">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">画面名</label>
                <input type="text" name="screen_name" id="adminFabScreenName" placeholder="例: イベント一覧" class="w-full h-9 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2 focus:ring-slate-200">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">改善事項</label>
                <textarea name="content" id="adminFabContent" required placeholder="改善したい内容を入力" rows="3" class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-slate-200 resize-none min-h-[72px]"></textarea>
            </div>
            <input type="hidden" name="source_url" id="adminFabSourceUrl">
            <button type="submit" class="w-full h-9 bg-slate-700 text-white rounded-lg font-bold text-sm hover:bg-slate-600 transition">
                追加
            </button>
        </form>
    </div>
</div>

<style>
#admin-utility-fab.admin-fab-position { bottom: 6rem; }
#admin-utility-fab.admin-fab-alone { bottom: 1.5rem; }

#adminFabArc {
    --fab-radius: 80px;
    width: 200px;
    height: 200px;
    left: -60px;
    top: -60px;
    overflow: visible;
    pointer-events: none;
}
#adminFabArc[data-state="closed"] { opacity: 0; }
#adminFabArc[data-state="open"] { opacity: 1; }
#adminFabArc[data-state="open"] > * { pointer-events: auto; }
#adminFabArc .admin-fab-pill {
    -webkit-tap-highlight-color: transparent;
    position: absolute;
    left: 50%;
    top: 50%;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease-out;
}
#adminFabArc[data-state="closed"] .admin-fab-pill {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0);
}
#adminFabArc[data-state="open"] .admin-fab-add {
    opacity: 1;
    transform: translate(calc(-50% - var(--fab-radius)), -50%) scale(1);
    transition-delay: 0ms;
}
#adminFabArc[data-state="open"] .admin-fab-list {
    opacity: 1;
    transform: translate(calc(-50% - 56px), calc(-50% - 56px)) scale(1);
    transition-delay: 50ms;
}
#adminFabArc .admin-fab-pill::after {
    content: '';
    position: absolute;
    width: 0;
    height: 0;
    border: 6px solid transparent;
}
.admin-fab-add::after {
    left: 100%;
    top: 50%;
    margin-top: -6px;
    border-left-color: #f1f5f9;
    border-right: none;
}
.admin-fab-list::after {
    left: 100%;
    top: 100%;
    margin-left: -2px;
    margin-top: -2px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent;
    border-top-color: #f1f5f9;
    border-left-color: #f1f5f9;
}

.admin-add-panel {
    box-shadow: 0 4px 20px rgba(71, 85, 105, 0.15), 0 0 0 1px rgba(71, 85, 105, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
}
#adminFabAddPanel[data-state="closed"] {
    opacity: 0;
    transform: translateY(12px);
    pointer-events: none;
}
#adminFabAddPanel[data-state="open"] {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
#adminFabOverlay.admin-overlay-scroll-through { pointer-events: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('adminFabBtn');
    const arc = document.getElementById('adminFabArc');
    const addBtn = document.getElementById('adminFabAddBtn');
    const addPanel = document.getElementById('adminFabAddPanel');
    const addClose = document.getElementById('adminFabAddClose');
    const overlay = document.getElementById('adminFabOverlay');
    const form = document.getElementById('adminFabAddForm');
    const screenNameInput = document.getElementById('adminFabScreenName');
    const contentInput = document.getElementById('adminFabContent');
    const sourceUrlInput = document.getElementById('adminFabSourceUrl');

    if (!btn || !arc) return;

    function setDefaultScreenName() {
        if (screenNameInput && document.title) {
            var t = document.title.replace(/\s*[-|]\s*MyPlatform.*$/i, '').trim();
            if (t && !screenNameInput.value) screenNameInput.placeholder = t;
        }
        if (sourceUrlInput && window.location) {
            sourceUrlInput.value = window.location.pathname + (window.location.search || '');
        }
    }

    function toggleMenu() {
        var isOpen = arc.getAttribute('data-state') === 'open';
        arc.setAttribute('data-state', isOpen ? 'closed' : 'open');
        arc.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        if (isOpen) {
            overlay.classList.add('hidden');
            overlay.classList.remove('admin-overlay-scroll-through');
        } else {
            overlay.classList.remove('hidden');
            overlay.classList.add('admin-overlay-scroll-through');
        }
    }

    function closeAll() {
        arc.setAttribute('data-state', 'closed');
        arc.setAttribute('aria-hidden', 'true');
        addPanel.setAttribute('data-state', 'closed');
        addPanel.setAttribute('aria-hidden', 'true');
        overlay.classList.add('hidden');
        overlay.classList.remove('admin-overlay-scroll-through');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (addPanel.getAttribute('data-state') !== 'open') {
            toggleMenu();
        } else {
            closeAll();
        }
    });

    overlay.addEventListener('click', closeAll);

    if (addBtn) {
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            arc.setAttribute('data-state', 'closed');
            arc.setAttribute('aria-hidden', 'true');
            overlay.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'false');
            addPanel.setAttribute('data-state', 'open');
            addPanel.setAttribute('aria-hidden', 'false');
            overlay.classList.remove('hidden');
            overlay.classList.add('admin-overlay-scroll-through');
            setDefaultScreenName();
        });
    }

    addClose.addEventListener('click', closeAll);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        var screenName = screenNameInput ? screenNameInput.value.trim() : '';
        var content = contentInput ? contentInput.value.trim() : '';
        var sourceUrl = sourceUrlInput ? sourceUrlInput.value.trim() : '';
        if (!content) return;
        if (!screenName) screenName = document.title || window.location.pathname || '不明';
        var res = await App.post('/admin/api/save_improvement_item.php', {
            screen_name: screenName,
            content: content,
            source_url: sourceUrl || null
        });
        if (res.status === 'success') {
            App.toast('改善事項を追加しました');
            if (contentInput) contentInput.value = '';
            if (screenNameInput) screenNameInput.value = '';
            closeAll();
        } else {
            App.toast('エラー: ' + (res.message || '保存に失敗しました'));
        }
    });
});
</script>
