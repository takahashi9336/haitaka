<?php
/**
 * 日向坂イベント管理 View (カテゴリ欄復活版)
 * 物理パス: haitaka/private/apps/Hinata/Views/event_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Admin - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 50; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-16 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-xl"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-calendar-plus text-sm"></i></div>
                <h1 class="font-black text-slate-700 tracking-tighter text-xl">イベント管理</h1>
            </div>
            <a href="/hinata/" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>ポータル</a>
        </header>

        <div class="p-4 md:p-12 max-w-5xl mx-auto w-full">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-6">
                    <section class="bg-white p-6 md:p-8 rounded-xl border <?= $cardBorder ?> shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-bold flex items-center gap-2 <?= $cardDeco ?>"><i class="fa-solid fa-calendar-plus <?= $cardIconText ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>></i> イベント登録</h2>
                            <button type="button" id="btnCancel" class="hidden text-xs text-red-400 font-bold">新規に戻る</button>
                        </div>

                        <form id="eventForm" class="space-y-6">
                            <input type="hidden" name="id" id="event_id">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">イベント名</label>
                                    <input type="text" name="event_name" id="f_name" required class="w-full h-12 border border-slate-100 rounded-lg px-4 text-sm outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-100' ?>">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">日付</label>
                                    <input type="date" name="event_date" id="f_date" required class="w-full h-12 border border-slate-100 rounded-lg px-4 text-sm bg-white outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- カテゴリ欄を復活 -->
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">カテゴリ</label>
                                    <select name="category" id="f_category" class="w-full h-12 border border-slate-100 rounded-lg px-4 text-sm bg-slate-50 outline-none">
                                        <option value="1">LIVE / ライブ</option>
                                        <option value="2">ミーグリ</option>
                                        <option value="3">リアルミーグリ</option>
                                        <option value="4">リリース</option>
                                        <option value="5">メディア</option>
                                        <option value="6">スペイベ</option>
                                        <option value="99">その他</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">会場・場所</label>
                                    <input type="text" name="event_place" id="f_place" class="w-full h-12 border border-slate-100 rounded-lg px-4 text-sm outline-none" placeholder="場所">
                                </div>
                            </div>

                            <textarea name="event_info" id="f_info" rows="3" class="w-full border rounded-lg p-4 text-sm outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-100' ?>" placeholder="詳細メモ"></textarea>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="url" name="event_url" id="f_url" class="w-full h-12 border border-slate-100 rounded-lg px-4 text-sm outline-none" placeholder="特設サイトURL">
                                <input type="url" name="youtube_url" id="f_youtube" class="w-full h-12 border border-slate-100 rounded-lg px-4 text-sm outline-none" placeholder="YouTube URL">
                            </div>

                            <div class="pt-2">
                                <label class="block text-[10px] font-black text-slate-400 mb-2 tracking-wider">出演メンバー</label>
                                <div class="flex gap-6 mb-3">
                                    <label class="flex items-center gap-1 cursor-pointer font-bold text-sm"><input type="radio" name="cast_type" value="group" checked onchange="toggleMemberSelect()"> 全員</label>
                                    <label class="flex items-center gap-1 cursor-pointer font-bold text-sm"><input type="radio" name="cast_type" value="individual" onchange="toggleMemberSelect()"> 個別</label>
                                </div>
                                <div id="memberSelectArea" class="hidden grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-4 border border-slate-100 rounded-xl bg-slate-50/50">
                                    <?php foreach ($members as $m): ?>
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-600"><input type="checkbox" name="member_ids[]" value="<?= $m['id'] ?>" class="w-4 h-4 rounded <?= $cardIconText ?>"<?= $isThemeHex ? ' style="accent-color: var(--hinata-theme)"' : '' ?>> <?= htmlspecialchars($m['name']) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <button type="submit" id="btnSubmit" class="flex-1 bg-slate-800 text-white h-14 rounded-lg font-black text-sm shadow-xl">保存する</button>
                                <button type="button" id="btnDelete" class="hidden w-14 h-14 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                        <h3 class="text-xs font-black text-slate-400 mb-4 tracking-wider">最近の編集</h3>
                        <div class="space-y-2">
                            <?php foreach ($events as $ev): ?>
                            <div onclick='editEvent(<?= json_encode($ev) ?>)' class="p-3 border border-slate-50 rounded-xl transition-all cursor-pointer <?= $isThemeHex ? 'hover:border-[var(--hinata-theme)] hover:bg-[var(--hinata-theme)]/10' : 'hover:border-' . $themeTailwind . '-200 hover:bg-' . $themeTailwind . '-50' ?>">
                                <p class="text-[9px] font-bold <?= $cardDeco ?>"><?= $ev['event_date'] ?></p>
                                <p class="text-xs font-black text-slate-700 truncate"><?= htmlspecialchars($ev['event_name']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        function toggleMemberSelect() {
            const isInd = document.querySelector('input[name="cast_type"]:checked').value === 'individual';
            document.getElementById('memberSelectArea').classList.toggle('hidden', !isInd);
        }
        function editEvent(ev) {
            document.getElementById('event_id').value = ev.id;
            document.getElementById('f_name').value = ev.event_name;
            document.getElementById('f_date').value = ev.event_date;
            document.getElementById('f_category').value = ev.category; // カテゴリ反映
            document.getElementById('f_place').value = ev.event_place || '';
            document.getElementById('f_info').value = ev.event_info || '';
            document.getElementById('f_url').value = ev.event_url || '';
            document.getElementById('f_youtube').value = '';
            document.getElementById('btnDelete').classList.remove('hidden');
            document.getElementById('btnCancel').classList.remove('hidden');
            document.getElementById('btnSubmit').innerText = '変更を保存';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        document.getElementById('btnCancel').onclick = () => location.reload();
        document.getElementById('eventForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.member_ids = Array.from(formData.getAll('member_ids[]'));
            const res = await App.post('api/save_event.php', data);
            if (res.status === 'success') location.reload(); else alert('エラー: ' + res.message);
        };
        document.getElementById('btnDelete').onclick = async () => {
            if (!confirm('削除しますか？')) return;
            const res = await App.post('api/delete_event.php', { id: document.getElementById('event_id').value });
            if (res.status === 'success') location.reload(); else alert('エラー: ' + res.message);
        }
    </script>
</body>
</html>