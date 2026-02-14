<?php
/**
 * リリース管理画面 View（管理者専用）
 * 物理パス: haitaka/private/apps/Hinata/Views/release_admin.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リリース管理 - 日向坂ポータル</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-[#f0f9ff] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-sky-100 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white shadow-lg shadow-sky-200">
                    <i class="fa-solid fa-compact-disc text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">リリース管理</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold text-sky-500 bg-sky-50 px-4 py-2 rounded-full hover:bg-sky-100 transition">
                ポータルへ戻る
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-6xl mx-auto">
                
                <!-- 新規登録ボタン -->
                <div class="mb-6">
                    <button id="btnNewRelease" class="h-11 px-6 bg-sky-500 text-white font-bold text-sm rounded-full hover:bg-sky-600 transition shadow-lg shadow-sky-200 flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        新規リリース登録
                    </button>
                </div>

                <!-- リリース一覧 -->
                <section class="bg-white rounded-3xl border border-sky-100 shadow-sm p-6 md:p-8">
                    <h2 class="text-lg font-black text-slate-800 mb-4">リリース一覧</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">種別</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">番号</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">タイトル</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">発売日</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($releases)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-slate-400 py-8">リリース情報がありません</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($releases as $rel): ?>
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                        <td class="p-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-600">
                                                <?= htmlspecialchars($releaseTypes[$rel['release_type']] ?? $rel['release_type']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-sm font-bold text-slate-600">
                                            <?= htmlspecialchars($rel['release_number'] ?? '-') ?>
                                        </td>
                                        <td class="p-3 text-sm font-bold text-slate-800">
                                            <?= htmlspecialchars($rel['title']) ?>
                                        </td>
                                        <td class="p-3 text-sm text-slate-600">
                                            <?= !empty($rel['release_date']) ? \Core\Utils\DateUtil::format($rel['release_date'], 'Y/m/d') : '-' ?>
                                        </td>
                                        <td class="p-3">
                                            <button class="btn-edit text-sky-500 hover:text-sky-700 text-xs font-bold mr-3" data-id="<?= $rel['id'] ?>">
                                                <i class="fa-solid fa-edit"></i> 編集
                                            </button>
                                            <button class="btn-delete text-red-500 hover:text-red-700 text-xs font-bold" data-id="<?= $rel['id'] ?>">
                                                <i class="fa-solid fa-trash"></i> 削除
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <!-- モーダル：リリース登録・編集 -->
    <div id="releaseModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 id="modalTitle" class="text-xl font-black text-slate-800">リリース登録</h2>
                <button id="btnCloseModal" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <form id="releaseForm" class="space-y-4">
                <input type="hidden" id="release_id" name="id">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2">リリース種別</label>
                        <select name="release_type" id="f_release_type" required class="w-full h-11 border border-sky-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                            <?php foreach ($releaseTypes as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2">番号</label>
                        <input type="text" name="release_number" id="f_release_number" placeholder="1st" class="w-full h-11 border border-sky-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">タイトル <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="f_title" required class="w-full h-11 border border-sky-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">よみがな</label>
                    <input type="text" name="title_kana" id="f_title_kana" class="w-full h-11 border border-sky-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">発売日</label>
                    <input type="date" name="release_date" id="f_release_date" class="w-full h-11 border border-sky-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">説明・備考</label>
                    <textarea name="description" id="f_description" rows="3" class="w-full border border-sky-100 rounded-xl px-4 py-3 text-sm outline-none bg-slate-50"></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 h-11 bg-sky-500 text-white font-bold text-sm rounded-full hover:bg-sky-600 transition shadow-lg shadow-sky-200">
                        保存
                    </button>
                    <button type="button" id="btnCancelModal" class="h-11 px-6 bg-slate-100 text-slate-600 font-bold text-sm rounded-full hover:bg-slate-200 transition">
                        キャンセル
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const releaseModal = document.getElementById('releaseModal');
        const releaseForm = document.getElementById('releaseForm');
        const modalTitle = document.getElementById('modalTitle');
        const btnNewRelease = document.getElementById('btnNewRelease');
        const btnCloseModal = document.getElementById('btnCloseModal');
        const btnCancelModal = document.getElementById('btnCancelModal');

        // 新規登録モーダル
        btnNewRelease.addEventListener('click', () => {
            releaseForm.reset();
            document.getElementById('release_id').value = '';
            modalTitle.textContent = 'リリース登録';
            releaseModal.classList.remove('hidden');
        });

        // モーダルを閉じる
        [btnCloseModal, btnCancelModal].forEach(btn => {
            btn.addEventListener('click', () => {
                releaseModal.classList.add('hidden');
            });
        });

        // フォーム送信
        releaseForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(releaseForm);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('/hinata/api/save_release.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                });

                const result = await response.json();
                if (result.status === 'success') {
                    alert('保存しました');
                    location.reload();
                } else {
                    alert('エラー: ' + (result.message || '不明なエラー'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            }
        });

        // 編集ボタン
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', async () => {
                const releaseId = btn.dataset.id;
                // TODO: リリース詳細を取得して編集モーダルを開く
                alert('編集機能は次のフェーズで実装します');
            });
        });

        // 削除ボタン
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('本当に削除しますか？')) return;

                const releaseId = btn.dataset.id;
                try {
                    const response = await fetch('/hinata/api/delete_release.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: releaseId }),
                    });

                    const result = await response.json();
                    if (result.status === 'success') {
                        alert('削除しました');
                        location.reload();
                    } else {
                        alert('エラー: ' + (result.message || '不明なエラー'));
                    }
                } catch (error) {
                    alert('通信エラー: ' + error.message);
                }
            });
        });

        // モバイルメニュー
        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };
    </script>
</body>
</html>
