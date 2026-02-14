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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --note-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .note-theme-btn { background-color: var(--note-theme); }
        .note-theme-btn:hover { filter: brightness(1.08); }
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
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }

        /* Google Keep風のMasonry風レイアウト（カード高さはコンテンツに応じて可変） */
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 0.75rem;
            align-items: start;
        }

        .note-card {
            break-inside: avoid;
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

                <?php if (empty($notes)): ?>
                    <div class="text-center py-16">
                        <div class="w-20 h-20 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-lightbulb text-4xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">メモがありません</h3>
                    </div>
                <?php else: ?>
                    <div class="notes-grid">
                        <?php foreach ($notes as $note): ?>
                            <div class="note-card bg-white rounded-lg border border-slate-100 shadow-sm overflow-hidden cursor-pointer" 
                                 style="background-color: <?= htmlspecialchars($note['bg_color']) ?>;"
                                 data-note-id="<?= $note['id'] ?>"
                                 onclick="NoteManager.showDetailModal(<?= $note['id'] ?>, event)">
                                <div class="p-4">
                                    <?php if ($note['is_pinned']): ?>
                                        <div class="flex justify-end mb-1.5">
                                            <i class="fa-solid fa-thumbtack note-theme-text text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($note['title'])): ?>
                                        <h3 class="font-bold text-slate-800 mb-2 text-base"><?= htmlspecialchars($note['title']) ?></h3>
                                    <?php endif; ?>
                                    
                                    <div class="note-content text-slate-700 text-sm mb-3"><?php
                                        $raw = (string)($note['content'] ?? '');
                                        $raw = preg_replace('/\r\n|\r/u', "\n", $raw);
                                        $raw = preg_replace('/\n{2,}/u', "\n", $raw);
                                        $raw = trim($raw);
                                        echo htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
                                    ?></div>
                                    
                                    <div class="text-xs text-slate-400 mb-3">
                                        <?= \Core\Utils\DateUtil::format($note['created_at'], 'Y/m/d H:i') ?>
                                    </div>
                                    
                                    <div class="note-actions flex items-center justify-between pt-2.5 border-t border-slate-200">
                                        <button onclick="NoteManager.togglePin(<?= $note['id'] ?>)" 
                                                class="p-2 text-slate-500 hover-note-theme transition rounded-lg hover:bg-slate-50"
                                                title="ピン留め">
                                            <i class="fa-solid fa-thumbtack text-sm"></i>
                                        </button>
                                        
                                        <button onclick="NoteManager.showColorPicker(<?= $note['id'] ?>)" 
                                                class="p-2 text-slate-500 hover:text-blue-500 transition rounded-lg hover:bg-slate-50"
                                                title="色を変更">
                                            <i class="fa-solid fa-palette text-sm"></i>
                                        </button>
                                        
                                        <button onclick="event.stopPropagation(); NoteManager.showDetailModal(<?= $note['id'] ?>)" 
                                                class="p-2 text-slate-500 hover:text-green-500 transition rounded-lg hover:bg-slate-50"
                                                title="編集">
                                            <i class="fa-solid fa-pen text-sm"></i>
                                        </button>
                                        
                                        <button onclick="event.stopPropagation(); NoteManager.deleteNote(<?= $note['id'] ?>)" 
                                                class="p-2 text-slate-500 hover:text-red-500 transition rounded-lg hover:bg-slate-50"
                                                title="削除">
                                            <i class="fa-solid fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

    <script src="/assets/js/core.js"></script>
    <script>
        // テキストエリアの高さを内容に合わせて自動伸長
        function autoResizeTextarea(ta) {
            ta.style.height = 'auto';
            ta.style.height = Math.max(40, Math.min(ta.scrollHeight, 400)) + 'px';
        }

        // クイックメモ機能
        const QuickMemo = {
            input: null,
            titleInput: null,
            actions: null,

            init() {
                this.input = document.getElementById('quickMemoInput');
                this.titleInput = document.getElementById('quickMemoTitle');
                this.actions = document.getElementById('quickMemoActions');
                
                if (this.input) {
                    this.input.addEventListener('focus', () => {
                        this.actions.classList.remove('opacity-0');
                        this.actions.classList.add('opacity-100');
                    });
                    
                    this.input.addEventListener('input', () => autoResizeTextarea(this.input));
                    this.input.addEventListener('focus', () => autoResizeTextarea(this.input));
                    
                    this.input.addEventListener('blur', () => {
                        setTimeout(() => {
                            if (!this.input.value.trim() && (!this.titleInput || !this.titleInput.value.trim())) {
                                this.actions.classList.remove('opacity-100');
                                this.actions.classList.add('opacity-0');
                            }
                        }, 200);
                    });
                }
            },

            async save(event) {
                const title = this.titleInput ? this.titleInput.value.trim() : '';
                const content = this.input.value.trim();
                
                if (!content) {
                    alert('メモの内容を入力してください');
                    return;
                }

                try {
                    const result = await App.post('/note/api/save.php', { title: title, content: content });

                    if (result && result.status === 'success') {
                        location.reload();
                    } else {
                        console.error('API Error Response:', result);
                        alert('エラー: ' + (result && result.message ? result.message : '保存に失敗しました'));
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('保存中にエラーが発生しました');
                }
            }
        };

        const NoteManager = {
            currentNoteId: null,
            notes: <?= json_encode($notes) ?>,

            async togglePin(noteId) {
                try {
                    const result = await App.post('/note/api/toggle_pin.php', { id: noteId });
                    if (result.status === 'success') {
                        location.reload();
                    } else {
                        alert('エラー: ' + (result.message || 'ピン留めに失敗しました'));
                    }
                } catch (error) {
                    console.error('Pin toggle error:', error);
                    alert('エラーが発生しました');
                }
            },

            toggleColorPicker(event) {
                event.stopPropagation();
                const picker = document.getElementById('inlineColorPicker');
                const isActive = picker.classList.contains('active');
                
                if (isActive) {
                    picker.classList.remove('active');
                } else {
                    picker.classList.add('active');
                    this.updateColorPickerSelection();
                }
            },

            closeColorPicker() {
                const picker = document.getElementById('inlineColorPicker');
                picker.classList.remove('active');
            },

            updateColorPickerSelection() {
                const currentColor = document.getElementById('detailModalContent').style.backgroundColor || '#ffffff';
                const swatches = document.querySelectorAll('.color-swatch');
                
                swatches.forEach(swatch => {
                    const swatchColor = swatch.dataset.color;
                    // RGBからHEXへの変換、または直接比較
                    if (this.compareColors(currentColor, swatchColor)) {
                        swatch.classList.add('selected');
                    } else {
                        swatch.classList.remove('selected');
                    }
                });
            },

            compareColors(color1, color2) {
                // 簡易的な色比較
                const normalize = (c) => {
                    if (c.startsWith('rgb')) {
                        const values = c.match(/\d+/g);
                        return '#' + values.map(v => parseInt(v).toString(16).padStart(2, '0')).join('');
                    }
                    return c.toLowerCase();
                };
                return normalize(color1) === normalize(color2);
            },

            async changeColorInline(color) {
                if (!this.currentNoteId) return;

                try {
                    // 即座にUIを更新
                    const modalContent = document.getElementById('detailModalContent');
                    modalContent.style.backgroundColor = color;
                    
                    // 選択状態を更新
                    this.updateColorPickerSelection();

                    // APIで保存
                    const result = await App.post('/note/api/update.php', { 
                        id: this.currentNoteId,
                        bg_color: color 
                    });
                    
                    if (result.status !== 'success') {
                        // エラー時は元に戻す
                        console.error('Color change failed:', result);
                        alert('色の変更に失敗しました');
                    }
                } catch (error) {
                    console.error('Color change error:', error);
                }
            },

            showEditModal(noteId) {
                const note = this.notes.find(n => n.id == noteId);
                if (!note) return;

                document.getElementById('editNoteId').value = noteId;
                document.getElementById('editTitle').value = note.title || '';
                document.getElementById('editContent').value = note.content || '';
                document.getElementById('editModal').classList.remove('hidden');
            },

            closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editNoteId').value = '';
                document.getElementById('editTitle').value = '';
                document.getElementById('editContent').value = '';
            },

            async saveEdit() {
                const noteId = document.getElementById('editNoteId').value;
                const title = document.getElementById('editTitle').value.trim();
                const content = document.getElementById('editContent').value.trim();

                if (!content) {
                    alert('内容を入力してください');
                    return;
                }

                try {
                    const result = await App.post('/note/api/update.php', { 
                        id: noteId,
                        title: title,
                        content: content
                    });
                    if (result.status === 'success') {
                        this.closeEditModal();
                        location.reload();
                    } else {
                        alert('エラー: ' + (result.message || '更新に失敗しました'));
                    }
                } catch (error) {
                    console.error('Update error:', error);
                    alert('エラーが発生しました');
                }
            },

            async deleteNote(noteId) {
                if (!confirm('このメモを削除してもよろしいですか?')) {
                    return;
                }

                try {
                    const result = await App.post('/note/api/delete.php', { id: noteId });
                    if (result.status === 'success') {
                        location.reload();
                    } else {
                        alert('エラー: ' + (result.message || '削除に失敗しました'));
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('エラーが発生しました');
                }
            },

            // 詳細モーダル関連
            showDetailModal(noteId, sourceEvent) {
                const note = this.notes.find(n => n.id == noteId);
                if (!note) return;

                this.currentNoteId = noteId;
                document.getElementById('detailNoteId').value = noteId;
                document.getElementById('detailTitle').value = note.title || '';
                const detailContent = document.getElementById('detailContent');
                detailContent.value = note.content || '';
                autoResizeTextarea(detailContent);
                document.getElementById('detailTimestamp').textContent = 
                    '作成: ' + note.created_at + (note.updated_at !== note.created_at ? ' / 更新: ' + note.updated_at : '');
                
                // ピン留めボタンの状態を反映
                const pinBtn = document.getElementById('detailPinBtn');
                if (note.is_pinned) {
                    pinBtn.classList.add('note-theme-text');
                } else {
                    pinBtn.classList.remove('note-theme-text');
                }
                
                const modal = document.getElementById('detailModal');
                const modalContent = document.getElementById('detailModalContent');
                
                // メモの背景色を適用
                modalContent.style.backgroundColor = note.bg_color || '#ffffff';
                
                // クリック位置から画面中央までの移動距離を計算
                if (sourceEvent && sourceEvent.currentTarget) {
                    const rect = sourceEvent.currentTarget.getBoundingClientRect();
                    const clickX = rect.left + rect.width / 2;
                    const clickY = rect.top + rect.height / 2;
                    const centerX = window.innerWidth / 2;
                    const centerY = window.innerHeight / 2;
                    const translateX = clickX - centerX;
                    const translateY = clickY - centerY;
                    
                    modalContent.style.setProperty('--modal-translate-x', `${translateX}px`);
                    modalContent.style.setProperty('--modal-translate-y', `${translateY}px`);
                }
                
                // カラーピッカーを非表示にリセット
                this.closeColorPicker();
                
                modal.classList.remove('hidden', 'modal-closing');
                modal.classList.add('active', 'modal-opening');
                
                // モーダル外クリックでカラーピッカーを閉じる
                modal.onclick = (e) => {
                    if (e.target === modal) {
                        this.closeDetailModal();
                    } else if (!e.target.closest('#inlineColorPicker') && !e.target.closest('#colorPickerToggleBtn')) {
                        this.closeColorPicker();
                    }
                };
                
                // アニメーション完了後にクラスを削除
                setTimeout(() => {
                    modal.classList.remove('modal-opening');
                }, 400);
            },

            closeDetailModal() {
                const modal = document.getElementById('detailModal');
                this.closeColorPicker();
                modal.classList.remove('modal-opening');
                modal.classList.add('modal-closing');
                
                // アニメーション完了後にモーダルを非表示
                setTimeout(() => {
                    modal.classList.remove('active', 'modal-closing');
                    modal.classList.add('hidden');
                    this.currentNoteId = null;
                    // ページをリロードして変更を反映
                    location.reload();
                }, 250);
            },

            async saveDetail() {
                const noteId = document.getElementById('detailNoteId').value;
                const title = document.getElementById('detailTitle').value.trim();
                const content = document.getElementById('detailContent').value.trim();

                if (!content) {
                    alert('内容を入力してください');
                    return;
                }

                try {
                    const result = await App.post('/note/api/update.php', { 
                        id: noteId,
                        title: title,
                        content: content
                    });
                    if (result.status === 'success') {
                        location.reload();
                    } else {
                        alert('エラー: ' + (result.message || '更新に失敗しました'));
                    }
                } catch (error) {
                    console.error('Update error:', error);
                    alert('エラーが発生しました');
                }
            },

            async togglePinFromDetail() {
                const noteId = this.currentNoteId;
                await this.togglePin(noteId);
            },

            showColorPickerFromDetail() {
                this.showColorPicker(this.currentNoteId);
            },

            async deleteNoteFromDetail() {
                const noteId = this.currentNoteId;
                if (!confirm('このメモを削除してもよろしいですか?')) {
                    return;
                }

                try {
                    const result = await App.post('/note/api/delete.php', { id: noteId });
                    if (result.status === 'success') {
                        this.closeDetailModal();
                        location.reload();
                    } else {
                        alert('エラー: ' + (result.message || '削除に失敗しました'));
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('エラーが発生しました');
                }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            QuickMemo.init();
            const detailContent = document.getElementById('detailContent');
            if (detailContent) {
                detailContent.addEventListener('input', () => autoResizeTextarea(detailContent));
            }
        });
    </script>
</body>
</html>
