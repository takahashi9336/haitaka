<?php
/**
 * テキストファイル管理 View（管理者専用）
 * - txt / md / html をサーバ上（private/storage）へ保存
 * - 一覧、プレビュー、削除
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$themeTailwind = $themeTailwind ?? 'slate';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>テキスト管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        <?php if ($isThemeHex): ?>
        .admin-btn-primary { background-color: var(--admin-theme); }
        .admin-btn-primary:hover { filter: brightness(1.08); }
        .admin-link { color: var(--admin-theme); }
        .admin-link:hover { background-color: <?= htmlspecialchars($themeLight ?: 'rgba(100,116,139,0.12)') ?>; }
        <?php endif; ?>
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .md-preview :is(h1,h2,h3) { font-weight: 900; margin-top: 1.2em; margin-bottom: 0.4em; }
        .md-preview h1 { font-size: 1.5rem; }
        .md-preview h2 { font-size: 1.25rem; }
        .md-preview h3 { font-size: 1.125rem; }
        .md-preview { line-height: 1.85; }
        .md-preview p { margin: 0.95em 0; }
        .md-preview :is(ul,ol) { padding-left: 1.2em; margin: 0.85em 0; }
        .md-preview code { background: rgba(148, 163, 184, 0.18); padding: 0.1em 0.35em; border-radius: 0.35em; }
        .md-preview pre { background: rgba(15, 23, 42, 0.92); color: #e2e8f0; padding: 0.9rem; border-radius: 0.75rem; overflow: auto; }
        .md-preview pre code { background: transparent; padding: 0; color: inherit; }
        .md-preview a { text-decoration: underline; }
        .md-preview blockquote { border-left: 3px solid rgba(148, 163, 184, 0.7); padding-left: 0.8em; color: rgb(71 85 105); margin: 0.8em 0; }
        .md-preview table { border-collapse: collapse; width: 100%; margin: 0.8em 0; }
        .md-preview th, .md-preview td { border: 1px solid rgb(226 232 240); padding: 0.4rem 0.6rem; font-size: 0.875rem; }
        .md-preview th { background: rgb(248 250 252); text-align: left; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/admin/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-file-lines text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">テキスト管理</h1>
            </div>
            <p class="text-[10px] font-bold text-slate-400 tracking-wider">txt / md / html を保存・プレビュー</p>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-10" data-scroll-persist="admin-text-files">
            <div class="max-w-[94rem] mx-auto flex flex-col gap-4 md:gap-6">
                <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div id="formHeader" class="px-5 py-4 border-b border-slate-100 flex items-center justify-between cursor-pointer select-none">
                        <div class="min-w-0">
                            <h2 class="font-black text-slate-800 tracking-tight">登録 / 更新</h2>
                            <p class="text-xs text-slate-500 mt-1">保存先はサーバ内（`private/storage`）。公開URLは持ちません。</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button id="btnToggleForm" type="button" class="text-xs font-bold text-slate-600 hover:bg-slate-100 px-3 py-2 rounded-xl transition">
                                <i class="fa-solid fa-chevron-down mr-1"></i> 展開
                            </button>
                            <button id="btnNew" type="button" class="text-xs font-bold text-slate-600 hover:bg-slate-100 px-3 py-2 rounded-xl transition">
                                <i class="fa-solid fa-pen-to-square mr-1"></i> 新規
                            </button>
                        </div>
                    </div>

                    <div id="formBody" class="p-5 space-y-3 hidden">
                        <input type="hidden" id="f_id" value="">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">タイトル</label>
                                <input id="f_title" type="text" maxlength="120" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm" placeholder="例: 便利メモ / 手順書 / 下書き">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">拡張子</label>
                                <select id="f_ext" class="w-full border border-slate-200 rounded-xl h-11 px-4 text-sm">
                                    <option value="txt">txt</option>
                                    <option value="md">md (Markdown)</option>
                                    <option value="html">html</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">本文</label>
                            <textarea id="f_content" rows="10" class="w-full border border-slate-200 rounded-xl p-3 text-sm mono min-h-[240px] focus:ring-2 focus:ring-slate-200 outline-none" placeholder="ここにテキストを入力して保存できます。Markdown もOK。"></textarea>
                            <div class="flex items-center justify-between mt-2">
                                <p id="metaHint" class="text-[11px] text-slate-400">最大 512KB</p>
                                <div class="flex items-center gap-2">
                                    <button id="btnSave" type="button" class="<?= $isThemeHex ? 'admin-btn-primary' : 'bg-slate-700 hover:bg-slate-600' ?> text-white text-xs font-black tracking-wider px-5 h-10 rounded-xl shadow-sm transition-all flex items-center gap-2"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                                        <i class="fa-solid fa-floppy-disk"></i> 保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col min-h-[640px]">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-black text-slate-800 tracking-tight">登録済み</h2>
                            <p class="text-xs text-slate-500 mt-1">クリックで読み込み / プレビュー。削除も可能。</p>
                        </div>
                        <button id="btnReload" type="button" class="text-xs font-bold text-slate-600 hover:bg-slate-100 px-3 py-2 rounded-xl transition shrink-0">
                            <i class="fa-solid fa-rotate mr-1"></i> 再読込
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-10 flex-1 min-h-0">
                        <div class="md:col-span-2 border-b md:border-b-0 md:border-r border-slate-100 overflow-y-auto">
                            <ul id="list" class="divide-y divide-slate-50"></ul>
                            <div id="empty" class="hidden px-5 py-8 text-center text-slate-400 text-sm">まだ登録がありません。</div>
                        </div>
                        <div class="md:col-span-8 overflow-hidden">
                            <div class="p-5 h-full min-h-0 flex flex-col">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="min-w-0">
                                        <h3 id="pvTitle" class="font-black text-slate-800 truncate">プレビュー</h3>
                                        <p id="pvMeta" class="text-[11px] text-slate-400 mt-1">未選択</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button id="btnPreviewModal" type="button" class="hidden text-xs font-bold text-slate-600 hover:bg-slate-100 px-3 py-2 rounded-xl transition">
                                            全体表示
                                        </button>
                                        <button id="btnLoadToEdit" type="button" class="hidden text-xs font-bold admin-link <?= !$isThemeHex ? 'text-' . $themeTailwind . '-700 hover:bg-' . $themeTailwind . '-50' : '' ?> px-3 py-2 rounded-xl transition">
                                            編集へ
                                        </button>
                                        <button id="btnDelete" type="button" class="hidden text-xs font-bold text-red-600 hover:bg-red-50 px-3 py-2 rounded-xl transition">
                                            削除
                                        </button>
                                    </div>
                                </div>
                                <div id="pv" class="rounded-xl border border-slate-200 bg-slate-50/40 p-4 flex-1 min-h-0 text-sm overflow-auto">
                                    <p class="text-slate-400">右の一覧から選択してください。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- プレビューモーダル -->
    <div id="previewModal" class="hidden fixed inset-0 z-[10000] bg-slate-900/60 backdrop-blur-sm">
        <div class="absolute inset-0" data-modal-close="1" aria-hidden="true"></div>
        <div class="relative mx-auto w-[98vw] max-w-[1600px] h-[92vh] mt-[4vh] bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 shrink-0">
                <div class="min-w-0">
                    <p id="modalTitle" class="font-black text-slate-800 truncate">プレビュー</p>
                    <p id="modalMeta" class="text-[11px] text-slate-400 mt-1 truncate">—</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="btnModalClose" type="button" class="text-xs font-bold text-slate-600 hover:bg-slate-100 px-3 py-2 rounded-xl transition">
                        閉じる
                    </button>
                </div>
            </div>
            <div class="p-5 flex-1 min-h-0 overflow-auto bg-slate-50/40">
                <div id="modalBody" class="rounded-xl border border-slate-200 bg-white p-4 h-full min-h-0 overflow-auto text-sm"></div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.5/dist/purify.min.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        const els = {
            id: document.getElementById('f_id'),
            title: document.getElementById('f_title'),
            ext: document.getElementById('f_ext'),
            content: document.getElementById('f_content'),
            metaHint: document.getElementById('metaHint'),
            btnSave: document.getElementById('btnSave'),
            btnNew: document.getElementById('btnNew'),
            btnToggleForm: document.getElementById('btnToggleForm'),
            formBody: document.getElementById('formBody'),
            formHeader: document.getElementById('formHeader'),
            btnReload: document.getElementById('btnReload'),
            list: document.getElementById('list'),
            empty: document.getElementById('empty'),
            pvTitle: document.getElementById('pvTitle'),
            pvMeta: document.getElementById('pvMeta'),
            pv: document.getElementById('pv'),
            btnPreviewModal: document.getElementById('btnPreviewModal'),
            btnDelete: document.getElementById('btnDelete'),
            btnLoadToEdit: document.getElementById('btnLoadToEdit'),
            previewModal: document.getElementById('previewModal'),
            modalTitle: document.getElementById('modalTitle'),
            modalMeta: document.getElementById('modalMeta'),
            modalBody: document.getElementById('modalBody'),
            btnModalClose: document.getElementById('btnModalClose'),
        };

        let state = {
            items: [],
            selectedId: null,
            selectedItem: null,
        };

        function setFormCollapsed(collapsed) {
            if (!els.formBody || !els.btnToggleForm) return;
            els.formBody.classList.toggle('hidden', !!collapsed);
            const icon = els.btnToggleForm.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-chevron-down', !!collapsed);
                icon.classList.toggle('fa-chevron-up', !collapsed);
            }
            els.btnToggleForm.lastChild.nodeValue = collapsed ? ' 展開' : ' 縮小';
        }

        function formatMeta(item) {
            const ext = item.ext ? '.' + item.ext : '';
            const size = item.size ? `${item.size} bytes` : '';
            const updated = item.updated_at || '';
            const created = item.created_at || '';
            return [ext, size, created ? `作成 ${created}` : '', updated ? `更新 ${updated}` : ''].filter(Boolean).join(' / ');
        }

        function escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s ?? '';
            return div.innerHTML;
        }

        function renderPreviewInto(containerEl, item) {
            if (!containerEl) return;
            if (!item) {
                containerEl.innerHTML = '<p class="text-slate-400">右の一覧から選択してください。</p>';
                return;
            }

            const content = item.content ?? '';
            const ext = (item.ext || '').toLowerCase();
            if (ext === 'md') {
                const html = marked.parse(content, { gfm: true, breaks: true });
                const safe = DOMPurify.sanitize(html, { USE_PROFILES: { html: true } });
                containerEl.innerHTML = `<div class="md-preview prose prose-slate max-w-none">${safe}</div>`;
            } else if (ext === 'html') {
                const srcdoc = String(content ?? '');
                containerEl.innerHTML = `
                    <iframe
                        class="w-full h-full rounded-lg bg-white"
                        sandbox=""
                        referrerpolicy="no-referrer"
                        title="HTML Preview"
                    ></iframe>
                `;
                const iframe = containerEl.querySelector('iframe');
                if (iframe) iframe.srcdoc = srcdoc;
            } else {
                containerEl.innerHTML = `<pre class="whitespace-pre-wrap mono text-slate-700">${escapeHtml(content)}</pre>`;
            }
        }

        function renderPreview(item) {
            if (!item) {
                renderPreviewInto(els.pv, null);
                els.pvTitle.textContent = 'プレビュー';
                els.pvMeta.textContent = '未選択';
                els.btnDelete.classList.add('hidden');
                els.btnLoadToEdit.classList.add('hidden');
                els.btnPreviewModal.classList.add('hidden');
                return;
            }

            els.pvTitle.textContent = item.title || '(無題)';
            els.pvMeta.textContent = formatMeta(item);
            els.btnDelete.classList.remove('hidden');
            els.btnLoadToEdit.classList.remove('hidden');
            els.btnPreviewModal.classList.remove('hidden');
            renderPreviewInto(els.pv, item);
        }

        function renderList() {
            els.list.innerHTML = '';
            if (!state.items || state.items.length === 0) {
                els.empty.classList.remove('hidden');
                return;
            }
            els.empty.classList.add('hidden');

            state.items.forEach(item => {
                const isActive = item.id === state.selectedId;
                const extRaw = (item.ext || 'txt').toLowerCase();
                const ext = (extRaw === 'md' || extRaw === 'html') ? extRaw : 'txt';
                const li = document.createElement('li');
                li.className = `px-4 py-3 cursor-pointer hover:bg-slate-50 transition flex items-start gap-3 ${isActive ? 'bg-slate-50' : ''}`;
                li.innerHTML = `
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 text-slate-700 shrink-0 border border-slate-200">
                        <span class="text-[10px] font-black uppercase tracking-wide">${escapeHtml(ext)}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-800 truncate">${escapeHtml(item.title || '(無題)')}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">${escapeHtml(formatMeta(item))}</div>
                    </div>
                `;
                li.addEventListener('click', () => selectItem(item.id));
                els.list.appendChild(li);
            });
        }

        async function reloadList(keepSelection = true) {
            const res = await App.post('/admin/api/text_files_list.php', {});
            if (res.status !== 'success') {
                App.toast('一覧取得に失敗しました: ' + (res.message || 'error'));
                return;
            }
            state.items = res.items || [];
            if (!keepSelection) state.selectedId = null;
            renderList();
        }

        async function selectItem(id) {
            state.selectedId = id;
            renderList();
            const res = await App.post('/admin/api/text_files_get.php', { id });
            if (res.status !== 'success') {
                App.toast('取得に失敗しました: ' + (res.message || 'error'));
                return;
            }
            state.selectedItem = res.item;
            renderPreview(state.selectedItem);
        }

        function resetForm() {
            els.id.value = '';
            els.title.value = '';
            els.ext.value = 'txt';
            els.content.value = '';
            els.metaHint.textContent = '最大 512KB';
        }

        function loadSelectedToForm() {
            const it = state.selectedItem;
            if (!it) return;
            els.id.value = it.id || '';
            els.title.value = it.title || '';
            const raw = (it.ext || 'txt').toLowerCase();
            els.ext.value = (raw === 'md' || raw === 'html') ? raw : 'txt';
            els.content.value = it.content || '';
            els.metaHint.textContent = (it.updated_at ? `更新 ${it.updated_at}` : '') + (it.created_at ? ` / 作成 ${it.created_at}` : '');
        }

        function toBase64Utf8(str) {
            const bytes = new TextEncoder().encode(str ?? '');
            let binary = '';
            const chunkSize = 0x8000;
            for (let i = 0; i < bytes.length; i += chunkSize) {
                binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunkSize));
            }
            return btoa(binary);
        }

        function openPreviewModal(item) {
            if (!els.previewModal || !els.modalBody) return;
            els.modalTitle.textContent = item?.title || '(無題)';
            els.modalMeta.textContent = item ? formatMeta(item) : '';
            renderPreviewInto(els.modalBody, item);
            els.previewModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePreviewModal() {
            if (!els.previewModal) return;
            els.previewModal.classList.add('hidden');
            document.body.style.overflow = '';
            if (els.modalBody) els.modalBody.innerHTML = '';
        }

        els.btnNew.addEventListener('click', () => {
            resetForm();
            setFormCollapsed(false);
            App.toast('新規入力に切り替えました');
        });
        els.btnToggleForm.addEventListener('click', (e) => {
            // ヘッダ全体クリックに伝播させない
            e.stopPropagation();
            const isCollapsed = els.formBody.classList.contains('hidden');
            setFormCollapsed(!isCollapsed);
        });
        els.btnNew.addEventListener('click', (e) => e.stopPropagation());
        els.btnSave.addEventListener('click', (e) => e.stopPropagation());
        if (els.formHeader) {
            els.formHeader.addEventListener('click', (e) => {
                // ボタン類やリンクを押した場合はトグルしない
                if (e.target && e.target.closest && e.target.closest('button,a,input,select,textarea,label')) return;
                const isCollapsed = els.formBody.classList.contains('hidden');
                setFormCollapsed(!isCollapsed);
            });
        }
        els.btnReload.addEventListener('click', () => reloadList(true));
        els.btnLoadToEdit.addEventListener('click', () => {
            loadSelectedToForm();
            setFormCollapsed(false);
            App.toast('選択中の内容を編集フォームに読み込みました');
        });
        els.btnPreviewModal.addEventListener('click', () => {
            if (!state.selectedItem) return;
            openPreviewModal(state.selectedItem);
        });
        els.btnModalClose.addEventListener('click', closePreviewModal);
        if (els.previewModal) {
            els.previewModal.addEventListener('click', (e) => {
                if (e.target && e.target.dataset && e.target.dataset.modalClose) {
                    closePreviewModal();
                }
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && els.previewModal && !els.previewModal.classList.contains('hidden')) {
                closePreviewModal();
            }
        });
        els.btnDelete.addEventListener('click', async () => {
            if (!state.selectedId) return;
            if (!confirm('削除してよろしいですか？')) return;
            const res = await App.post('/admin/api/text_files_delete.php', { id: state.selectedId });
            if (res.status === 'success') {
                App.toast('削除しました');
                state.selectedId = null;
                state.selectedItem = null;
                renderPreview(null);
                await reloadList(false);
            } else {
                App.toast('削除に失敗しました: ' + (res.message || 'error'));
            }
        });
        els.btnSave.addEventListener('click', async () => {
            const ext = els.ext.value || 'txt';
            const content = els.content.value ?? '';
            const payload = {
                id: els.id.value || '',
                title: (els.title.value || '').trim(),
                ext: ext,
                content: ext === 'html' ? '' : content
            };
            if (ext === 'html') {
                payload.content_b64 = toBase64Utf8(content);
            }
            const res = await App.post('/admin/api/text_files_save.php', payload);
            if (res.status === 'success') {
                App.toast('保存しました');
                await reloadList(true);
                if (res.id) {
                    await selectItem(res.id);
                }
            } else {
                App.toast('保存に失敗しました: ' + (res.message || 'error'));
            }
        });

        // 初期表示
        (async function init() {
            setFormCollapsed(true);
            await reloadList(false);
        })();
    </script>
</body>
</html>

