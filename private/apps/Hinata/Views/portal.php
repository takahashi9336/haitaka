<?php
/**
 * 日向坂ポータル View（テーマ色はセッションの hinata アプリから取得）
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日向坂ポータル - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if ($isThemeHex): ?>
    <style>
        .hinata-portal-card .card-icon { <?= $cardIconStyle ?> }
        .hinata-portal-card:hover .card-icon { <?= $cardIconHoverStyle ?> }
        .hinata-portal-card .card-deco { <?= $cardDecoStyle ?> }
        .hinata-next-event-icon { background-color: <?= htmlspecialchars($themePrimary) ?>; color: #fff; }
        .hinata-next-event-label { color: <?= htmlspecialchars($themePrimary) ?>; }
        .hinata-user-name { color: <?= htmlspecialchars($themePrimary) ?>; }
    </style>
    <?php endif; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .app-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .app-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgb(0 0 0 / 0.08); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-sun text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">日向坂ポータル</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider">ログインユーザー</span>
                    <span class="text-xs font-black hinata-user-name <?= !$isThemeHex ? "text-{$themeTailwind}-500" : '' ?>"><?= htmlspecialchars($user['id_name'] ?? 'ゲスト') ?></span>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scroll">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight mb-2">おかえりなさい！</h2>
                    <p class="text-slate-500 font-medium">お疲れ様です。今日も日向坂46を応援しましょう。</p>
                </div>

                <?php if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] >= 0): ?>
                <div class="mb-10 flex items-center">
                    <div class="flex items-center gap-4 bg-white rounded-xl border <?= $cardBorder ?> shadow-sm px-5 py-4 w-full md:w-auto">
                        <div class="w-10 h-10 rounded-lg text-white flex items-center justify-center shadow-md hinata-next-event-icon <?= $headerIconBg ?>">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-bold tracking-wider mb-1 hinata-next-event-label <?= !$isThemeHex ? "text-{$themeTailwind}-500" : '' ?>">次のイベント</p>
                            <p class="text-sm font-bold text-slate-800 mb-0.5">
                                <?= htmlspecialchars($nextEvent['event_name'] ?? '次のイベント') ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                <?php
                                    $days = (int)$nextEvent['days_left'];
                                    $dateText = !empty($nextEvent['event_date']) ? \Core\Utils\DateUtil::format($nextEvent['event_date'], 'Y/m/d') : '';
                                    if ($days === 0) {
                                        echo '本日開催';
                                    } elseif ($days === 1) {
                                        echo 'あと 1 日';
                                    } else {
                                        echo 'あと ' . $days . ' 日';
                                    }
                                    if ($dateText) {
                                        echo '（' . $dateText . '）';
                                    }
                                ?>
                            </p>
                        </div>
                        <a href="/hinata/events.php" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border <?= $cardBorder ?> <?= $cardIconText ?> hover:opacity-80 transition"<?= $isThemeHex ? ' style="color: ' . htmlspecialchars($themePrimary) . ';"' : '' ?>>
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-3 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- ミーグリネタ帳 -->
                    <a href="/hinata/talk.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-book-open text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-comment-dots text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ミーグリネタ帳</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メンバーとの会話ネタや、ミーグリのレポを記録・管理します。</p>
                        </div>
                    </a>

                    <!-- イベント -->
                    <a href="/hinata/events.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-calendar-days text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-calendar-check text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">イベント</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ライブやミーグリ、発売日などの重要日程を確認します。</p>
                        </div>
                    </a>

                    <!-- メンバー帳 -->
                    <a href="/hinata/members.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-users text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-address-card text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">メンバー帳</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">メンバーのプロフィール、サイリウムカラーなどをチェックします。</p>
                        </div>
                    </a>

                    <!-- 楽曲 -->
                    <a href="/hinata/songs.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-music text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-music text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">楽曲</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">リリース一覧・全曲一覧・楽曲の紹介を確認します。</p>
                        </div>
                    </a>

                    <!-- 動画一覧 -->
                    <a href="/hinata/media_list.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-video text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-play-circle text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画一覧</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">登録されたすべての動画を閲覧します。</p>
                        </div>
                    </a>

                    <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                    <!-- リリース管理（管理者のみ） -->
                    <a href="/hinata/release_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-compact-disc text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-compact-disc text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">リリース管理</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">シングル・アルバム情報を管理します。</p>
                        </div>
                    </a>

                    <!-- 動画・メンバー紐付け管理（管理者のみ） -->
                    <a href="/hinata/media_member_admin.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-link text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-link text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画・メンバー紐付け</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">動画に出演メンバーを紐づけます。</p>
                        </div>
                    </a>

                    <!-- 動画一括登録（管理者のみ） -->
                    <a href="/hinata/media_import.php" class="app-card hinata-portal-card group relative bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform card-deco <?= $cardDeco ?>">
                            <i class="fa-solid fa-cloud-arrow-up text-6xl"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 rounded-lg flex items-center justify-center mb-2 md:mb-6 transition-colors card-icon <?= $cardIconBg ?> <?= $cardIconText ?> <?= $cardIconHover ?>">
                                <i class="fa-solid fa-film text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">動画一括登録</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">YouTube動画を一括でインポートします。</p>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };
    </script>
</body>
</html>