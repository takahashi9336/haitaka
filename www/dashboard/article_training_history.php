<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

$auth = new Auth();
if (!$auth->check()) {
    header('Location: /login.php');
    exit;
}
$user = $_SESSION['user'];

$pdo = Database::connect();

$stmt = $pdo->prepare('
    SELECT article_url, article_title, created_at, updated_at
    FROM dashboard_article_training
    WHERE user_id = ?
    ORDER BY updated_at DESC
    LIMIT 50
');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$appKey = 'dashboard';
require_once __DIR__ . '/../../private/components/theme_from_session.php';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>記事トレーニング履歴 - ダッシュボード</title>
    <?php require_once __DIR__ . '/../../private/components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between gap-3 px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">記事トレーニング履歴</h1>
                    <p class="text-xs text-slate-400 truncate">これまで書いた「ほめポイント／ツッコミポイント」のログです。</p>
                </div>
            </div>
            <a href="/dashboard/article_training.php" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 text-white text-[11px] font-bold shadow-sm hover:shadow-md active:scale-[0.98] transition whitespace-nowrap">
                <i class="fa-solid fa-plus"></i>
                <span class="hidden sm:inline">新しい記事でトレーニング</span>
                <span class="sm:hidden">新規</span>
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-3xl mx-auto">
                <?php if (empty($rows)): ?>
                    <p class="text-sm text-slate-500">まだ記事トレーニングは登録されていません。</p>
                    <p class="text-xs text-slate-400 mt-1">右上の「新しい記事でトレーニング」から記事URLを入力して始められます。ダッシュボードの「今日の好奇心ブースト」「AI関連」「パレオな男」から選ぶこともできます。</p>
                    <a href="/dashboard/article_training.php" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-sm hover:shadow-md active:scale-[0.98] transition">
                        <i class="fa-solid fa-plus"></i>
                        新しい記事でトレーニング
                    </a>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $params = ['url' => $row['article_url']];
                                if (!empty($row['article_title'])) {
                                    $params['title'] = $row['article_title'];
                                }
                                $trainingUrl = '/dashboard/article_training.php?' . http_build_query($params);
                            ?>
                            <li>
                                <a href="<?= htmlspecialchars($trainingUrl) ?>" class="block bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 hover:shadow-md hover:border-slate-300 transition">
                                    <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($row['article_title'] ?: $row['article_url']) ?></p>
                                    <p class="text-[11px] text-slate-400 break-all mt-1"><?= htmlspecialchars($row['article_url']) ?></p>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        最終更新:
                                        <?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['updated_at']))) ?>
                                    </p>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
</body>
</html>

