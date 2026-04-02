<?php
/** @var array $user */
/** @var array $errors */
$appKey = 'sense_lab';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sense Lab - 新規スクラップ - MyPlatform</title>
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
        <div class="flex items-center gap-3">
            <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
            </div>
            <div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">Sense Lab</h1>
                <p class="text-[10px] font-bold text-slate-400 tracking-wider">新規スクラップ</p>
            </div>
        </div>
        <a href="/sense_lab/" class="text-xs font-black tracking-wider text-slate-500 hover:text-slate-800 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i>一覧へ
        </a>
    </header>

    <div class="flex-1 overflow-y-auto p-6 md:p-10">
        <div class="max-w-3xl mx-auto">

            <?php if ($errors): ?>
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <div class="flex items-center gap-2 font-black mb-2"><i class="fa-solid fa-triangle-exclamation"></i>入力を確認してください</div>
                    <ul class="list-disc list-inside text-xs font-bold">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/sense_lab/create.php" method="post" enctype="multipart/form-data" class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5 md:p-8 space-y-6">
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-image text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-800">インプット（画像＋一言）</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">まずは「いいな」をそのまま保存</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="image" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">画像（任意）</label>
                            <input type="file" name="image" id="image" accept="image/*"
                                   class="block w-full text-sm text-slate-700">
                            <p class="mt-1 text-xs text-slate-400">JPG/PNG/GIF、最大2MB（超える場合は自動でJPEGに圧縮）。自炊・MVキャプチャ・風景など。</p>
                        </div>
                        <div>
                            <label for="title" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">タイトル（必須）</label>
                            <input type="text" name="title" id="title" required
                                   class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all"
                                   placeholder="直感的なひとこと（例: 余白のある食卓）">
                        </div>
                        <div class="max-w-xs">
                            <label for="category" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">カテゴリ</label>
                            <select name="category" id="category" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white outline-none">
                                <option value="food">食事</option>
                                <option value="design">デザイン</option>
                                <option value="daily">日常</option>
                                <option value="other" selected>その他</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-800">言語化（理由1〜3）</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">最低1つは必ず書く（センス＝選ぶ力の筋トレ）</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="reason_1" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">理由1: どこが一番「いい」と感じた？</label>
                            <textarea name="reason_1" id="reason_1" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                                      placeholder="例: メインの皿に視線が自然に集まるレイアウトになっているから"></textarea>
                        </div>
                        <div>
                            <label for="reason_2" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">理由2: 色・構図・余白など、テクニカルな視点で言うと？</label>
                            <textarea name="reason_2" id="reason_2" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                                      placeholder="例: 全体が3色以内にまとまっていて、食器の白が余白として効いているから"></textarea>
                        </div>
                        <div>
                            <label for="reason_3" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">理由3: その場の空気・文脈として、なぜ響いた？</label>
                            <textarea name="reason_3" id="reason_3" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                                      placeholder="例: 仕事終わりの夜に、この落ち着いた光の感じがちょうどよかったから"></textarea>
                        </div>
                        <p class="text-xs text-slate-400 font-bold">※ 理由は3つすべてでなくてもOKですが、最低1つは入力してください。</p>
                    </div>
                </section>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 h-12 rounded-xl text-xs font-black tracking-wider text-white shadow-md transition <?= $btnBgClass ?>"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-floppy-disk"></i> 保存する
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="/assets/js/sense_lab_image_compress.js?v=1"></script>
<script src="/assets/js/core.js?v=2"></script>
<script>
    const btn = document.getElementById('mobileMenuBtn');
    if (btn) btn.onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
</script>
</body>
</html>

