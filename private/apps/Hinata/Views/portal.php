<?php
/**
 * 日向坂ポータル View
 * 物理パス: haitaka/private/apps/Hinata/Views/portal.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日向坂ポータル - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
<body class="bg-[#f0f9ff] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-sky-100 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white shadow-lg shadow-sky-200">
                    <i class="fa-solid fa-sun text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter uppercase">Hinata Portal</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ログインユーザー</span>
                    <span class="text-xs font-black text-sky-500"><?= htmlspecialchars($user['id_name'] ?? 'ゲスト') ?></span>
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
                    <div class="flex items-center gap-4 bg-white rounded-xl border border-sky-100 shadow-sm px-5 py-4 w-full md:w-auto">
                        <div class="w-10 h-10 rounded-lg bg-sky-500 text-white flex items-center justify-center shadow-md">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-bold text-sky-500 uppercase tracking-[0.2em] mb-1">Next Event</p>
                            <p class="text-sm font-bold text-slate-800 mb-0.5">
                                <?= htmlspecialchars($nextEvent['event_name'] ?? '次のイベント') ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                <?php
                                    $days = (int)$nextEvent['days_left'];
                                    $dateText = isset($nextEvent['event_date']) ? date('Y/m/d', strtotime($nextEvent['event_date'])) : '';
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
                        <a href="/hinata/events.php" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-full border border-sky-100 text-sky-500 hover:bg-sky-50 transition">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- ミーグリネタ帳：リンク先を talk.php に修正 -->
                    <a href="/hinata/talk.php" class="app-card group relative bg-white p-8 rounded-xl border border-sky-100 shadow-sm overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-book-open text-6xl text-sky-500"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-sky-50 rounded-lg flex items-center justify-center text-sky-500 mb-6 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-comment-dots"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">ミーグリネタ帳</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mb-4">Talk Topics</p>
                            <p class="text-sm text-slate-400 leading-relaxed">メンバーとの会話ネタや、ミーグリのレポを記録・管理します。</p>
                        </div>
                    </a>

                    <!-- イベント -->
                    <a href="/hinata/events.php" class="app-card group relative bg-white p-8 rounded-xl border border-sky-100 shadow-sm overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-calendar-days text-6xl text-sky-500"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-sky-50 rounded-lg flex items-center justify-center text-sky-500 mb-6 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">イベント</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mb-4">Event Schedule</p>
                            <p class="text-sm text-slate-400 leading-relaxed">ライブやミーグリ、発売日などの重要日程を確認します。</p>
                        </div>
                    </a>

                    <!-- メンバー帳 -->
                    <a href="/hinata/members.php" class="app-card group relative bg-white p-8 rounded-xl border border-sky-100 shadow-sm overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-users text-6xl text-sky-500"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-sky-50 rounded-lg flex items-center justify-center text-sky-500 mb-6 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-address-card"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">メンバー帳</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mb-4">Member Bio</p>
                            <p class="text-sm text-slate-400 leading-relaxed">メンバーのプロフィール、サイリウムカラーなどをチェックします。</p>
                        </div>
                    </a>
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