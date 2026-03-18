<?php
/**
 * Sense Lab ユーティリティFAB
 * - Adminロールのみ表示
 * - 右下の丸ボタンから「クイックスクラップ」入力パネルを開き、sl_sense_quick_entries にテキストメモを保存
 */
?>
<script src="/assets/js/core.js?v=2"></script>
<div id="sense-lab-utility-fab" class="fixed right-6 bottom-32 z-[9000]" aria-label="Sense Lab クイックスクラップ">
    <div id="senseLabFabOverlay" class="fixed inset-0 z-[8999] hidden transition-opacity duration-200" aria-hidden="true"></div>
    <div class="relative z-[9100]">
        <button type="button" id="senseLabFabBtn" class="w-14 h-14 rounded-full bg-violet-600 text-white shadow-lg shadow-violet-300 border-2 border-white flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-violet-300"
                aria-expanded="false" aria-haspopup="true" title="Sense Lab クイックスクラップ">
            <i class="fa-solid fa-wand-magic-sparkles text-xl"></i>
        </button>
    </div>

    <div id="senseLabFabPanel" class="fixed right-4 bottom-32 left-4 md:left-auto md:bottom-32 md:max-w-sm lg:max-w-md bg-white rounded-xl border border-slate-200 p-3 z-[9101] shadow-lg shadow-violet-200/60 transition-all duration-200 ease-out"
         data-state="closed" aria-hidden="true">
        <div class="flex justify-between items-center mb-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                    <i class="fa-solid fa-pen text-xs"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-700 leading-tight">クイックスクラップ</p>
                    <p class="text-[10px] font-bold text-slate-400 tracking-wider">今のページの「いいな」を1メモだけ残す</p>
                </div>
            </div>
            <button type="button" id="senseLabFabClose" class="text-slate-400 hover:text-slate-600 p-1" aria-label="閉じる">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="senseLabFabForm" class="space-y-2">
            <textarea id="senseLabFabNote" name="note" rows="3"
                      class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-violet-200 resize-none min-h-[70px]"
                      placeholder="何が『いい』と感じた？ 1〜3行でざっくりメモ（後で画像や理由3つに仕上げます）"></textarea>
            <div class="flex gap-2 items-center">
                <select id="senseLabFabCategory" name="category_hint"
                        class="h-8 border border-slate-200 rounded-lg px-2 text-[11px] text-slate-600 bg-slate-50 outline-none">
                    <option value="">カテゴリ（任意）</option>
                    <option value="food">食事</option>
                    <option value="design">デザイン</option>
                    <option value="daily">日常</option>
                    <option value="other">その他</option>
                </select>
                <button type="submit"
                        class="ml-auto inline-flex items-center gap-1 h-8 px-4 rounded-lg bg-violet-600 text-white text-[11px] font-black tracking-wider hover:bg-violet-700 transition">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> 保存
                </button>
            </div>
        </form>
    </div>
</div>

<style>
#senseLabFabPanel[data-state="closed"] {
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
}
#senseLabFabPanel[data-state="open"] {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('senseLabFabBtn');
    const panel = document.getElementById('senseLabFabPanel');
    const closeBtn = document.getElementById('senseLabFabClose');
    const overlay = document.getElementById('senseLabFabOverlay');
    const form = document.getElementById('senseLabFabForm');
    const noteInput = document.getElementById('senseLabFabNote');
    const categorySelect = document.getElementById('senseLabFabCategory');

    if (!btn || !panel || !form || !noteInput) return;

    function openPanel() {
        panel.setAttribute('data-state', 'open');
        panel.setAttribute('aria-hidden', 'false');
        overlay.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
        setTimeout(() => noteInput.focus(), 50);
    }

    function closePanel() {
        panel.setAttribute('data-state', 'closed');
        panel.setAttribute('aria-hidden', 'true');
        overlay.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = panel.getAttribute('data-state') === 'open';
        if (isOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    overlay.addEventListener('click', closePanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const note = noteInput.value.trim();
        if (!note) {
            App.toast('メモを入力してください');
            noteInput.focus();
            return;
        }

        const appKey = (window.App && App.currentAppKey) ? App.currentAppKey() : null;
        const title = document.title || '';
        const pageTitle = title.replace(/\s*[-|]\s*MyPlatform.*$/i, '').trim() || title;
        const sourceUrl = window.location.pathname + (window.location.search || '');
        const categoryHint = categorySelect.value || '';

        try {
            const res = await App.post('/sense_lab/api/quick_save.php', {
                note,
                app_key: appKey,
                page_title: pageTitle,
                source_url: sourceUrl,
                category_hint: categoryHint
            });
            if (res.status === 'success') {
                App.toast('クイックスクラップを保存しました');
                noteInput.value = '';
                categorySelect.value = '';
                closePanel();
            } else {
                App.toast(res.message || '保存に失敗しました');
            }
        } catch (err) {
            console.error(err);
            App.toast('保存中にエラーが発生しました');
        }
    });
});
</script>

