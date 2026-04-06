<?php
/**
 * ポータル情報管理 View（トピック・お知らせ・応募締め切りを1画面で管理）
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
use App\Hinata\Model\TopicModel;
use App\Hinata\Model\AnnouncementModel;
use App\Hinata\Model\EventApplicationModel;

$tab = $_GET['tab'] ?? 'topics';
if (!in_array($tab, ['topics', 'announcements', 'deadlines'], true)) $tab = 'topics';

$topicTypes = TopicModel::TOPIC_TYPES;
$announceTypes = AnnouncementModel::TYPES;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ポータル情報管理 - 日向坂ポータル</title>
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
        }
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex ?? '#6366f1') ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?? '' ?>"<?= !empty($bodyStyle) ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-16 bg-white border-b <?= $headerBorder ?? 'border-slate-200' ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-xl"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?? 'bg-indigo-600' ?>"<?= !empty($headerIconStyle) ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-newspaper text-sm"></i></div>
                <h1 class="font-black text-slate-700 tracking-tighter text-xl">ポータル情報管理</h1>
            </div>
            <a href="/hinata/" class="text-xs font-bold <?= $cardIconText ?? 'text-indigo-600' ?> <?= $cardIconBg ?? 'bg-indigo-50' ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= !empty($cardIconStyle) ? ' style="color:' . htmlspecialchars($themePrimary ?? '#6366f1') . ';"' : '' ?>>ポータル</a>
        </header>

        <!-- タブ -->
        <div class="border-b <?= $cardBorder ?? 'border-slate-200' ?> bg-white px-6">
            <div class="flex gap-1">
                <a href="?tab=topics" class="px-4 py-3 text-sm font-bold border-b-2 transition <?= $tab === 'topics' ? ($cardDeco ?? 'text-indigo-600 border-indigo-500') : 'text-slate-400 border-transparent hover:text-slate-600' ?>"<?= $tab === 'topics' && !empty($cardDecoStyle) ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>TOPICS</a>
                <a href="?tab=announcements" class="px-4 py-3 text-sm font-bold border-b-2 transition <?= $tab === 'announcements' ? ($cardDeco ?? 'text-indigo-600 border-indigo-500') : 'text-slate-400 border-transparent hover:text-slate-600' ?>">お知らせ</a>
                <a href="?tab=deadlines" class="px-4 py-3 text-sm font-bold border-b-2 transition <?= $tab === 'deadlines' ? ($cardDeco ?? 'text-indigo-600 border-indigo-500') : 'text-slate-400 border-transparent hover:text-slate-600' ?>">応募締め切り</a>
            </div>
        </div>

        <div class="p-4 md:p-12 max-w-5xl mx-auto w-full flex-1">
            <?php if ($tab === 'topics'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <section class="bg-white p-6 md:p-8 rounded-xl border <?= $cardBorder ?? 'border-slate-200' ?> shadow-sm">
                        <h2 class="text-lg font-bold mb-6 flex items-center gap-2"><i class="fa-solid fa-bullhorn <?= $cardIconText ?? '' ?>"></i> トピック登録</h2>
                        <form id="topicForm" class="space-y-4">
                            <input type="hidden" name="id" id="topic_id">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-1">タイトル</label>
                                <input type="text" name="title" id="t_title" required class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-1">概要</label>
                                <textarea name="summary" id="t_summary" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">URL</label>
                                    <input type="url" name="url" id="t_url" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">画像</label>
                                    <input type="hidden" name="image_url" id="t_image_url">
                                    <div class="flex items-center gap-3">
                                        <input type="file" id="t_image_file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                        <button type="button" id="t_image_upload_btn" class="h-11 px-4 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50">画像を選択</button>
                                        <button type="button" id="t_image_clear_btn" class="h-11 px-3 text-xs text-red-500 hover:text-red-700 hidden">削除</button>
                                        <span id="t_image_name" class="text-xs text-slate-500 truncate max-w-[120px]"></span>
                                    </div>
                                    <div id="t_image_preview" class="mt-2 w-20 h-20 rounded-lg overflow-hidden bg-slate-100 hidden"><img src="" alt="" class="w-full h-full object-cover"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">種別</label>
                                    <select name="topic_type" id="t_type" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm bg-slate-50 outline-none">
                                        <?php foreach ($topicTypes as $k => $v): ?><option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">表示開始日</label>
                                    <input type="date" name="start_date" id="t_start" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">表示終了日</label>
                                    <input type="date" name="end_date" id="t_end" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none">
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" id="t_active" value="1" checked> 有効</label>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">並び順</label>
                                    <input type="number" name="sort_order" id="t_sort" value="0" class="w-20 h-9 border border-slate-200 rounded-lg px-2 text-sm">
                                </div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-slate-800 text-white h-12 rounded-lg font-bold text-sm">保存</button>
                                <button type="button" id="topicBtnCancel" class="hidden px-4 text-red-500 font-bold text-sm">キャンセル</button>
                            </div>
                        </form>
                    </section>
                </div>
                <div>
                    <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?? 'border-slate-200' ?> shadow-sm">
                        <h3 class="text-xs font-black text-slate-400 mb-4">トピック一覧</h3>
                        <div id="topicList" class="space-y-2">
                            <?php foreach ($topics as $t): ?>
                            <div class="p-3 border border-slate-100 rounded-lg hover:bg-slate-50 cursor-pointer" data-id="<?= (int)$t['id'] ?>">
                                <p class="text-xs font-bold text-slate-600 truncate"><?= htmlspecialchars($t['title']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($topicTypes[$t['topic_type']] ?? $t['topic_type']) ?> <?= $t['start_date'] ?>〜<?= $t['end_date'] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
            <?php elseif ($tab === 'announcements'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <section class="bg-white p-6 md:p-8 rounded-xl border <?= $cardBorder ?? 'border-slate-200' ?> shadow-sm">
                        <h2 class="text-lg font-bold mb-6 flex items-center gap-2"><i class="fa-solid fa-bell <?= $cardIconText ?? '' ?>"></i> お知らせ登録</h2>
                        <form id="announceForm" class="space-y-4">
                            <input type="hidden" name="id" id="a_id">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-1">タイトル</label>
                                <input type="text" name="title" id="a_title" required class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-1">本文</label>
                                <textarea name="body" id="a_body" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">URL</label>
                                    <input type="url" name="url" id="a_url" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">画像</label>
                                    <input type="hidden" name="image_url" id="a_image_url">
                                    <div class="flex items-center gap-3">
                                        <input type="file" id="a_image_file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                        <button type="button" id="a_image_upload_btn" class="h-11 px-4 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50">画像を選択</button>
                                        <button type="button" id="a_image_clear_btn" class="h-11 px-3 text-xs text-red-500 hover:text-red-700 hidden">削除</button>
                                        <span id="a_image_name" class="text-xs text-slate-500 truncate max-w-[120px]"></span>
                                    </div>
                                    <div id="a_image_preview" class="mt-2 w-20 h-20 rounded-lg overflow-hidden bg-slate-100 hidden"><img src="" alt="" class="w-full h-full object-cover"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">種別</label>
                                    <select name="announcement_type" id="a_type" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm bg-slate-50 outline-none">
                                        <?php foreach ($announceTypes as $k => $v): ?><option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">公開日時</label>
                                    <input type="datetime-local" name="published_at" id="a_published" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">終了日時</label>
                                    <input type="datetime-local" name="expires_at" id="a_expires" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm outline-none">
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" id="a_active" value="1" checked> 有効</label>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1">並び順</label>
                                    <input type="number" name="sort_order" id="a_sort" value="0" class="w-20 h-9 border border-slate-200 rounded-lg px-2 text-sm">
                                </div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-slate-800 text-white h-12 rounded-lg font-bold text-sm">保存</button>
                                <button type="button" id="announceBtnCancel" class="hidden px-4 text-red-500 font-bold text-sm">キャンセル</button>
                            </div>
                        </form>
                    </section>
                </div>
                <div>
                    <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?? 'border-slate-200' ?> shadow-sm">
                        <h3 class="text-xs font-black text-slate-400 mb-4">お知らせ一覧</h3>
                        <div id="announceList" class="space-y-2">
                            <?php foreach ($announcements as $a): ?>
                            <div class="p-3 border border-slate-100 rounded-lg hover:bg-slate-50 cursor-pointer" data-id="<?= (int)$a['id'] ?>">
                                <p class="text-xs font-bold text-slate-600 truncate"><?= htmlspecialchars($a['title']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($announceTypes[$a['announcement_type']] ?? $a['announcement_type']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <section class="bg-white p-6 md:p-8 rounded-xl border <?= $cardBorder ?? 'border-slate-200' ?> shadow-sm">
                        <h2 class="text-lg font-bold mb-6 flex items-center gap-2"><i class="fa-solid fa-hourglass-end <?= $cardIconText ?? '' ?>"></i> 応募締め切り</h2>
                        <div class="mb-4">
                            <label class="block text-[10px] font-black text-slate-400 mb-1">対象イベント（ライブ・ミーグリ・リアルミーグリ）</label>
                            <select id="deadlineEventId" class="w-full h-11 border border-slate-200 rounded-lg px-3 text-sm bg-slate-50 outline-none">
                                <option value="">-- イベントを選択 --</option>
                                <?php foreach ($mgEvents as $e): ?>
                                <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['event_date'] . ' ' . $e['event_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="deadlineFormWrap" class="hidden">
                            <div class="text-xs text-slate-500 mb-3">ラウンドごとに締切を登録（行を追加して複数登録可能）</div>
                            <div id="deadlineRows" class="space-y-3 mb-4"></div>
                            <button type="button" id="addDeadlineRow" class="text-sm font-bold text-indigo-600 hover:text-indigo-700"><i class="fa-solid fa-plus mr-1"></i>行を追加</button>
                            <div class="flex gap-3 pt-4">
                                <button type="button" id="saveDeadlines" class="flex-1 bg-slate-800 text-white h-12 rounded-lg font-bold text-sm">保存</button>
                            </div>
                        </div>
                    </section>
                </div>
                <div>
                    <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?? 'border-slate-200' ?> shadow-sm">
                        <h3 class="text-xs font-black text-slate-400 mb-4">イベント一覧</h3>
                        <div id="deadlineEventList" class="space-y-2">
                            <?php foreach ($mgEvents as $e): ?>
                            <div class="p-3 border border-slate-100 rounded-lg hover:bg-slate-50 cursor-pointer deadline-event-item" data-id="<?= (int)$e['id'] ?>">
                                <p class="text-xs font-bold text-slate-600 truncate"><?= htmlspecialchars($e['event_name']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($e['event_date']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="/assets/js/core.js?v=3"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        <?php if ($tab === 'topics'): ?>
        const topicList = <?= json_encode($topics, JSON_UNESCAPED_UNICODE) ?>;
        document.querySelectorAll('#topicList [data-id]').forEach(el => {
            el.onclick = () => {
                const t = topicList.find(x => x.id == el.dataset.id);
                if (!t) return;
                document.getElementById('topic_id').value = t.id;
                document.getElementById('t_title').value = t.title || '';
                document.getElementById('t_summary').value = t.summary || '';
                document.getElementById('t_url').value = t.url || '';
                document.getElementById('t_image_url').value = t.image_url || '';
                const tPrev = document.getElementById('t_image_preview');
                const tImg = tPrev.querySelector('img');
                if (t.image_url) {
                    tImg.src = t.image_url.startsWith('/') || t.image_url.startsWith('http') ? t.image_url : '/assets/' + t.image_url;
                    tPrev.classList.remove('hidden');
                    document.getElementById('t_image_clear_btn').classList.remove('hidden');
                } else { tPrev.classList.add('hidden'); tImg.src = ''; document.getElementById('t_image_clear_btn').classList.add('hidden'); }
                document.getElementById('t_type').value = t.topic_type || 'other';
                document.getElementById('t_start').value = t.start_date || '';
                document.getElementById('t_end').value = t.end_date || '';
                document.getElementById('t_active').checked = t.is_active != 0;
                document.getElementById('t_sort').value = t.sort_order || 0;
                document.getElementById('topicBtnCancel').classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        });
        document.getElementById('topicBtnCancel').onclick = () => {
            document.getElementById('topic_id').value = '';
            document.getElementById('topicForm').reset();
            document.getElementById('t_image_url').value = '';
            document.getElementById('t_image_preview').classList.add('hidden');
            document.getElementById('t_image_preview').querySelector('img').src = '';
            document.getElementById('t_image_name').textContent = '';
            document.getElementById('t_image_clear_btn').classList.add('hidden');
            document.getElementById('topicBtnCancel').classList.add('hidden');
        };
        document.getElementById('t_image_clear_btn').onclick = () => {
            document.getElementById('t_image_url').value = '';
            document.getElementById('t_image_preview').classList.add('hidden');
            document.getElementById('t_image_preview').querySelector('img').src = '';
            document.getElementById('t_image_name').textContent = '';
            document.getElementById('t_image_clear_btn').classList.add('hidden');
        };
        document.getElementById('t_image_upload_btn').onclick = () => document.getElementById('t_image_file').click();
        document.getElementById('t_image_file').onchange = async function() {
            if (!this.files || !this.files[0]) return;
            const fd = new FormData();
            fd.append('file', this.files[0]);
            try {
                const res = await fetch('/hinata/api/upload_topic_image.php', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.status === 'success' && json.image_url) {
                    document.getElementById('t_image_url').value = json.image_url;
                    const prev = document.getElementById('t_image_preview');
                    prev.querySelector('img').src = '/assets/' + json.image_url;
                    prev.classList.remove('hidden');
                    document.getElementById('t_image_name').textContent = this.files[0].name;
                    document.getElementById('t_image_clear_btn').classList.remove('hidden');
                } else alert(json.message || 'アップロードに失敗しました');
            } catch (e) { alert('アップロード通信エラー: ' + e.message); }
            this.value = '';
        };
        document.getElementById('topicForm').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd.entries());
            data.is_active = document.getElementById('t_active').checked ? 1 : 0;
            const res = await App.post('/hinata/api/save_topic.php', data);
            if (res.status === 'success') location.reload(); else alert('エラー: ' + (res.message || ''));
        };
        <?php elseif ($tab === 'announcements'): ?>
        const announceList = <?= json_encode($announcements, JSON_UNESCAPED_UNICODE) ?>;
        document.querySelectorAll('#announceList [data-id]').forEach(el => {
            el.onclick = () => {
                const a = announceList.find(x => x.id == el.dataset.id);
                if (!a) return;
                document.getElementById('a_id').value = a.id;
                document.getElementById('a_title').value = a.title || '';
                document.getElementById('a_body').value = a.body || '';
                document.getElementById('a_url').value = a.url || '';
                document.getElementById('a_image_url').value = a.image_url || '';
                const aPrev = document.getElementById('a_image_preview');
                const aImg = aPrev.querySelector('img');
                if (a.image_url) {
                    aImg.src = a.image_url.startsWith('/') || a.image_url.startsWith('http') ? a.image_url : '/assets/' + a.image_url;
                    aPrev.classList.remove('hidden');
                    document.getElementById('a_image_clear_btn').classList.remove('hidden');
                } else { aPrev.classList.add('hidden'); aImg.src = ''; document.getElementById('a_image_clear_btn').classList.add('hidden'); }
                document.getElementById('a_type').value = a.announcement_type || 'other';
                document.getElementById('a_published').value = a.published_at ? a.published_at.slice(0, 16) : '';
                document.getElementById('a_expires').value = a.expires_at ? a.expires_at.slice(0, 16) : '';
                document.getElementById('a_active').checked = a.is_active != 0;
                document.getElementById('a_sort').value = a.sort_order || 0;
                const ac = document.getElementById('announceBtnCancel');
                if (ac) ac.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        });
        const announceBtnCancel = document.getElementById('announceBtnCancel');
        if (announceBtnCancel) announceBtnCancel.onclick = () => {
            document.getElementById('a_id').value = '';
            document.getElementById('announceForm').reset();
            document.getElementById('a_image_url').value = '';
            document.getElementById('a_image_preview').classList.add('hidden');
            document.getElementById('a_image_preview').querySelector('img').src = '';
            document.getElementById('a_image_name').textContent = '';
            document.getElementById('a_image_clear_btn').classList.add('hidden');
            announceBtnCancel.classList.add('hidden');
        };
        document.getElementById('a_image_clear_btn').onclick = () => {
            document.getElementById('a_image_url').value = '';
            document.getElementById('a_image_preview').classList.add('hidden');
            document.getElementById('a_image_preview').querySelector('img').src = '';
            document.getElementById('a_image_name').textContent = '';
            document.getElementById('a_image_clear_btn').classList.add('hidden');
        };
        document.getElementById('a_image_upload_btn').onclick = () => document.getElementById('a_image_file').click();
        document.getElementById('a_image_file').onchange = async function() {
            if (!this.files || !this.files[0]) return;
            const fd = new FormData();
            fd.append('file', this.files[0]);
            try {
                const res = await fetch('/hinata/api/upload_announcement_image.php', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.status === 'success' && json.image_url) {
                    document.getElementById('a_image_url').value = json.image_url;
                    const prev = document.getElementById('a_image_preview');
                    prev.querySelector('img').src = '/assets/' + json.image_url;
                    prev.classList.remove('hidden');
                    document.getElementById('a_image_name').textContent = this.files[0].name;
                    document.getElementById('a_image_clear_btn').classList.remove('hidden');
                } else alert(json.message || 'アップロードに失敗しました');
            } catch (e) { alert('アップロード通信エラー: ' + e.message); }
            this.value = '';
        };
        document.getElementById('announceForm').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd.entries());
            data.is_active = document.getElementById('a_active').checked ? 1 : 0;
            const res = await App.post('/hinata/api/save_announcement.php', data);
            if (res.status === 'success') location.reload(); else alert('エラー: ' + (res.message || ''));
        };
        <?php else: ?>
        let currentDeadlineEventId = null;

        function renderDeadlineRow(i, row) {
            return `<div class="deadline-row flex flex-wrap gap-2 items-end p-3 border border-slate-100 rounded-lg bg-slate-50/50" data-idx="${i}">
                <div class="flex-1 min-w-[100px]">
                    <label class="block text-[9px] font-bold text-slate-400">ラウンド名</label>
                    <input type="text" class="deadline-round w-full h-9 border rounded px-2 text-sm" placeholder="第1次" value="${(row && row.round_name) || ''}">
                </div>
                <div class="flex-1 min-w-[120px]">
                    <label class="block text-[9px] font-bold text-slate-400">応募開始</label>
                    <input type="datetime-local" class="deadline-start w-full h-9 border rounded px-2 text-sm" value="${(row && row.application_start) ? row.application_start.slice(0, 16) : ''}">
                </div>
                <div class="flex-1 min-w-[120px]">
                    <label class="block text-[9px] font-bold text-slate-400">締切日時 *</label>
                    <input type="datetime-local" class="deadline-deadline w-full h-9 border rounded px-2 text-sm" value="${(row && row.application_deadline) ? row.application_deadline.slice(0, 16) : ''}">
                </div>
                <div class="flex-1 min-w-[120px]">
                    <label class="block text-[9px] font-bold text-slate-400">当選発表</label>
                    <input type="datetime-local" class="deadline-announce w-full h-9 border rounded px-2 text-sm" value="${(row && row.announcement_date) ? row.announcement_date.slice(0, 16) : ''}">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[9px] font-bold text-slate-400">応募URL</label>
                    <input type="url" class="deadline-url w-full h-9 border rounded px-2 text-sm" value="${(row && row.application_url) || ''}">
                </div>
                <button type="button" class="remove-row h-9 w-9 text-red-400 hover:text-red-600 shrink-0"><i class="fa-solid fa-trash-can"></i></button>
            </div>`;
        }

        function loadDeadlines(eventId) {
            fetch('/hinata/api/event_applications.php?event_id=' + eventId)
                .then(r => r.json())
                .then(data => {
                    const rows = data.applications || [];
                    const cont = document.getElementById('deadlineRows');
                    cont.innerHTML = rows.length ? rows.map((r, i) => renderDeadlineRow(i, r)).join('') : renderDeadlineRow(0, null);
                    cont.querySelectorAll('.remove-row').forEach(btn => {
                        btn.onclick = () => {
                            const row = btn.closest('.deadline-row');
                            if (cont.querySelectorAll('.deadline-row').length <= 1) return;
                            row.remove();
                        };
                    });
                })
                .catch(() => { document.getElementById('deadlineRows').innerHTML = renderDeadlineRow(0, null); });
        }

        document.getElementById('deadlineEventId').onchange = function() {
            currentDeadlineEventId = this.value ? parseInt(this.value) : null;
            document.getElementById('deadlineFormWrap').classList.toggle('hidden', !currentDeadlineEventId);
            if (currentDeadlineEventId) loadDeadlines(currentDeadlineEventId);
        };

        document.getElementById('addDeadlineRow').onclick = () => {
            const cont = document.getElementById('deadlineRows');
            const div = document.createElement('div');
            div.innerHTML = renderDeadlineRow(cont.querySelectorAll('.deadline-row').length, {});
            cont.appendChild(div.firstElementChild);
            const last = cont.querySelector('.deadline-row:last-child');
            last.querySelector('.remove-row').onclick = () => {
                if (cont.querySelectorAll('.deadline-row').length <= 1) return;
                last.remove();
            };
        };

        document.getElementById('saveDeadlines').onclick = async () => {
            const rows = [];
            document.querySelectorAll('#deadlineRows .deadline-row').forEach(row => {
                const dl = row.querySelector('.deadline-deadline').value;
                if (!dl) return;
                rows.push({
                    round_name: row.querySelector('.deadline-round').value,
                    application_start: row.querySelector('.deadline-start').value || null,
                    application_deadline: dl,
                    announcement_date: row.querySelector('.deadline-announce').value || null,
                    application_url: row.querySelector('.deadline-url').value || null,
                });
            });
            const res = await App.post('/hinata/api/save_event_applications.php', { event_id: currentDeadlineEventId, rows });
            if (res.status === 'success') {
                loadDeadlines(currentDeadlineEventId);
                alert('保存しました');
            } else alert('エラー: ' + (res.message || ''));
        };

        document.querySelectorAll('.deadline-event-item').forEach(el => {
            el.onclick = () => {
                document.getElementById('deadlineEventId').value = el.dataset.id;
                document.getElementById('deadlineEventId').dispatchEvent(new Event('change'));
            };
        });
        <?php endif; ?>
    </script>
</body>
</html>
