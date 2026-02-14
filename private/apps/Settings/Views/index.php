<?php
/**
 * 設定画面 View (日本語版) - マイアカウント（パスワード変更）のみ
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設定 - MyPlatform</title>
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
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
    </style>
</head>
<body class="bg-[#f8fafc] flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                    <i class="fa-solid fa-gear text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">設定</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-4xl mx-auto w-full">
                <section id="section-profile" class="space-y-6">
                    <div class="bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-key text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-800">パスワード変更</h2>
                                <p class="text-[10px] font-bold text-slate-400 tracking-wider">現在のパスワードと新しいパスワードを入力してください</p>
                            </div>
                        </div>
                        <form id="formSelfPass" class="space-y-4 w-full">
                            <div class="max-w-md">
                                <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">現在のパスワード</label>
                                <input type="password" name="current_password" required class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                            </div>
                            <div class="max-w-md">
                                <label class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">新しいパスワード</label>
                                <input type="password" name="new_password" required class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                            </div>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black tracking-wider px-8 h-12 rounded-xl shadow-md shadow-indigo-200/50 transition-all">変更を保存する</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        document.getElementById('formSelfPass').onsubmit = async (e) => {
            e.preventDefault();
            const res = await App.post('/users_settings/api/update_self.php', Object.fromEntries(new FormData(e.target)));
            if (res.status === 'success') { alert('パスワードを更新しました'); e.target.reset(); }
            else { alert('エラー: ' + res.message); }
        };
    </script>
</body>
</html>
