<?php
/**
 * 共通ガイド表示コンポーネント
 * ?アイコンからモーダル表示、または初回1回だけ自動表示
 *
 * 使用例:
 *   $guideKey = 'meetgreet_import';
 *   require_once __DIR__ . '/guide_display.php';
 *
 * 出力: ?アイコン + モーダル用マークアップ
 * guideKey が渡されている必要あり。未設定の場合は何も出力しない。
 */
if (empty($guideKey)) return;

$guideId = 'guide-' . preg_replace('/[^a-z0-9_-]/i', '-', $guideKey);
?>
<button type="button" id="<?= $guideId ?>-btn" class="guide-help-btn inline-flex items-center justify-center w-7 h-7 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition" title="使い方を確認" aria-label="ガイドを表示">
    <i class="fa-solid fa-circle-question text-sm"></i>
</button>
<div id="<?= $guideId ?>-modal" class="guide-modal fixed inset-0 z-50 hidden">
    <div class="guide-modal-backdrop absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="GuideDisplay.close('<?= $guideId ?>')"></div>
    <div class="relative z-10 flex items-center justify-center min-h-full p-4">
        <div class="guide-modal-content bg-white rounded-2xl shadow-2xl w-full max-w-2xl md:max-w-4xl lg:max-w-5xl max-h-[85vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 md:px-8 py-4 border-b border-slate-100 shrink-0">
                <h2 id="<?= $guideId ?>-title" class="text-lg md:text-xl font-bold text-slate-700">ガイド</h2>
                <button type="button" onclick="GuideDisplay.close('<?= $guideId ?>')" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div id="<?= $guideId ?>-body" class="flex-1 overflow-y-auto px-6 md:px-8 py-4 md:py-6 space-y-4 md:space-y-6 text-base md:text-lg text-slate-600 leading-relaxed"></div>
        </div>
    </div>
</div>
<script>
(function() {
    const guideKey = <?= json_encode($guideKey) ?>;
    const guideId = <?= json_encode($guideId) ?>;
    const storageKey = 'guide_shown_' + guideKey;

    window.GuideDisplay = window.GuideDisplay || {};
    GuideDisplay.modals = GuideDisplay.modals || {};
    GuideDisplay.modals[guideId] = { guideKey, shown: false };

    GuideDisplay.open = function(id) {
        const m = document.getElementById(id + '-modal');
        if (m) m.classList.remove('hidden');
        const mdata = GuideDisplay.modals[id];
        if (mdata && mdata.guideKey && !mdata.loaded) {
            GuideDisplay.load(id, mdata.guideKey);
        }
    };
    GuideDisplay.close = function(id) {
        const m = document.getElementById(id + '-modal');
        if (m) m.classList.add('hidden');
    };
    GuideDisplay.load = function(id, key) {
        const mdata = GuideDisplay.modals[id];
        if (!mdata) return;
        fetch('/api/guide.php?guide_key=' + encodeURIComponent(key))
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success' || !res.guide) {
                    document.getElementById(id + '-body').innerHTML = '<p class="text-slate-400">ガイドが見つかりません</p>';
                    document.getElementById(id + '-btn').classList.add('hidden');
                    return;
                }
                const g = res.guide;
                document.getElementById(id + '-title').textContent = g.title || 'ガイド';
                const body = document.getElementById(id + '-body');
                let html = '';
                const esc = function(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
                (g.blocks || []).forEach(function(b) {
                    if (b.type === 'text') {
                        html += '<div class="text-slate-600 leading-relaxed">' + esc(b.content || '').replace(/\n/g, '<br>') + '</div>';
                    } else if (b.type === 'image' && b.src) {
                        html += '<div class="my-2 md:my-4"><img src="' + esc(b.src) + '" alt="' + esc(b.alt || '') + '" class="max-w-full max-h-64 md:max-h-[28rem] rounded-lg border border-slate-200 shadow-sm object-contain"></div>';
                    }
                });
                body.innerHTML = html || '<p class="text-slate-400">内容がありません</p>';
                mdata.loaded = true;
            })
            .catch(function() {
                document.getElementById(id + '-body').innerHTML = '<p class="text-slate-400">読み込みに失敗しました</p>';
            });
    };

    document.getElementById(guideId + '-btn').onclick = function() {
        GuideDisplay.open(guideId);
    };

    if (typeof document.addEventListener === 'function') {
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/api/guide.php?guide_key=' + encodeURIComponent(guideKey))
                .then(r => r.json())
                .then(function(res) {
                    if (res.status !== 'success' || !res.guide) return;
                    const g = res.guide;
                    if (!g.show_on_first_visit) return;
                    if (localStorage.getItem(storageKey)) return;
                    GuideDisplay.modals[guideId].loaded = false;
                    GuideDisplay.open(guideId);
                    localStorage.setItem(storageKey, new Date().toISOString());
                })
                .catch(function() {});
        });
    }
})();
</script>
