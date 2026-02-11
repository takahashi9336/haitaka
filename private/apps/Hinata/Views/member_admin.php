<?php
/**
 * メンバーメンテナンス View (期別選択肢追加版)
 * 物理パス: haitaka/private/apps/Hinata/Views/member_admin.php
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Admin - Hinata Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 50; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .image-preview { width: 100px; height: 100px; border-radius: 24px; object-fit: cover; background: #f1f5f9; border: 2px solid #e2e8f0; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 bg-[#f0f9ff] overflow-y-auto">
        <header class="h-14 bg-white border-b border-sky-100 flex items-center justify-between px-4 shrink-0 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <h1 class="font-black text-slate-700 text-lg uppercase tracking-tight">Member Admin</h1>
            </div>
            <a href="/hinata/members.php" class="text-xs font-bold text-sky-500 bg-sky-50 px-4 py-2 rounded-full hover:bg-sky-100 transition">戻る</a>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <section id="formArea" class="bg-white p-6 md:p-8 rounded-[2.5rem] border border-sky-100 shadow-sm">
                        <div class="mb-6 flex items-center gap-5 border-b border-slate-50 pb-6">
                            <div class="relative group">
                                <img id="imgPreview" src="" class="image-preview hidden">
                                <div id="imgPlaceholder" class="image-preview flex items-center justify-center text-slate-300"><i class="fa-solid fa-camera text-2xl"></i></div>
                            </div>
                            <div>
                                <h2 id="formTitle" class="text-xl font-black text-slate-800 tracking-tight">メンバーを選択</h2>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Identity & Media Management</p>
                            </div>
                        </div>

                        <form id="memberForm" class="space-y-6 opacity-30 pointer-events-none transition-opacity">
                            <input type="hidden" name="id" id="m_id">
                            
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 ml-1 text-sky-500">Portrait Image Upload</label>
                                <input type="file" name="image_file" id="f_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-sky-50 file:text-sky-600 hover:file:bg-sky-100 cursor-pointer">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Name</label><input type="text" name="name" id="f_name" required class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Kana</label><input type="text" name="kana" id="f_kana" required class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Blood</label><select name="blood_type" id="f_blood" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><option value="">未設定</option><option value="A">A型</option><option value="B">B型</option><option value="O">O型</option><option value="AB">AB型</option><option value="不明">不明</option></select></div>
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Height</label><input type="number" name="height" id="f_height" step="0.1" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Gen</label>
                                    <select name="generation" id="f_gen" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                                        <option value="1">1期</option>
                                        <option value="2">2期</option>
                                        <option value="3">3期</option>
                                        <option value="4">4期</option>
                                        <option value="5">5期</option> <!-- 5期を追加 -->
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Birth Date</label><input type="date" name="birth_date" id="f_birth_date" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Birth Place</label><input type="text" name="birth_place" id="f_birth_place" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Member Information</label>
                                <textarea name="member_info" id="f_info" rows="3" class="w-full border border-slate-100 rounded-xl p-4 text-sm outline-none bg-slate-50 focus:bg-white transition-all" placeholder="紹介文、メモ等..."></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Penlight 1</label><select name="color_id1" id="f_c1" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><?php foreach($colors as $c): ?><option value="<?= $c['id'] ?>"><?= $c['color_name'] ?></option><?php endforeach; ?></select></div>
                                <div><label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Penlight 2</label><select name="color_id2" id="f_c2" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><?php foreach($colors as $c): ?><option value="<?= $c['id'] ?>"><?= $c['color_name'] ?></option><?php endforeach; ?></select></div>
                            </div>

                            <div class="pt-4 border-t border-slate-50 space-y-4">
                                <input type="url" name="pv_youtube_url" id="f_pv_url" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="YouTube URL">
                                <input type="url" name="blog_url" id="f_blog" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="Blog URL">
                                <input type="url" name="insta_url" id="f_insta" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="Instagram URL">
                                <input type="url" name="twitter_url" id="f_twitter" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="Twitter URL">
                                <select name="is_active" id="f_active" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><option value="1">現役メンバー</option><option value="0">卒業メンバー</option></select>
                            </div>

                            <button type="submit" class="w-full bg-slate-800 text-white h-14 rounded-2xl font-black text-sm shadow-xl hover:bg-slate-900 transition-all">メンバー情報を保存</button>
                        </form>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="bg-white p-6 rounded-[2rem] border border-sky-50 shadow-sm overflow-hidden">
                        <div class="space-y-1 max-h-[600px] overflow-y-auto custom-scroll pr-2">
                            <?php foreach ($members as $m): ?>
                            <div onclick='selectMember(<?= json_encode($m) ?>)' class="p-2 rounded-2xl hover:bg-sky-50 cursor-pointer flex items-center gap-3 transition-all border border-transparent hover:border-sky-100 <?= $m['is_active'] ? '' : 'opacity-40' ?>">
                                <?php if($m['image_url']): ?>
                                    <img src="/assets/img/members/<?= $m['image_url'] ?>" class="w-10 h-10 rounded-xl object-cover">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-300"><?= $m['generation'] ?></div>
                                <?php endif; ?>
                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($m['name']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        // プレビュー表示
        document.getElementById('f_image').onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                const preview = document.getElementById('imgPreview');
                preview.src = url;
                preview.classList.remove('hidden');
                document.getElementById('imgPlaceholder').classList.add('hidden');
            }
        };

        function selectMember(m) {
            const form = document.getElementById('memberForm');
            form.classList.remove('opacity-30', 'pointer-events-none');
            form.reset();
            
            document.getElementById('formTitle').innerText = m.name;
            document.getElementById('m_id').value = m.id;
            document.getElementById('f_name').value = m.name;
            document.getElementById('f_kana').value = m.kana;
            document.getElementById('f_gen').value = m.generation;
            document.getElementById('f_blood').value = m.blood_type || '';
            document.getElementById('f_height').value = m.height || '';
            document.getElementById('f_birth_date').value = m.birth_date || '';
            document.getElementById('f_birth_place').value = m.birth_place || '';
            document.getElementById('f_info').value = m.member_info || '';
            document.getElementById('f_blog').value = m.blog_url || '';
            document.getElementById('f_insta').value = m.insta_url || '';
            document.getElementById('f_twitter').value = m.twitter_url || '';
            document.getElementById('f_c1').value = m.color_id1 || '';
            document.getElementById('f_c2').value = m.color_id2 || '';
            document.getElementById('f_active').value = m.is_active;

            const preview = document.getElementById('imgPreview');
            const placeholder = document.getElementById('imgPlaceholder');
            if (m.image_url) {
                preview.src = '/assets/img/members/' + m.image_url;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            } else {
                preview.classList.add('hidden');
                placeholder.classList.remove('hidden');
            }
        }

        document.getElementById('memberForm').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/save_member.php', {
                method: 'POST',
                body: new FormData(e.target)
            }).then(r => r.json());

            if (res.status === 'success') {
                location.reload();
            } else {
                alert('エラー: ' + res.message);
            }
        };
    </script>
</body>
</html>