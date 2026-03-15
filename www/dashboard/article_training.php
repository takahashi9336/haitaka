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

$articleUrl = isset($_GET['url']) ? trim((string)$_GET['url']) : '';
$articleTitle = isset($_GET['title']) ? trim((string)$_GET['title']) : '';

if ($articleUrl === '') {
    header('Location: /');
    exit;
}

$pdo = Database::connect();

// 既存データ取得
$stmt = $pdo->prepare('SELECT * FROM dashboard_article_training WHERE user_id = ? AND article_url = ? LIMIT 1');
$stmt->execute([$user['id'], $articleUrl]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$appKey = 'dashboard';
require_once __DIR__ . '/../../private/components/theme_from_session.php';

// 既存レコードがあればタイトルはDB優先
if ($existing && !empty($existing['article_title'])) {
    $articleTitle = $existing['article_title'];
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>記事トレーニング - ダッシュボード</title>
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
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                </div>
                <div>
                    <h1 class="font-black text-slate-700 text-xl tracking-tighter">記事トレーニング</h1>
                    <p class="text-xs text-slate-400">「ほめポイント」と「ツッコミポイント」を3つずつ書き出してみましょう。</p>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-3xl mx-auto space-y-6">

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <p class="text-[11px] font-bold tracking-wider text-slate-500 mb-2"><i class="fa-solid fa-newspaper mr-1"></i>トレーニング対象の記事</p>
                    <a href="<?= htmlspecialchars($articleUrl) ?>" target="_blank" rel="noopener noreferrer" class="block group">
                        <p class="text-sm font-bold text-slate-800 group-hover:text-slate-900">
                            <?= $articleTitle !== '' ? htmlspecialchars($articleTitle) : 'タイトル未取得の記事' ?>
                        </p>
                        <p class="text-[11px] text-slate-400 break-all mt-1"><?= htmlspecialchars($articleUrl) ?></p>
                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 mt-2">
                            記事を開いてざっと読んでみましょう
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </span>
                    </a>
                </section>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <p class="text-[11px] font-bold tracking-wider text-emerald-600 mb-1"><i class="fa-solid fa-thumbs-up mr-1"></i>ほめたいところ</p>
                    <p class="text-xs text-slate-500 mb-3">「ここが面白い」「ここが新しい」「この切り口が好き」など、素直にほめポイントを3つ書き出してみてください。</p>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php $key = 'praise_' . $i; $value = $existing[$key] ?? ''; ?>
                        <div class="mb-3">
                            <label class="text-[11px] font-medium text-slate-500 mb-1 block">ほめポイント <?= $i ?></label>
                            <textarea
                                id="praise_<?= $i ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none min-h-[56px]"
                                maxlength="500"
                            ><?= htmlspecialchars($value) ?></textarea>
                        </div>
                    <?php endfor; ?>
                </section>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
                    <p class="text-[11px] font-bold tracking-wider text-sky-600 mb-1"><i class="fa-solid fa-comment-dots mr-1"></i>ツッコミたいところ</p>
                    <p class="text-xs text-slate-500 mb-3">「ここは本当？」「別の見方もありそう」「ここをもう少し詳しく知りたい」など、ツッコミポイントを3つ書き出してみてください。</p>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php $key = 'tsukkomi_' . $i; $value = $existing[$key] ?? ''; ?>
                        <div class="mb-3">
                            <label class="text-[11px] font-medium text-slate-500 mb-1 block">ツッコミポイント <?= $i ?></label>
                            <textarea
                                id="tsukkomi_<?= $i ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent resize-none min-h-[56px]"
                                maxlength="500"
                            ><?= htmlspecialchars($value) ?></textarea>
                        </div>
                    <?php endfor; ?>
                </section>

                <section class="flex items-center justify-between gap-3">
                    <a href="/" class="inline-flex items-center text-xs text-slate-500 hover:text-slate-700">
                        <i class="fa-solid fa-chevron-left mr-1"></i> ダッシュボードに戻る
                    </a>
                    <button
                        type="button"
                        id="saveBtn"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-sm hover:shadow-md active:scale-[0.98] transition"
                        onclick="ArticleTraining.save()"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        保存する
                    </button>
                </section>

            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const ArticleTraining = {
            async save() {
                const payload = {
                    article_url: <?= json_encode($articleUrl, JSON_UNESCAPED_UNICODE) ?>,
                    article_title: <?= json_encode($articleTitle, JSON_UNESCAPED_UNICODE) ?>,
                    praise_1: document.getElementById('praise_1').value.trim(),
                    praise_2: document.getElementById('praise_2').value.trim(),
                    praise_3: document.getElementById('praise_3').value.trim(),
                    tsukkomi_1: document.getElementById('tsukkomi_1').value.trim(),
                    tsukkomi_2: document.getElementById('tsukkomi_2').value.trim(),
                    tsukkomi_3: document.getElementById('tsukkomi_3').value.trim(),
                };

                const nonEmptyCount =
                    (payload.praise_1 ? 1 : 0) +
                    (payload.praise_2 ? 1 : 0) +
                    (payload.praise_3 ? 1 : 0) +
                    (payload.tsukkomi_1 ? 1 : 0) +
                    (payload.tsukkomi_2 ? 1 : 0) +
                    (payload.tsukkomi_3 ? 1 : 0);

                if (nonEmptyCount === 0) {
                    App.toast('少なくとも1つはコメントを書いてみましょう');
                    return;
                }

                try {
                    const res = await App.post('/dashboard/api/save_article_training.php', payload);
                    if (res && res.status === 'success') {
                        App.toast('トレーニング内容を保存しました');
                    } else {
                        App.toast(res && res.message ? res.message : '保存に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('保存中にエラーが発生しました');
                }
            }
        };
    </script>
</body>
</html>

