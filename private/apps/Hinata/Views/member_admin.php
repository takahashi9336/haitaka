<?php
/**
 * メンバーメンテナンス View (期別選択肢追加版)
 * 物理パス: haitaka/private/apps/Hinata/Views/member_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
use App\Hinata\Helper\MemberGroupHelper;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Admin - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
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

        /* 一覧編集テーブル：ID・名前を横スクロール時も固定表示 */
        :root {
            --member-admin-id-width: 3.5rem;
        }
        .sticky-col-id {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #ffffff;
            min-width: var(--member-admin-id-width);
            max-width: var(--member-admin-id-width);
        }
        .sticky-col-name {
            position: sticky;
            left: var(--member-admin-id-width); /* ID列幅ぶんだけ右へずらす */
            z-index: 10;
            background-color: #ffffff;
        }
        thead .sticky-col-id,
        thead .sticky-col-name {
            z-index: 20;
        }
    </style>
    <?php if ($isThemeHex): ?>
    <style>#f_image::file-selector-button { background: <?= htmlspecialchars($themeLight ?: $themePrimary) ?>; color: <?= htmlspecialchars($themePrimary) ?>; }</style>
    <?php endif; ?>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-user-gear text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight">メンバー管理</h1>
            </div>
            <a href="/hinata/members.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>戻る</a>
        </header>

        <div class="p-4 md:p-8 max-w-6xl mx-auto w-full">
            <div class="mb-4 flex gap-2">
                <button id="tabDetail" class="px-4 py-1.5 rounded-full text-xs font-bold bg-slate-800 text-white">詳細編集</button>
                <button id="tabBulk" class="px-4 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-500">一覧編集</button>
            </div>

            <div id="detailSection" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <section id="formArea" class="bg-white p-6 md:p-8 rounded-[2.5rem] border <?= $cardBorder ?> shadow-sm">
                        <div class="mb-6 flex items-center gap-5 border-b border-slate-50 pb-6">
                            <div id="imgPreviewMain" class="image-preview flex items-center justify-center text-slate-300 bg-slate-50"><i class="fa-solid fa-camera text-2xl"></i></div>
                            <div>
                                <h2 id="formTitle" class="text-xl font-black text-slate-800 tracking-tight">メンバーを選択</h2>
                                <p class="text-[10px] text-slate-400 font-bold tracking-wider mt-1">メンバー情報・メディア管理</p>
                            </div>
                        </div>

                        <form id="memberForm" class="space-y-6 opacity-30 pointer-events-none transition-opacity" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="m_id">
                            
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                                <label class="block text-[10px] font-black text-slate-400 mb-3 ml-1 <?= $cardIconText ?> tracking-wider"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>写真（最大5枚）</label>
                                <div class="grid grid-cols-5 gap-3">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                    <div class="image-slot flex flex-col items-center gap-2" data-slot="<?= $i ?>">
                                        <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center shrink-0 img-slot-preview">
                                            <span class="text-slate-300 text-xs"><i class="fa-solid fa-plus"></i></span>
                                        </div>
                                        <input type="hidden" name="image_existing[<?= $i ?>]" class="img-existing" value="">
                                        <input type="file" name="image_file_<?= $i ?>" accept="image/*" class="img-file text-[10px] w-full max-w-[100px]">
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">名前</label><input type="text" name="name" id="f_name" required class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">かな</label><input type="text" name="kana" id="f_kana" required class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">血液型</label><select name="blood_type" id="f_blood" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><option value="">未設定</option><option value="A">A型</option><option value="B">B型</option><option value="O">O型</option><option value="AB">AB型</option><option value="不明">不明</option></select></div>
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">身長</label><input type="number" name="height" id="f_height" step="0.1" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">期生</label>
                                    <select name="generation" id="f_gen" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50">
                                        <option value="0">ポカ／期別なし</option>
                                        <option value="1">1期</option>
                                        <option value="2">2期</option>
                                        <option value="3">3期</option>
                                        <option value="4">4期</option>
                                        <option value="5">5期</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">生年月日</label><input type="date" name="birth_date" id="f_birth_date" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">出身地</label><input type="text" name="birth_place" id="f_birth_place" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"></div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">メンバー情報</label>
                                <textarea name="member_info" id="f_info" rows="3" class="w-full border border-slate-100 rounded-xl p-4 text-sm outline-none bg-slate-50 focus:bg-white transition-all" placeholder="紹介文、メモ等..."></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">サイリウムカラー 1</label><select name="color_id1" id="f_c1" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><?php foreach($colors as $c): ?><option value="<?= $c['id'] ?>"><?= $c['color_name'] ?></option><?php endforeach; ?></select></div>
                                <div><label class="block text-[10px] font-black text-slate-400 mb-1 ml-1 tracking-wider">サイリウムカラー 2</label><select name="color_id2" id="f_c2" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><?php foreach($colors as $c): ?><option value="<?= $c['id'] ?>"><?= $c['color_name'] ?></option><?php endforeach; ?></select></div>
                            </div>

                            <div class="pt-4 border-t border-slate-50 space-y-4">
                                <input type="url" name="pv_youtube_url" id="f_pv_url" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="YouTube URL">
                                <input type="url" name="blog_url" id="f_blog" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="Blog URL">
                                <input type="url" name="insta_url" id="f_insta" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="Instagram URL">
                                <input type="url" name="twitter_url" id="f_twitter" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50" placeholder="Twitter URL">
                                <select name="is_active" id="f_active" class="w-full h-11 border border-slate-100 rounded-xl px-4 text-sm outline-none bg-slate-50"><option value="1">現役メンバー</option><option value="0">卒業メンバー</option></select>
                            </div>

                            <button type="submit" class="w-full bg-slate-800 text-white h-14 rounded-lg font-black text-sm shadow-xl hover:bg-slate-900 transition-all">メンバー情報を保存</button>
                        </form>

                        <!-- 個人活動セクション（メンバー選択後に表示） -->
                        <div id="activitySection" class="mt-8 opacity-30 pointer-events-none transition-opacity">
                            <div class="border-t border-slate-200 pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-briefcase text-indigo-500"></i>
                                        <h3 class="text-sm font-black text-slate-700 tracking-tight">個人活動</h3>
                                    </div>
                                    <button type="button" onclick="ActivityAdmin.showForm()" class="text-[10px] font-bold text-indigo-500 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-full transition">
                                        <i class="fa-solid fa-plus mr-1"></i>活動を追加
                                    </button>
                                </div>
                                <div id="activityList" class="space-y-3 mb-4"></div>

                                <!-- 活動 追加/編集フォーム -->
                                <div id="activityFormWrap" class="hidden bg-indigo-50/50 border border-indigo-100 rounded-xl p-5 space-y-4">
                                    <input type="hidden" id="act_id" value="">
                                    <input type="hidden" id="act_image_existing" value="">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">カテゴリ</label>
                                            <select id="act_category" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white">
                                                <?php foreach ($activityCategories as $catKey => $cat): ?>
                                                <option value="<?= $catKey ?>"><?= htmlspecialchars($cat['label']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">表示順</label>
                                            <input type="number" id="act_sort_order" value="0" min="0" max="99" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">活動名 *</label>
                                        <input type="text" id="act_title" required class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white" placeholder="例：ほっとひといき">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">説明</label>
                                        <textarea id="act_description" rows="2" class="w-full border border-slate-200 rounded-lg p-3 text-xs outline-none bg-white" placeholder="番組概要など..."></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">誘導先URL</label>
                                            <input type="url" id="act_url" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white" placeholder="https://...">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">ボタンラベル</label>
                                            <input type="text" id="act_url_label" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white" placeholder="例：ポッドキャストを聴く">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">サムネイル画像</label>
                                        <div class="flex items-center gap-3">
                                            <div id="act_img_preview" class="w-14 h-14 rounded-lg bg-white border border-slate-200 overflow-hidden flex items-center justify-center text-slate-300 shrink-0">
                                                <i class="fa-solid fa-image text-lg"></i>
                                            </div>
                                            <input type="file" id="act_image_file" accept="image/*" class="text-[10px]">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">開始日</label>
                                            <input type="date" id="act_start_date" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">終了日</label>
                                            <input type="date" id="act_end_date" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 mb-1 tracking-wider">表示状態</label>
                                        <select id="act_is_active" class="w-full h-10 border border-slate-200 rounded-lg px-3 text-xs outline-none bg-white">
                                            <option value="1">表示</option>
                                            <option value="0">非表示</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-2 pt-2">
                                        <button type="button" onclick="ActivityAdmin.save()" class="flex-1 bg-indigo-600 text-white h-10 rounded-lg font-bold text-xs hover:bg-indigo-700 transition">保存</button>
                                        <button type="button" onclick="ActivityAdmin.hideForm()" class="px-4 h-10 rounded-lg text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 transition">キャンセル</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="bg-white p-6 rounded-xl border border-sky-50 shadow-sm overflow-hidden">
                        <div class="space-y-2 max-h-[600px] overflow-y-auto custom-scroll pr-2">
                            <?php $adminGrouped = MemberGroupHelper::group($members); ?>
                            <?php foreach ($adminGrouped['order'] as $g): ?>
                            <?php if (empty($adminGrouped['active'][$g])) continue; ?>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 mb-1 tracking-wider"><?= htmlspecialchars(MemberGroupHelper::getGenLabel($g)) ?></p>
                                <?php foreach ($adminGrouped['active'][$g] as $m): ?>
                                <div onclick='selectMember(<?= json_encode($m, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)' class="p-2 rounded-lg hover:bg-sky-50 cursor-pointer flex items-center gap-3 transition-all border border-transparent hover:border-sky-100">
                                    <?php if($m['image_url']): ?>
                                        <img src="/assets/img/members/<?= $m['image_url'] ?>?v=<?= time() ?>" class="w-10 h-10 rounded-xl object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-300"><?= $m['generation'] ?></div>
                                    <?php endif; ?>
                                    <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($m['name']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!empty($adminGrouped['graduates'])): ?>
                            <div class="border-t border-slate-100 pt-2 mt-2">
                                <p class="text-[10px] font-black text-slate-400 mb-1 tracking-wider">卒業生</p>
                                <?php foreach ($adminGrouped['graduates'] as $m): ?>
                                <div onclick='selectMember(<?= json_encode($m, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)' class="p-2 rounded-lg hover:bg-sky-50 cursor-pointer flex items-center gap-3 transition-all border border-transparent hover:border-sky-100 opacity-60">
                                    <?php if($m['image_url']): ?>
                                        <img src="/assets/img/members/<?= $m['image_url'] ?>?v=<?= time() ?>" class="w-10 h-10 rounded-xl object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-300"><?= $m['generation'] ?></div>
                                    <?php endif; ?>
                                    <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($m['name']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>

                </div>
            </div>

            <!-- 一括編集用セクション（別タブ） -->
            <div id="bulkSection" class="hidden">
                <section class="bg-white p-6 rounded-xl border border-sky-50 shadow-sm overflow-hidden">
                        <h2 class="text-sm font-black text-slate-800 mb-4">一覧編集（全メンバー）</h2>
                        <p class="text-[11px] text-slate-400 mb-3">テキストや期・状態・サイリウム・SNSをまとめて更新できます。画像やPVは「詳細編集」タブで編集してください。</p>
                        <div class="mb-2 text-right">
                            <button type="button" id="saveAllBtn" class="px-4 py-1.5 rounded-full bg-emerald-500 text-white text-[11px] font-bold hover:bg-emerald-600">一括保存</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500 sticky-col-id">ID</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500 sticky-col-name">名前</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">Kana</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">期</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">状態</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">Penlight 1</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">Penlight 2</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">Blog</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">Instagram</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">X(Twitter)</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500">メモ</th>
                                        <th class="px-2 py-2 text-left font-bold text-slate-500"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $m): ?>
                                    <tr class="border-t border-slate-100 <?= $m['is_active'] ? '' : 'bg-slate-50' ?> member-admin-row" data-id="<?= $m['id'] ?>">
                                        <td class="px-2 py-1 text-slate-400 font-mono sticky-col-id bg-white"><?= $m['id'] ?></td>
                                        <td class="px-2 py-1 sticky-col-name">
                                            <input type="text" id="row_<?= $m['id'] ?>_name" value="<?= htmlspecialchars($m['name']) ?>" class="w-32 border border-slate-200 rounded px-2 py-1 text-[11px]">
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" id="row_<?= $m['id'] ?>_kana" value="<?= htmlspecialchars($m['kana']) ?>" class="w-32 border border-slate-200 rounded px-2 py-1 text-[11px]">
                                        </td>
                                        <td class="px-2 py-1">
                                            <select id="row_<?= $m['id'] ?>_generation" class="border border-slate-200 rounded px-1 py-1 text-[11px]">
                                                <option value="0" <?= (int)$m['generation']===0?'selected':'' ?>>ポカ／期別なし</option>
                                                <?php for($g=1;$g<=5;$g++): ?>
                                                    <option value="<?= $g ?>" <?= (int)$m['generation']===$g?'selected':'' ?>><?= $g ?>期</option>
                                                <?php endfor; ?>
                                            </select>
                                        </td>
                                        <td class="px-2 py-1">
                                            <select id="row_<?= $m['id'] ?>_is_active" class="border border-slate-200 rounded px-1 py-1 text-[11px]">
                                                <option value="1" <?= $m['is_active'] ? 'selected' : '' ?>>現役</option>
                                                <option value="0" <?= !$m['is_active'] ? 'selected' : '' ?>>卒業</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-1">
                                            <select id="row_<?= $m['id'] ?>_color_id1" class="border border-slate-200 rounded px-1 py-1 text-[11px]">
                                                <option value="">--</option>
                                                <?php foreach($colors as $c): ?>
                                                    <option value="<?= $c['id'] ?>" <?= (int)$m['color_id1'] === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['color_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="px-2 py-1">
                                            <select id="row_<?= $m['id'] ?>_color_id2" class="border border-slate-200 rounded px-1 py-1 text-[11px]">
                                                <option value="">--</option>
                                                <?php foreach($colors as $c): ?>
                                                    <option value="<?= $c['id'] ?>" <?= (int)$m['color_id2'] === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['color_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" id="row_<?= $m['id'] ?>_blog_url" value="<?= htmlspecialchars($m['blog_url']) ?>" class="w-36 border border-slate-200 rounded px-2 py-1 text-[11px]">
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" id="row_<?= $m['id'] ?>_insta_url" value="<?= htmlspecialchars($m['insta_url']) ?>" class="w-36 border border-slate-200 rounded px-2 py-1 text-[11px]">
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" id="row_<?= $m['id'] ?>_twitter_url" value="<?= htmlspecialchars($m['twitter_url']) ?>" class="w-36 border border-slate-200 rounded px-2 py-1 text-[11px]">
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" id="row_<?= $m['id'] ?>_member_info" value="<?= htmlspecialchars($m['member_info']) ?>" class="w-48 border border-slate-200 rounded px-2 py-1 text-[11px]">
                                        </td>
                                        <td class="px-2 py-1 text-right">
                                            <button type="button" class="px-3 py-1 rounded-full bg-slate-800 text-white text-[11px] font-bold hover:bg-slate-900" onclick="saveRow(<?= $m['id'] ?>)">保存</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                </section>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const MEMBER_MAP = <?= json_encode(array_column($members, null, 'id'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const IMG_CACHE_BUST = '?v=<?= time() ?>';
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        // タブ切り替え
        const detailSection = document.getElementById('detailSection');
        const bulkSection = document.getElementById('bulkSection');
        const tabDetail = document.getElementById('tabDetail');
        const tabBulk = document.getElementById('tabBulk');

        tabDetail.onclick = () => {
            detailSection.classList.remove('hidden');
            bulkSection.classList.add('hidden');
            tabDetail.classList.add('bg-slate-800', 'text-white');
            tabDetail.classList.remove('bg-slate-100', 'text-slate-500');
            tabBulk.classList.remove('bg-slate-800', 'text-white');
            tabBulk.classList.add('bg-slate-100', 'text-slate-500');
        };

        tabBulk.onclick = () => {
            detailSection.classList.add('hidden');
            bulkSection.classList.remove('hidden');
            tabBulk.classList.add('bg-slate-800', 'text-white');
            tabBulk.classList.remove('bg-slate-100', 'text-slate-500');
            tabDetail.classList.remove('bg-slate-800', 'text-white');
            tabDetail.classList.add('bg-slate-100', 'text-slate-500');
        };

        const IMG_BASE = '/assets/img/members/';

        document.querySelectorAll('.img-file').forEach((input, i) => {
            input.onchange = function(e) {
                const file = e.target.files[0];
                const slot = e.target.closest('.image-slot');
                const preview = slot.querySelector('.img-slot-preview');
                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.innerHTML = '<img src="'+url+'" class="w-full h-full object-cover">';
                }
            };
        });

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

            const images = m.images || (m.image_url ? [m.image_url] : []);
            document.querySelectorAll('.image-slot').forEach((slot, i) => {
                const preview = slot.querySelector('.img-slot-preview');
                const existingInput = slot.querySelector('.img-existing');
                const fileInput = slot.querySelector('.img-file');
                fileInput.value = '';
                if (images[i]) {
                    preview.innerHTML = '<img src="'+IMG_BASE+images[i]+IMG_CACHE_BUST+'" class="w-full h-full object-cover" alt="">';
                    existingInput.value = images[i];
                } else {
                    preview.innerHTML = '<span class="text-slate-300 text-xs"><i class="fa-solid fa-plus"></i></span>';
                    existingInput.value = '';
                }
            });
            const mainPreview = document.getElementById('imgPreviewMain');
            if (images[0]) {
                mainPreview.innerHTML = '<img src="'+IMG_BASE+images[0]+IMG_CACHE_BUST+'" class="w-full h-full object-cover rounded-[24px]" alt="">';
            } else {
                mainPreview.innerHTML = '<i class="fa-solid fa-camera text-2xl"></i>';
            }

            if (typeof ActivityAdmin !== 'undefined') {
                ActivityAdmin.loadForMember(m.id);
            }
        }

        document.getElementById('memberForm').onsubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            fd.set('id', document.getElementById('m_id').value);
            const res = await fetch('/hinata/api/save_member.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json());

            if (res.status === 'success') {
                location.reload();
            } else {
                alert('エラー: ' + (res.message || '保存に失敗しました'));
            }
        };

        async function saveRow(id) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('name', document.getElementById(`row_${id}_name`).value);
            fd.append('kana', document.getElementById(`row_${id}_kana`).value);
            fd.append('generation', document.getElementById(`row_${id}_generation`).value);
            fd.append('is_active', document.getElementById(`row_${id}_is_active`).value);
            fd.append('color_id1', document.getElementById(`row_${id}_color_id1`).value);
            fd.append('color_id2', document.getElementById(`row_${id}_color_id2`).value);
            fd.append('blog_url', document.getElementById(`row_${id}_blog_url`).value);
            fd.append('insta_url', document.getElementById(`row_${id}_insta_url`).value);
            fd.append('twitter_url', document.getElementById(`row_${id}_twitter_url`).value);
            fd.append('member_info', document.getElementById(`row_${id}_member_info`).value);

            const res = await fetch('/hinata/api/save_member_basic.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json());

            if (res.status === 'success') {
                // 軽くフィードバック
                const row = event.target.closest('tr');
                if (row) {
                    row.classList.add('bg-emerald-50');
                    setTimeout(() => row.classList.remove('bg-emerald-50'), 800);
                }
            } else {
                alert('保存に失敗しました: ' + res.message);
            }
        }

        // 一括保存
        document.getElementById('saveAllBtn').onclick = async () => {
            const rows = Array.from(document.querySelectorAll('.member-admin-row'));
            const items = rows.map(row => {
                const id = row.dataset.id;
                return {
                    id: id,
                    name: document.getElementById(`row_${id}_name`).value,
                    kana: document.getElementById(`row_${id}_kana`).value,
                    generation: document.getElementById(`row_${id}_generation`).value,
                    is_active: document.getElementById(`row_${id}_is_active`).value,
                    color_id1: document.getElementById(`row_${id}_color_id1`).value,
                    color_id2: document.getElementById(`row_${id}_color_id2`).value,
                    blog_url: document.getElementById(`row_${id}_blog_url`).value,
                    insta_url: document.getElementById(`row_${id}_insta_url`).value,
                    twitter_url: document.getElementById(`row_${id}_twitter_url`).value,
                    member_info: document.getElementById(`row_${id}_member_info`).value,
                };
            });

            const res = await fetch('/hinata/api/save_member_basic_bulk.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items })
            }).then(r => r.json());

            if (res.status === 'success') {
                alert('一括保存が完了しました。');
            } else {
                alert('一括保存に失敗しました: ' + res.message);
            }
        };

        const ACTIVITIES_BY_MEMBER = <?= json_encode($activitiesByMember, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const ACTIVITY_CATEGORIES = <?= json_encode($activityCategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const ACT_IMG_BASE = '/assets/img/activities/';

        var ActivityAdmin = {
            currentMemberId: null,

            loadForMember: function(memberId) {
                this.currentMemberId = memberId;
                var section = document.getElementById('activitySection');
                section.classList.remove('opacity-30', 'pointer-events-none');
                this.hideForm();
                this.renderList(ACTIVITIES_BY_MEMBER[memberId] || []);
            },

            renderList: function(activities) {
                var list = document.getElementById('activityList');
                if (!activities.length) {
                    list.innerHTML = '<p class="text-xs text-slate-400 text-center py-3">登録された活動はありません</p>';
                    return;
                }
                var html = '';
                activities.forEach(function(a) {
                    var cat = ACTIVITY_CATEGORIES[a.category] || ACTIVITY_CATEGORIES['other'];
                    var inactive = a.is_active == 0 ? ' opacity-50' : '';
                    html += '<div class="flex items-center gap-3 bg-white rounded-xl border border-slate-200 p-3 group' + inactive + '">';
                    if (a.image_url) {
                        html += '<img src="' + ACT_IMG_BASE + a.image_url + '" class="w-10 h-10 rounded-lg object-cover shrink-0">';
                    } else {
                        html += '<div class="w-10 h-10 rounded-lg ' + cat.bg + ' flex items-center justify-center shrink-0"><i class="' + cat.icon + ' ' + cat.color + ' text-sm"></i></div>';
                    }
                    html += '<div class="flex-1 min-w-0">';
                    html += '<div class="flex items-center gap-1.5">';
                    html += '<span class="text-[8px] font-bold px-1.5 py-0.5 rounded ' + cat.pill + '">' + cat.label + '</span>';
                    if (a.is_active == 0) html += '<span class="text-[8px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">非表示</span>';
                    html += '</div>';
                    html += '<p class="text-xs font-bold text-slate-700 truncate mt-0.5">' + (a.title || '') + '</p>';
                    if (a.url) html += '<p class="text-[10px] text-slate-400 truncate">' + a.url + '</p>';
                    html += '</div>';
                    html += '<div class="flex gap-1 opacity-0 group-hover:opacity-100 transition shrink-0">';
                    html += '<button onclick="ActivityAdmin.edit(' + a.id + ')" class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-pen"></i></button>';
                    html += '<button onclick="ActivityAdmin.remove(' + a.id + ')" class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-trash"></i></button>';
                    html += '</div>';
                    html += '</div>';
                });
                list.innerHTML = html;
            },

            showForm: function(activity) {
                var wrap = document.getElementById('activityFormWrap');
                wrap.classList.remove('hidden');
                document.getElementById('act_id').value = activity ? activity.id : '';
                document.getElementById('act_category').value = activity ? activity.category : 'radio';
                document.getElementById('act_title').value = activity ? (activity.title || '') : '';
                document.getElementById('act_description').value = activity ? (activity.description || '') : '';
                document.getElementById('act_url').value = activity ? (activity.url || '') : '';
                document.getElementById('act_url_label').value = activity ? (activity.url_label || '') : '';
                document.getElementById('act_sort_order').value = activity ? (activity.sort_order || 0) : 0;
                document.getElementById('act_start_date').value = activity ? (activity.start_date || '') : '';
                document.getElementById('act_end_date').value = activity ? (activity.end_date || '') : '';
                document.getElementById('act_is_active').value = activity ? activity.is_active : '1';
                document.getElementById('act_image_existing').value = activity ? (activity.image_url || '') : '';
                document.getElementById('act_image_file').value = '';

                var preview = document.getElementById('act_img_preview');
                if (activity && activity.image_url) {
                    preview.innerHTML = '<img src="' + ACT_IMG_BASE + activity.image_url + '" class="w-full h-full object-cover">';
                } else {
                    preview.innerHTML = '<i class="fa-solid fa-image text-lg"></i>';
                }
                wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            },

            hideForm: function() {
                document.getElementById('activityFormWrap').classList.add('hidden');
            },

            edit: function(id) {
                var activities = ACTIVITIES_BY_MEMBER[this.currentMemberId] || [];
                var activity = activities.find(function(a) { return a.id == id; });
                if (activity) this.showForm(activity);
            },

            save: async function() {
                var title = document.getElementById('act_title').value.trim();
                if (!title) { alert('活動名を入力してください'); return; }

                var fd = new FormData();
                fd.append('member_id', this.currentMemberId);
                var actId = document.getElementById('act_id').value;
                if (actId) fd.append('activity_id', actId);
                fd.append('category', document.getElementById('act_category').value);
                fd.append('title', title);
                fd.append('description', document.getElementById('act_description').value);
                fd.append('url', document.getElementById('act_url').value);
                fd.append('url_label', document.getElementById('act_url_label').value);
                fd.append('sort_order', document.getElementById('act_sort_order').value);
                fd.append('start_date', document.getElementById('act_start_date').value);
                fd.append('end_date', document.getElementById('act_end_date').value);
                fd.append('is_active', document.getElementById('act_is_active').value);
                fd.append('image_existing', document.getElementById('act_image_existing').value);

                var fileInput = document.getElementById('act_image_file');
                if (fileInput.files.length > 0) {
                    fd.append('activity_image', fileInput.files[0]);
                }

                var res = await fetch('/hinata/api/save_member_activity.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); });
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert('保存エラー: ' + (res.message || ''));
                }
            },

            remove: async function(id) {
                if (!confirm('この活動を削除しますか？')) return;
                var res = await fetch('/hinata/api/delete_member_activity.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                }).then(function(r) { return r.json(); });
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert('削除エラー: ' + (res.message || ''));
                }
            }
        };

        document.getElementById('act_image_file').onchange = function(e) {
            var file = e.target.files[0];
            var preview = document.getElementById('act_img_preview');
            if (file) {
                preview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" class="w-full h-full object-cover">';
            }
        };

        // クエリパラメータからmember_id指定時、自動で該当メンバーを選択
        (function () {
            const params = new URLSearchParams(location.search);
            const id = params.get('member_id');
            if (id && MEMBER_MAP[id]) {
                selectMember(MEMBER_MAP[id]);
            }
        })();
    </script>
</body>
</html>