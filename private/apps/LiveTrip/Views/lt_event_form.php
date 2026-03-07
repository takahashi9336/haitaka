<?php
$appKey = 'live_trip';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$redirect = $_GET['redirect'] ?? '/live_trip/create.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>汎用イベントを登録 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --lt-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .lt-theme-btn { background-color: var(--lt-theme); }
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { width: 240px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 overflow-auto">
    <header class="h-16 bg-white border-b border-slate-200 flex items-center px-6 shrink-0">
        <a href="<?= htmlspecialchars($redirect) ?>" class="text-slate-500 hover:text-slate-700 mr-4"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="font-black text-slate-700">汎用イベントを登録</h1>
    </header>

    <div class="p-6 max-w-xl">
        <form method="post" action="/live_trip/lt_event_store.php" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-600 mb-2">イベント名 *</label>
                <input type="text" name="event_name" required class="w-full border border-slate-200 rounded-lg px-4 py-2" placeholder="例: Summer Sonic 2025">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-600 mb-2">日付 *</label>
                <input type="date" name="event_date" required class="w-full border border-slate-200 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-600 mb-2">会場</label>
                <input type="text" name="event_place" class="w-full border border-slate-200 rounded-lg px-4 py-2" placeholder="例:  ZOZOマリンスタジアム">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-600 mb-2">メモ</label>
                <textarea name="event_info" rows="3" class="w-full border border-slate-200 rounded-lg px-4 py-2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lt-theme-btn text-white px-6 py-2 rounded-lg font-bold">登録</button>
                <a href="<?= htmlspecialchars($redirect) ?>" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">キャンセル</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
