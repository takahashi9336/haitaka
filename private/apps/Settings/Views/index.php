<?php
/**
 * 設定画面 View (日本語版)
 * 物理パス: haitaka/private/apps/Settings/Views/index.php
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-xl"></i></button>
                <h1 class="text-base font-bold text-slate-800 uppercase tracking-widest">設定</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-12">
            <div class="max-w-4xl mx-auto w-full">
                
                <?php if ($user['role'] === 'admin'): ?>
                <div class="flex gap-6 mb-8 border-b border-slate-200 overflow-x-auto whitespace-nowrap">
                    <button onclick="switchTab('profile')" id="tab-profile" class="pb-3 text-xs font-black uppercase tracking-tighter border-b-2 border-indigo-600 text-indigo-600">マイアカウント</button>
                    <button onclick="switchTab('users')" id="tab-users" class="pb-3 text-xs font-black uppercase tracking-tighter border-b-2 border-transparent text-slate-400 hover:text-slate-600">ユーザー管理</button>
                </div>
                <?php endif; ?>

                <section id="section-profile" class="space-y-6">
                    <div class="bg-white p-5 md:p-8 rounded-3xl border border-slate-200 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-800 mb-1">パスワード変更</h2>
                        <p class="text-[10px] font-bold text-slate-400 mb-6 uppercase tracking-widest">Change Password</p>
                        
                        <form id="formSelfPass" class="space-y-4 w-full">
                            <div class="max-w-md">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">現在のパスワード</label>
                                <input type="password" name="current_password" required class="w-full border border-slate-100 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                            </div>
                            <div class="max-w-md">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">新しいパスワード</label>
                                <input type="password" name="new_password" required class="w-full border border-slate-100 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                            </div>
                            <button type="submit" class="bg-slate-800 text-white text-xs font-black uppercase tracking-widest px-8 h-12 rounded-xl hover:bg-slate-900 transition-all shadow-lg">変更を保存する</button>
                        </form>
                    </div>
                </section>

                <?php if ($user['role'] === 'admin'): ?>
                <section id="section-users" class="hidden space-y-6">
                    <div class="flex flex-col md:flex-row gap-4 mb-4">
                        <button onclick="document.getElementById('createUserModal').classList.remove('hidden')" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest px-6 h-14 rounded-2xl shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-user-plus"></i> ユーザーを追加
                        </button>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto w-full">
                            <table class="w-full text-left text-sm min-w-[500px]">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-4 font-black text-[10px] text-slate-400 uppercase">ユーザーID</th>
                                        <th class="px-6 py-4 font-black text-[10px] text-slate-400 uppercase">権限</th>
                                        <th class="px-6 py-4 font-black text-[10px] text-slate-400 uppercase text-right">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php foreach ($allUsers as $u): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($u['id_name']) ?></td>
                                        <td class="px-6 py-4 text-xs font-bold uppercase text-slate-400"><?= $u['role'] === 'admin' ? '管理者' : 'ユーザー' ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['id_name']) ?>')" class="text-[10px] font-black uppercase text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg">リセット</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        const switchTab = (tab) => {
            document.getElementById('section-profile').classList.toggle('hidden', tab !== 'profile');
            document.getElementById('section-users').classList.toggle('hidden', tab !== 'users');
            document.getElementById('tab-profile').classList.toggle('border-indigo-600', tab === 'profile');
            document.getElementById('tab-users').classList.toggle('border-indigo-600', tab === 'users');
        };
        
        document.getElementById('formSelfPass').onsubmit = async (e) => {
            e.preventDefault();
            const res = await App.post('api/update_self.php', Object.fromEntries(new FormData(e.target)));
            if (res.status === 'success') { alert('パスワードを更新しました'); e.target.reset(); }
            else { alert('エラー: ' + res.message); }
        };
    </script>
</body>
</html>