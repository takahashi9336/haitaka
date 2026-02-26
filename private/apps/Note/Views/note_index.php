<?php
$appKey = 'note';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メモ管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --note-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .note-theme-btn { background-color: var(--note-theme); }
        .note-theme-btn:hover { filter: brightness(1.08); }
        .note-theme-btn.saved { background-color: #22c55e !important; }
        .note-theme-link, .note-theme-text { color: var(--note-theme); }
        .note-theme-focus:focus { --tw-ring-color: var(--note-theme); }
        .focus\:border-note-theme:focus { border-color: var(--note-theme); }
        .hover-note-theme:hover { color: var(--note-theme); }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }

        /* ミーグリネタ帳風：メモ単位で高さ可変のカラムレイアウト */
        .notes-grid {
            column-count: 1;
            column-gap: 0.75rem;
        }
        @media (min-width: 640px) {
            .notes-grid { column-count: 2; }
        }
        @media (min-width: 1024px) {
            .notes-grid { column-count: 3; }
        }
        @media (min-width: 1280px) {
            .notes-grid { column-count: 4; }
        }

        .note-card {
            break-inside: avoid;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .note-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }

        .note-content {
            white-space: pre-line;
            word-wrap: break-word;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        /* Google Keep風: カードアクションはホバー時のみ表示 */
        .note-card .note-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .note-card:hover .note-actions,
        .note-card:focus-within .note-actions {
            opacity: 1;
        }

        /* インラインカラーピッカー（Google Keep風） */
        .inline-color-picker {
            display: none;
        }

        .inline-color-picker.active {
            display: flex;
        }

        .color-swatch {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.15s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .color-swatch:hover {
            transform: scale(1.1);
            border-color: rgba(0,0,0,0.15);
        }

        .color-swatch.selected::after {
            content: '✓';
            color: rgba(0,0,0,0.6);
            font-weight: bold;
            font-size: 18px;
        }

        .color-swatch[data-color="#ffffff"].selected::after {
            color: rgba(0,0,0,0.4);
        }

        /* モーダルアニメーション（日向坂メンバー風・0.4秒版） */
        #detailModal {
            opacity: 0;
            pointer-events: none;
        }

        #detailModal.active {
            opacity: 1;
            pointer-events: auto;
        }

        @keyframes backdropFadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes backdropFadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
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

        #detailModal.modal-opening {
            animation: backdropFadeIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        #detailModal.modal-opening #detailModalContent {
            animation: modalExpandFromPoint 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        #detailModal.modal-closing {
            animation: backdropFadeOut 0.25s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards;
        }

        #detailModal.modal-closing #detailModalContent {
            animation: modalShrinkToPoint 0.25s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-lightbulb text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">メモ</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider">すべてのメモを管理</span>
                    <a href="/" class="text-xs font-black note-theme-link hover:opacity-80 transition <?= !$isThemeHex ? "text-{$themeTailwind}-500" : '' ?>">
                        <i class="fa-solid fa-arrow-left mr-1"></i> ダッシュボード
                    </a>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-7xl mx-auto">

                <!-- クイックメモ追加 -->
                <div class="mb-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-4">
                            <input type="text" id="quickMemoTitle" placeholder="タイトル（任意）"
                                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--note-theme)] focus:border-transparent text-sm font-medium mb-2">
                            <textarea 
                                id="quickMemoInput" 
                                placeholder="メモを入力..."
                                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--note-theme)] focus:border-transparent resize-none overflow-hidden transition-all text-sm min-h-[2.5rem]"
                                rows="1"
                            ></textarea>
                            <div id="quickMemoActions" class="mt-3 flex items-center justify-end opacity-0 transition-opacity duration-200">
                                <button id="quickMemoSaveBtn" onclick="QuickMemo.save(event)" class="px-4 py-1.5 note-theme-btn text-white text-xs font-bold rounded-lg transition shadow-sm">
                                    <i class="fa-solid fa-plus mr-1"></i> 追加
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- タブ: アクティブ / アーカイブ -->
                <div class="mb-4 flex gap-2 border-b border-slate-200">
                    <button id="tabActive" class="tab-btn px-4 py-2 text-sm font-bold border-b-2 border-[var(--note-theme)] text-[var(--note-theme)]" data-tab="active">アクティブ</button>
                    <button id="tabArchived" class="tab-btn px-4 py-2 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-600" data-tab="archived">アーカイブ</button>
                </div>

                <div id="notesEmptyState" class="text-center py-16 hidden">
                    <div class="w-20 h-20 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-lightbulb text-4xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">メモがありません</h3>
                </div>
                <div id="notesGrid" class="notes-grid"></div>
            </div>
        </div>
    </main>

    <!-- 詳細モーダル（Google Keep風） -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" onclick="NoteManager.closeDetailModal()">
        <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl" 
             id="detailModalContent" 
             onclick="event.stopPropagation()">
            <div class="p-6">
                <input type="hidden" id="detailNoteId">
                
                <!-- タイトル -->
                <input type="text" id="detailTitle" placeholder="タイトル" 
                       class="w-full text-xl font-bold text-slate-800 mb-4 px-3 py-2 border-0 border-b-2 border-transparent focus:border-[var(--note-theme)] focus:outline-none transition">
                
                <!-- 内容 -->
                <textarea id="detailContent" placeholder="メモを入力..." 
                          class="w-full text-slate-700 px-3 py-2 border-0 focus:outline-none resize-none overflow-hidden text-sm min-h-[2.5rem]" 
                          rows="1"></textarea>
                
                <!-- メタ情報 -->
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <p class="text-xs text-slate-400" id="detailTimestamp"></p>
                </div>
                
                <!-- アクション -->
                <div class="mt-6 pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <button onclick="NoteManager.togglePinFromDetail()" 
                                    id="detailPinBtn"
                                    class="p-2 text-slate-500 hover-note-theme transition rounded-lg hover:bg-slate-50"
                                    title="ピン留め">
                                <i class="fa-solid fa-thumbtack"></i>
                            </button>
                            
                            <button onclick="NoteManager.toggleColorPicker(event)" 
                                    id="colorPickerToggleBtn"
                                    class="p-2 text-slate-500 hover:text-blue-500 transition rounded-lg hover:bg-slate-50"
                                    title="色を変更">
                                <i class="fa-solid fa-palette"></i>
                            </button>
                            
                            <button id="detailArchiveBtn" onclick="NoteManager.archiveNoteFromDetail()" 
                                    class="p-2 text-slate-500 hover:text-amber-600 transition rounded-lg hover:bg-slate-50 hidden"
                                    title="アーカイブ">
                                <i class="fa-solid fa-box-archive"></i>
                            </button>
                            <button id="detailRestoreBtn" onclick="NoteManager.restoreNoteFromDetail()" 
                                    class="p-2 text-slate-500 hover:text-green-600 transition rounded-lg hover:bg-slate-50 hidden"
                                    title="復元">
                                <i class="fa-solid fa-box-open"></i>
                            </button>
                            <button onclick="NoteManager.deleteNoteFromDetail()" 
                                    class="p-2 text-slate-500 hover:text-red-500 transition rounded-lg hover:bg-slate-50"
                                    title="削除">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        
                        <button onclick="NoteManager.saveDetail()" 
                                class="px-6 py-2 note-theme-btn text-white text-sm font-bold rounded-lg transition shadow-sm">
                            完了
                        </button>
                    </div>
                    
                    <!-- インラインカラーピッカー -->
                    <div id="inlineColorPicker" class="inline-color-picker flex-wrap gap-3 p-4 bg-slate-50 rounded-lg" onclick="event.stopPropagation()">
                        <div class="color-swatch" data-color="#ffffff" style="background-color: #ffffff; border: 2px solid #e2e8f0;" onclick="NoteManager.changeColorInline('#ffffff')"></div>
                        <div class="color-swatch" data-color="#fef3c7" style="background-color: #fef3c7;" onclick="NoteManager.changeColorInline('#fef3c7')"></div>
                        <div class="color-swatch" data-color="#fecaca" style="background-color: #fecaca;" onclick="NoteManager.changeColorInline('#fecaca')"></div>
                        <div class="color-swatch" data-color="#bfdbfe" style="background-color: #bfdbfe;" onclick="NoteManager.changeColorInline('#bfdbfe')"></div>
                        <div class="color-swatch" data-color="#bbf7d0" style="background-color: #bbf7d0;" onclick="NoteManager.changeColorInline('#bbf7d0')"></div>
                        <div class="color-swatch" data-color="#ddd6fe" style="background-color: #ddd6fe;" onclick="NoteManager.changeColorInline('#ddd6fe')"></div>
                        <div class="color-swatch" data-color="#fbcfe8" style="background-color: #fbcfe8;" onclick="NoteManager.changeColorInline('#fbcfe8')"></div>
                        <div class="color-swatch" data-color="#fed7aa" style="background-color: #fed7aa;" onclick="NoteManager.changeColorInline('#fed7aa')"></div>
                        <div class="color-swatch" data-color="#e0e7ff" style="background-color: #e0e7ff;" onclick="NoteManager.changeColorInline('#e0e7ff')"></div>
                        <div class="color-swatch" data-color="#f3f4f6" style="background-color: #f3f4f6;" onclick="NoteManager.changeColorInline('#f3f4f6')"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        // テキストエリアの高さを内容に合わせて自動伸長
        function autoResizeTextarea(ta) {
            ta.style.height = 'auto';
            ta.style.height = Math.max(40, Math.min(ta.scrollHeight, 400)) + 'px';
        }

        function escapeHtml(s) {
            if (s == null) return '';
            const div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }

        function formatNoteDate(s) {
            if (!s) return '';
            return String(s).replace(/-/g, '/').substring(0, 16);
        }

        function normalizeNoteContent(raw) {
            if (!raw) return '';
            return String(raw).replace(/\r\n|\r/g, '\n').replace(/\n{2,}/g, '\n').trim();
        }

        const NoteManager = {
            currentNoteId: null,
            currentViewMode: 'active',
            viewMode: 'active',
            notes: <?= json_encode($notes ?? []) ?>,
            archivedNotes: <?= json_encode($archivedNotes ?? []) ?>,

            getNote(noteId) {
                return this.notes.find(n => n.id == noteId) || this.archivedNotes.find(n => n.id == noteId);
            },

            renderNoteCard(note, viewMode) {
                const raw = normalizeNoteContent(note.content);
                const contentEscaped = escapeHtml(raw);
                const titleEscaped = escapeHtml(note.title || '');
                const dateStr = formatNoteDate(note.created_at);
                const bgColor = escapeHtml(note.bg_color || '#ffffff');
                const pinHtml = note.is_pinned ? '<div class="flex justify-end mb-1.5"><i class="fa-solid fa-thumbtack note-theme-text text-xs"></i></div>' : '';
                const titleHtml = note.title ? '<h3 class="font-bold text-slate-800 mb-2 text-base">' + titleEscaped + '</h3>' : '';

                let actionsHtml = '';
                if (viewMode === 'active') {
                    actionsHtml = '<button data-action="pin" data-id="' + note.id + '" class="p-2 text-slate-500 hover-note-theme transition rounded-lg hover:bg-slate-50" title="ピン留め"><i class="fa-solid fa-thumbtack text-sm"></i></button>' +
                        '<button data-action="color" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-blue-500 transition rounded-lg hover:bg-slate-50" title="色を変更"><i class="fa-solid fa-palette text-sm"></i></button>' +
                        '<button data-action="edit" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-green-500 transition rounded-lg hover:bg-slate-50" title="編集"><i class="fa-solid fa-pen text-sm"></i></button>' +
                        '<button data-action="archive" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-amber-600 transition rounded-lg hover:bg-slate-50" title="アーカイブ"><i class="fa-solid fa-box-archive text-sm"></i></button>' +
                        '<button data-action="delete" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-red-500 transition rounded-lg hover:bg-slate-50" title="削除"><i class="fa-solid fa-trash text-sm"></i></button>';
                } else {
                    actionsHtml = '<button data-action="restore" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-green-600 transition rounded-lg hover:bg-slate-50" title="復元"><i class="fa-solid fa-box-open text-sm"></i></button>' +
                        '<button data-action="edit" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-green-500 transition rounded-lg hover:bg-slate-50" title="編集"><i class="fa-solid fa-pen text-sm"></i></button>' +
                        '<button data-action="delete" data-id="' + note.id + '" class="p-2 text-slate-500 hover:text-red-500 transition rounded-lg hover:bg-slate-50" title="削除"><i class="fa-solid fa-trash text-sm"></i></button>';
                }

                return '<div class="note-card bg-white rounded-lg border border-slate-100 shadow-sm overflow-hidden cursor-pointer" style="background-color:' + bgColor + ';" data-note-id="' + note.id + '">' +
                    '<div class="p-4">' + pinHtml + titleHtml +
                    '<div class="note-content text-slate-700 text-sm mb-3">' + contentEscaped + '</div>' +
                    '<div class="text-xs text-slate-400 mb-3">' + dateStr + '</div>' +
                    '<div class="note-actions flex items-center justify-between pt-2.5 border-t border-slate-200">' + actionsHtml + '</div>' +
                    '</div></div>';
            },

            renderNotesGrid() {
                const grid = document.getElementById('notesGrid');
                const emptyState = document.getElementById('notesEmptyState');
                const list = this.viewMode === 'active' ? this.notes : this.archivedNotes;

                if (list.length === 0) {
                    grid.innerHTML = '';
                    emptyState.classList.remove('hidden');
                    return;
                }
                emptyState.classList.add('hidden');
                grid.innerHTML = list.map(n => this.renderNoteCard(n, this.viewMode)).join('');

                grid.querySelectorAll('.note-card').forEach(card => {
                    card.addEventListener('click', (e) => {
                        if (e.target.closest('[data-action]')) return;
                        const id = parseInt(card.dataset.noteId, 10);
                        NoteManager.showDetailModal(id, e);
                    });
                });
                grid.querySelectorAll('[data-action]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const action = btn.dataset.action;
                        const id = parseInt(btn.dataset.id, 10);
                        if (action === 'pin') NoteManager.togglePin(id);
                        else if (action === 'color') NoteManager.showColorPickerCard(id);
                        else if (action === 'edit') NoteManager.showDetailModal(id, e);
                        else if (action === 'archive') NoteManager.archiveNote(id);
                        else if (action === 'restore') NoteManager.restoreNote(id);
                        else if (action === 'delete') NoteManager.deleteNote(id);
                    });
                });
            },

            showColorPickerCard(noteId) {
                this.showDetailModal(noteId, null);
                setTimeout(() => NoteManager.toggleColorPicker({ stopPropagation: () => {} }), 100);
            },

            switchTab(tab) {
                this.viewMode = tab;
                document.querySelectorAll('.tab-btn').forEach(b => {
                    const isActive = b.dataset.tab === tab;
                    b.classList.toggle('border-[var(--note-theme)]', isActive);
                    b.classList.toggle('text-[var(--note-theme)]', isActive);
                    b.classList.toggle('border-transparent', !isActive);
                    b.classList.toggle('text-slate-400', !isActive);
                });
                this.renderNotesGrid();
            },

            updateNoteInList(note) {
                const idx = this.notes.findIndex(n => n.id == note.id);
                if (idx >= 0) this.notes[idx] = { ...this.notes[idx], ...note };
                const aidx = this.archivedNotes.findIndex(n => n.id == note.id);
                if (aidx >= 0) this.archivedNotes[aidx] = { ...this.archivedNotes[aidx], ...note };
            },

            removeNoteFromList(noteId) {
                this.notes = this.notes.filter(n => n.id != noteId);
                this.archivedNotes = this.archivedNotes.filter(n => n.id != noteId);
            },

            async togglePin(noteId) {
                try {
                    const result = await App.post('/note/api/toggle_pin.php', { id: noteId });
                    if (result.status === 'success') {
                        const note = this.getNote(noteId);
                        if (note) this.updateNoteInList({ ...note, is_pinned: note.is_pinned ? 0 : 1 });
                        this.renderNotesGrid();
                    } else alert('エラー: ' + (result.message || 'ピン留めに失敗しました'));
                } catch (error) {
                    console.error('Pin toggle error:', error);
                    alert('エラーが発生しました');
                }
            },

            toggleColorPicker(event) {
                event.stopPropagation();
                const picker = document.getElementById('inlineColorPicker');
                if (picker.classList.contains('active')) picker.classList.remove('active');
                else { picker.classList.add('active'); this.updateColorPickerSelection(); }
            },

            closeColorPicker() {
                document.getElementById('inlineColorPicker').classList.remove('active');
            },

            updateColorPickerSelection() {
                const currentColor = document.getElementById('detailModalContent').style.backgroundColor || '#ffffff';
                document.querySelectorAll('.color-swatch').forEach(swatch => {
                    swatch.classList.toggle('selected', this.compareColors(currentColor, swatch.dataset.color));
                });
            },

            compareColors(c1, c2) {
                const n = (c) => c.startsWith('rgb') ? '#' + c.match(/\d+/g).map(v => parseInt(v).toString(16).padStart(2, '0')).join('') : c.toLowerCase();
                return n(c1) === n(c2);
            },

            async changeColorInline(color) {
                if (!this.currentNoteId) return;
                const modalContent = document.getElementById('detailModalContent');
                modalContent.style.backgroundColor = color;
                this.updateColorPickerSelection();
                try {
                    const result = await App.post('/note/api/update.php', { id: this.currentNoteId, bg_color: color });
                    if (result.status === 'success') {
                        const note = this.getNote(this.currentNoteId);
                        if (note) this.updateNoteInList({ ...note, bg_color: color });
                        const card = document.querySelector('.note-card[data-note-id="' + this.currentNoteId + '"]');
                        if (card) card.style.backgroundColor = color;
                    } else alert('色の変更に失敗しました');
                } catch (e) { console.error(e); }
            },

            async deleteNote(noteId) {
                if (!confirm('このメモを削除してもよろしいですか?')) return;
                try {
                    const result = await App.post('/note/api/delete.php', { id: noteId });
                    if (result.status === 'success') {
                        this.removeNoteFromList(noteId);
                        this.renderNotesGrid();
                        if (this.currentNoteId == noteId) this.closeDetailModal();
                    } else alert('エラー: ' + (result.message || '削除に失敗しました'));
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('エラーが発生しました');
                }
            },

            async archiveNote(noteId) {
                try {
                    const result = await App.post('/note/api/update.php', { id: noteId, status: 'archived' });
                    if (result.status === 'success') {
                        const note = this.getNote(noteId);
                        if (note) {
                            this.notes = this.notes.filter(n => n.id != noteId);
                            this.archivedNotes = [{ ...note, status: 'archived' }, ...this.archivedNotes];
                        }
                        this.renderNotesGrid();
                        if (this.currentNoteId == noteId) this.closeDetailModal();
                    } else alert('エラー: ' + (result.message || 'アーカイブに失敗しました'));
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            async restoreNote(noteId) {
                try {
                    const result = await App.post('/note/api/update.php', { id: noteId, status: 'active' });
                    if (result.status === 'success') {
                        const note = this.getNote(noteId);
                        if (note) {
                            this.archivedNotes = this.archivedNotes.filter(n => n.id != noteId);
                            this.notes = [{ ...note, status: 'active' }, ...this.notes];
                        }
                        this.renderNotesGrid();
                        if (this.currentNoteId == noteId) this.closeDetailModal();
                    } else alert('エラー: ' + (result.message || '復元に失敗しました'));
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            showDetailModal(noteId, sourceEvent) {
                const note = this.getNote(noteId);
                if (!note) return;
                this.currentNoteId = noteId;
                this.currentViewMode = this.viewMode;

                document.getElementById('detailNoteId').value = noteId;
                document.getElementById('detailTitle').value = note.title || '';
                const detailContent = document.getElementById('detailContent');
                detailContent.value = note.content || '';
                autoResizeTextarea(detailContent);
                document.getElementById('detailTimestamp').textContent = '作成: ' + note.created_at + (note.updated_at !== note.created_at ? ' / 更新: ' + note.updated_at : '');

                const pinBtn = document.getElementById('detailPinBtn');
                pinBtn.classList.toggle('note-theme-text', !!note.is_pinned);
                document.getElementById('detailArchiveBtn').classList.toggle('hidden', this.viewMode !== 'active');
                document.getElementById('detailRestoreBtn').classList.toggle('hidden', this.viewMode !== 'archived');

                const modal = document.getElementById('detailModal');
                const modalContent = document.getElementById('detailModalContent');
                modalContent.style.backgroundColor = note.bg_color || '#ffffff';

                if (sourceEvent && sourceEvent.currentTarget) {
                    const rect = sourceEvent.currentTarget.getBoundingClientRect();
                    const clickX = rect.left + rect.width / 2, clickY = rect.top + rect.height / 2;
                    modalContent.style.setProperty('--modal-translate-x', (clickX - window.innerWidth / 2) + 'px');
                    modalContent.style.setProperty('--modal-translate-y', (clickY - window.innerHeight / 2) + 'px');
                }
                this.closeColorPicker();
                modal.classList.remove('hidden', 'modal-closing');
                modal.classList.add('active', 'modal-opening');
                modal.onclick = (e) => {
                    if (e.target === modal) this.closeDetailModal();
                    else if (!e.target.closest('#inlineColorPicker') && !e.target.closest('#colorPickerToggleBtn')) this.closeColorPicker();
                };
                setTimeout(() => modal.classList.remove('modal-opening'), 400);
            },

            closeDetailModal() {
                const modal = document.getElementById('detailModal');
                this.closeColorPicker();
                modal.classList.remove('modal-opening');
                modal.classList.add('modal-closing');
                setTimeout(() => {
                    modal.classList.remove('active', 'modal-closing');
                    modal.classList.add('hidden');
                    this.currentNoteId = null;
                }, 250);
            },

            async saveDetail() {
                const noteId = document.getElementById('detailNoteId').value;
                const title = document.getElementById('detailTitle').value.trim();
                const content = document.getElementById('detailContent').value.trim();
                if (!content) { alert('内容を入力してください'); return; }
                try {
                    const result = await App.post('/note/api/update.php', { id: noteId, title, content });
                    if (result.status === 'success') {
                        const note = this.getNote(parseInt(noteId, 10));
                        if (note) this.updateNoteInList({ ...note, title, content });
                        this.renderNotesGrid();
                        this.closeDetailModal();
                    } else alert('エラー: ' + (result.message || '更新に失敗しました'));
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            async togglePinFromDetail() {
                await this.togglePin(this.currentNoteId);
                const note = this.getNote(this.currentNoteId);
                if (note) {
                    document.getElementById('detailPinBtn').classList.toggle('note-theme-text', !!note.is_pinned);
                }
            },

            async archiveNoteFromDetail() {
                await this.archiveNote(this.currentNoteId);
            },

            async restoreNoteFromDetail() {
                await this.restoreNote(this.currentNoteId);
            },

            async deleteNoteFromDetail() {
                await this.deleteNote(this.currentNoteId);
            }
        };

        const QuickMemo = {
            input: null,
            titleInput: null,
            actions: null,

            init() {
                this.input = document.getElementById('quickMemoInput');
                this.titleInput = document.getElementById('quickMemoTitle');
                this.actions = document.getElementById('quickMemoActions');
                if (this.input) {
                    this.input.addEventListener('focus', () => { this.actions.classList.remove('opacity-0'); this.actions.classList.add('opacity-100'); });
                    this.input.addEventListener('input', () => autoResizeTextarea(this.input));
                    this.input.addEventListener('blur', () => {
                        setTimeout(() => {
                            if (!this.input.value.trim() && (!this.titleInput || !this.titleInput.value.trim()))
                                this.actions.classList.remove('opacity-100'), this.actions.classList.add('opacity-0');
                        }, 200);
                    });
                }
            },

            clearInput() {
                this.input.value = '';
                if (this.titleInput) this.titleInput.value = '';
                this.input.style.height = 'auto';
                this.input.blur();
                this.actions.classList.remove('opacity-100');
                this.actions.classList.add('opacity-0');
            },

            async save(event) {
                const title = this.titleInput ? this.titleInput.value.trim() : '';
                const content = this.input.value.trim();
                if (!content) { alert('メモの内容を入力してください'); return; }
                const btn = event?.target || document.getElementById('quickMemoSaveBtn');
                try {
                    const result = await App.post('/note/api/save.php', { title, content });
                    if (result && result.status === 'success') {
                        this.clearInput();
                        if (btn) {
                            const orig = btn.innerHTML;
                            btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 保存しました';
                            btn.classList.add('saved');
                            setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('saved'); }, 2000);
                        }
                        if (result.note && NoteManager.viewMode === 'active') {
                            NoteManager.notes = [result.note, ...NoteManager.notes];
                            NoteManager.renderNotesGrid();
                        }
                    } else alert('エラー: ' + (result?.message || '保存に失敗しました'));
                } catch (error) {
                    console.error('Save error:', error);
                    alert('保存中にエラーが発生しました');
                }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            QuickMemo.init();
            document.getElementById('detailContent')?.addEventListener('input', () => autoResizeTextarea(document.getElementById('detailContent')));
            document.getElementById('tabActive').addEventListener('click', () => NoteManager.switchTab('active'));
            document.getElementById('tabArchived').addEventListener('click', () => NoteManager.switchTab('archived'));
            NoteManager.renderNotesGrid();
        });
    </script>
</body>
</html>
