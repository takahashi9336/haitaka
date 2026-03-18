<?php
/** @var array $entry */
/** @var array $user */
$appKey = 'sense_lab';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sense Lab - <?= htmlspecialchars($entry['title'] ?? '', ENT_QUOTES, 'UTF-8') ?> - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

<?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 relative">
    <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
        <div class="flex items-center gap-3 min-w-0">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
            </div>
            <div class="min-w-0">
                <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate"><?= htmlspecialchars($entry['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="mt-0.5 flex items-center gap-2 text-[10px] font-bold text-slate-400 tracking-wider">
                    <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2 py-0.5">
                        <?= htmlspecialchars($entry['category'] ?? 'other', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span><?= htmlspecialchars(substr((string)$entry['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 text-[10px] font-black tracking-wider shrink-0">
            <a href="/sense_lab/" class="text-slate-500 hover:text-slate-800 transition"><i class="fa-solid fa-arrow-left mr-1"></i>一覧</a>
            <a href="/sense_lab/edit.php?id=<?= (int)$entry['id'] ?>" class="text-slate-500 hover:text-slate-800 transition"><i class="fa-solid fa-pen mr-1"></i>編集</a>
            <form action="/sense_lab/delete.php" method="post" onsubmit="return confirm('削除してよろしいですか？');">
                <input type="hidden" name="id" value="<?= (int)$entry['id'] ?>">
                <button type="submit" class="text-rose-500 hover:text-rose-700 transition"><i class="fa-solid fa-trash mr-1"></i>削除</button>
            </form>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6 md:p-10">
        <div class="max-w-4xl mx-auto space-y-6">

            <?php if (!empty($entry['image_path'])): ?>
                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-4 md:p-5">
                    <img src="<?= htmlspecialchars($entry['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                         alt=""
                         class="w-full max-h-[520px] object-contain rounded-lg border border-slate-200 bg-slate-50">
                </section>
            <?php endif; ?>

            <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5 md:p-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-comment-dots text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-800">なぜ「いい」と感じたか</h2>
                        <p class="text-[10px] font-bold text-slate-400 tracking-wider">理由1〜3</p>
                    </div>
                </div>
                <ul class="text-sm text-slate-700 space-y-2">
                    <?php if (!empty($entry['reason_1'])): ?><li class="flex gap-2"><span class="<?= $cardDeco ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>●</span><span><?= htmlspecialchars($entry['reason_1'], ENT_QUOTES, 'UTF-8') ?></span></li><?php endif; ?>
                    <?php if (!empty($entry['reason_2'])): ?><li class="flex gap-2"><span class="<?= $cardDeco ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>●</span><span><?= htmlspecialchars($entry['reason_2'], ENT_QUOTES, 'UTF-8') ?></span></li><?php endif; ?>
                    <?php if (!empty($entry['reason_3'])): ?><li class="flex gap-2"><span class="<?= $cardDeco ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>●</span><span><?= htmlspecialchars($entry['reason_3'], ENT_QUOTES, 'UTF-8') ?></span></li><?php endif; ?>
                </ul>
            </section>

            <section class="bg-white/60 rounded-xl border border-dashed border-slate-300 p-5 md:p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-slate-100 text-slate-600">
                        <i class="fa-solid fa-robot text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-800">AIからのコメント / 深掘り質問（将来拡張用）</h2>
                        <p class="text-[10px] font-bold text-slate-400 tracking-wider">現時点ではプレースホルダー</p>
                    </div>
                </div>
                <p class="text-xs text-slate-500 font-bold leading-relaxed">
                    将来的に、ここに外部AIからのフィードバックや「さらに深掘りするための質問」を表示します。
                </p>
            </section>

        </div>
    </div>
</main>

<script src="/assets/js/core.js?v=2"></script>
<script>
    const btn = document.getElementById('mobileMenuBtn');
    if (btn) btn.onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
</script>
</body>
</html>

