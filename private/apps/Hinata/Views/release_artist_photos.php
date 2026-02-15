<?php
/**
 * リリース別アーティスト写真登録 View（管理者専用）
 * 楽曲フォーメーション表示で参照する、リリースごとのメンバー写真を設定
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$releaseId = (int)$release['id'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アーティスト写真 - <?= htmlspecialchars($release['title']) ?> - Hinata Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex ?? '') ?>; }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= isset($bodyStyle) && $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $cardBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/release.php?id=<?= $releaseId ?>" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= isset($headerIconStyle) && $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-image text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight truncate max-w-[200px]">アーティスト写真</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-2xl mx-auto space-y-6">
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 tracking-wider">リリース</p>
                    <h2 class="text-xl font-black text-slate-800 mt-0.5"><?= htmlspecialchars($release['title']) ?></h2>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($releaseTypes[$release['release_type']] ?? $release['release_type']) ?></p>
                    <p class="text-xs text-slate-400 mt-2">このリリースの楽曲フォーメーション表示で使用するメンバー写真を設定します。未設定のメンバーはメンバー登録の画像が使われます。</p>
                </section>

                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h3 class="text-sm font-black text-slate-700 mb-3">メンバー別アーティスト写真</h3>
                    <p class="text-xs text-slate-500 mb-3">URLを直接入力するか、ファイルを選択してアップロードできます。未設定のメンバーはメンバー登録の画像が使われます。</p>
                    <form id="artistPhotosForm" class="space-y-3">
                        <?php foreach ($members as $m): ?>
                        <?php $mid = (int)$m['id']; $currentUrl = $imageMap[$mid] ?? ''; ?>
                        <div class="flex items-center gap-3 flex-wrap member-row" data-member-id="<?= $mid ?>">
                            <?php $avatarUrl = $currentUrl !== '' ? $currentUrl : ($m['image_url'] ?? ''); ?>
                            <div class="w-10 h-10 shrink-0 rounded-full bg-slate-100 overflow-hidden flex items-center justify-center member-default-avatar">
                                <?php if ($avatarUrl !== ''): ?>
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                <i class="fa-solid fa-user text-slate-400 text-sm"></i>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-[100px] shrink-0">
                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($m['name']) ?></span>
                            </div>
                            <div class="flex-1 min-w-0 flex flex-wrap items-center gap-2">
                                <input type="hidden" name="members[][member_id]" value="<?= $mid ?>">
                                <input type="text" name="members[][image_url]" value="<?= htmlspecialchars($currentUrl) ?>"
                                    placeholder="画像URL"
                                    class="member-image-url flex-1 min-w-[140px] h-10 border <?= $cardBorder ?> rounded-lg px-3 text-sm">
                                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden member-file-input" data-member-id="<?= $mid ?>">
                                <button type="button" class="member-upload-btn h-10 px-3 rounded-lg border <?= $cardBorder ?> bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition shrink-0">
                                    <i class="fa-solid fa-upload mr-1"></i>ファイルを選択
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </form>
                    <div class="mt-4 flex justify-end">
                        <button type="button" id="saveBtn" class="px-4 py-2 rounded-lg font-bold text-white <?= $cardIconBg ?> hover:opacity-90 transition"<?= isset($cardIconStyle) && $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-save mr-1"></i>保存</button>
                    </div>
                </section>

                <p class="text-center">
                    <a href="/hinata/release.php?id=<?= $releaseId ?>" class="text-sm font-bold <?= $cardIconText ?> inline-flex items-center justify-center gap-1 py-2 px-4 rounded-lg hover:opacity-90 transition"<?= isset($cardIconStyle) && $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-arrow-left mr-1"></i>リリース詳細へ戻る</a>
                </p>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        });

        document.getElementById('saveBtn').addEventListener('click', function () {
            const form = document.getElementById('artistPhotosForm');
            const members = [];
            const midInputs = form.querySelectorAll('input[name="members[][member_id]"]');
            const urlInputs = form.querySelectorAll('input[name="members[][image_url]"]');
            for (let i = 0; i < midInputs.length; i++) {
                members.push({
                    member_id: parseInt(midInputs[i].value, 10),
                    image_url: (urlInputs[i].value || '').trim()
                });
            }
            const btn = this;
            btn.disabled = true;
            fetch('/hinata/api/save_release_member_images.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ release_id: <?= $releaseId ?>, members: members })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || '保存しました');
                } else {
                    alert(data.message || '保存に失敗しました');
                }
            })
            .catch(() => alert('通信エラー'))
            .finally(() => { btn.disabled = false; });
        });

        document.querySelectorAll('.member-upload-btn').forEach((btn) => {
            const row = btn.closest('.member-row');
            const fileInput = row.querySelector('.member-file-input');
            const urlInput = row.querySelector('.member-image-url');
            const avatar = row.querySelector('.member-default-avatar');
            btn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', function () {
                if (!this.files || !this.files[0]) return;
                const formData = new FormData();
                formData.append('file', this.files[0]);
                btn.disabled = true;
                fetch('/hinata/api/upload_release_artist_photo.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then((data) => {
                        if (data.status === 'success' && data.url) {
                            urlInput.value = data.url;
                            const img = avatar.querySelector('img');
                            if (img) {
                                img.src = data.url;
                            } else {
                                const newImg = document.createElement('img');
                                newImg.src = data.url;
                                newImg.alt = '';
                                newImg.className = 'w-full h-full object-cover';
                                avatar.innerHTML = '';
                                avatar.appendChild(newImg);
                            }
                        } else {
                            alert(data.message || 'アップロードに失敗しました');
                        }
                    })
                    .catch(() => alert('アップロード通信エラー'))
                    .finally(() => { btn.disabled = false; this.value = ''; });
            });
        });
    </script>
</body>
</html>
