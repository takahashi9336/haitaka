<?php
/**
 * ミーグリ レポ（チャット形式）View
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$dayNames = ['日','月','火','水','木','金','土'];
$dt = new DateTime($slot['event_date']);
$dow = $dayNames[(int)$dt->format('w')];
$dateLabel = $dt->format('n/j') . "（{$dow}）";
$memberName = $member ? $member['name'] : ($slot['member_name_raw'] ?? '不明');
$memberColor = $member['color1'] ?? '#94a3b8';
$memberImage = $memberImage ?? $member['image_url'] ?? null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レポ - <?= htmlspecialchars($memberName) ?> <?= $dateLabel ?> - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        :root {
            --mg-theme: <?= htmlspecialchars($themePrimaryHex) ?>;
            --member-color: <?= htmlspecialchars($memberColor) ?>;
        }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }

        /* チャットバブル */
        .bubble-member {
            background: color-mix(in srgb, var(--member-color) 10%, white);
            border: 1px solid color-mix(in srgb, var(--member-color) 20%, white);
            border-radius: 2px 16px 16px 16px;
        }
        .bubble-self {
            background: var(--mg-theme);
            color: #fff;
            border-radius: 16px 2px 16px 16px;
        }
        .bubble-narration {
            background: #f1f5f9;
            color: #64748b;
            border-radius: 12px;
        }
        .bubble-self-thought {
            background: color-mix(in srgb, var(--mg-theme) 8%, white);
            border: 1px dashed color-mix(in srgb, var(--mg-theme) 30%, white);
            border-radius: 16px 2px 16px 16px;
            font-style: italic;
            color: #64748b;
        }

        .msg-row { transition: background-color 0.15s; }
        .msg-row:hover .msg-actions { opacity: 1; }
        .msg-actions { opacity: 0; transition: opacity 0.15s; }

        .insert-divider { position: relative; display: none; align-items: center; justify-content: center; height: 16px; margin: 0; z-index: 1; }
        .insert-mode .insert-divider { display: flex; }
        .insert-divider .insert-btn { opacity: 0.4; transition: opacity 0.2s, transform 0.15s; }
        .insert-divider:hover .insert-btn, .insert-divider .insert-btn:focus { opacity: 1; transform: scale(1.15); }
        .insert-divider:hover .insert-line { opacity: 1; }
        .insert-line { position: absolute; left: 10%; right: 10%; height: 1px; background: var(--mg-theme); opacity: 0; transition: opacity 0.2s; }
        .insert-divider.active .insert-btn { opacity: 1; transform: scale(1.15); }
        .insert-divider.active .insert-line { opacity: 1; }
        .insert-indicator { animation: insertPulse 1.5s ease-in-out infinite; }
        @keyframes insertPulse { 0%,100% { opacity: 0.7; } 50% { opacity: 1; } }

        .hc-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: #fff; overflow-y: auto;
        }
        .hc-overlay-header {
            position: sticky; top: 0; z-index: 1;
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
            display: flex; align-items: center; gap: 12px;
        }
        .hc-overlay-body { padding: 16px; max-width: 520px; margin: 0 auto; }
        .hc-overlay .msg-actions,
        .hc-overlay .insert-divider { display: none !important; }
        .hc-overlay .msg-row { cursor: default; pointer-events: none; }

        .sender-btn { transition: all 0.15s; }
        .sender-btn.active { font-weight: 700; }

        .report-card { transition: box-shadow 0.2s; }
        .report-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <!-- ヘッダー -->
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-xl"></i></button>
                <a href="/hinata/meetgreet.php" class="text-slate-400 p-2 shrink-0 transition hover:opacity-80"><i class="fa-solid fa-chevron-left"></i></a>
                <div class="relative cursor-pointer group shrink-0" onclick="MGR.changeAvatar()" title="アバターを変更">
                    <?php if ($memberImage): ?>
                    <img id="headerAvatar" src="<?= htmlspecialchars($memberImage) ?>" alt="" class="w-8 h-8 rounded-full object-cover border-2" style="border-color: var(--member-color);">
                    <?php else: ?>
                    <div id="headerAvatar" class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background: var(--member-color);">
                        <?= mb_substr($memberName, 0, 1) ?>
                    </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 rounded-full bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                        <i class="fa-solid fa-camera text-white text-[8px]"></i>
                    </div>
                </div>
                <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="MGR.uploadAvatar(this)">
                <div class="min-w-0">
                    <h1 class="font-black text-slate-700 text-base md:text-lg tracking-tight truncate"><?= htmlspecialchars($memberName) ?></h1>
                    <p class="text-[10px] text-slate-400"><?= $dateLabel ?> <?= htmlspecialchars($slot['slot_name']) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button onclick="MGR.addReport()" class="text-xs font-bold px-3 py-1.5 rounded-full <?= $cardIconText ?> <?= $cardIconBg ?> hover:opacity-90"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>+ レポ追加</button>
            </div>
        </header>

        <!-- レポ一覧 -->
        <div id="scrollContainer" class="flex-1 overflow-y-auto p-4 pb-24">
            <div id="reportsContainer" class="max-w-2xl mx-auto w-full space-y-4">

                <!-- メモ欄 -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 cursor-pointer" onclick="MGR.toggleMemo()">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-xs" style="color: var(--mg-theme);"></i>
                            <span class="text-xs font-bold text-slate-600">メモ</span>
                            <?php if (!empty($slot['report'])): ?>
                            <span class="text-[10px] text-slate-400">記入済み</span>
                            <?php endif; ?>
                        </div>
                        <i id="memoChevron" class="fa-solid fa-chevron-down text-slate-300 text-xs transition-transform duration-300 <?= !empty($slot['report']) ? 'rotate-180' : '' ?>"></i>
                    </div>
                    <div id="memoArea" class="px-4 py-3 <?= empty($slot['report']) && !empty($reports) ? 'hidden' : '' ?>">
                        <textarea id="memoText" rows="3" class="w-full border border-slate-200 rounded-lg p-3 text-sm outline-none focus:ring-2 resize-y leading-relaxed" style="--tw-ring-color: var(--mg-theme);" placeholder="メモ・感想を自由に記入..."><?= htmlspecialchars($slot['report'] ?? '') ?></textarea>
                        <div class="flex justify-end mt-2">
                            <button onclick="MGR.saveMemo()" class="text-[10px] font-bold text-white px-4 py-1.5 rounded-md transition active:scale-95" style="background: var(--mg-theme);">
                                <i class="fa-solid fa-check mr-0.5"></i>保存
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($reports)): ?>
                <div id="emptyState" class="text-center py-12">
                    <div class="text-4xl text-slate-200 mb-4"><i class="fa-solid fa-comments"></i></div>
                    <p class="text-slate-400 text-xs tracking-wider mb-4">チャット形式のレポもあります</p>
                    <button onclick="MGR.addReport()" class="text-sm font-bold text-white px-5 py-2.5 rounded-full transition active:scale-95" style="background: var(--mg-theme);">
                        <i class="fa-solid fa-plus mr-1"></i>チャット形式で追加
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($reports as $ri => $report):
                        $rId = (int)$report['id'];
                        $msgs = $messagesMap[$rId] ?? [];
                    ?>
                    <div class="report-card bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm" data-report-id="<?= $rId ?>">
                        <!-- レポヘッダー -->
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600">レポ <?= $ri + 1 ?></span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background: color-mix(in srgb, var(--mg-theme) 10%, white); color: var(--mg-theme);">
                                    <span class="ticket-used-value"><?= (int)$report['ticket_used'] ?></span>枚使用
                                </span>
                                <span class="report-nickname text-[10px] text-slate-400 <?= $report['my_nickname'] ? '' : 'hidden' ?>" data-nickname="<?= htmlspecialchars($report['my_nickname'] ?? '') ?>">
                                    <?= $report['my_nickname'] ? '表示名: ' . htmlspecialchars($report['my_nickname']) : '' ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button onclick="MGR.openHcMode(<?= $rId ?>)" class="text-slate-300 hover:text-slate-500 p-1 transition capture-hide" title="表示モード"><i class="fa-solid fa-expand text-xs"></i></button>
                                <button onclick="MGR.editReportMeta(<?= $rId ?>)" class="text-slate-300 hover:text-slate-500 p-1 transition capture-hide" title="設定"><i class="fa-solid fa-gear text-xs"></i></button>
                                <button onclick="MGR.deleteReport(<?= $rId ?>)" class="text-slate-300 hover:text-red-400 p-1 transition capture-hide" title="削除"><i class="fa-solid fa-trash-can text-xs"></i></button>
                            </div>
                        </div>
                        <!-- チャットエリア -->
                        <div class="chat-area px-4 py-3 space-y-3" id="chat-<?= $rId ?>">
                            <?php if (empty($msgs)): ?>
                            <p class="text-center text-slate-300 text-xs py-4">メッセージを追加してください</p>
                            <?php else: ?>
                                <?php foreach ($msgs as $msg): ?>
                                <?= renderMessage($msg, $memberName, $memberImage, $memberColor, $report['my_nickname']) ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <!-- 入力エリア -->
                        <div class="border-t border-slate-100 px-4 py-3 input-area">
                            <div class="flex items-center gap-1 mb-2">
                                <button class="sender-btn text-[9px] px-2 py-1 rounded-full border active" data-report="<?= $rId ?>" data-type="member" onclick="MGR.setSender(<?= $rId ?>,'member')" style="border-color: var(--member-color); color: var(--member-color); background: color-mix(in srgb, var(--member-color) 10%, white);">
                                    <?= htmlspecialchars(mb_substr($memberName, 0, 4)) ?>
                                </button>
                                <button class="sender-btn text-[9px] px-2 py-1 rounded-full border border-slate-200 text-slate-400" data-report="<?= $rId ?>" data-type="self" onclick="MGR.setSender(<?= $rId ?>,'self')">自分</button>
                                <button class="sender-btn text-[9px] px-2 py-1 rounded-full border border-slate-200 text-slate-400" data-report="<?= $rId ?>" data-type="self_thought" onclick="MGR.setSender(<?= $rId ?>,'self_thought')">内心</button>
                                <button class="sender-btn text-[9px] px-2 py-1 rounded-full border border-slate-200 text-slate-400" data-report="<?= $rId ?>" data-type="narration" onclick="MGR.setSender(<?= $rId ?>,'narration')">ナレ</button>
                                <button id="insertToggle-<?= $rId ?>" onclick="MGR.toggleInsertMode(<?= $rId ?>)" class="ml-auto text-[10px] px-2.5 py-1 rounded-full border border-slate-200 text-slate-400 hover:text-slate-600 transition capture-hide" title="途中に挿入">
                                    <i class="fa-solid fa-arrow-down-short-wide mr-0.5"></i>挿入
                                </button>
                            </div>
                            <div class="flex items-end gap-2">
                                <textarea id="input-<?= $rId ?>" rows="1" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 resize-none" style="--tw-ring-color: var(--mg-theme);" placeholder="メッセージを入力..." onkeydown="MGR.onInputKey(event,<?= $rId ?>)" oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px';"></textarea>
                                <button onclick="MGR.sendMessage(<?= $rId ?>)" class="w-9 h-9 rounded-full flex items-center justify-center text-white shrink-0 transition active:scale-90" style="background: var(--mg-theme);">
                                    <i class="fa-solid fa-paper-plane text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- レポメタ編集モーダル -->
    <div id="metaModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="MGR.closeMetaModal()"></div>
        <div class="relative z-10 flex items-center justify-center min-h-full p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-700 text-sm">レポ設定</h2>
                    <button onclick="MGR.closeMetaModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <input type="hidden" id="metaReportId">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">使用枚数</label>
                        <input type="number" id="metaTicketUsed" min="1" value="1" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">ニックネーム</label>
                        <input type="text" id="metaNickname" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);" placeholder="表示名を入力">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="metaSaveDefault" class="w-4 h-4 rounded" style="accent-color: var(--mg-theme);">
                        <span class="text-xs text-slate-500">このニックネームをデフォルトとして保存</span>
                    </label>
                    <button onclick="MGR.saveMetaModal()" class="w-full h-10 rounded-lg font-bold text-white text-sm transition active:scale-95" style="background: var(--mg-theme);">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- レポ新規作成モーダル -->
    <div id="addModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="MGR.closeAddModal()"></div>
        <div class="relative z-10 flex items-center justify-center min-h-full p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-700 text-sm">新しいレポを追加</h2>
                    <button onclick="MGR.closeAddModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">使用枚数</label>
                        <input type="number" id="addTicketUsed" min="1" value="1" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">ニックネーム</label>
                        <input type="text" id="addNickname" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);" placeholder="表示名を入力">
                    </div>
                    <button onclick="MGR.submitAddReport()" class="w-full h-10 rounded-lg font-bold text-white text-sm transition active:scale-95" style="background: var(--mg-theme);">
                        <i class="fa-solid fa-plus mr-1"></i>作成する
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- メッセージ編集モーダル -->
    <div id="editMsgModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="MGR.closeEditMsg()"></div>
        <div class="relative z-10 flex items-center justify-center min-h-full p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-700 text-sm">メッセージ編集</h2>
                    <button onclick="MGR.closeEditMsg()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <input type="hidden" id="editMsgReportId">
                    <input type="hidden" id="editMsgIndex">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">送信者</label>
                        <select id="editMsgSender" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2" style="--tw-ring-color: var(--mg-theme);">
                            <option value="member"><?= htmlspecialchars($memberName) ?></option>
                            <option value="self">自分</option>
                            <option value="self_thought">内心</option>
                            <option value="narration">ナレーション</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 tracking-wider mb-1">内容</label>
                        <textarea id="editMsgContent" rows="3" class="w-full border border-slate-200 rounded-lg p-3 text-sm outline-none focus:ring-2 resize-y" style="--tw-ring-color: var(--mg-theme);"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="MGR.deleteMessage()" class="flex-1 h-10 rounded-lg font-bold text-red-500 text-sm border border-red-200 hover:bg-red-50 transition">
                            <i class="fa-solid fa-trash-can mr-1"></i>削除
                        </button>
                        <button onclick="MGR.saveEditMsg()" class="flex-1 h-10 rounded-lg font-bold text-white text-sm transition active:scale-95" style="background: var(--mg-theme);">保存</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
    const SLOT_ID = <?= (int)$slot['id'] ?>;
    const MEMBER_ID = <?= (int)($slot['member_id'] ?? 0) ?>;
    const MEMBER_NAME = <?= json_encode($memberName, JSON_UNESCAPED_UNICODE) ?>;
    let MEMBER_IMAGE = <?= json_encode($memberImage, JSON_UNESCAPED_UNICODE) ?>;
    const MEMBER_COLOR = <?= json_encode($memberColor, JSON_UNESCAPED_UNICODE) ?>;

    const MGR = {
        senderState: {},
        reportMessages: {},
        insertPosition: {},
        insertMode: {},

        // === アバター変更 ===
        changeAvatar() {
            document.getElementById('avatarFileInput').click();
        },

        async uploadAvatar(input) {
            const file = input.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('avatar', file);
            formData.append('member_id', MEMBER_ID);

            try {
                const resp = await fetch('/hinata/api/meetgreet_avatar_upload.php', {
                    method: 'POST',
                    body: formData,
                });
                const res = await resp.json();
                if (res.status === 'success' && res.image_path) {
                    MEMBER_IMAGE = res.image_path;
                    const headerAvatar = document.getElementById('headerAvatar');
                    if (headerAvatar.tagName === 'IMG') {
                        headerAvatar.src = res.image_path;
                    } else {
                        const img = document.createElement('img');
                        img.id = 'headerAvatar';
                        img.src = res.image_path;
                        img.alt = '';
                        img.className = 'w-8 h-8 rounded-full object-cover border-2';
                        img.style.borderColor = 'var(--member-color)';
                        headerAvatar.replaceWith(img);
                    }
                    Object.keys(this.reportMessages).forEach(rid => this.renderChat(parseInt(rid)));
                    App.toast('アバターを更新しました');
                } else {
                    App.toast(res.message || 'アップロードに失敗しました');
                }
            } catch (e) {
                App.toast('アップロードに失敗しました');
            }
            input.value = '';
        },

        // === メモ欄 ===
        toggleMemo() {
            const area = document.getElementById('memoArea');
            const icon = document.getElementById('memoChevron');
            area.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        },

        async saveMemo() {
            const text = document.getElementById('memoText').value;
            const res = await App.post('/hinata/api/meetgreet_save_report.php', {
                id: SLOT_ID,
                report: text,
            });
            if (res.status === 'success') {
                App.toast('メモを保存しました');
            } else {
                App.toast('保存に失敗しました');
            }
        },

        init() {
            <?php foreach ($reports as $report):
                $rId = (int)$report['id'];
                $msgs = $messagesMap[$rId] ?? [];
            ?>
            this.senderState[<?= $rId ?>] = 'member';
            this.reportMessages[<?= $rId ?>] = <?= json_encode(array_map(fn($m) => [
                'sender_type' => $m['sender_type'],
                'content'     => $m['content'],
                'sort_order'  => (int)$m['sort_order'],
            ], $msgs), JSON_UNESCAPED_UNICODE) ?>;
            <?php endforeach; ?>
        },

        // === 挿入モード ===
        toggleInsertMode(reportId) {
            this.insertMode[reportId] = !this.insertMode[reportId];
            const chatEl = document.getElementById('chat-' + reportId);
            const btn = document.getElementById('insertToggle-' + reportId);
            if (this.insertMode[reportId]) {
                chatEl?.classList.add('insert-mode');
                if (btn) { btn.style.borderColor = 'var(--mg-theme)'; btn.style.color = 'var(--mg-theme)'; btn.style.background = 'color-mix(in srgb, var(--mg-theme) 10%, white)'; }
            } else {
                chatEl?.classList.remove('insert-mode');
                if (btn) { btn.style.borderColor = ''; btn.style.color = ''; btn.style.background = ''; }
                this.clearInsertPosition(reportId);
                this.renderChat(reportId);
            }
        },

        // === HC表示モード ===
        openHcMode(reportId) {
            const overlay = document.getElementById('hcOverlay');
            if (!overlay) return;

            const msgs = this.reportMessages[reportId] || [];
            const card = document.querySelector(`[data-report-id="${reportId}"]`);
            const nickname = card?.querySelector('[data-nickname]')?.dataset.nickname || this._getReportNickname(reportId);
            const ticketEl = card?.querySelector('.ticket-used-value');
            const ticketUsed = ticketEl ? ticketEl.textContent : '1';
            const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

            const avatarHtml = MEMBER_IMAGE
                ? `<img src="${esc(MEMBER_IMAGE)}" class="w-10 h-10 rounded-full object-cover border-2 shrink-0" style="border-color: ${MEMBER_COLOR};">`
                : `<div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0" style="background:${MEMBER_COLOR};">${esc(MEMBER_NAME.charAt(0))}</div>`;

            let chatHtml = '';
            msgs.forEach(msg => {
                const content = esc(msg.content).replace(/\n/g, '<br>');
                if (msg.sender_type === 'member') {
                    const av = MEMBER_IMAGE
                        ? `<img src="${esc(MEMBER_IMAGE)}" class="w-8 h-8 rounded-full object-cover shrink-0 border" style="border-color: ${MEMBER_COLOR};">`
                        : `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0" style="background:${MEMBER_COLOR};">${esc(MEMBER_NAME.charAt(0))}</div>`;
                    chatHtml += `<div class="flex items-start gap-2">${av}<div class="max-w-[75%]"><div class="bubble-member px-3 py-2 text-sm leading-relaxed text-slate-700">${content}</div></div></div>`;
                } else if (msg.sender_type === 'self') {
                    const nickHtml = nickname ? `<span class="block text-right text-[10px] text-slate-400 mb-0.5">${esc(nickname)}</span>` : '';
                    chatHtml += `<div class="flex items-start gap-2 justify-end"><div class="max-w-[75%]">${nickHtml}<div class="bubble-self px-3 py-2 text-sm leading-relaxed">${content}</div></div></div>`;
                } else if (msg.sender_type === 'self_thought') {
                    chatHtml += `<div class="flex items-start gap-2 justify-end"><div class="max-w-[75%]"><div class="bubble-self-thought px-3 py-2 text-sm leading-relaxed">${content}</div></div></div>`;
                } else {
                    chatHtml += `<div class="flex justify-center"><div class="bubble-narration px-4 py-1.5 text-xs text-center">${content}</div></div>`;
                }
            });

            overlay.innerHTML = `
                <div class="hc-overlay-header">
                    ${avatarHtml}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">${esc(MEMBER_NAME)}</p>
                        <p class="text-[10px] text-slate-400">${esc(document.querySelector('header .text-\\[10px\\]')?.textContent?.trim() || '')} &middot; ${ticketUsed}枚使用</p>
                    </div>
                    <button onclick="MGR.closeHcMode()" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 transition shrink-0" title="閉じる">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
                <div class="hc-overlay-body">
                    <div class="space-y-3">${chatHtml || '<p class="text-center text-slate-300 text-xs py-8">メッセージがありません</p>'}</div>
                </div>`;
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        },

        closeHcMode() {
            const overlay = document.getElementById('hcOverlay');
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.innerHTML = '';
            }
            document.body.style.overflow = '';
        },

        // === 送信者切り替え ===
        setSender(reportId, type) {
            this.senderState[reportId] = type;
            const btns = document.querySelectorAll(`.sender-btn[data-report="${reportId}"]`);
            btns.forEach(btn => {
                const isActive = btn.dataset.type === type;
                btn.classList.toggle('active', isActive);
                const t = btn.dataset.type;
                if (t === 'member') {
                    btn.style.borderColor = isActive ? MEMBER_COLOR : '#e2e8f0';
                    btn.style.color = isActive ? MEMBER_COLOR : '#94a3b8';
                    btn.style.background = isActive ? `color-mix(in srgb, ${MEMBER_COLOR} 10%, white)` : '';
                } else if (t === 'self' || t === 'self_thought') {
                    btn.style.borderColor = isActive ? 'var(--mg-theme)' : '#e2e8f0';
                    btn.style.color = isActive ? 'var(--mg-theme)' : '#94a3b8';
                    btn.style.background = isActive ? 'color-mix(in srgb, var(--mg-theme) 10%, white)' : '';
                } else {
                    btn.style.borderColor = isActive ? '#64748b' : '#e2e8f0';
                    btn.style.color = isActive ? '#64748b' : '#94a3b8';
                    btn.style.background = isActive ? '#f1f5f9' : '';
                }
            });
        },

        // === メッセージ送信 ===
        onInputKey(e, reportId) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage(reportId);
            }
        },

        async sendMessage(reportId) {
            const input = document.getElementById('input-' + reportId);
            const text = input.value.trim();
            if (!text) return;

            const senderType = this.senderState[reportId] || 'self';
            if (!this.reportMessages[reportId]) this.reportMessages[reportId] = [];

            const msgs = this.reportMessages[reportId];
            const newMsg = { sender_type: senderType, content: text, sort_order: 0 };

            const insertAfter = this.insertPosition[reportId];
            if (insertAfter !== undefined && insertAfter !== null) {
                msgs.splice(insertAfter + 1, 0, newMsg);
                msgs.forEach((m, i) => m.sort_order = i);
                this.clearInsertPosition(reportId);
                if (this.insertMode[reportId]) this.toggleInsertMode(reportId);
            } else {
                newMsg.sort_order = msgs.length;
                msgs.push(newMsg);
            }

            input.value = '';
            input.style.height = 'auto';

            this.renderChat(reportId);
            await this.saveMessages(reportId);
        },

        setInsertPosition(reportId, afterIdx) {
            this.insertPosition[reportId] = afterIdx;
            this.renderChat(reportId);
            this._updateInsertIndicator(reportId);
            const input = document.getElementById('input-' + reportId);
            if (input) input.focus();
        },

        clearInsertPosition(reportId) {
            delete this.insertPosition[reportId];
            this._updateInsertIndicator(reportId);
        },

        _updateInsertIndicator(reportId) {
            const card = document.querySelector(`[data-report-id="${reportId}"]`);
            if (!card) return;
            const existing = card.querySelector('.insert-position-indicator');
            if (existing) existing.remove();

            const insertAfter = this.insertPosition[reportId];
            if (insertAfter === undefined || insertAfter === null) return;

            const inputArea = card.querySelector('.input-area');
            if (!inputArea) return;

            const msgs = this.reportMessages[reportId] || [];
            const posLabel = insertAfter === -1 ? '先頭' : `${insertAfter + 1}番目の後`;

            const indicator = document.createElement('div');
            indicator.className = 'insert-position-indicator flex items-center justify-between px-4 py-1.5 text-[10px] insert-indicator';
            indicator.style.cssText = 'background: color-mix(in srgb, var(--mg-theme) 8%, white); color: var(--mg-theme);';
            indicator.innerHTML = `<span><i class="fa-solid fa-arrow-turn-down mr-1"></i>${posLabel}に挿入します</span><button onclick="MGR.clearInsertPosition(${reportId}); this.parentElement.remove();" class="font-bold hover:opacity-70"><i class="fa-solid fa-xmark mr-0.5"></i>解除</button>`;
            inputArea.insertBefore(indicator, inputArea.firstChild);
        },

        // === メッセージ保存 ===
        async saveMessages(reportId) {
            const msgs = this.reportMessages[reportId] || [];
            const payload = msgs.map((m, i) => ({
                sender_type: m.sender_type,
                content: m.content,
                sort_order: i,
            }));
            await App.post('/hinata/api/meetgreet_report_messages_save.php', {
                report_id: reportId,
                messages: payload,
            });
        },

        // === チャット描画 ===
        renderChat(reportId) {
            const container = document.getElementById('chat-' + reportId);
            const msgs = this.reportMessages[reportId] || [];
            if (msgs.length === 0) {
                container.innerHTML = '<p class="text-center text-slate-300 text-xs py-4">メッセージを追加してください</p>';
                return;
            }

            const card = container.closest('.report-card');
            const nickname = card?.querySelector('[data-nickname]')?.dataset.nickname || this._getReportNickname(reportId);
            const activeInsert = this.insertPosition[reportId];

            let html = this._renderInsertDivider(reportId, -1, activeInsert === -1);
            msgs.forEach((msg, idx) => {
                html += this._renderBubble(msg, idx, reportId, nickname);
                html += this._renderInsertDivider(reportId, idx, activeInsert === idx);
            });
            container.innerHTML = html;
        },

        _renderInsertDivider(reportId, afterIdx, isActive) {
            const cls = isActive ? 'insert-divider active' : 'insert-divider';
            return `<div class="${cls}" data-insert-after="${afterIdx}">
                <div class="insert-line"></div>
                <button class="insert-btn w-6 h-6 rounded-full flex items-center justify-center text-white text-[9px] relative z-[1]" style="background: var(--mg-theme);"
                    onclick="event.stopPropagation(); MGR.setInsertPosition(${reportId}, ${afterIdx})"
                    title="ここに挿入">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>`;
        },

        _getReportNickname(reportId) {
            const card = document.querySelector(`[data-report-id="${reportId}"]`);
            const nicknameEl = card?.querySelector('.report-nickname');
            return nicknameEl?.textContent || localStorage.getItem('mg_default_nickname') || '';
        },

        _renderBubble(msg, idx, reportId, nickname) {
            const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
            const content = esc(msg.content).replace(/\n/g, '<br>');

            if (msg.sender_type === 'member') {
                const avatarHtml = MEMBER_IMAGE
                    ? `<img src="${esc(MEMBER_IMAGE)}" class="w-8 h-8 rounded-full object-cover shrink-0 border" style="border-color: ${MEMBER_COLOR};">`
                    : `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0" style="background:${MEMBER_COLOR};">${esc(MEMBER_NAME.charAt(0))}</div>`;
                return `<div class="msg-row flex items-start gap-2 cursor-pointer" onclick="MGR.openEditMsg(${reportId},${idx})">
                    ${avatarHtml}
                    <div class="max-w-[75%]">
                        <div class="bubble-member px-3 py-2 text-sm leading-relaxed text-slate-700">${content}</div>
                    </div>
                    <div class="msg-actions self-center"><i class="fa-solid fa-pen text-[10px] text-slate-300"></i></div>
                </div>`;
            }
            if (msg.sender_type === 'self') {
                const nickHtml = nickname ? `<span class="block text-right text-[10px] text-slate-400 mb-0.5">${esc(nickname)}</span>` : '';
                return `<div class="msg-row flex items-start gap-2 justify-end cursor-pointer" onclick="MGR.openEditMsg(${reportId},${idx})">
                    <div class="msg-actions self-center"><i class="fa-solid fa-pen text-[10px] text-slate-300"></i></div>
                    <div class="max-w-[75%]">${nickHtml}<div class="bubble-self px-3 py-2 text-sm leading-relaxed">${content}</div></div>
                </div>`;
            }
            if (msg.sender_type === 'self_thought') {
                return `<div class="msg-row flex items-start gap-2 justify-end cursor-pointer" onclick="MGR.openEditMsg(${reportId},${idx})">
                    <div class="msg-actions self-center"><i class="fa-solid fa-pen text-[10px] text-slate-300"></i></div>
                    <div class="max-w-[75%]"><div class="bubble-self-thought px-3 py-2 text-sm leading-relaxed">${content}</div></div>
                </div>`;
            }
            // narration
            return `<div class="msg-row flex justify-center cursor-pointer" onclick="MGR.openEditMsg(${reportId},${idx})">
                <div class="bubble-narration px-4 py-1.5 text-xs text-center">${content}</div>
            </div>`;
        },

        // === メッセージ編集 ===
        openEditMsg(reportId, idx) {
            const msgs = this.reportMessages[reportId] || [];
            const msg = msgs[idx];
            if (!msg) return;
            document.getElementById('editMsgReportId').value = reportId;
            document.getElementById('editMsgIndex').value = idx;
            document.getElementById('editMsgSender').value = msg.sender_type;
            document.getElementById('editMsgContent').value = msg.content;
            document.getElementById('editMsgModal').classList.remove('hidden');
        },

        closeEditMsg() {
            document.getElementById('editMsgModal').classList.add('hidden');
        },

        async saveEditMsg() {
            const reportId = parseInt(document.getElementById('editMsgReportId').value);
            const idx = parseInt(document.getElementById('editMsgIndex').value);
            const senderType = document.getElementById('editMsgSender').value;
            const content = document.getElementById('editMsgContent').value.trim();
            if (!content) { App.toast('内容を入力してください'); return; }

            const msgs = this.reportMessages[reportId];
            if (!msgs || !msgs[idx]) return;
            msgs[idx].sender_type = senderType;
            msgs[idx].content = content;

            this.closeEditMsg();
            this.renderChat(reportId);
            await this.saveMessages(reportId);
            App.toast('保存しました');
        },

        async deleteMessage() {
            const reportId = parseInt(document.getElementById('editMsgReportId').value);
            const idx = parseInt(document.getElementById('editMsgIndex').value);
            const msgs = this.reportMessages[reportId];
            if (!msgs) return;
            msgs.splice(idx, 1);
            msgs.forEach((m, i) => m.sort_order = i);

            this.closeEditMsg();
            this.renderChat(reportId);
            await this.saveMessages(reportId);
            App.toast('削除しました');
        },

        // === レポ追加 ===
        addReport() {
            const defaultNick = localStorage.getItem('mg_default_nickname') || '';
            document.getElementById('addTicketUsed').value = 1;
            document.getElementById('addNickname').value = defaultNick;
            document.getElementById('addModal').classList.remove('hidden');
        },

        closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        },

        async submitAddReport() {
            const ticketUsed = parseInt(document.getElementById('addTicketUsed').value) || 1;
            const nickname = document.getElementById('addNickname').value.trim() || null;

            const res = await App.post('/hinata/api/meetgreet_report_create.php', {
                slot_id: SLOT_ID,
                ticket_used: ticketUsed,
                my_nickname: nickname,
            });

            if (res.status === 'success') {
                if (nickname) {
                    localStorage.setItem('mg_default_nickname', nickname);
                }
                App.toast('レポを作成しました');
                location.reload();
            } else {
                App.toast('作成に失敗しました');
            }
        },

        // === レポメタ編集 ===
        editReportMeta(reportId) {
            const card = document.querySelector(`[data-report-id="${reportId}"]`);
            const ticketEl = card?.querySelector('.ticket-used-value');
            const nickEl = card?.querySelector('.report-nickname');
            document.getElementById('metaReportId').value = reportId;
            document.getElementById('metaTicketUsed').value = ticketEl?.textContent || 1;
            document.getElementById('metaNickname').value = nickEl?.dataset.nickname || '';
            document.getElementById('metaSaveDefault').checked = false;
            document.getElementById('metaModal').classList.remove('hidden');
        },

        closeMetaModal() {
            document.getElementById('metaModal').classList.add('hidden');
        },

        async saveMetaModal() {
            const reportId = parseInt(document.getElementById('metaReportId').value);
            const ticketUsed = parseInt(document.getElementById('metaTicketUsed').value) || 1;
            const nickname = document.getElementById('metaNickname').value.trim() || null;
            const saveDefault = document.getElementById('metaSaveDefault').checked;

            const res = await App.post('/hinata/api/meetgreet_report_update.php', {
                id: reportId,
                ticket_used: ticketUsed,
                my_nickname: nickname,
            });

            if (res.status === 'success') {
                if (saveDefault && nickname) {
                    localStorage.setItem('mg_default_nickname', nickname);
                }
                App.toast('保存しました');
                location.reload();
            } else {
                App.toast('保存に失敗しました');
            }
        },

        // === レポ削除 ===
        async deleteReport(reportId) {
            if (!confirm('このレポを削除しますか？メッセージも全て削除されます。')) return;
            const res = await App.post('/hinata/api/meetgreet_report_delete.php', { id: reportId });
            if (res.status === 'success') {
                App.toast('削除しました');
                location.reload();
            } else {
                App.toast('削除に失敗しました');
            }
        },
    };

    MGR.init();
    Object.keys(MGR.reportMessages).forEach(rid => MGR.renderChat(parseInt(rid)));
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.add('mobile-open');
    });
    </script>

    <!-- HC全画面表示オーバーレイ -->
    <div id="hcOverlay" class="hc-overlay hidden"></div>
</body>
</html>
<?php
function renderMessage(array $msg, string $memberName, ?string $memberImage, string $memberColor, ?string $nickname): string {
    $content = nl2br(htmlspecialchars($msg['content']));
    $reportId = (int)$msg['report_id'];
    $idx = (int)$msg['sort_order'];

    if ($msg['sender_type'] === 'member') {
        $avatar = $memberImage
            ? '<img src="' . htmlspecialchars($memberImage) . '" class="w-8 h-8 rounded-full object-cover shrink-0 border" style="border-color: ' . htmlspecialchars($memberColor) . ';">'
            : '<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0" style="background:' . htmlspecialchars($memberColor) . ';">' . htmlspecialchars(mb_substr($memberName, 0, 1)) . '</div>';
        return <<<HTML
        <div class="msg-row flex items-start gap-2 cursor-pointer" onclick="MGR.openEditMsg({$reportId},{$idx})">
            {$avatar}
            <div class="max-w-[75%]"><div class="bubble-member px-3 py-2 text-sm leading-relaxed text-slate-700">{$content}</div></div>
            <div class="msg-actions self-center"><i class="fa-solid fa-pen text-[10px] text-slate-300"></i></div>
        </div>
        HTML;
    }

    if ($msg['sender_type'] === 'self') {
        $nickHtml = $nickname ? '<span class="block text-right text-[10px] text-slate-400 mb-0.5">' . htmlspecialchars($nickname) . '</span>' : '';
        return <<<HTML
        <div class="msg-row flex items-start gap-2 justify-end cursor-pointer" onclick="MGR.openEditMsg({$reportId},{$idx})">
            <div class="msg-actions self-center"><i class="fa-solid fa-pen text-[10px] text-slate-300"></i></div>
            <div class="max-w-[75%]">{$nickHtml}<div class="bubble-self px-3 py-2 text-sm leading-relaxed">{$content}</div></div>
        </div>
        HTML;
    }

    if ($msg['sender_type'] === 'self_thought') {
        return <<<HTML
        <div class="msg-row flex items-start gap-2 justify-end cursor-pointer" onclick="MGR.openEditMsg({$reportId},{$idx})">
            <div class="msg-actions self-center"><i class="fa-solid fa-pen text-[10px] text-slate-300"></i></div>
            <div class="max-w-[75%]"><div class="bubble-self-thought px-3 py-2 text-sm leading-relaxed">{$content}</div></div>
        </div>
        HTML;
    }

    // narration
    return <<<HTML
    <div class="msg-row flex justify-center cursor-pointer" onclick="MGR.openEditMsg({$reportId},{$idx})">
        <div class="bubble-narration px-4 py-1.5 text-xs text-center">{$content}</div>
    </div>
    HTML;
}
?>
