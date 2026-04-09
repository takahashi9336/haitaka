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

        /* リスト種別別デザイン（提案レイアウト.html参考） */
        .custom-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .custom-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .timeline-container { position: relative; }
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 10px;
            bottom: 10px;
            width: 2px;
            background: #cbd5e1; /* slate-300 */
            border-radius: 2px;
        }
        .solved-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
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

                <!-- 主タブ: メモ / リスト -->
                <div class="mb-4 flex gap-2 border-b border-slate-200">
                    <button id="mainTabMemo" class="main-tab-btn px-4 py-2 text-sm font-black border-b-2 border-[var(--note-theme)] text-[var(--note-theme)]" data-main-tab="memo">
                        <i class="fa-solid fa-lightbulb mr-1"></i> 汎用メモ
                    </button>
                    <button id="mainTabList" class="main-tab-btn px-4 py-2 text-sm font-black border-b-2 border-transparent text-slate-400 hover:text-slate-600" data-main-tab="list">
                        <i class="fa-solid fa-list-check mr-1"></i> リスト
                    </button>
                </div>

                <!-- クイックメモ追加 -->
                <div class="mb-6" id="quickMemoSection">
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

                <!-- リスト管理 -->
                <div id="listSection" class="hidden">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div id="listKindChips" class="flex gap-2 overflow-x-auto pb-2"></div>
                        <button id="btnNewListEntry" class="shrink-0 px-4 py-2 note-theme-btn text-white text-xs font-black rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-plus mr-1"></i> 追加
                        </button>
                    </div>

                    <div id="listEmptyState" class="text-center py-16 hidden">
                        <div class="w-20 h-20 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-list text-4xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">リストがありません</h3>
                    </div>
                    <div id="listGrid" class="space-y-2"></div>
                </div>
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

    <!-- リスト詳細モーダル -->
    <div id="listDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-4xl w-full max-h-[92vh] overflow-y-auto shadow-2xl" id="listDetailModalContent">
            <div class="p-6">
                <input type="hidden" id="listDetailId">
                <input type="hidden" id="listDetailKind">

                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-black text-slate-800">リスト編集</h2>
                        <p class="text-xs text-slate-400" id="listDetailMeta"></p>
                    </div>
                    <button type="button" id="btnCloseListModal" class="p-2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div id="listDetailFields" class="space-y-3"></div>

                <div class="mt-6 pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button id="listColorPickerToggleBtn" type="button"
                                    class="p-2 text-slate-500 hover:text-blue-500 transition rounded-lg hover:bg-slate-50"
                                    title="色を変更">
                                <i class="fa-solid fa-palette"></i>
                            </button>
                            <button id="listPinBtn" type="button"
                                    class="p-2 text-slate-500 hover-note-theme transition rounded-lg hover:bg-slate-50"
                                    title="ピン留め">
                                <i class="fa-solid fa-thumbtack"></i>
                            </button>
                            <button id="listArchiveBtn" type="button"
                                    class="p-2 text-slate-500 hover:text-amber-600 transition rounded-lg hover:bg-slate-50"
                                    title="アーカイブ">
                                <i class="fa-solid fa-box-archive"></i>
                            </button>
                            <button id="listRestoreBtn" type="button"
                                    class="p-2 text-slate-500 hover:text-green-600 transition rounded-lg hover:bg-slate-50 hidden"
                                    title="復元">
                                <i class="fa-solid fa-box-open"></i>
                            </button>
                            <button id="listDeleteBtn" type="button"
                                    class="p-2 text-slate-500 hover:text-red-500 transition rounded-lg hover:bg-slate-50"
                                    title="削除">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>

                        <button id="btnSaveListModal" type="button"
                                class="px-6 py-2 note-theme-btn text-white text-sm font-bold rounded-lg transition shadow-sm">
                            完了
                        </button>
                    </div>

                    <div id="listInlineColorPicker" class="inline-color-picker flex-wrap gap-3 p-4 bg-slate-50 rounded-lg mt-3">
                        <div class="color-swatch" data-color="#ffffff" style="background-color: #ffffff; border: 2px solid #e2e8f0;"></div>
                        <div class="color-swatch" data-color="#fef3c7" style="background-color: #fef3c7;"></div>
                        <div class="color-swatch" data-color="#fecaca" style="background-color: #fecaca;"></div>
                        <div class="color-swatch" data-color="#bfdbfe" style="background-color: #bfdbfe;"></div>
                        <div class="color-swatch" data-color="#bbf7d0" style="background-color: #bbf7d0;"></div>
                        <div class="color-swatch" data-color="#ddd6fe" style="background-color: #ddd6fe;"></div>
                        <div class="color-swatch" data-color="#fbcfe8" style="background-color: #fbcfe8;"></div>
                        <div class="color-swatch" data-color="#fed7aa" style="background-color: #fed7aa;"></div>
                        <div class="color-swatch" data-color="#e0e7ff" style="background-color: #e0e7ff;"></div>
                        <div class="color-swatch" data-color="#f3f4f6" style="background-color: #f3f4f6;"></div>
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

        function updateViewTabButtons(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => {
                const isActive = b.dataset.tab === tab;
                b.classList.toggle('border-[var(--note-theme)]', isActive);
                b.classList.toggle('text-[var(--note-theme)]', isActive);
                b.classList.toggle('border-transparent', !isActive);
                b.classList.toggle('text-slate-400', !isActive);
            });
        }

        const LIST_KINDS = <?= json_encode($listKinds ?? [], JSON_UNESCAPED_UNICODE) ?>;

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
                updateViewTabButtons(tab);
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

        const ListManager = {
            mainTab: 'memo',
            viewMode: 'active',
            listKind: 'todo',
            listKinds: LIST_KINDS,
            entriesByKind: <?= json_encode($listEntries ?? [], JSON_UNESCAPED_UNICODE) ?>,
            archivedEntriesByKind: <?= json_encode($archivedListEntries ?? [], JSON_UNESCAPED_UNICODE) ?>,
            currentEntryId: null,
            currentBgColor: '#ffffff',

            parsePayloadIfNeeded(entry) {
                if (!entry || typeof entry !== 'object') return entry;
                if (typeof entry.payload === 'string') {
                    try {
                        const p = JSON.parse(entry.payload);
                        entry.payload = (p && typeof p === 'object') ? p : {};
                    } catch (_) {
                        entry.payload = {};
                    }
                }
                if (!entry.payload || typeof entry.payload !== 'object') entry.payload = {};
                return entry;
            },

            getCurrentList() {
                const src = this.viewMode === 'active' ? this.entriesByKind : this.archivedEntriesByKind;
                return (src && src[this.listKind]) ? src[this.listKind] : [];
            },

            getEntry(id) {
                const all = (this.entriesByKind[this.listKind] || []).concat(this.archivedEntriesByKind[this.listKind] || []);
                return all.find(e => e.id == id);
            },

            switchMain(tab) {
                this.mainTab = tab;
                document.querySelectorAll('.main-tab-btn').forEach(b => {
                    const isActive = b.dataset.mainTab === tab;
                    b.classList.toggle('border-[var(--note-theme)]', isActive);
                    b.classList.toggle('text-[var(--note-theme)]', isActive);
                    b.classList.toggle('border-transparent', !isActive);
                    b.classList.toggle('text-slate-400', !isActive);
                });

                document.getElementById('quickMemoSection').classList.toggle('hidden', tab !== 'memo');
                document.getElementById('notesEmptyState').classList.toggle('hidden', tab !== 'memo' || (NoteManager.viewMode === 'active' ? NoteManager.notes.length : NoteManager.archivedNotes.length) !== 0);
                document.getElementById('notesGrid').classList.toggle('hidden', tab !== 'memo');
                document.getElementById('listSection').classList.toggle('hidden', tab !== 'list');

                if (tab === 'memo') {
                    NoteManager.renderNotesGrid();
                } else {
                    this.renderKindChips();
                    this.renderListGrid();
                }
            },

            switchKind(kind) {
                if (!this.listKinds || !Object.prototype.hasOwnProperty.call(this.listKinds, kind)) return;
                this.listKind = kind;
                this.renderKindChips();
                this.renderListGrid();
            },

            renderKindChips() {
                const box = document.getElementById('listKindChips');
                if (!box) return;
                const kinds = this.listKinds || {};
                const html = Object.keys(kinds).map(k => {
                    const active = k === this.listKind;
                    const cls = active
                        ? 'bg-[var(--note-theme)] text-white border-[var(--note-theme)]'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
                    return '<button type="button" class="list-kind-chip shrink-0 px-3 py-1.5 rounded-full border text-xs font-black transition ' + cls + '" data-kind="' + escapeHtml(k) + '">' +
                        escapeHtml(kinds[k]) + '</button>';
                }).join('');
                box.innerHTML = html;
                box.querySelectorAll('.list-kind-chip').forEach(btn => {
                    btn.addEventListener('click', () => this.switchKind(btn.dataset.kind));
                });
            },

            renderEntry(kind, entry, viewMode) {
                entry = this.parsePayloadIfNeeded(entry);
                const bgColor = escapeHtml(entry.bg_color || '#ffffff');
                const dateStr = formatNoteDate(entry.created_at);
                const p = entry.payload || {};

                const getHeadingText = () => {
                    if (kind === 'todo' || kind === 'generic_list') {
                        const items = Array.isArray(p.items) ? p.items : [];
                        for (const it of items) {
                            const t = (it && typeof it.text === 'string') ? it.text.trim() : '';
                            if (t) return t;
                        }
                        return '';
                    }
                    if (kind === 'question') return (typeof p.question === 'string' ? p.question.trim() : '');
                    if (kind === 'first_time') return (typeof p.what === 'string' ? p.what.trim() : '');
                    if (kind === 'fun') return (typeof p.hook === 'string' ? p.hook.trim() : '');
                    if (kind === 'book') return (typeof p.title === 'string' ? p.title.trim() : '');
                    return '';
                };
                const headingText = getHeadingText();
                const headingEscaped = escapeHtml(headingText);

                // 一覧表示では操作アイコン類は表示しない（操作はモーダル側に集約）
                const pinBadge = entry.is_pinned
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black border border-slate-200 bg-white/70 text-slate-700">ピン</span>'
                    : '';

                // first_time: timeline
                if (kind === 'first_time') {
                    const what = escapeHtml(p.what || '');
                    const memo = escapeHtml(p.memo || '');
                    const dateLabel = escapeHtml((p.occurred_at || '').replace(/-/g, '.'));
                    const dotStyle = 'background-color: var(--note-theme); border-color: rgba(245,158,11,0.12);';
                    return '<div class="list-entry relative pl-8" data-entry-id="' + entry.id + '">' +
                        '<div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-4 z-10" style="' + dotStyle + '"></div>' +
                        '<div class="custom-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm cursor-pointer" style="background-color:' + bgColor + ';">' +
                        '<div class="flex justify-between items-start mb-2 gap-3">' +
                        '<div class="min-w-0">' +
                        (what ? '<h3 class="font-bold text-slate-800 truncate">' + what + '</h3>' : '<h3 class="font-bold text-slate-800 truncate">(無題)</h3>') +
                        '</div>' +
                        '<div class="shrink-0 flex items-center gap-2">' + pinBadge +
                        (dateLabel ? '<span class="text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-1 rounded">' + dateLabel + '</span>' : '') +
                        '</div>' +
                        '</div>' +
                        (memo ? '<p class="text-sm text-slate-600 leading-relaxed">' + memo + '</p>' : '') +
                        '</div>' +
                        '</div>';
                }

                // question: Q&A card (solved/in-progress)
                if (kind === 'question') {
                    const q = escapeHtml(p.question || '');
                    const h = escapeHtml(p.hypothesis || '');
                    const a = escapeHtml(p.answer || '');
                    const solved = !!a;
                    const headClass = solved ? 'solved-gradient border-b border-green-100' : 'bg-slate-50 border-b border-slate-100';
                    const statusHtml = solved
                        ? '<div class="flex items-center gap-2 mb-1"><i class="fa-solid fa-circle-check text-green-500 text-[10px]"></i><span class="text-[10px] font-black text-green-600 uppercase tracking-widest">Solved</span></div>'
                        : '<div class="flex items-center gap-2 mb-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span><span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">In Progress</span></div>';
                    const answerHtml = solved
                        ? '<div class="bg-green-50 p-3 rounded-xl border border-green-100"><span class="text-[10px] font-bold text-green-600 block mb-1 uppercase">Answer</span><p class="text-sm text-slate-700 leading-relaxed">' + a + '</p></div>'
                        : '<div class="text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200"><p class="text-[11px] text-slate-400 font-bold">回答を調査中...</p></div>';
                    return '<div class="list-entry custom-card bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col cursor-pointer" data-entry-id="' + entry.id + '">' +
                        '<div class="p-5 ' + headClass + '">' + statusHtml +
                        '<h3 class="font-bold text-slate-800">' + (q || headingEscaped || '(無題)') + '</h3>' +
                        '<div class="mt-2 flex items-center justify-end gap-2">' + pinBadge + '</div>' +
                        '</div>' +
                        '<div class="p-5 space-y-4 flex-1">' +
                        (h ? '<div class="relative pl-4 border-l-2 border-slate-200"><span class="text-[10px] font-bold text-slate-400 block mb-1 uppercase">Hypothesis</span><p class="text-xs text-slate-500 italic">' + h + '</p></div>' : '') +
                        answerHtml +
                        '</div>' +
                        '<div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">' +
                        '<span class="text-[10px] text-slate-400">' + dateStr + '</span>' +
                        '</div>' +
                        '</div>';
                }

                // book: split layout
                if (kind === 'book') {
                    const bt = escapeHtml(p.title || headingText || '');
                    const why = escapeHtml(p.why_read || '');
                    const notes = escapeHtml(p.notes || '');
                    return '<div class="list-entry custom-card bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden cursor-pointer" data-entry-id="' + entry.id + '">' +
                        '<div class="flex flex-col md:flex-row">' +
                        '<div class="md:w-1/3 p-6 bg-slate-900 text-white flex flex-col justify-between">' +
                        '<div><h3 class="text-lg font-black tracking-tight mb-2">' + (bt || '(無題)') + '</h3></div>' +
                        '<div class="mt-6 flex items-center justify-between gap-2"><span class="text-[10px] font-black px-2 py-1 bg-white/10 rounded uppercase">Book</span>' + (pinBadge ? '<span class="text-[10px] font-black px-2 py-1 bg-white/10 rounded">' + pinBadge.replace(/<[^>]*>/g,'') + '</span>' : '') + '</div>' +
                        '</div>' +
                        '<div class="md:w-2/3 p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 bg-white" style="background-color:' + bgColor + ';">' +
                        '<div><div class="flex items-center gap-2 mb-2"><i class="fa-solid fa-bullseye text-slate-300 text-xs"></i><span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Why read?</span></div>' +
                        '<p class="text-sm text-slate-600 leading-relaxed">' + (why || '—') + '</p></div>' +
                        '<div class="relative pl-6 border-l border-slate-100"><div class="flex items-center gap-2 mb-2"><i class="fa-solid fa-bolt text-amber-500 text-xs"></i><span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Notes</span></div>' +
                        '<p class="text-sm text-slate-700 font-bold leading-relaxed italic">' + (notes || '—') + '</p></div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">' +
                        '<span class="text-[10px] text-slate-400">' + dateStr + '</span>' +
                        '</div>' +
                        '</div>';
                }

                // default: list row
                let preview = '';
                if (kind === 'todo' || kind === 'generic_list') {
                    const items = Array.isArray(p.items) ? p.items : [];
                    preview = items.slice(0, 4).map(it => {
                        const t = escapeHtml(it?.text || '');
                        if (!t) return '';
                        if (kind === 'todo') {
                            const done = it?.done ? ' checked' : '';
                            return '<label class="flex items-center gap-2 text-sm"><input type="checkbox" disabled' + done + '> <span>' + t + '</span></label>';
                        }
                        return '<div class="text-sm">・' + t + '</div>';
                    }).join('');
                } else if (kind === 'fun') {
                    const hook = escapeHtml(p.hook || '');
                    const detail = escapeHtml(p.detail || '');
                    preview = '<div class="text-sm text-slate-700 font-bold">' + (hook || headingEscaped || '(無題)') + '</div>' +
                        (detail ? '<div class="text-xs text-slate-500 mt-1">' + detail + '</div>' : '');
                } else {
                    preview = headingEscaped ? '<div class="text-sm text-slate-700">' + headingEscaped + '</div>' : '';
                }

                return '<div class="list-entry list-row border border-slate-100 rounded-xl shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition" style="background-color:' + bgColor + ';" data-entry-id="' + entry.id + '">' +
                    '<div class="px-4 py-3 flex items-start gap-3">' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="flex items-center gap-2 mb-1">' +
                    (headingEscaped ? '<h3 class="font-black text-slate-800 text-sm truncate">' + headingEscaped + '</h3>' : '<h3 class="font-black text-slate-800 text-sm truncate">(無題)</h3>') +
                    pinBadge +
                    '</div>' +
                    '<div class="text-slate-700 text-sm">' + preview + '</div>' +
                    '<div class="text-[10px] text-slate-400 mt-2">' + dateStr + '</div>' +
                    '</div>' +
                    '</div></div>';
            },

            renderListGrid() {
                const grid = document.getElementById('listGrid');
                const emptyState = document.getElementById('listEmptyState');
                if (!grid || !emptyState) return;
                const list = this.getCurrentList();
                if (!list || list.length === 0) {
                    grid.innerHTML = '';
                    emptyState.classList.remove('hidden');
                    return;
                }
                emptyState.classList.add('hidden');
                // kindごとにコンテナレイアウトを切替
                grid.className = '';
                if (this.listKind === 'question') {
                    grid.className = 'grid grid-cols-1 md:grid-cols-2 gap-6';
                } else if (this.listKind === 'first_time') {
                    grid.className = 'timeline-container ml-2 space-y-8';
                } else if (this.listKind === 'book') {
                    grid.className = 'space-y-4';
                } else {
                    grid.className = 'space-y-2';
                }

                grid.innerHTML = list.map(e => this.renderEntry(this.listKind, e, this.viewMode)).join('');

                grid.querySelectorAll('.list-entry').forEach(card => {
                    card.addEventListener('click', (e) => {
                        const id = parseInt(card.dataset.entryId, 10);
                        this.openModal(id);
                    });
                });
            },

            openModal(entryIdOrNull) {
                const modal = document.getElementById('listDetailModal');
                const content = document.getElementById('listDetailModalContent');
                modal.classList.remove('hidden');

                const isNew = !entryIdOrNull;
                const entry = isNew ? null : this.getEntry(entryIdOrNull);
                this.currentEntryId = entry ? entry.id : null;
                this.currentBgColor = entry?.bg_color || '#ffffff';
                content.style.backgroundColor = this.currentBgColor;

                document.getElementById('listDetailId').value = entry ? entry.id : '';
                document.getElementById('listDetailKind').value = this.listKind;
                document.getElementById('listDetailMeta').textContent = entry ? ('作成: ' + entry.created_at + (entry.updated_at !== entry.created_at ? ' / 更新: ' + entry.updated_at : '')) : '';

                const pinBtn = document.getElementById('listPinBtn');
                pinBtn.classList.toggle('note-theme-text', !!entry?.is_pinned);
                document.getElementById('listRestoreBtn').classList.toggle('hidden', this.viewMode !== 'archived');
                document.getElementById('listArchiveBtn').classList.toggle('hidden', this.viewMode !== 'active');

                this.renderModalFields(entry?.payload || {});
                this.closeListColorPicker();
            },

            closeModal() {
                document.getElementById('listDetailModal').classList.add('hidden');
                this.currentEntryId = null;
            },

            renderModalFields(payload) {
                const box = document.getElementById('listDetailFields');
                if (!box) return;
                const p = payload || {};

                if (this.listKind === 'todo' || this.listKind === 'generic_list') {
                    const items = Array.isArray(p.items) ? p.items : [];
                    const rows = items.length ? items : [{ id: null, text: '', done: 0 }];
                    const isTodo = this.listKind === 'todo';
                    box.innerHTML =
                        '<div class="flex items-center justify-between gap-2">' +
                        '<h3 class="text-sm font-black text-slate-700">項目</h3>' +
                        '<button type="button" id="btnAddListItem" class="text-xs font-black note-theme-link hover:opacity-80 transition"><i class="fa-solid fa-plus mr-1"></i>追加</button>' +
                        '</div>' +
                        '<div id="listItemRows" class="space-y-2"></div>';

                    const rowsBox = document.getElementById('listItemRows');
                    const addRow = (it) => {
                        const row = document.createElement('div');
                        row.className = 'flex items-center gap-2';
                        const idVal = it?.id != null ? String(it.id) : '';
                        const textVal = it?.text != null ? String(it.text) : '';
                        const done = !!it?.done;
                        row.innerHTML =
                            '<input type="hidden" class="li-id" value="' + escapeHtml(idVal) + '">' +
                            (isTodo ? '<input type="checkbox" class="li-done" ' + (done ? 'checked' : '') + '>' : '') +
                            '<input type="text" class="li-text flex-1 h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none bg-white" placeholder="項目" value="' + escapeHtml(textVal) + '">' +
                            '<button type="button" class="btnRemoveLi h-10 px-3 rounded-lg border border-red-100 text-red-500 text-xs font-black hover:bg-red-50 transition">削除</button>';
                        rowsBox.appendChild(row);
                        row.querySelector('.btnRemoveLi').addEventListener('click', () => row.remove());
                    };
                    rows.forEach(addRow);
                    document.getElementById('btnAddListItem').addEventListener('click', () => addRow({ id: null, text: '', done: 0 }));
                    return;
                }

                if (this.listKind === 'question') {
                    box.innerHTML =
                        '<div class="space-y-2">' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">疑問</label><textarea id="q_question" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.question || '') + '</textarea></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">仮説</label><textarea id="q_hypothesis" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.hypothesis || '') + '</textarea></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">回答</label><textarea id="q_answer" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.answer || '') + '</textarea></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">仮説とのギャップ</label><textarea id="q_gap" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.gap || '') + '</textarea></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">他への転用</label><textarea id="q_transfer" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.transfer || '') + '</textarea></div>' +
                        '</div>';
                    return;
                }
                if (this.listKind === 'first_time') {
                    box.innerHTML =
                        '<div class="grid grid-cols-1 sm:grid-cols-3 gap-2">' +
                        '<div class="sm:col-span-1"><label class="block text-xs font-black text-slate-600 mb-1">日付</label><input id="ft_date" type="date" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm bg-white" value="' + escapeHtml(p.occurred_at || '') + '"></div>' +
                        '<div class="sm:col-span-2"><label class="block text-xs font-black text-slate-600 mb-1">何をした</label><input id="ft_what" type="text" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm bg-white" value="' + escapeHtml(p.what || '') + '"></div>' +
                        '</div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1 mt-2">メモ</label><textarea id="ft_memo" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.memo || '') + '</textarea></div>';
                    return;
                }
                if (this.listKind === 'fun') {
                    box.innerHTML =
                        '<div class="space-y-2">' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">ひとこと</label><input id="fun_hook" type="text" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm bg-white" value="' + escapeHtml(p.hook || '') + '"></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">詳細</label><textarea id="fun_detail" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.detail || '') + '</textarea></div>' +
                        '</div>';
                    return;
                }
                if (this.listKind === 'book') {
                    box.innerHTML =
                        '<div class="space-y-2">' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">書籍名</label><input id="book_title" type="text" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm bg-white" value="' + escapeHtml(p.title || '') + '"></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">なぜ読んだ</label><textarea id="book_why" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.why_read || '') + '</textarea></div>' +
                        '<div><label class="block text-xs font-black text-slate-600 mb-1">感想メモ</label><textarea id="book_notes" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white" rows="7">' + escapeHtml(p.notes || '') + '</textarea></div>' +
                        '</div>';
                    return;
                }

                box.innerHTML = '<p class="text-sm text-slate-500">この種別は未対応です。</p>';
            },

            collectPayloadFromModal() {
                if (this.listKind === 'todo' || this.listKind === 'generic_list') {
                    const rows = Array.from(document.querySelectorAll('#listItemRows > div'));
                    const items = [];
                    rows.forEach(r => {
                        const text = (r.querySelector('.li-text')?.value || '').trim();
                        if (!text) return;
                        const id = (r.querySelector('.li-id')?.value || '').trim() || null;
                        const done = this.listKind === 'todo' ? (r.querySelector('.li-done')?.checked ? 1 : 0) : undefined;
                        const it = { id, text };
                        if (this.listKind === 'todo') it.done = done;
                        items.push(it);
                    });
                    return { items };
                }
                if (this.listKind === 'question') {
                    return {
                        question: (document.getElementById('q_question')?.value || '').trim(),
                        hypothesis: (document.getElementById('q_hypothesis')?.value || '').trim(),
                        gap: (document.getElementById('q_gap')?.value || '').trim(),
                        answer: (document.getElementById('q_answer')?.value || '').trim(),
                        transfer: (document.getElementById('q_transfer')?.value || '').trim(),
                    };
                }
                if (this.listKind === 'first_time') {
                    return {
                        occurred_at: (document.getElementById('ft_date')?.value || '').trim(),
                        what: (document.getElementById('ft_what')?.value || '').trim(),
                        memo: (document.getElementById('ft_memo')?.value || '').trim(),
                    };
                }
                if (this.listKind === 'fun') {
                    return {
                        hook: (document.getElementById('fun_hook')?.value || '').trim(),
                        detail: (document.getElementById('fun_detail')?.value || '').trim(),
                    };
                }
                if (this.listKind === 'book') {
                    return {
                        title: (document.getElementById('book_title')?.value || '').trim(),
                        why_read: (document.getElementById('book_why')?.value || '').trim(),
                        notes: (document.getElementById('book_notes')?.value || '').trim(),
                    };
                }
                return {};
            },

            async saveFromModal() {
                const idRaw = (document.getElementById('listDetailId').value || '').trim();
                const payload = this.collectPayloadFromModal();
                const bg_color = this.currentBgColor || '#ffffff';

                try {
                    if (!idRaw) {
                        const res = await App.post('/note/api/list_save.php', { list_kind: this.listKind, payload, bg_color });
                        if (res.status !== 'success') { alert('エラー: ' + (res.message || '保存に失敗しました')); return; }
                        const entry = this.parsePayloadIfNeeded(res.entry);
                        if (entry) {
                            this.entriesByKind[this.listKind] = [entry, ...(this.entriesByKind[this.listKind] || [])];
                        }
                    } else {
                        const id = parseInt(idRaw, 10);
                        const res = await App.post('/note/api/list_update.php', { id, payload, bg_color });
                        if (res.status !== 'success') { alert('エラー: ' + (res.message || '更新に失敗しました')); return; }
                        const list = this.viewMode === 'active' ? (this.entriesByKind[this.listKind] || []) : (this.archivedEntriesByKind[this.listKind] || []);
                        const idx = list.findIndex(e => e.id == id);
                        if (idx >= 0) list[idx] = { ...list[idx], payload, bg_color };
                    }
                    this.renderListGrid();
                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    alert('エラーが発生しました');
                }
            },

            switchViewMode(tab) {
                this.viewMode = tab;
                updateViewTabButtons(tab);
                this.renderListGrid();
            },

            async togglePin(id) {
                try {
                    const res = await App.post('/note/api/list_toggle_pin.php', { id });
                    if (res.status !== 'success') { alert('エラー: ' + (res.message || 'ピン留めに失敗しました')); return; }
                    const listA = this.entriesByKind[this.listKind] || [];
                    const listB = this.archivedEntriesByKind[this.listKind] || [];
                    [listA, listB].forEach(list => {
                        const idx = list.findIndex(e => e.id == id);
                        if (idx >= 0) list[idx] = { ...list[idx], is_pinned: list[idx].is_pinned ? 0 : 1 };
                    });
                    this.renderListGrid();
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            async archive(id) {
                try {
                    const res = await App.post('/note/api/list_update.php', { id, status: 'archived' });
                    if (res.status !== 'success') { alert('エラー: ' + (res.message || 'アーカイブに失敗しました')); return; }
                    const cur = this.entriesByKind[this.listKind] || [];
                    const idx = cur.findIndex(e => e.id == id);
                    if (idx >= 0) {
                        const entry = { ...cur[idx], status: 'archived' };
                        this.entriesByKind[this.listKind] = cur.filter(e => e.id != id);
                        this.archivedEntriesByKind[this.listKind] = [entry, ...(this.archivedEntriesByKind[this.listKind] || [])];
                    }
                    this.renderListGrid();
                    if (this.currentEntryId == id) this.closeModal();
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            async restore(id) {
                try {
                    const res = await App.post('/note/api/list_update.php', { id, status: 'active' });
                    if (res.status !== 'success') { alert('エラー: ' + (res.message || '復元に失敗しました')); return; }
                    const cur = this.archivedEntriesByKind[this.listKind] || [];
                    const idx = cur.findIndex(e => e.id == id);
                    if (idx >= 0) {
                        const entry = { ...cur[idx], status: 'active' };
                        this.archivedEntriesByKind[this.listKind] = cur.filter(e => e.id != id);
                        this.entriesByKind[this.listKind] = [entry, ...(this.entriesByKind[this.listKind] || [])];
                    }
                    this.renderListGrid();
                    if (this.currentEntryId == id) this.closeModal();
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            async remove(id) {
                if (!confirm('このリストを削除してもよろしいですか?')) return;
                try {
                    const res = await App.post('/note/api/list_delete.php', { id });
                    if (res.status !== 'success') { alert('エラー: ' + (res.message || '削除に失敗しました')); return; }
                    this.entriesByKind[this.listKind] = (this.entriesByKind[this.listKind] || []).filter(e => e.id != id);
                    this.archivedEntriesByKind[this.listKind] = (this.archivedEntriesByKind[this.listKind] || []).filter(e => e.id != id);
                    this.renderListGrid();
                    if (this.currentEntryId == id) this.closeModal();
                } catch (e) { console.error(e); alert('エラーが発生しました'); }
            },

            toggleListColorPicker() {
                const picker = document.getElementById('listInlineColorPicker');
                if (picker.classList.contains('active')) picker.classList.remove('active');
                else { picker.classList.add('active'); this.updateListColorPickerSelection(); }
            },
            closeListColorPicker() {
                document.getElementById('listInlineColorPicker').classList.remove('active');
            },
            updateListColorPickerSelection() {
                const currentColor = this.currentBgColor || '#ffffff';
                document.querySelectorAll('#listInlineColorPicker .color-swatch').forEach(swatch => {
                    swatch.classList.toggle('selected', (swatch.dataset.color || '').toLowerCase() === String(currentColor).toLowerCase());
                });
            },
            async changeListColor(color) {
                this.currentBgColor = color;
                document.getElementById('listDetailModalContent').style.backgroundColor = color;
                this.updateListColorPickerSelection();
                const idRaw = (document.getElementById('listDetailId').value || '').trim();
                if (!idRaw) return;
                try {
                    const id = parseInt(idRaw, 10);
                    const res = await App.post('/note/api/list_update.php', { id, bg_color: color });
                    if (res.status !== 'success') return;
                    const list = this.viewMode === 'active' ? (this.entriesByKind[this.listKind] || []) : (this.archivedEntriesByKind[this.listKind] || []);
                    const idx = list.findIndex(e => e.id == id);
                    if (idx >= 0) list[idx] = { ...list[idx], bg_color: color };
                    const card = document.querySelector('.note-card[data-entry-id="' + id + '"]');
                    if (card) card.style.backgroundColor = color;
                } catch (e) { console.error(e); }
            },
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
            const applyViewMode = (tab) => {
                if (ListManager.mainTab === 'list') {
                    ListManager.switchViewMode(tab);
                } else {
                    NoteManager.switchTab(tab);
                }
            };
            document.getElementById('tabActive').addEventListener('click', () => applyViewMode('active'));
            document.getElementById('tabArchived').addEventListener('click', () => applyViewMode('archived'));
            NoteManager.renderNotesGrid();

            // 主タブ
            document.getElementById('mainTabMemo').addEventListener('click', () => ListManager.switchMain('memo'));
            document.getElementById('mainTabList').addEventListener('click', () => ListManager.switchMain('list'));

            // リスト: 新規
            document.getElementById('btnNewListEntry').addEventListener('click', () => ListManager.openModal(null));

            // リストモーダル: close/save/actions
            const listModal = document.getElementById('listDetailModal');
            listModal.addEventListener('click', (e) => { if (e.target === listModal) ListManager.closeModal(); });
            document.getElementById('btnCloseListModal').addEventListener('click', () => ListManager.closeModal());
            document.getElementById('btnSaveListModal').addEventListener('click', () => ListManager.saveFromModal());
            document.getElementById('listColorPickerToggleBtn').addEventListener('click', (e) => { e.stopPropagation(); ListManager.toggleListColorPicker(); });
            document.querySelectorAll('#listInlineColorPicker .color-swatch').forEach(s => s.addEventListener('click', (e) => { e.stopPropagation(); ListManager.changeListColor(s.dataset.color); }));
            document.getElementById('listPinBtn').addEventListener('click', async () => {
                if (!ListManager.currentEntryId) return;
                await ListManager.togglePin(ListManager.currentEntryId);
                const entry = ListManager.getEntry(ListManager.currentEntryId);
                if (entry) document.getElementById('listPinBtn').classList.toggle('note-theme-text', !!entry.is_pinned);
            });
            document.getElementById('listArchiveBtn').addEventListener('click', async () => { if (ListManager.currentEntryId) await ListManager.archive(ListManager.currentEntryId); });
            document.getElementById('listRestoreBtn').addEventListener('click', async () => { if (ListManager.currentEntryId) await ListManager.restore(ListManager.currentEntryId); });
            document.getElementById('listDeleteBtn').addEventListener('click', async () => { if (ListManager.currentEntryId) await ListManager.remove(ListManager.currentEntryId); });

            // URLパラメータで直接リスト種別を開く（/note?tab=list&kind=todo）
            const params = new URLSearchParams(location.search || '');
            const tab = params.get('tab');
            const kind = params.get('kind');
            if (tab === 'list') {
                ListManager.switchMain('list');
                if (kind) {
                    ListManager.switchKind(kind);
                }
            }
        });
    </script>
</body>
</html>
