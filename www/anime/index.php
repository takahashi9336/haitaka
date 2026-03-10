<?php
/**
 * アニメ画面（プレースホルダー）
 * 物理パス: haitaka/www/anime/index.php
 * .env の ANIME_BETA_ID_NAMES に id_name が含まれるユーザーのみアクセス可能
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

// .env を読み込む（Database 利用前に必要）
Database::connect();

$auth = new Auth();
$auth->requireLogin();

$user = $_SESSION['user'];
$allowedIds = isset($_ENV['ANIME_BETA_ID_NAMES']) ? array_map('trim', explode(',', $_ENV['ANIME_BETA_ID_NAMES'])) : [];
if (empty($allowedIds) || !in_array($user['id_name'] ?? '', $allowedIds, true)) {
    header('HTTP/1.1 403 Forbidden');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403</title></head><body><p>アクセス権限がありません。</p><a href="/">ダッシュボードへ</a></body></html>';
    exit;
}

$appKey = 'dashboard';
require_once __DIR__ . '/../../private/components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アニメ - MyPlatform</title>
    <?php require_once __DIR__ . '/../../private/components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>">
    <?php require_once __DIR__ . '/../../private/components/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center px-6 shrink-0">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-500 text-white shadow-lg">
                <i class="fa-solid fa-tv text-sm"></i>
            </div>
            <h1 class="font-black text-slate-700 text-xl tracking-tighter ml-3">アニメ</h1>
        </header>
        <div class="flex-1 overflow-y-auto p-8 flex items-center justify-center">
            <div class="text-center text-slate-500">
                <i class="fa-solid fa-tv text-4xl mb-4 text-slate-300"></i>
                <p class="text-sm font-bold">Annict アニメ管理（準備中）</p>
                <p class="text-xs mt-2">sys_apps ・ロール登録後、本格展開予定</p>
                <a href="/" class="inline-block mt-6 px-4 py-2 bg-indigo-500 text-white text-sm font-bold rounded-lg hover:bg-indigo-600 transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i>ダッシュボードへ戻る
                </a>
            </div>
        </div>
    </main>
</body>
</html>
