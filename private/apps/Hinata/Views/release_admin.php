<?php
/**
 * リリース管理画面 View（管理者専用）
 * 物理パス: haitaka/private/apps/Hinata/Views/release_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リリース管理 - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
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
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-compact-disc text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">リリース管理</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                ポータルへ戻る
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-6xl mx-auto">
                
                <!-- 新規登録ボタン -->
                <div class="mb-6">
                    <button id="btnNewRelease" class="h-11 px-6 <?= $btnBgClass ?> text-white font-bold text-sm rounded-full transition shadow-lg flex items-center gap-2"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-plus"></i>
                        新規リリース登録
                    </button>
                </div>

                <!-- リリース一覧 -->
                <section class="bg-white rounded-3xl border <?= $cardBorder ?> shadow-sm p-6 md:p-8">
                    <h2 class="text-lg font-black text-slate-800 mb-4">リリース一覧</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">種別</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">番号</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">タイトル</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">版・ジャケット</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">発売日</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($releases)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-slate-400 py-8">リリース情報がありません</td>
                                </tr>
                                <?php else: ?>
                                    <?php
                                    $typeBadgeClasses = [
                                        'single' => 'bg-sky-100 text-sky-700',
                                        'album' => 'bg-indigo-100 text-indigo-700',
                                        'digital' => 'bg-emerald-100 text-emerald-700',
                                        'ep' => 'bg-amber-100 text-amber-700',
                                        'best' => 'bg-violet-100 text-violet-700',
                                    ];
                                    $editionShort = ['type_a' => 'A', 'type_b' => 'B', 'type_c' => 'C', 'type_d' => 'D', 'normal' => '通常'];
                                    foreach ($releases as $rel):
                                        $typeKey = $rel['release_type'] ?? 'single';
                                        $badgeClass = $typeBadgeClasses[$typeKey] ?? 'bg-slate-100 text-slate-600';
                                        $editions = $editionsByRelease[$rel['id']] ?? [];
                                        $mainJacket = null;
                                        foreach ($editions as $ed) {
                                            if (($ed['edition'] ?? '') === 'type_a' && !empty($ed['jacket_image_url'])) {
                                                $mainJacket = $ed['jacket_image_url'];
                                                break;
                                            }
                                        }
                                        if (!$mainJacket && !empty($editions)) {
                                            $first = $editions[0];
                                            $mainJacket = $first['jacket_image_url'] ?? null;
                                        }
                                    ?>
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                        <td class="p-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold <?= $badgeClass ?>">
                                                <?= htmlspecialchars($releaseTypes[$rel['release_type']] ?? $rel['release_type']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-sm font-bold text-slate-600">
                                            <?= htmlspecialchars($rel['release_number'] ?? '-') ?>
                                        </td>
                                        <td class="p-3 text-sm font-bold text-slate-800">
                                            <?= htmlspecialchars($rel['title']) ?>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <?php if ($mainJacket): ?>
                                                <img src="<?= htmlspecialchars($mainJacket) ?>" alt="" class="w-10 h-10 rounded object-cover border border-slate-200" onerror="this.style.display='none'">
                                                <?php endif; ?>
                                                <?php foreach ($editions as $ed): ?>
                                                    <?php if (!empty($ed['jacket_image_url'])): ?>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600" title="<?= htmlspecialchars($editionLabels[$ed['edition']] ?? $ed['edition']) ?>">
                                                        <?= htmlspecialchars($editionShort[$ed['edition']] ?? $ed['edition']) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (empty($editions) || !$mainJacket): ?>
                                                <span class="text-slate-400 text-xs">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-3 text-sm text-slate-600">
                                            <?= !empty($rel['release_date']) ? \Core\Utils\DateUtil::format($rel['release_date'], 'Y/m/d') : '-' ?>
                                        </td>
                                        <td class="p-3">
                                            <button class="btn-edit text-xs font-bold mr-3 <?= $cardIconText ?> hover:opacity-80"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?> data-id="<?= $rel['id'] ?>">
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
                        <select name="release_type" id="f_release_type" required class="w-full h-11 border <?= $cardBorder ?> rounded-xl px-4 text-sm outline-none bg-slate-50">
                            <?php foreach ($releaseTypes as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2">番号</label>
                        <input type="text" name="release_number" id="f_release_number" placeholder="1st" class="w-full h-11 border <?= $cardBorder ?> rounded-xl px-4 text-sm outline-none bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">タイトル <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="f_title" required class="w-full h-11 border <?= $cardBorder ?> rounded-xl px-4 text-sm outline-none bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">よみがな</label>
                    <input type="text" name="title_kana" id="f_title_kana" class="w-full h-11 border <?= $cardBorder ?> rounded-xl px-4 text-sm outline-none bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">発売日</label>
                    <input type="date" name="release_date" id="f_release_date" class="w-full h-11 border <?= $cardBorder ?> rounded-xl px-4 text-sm outline-none bg-slate-50">
                </div>

                <!-- 版別情報（ジャケット画像） -->
                <div class="border-t border-slate-100 pt-4 mt-4">
                    <h3 class="text-sm font-black text-slate-700 mb-3">版別ジャケット画像</h3>
                    <p class="text-xs text-slate-500 mb-3">ファイルをアップロードするか、URLを直接入力。TYPE-A はメインジャケット（一覧表示）に使います。</p>
                    <div class="space-y-4">
                        <?php foreach ($editionLabels as $editionKey => $editionLabel): ?>
                        <div class="edition-row border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                            <label class="block text-xs font-bold text-slate-600 mb-2" for="edition_<?= htmlspecialchars($editionKey) ?>">
                                <?= htmlspecialchars($editionLabel) ?>
                                <?php if ($editionKey === 'type_a'): ?><span class="text-red-500">*</span><?php endif; ?>
                            </label>
                            <div class="flex gap-3 items-start flex-wrap">
                                <div class="edition-preview w-16 h-16 rounded-lg border border-slate-200 bg-white overflow-hidden shrink-0 flex items-center justify-center text-slate-300 text-xs">
                                    未設定
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="text" id="edition_<?= htmlspecialchars($editionKey) ?>" data-edition="<?= htmlspecialchars($editionKey) ?>"
                                        placeholder="URL またはアップロード後に自動入力" class="w-full h-10 border <?= $cardBorder ?> rounded-lg px-3 text-sm outline-none bg-white edition-input mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="edition-file hidden" data-edition="<?= htmlspecialchars($editionKey) ?>">
                                        <button type="button" class="edition-upload-btn h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition" data-edition="<?= htmlspecialchars($editionKey) ?>">
                                            <i class="fa-solid fa-upload mr-1"></i>ファイルを選択
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">説明・備考</label>
                    <textarea name="description" id="f_description" rows="3" class="w-full border <?= $cardBorder ?> rounded-xl px-4 py-3 text-sm outline-none bg-slate-50"></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 h-11 <?= $btnBgClass ?> text-white font-bold text-sm rounded-full transition shadow-lg"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
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
            document.querySelectorAll('.edition-input').forEach((input) => updateEditionPreview(input.dataset.edition, ''));
            modalTitle.textContent = 'リリース登録';
            releaseModal.classList.remove('hidden');
        });

        // モーダルを閉じる
        [btnCloseModal, btnCancelModal].forEach(btn => {
            btn.addEventListener('click', () => {
                releaseModal.classList.add('hidden');
            });
        });

        // フォーム送信（editions を組み立てて JSON 送信）
        releaseForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(releaseForm);
            const data = Object.fromEntries(formData);

            // 版別ジャケットURLを配列で付与
            data.editions = [];
            document.querySelectorAll('.edition-input').forEach((input) => {
                const edition = input.dataset.edition;
                const url = (input.value || '').trim();
                data.editions.push({ edition, jacket_image_url: url || null });
            });

            // TYPE-A（メインジャケット）必須
            const typeA = data.editions.find((e) => e.edition === 'type_a');
            if (!typeA || !typeA.jacket_image_url) {
                alert('初回限定 TYPE-A のジャケット画像URLは必須です（メインジャケットに使用します）');
                document.getElementById('edition_type_a').focus();
                return;
            }

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

        // 版別プレビュー更新
        function updateEditionPreview(editionKey, url) {
            const input = document.getElementById('edition_' + editionKey);
            if (!input) return;
            const row = input.closest('.edition-row');
            const preview = row ? row.querySelector('.edition-preview') : null;
            if (!preview) return;
            if (url && url.trim()) {
                preview.innerHTML = '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML=\'未設定\'">';
            } else {
                preview.innerHTML = '未設定';
            }
        }

        // 版別URL入力変更でプレビュー更新
        document.querySelectorAll('.edition-input').forEach((input) => {
            input.addEventListener('input', () => updateEditionPreview(input.dataset.edition, input.value));
            input.addEventListener('change', () => updateEditionPreview(input.dataset.edition, input.value));
        });

        // 版別アップロード：ファイル選択ボタン
        document.querySelectorAll('.edition-upload-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const fileInput = document.querySelector('.edition-file[data-edition="' + btn.dataset.edition + '"]');
                if (fileInput) fileInput.click();
            });
        });

        // 版別アップロード：ファイル選択時
        document.querySelectorAll('.edition-file').forEach((fileInput) => {
            fileInput.addEventListener('change', async () => {
                if (!fileInput.files || !fileInput.files[0]) return;
                const editionKey = fileInput.dataset.edition;
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                try {
                    const res = await fetch('/hinata/api/upload_release_jacket.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    if (json.status === 'success' && json.url) {
                        const input = document.getElementById('edition_' + editionKey);
                        if (input) { input.value = json.url; updateEditionPreview(editionKey, json.url); }
                    } else {
                        alert('アップロードに失敗しました: ' + (json.message || ''));
                    }
                } catch (err) {
                    alert('アップロード通信エラー: ' + err.message);
                }
                fileInput.value = '';
            });
        });

        // 編集ボタン：詳細取得してフォームに反映
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', async () => {
                const releaseId = btn.dataset.id;
                try {
                    const res = await fetch(`/hinata/api/detail_release.php?id=${releaseId}`);
                    const json = await res.json();
                    if (json.status !== 'success' || !json.data) {
                        alert('取得に失敗しました: ' + (json.message || ''));
                        return;
                    }
                    const d = json.data;
                    document.getElementById('release_id').value = d.id || '';
                    document.getElementById('f_release_type').value = d.release_type || 'single';
                    document.getElementById('f_release_number').value = d.release_number || '';
                    document.getElementById('f_title').value = d.title || '';
                    document.getElementById('f_title_kana').value = d.title_kana || '';
                    document.getElementById('f_release_date').value = (d.release_date || '').slice(0, 10);
                    document.getElementById('f_description').value = d.description || '';
                    document.querySelectorAll('.edition-input').forEach((input) => { input.value = ''; updateEditionPreview(input.dataset.edition, ''); });
                    (d.editions || []).forEach((ed) => {
                        const el = document.getElementById('edition_' + ed.edition);
                        if (el) { el.value = ed.jacket_image_url || ''; updateEditionPreview(ed.edition, ed.jacket_image_url || ''); }
                    });
                    modalTitle.textContent = 'リリース編集';
                    releaseModal.classList.remove('hidden');
                } catch (err) {
                    alert('通信エラー: ' + err.message);
                }
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
