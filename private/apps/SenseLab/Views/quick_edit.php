<?php
/** @var array $quick */
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
    <title>Sense Lab - クイックスクラップ編集 - MyPlatform</title>
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
                <i class="fa-solid fa-pen-to-square text-sm"></i>
            </div>
            <div class="min-w-0">
                <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">クイックスクラップ編集</h1>
                <p class="text-[10px] font-bold text-slate-400 tracking-wider">メモ＋画像を後から整える</p>
            </div>
        </div>
        <a href="/sense_lab/" class="text-xs font-black tracking-wider text-slate-500 hover:text-slate-800 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i>一覧へ
        </a>
    </header>

    <div class="flex-1 overflow-y-auto p-6 md:p-10">
        <div class="max-w-3xl mx-auto">

            <?php if (!empty($errors)): ?>
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <div class="flex items-center gap-2 font-black mb-2"><i class="fa-solid fa-triangle-exclamation"></i>入力を確認してください</div>
                    <ul class="list-disc list-inside text-xs font-bold">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/sense_lab/quick_update.php" method="post" enctype="multipart/form-data" class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5 md:p-8 space-y-6">
                <input type="hidden" name="id" value="<?= (int)($quick['id'] ?? 0) ?>">

                <?php if (!empty($quick['image_path'])): ?>
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <img src="<?= htmlspecialchars($quick['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""
                             class="w-full max-h-[420px] object-contain rounded-lg border border-slate-200 bg-white">
                    </section>
                <?php endif; ?>

                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-image text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-800">画像（任意）</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">差し替えたい場合のみ選択</p>
                        </div>
                    </div>
                    <input type="file" name="image" id="senseLabQuickEditImage" accept="image/*" class="block w-full text-sm text-slate-700" data-sense-lab-compress="0">
                    <p class="mt-1 text-xs text-slate-400 font-bold">JPG/PNG/GIF、最大2MB（超える場合は自動でJPEGに圧縮）。</p>
                </section>

                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-note-sticky text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-800">メモ（必須）</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">まずは粗い言葉のままでOK</p>
                        </div>
                    </div>
                    <textarea name="note" id="senseLabQuickEditNote" rows="4" required
                              class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                              placeholder="何が『いい』と感じた？"><?= htmlspecialchars($quick['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </section>

                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $cardIconBg ?> <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <i class="fa-solid fa-comment-dots text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-800">理由（任意・1〜3）</h2>
                            <p class="text-[10px] font-bold text-slate-400 tracking-wider">後からゆっくり言語化</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label for="senseLabQuickEditReason1" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">理由1</label>
                            <textarea name="reason_1" id="senseLabQuickEditReason1" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                                      placeholder="どこが一番『いい』と感じた？"><?= htmlspecialchars($quick['reason_1'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div>
                            <label for="senseLabQuickEditReason2" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">理由2</label>
                            <textarea name="reason_2" id="senseLabQuickEditReason2" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                                      placeholder="色・構図・余白など、テクニカルに言うと？"><?= htmlspecialchars($quick['reason_2'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div>
                            <label for="senseLabQuickEditReason3" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">理由3</label>
                            <textarea name="reason_3" id="senseLabQuickEditReason3" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all resize-none"
                                      placeholder="空気・文脈として、なぜ響いた？"><?= htmlspecialchars($quick['reason_3'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <p class="text-xs text-slate-400 font-bold">※ クイックスクラップでは理由は任意です（後で本番スクラップにまとめる想定）。</p>
                    </div>
                </section>

                <section class="max-w-xs">
                    <label for="senseLabQuickEditCategory" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">カテゴリ（任意）</label>
                    <?php $v = (string)($quick['category_hint'] ?? ''); ?>
                    <select name="category_hint" id="senseLabQuickEditCategory" class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white outline-none">
                        <option value=""<?= $v === '' ? ' selected' : '' ?>>未設定</option>
                        <option value="food"<?= $v === 'food' ? ' selected' : '' ?>>食事</option>
                        <option value="design"<?= $v === 'design' ? ' selected' : '' ?>>デザイン</option>
                        <option value="daily"<?= $v === 'daily' ? ' selected' : '' ?>>日常</option>
                        <option value="other"<?= $v === 'other' ? ' selected' : '' ?>>その他</option>
                    </select>
                </section>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 h-12 rounded-xl text-xs font-black tracking-wider text-white shadow-md transition <?= $btnBgClass ?>"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                        <i class="fa-solid fa-floppy-disk"></i> 更新する
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="/assets/js/sense_lab_image_compress.js?v=2"></script>
<script src="/assets/js/core.js?v=2"></script>
<script>
    const btn = document.getElementById('mobileMenuBtn');
    if (btn) btn.onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
</script>
</body>
</html>

