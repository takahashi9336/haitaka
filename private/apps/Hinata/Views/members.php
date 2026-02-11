<?php
/**
 * メンバー帳 View (日本語版)
 * 物理パス: haitaka/private/apps/Hinata/Views/members.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>メンバー帳 - Hinata Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .filter-btn.active { background-color: #7cc7e8; color: white; border-color: #7cc7e8; }
        .member-card { transition: all 0.3s ease; }
        .member-card:hover { transform: translateY(-4px); border-color: #7cc7e8; }
        .portrait-img { width: 100%; height: 100%; object-fit: cover; }
        #memberModal.active { display: block !important; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        @keyframes slideInUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-up { animation: slideInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 bg-[#f8fbff]">
        <header class="h-14 bg-white border-b border-sky-100 flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/portal.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <h1 class="font-black text-slate-700 text-lg uppercase tracking-tight">メンバー帳</h1>
            </div>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/hinata/member_admin.php" class="text-[10px] font-bold text-sky-500 bg-sky-50 px-3 py-1.5 rounded-full"><i class="fa-solid fa-user-gear mr-1"></i>管理</a>
            <?php endif; ?>
        </header>

        <div class="bg-white border-b border-sky-50 px-4 py-3 flex gap-2 overflow-x-auto no-scrollbar shrink-0">
            <button onclick="filterGen('all')" class="filter-btn active px-6 py-1.5 rounded-full border border-slate-100 text-[10px] font-black uppercase transition-all">全員</button>
            <?php foreach([1,2,3,4,5] as $g): ?>
                <button onclick="filterGen(<?= $g ?>)" class="filter-btn px-6 py-1.5 rounded-full border border-slate-100 text-[10px] font-black uppercase transition-all"><?= $g ?>期生</button>
            <?php endforeach; ?>
        </div>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scroll pb-24">
            <div id="memberGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 max-w-6xl mx-auto">
                <?php foreach ($members as $m): ?>
                <div class="member-card bg-white rounded-[2.5rem] p-4 shadow-sm border border-sky-50/50 cursor-pointer flex flex-col items-center text-center relative overflow-hidden" 
                     data-gen="<?= $m['generation'] ?>" onclick="showDetail(<?= $m['id'] ?>)">
                    <div class="absolute top-0 left-0 w-full h-1.5" style="background: linear-gradient(to right, <?= $m['color1'] ?: '#ccc' ?>, <?= $m['color2'] ?: '#ddd' ?>);"></div>
                    <div class="w-full aspect-square rounded-[2rem] bg-sky-50 mb-4 shadow-inner overflow-hidden flex items-center justify-center">
                        <?php if(!empty($m['image_url'])): ?>
                            <img src="/assets/img/members/<?= $m['image_url'] ?>" class="portrait-img">
                        <?php else: ?>
                            <span class="text-4xl font-black text-sky-200"><?= mb_substr($m['name'], 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[9px] font-black text-sky-400 uppercase tracking-widest"><?= $m['generation'] ?>期生</span>
                        <h3 class="font-black text-slate-800 text-base"><?= htmlspecialchars($m['name']) ?></h3>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- モーダル -->
    <div id="memberModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-slate-900/80 backdrop-blur-xl transition-all">
        <div class="relative w-full max-w-5xl mx-auto md:my-10 min-h-full flex items-center">
            <div class="bg-white w-full h-full md:h-auto md:max-h-[90vh] md:rounded-[3rem] shadow-2xl relative flex flex-col md:flex-row md:overflow-hidden min-h-screen md:min-h-0 animate-up">
                <button onclick="closeModal()" class="fixed md:absolute top-4 right-4 md:top-6 md:right-6 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-[110] hover:bg-white shadow-lg"><i class="fa-solid fa-xmark text-lg"></i></button>

                <div class="w-full md:w-[380px] shrink-0 border-r border-slate-50 flex flex-col bg-white">
                    <div id="modalHeader" class="h-64 md:h-72 relative shrink-0 flex flex-col justify-end p-8 text-white overflow-hidden bg-slate-200">
                        <div class="absolute inset-0" id="modalBg"></div>
                        <img id="modalImg" src="" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay hidden">
                        <div class="relative z-10 text-white drop-shadow-md">
                            <span id="modalGen" class="text-[10px] font-black opacity-90 uppercase tracking-widest bg-black/20 px-2 py-0.5 rounded"></span>
                            <h2 id="modalName" class="text-4xl font-black mt-2 leading-tight"></h2>
                        </div>
                    </div>

                    <div class="p-8 space-y-8 overflow-y-auto custom-scroll">
                        <div id="modalInfoArea" class="hidden bg-sky-50/50 p-5 rounded-3xl border border-sky-100/50">
                            <p class="text-[9px] font-black text-sky-400 uppercase mb-1 tracking-widest">紹介文</p>
                            <p id="modalInfo" class="text-sm font-medium text-slate-600 leading-relaxed whitespace-pre-wrap"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">血液型</p><p id="modalBlood" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">身長</p><p id="modalHeight" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">生年月日</p><p id="modalBirth" class="text-base font-black text-slate-700">--</p></div>
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">出身地</p><p id="modalPlace" class="text-base font-black text-slate-700">--</p></div>
                        </div>
                        <div id="snsLinks" class="grid grid-cols-1 gap-2 pb-10 md:pb-0">
                            <a id="blogBtn" href="#" target="_blank" class="h-12 bg-sky-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-sky-600 border border-sky-100/50 hover:bg-sky-100 transition-all"><i class="fa-solid fa-blog"></i> 公式ブログ</a>
                            <a id="instaBtn" href="#" target="_blank" class="h-12 bg-pink-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-pink-600 border border-pink-100/50 hover:bg-pink-100 transition-all"><i class="fa-brands fa-instagram text-lg"></i> Instagram</a>
                        </div>
                    </div>
                </div>

                <div id="videoContainer" class="flex-1 bg-slate-50 p-6 md:p-12 flex flex-col justify-center pb-32 md:pb-12 overflow-y-auto">
                    <div id="videoArea" class="hidden w-full max-w-3xl mx-auto">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 text-center">紹介動画</p>
                        <div class="aspect-video w-full rounded-[1.5rem] md:rounded-[2.5rem] overflow-hidden bg-black shadow-2xl ring-4 md:ring-8 ring-white">
                            <iframe id="modalVideo" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                    <div id="noVideoMsg" class="text-center text-slate-300 py-20">
                        <i class="fa-brands fa-youtube text-6xl mb-4 opacity-20"></i>
                        <p class="text-[10px] font-bold uppercase tracking-widest">紹介動画はありません</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        function filterGen(gen) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            document.querySelectorAll('.member-card').forEach(card => card.style.display = (gen === 'all' || card.dataset.gen == gen) ? 'flex' : 'none');
        }

        async function showDetail(id) {
            const res = await fetch(`members.php?action=detail&id=${id}`).then(r => r.json());
            if (res.status !== 'success') return;
            const d = res.data;

            const modal = document.getElementById('memberModal');
            modal.classList.add('active');
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';

            document.getElementById('modalName').innerText = d.name || '--';
            document.getElementById('modalGen').innerText = d.generation ? `${d.generation}期生` : '--';
            document.getElementById('modalBlood').innerText = d.blood_type ? `${d.blood_type}型` : '--';
            document.getElementById('modalHeight').innerHTML = d.height ? `${d.height} <span class="text-[10px] text-slate-400 font-bold">cm</span>` : '--';
            document.getElementById('modalBirth').innerText = d.birth_date ? d.birth_date.replace(/-/g, '/') : '--';
            document.getElementById('modalPlace').innerText = d.birth_place || '--';
            document.getElementById('modalBg').style.background = `linear-gradient(135deg, ${d.color1 || '#7cc7e8'}, ${d.color2 || '#ffffff'})`;
            
            const mImg = document.getElementById('modalImg');
            if (d.image_url) { mImg.src = '/assets/img/members/' + d.image_url; mImg.classList.remove('hidden'); } else { mImg.classList.add('hidden'); }

            if (d.member_info) { document.getElementById('modalInfo').innerText = d.member_info; document.getElementById('modalInfoArea').classList.remove('hidden'); } else { document.getElementById('modalInfoArea').classList.add('hidden'); }

            const setLink = (id, url) => { const el = document.getElementById(id); if(url){ el.href = url; el.classList.remove('hidden'); }else{ el.classList.add('hidden'); } };
            setLink('blogBtn', d.blog_url); setLink('instaBtn', d.insta_url);

            const video = document.getElementById('modalVideo');
            if (d.pv_video_key) {
                video.src = `https://www.youtube.com/embed/${d.pv_video_key}?rel=0`;
                document.getElementById('videoArea').classList.remove('hidden');
                document.getElementById('noVideoMsg').classList.add('hidden');
            } else {
                video.src = "";
                document.getElementById('videoArea').classList.add('hidden');
                document.getElementById('noVideoMsg').classList.remove('hidden');
            }
        }

        function closeModal() {
            document.getElementById('memberModal').classList.remove('active');
            document.getElementById('modalVideo').src = "";
            document.body.style.overflow = '';
        }
        document.getElementById('memberModal').onclick = (e) => { if (e.target.id === 'memberModal') closeModal(); };
    </script>
</body>
</html>