<?php
/**
 * ガイド管理 View（一覧）
 */
$appKey = 'admin';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$guideSuccess = $_SESSION['guide_success'] ?? null;
$guideError = $_SESSION['guide_error'] ?? null;
unset($_SESSION['guide_success'], $_SESSION['guide_error']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ガイド管理 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --admin-theme: <?= htmlspecialchars($themePrimaryHex ?? '#64748b') ?>; }
        .admin-btn-primary { background-color: var(--admin-theme); }
        .admin-btn-primary:hover { filter: brightness(1.08); }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?? 'bg-slate-50' ?>">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/admin/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg bg-slate-700">
                    <i class="fa-solid fa-book-open text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">ガイド管理</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-4xl mx-auto w-full">
                <?php if ($guideSuccess): ?>
                <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700 font-bold"><?= htmlspecialchars($guideSuccess) ?></div>
                <?php endif; ?>
                <?php if ($guideError): ?>
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 font-bold"><?= htmlspecialchars($guideError) ?></div>
                <?php endif; ?>

                <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">ガイド一覧</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">手順ガイドの作成・編集</p>
                        </div>
                        <a href="/admin/guides.php?new=1" class="shrink-0 admin-btn-primary text-white text-xs font-black tracking-wider px-6 h-12 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-plus"></i> 新規作成
                        </a>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm min-w-[400px]">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">guide_key</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">タイトル</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider">ブロック数</th>
                                    <th class="px-6 py-4 font-black text-[10px] text-slate-400 tracking-wider text-right">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($guides)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-sm font-bold">ガイドがありません</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($guides as $g): ?>
                                <?php $blockCount = is_array($g['blocks'] ?? null) ? count($g['blocks']) : 0; ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-700"><?= htmlspecialchars($g['guide_key']) ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($g['title']) ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-500"><?= $blockCount ?> ブロック</td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <a href="/admin/guides.php?id=<?= (int)$g['id'] ?>" class="text-[10px] font-black tracking-wider px-3 py-1.5 rounded-lg transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200">編集</a>
                                        <form method="post" action="/admin/guides.php" class="inline" onsubmit="return confirm('このガイドを削除しますか？');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                            <button type="submit" class="text-[10px] font-black tracking-wider px-3 py-1.5 rounded-lg transition-colors text-red-500 hover:bg-red-50">削除</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
