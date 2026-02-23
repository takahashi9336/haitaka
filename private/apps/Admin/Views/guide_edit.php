<?php
/**
 * ガイド編集 View（ブロックエディタ）
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$guide = $guide ?? null;
$guideError = $_SESSION['guide_error'] ?? null;
unset($_SESSION['guide_error']);

$isNew = empty($guide['id']);
$blocks = $guide['blocks'] ?? [];
$blocksJson = json_encode($blocks, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isNew ? '新規作成' : '編集' ?> - ガイド管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex ?? '#64748b') ?>; }
        .admin-btn-primary { background-color: var(--admin-theme); }
        .admin-btn-primary:hover { filter: brightness(1.08); }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .block-item { transition: box-shadow 0.2s; }
        .block-item:hover { box-shadow: 0 0 0 2px var(--admin-theme); }
        .block-item.dragging { opacity: 0.5; }
        .block-item:has(.block-image-area:focus-within) { box-shadow: 0 0 0 2px var(--admin-theme); background-color: rgba(100, 116, 139, 0.05); }
        .insert-slot { transition: background 0.15s; border-radius: 6px; }
        .insert-slot:hover { background: rgba(100, 116, 139, 0.05); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?? 'bg-slate-50' ?>">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/admin/guides.php" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg bg-slate-700">
                    <i class="fa-solid fa-book-open text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter"><?= $isNew ? '新規作成' : '編集' ?> - ガイド</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-3xl mx-auto w-full">
                <?php if ($guideError): ?>
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 font-bold"><?= htmlspecialchars($guideError) ?></div>
                <?php endif; ?>

                <form method="post" action="" class="space-y-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">guide_key <span class="text-red-500">*</span></label>
                            <input type="text" name="guide_key" value="<?= htmlspecialchars($guide['guide_key'] ?? '') ?>" required
                                   placeholder="例: meetgreet_import"
                                   class="w-full h-11 border border-slate-200 rounded-lg px-4 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--admin-theme);">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">タイトル <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="<?= htmlspecialchars($guide['title'] ?? '') ?>" required
                                   placeholder="例: ミーグリ予定のテキスト一括追加"
                                   class="w-full h-11 border border-slate-200 rounded-lg px-4 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--admin-theme);">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">app_key（任意）</label>
                            <input type="text" name="app_key" value="<?= htmlspecialchars($guide['app_key'] ?? '') ?>"
                                   placeholder="例: hinata"
                                   class="w-full h-11 border border-slate-200 rounded-lg px-4 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--admin-theme);">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="show_on_first_visit" value="1" <?= !empty($guide['show_on_first_visit']) ? 'checked' : '' ?> class="w-4 h-4 rounded" style="accent-color: var(--admin-theme);">
                                <span class="text-sm font-bold text-slate-700">初回表示する（ページ初訪問時に1回だけモーダル表示）</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-bold text-slate-700">ブロック</h2>
                            <p class="text-[10px] text-slate-400">各ブロックの間にも追加できます</p>
                        </div>
                        <input type="hidden" name="blocks_json" id="blocksJson" value="<?= htmlspecialchars($blocksJson) ?>">
                        <div id="blocksContainer" class="space-y-2"></div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="admin-btn-primary text-white h-12 px-8 rounded-lg font-black text-sm shadow-md transition">保存</button>
                        <a href="/admin/guides.php" class="h-12 px-8 rounded-lg font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center">キャンセル</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        const GuideEditor = {
            blocks: <?= $blocksJson ?>,
            guideId: <?= json_encode($guide['id'] ?? null) ?>,
            activeImageBlockIndex: null,

            init() {
                this.render();
                this.setupPasteListener();
            },

            setupPasteListener() {
                document.addEventListener('paste', (e) => {
                    const index = this.activeImageBlockIndex;
                    if (index === null || index < 0 || index >= this.blocks.length) return;
                    const block = this.blocks[index];
                    if (block.type !== 'image') return;
                    const items = e.clipboardData?.items;
                    if (!items) return;
                    for (let i = 0; i < items.length; i++) {
                        if (items[i].type.startsWith('image/')) {
                            e.preventDefault();
                            const blob = items[i].getAsFile();
                            if (blob) this.uploadBlob(blob, index);
                            break;
                        }
                    }
                });
            },

            render() {
                const container = document.getElementById('blocksContainer');
                container.innerHTML = '';
                this.blocks.forEach((b, i) => {
                    container.appendChild(this.createInsertSlot(i));
                    container.appendChild(this.createBlockEl(b, i));
                });
                container.appendChild(this.createInsertSlot(this.blocks.length));
                this.syncHidden();
            },

            createInsertSlot(insertIndex) {
                const div = document.createElement('div');
                div.className = 'insert-slot flex items-center gap-2 py-2';
                div.innerHTML = `
                    <span class="text-[10px] text-slate-300 font-bold">＋</span>
                    <button type="button" onclick="GuideEditor.insertBlock(${insertIndex}, 'text')" class="text-[10px] font-bold px-2 py-1 rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                        <i class="fa-solid fa-font mr-0.5"></i>テキスト
                    </button>
                    <button type="button" onclick="GuideEditor.insertBlock(${insertIndex}, 'image')" class="text-[10px] font-bold px-2 py-1 rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                        <i class="fa-solid fa-image mr-0.5"></i>画像
                    </button>
                `;
                return div;
            },

            createBlockEl(block, index) {
                const div = document.createElement('div');
                div.className = 'block-item border border-slate-200 rounded-lg p-4 bg-slate-50/50';
                div.dataset.index = index;
                div.draggable = true;

                let inner = '';
                if (block.type === 'text') {
                    inner = `
                        <div class="flex items-start gap-2">
                            <span class="cursor-move text-slate-300 hover:text-slate-500 mt-2" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-vertical"></i></span>
                            <div class="flex-1">
                                <label class="text-[10px] font-bold text-slate-400">テキスト</label>
                                <textarea class="block-editor mt-1 w-full h-24 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 resize-y" data-field="content" placeholder="テキストを入力...">${this._esc(block.content || '')}</textarea>
                            </div>
                            <button type="button" onclick="GuideEditor.removeBlock(${index})" class="text-slate-300 hover:text-red-500 p-2"><i class="fa-solid fa-trash-can text-xs"></i></button>
                        </div>
                    `;
                } else {
                    inner = `
                        <div class="flex items-start gap-2 block-image-area" data-index="${index}">
                            <span class="cursor-move text-slate-300 hover:text-slate-500 mt-2" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-vertical"></i></span>
                            <div class="flex-1 space-y-2">
                                <label class="text-[10px] font-bold text-slate-400">画像</label>
                                <div class="flex gap-2 items-start">
                                    <input type="text" class="block-editor flex-1 h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" data-field="src" placeholder="URL または Ctrl+V で貼り付け" value="${this._esc(block.src || '')}">
                                    <input type="file" accept="image/*" class="block-image-upload hidden" data-index="${index}">
                                    <button type="button" onclick="document.querySelector('.block-image-upload[data-index=\\'${index}\\']').click()" class="text-xs font-bold px-3 py-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 shrink-0">ファイル選択</button>
                                </div>
                                <p class="text-[9px] text-slate-400">このブロックをクリックしてから Ctrl+V（Mac: Cmd+V）で貼り付け</p>
                                <input type="text" class="block-editor w-full h-9 border border-slate-200 rounded-lg px-3 text-xs outline-none focus:ring-2" data-field="alt" placeholder="alt テキスト（任意）" value="${this._esc(block.alt || '')}">
                                ${block.src ? `<div class="mt-2"><img src="${this._esc(block.src)}" alt="" class="max-h-32 rounded border border-slate-200"></div>` : ''}
                            </div>
                            <button type="button" onclick="GuideEditor.removeBlock(${index})" class="text-slate-300 hover:text-red-500 p-2"><i class="fa-solid fa-trash-can text-xs"></i></button>
                        </div>
                    `;
                }
                div.innerHTML = inner;

                div.addEventListener('input', () => this.syncFromEl(parseInt(div.dataset.index)));
                div.addEventListener('change', () => this.syncFromEl(parseInt(div.dataset.index)));
                const uploadInput = div.querySelector('.block-image-upload');
                if (uploadInput) {
                    uploadInput.addEventListener('change', (e) => this.handleImageUpload(e, parseInt(div.dataset.index)));
                }
                if (block.type === 'image') {
                    div.addEventListener('focusin', () => { this.activeImageBlockIndex = index; });
                    div.addEventListener('focusout', (e) => {
                        setTimeout(() => {
                            const active = document.activeElement;
                            if (!active || !active.closest?.('.block-item')?.querySelector('.block-image-area')) {
                                this.activeImageBlockIndex = null;
                            }
                        }, 0);
                    });
                }

                div.addEventListener('dragstart', (e) => {
                    div.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', index);
                    e.dataTransfer.effectAllowed = 'move';
                });
                div.addEventListener('dragend', () => div.classList.remove('dragging'));
                div.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                });
                div.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const from = parseInt(e.dataTransfer.getData('text/plain'));
                    const to = parseInt(div.dataset.index);
                    if (from !== to) {
                        const [moved] = this.blocks.splice(from, 1);
                        this.blocks.splice(to, 0, moved);
                        this.render();
                    }
                });

                return div;
            },

            insertBlock(index, type) {
                const newBlock = type === 'text'
                    ? { type: 'text', content: '' }
                    : { type: 'image', src: '', alt: '' };
                this.blocks.splice(index, 0, newBlock);
                this.render();
            },

            removeBlock(index) {
                this.blocks.splice(index, 1);
                this.render();
            },

            syncFromEl(index) {
                const container = document.getElementById('blocksContainer');
                const blockEl = container.querySelector(`[data-index="${index}"]`);
                if (!blockEl) return;
                const block = this.blocks[index];
                blockEl.querySelectorAll('.block-editor').forEach(input => {
                    const field = input.dataset.field;
                    block[field] = input.value;
                });
                this.syncHidden();
            },

            syncHidden() {
                document.getElementById('blocksJson').value = JSON.stringify(this.blocks);
            },

            handleImageUpload(e, index) {
                const file = e.target.files?.[0];
                if (!file) return;
                this.uploadBlob(file, index);
                e.target.value = '';
            },

            uploadBlob(blob, index) {
                const ext = blob.type === 'image/png' ? 'png' : blob.type === 'image/gif' ? 'gif' : blob.type === 'image/webp' ? 'webp' : 'jpg';
                const fd = new FormData();
                fd.append('file', blob, 'paste.' + ext);
                fd.append('guide_id', this.guideId || '0');
                fetch('/admin/api/guide_image_upload.php', {
                    method: 'POST',
                    body: fd,
                }).then(r => r.json()).then(res => {
                    if (res.status === 'success' && res.url) {
                        this.blocks[index].src = res.url;
                        this.render();
                        if (typeof App !== 'undefined' && App.toast) App.toast('画像をアップロードしました');
                    } else {
                        alert(res.message || 'アップロードに失敗しました');
                    }
                }).catch(() => alert('アップロードに失敗しました'));
            },

            _esc(s) {
                const d = document.createElement('div');
                d.textContent = s || '';
                return d.innerHTML;
            },
        };

        GuideEditor.init();

        document.querySelector('form').addEventListener('submit', () => {
            document.getElementById('blocksContainer').querySelectorAll('.block-item').forEach((el, i) => {
                GuideEditor.syncFromEl(i);
            });
            GuideEditor.syncHidden();
        });
    </script>
</body>
</html>
