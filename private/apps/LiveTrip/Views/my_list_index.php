<?php
$appKey = 'live_trip';
$editId = (int) ($_GET['edit'] ?? 0);
$editingList = null;
if ($editId) {
    foreach ($lists as $l) {
        if ((int)$l['id'] === $editId) { $editingList = $l; break; }
    }
}
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>持ち物マイリスト - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --lt-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .lt-theme-btn { background-color: var(--lt-theme); }
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { width: 240px; }
        @media (max-width: 768px) { .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; } .sidebar.mobile-open { transform: translateX(0); } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 overflow-auto overflow-x-hidden w-full">
    <header class="h-14 sm:h-16 bg-white border-b border-slate-200 flex items-center px-4 sm:px-6 shrink-0">
        <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 mr-2"><i class="fa-solid fa-bars"></i></button>
        <a href="/live_trip/" class="text-slate-500 hover:text-slate-700 mr-2"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="font-black text-slate-700 text-lg truncate">持ち物マイリスト</h1>
    </header>

    <div class="p-4 sm:p-6 max-w-2xl w-full min-w-0 space-y-6 flex-1">
        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6">
            <h2 class="font-bold text-slate-700 mb-4">新規リスト作成</h2>
            <form method="post" action="/live_trip/my_list_store.php" class="flex flex-wrap gap-2">
                <input type="text" name="list_name" placeholder="例: 遠征基本セット" class="flex-1 min-w-0 border border-slate-200 rounded-lg px-4 py-2 text-sm" required>
                <button type="submit" class="lt-theme-btn text-white px-4 py-2 rounded-lg font-bold text-sm shrink-0">作成</button>
            </form>
        </div>

        <div class="space-y-4">
            <?php foreach ($lists as $list): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-4 overflow-hidden">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-slate-800"><?= htmlspecialchars($list['list_name']) ?></h3>
                    <form method="post" action="/live_trip/my_list_delete.php" class="inline" onsubmit="return confirm('このリストを削除しますか？');">
                        <input type="hidden" name="id" value="<?= (int)$list['id'] ?>">
                        <button type="submit" class="text-red-500 text-sm"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                </div>
                <?php if ($editingList && (int)$editingList['id'] === (int)$list['id']): ?>
                <form method="post" action="/live_trip/my_list_item_store.php" class="flex flex-wrap gap-2 mb-3">
                    <input type="hidden" name="my_list_id" value="<?= (int)$list['id'] ?>">
                    <input type="text" name="item_name" placeholder="項目を追加" class="flex-1 min-w-0 border border-slate-200 rounded px-3 py-2 text-sm" required>
                    <button type="submit" class="lt-theme-btn text-white px-3 py-2 rounded text-sm">追加</button>
                </form>
                <?php endif; ?>
                <ul class="space-y-1">
                    <?php foreach ($list['items'] ?? [] as $item): ?>
                    <li class="flex justify-between items-center gap-2 py-1 min-w-0">
                        <span class="min-w-0 truncate"><?= htmlspecialchars($item['item_name']) ?></span>
                        <?php if ($editingList && (int)$editingList['id'] === (int)$list['id']): ?>
                        <form method="post" action="/live_trip/my_list_item_delete.php" class="inline" onsubmit="return confirm('削除しますか？');">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="my_list_id" value="<?= (int)$list['id'] ?>">
                            <button type="submit" class="text-red-500 text-xs"><i class="fa-solid fa-times"></i></button>
                        </form>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!($editingList && (int)$editingList['id'] === (int)$list['id'])): ?>
                <a href="?edit=<?= (int)$list['id'] ?>" class="text-sm text-emerald-600 hover:underline mt-2 inline-block">編集</a>
                <?php else: ?>
                <a href="?" class="text-sm text-slate-500 hover:underline mt-2 inline-block">編集終了</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($lists)): ?>
        <p class="text-slate-500 text-center py-8 text-sm sm:text-base px-2">マイリストがありません。「遠征基本セット」など、よく使う持ち物リストを作成しておくと便利です。</p>
        <?php endif; ?>
    </div>
</main>
<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
    document.getElementById('sidebar')?.classList.toggle('mobile-open');
});
</script>
</body>
</html>
