<?php
/**
 * 管理画面ポータル View
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - MyPlatform</title>
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
<body class="bg-[#f1f5f9] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 bg-slate-700 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-300">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">管理画面</h1>
            </div>
            <p class="text-[10px] font-bold text-slate-400 tracking-wider">管理者専用</p>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight mb-2">管理ツール</h2>
                    <p class="text-slate-500 font-medium">データベースの確認やユーザー管理を行います。</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- DBビューワ -->
                    <a href="/db_viewer/" class="app-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-database text-6xl text-emerald-500"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 mb-2 md:mb-6 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-database text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">DBビューワ</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">データベースのテーブル内容を参照します。</p>
                        </div>
                    </a>

                    <!-- ユーザー管理 -->
                    <a href="/admin/users.php" class="app-card group relative bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center p-4 md:p-8 md:block">
                        <div class="hidden md:block absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-users-gear text-6xl text-indigo-500"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center md:block">
                            <div class="w-16 h-16 md:w-12 md:h-12 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 mb-2 md:mb-6 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-users-gear text-2xl md:text-base"></i>
                            </div>
                            <h3 class="text-[10px] md:text-xl font-bold md:font-black text-slate-800 md:mb-4 text-center md:text-left">ユーザー管理</h3>
                            <p class="hidden md:block text-sm text-slate-400 leading-relaxed">ユーザーの追加・パスワードリセットを行います。</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
