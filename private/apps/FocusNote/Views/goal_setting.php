<?php
/**
 * 仕事における本当に効果的な目標設定（パレオな男・鈴木祐氏の知見に基づく）
 */
$appKey = 'focus_note';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>目標設定の考え方 - Focus Note - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --fn-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .fn-theme-btn { background-color: var(--fn-theme); }
        .fn-theme-btn:hover { filter: brightness(1.08); }
        .fn-theme-link { color: var(--fn-theme); }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; }
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
                <a href="/focus_note/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-bullseye text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">目標設定の考え方</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-3xl mx-auto space-y-8">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <p class="text-sm text-slate-500 leading-relaxed flex-1">
                        パレオな男（<a href="https://yuchrszk.blogspot.com/" target="_blank" rel="noopener" class="fn-theme-link hover:underline">yuchrszk.blogspot.com</a>）や著者・鈴木祐氏の知見に基づき、仕事において科学的に効果があるとされる目標設定の手法をまとめました。
                    </p>
                    <a href="/focus_note/goal_setting_form.php" class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-xl fn-theme-btn text-white text-sm font-bold">
                        <i class="fa-solid fa-pen"></i> 設定する
                    </a>
                </div>

                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">1</span>
                        MACの原則
                    </h2>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        多くの企業で使われる SMART は、実は科学的な根拠が乏しい面があると指摘されています。代わりに推奨されているのが、心理学者ゲーリー・レイサムらが提唱する <strong>MACの原則</strong> です。
                    </p>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <strong class="text-slate-700 shrink-0">Measurable（測定可能性）</strong>
                            <span>目標の達成度合いが数字で測れること。</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <strong class="text-slate-700 shrink-0">Actionable（行動可能性）</strong>
                            <span>目標達成のための具体的なプロセス（行動）が明確であること。「売上を上げる」ではなく「毎日3件新規に電話する」といった行動に落とし込みます。</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <strong class="text-slate-700 shrink-0">Competent（適格性・能力向上）</strong>
                            <span>その目標に取り組むことで、自分のスキルが向上し、成長を実感できること。単なる数字の達成ではなく、「自分の能力が試される」感覚が重要です。</span>
                        </li>
                    </ul>
                </section>

                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">2</span>
                        WOOP（ウープ）
                    </h2>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        目標を立てるだけでなく、それを実行に移すための最強の心理ツールが <strong>WOOP</strong> です。ポジティブな想像だけでなく、あえて「障害」をセットで考えるのが特徴です。
                    </p>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li><strong class="text-slate-700">Wish（願望）</strong> — 仕事で達成したいこと（例：プロジェクトを納期通りに終わらせる）</li>
                        <li><strong class="text-slate-700">Outcome（成果）</strong> — それが達成された時の最高のメリットを想像する（例：上司に評価され、週末を心置きなく休める）</li>
                        <li><strong class="text-slate-700">Obstacle（障害）</strong> — 達成を阻む「自分の内面にある障害」を特定する（例：ついYouTubeを見てしまう、完璧主義で筆が止まる）</li>
                        <li><strong class="text-slate-700">Plan（計画）</strong> — 障害が起きた時の対策を「If-Thenプランニング」で決めておく</li>
                    </ul>
                </section>

                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">3</span>
                        If-Thenプランニング
                    </h2>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        「もし A が起きたら、B をする」という形で行動をルール化する手法です。意志の力に頼らずにタスクをこなせるようになります。
                    </p>
                    <ul class="space-y-2 text-sm text-slate-600 list-disc list-inside">
                        <li>「メールを開いたら（If）、その場で返信するか、タスクリストに入れる（Then）」</li>
                        <li>「午後の会議が終わったら（If）、すぐに5分だけ重要書類に手をつける（Then）」</li>
                    </ul>
                </section>

                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">4</span>
                        プロセス目標に集中する
                    </h2>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        「売上1億円」といった<strong>成果目標</strong>（コントロールできないもの）ばかりを追うと、ストレスが増えモチベーションが低下しやすくなります。代わりに、<strong>プロセス目標</strong>（自分が100%コントロールできる行動）に集中することが推奨されます。
                    </p>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                            <p class="font-bold text-slate-600 mb-1">成果目標の例</p>
                            <p class="text-slate-500">コンペで勝つ</p>
                        </div>
                        <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-100">
                            <p class="font-bold text-slate-600 mb-1">プロセス目標の例</p>
                            <p class="text-slate-500">プレゼン資料を毎日1ページ完成させる、毎日15分練習する</p>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm fn-theme-btn">5</span>
                        目標設定の落とし穴（副作用）
                    </h2>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        目標設定には副作用があることも強調されています。
                    </p>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li><strong class="text-slate-700">不正の誘発</strong> — 過度な数値目標は、不祥事やデータの改ざんを招くリスクがある</li>
                        <li><strong class="text-slate-700">視野の狭窄</strong> — 設定した目標以外の重要なこと（チームワークや長期的な改善など）を無視してしまう</li>
                        <li><strong class="text-slate-700">燃え尽き</strong> — 目標達成そのものが目的化すると、達成後に意欲がなくなる</li>
                    </ul>
                    <p class="text-sm text-slate-600 mt-4 leading-relaxed">
                        これらを防ぐためには、「なぜその仕事をするのか？」という<strong>抽象度の高い目的（Being／ありたい姿）</strong>と、具体的な目標をリンクさせることが重要です。
                    </p>
                </section>

                <section class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-lightbulb fn-theme-link"></i>
                        推奨の始め方
                    </h2>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        まずは、目の前のタスクに対して「If-Thenプランニング」を設定し、週単位などの短いスパンで「MACの原則」に沿った行動目標を立てることから始めるのが最も効果的です。
                    </p>
                </section>

                <footer class="pt-4 border-t border-slate-100 text-xs text-slate-400">
                    <p>参考：パレオな男（<a href="https://yuchrszk.blogspot.com/" target="_blank" rel="noopener" class="fn-theme-link hover:underline">yuchrszk.blogspot.com</a>）、著者・鈴木祐氏の知見に基づく</p>
                </footer>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = function() {
            document.getElementById('sidebar').classList.add('mobile-open');
        };
    </script>
</body>
</html>
