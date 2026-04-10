<?php
/**
 * 動画管理（楽曲/メンバー/設定）統合画面 View（管理者専用）
 * 物理パス: haitaka/private/apps/Hinata/Views/media_admin.php
 *
 * Controller から渡される想定:
 * - $categories
 * - $members
 * - $releasesWithSongs
 * - $trackTypesDisplay
 * - $initialTab ('song'|'member'|'settings')
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$initialTab = $initialTab ?? 'member';
if (!in_array($initialTab, ['song', 'member', 'settings'], true)) $initialTab = 'member';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>動画管理（統合） - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/assets/js/hinata-member-groups.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex ?? '#0ea5e9') ?>; }
        .video-row { transition: background-color 0.15s ease-out, border-color 0.15s ease-out; }
        .video-row:hover { background: #f1f5f9; }
        .video-row.selected { border-left: 4px solid var(--hinata-theme); background: #e0f2fe; }
        #toast {
            position: fixed; top: 1rem; left: 50%; transform: translateX(-50%) translateY(-120%);
            z-index: 9999; padding: 0.75rem 1.5rem; background: #0f766e; color: white;
            font-size: 0.875rem; font-weight: 700; border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(15, 118, 110, 0.4);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease-out;
            pointer-events: none; opacity: 0;
        }
        #toast.visible { transform: translateX(-50%) translateY(0); opacity: 1; }
        #linkedSongDisplay { min-height: 3rem; }
        .btn-unlink-hidden { visibility: hidden; }
        #selectedDescriptionContainer { max-height: 200px; overflow-y: auto; }
        .selected-desc { max-height: 3.6em; overflow: hidden; white-space: pre-wrap; line-height: 1.4; }
        .selected-desc.expanded { max-height: none; }
        @media (max-width: 767px) {
            .media-admin-wrap { flex-direction: column; }
            .media-admin-wrap .video-list-section { flex: 0 0 auto; max-height: 45vh; min-height: 200px; display: flex; flex-direction: column; }
            .media-admin-wrap .video-list-section #videoList { min-height: 0; }
            .media-admin-wrap .right-panel-section { flex: 1 1 0; min-height: 280px; overflow: hidden; display: flex; flex-direction: column; }
            .media-admin-wrap .right-panel-section .right-scroll { min-height: 0; overflow: auto; -webkit-overflow-scrolling: touch; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?? '' ?>"<?= ($bodyStyle ?? '') ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <div id="toast" role="status" aria-live="polite"></div>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?? 'border-slate-100' ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?? 'bg-sky-500' ?> <?= $headerShadow ?? '' ?>"<?= ($headerIconStyle ?? '') ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-video text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画管理（統合）</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?? 'text-sky-600' ?> <?= $cardIconBg ?? 'bg-sky-50' ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= ($cardIconStyle ?? '') ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                ポータルへ戻る
            </a>
        </header>

        <div class="media-admin-wrap flex-1 flex flex-col md:flex-row min-h-0">
            <!-- 左：動画一覧（共通） -->
            <section class="video-list-section w-full md:w-96 shrink-0 border-r <?= $cardBorder ?? 'border-slate-100' ?> bg-white flex flex-col min-h-0">
                <div class="p-4 border-b <?= $cardBorder ?? 'border-slate-100' ?> space-y-3">
                    <input type="text" id="searchVideo" placeholder="動画を検索..." class="w-full h-10 px-4 border <?= $cardBorder ?? 'border-slate-200' ?> rounded-lg text-sm outline-none focus:ring-2 <?= !empty($isThemeHex) ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-sky-200' ?>">
                    <select id="filterCategory" class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none bg-slate-50">
                        <option value="">カテゴリ: すべて</option>
                        <option value="__unset__">カテゴリ: 未設定</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterPlatform" class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none bg-slate-50">
                        <option value="">プラットフォーム: すべて</option>
                        <option value="youtube">YouTube</option>
                        <option value="instagram">Instagram</option>
                        <option value="tiktok">TikTok</option>
                    </select>
                    <select id="filterMediaType" class="w-full h-10 px-4 border border-sky-100 rounded-lg text-sm outline-none bg-slate-50">
                        <option value="">種別: すべて</option>
                        <option value="video">動画</option>
                        <option value="short">ショート</option>
                        <option value="live">ライブ</option>
                    </select>
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-slate-600 hover:text-slate-800">
                        <input type="checkbox" id="filterUnlinkedOnly" class="rounded border-sky-200 text-sky-500 focus:ring-sky-300">
                        <span id="unlinkedLabel">未紐づけの動画のみ</span>
                    </label>
                    <button id="btnSearch" class="w-full h-10 bg-sky-500 text-white rounded-lg text-sm font-bold hover:bg-sky-600 transition">
                        <i class="fa-solid fa-search mr-2"></i>検索
                    </button>
                </div>
                <div id="videoList" class="flex-1 overflow-y-auto p-2">
                    <p class="text-slate-400 text-sm text-center py-8">読み込み中...</p>
                </div>
            </section>

            <!-- 右：タブ別編集 -->
            <section class="right-panel-section flex-1 flex flex-col min-w-0 min-h-0 bg-slate-50/50">
                <div class="shrink-0 bg-white border-b <?= $cardBorder ?? 'border-slate-100' ?>">
                    <div class="px-4 md:px-6 pt-3">
                        <div class="inline-flex bg-slate-100 rounded-xl p-1 gap-1">
                            <button type="button" data-tab="member" class="tab-btn px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:text-slate-900 hover:bg-white transition">
                                <i class="fa-solid fa-users mr-1.5"></i>メンバー
                            </button>
                            <button type="button" data-tab="song" class="tab-btn px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:text-slate-900 hover:bg-white transition">
                                <i class="fa-solid fa-music mr-1.5"></i>楽曲
                            </button>
                            <button type="button" data-tab="settings" class="tab-btn px-3 py-2 rounded-lg text-xs font-black text-slate-600 hover:text-slate-900 hover:bg-white transition">
                                <i class="fa-solid fa-sliders mr-1.5"></i>設定
                            </button>
                        </div>
                    </div>
                    <div class="p-4 md:p-6">
                        <div class="flex gap-4 items-start">
                            <div id="selectedThumb" class="w-24 md:w-32 shrink-0 aspect-video rounded-lg overflow-hidden bg-slate-200 cursor-pointer group relative" onclick="MediaAdmin.playSelectedVideo(event)">
                                <img id="selectedThumbImg" src="" alt="" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <i class="fa-solid fa-play text-white text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span id="selectedPlatformBadge" class="hidden inline-block px-2 py-0.5 rounded text-[10px] font-bold text-white"></span>
                                    <span id="selectedMediaTypeBadge" class="hidden inline-block px-2 py-0.5 rounded text-[10px] font-bold"></span>
                                    <span id="selectedCategory" class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-700"></span>
                                </div>
                                <h2 id="selectedTitle" class="text-lg font-bold text-slate-800 truncate"></h2>
                                <p id="selectedDate" class="text-xs text-slate-400 mt-1"></p>
                                <div id="selectedDescriptionContainer" class="mt-2 text-xs text-slate-500 hidden">
                                    <p id="selectedDescription" class="selected-desc"></p>
                                    <button type="button" id="btnToggleSelectedDesc" class="mt-0.5 text-[10px] text-sky-600 font-bold hover:underline">...もっと見る</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="noSelection" class="flex-1 flex items-center justify-center text-slate-400">
                    <div class="text-center">
                        <i class="fa-solid fa-video text-6xl mb-4 opacity-30"></i>
                        <p class="text-sm font-bold">左の一覧から動画を選択してください</p>
                        <p class="text-xs mt-2">タブを切り替えて、楽曲・メンバー・設定を編集できます</p>
                    </div>
                </div>

                <div id="rightTabs" class="hidden flex flex-1 min-h-0">
                    <!-- ===== member tab ===== -->
                    <div id="tabPanel_member" class="tab-panel flex-1 flex flex-col min-h-0">
                        <div class="right-scroll flex-1 overflow-y-auto p-4 md:p-6">
                            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-sm font-bold text-slate-700">出演メンバーを選択（チェックボックス）</h3>
                                    <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fa-solid fa-music text-sky-500"></i>
                                            <span id="linkedSongInfo" class="font-bold">紐づく楽曲: なし</span>
                                        </span>
                                        <button id="btnReflectSongMembers" type="button" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-500 text-white font-bold hover:bg-emerald-600 text-[11px] shadow-sm">
                                            <i class="fa-solid fa-users-line"></i>
                                            <span>楽曲メンバーから反映</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border border-sky-200 bg-sky-50 text-xs font-bold text-sky-600 hover:bg-sky-100 transition">
                                        <input type="checkbox" id="selectAllActive" class="rounded border-sky-200 text-sky-500">
                                        現役メンバー一括選択
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border border-sky-100 text-xs font-bold text-slate-600 hover:bg-sky-50 transition">
                                        <input type="checkbox" id="toggleGraduates" checked class="rounded border-sky-200 text-sky-500">
                                        <span id="toggleGraduatesLabel">卒業生を表示</span>
                                    </label>
                                </div>
                            </div>

                            <div id="memberCheckboxList" class="space-y-6 mb-6"></div>

                            <div class="flex items-center gap-3">
                                <button id="btnAutoDetect" class="h-10 px-5 bg-amber-500 text-white rounded-full text-sm font-bold hover:bg-amber-600 transition shadow-lg shadow-amber-200">
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>本文から自動検出
                                </button>
                                <button id="btnSaveMembers" class="h-10 px-6 bg-sky-500 text-white rounded-full text-sm font-bold hover:bg-sky-600 transition shadow-lg shadow-sky-200">
                                    <i class="fa-solid fa-check mr-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== song tab ===== -->
                    <div id="tabPanel_song" class="tab-panel hidden flex-1 flex flex-col min-h-0">
                        <div class="right-scroll flex-1 overflow-y-auto p-4 md:p-6">
                            <div class="mb-6">
                                <h3 class="text-sm font-bold text-slate-700 mb-2">紐づいている楽曲</h3>
                                <div id="linkedSongDisplay" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-600 text-sm flex items-center justify-between">
                                    <span id="linkedSongTitle">未紐付け</span>
                                    <button id="btnUnlink" type="button" class="btn-unlink-hidden h-8 px-3 rounded-lg text-[11px] font-bold bg-slate-200 hover:bg-slate-300 text-slate-700 transition">
                                        紐付けを解除
                                    </button>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h3 class="text-sm font-bold text-slate-700 mb-2">ハッシュタグ（初参戦ガイド用）</h3>
                                <p class="text-[10px] text-slate-500 mb-1">カンマ区切りで入力（#は不要）例: 七回目のひな誕祭</p>
                                <div class="flex gap-2">
                                    <input type="text" id="hashtagsInput" placeholder="七回目のひな誕祭" class="flex-1 h-10 px-4 border <?= $cardBorder ?? 'border-slate-200' ?> rounded-lg text-sm outline-none focus:ring-2 <?= !empty($isThemeHex) ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-sky-200' ?>">
                                    <button type="button" id="btnSaveHashtags" class="shrink-0 h-10 px-4 rounded-lg text-xs font-bold bg-sky-500 hover:bg-sky-600 text-white transition">保存</button>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h3 class="text-sm font-bold text-slate-700 mb-2">楽曲を選択して紐づける</h3>
                                <div class="mb-3">
                                    <input type="text" id="searchSong" placeholder="楽曲・リリース名で検索..." class="w-full h-10 px-4 border <?= $cardBorder ?? 'border-slate-200' ?> rounded-lg text-sm outline-none focus:ring-2 <?= !empty($isThemeHex) ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-sky-200' ?>">
                                </div>
                                <div id="songsByRelease" class="space-y-4 border <?= $cardBorder ?? 'border-slate-200' ?> rounded-lg p-3 bg-white max-h-[50vh] overflow-y-auto">
                                    <?php foreach ($releasesWithSongs as $rel): ?>
                                        <?php if (!empty($rel['songs'])): ?>
                                        <div class="release-group" data-release-title="<?= htmlspecialchars($rel['title'] ?? '') ?>" data-release-number="<?= htmlspecialchars($rel['release_number'] ?? '') ?>">
                                            <h4 class="text-xs font-black text-sky-600 tracking-wider mb-2 pb-1 border-b border-sky-100">
                                                <?= htmlspecialchars($rel['release_number'] ?? '') ?> <?= htmlspecialchars($rel['title'] ?? '') ?>
                                            </h4>
                                            <div class="space-y-1">
                                                <?php foreach ($rel['songs'] as $s): ?>
                                                <div class="song-row flex items-center justify-between gap-2 p-2 rounded-lg hover:bg-sky-50"
                                                     data-song-title="<?= htmlspecialchars($s['title'] ?? '') ?>"
                                                     data-track-type="<?= htmlspecialchars(($trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? '')) ?>">
                                                    <span class="text-sm text-slate-800"><?= htmlspecialchars($s['title'] ?? '') ?>
                                                        <?php $tt = $trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? ''; if ($tt): ?>
                                                        <span class="text-slate-500 text-xs">(<?= htmlspecialchars($tt) ?>)</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <button type="button" class="btn-link-song shrink-0 h-8 px-3 rounded-lg text-xs font-bold bg-sky-500 hover:bg-sky-600 text-white transition" data-song-id="<?= (int)$s['id'] ?>">この楽曲に紐づける</button>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== settings tab ===== -->
                    <div id="tabPanel_settings" class="tab-panel hidden flex-1 flex flex-col min-h-0">
                        <div class="right-scroll flex-1 overflow-y-auto p-4 md:p-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">動画の設定</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-2">カテゴリ</label>
                                    <select id="editCategory" class="w-full max-w-xs h-10 px-4 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200 bg-white">
                                        <option value="">（未設定）</option>
                                        <?php foreach ($categories as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-2">動画種別</label>
                                    <select id="editMediaType" class="w-full max-w-xs h-10 px-4 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200 bg-white">
                                        <option value="">（未設定）</option>
                                        <option value="video">動画</option>
                                        <option value="short">ショート</option>
                                        <option value="live">ライブ</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-2">アップロード日</label>
                                    <input type="date" id="editUploadDate" class="w-full max-w-xs h-10 px-4 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200 bg-white">
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-xs font-bold text-slate-600 mb-2">サムネイル画像</label>
                                <div class="flex items-center gap-4">
                                    <div id="thumbPreviewWrap" class="w-32 aspect-video rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                        <img id="thumbPreview" src="" alt="" class="w-full h-full object-cover hidden">
                                        <div id="thumbPlaceholder" class="w-full h-full flex items-center justify-center text-slate-400 text-[10px]">No Image</div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="inline-flex items-center gap-2 h-9 px-4 bg-purple-500 text-white rounded-lg text-xs font-bold hover:bg-purple-600 transition cursor-pointer">
                                            <i class="fa-solid fa-upload"></i> 画像をアップロード
                                            <input type="file" id="thumbFileInput" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
                                        </label>
                                        <p id="thumbUploadStatus" class="text-[10px] text-slate-400"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center gap-3">
                                <button id="btnSaveSettings" class="h-10 px-6 bg-sky-500 text-white rounded-full text-sm font-bold hover:bg-sky-600 transition shadow-lg shadow-sky-200">
                                    <i class="fa-solid fa-check mr-2"></i>保存
                                </button>
                                <button id="btnDelete" class="h-10 px-5 bg-white border border-red-200 text-red-500 rounded-full text-sm font-bold hover:bg-red-50 hover:border-red-300 transition">
                                    <i class="fa-solid fa-trash mr-1"></i>削除
                                </button>
                            </div>

                            <hr class="my-8 border-slate-200">

                            <h3 class="text-sm font-bold text-slate-700 mb-3">カテゴリ管理</h3>
                            <div class="space-y-3">
                                <div class="flex gap-2">
                                    <input type="text" id="newCategoryName" placeholder="新規カテゴリ名" class="flex-1 h-9 px-3 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-200">
                                    <button id="btnAddCategory" class="h-9 px-4 bg-emerald-500 text-white rounded-lg text-sm font-bold hover:bg-emerald-600 transition shrink-0">
                                        <i class="fa-solid fa-plus mr-1"></i>追加
                                    </button>
                                </div>
                                <ul id="categoryList" class="space-y-1.5">
                                    <?php foreach ($categories as $key => $label): ?>
                                    <li class="flex items-center gap-2 group" data-name="<?= htmlspecialchars($key) ?>">
                                        <span class="category-name flex-1 text-sm font-bold text-slate-700"><?= htmlspecialchars($label) ?></span>
                                        <button type="button" class="btnRenameCategory opacity-0 group-hover:opacity-100 h-7 px-2 text-xs font-bold text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded transition" title="名称変更">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/../../../components/video_modal.php'; ?>

    <!-- 楽曲選択モーダル（メンバータブ用） -->
    <div id="songSelectModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-40 hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full mx-4 max-h-[80vh] flex flex-col overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 backdrop-blur-sm">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center bg-sky-500 text-white text-xs">
                        <i class="fa-solid fa-music"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 leading-tight">楽曲を選択して動画に紐づけ</p>
                        <p class="text-[11px] text-slate-400 leading-tight">リリース → 楽曲を選ぶと、その楽曲メンバーを動画に反映します</p>
                    </div>
                </div>
                <button id="btnCloseSongModal" type="button" class="text-slate-400 hover:text-slate-600 text-lg px-2">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="px-5 pt-3 pb-4 border-b border-slate-100">
                <input type="text" id="songModalSearch" placeholder="リリース名・楽曲名・種別で絞り込み..." class="w-full h-9 px-3 border <?= $cardBorder ?? 'border-slate-200' ?> rounded-lg text-xs outline-none focus:ring-2 <?= !empty($isThemeHex) ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-sky-200' ?>">
            </div>
            <div class="flex-1 flex flex-col md:flex-row min-h-0">
                <div class="md:w-1/2 border-r <?= $cardBorder ?? 'border-slate-100' ?> bg-slate-50/60 min-h-[180px] max-h-[60vh] overflow-y-auto" id="songModalReleaseList">
                    <?php foreach ($releasesWithSongs as $rel): ?>
                        <div class="song-modal-release border-b border-slate-100" data-release-title="<?= htmlspecialchars($rel['title'] ?? '') ?>" data-release-number="<?= htmlspecialchars($rel['release_number'] ?? '') ?>">
                            <button type="button" class="w-full text-left px-4 py-3 hover:bg-sky-50 flex items-center justify-between gap-2 song-modal-release-toggle">
                                <div>
                                    <p class="text-[11px] font-bold text-sky-600">
                                        <?= htmlspecialchars($rel['release_number'] ?? '') ?> <?= htmlspecialchars($rel['title'] ?? '') ?>
                                    </p>
                                </div>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                            </button>
                            <div class="song-modal-release-songs border-t border-slate-100 hidden">
                                <?php foreach (($rel['songs'] ?? []) as $s): ?>
                                    <div class="flex items-center justify-between gap-2 px-5 py-2 hover:bg-sky-50 song-modal-song-row"
                                         data-song-title="<?= htmlspecialchars($s['title'] ?? '') ?>"
                                         data-track-type="<?= htmlspecialchars(($trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? '')) ?>">
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-800 truncate">
                                                <?= htmlspecialchars($s['title'] ?? '') ?>
                                                <?php $tt = $trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? ''; if ($tt): ?>
                                                    <span class="text-[10px] text-slate-500">(<?= htmlspecialchars($tt) ?>)</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <button type="button"
                                                class="song-modal-link-btn shrink-0 h-7 px-3 rounded-full text-[11px] font-bold bg-sky-500 hover:bg-sky-600 text-white transition"
                                                data-song-id="<?= (int)$s['id'] ?>">
                                            この曲を選ぶ
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="md:w-1/2 min-h-[180px] max-h-[60vh] overflow-y-auto bg-white hidden md:flex items-center justify-center text-xs text-slate-400" id="songModalHint">
                    <div class="px-6 py-4 text-center">
                        <p class="font-bold mb-1">左からリリースを選択し、楽曲をクリックしてください。</p>
                        <p>選択した楽曲のメンバー構成が、この動画の出演メンバー候補として反映されます。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const MediaAdmin = (() => {
            const state = {
                activeTab: <?= json_encode($initialTab) ?>,
                selectedMetaId: null,
                selectedVideo: null,
                linkedSong: null,
                showGraduates: true,
                checkedMemberIds: new Set(),
            };

            const trackTypesDisplay = <?= json_encode($trackTypesDisplay ?? []) ?>;
            const allMembers = <?= json_encode(array_map(fn($m) => [
                'id' => (int)$m['id'],
                'name' => $m['name'],
                'kana' => $m['kana'] ?? '',
                'generation' => (int)($m['generation'] ?? 0),
                'is_active' => (int)($m['is_active'] ?? 1)
            ], $members ?? [])) ?>;

            // Common elements
            const videoList = document.getElementById('videoList');
            const noSelection = document.getElementById('noSelection');
            const rightTabs = document.getElementById('rightTabs');
            const searchVideo = document.getElementById('searchVideo');
            const filterCategory = document.getElementById('filterCategory');
            const filterPlatform = document.getElementById('filterPlatform');
            const filterMediaType = document.getElementById('filterMediaType');
            const filterUnlinkedOnly = document.getElementById('filterUnlinkedOnly');
            const unlinkedLabel = document.getElementById('unlinkedLabel');
            const btnSearch = document.getElementById('btnSearch');

            function showToast(msg) {
                const toast = document.getElementById('toast');
                toast.textContent = msg;
                toast.classList.add('visible');
                clearTimeout(showToast._tid);
                showToast._tid = setTimeout(() => toast.classList.remove('visible'), 2500);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function getThumbnailUrl(v) {
                if (!v) return '';
                if (v.thumbnail_url) return v.thumbnail_url;
                if (v.platform === 'youtube' && v.media_key) return 'https://img.youtube.com/vi/' + v.media_key + '/mqdefault.jpg';
                return '';
            }

            function platformBadge(p) {
                const c = { youtube: { bg: 'bg-red-500', l: 'YT' }, instagram: { bg: 'bg-gradient-to-r from-purple-500 to-pink-500', l: 'IG' }, tiktok: { bg: 'bg-slate-800', l: 'TT' } }[p];
                return c ? `<span class="text-[9px] font-bold text-white px-1.5 py-0.5 rounded ${c.bg}">${c.l}</span>` : '';
            }
            function mediaTypeBadge(mt) {
                const c = { video: { cls: 'bg-blue-100 text-blue-700', l: '動画' }, short: { cls: 'bg-amber-100 text-amber-700', l: 'ショート' }, live: { cls: 'bg-red-100 text-red-700', l: 'ライブ' } }[mt];
                return c ? `<span class="text-[9px] font-bold px-1.5 py-0.5 rounded ${c.cls}">${c.l}</span>` : '';
            }

            function setPlatformBadgeEl(el, platform) {
                const cfg = {
                    youtube:   { bg: 'bg-red-500', label: 'YouTube' },
                    instagram: { bg: 'bg-gradient-to-r from-purple-500 to-pink-500', label: 'Instagram' },
                    tiktok:    { bg: 'bg-slate-800', label: 'TikTok' },
                };
                const c = cfg[platform] || { bg: 'bg-slate-400', label: platform || '' };
                el.className = 'inline-block px-2 py-0.5 rounded text-[10px] font-bold text-white ' + c.bg;
                el.textContent = c.label;
                el.classList.toggle('hidden', !c.label);
            }
            function setMediaTypeBadgeEl(el, mt) {
                const cfg = {
                    video: { cls: 'bg-blue-100 text-blue-700', label: '動画' },
                    short: { cls: 'bg-amber-100 text-amber-700', label: 'ショート' },
                    live:  { cls: 'bg-red-100 text-red-700', label: 'ライブ' },
                };
                const c = cfg[mt];
                if (!c) { el.classList.add('hidden'); el.textContent = ''; return; }
                el.className = 'inline-block px-2 py-0.5 rounded text-[10px] font-bold ' + c.cls;
                el.textContent = c.label;
                el.classList.remove('hidden');
            }

            function updateUnlinkedLabel() {
                const map = { member: '未紐づけの動画のみ', song: '未紐づけの動画のみ', settings: '未紐づけフィルタ（設定タブでは無効）' };
                unlinkedLabel.textContent = map[state.activeTab] || '未紐づけの動画のみ';

                // settings タブでは、バックエンドの unlinked_only 判定が「楽曲リンク有無」基準になり得て誤解を招くため無効化
                const isSettings = state.activeTab === 'settings';
                if (filterUnlinkedOnly) {
                    filterUnlinkedOnly.disabled = isSettings;
                    if (isSettings) filterUnlinkedOnly.checked = false;
                    filterUnlinkedOnly.closest('label')?.classList.toggle('opacity-50', isSettings);
                    filterUnlinkedOnly.closest('label')?.classList.toggle('cursor-not-allowed', isSettings);
                }
            }

            function setActiveTab(tab) {
                state.activeTab = tab;
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    const t = btn.getAttribute('data-tab');
                    const active = (t === tab);
                    btn.classList.toggle('bg-white', active);
                    btn.classList.toggle('text-slate-900', active);
                    btn.classList.toggle('shadow-sm', active);
                });
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                const panel = document.getElementById('tabPanel_' + tab);
                panel && panel.classList.remove('hidden');
                updateUnlinkedLabel();
                loadVideos(true);
                // 選択中の動画がある場合、タブ固有データを更新
                if (state.selectedMetaId) {
                    onVideoSelected();
                }
            }

            async function loadVideos(keepSelection = false) {
                const params = new URLSearchParams({
                    q: searchVideo.value,
                    category: filterCategory.value,
                    platform: filterPlatform.value,
                    media_type: filterMediaType.value,
                    limit: 200,
                    link_type: state.activeTab,
                });
                if (filterUnlinkedOnly && !filterUnlinkedOnly.disabled && filterUnlinkedOnly.checked) params.set('unlinked_only', '1');
                const res = await fetch('/hinata/api/list_media_for_link.php?' + params);
                const json = await res.json();
                if (json.status !== 'success') {
                    videoList.innerHTML = '<p class="text-red-500 text-sm text-center py-4">エラー: ' + escapeHtml(json.message || '') + '</p>';
                    return;
                }
                const data = json.data || [];
                if (data.length === 0) {
                    videoList.innerHTML = '<p class="text-slate-400 text-sm text-center py-8">該当する動画がありません</p>';
                    if (!keepSelection) clearSelection();
                    return;
                }
                videoList.innerHTML = data.map(v => {
                    const dataStr = JSON.stringify(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                    const thumbUrl = getThumbnailUrl(v);
                    return `<div class="video-row cursor-pointer px-3 py-2 rounded-lg flex items-center gap-3 border-b border-slate-50 transition" data-meta-id="${v.meta_id}" data-video="${dataStr}">
                        <div class="w-12 shrink-0 aspect-video rounded overflow-hidden bg-slate-100">
                            <img src="${thumbUrl}" alt="" class="w-full h-full object-cover" onerror="this.src=''">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1 mb-0.5">
                                ${platformBadge(v.platform)}
                                ${mediaTypeBadge(v.media_type)}
                                <span class="text-[10px] font-bold text-sky-600">${v.category || ''}</span>
                            </div>
                            <p class="text-sm font-bold text-slate-800 truncate">${escapeHtml(v.title || '')}</p>
                        </div>
                    </div>`;
                }).join('');
                videoList.querySelectorAll('.video-row').forEach(row => row.onclick = () => selectVideo(row));

                if (keepSelection && state.selectedMetaId) {
                    const el = videoList.querySelector(`[data-meta-id="${state.selectedMetaId}"]`);
                    if (el) el.classList.add('selected');
                } else if (!keepSelection) {
                    clearSelection();
                }
            }

            function clearSelection() {
                state.selectedMetaId = null;
                state.selectedVideo = null;
                state.linkedSong = null;
                noSelection.classList.remove('hidden');
                rightTabs.classList.add('hidden');
                document.querySelectorAll('.video-row').forEach(r => r.classList.remove('selected'));
            }

            function applySelectedHeader(video) {
                document.getElementById('selectedThumbImg').src = getThumbnailUrl(video) || '';
                document.getElementById('selectedCategory').textContent = video.category || '（未設定）';
                document.getElementById('selectedTitle').textContent = video.title || '';
                const primaryDate = video.upload_date || video.release_date || '';
                document.getElementById('selectedDate').textContent = primaryDate ? new Date(primaryDate).toLocaleDateString('ja-JP') : '';

                setPlatformBadgeEl(document.getElementById('selectedPlatformBadge'), video.platform);
                setMediaTypeBadgeEl(document.getElementById('selectedMediaTypeBadge'), video.media_type);

                const descContainer = document.getElementById('selectedDescriptionContainer');
                const descEl = document.getElementById('selectedDescription');
                const toggleBtn = document.getElementById('btnToggleSelectedDesc');
                if (video.description && video.description.trim() !== '') {
                    descEl.textContent = video.description;
                    descEl.classList.remove('expanded');
                    toggleBtn.textContent = '...もっと見る';
                    descContainer.classList.remove('hidden');
                } else {
                    descContainer.classList.add('hidden');
                    descEl.textContent = '';
                }
            }

            async function selectVideo(rowEl) {
                document.querySelectorAll('.video-row').forEach(r => r.classList.remove('selected'));
                rowEl.classList.add('selected');
                const dataStr = rowEl.getAttribute('data-video');
                const video = JSON.parse(dataStr ? dataStr.replace(/&quot;/g, '"').replace(/&amp;/g, '&') : '{}');
                state.selectedMetaId = video.meta_id;
                state.selectedVideo = video;
                applySelectedHeader(video);
                noSelection.classList.add('hidden');
                rightTabs.classList.remove('hidden');
                await onVideoSelected();
                if (window.innerWidth < 768) {
                    setTimeout(() => rightTabs.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
                }
            }

            async function onVideoSelected() {
                // member tab
                await member_loadLinkedMembers();
                await member_loadLinkedSongForInfo();
                // song tab
                await song_loadLinkedSong();
                await song_loadHashtags();
                // settings tab
                settings_applySelected();
            }

            function playSelectedVideo(ev) {
                if (!state.selectedVideo) return;
                openVideoModalWithData(state.selectedVideo, ev);
            }

            // --- Member tab ---
            const memberCheckboxList = document.getElementById('memberCheckboxList');
            const btnSaveMembers = document.getElementById('btnSaveMembers');
            const btnAutoDetect = document.getElementById('btnAutoDetect');
            const btnReflectSongMembers = document.getElementById('btnReflectSongMembers');
            const linkedSongInfo = document.getElementById('linkedSongInfo');

            async function member_loadLinkedMembers() {
                if (!state.selectedMetaId) return;
                const res = await fetch(`/hinata/api/get_media_members.php?meta_id=${state.selectedMetaId}`);
                const json = await res.json();
                const linkedIds = (json.status === 'success' && json.data) ? json.data.map(m => m.id) : [];
                state.checkedMemberIds = new Set(linkedIds.map(Number));
                member_renderMemberCheckboxes();
            }

            async function member_loadLinkedSongForInfo() {
                if (!state.selectedMetaId || !linkedSongInfo) return;
                try {
                    const res = await fetch(`/hinata/api/get_song_members_for_media.php?meta_id=${state.selectedMetaId}`);
                    const json = await res.json();
                    if (json.status !== 'success') {
                        linkedSongInfo.textContent = '紐づく楽曲: 取得エラー';
                        return;
                    }
                    const song = json.song;
                    if (!song) {
                        linkedSongInfo.textContent = '紐づく楽曲: なし';
                    } else {
                        const labelParts = [];
                        if (song.release_number) labelParts.push(song.release_number);
                        if (song.release_title) labelParts.push(song.release_title);
                        const head = labelParts.length > 0 ? labelParts.join(' ') + ' / ' : '';
                        linkedSongInfo.textContent = '紐づく楽曲: ' + head + (song.title || '');
                    }
                } catch (_) {
                    linkedSongInfo.textContent = '紐づく楽曲: 取得エラー';
                }
            }

            function member_renderMemberCheckboxes() {
                const ids = state.checkedMemberIds;
                const membersToShow = state.showGraduates ? allMembers : allMembers.filter(m => m.is_active);
                const grouped = HinataMemberGroups.group(membersToShow);
                if (grouped.order.length === 0 && grouped.graduates.length === 0) {
                    memberCheckboxList.innerHTML = '<p class="text-slate-400 text-sm py-4">メンバーが登録されていません</p>';
                    return;
                }
                let html = grouped.order.map(gen => {
                    const members = (grouped.active[gen] || []).sort((a, b) => (a.kana || a.name).localeCompare(b.kana || b.name, 'ja'));
                    const memberIds = members.map(m => m.id);
                    const allChecked = memberIds.every(id => ids.has(id));
                    const items = members.map(m => {
                        const checked = ids.has(m.id) ? ' checked' : '';
                        const gradBadge = !m.is_active ? ' <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">卒業</span>' : '';
                        const activeClass = m.is_active ? ' member-cb-active' : '';
                        return `
                            <label class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-sky-50 cursor-pointer transition">
                                <input type="checkbox" class="member-cb member-cb-gen-${gen}${activeClass} rounded border-sky-200 text-sky-500 focus:ring-sky-300" value="${m.id}" data-gen="${gen}" data-active="${m.is_active ? 1 : 0}"${checked}>
                                <span class="text-sm font-bold text-slate-800">${escapeHtml(m.name)}</span>${gradBadge}
                            </label>
                        `;
                    }).join('');
                    return `
                        <div class="gen-group" data-gen="${gen}">
                            <div class="flex items-center justify-between mb-2 pb-1 border-b border-sky-100">
                                <h4 class="text-xs font-black text-sky-600 tracking-wider">${HinataMemberGroups.getGenLabel(gen)}</h4>
                                <label class="flex items-center gap-2 cursor-pointer text-[11px] font-bold text-sky-600 hover:text-sky-700">
                                    <input type="checkbox" class="gen-all-cb rounded border-sky-200 text-sky-500" data-gen="${gen}"${allChecked ? ' checked' : ''}>
                                    期別でまとめて選択
                                </label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">${items}</div>
                        </div>
                    `;
                }).join('');
                if (grouped.graduates.length > 0) {
                    const gradIds = grouped.graduates.map(m => m.id);
                    const allGradChecked = gradIds.every(id => ids.has(id));
                    const gradItems = grouped.graduates.map(m => {
                        const checked = ids.has(m.id) ? ' checked' : '';
                        const gradBadge = ' <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">卒業</span>';
                        return `<label class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-sky-50 cursor-pointer transition">
                            <input type="checkbox" class="member-cb member-cb-gen-graduated rounded border-sky-200 text-sky-500 focus:ring-sky-300" value="${m.id}" data-gen="graduated" data-active="0"${checked}>
                            <span class="text-sm font-bold text-slate-800">${escapeHtml(m.name)}</span>${gradBadge}
                        </label>`;
                    }).join('');
                    html += `<div class="gen-group mt-4 pt-4 border-t border-slate-200" data-gen="graduated">
                        <div class="flex items-center justify-between mb-2 pb-1 border-b border-sky-100">
                            <h4 class="text-xs font-black text-slate-500 tracking-wider">卒業生</h4>
                            <label class="flex items-center gap-2 cursor-pointer text-[11px] font-bold text-sky-600 hover:text-sky-700">
                                <input type="checkbox" class="gen-all-cb rounded border-sky-200 text-sky-500" data-gen="graduated"${allGradChecked ? ' checked' : ''}>
                                まとめて選択
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">${gradItems}</div>
                    </div>`;
                }
                memberCheckboxList.innerHTML = html;

                memberCheckboxList.querySelectorAll('.gen-all-cb').forEach(cb => {
                    cb.addEventListener('change', () => {
                        const gen = cb.getAttribute('data-gen');
                        memberCheckboxList.querySelectorAll(`.member-cb-gen-${gen}`).forEach(mcb => {
                            mcb.checked = cb.checked;
                            const id = parseInt(mcb.value);
                            if (cb.checked) state.checkedMemberIds.add(id); else state.checkedMemberIds.delete(id);
                        });
                        member_updateSelectAllActiveState();
                    });
                });
                memberCheckboxList.querySelectorAll('.member-cb').forEach(mcb => {
                    mcb.addEventListener('change', () => {
                        const id = parseInt(mcb.value);
                        if (mcb.checked) state.checkedMemberIds.add(id); else state.checkedMemberIds.delete(id);
                        const gen = mcb.getAttribute('data-gen');
                        const groupCbs = memberCheckboxList.querySelectorAll(`.member-cb-gen-${gen}`);
                        const genAllCb = memberCheckboxList.querySelector(`.gen-all-cb[data-gen="${gen}"]`);
                        if (genAllCb) genAllCb.checked = Array.from(groupCbs).every(c => c.checked);
                        member_updateSelectAllActiveState();
                    });
                });
                member_updateSelectAllActiveState();
            }

            function member_updateSelectAllActiveState() {
                const selectAllActive = document.getElementById('selectAllActive');
                if (!selectAllActive) return;
                const activeIds = allMembers.filter(m => m.is_active).map(m => m.id);
                const allActiveChecked = activeIds.length > 0 && activeIds.every(id => state.checkedMemberIds.has(id));
                selectAllActive.checked = allActiveChecked;
            }

            async function member_fetchAndApplySongMembers() {
                if (!state.selectedMetaId) { showToast('動画を選択してください'); return; }
                const res = await fetch(`/hinata/api/get_song_members_for_media.php?meta_id=${state.selectedMetaId}`);
                const json = await res.json();
                if (json.status !== 'success') { alert('楽曲メンバー取得エラー: ' + (json.message || '')); return; }
                const members = Array.isArray(json.members) ? json.members : [];
                if (members.length === 0) { showToast('この動画には紐づく楽曲メンバーがありません'); return; }
                let added = 0;
                members.forEach(m => {
                    const mid = Number(m.member_id);
                    if (!state.checkedMemberIds.has(mid)) added++;
                    state.checkedMemberIds.add(mid);
                });
                member_renderMemberCheckboxes();
                showToast(`楽曲メンバー ${members.length}人を反映しました（新規追加 ${added}人）`);
            }

            // Song select modal (for member tab)
            const songSelectModal = document.getElementById('songSelectModal');
            const btnCloseSongModal = document.getElementById('btnCloseSongModal');
            const songModalSearch = document.getElementById('songModalSearch');
            const songModalReleaseList = document.getElementById('songModalReleaseList');
            const songModalHint = document.getElementById('songModalHint');

            function openSongSelectModal() {
                if (!songSelectModal) return;
                songSelectModal.classList.remove('hidden');
                if (songModalHint && window.innerWidth >= 768) songModalHint.classList.remove('hidden');
            }
            function closeSongSelectModal() {
                if (!songSelectModal) return;
                songSelectModal.classList.add('hidden');
            }

            // --- Song tab ---
            const linkedSongTitle = document.getElementById('linkedSongTitle');
            const btnUnlink = document.getElementById('btnUnlink');
            const hashtagsInput = document.getElementById('hashtagsInput');
            const btnSaveHashtags = document.getElementById('btnSaveHashtags');
            const searchSong = document.getElementById('searchSong');
            const songsByRelease = document.getElementById('songsByRelease');

            async function song_loadHashtags() {
                if (!hashtagsInput || !state.selectedMetaId) return;
                try {
                    const res = await fetch('/hinata/api/get_media_hashtags.php?meta_id=' + state.selectedMetaId);
                    const json = await res.json();
                    hashtagsInput.value = (json.status === 'success' && Array.isArray(json.data)) ? json.data.join(', ') : '';
                } catch (_) { hashtagsInput.value = ''; }
            }

            async function song_loadLinkedSong() {
                if (!state.selectedMetaId) return;
                const res = await fetch('/hinata/api/get_media_linked_song.php?meta_id=' + state.selectedMetaId);
                const json = await res.json();
                state.linkedSong = (json.status === 'success' && json.data) ? json.data : null;
                if (state.linkedSong) {
                    const typeLabel = trackTypesDisplay[state.linkedSong.track_type] || state.linkedSong.track_type || '';
                    linkedSongTitle.textContent = (state.linkedSong.title || '');
                    if (typeLabel) linkedSongTitle.textContent += ' (' + typeLabel + ')';
                    btnUnlink.classList.remove('btn-unlink-hidden');
                } else {
                    linkedSongTitle.textContent = '未紐付け';
                    btnUnlink.classList.add('btn-unlink-hidden');
                }
            }

            function song_applySongFilter() {
                const q = (searchSong && searchSong.value) ? searchSong.value.trim().toLowerCase() : '';
                if (!songsByRelease) return;
                songsByRelease.querySelectorAll('.release-group').forEach(function(group) {
                    const releaseTitle = (group.getAttribute('data-release-title') || '').toLowerCase();
                    const releaseNumber = (group.getAttribute('data-release-number') || '').toLowerCase();
                    let hasVisible = false;
                    group.querySelectorAll('.song-row').forEach(function(row) {
                        const songTitle = (row.getAttribute('data-song-title') || '').toLowerCase();
                        const trackType = (row.getAttribute('data-track-type') || '').toLowerCase();
                        const match = !q || releaseTitle.includes(q) || releaseNumber.includes(q) || songTitle.includes(q) || trackType.includes(q);
                        row.style.display = match ? '' : 'none';
                        if (match) hasVisible = true;
                    });
                    group.style.display = hasVisible ? '' : 'none';
                });
            }

            async function song_saveLink(songId) {
                if (!state.selectedMetaId) return;
                try {
                    const res = await fetch('/hinata/api/save_media_song_link.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ meta_id: state.selectedMetaId, song_id: songId })
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        showToast('紐付けました。');
                        await song_loadLinkedSong();
                        await member_loadLinkedSongForInfo();
                    } else {
                        alert('エラー: ' + (json.message || ''));
                    }
                } catch (e) {
                    alert('通信エラー: ' + e.message);
                }
            }

            // --- Settings tab ---
            const editCategory = document.getElementById('editCategory');
            const editMediaType = document.getElementById('editMediaType');
            const editUploadDate = document.getElementById('editUploadDate');
            const btnSaveSettings = document.getElementById('btnSaveSettings');

            function settings_applySelected() {
                if (!state.selectedVideo) return;
                editCategory.value = state.selectedVideo.category || '';
                editMediaType.value = state.selectedVideo.media_type || '';
                const ud = state.selectedVideo.upload_date || '';
                editUploadDate.value = ud ? ud.substring(0, 10) : '';

                const thumbUrl = getThumbnailUrl(state.selectedVideo);
                const thumbPreview = document.getElementById('thumbPreview');
                const thumbPlaceholder = document.getElementById('thumbPlaceholder');
                if (thumbUrl) {
                    thumbPreview.src = thumbUrl;
                    thumbPreview.classList.remove('hidden');
                    thumbPlaceholder.classList.add('hidden');
                } else {
                    thumbPreview.classList.add('hidden');
                    thumbPlaceholder.classList.remove('hidden');
                }
                document.getElementById('thumbUploadStatus').textContent = '';
                document.getElementById('thumbFileInput').value = '';
            }

            async function settings_saveSettings() {
                if (!state.selectedMetaId) return;
                const category = editCategory.value;
                const uploadDate = editUploadDate.value || null;
                const mediaType = editMediaType.value || null;
                const res = await fetch('/hinata/api/update_media_metadata.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        meta_id: state.selectedMetaId,
                        asset_id: state.selectedVideo?.asset_id || null,
                        category: category,
                        upload_date: uploadDate,
                        media_type: mediaType,
                    })
                });
                const json = await res.json();
                if (json.status !== 'success') { showToast('エラー: ' + (json.message || '保存に失敗しました')); return; }
                showToast('保存しました');
                state.selectedVideo.category = category || null;
                state.selectedVideo.media_type = mediaType || null;
                if (uploadDate) state.selectedVideo.upload_date = uploadDate;
                applySelectedHeader(state.selectedVideo);
                loadVideos(true);
            }

            async function settings_deleteMedia() {
                if (!state.selectedMetaId) return;
                if (!confirm(`「${state.selectedVideo?.title || ''}」を削除しますか？\nメンバー紐付け・楽曲紐付けも同時に削除されます。`)) return;
                const res = await fetch('/hinata/api/delete_media.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meta_id: state.selectedMetaId })
                });
                const json = await res.json();
                if (json.status !== 'success') { showToast('エラー: ' + (json.message || '削除に失敗しました')); return; }
                showToast('削除しました');
                clearSelection();
                loadVideos(false);
            }

            // Category management (settings)
            const newCategoryName = document.getElementById('newCategoryName');
            const btnAddCategory = document.getElementById('btnAddCategory');
            const categoryList = document.getElementById('categoryList');

            async function refreshCategoryDropdowns() {
                const res = await fetch('/hinata/api/list_media_categories.php');
                const json = await res.json();
                if (json.status !== 'success') return [];
                const list = json.data || [];
                const opts = ['<option value="">カテゴリ: すべて</option>', '<option value="__unset__">カテゴリ: 未設定</option>'];
                list.forEach(n => { opts.push(`<option value="${escapeHtml(n)}">${escapeHtml(n)}</option>`); });
                filterCategory.innerHTML = opts.join('');
                const editOpts = ['<option value="">（未設定）</option>'];
                list.forEach(n => { editOpts.push(`<option value="${escapeHtml(n)}">${escapeHtml(n)}</option>`); });
                editCategory.innerHTML = editOpts.join('');
                return list;
            }

            function renderCategoryList(list) {
                categoryList.innerHTML = list.map(n => `
                    <li class="flex items-center gap-2 group" data-name="${escapeHtml(n)}">
                        <span class="category-name flex-1 text-sm font-bold text-slate-700">${escapeHtml(n)}</span>
                        <button type="button" class="btnRenameCategory opacity-0 group-hover:opacity-100 h-7 px-2 text-xs font-bold text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded transition" title="名称変更">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </li>
                `).join('');
                categoryList.querySelectorAll('.btnRenameCategory').forEach(btn => btn.onclick = () => startRename(btn.closest('li')));
            }

            async function startRename(liEl) {
                const oldName = liEl.getAttribute('data-name');
                const span = liEl.querySelector('.category-name');
                const input = document.createElement('input');
                input.type = 'text';
                input.value = oldName;
                input.className = 'flex-1 h-7 px-2 border border-sky-300 rounded text-sm outline-none focus:ring-2 focus:ring-sky-200';
                const save = async () => {
                    const newName = input.value.trim();
                    input.remove();
                    liEl.insertBefore(span, liEl.firstChild);
                    span.textContent = oldName;
                    if (newName === '' || newName === oldName) return;
                    const res = await fetch('/hinata/api/rename_media_category.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ old_name: oldName, new_name: newName })
                    });
                    const json = await res.json();
                    if (json.status !== 'success') { showToast('エラー: ' + (json.message || '')); return; }
                    showToast('カテゴリ名を変更しました');
                    const list = await refreshCategoryDropdowns();
                    renderCategoryList(list);
                    if (state.selectedVideo && state.selectedVideo.category === oldName) {
                        state.selectedVideo.category = newName;
                        editCategory.value = newName;
                        applySelectedHeader(state.selectedVideo);
                    }
                };
                span.replaceWith(input);
                input.focus(); input.select();
                input.onblur = save;
                input.onkeydown = (e) => {
                    if (e.key === 'Enter') { input.onblur = null; save(); }
                    if (e.key === 'Escape') { input.onblur = null; input.replaceWith(span); span.textContent = oldName; }
                };
            }

            // Wire events
            document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar')?.classList.add('mobile-open');
            document.getElementById('btnToggleSelectedDesc').addEventListener('click', function () {
                const descEl = document.getElementById('selectedDescription');
                const expanded = descEl.classList.toggle('expanded');
                this.textContent = expanded ? '閉じる' : '...もっと見る';
            });

            document.querySelectorAll('.tab-btn').forEach(btn => btn.addEventListener('click', () => setActiveTab(btn.getAttribute('data-tab'))));

            btnSearch.onclick = () => loadVideos(true);
            searchVideo.onkeydown = (e) => { if (e.key === 'Enter') loadVideos(true); };
            filterCategory.onchange = () => loadVideos(true);
            filterPlatform.onchange = () => loadVideos(true);
            filterMediaType.onchange = () => loadVideos(true);
            filterUnlinkedOnly.addEventListener('change', () => loadVideos(true));

            // member events
            document.getElementById('toggleGraduates').addEventListener('change', (e) => {
                state.showGraduates = e.target.checked;
                document.getElementById('toggleGraduatesLabel').textContent = state.showGraduates ? '卒業生を表示' : '卒業生を非表示';
                member_renderMemberCheckboxes();
            });
            document.getElementById('selectAllActive').addEventListener('change', (e) => {
                const activeIds = allMembers.filter(m => m.is_active && m.id !== HinataMemberGroups.POKA_MEMBER_ID).map(m => m.id);
                if (e.target.checked) activeIds.forEach(id => state.checkedMemberIds.add(id));
                else activeIds.forEach(id => state.checkedMemberIds.delete(id));
                memberCheckboxList.querySelectorAll('.member-cb-active').forEach(cb => { cb.checked = e.target.checked; });
                memberCheckboxList.querySelectorAll('.gen-all-cb').forEach(genCb => {
                    const gen = genCb.getAttribute('data-gen');
                    const groupCbs = memberCheckboxList.querySelectorAll(`.member-cb-gen-${gen}`);
                    genCb.checked = Array.from(groupCbs).every(c => c.checked);
                });
            });
            btnAutoDetect.onclick = () => {
                if (!state.selectedVideo) { showToast('動画を選択してください'); return; }
                const text = (state.selectedVideo.title || '') + ' ' + (state.selectedVideo.description || '');
                if (text.trim() === '') { showToast('タイトル・本文が空です'); return; }
                let detected = 0;
                allMembers.forEach(m => {
                    const name = m.name || '';
                    if (!name) return;
                    if (text.includes(name)) {
                        if (!state.checkedMemberIds.has(m.id)) detected++;
                        state.checkedMemberIds.add(m.id);
                        return;
                    }
                    const parts = name.split(/\s+/);
                    if (parts.length === 2 && parts[1].length >= 2 && text.includes(parts[1])) {
                        if (!state.checkedMemberIds.has(m.id)) detected++;
                        state.checkedMemberIds.add(m.id);
                    }
                });
                member_renderMemberCheckboxes();
                showToast(detected > 0 ? `${detected}人のメンバーを検出しました` : '該当するメンバーが見つかりませんでした');
            };
            btnSaveMembers.onclick = async () => {
                if (!state.selectedMetaId) return;
                btnSaveMembers.disabled = true;
                try {
                    const memberIds = Array.from(state.checkedMemberIds);
                    const res = await fetch('/hinata/api/save_media_members.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ meta_id: state.selectedMetaId, member_ids: memberIds })
                    });
                    const json = await res.json();
                    if (json.status === 'success') showToast('保存しました。');
                    else alert('エラー: ' + (json.message || ''));
                } catch (e) {
                    alert('通信エラー: ' + e.message);
                } finally {
                    btnSaveMembers.disabled = false;
                }
            };

            btnReflectSongMembers.addEventListener('click', async () => {
                if (!state.selectedMetaId) { showToast('動画を選択してください'); return; }
                try {
                    const res = await fetch(`/hinata/api/get_song_members_for_media.php?meta_id=${state.selectedMetaId}`);
                    const json = await res.json();
                    if (json.status !== 'success') { alert('楽曲メンバー取得エラー: ' + (json.message || '')); return; }
                    if (!json.song) { openSongSelectModal(); return; }
                    await member_fetchAndApplySongMembers();
                } catch (e) {
                    alert('楽曲メンバー取得中にエラーが発生しました: ' + e.message);
                }
            });

            // song tab events
            if (searchSong) searchSong.addEventListener('input', song_applySongFilter);
            songsByRelease && songsByRelease.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-link-song');
                if (btn) song_saveLink(parseInt(btn.getAttribute('data-song-id'), 10));
            });
            btnUnlink.onclick = async function() {
                if (!state.selectedMetaId) return;
                if (!confirm('この動画と楽曲の紐付けを解除しますか？')) return;
                await song_saveLink(null);
            };
            btnSaveHashtags.onclick = async function() {
                if (!state.selectedMetaId) return;
                const tags = (hashtagsInput.value || '').split(/[,、\s]+/).map(t => t.replace(/^#/, '').trim()).filter(t => t);
                try {
                    const res = await fetch('/hinata/api/save_media_hashtags.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ meta_id: state.selectedMetaId, hashtags: tags })
                    });
                    const json = await res.json();
                    if (json.status === 'success') showToast('ハッシュタグを保存しました');
                    else alert('エラー: ' + (json.message || ''));
                } catch (e) {
                    alert('通信エラー: ' + e.message);
                }
            };

            // settings tab events
            btnSaveSettings.addEventListener('click', settings_saveSettings);
            document.getElementById('btnDelete').addEventListener('click', settings_deleteMedia);

            document.getElementById('thumbFileInput').addEventListener('change', async function() {
                if (!this.files.length || !state.selectedVideo) return;
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) { showToast('5MB以下の画像を選択してください'); this.value = ''; return; }
                const statusEl = document.getElementById('thumbUploadStatus');
                statusEl.textContent = 'アップロード中...';
                statusEl.className = 'text-[10px] text-sky-500 font-bold';
                const formData = new FormData();
                formData.append('asset_id', state.selectedVideo.asset_id);
                formData.append('file', file);
                try {
                    const res = await fetch('/hinata/api/upload_thumbnail.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    if (json.status === 'success') {
                        const newUrl = json.thumbnail_url;
                        document.getElementById('thumbPreview').src = newUrl;
                        document.getElementById('thumbPreview').classList.remove('hidden');
                        document.getElementById('thumbPlaceholder').classList.add('hidden');
                        document.getElementById('selectedThumbImg').src = newUrl;
                        state.selectedVideo.thumbnail_url = newUrl;
                        const row = videoList.querySelector('[data-meta-id="' + state.selectedMetaId + '"]');
                        if (row) {
                            const img = row.querySelector('img');
                            if (img) img.src = newUrl;
                        }
                        statusEl.textContent = 'アップロード完了';
                        statusEl.className = 'text-[10px] text-emerald-600 font-bold';
                        showToast('サムネイルを更新しました');
                    } else {
                        statusEl.textContent = json.message || 'エラーが発生しました';
                        statusEl.className = 'text-[10px] text-red-500 font-bold';
                    }
                } catch (e) {
                    statusEl.textContent = '通信エラー: ' + e.message;
                    statusEl.className = 'text-[10px] text-red-500 font-bold';
                }
                this.value = '';
            });

            btnAddCategory.addEventListener('click', async () => {
                const name = newCategoryName.value.trim();
                if (!name) { showToast('カテゴリ名を入力してください'); return; }
                const res = await fetch('/hinata/api/create_media_category.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });
                const json = await res.json();
                if (json.status !== 'success') { showToast('エラー: ' + (json.message || '')); return; }
                showToast('カテゴリを追加しました');
                newCategoryName.value = '';
                const list = await refreshCategoryDropdowns();
                renderCategoryList(list);
            });
            newCategoryName.addEventListener('keydown', (e) => { if (e.key === 'Enter') btnAddCategory.click(); });
            categoryList.querySelectorAll('.btnRenameCategory').forEach(btn => btn.onclick = () => startRename(btn.closest('li')));

            // song modal events
            btnCloseSongModal && btnCloseSongModal.addEventListener('click', closeSongSelectModal);
            songSelectModal && songSelectModal.addEventListener('click', (e) => { if (e.target === songSelectModal) closeSongSelectModal(); });
            if (songModalSearch && songModalReleaseList) {
                songModalSearch.addEventListener('input', () => {
                    const q = songModalSearch.value.trim().toLowerCase();
                    songModalReleaseList.querySelectorAll('.song-modal-release').forEach(group => {
                        const releaseTitle = (group.getAttribute('data-release-title') || '').toLowerCase();
                        const releaseNumber = (group.getAttribute('data-release-number') || '').toLowerCase();
                        let hasVisible = false;
                        group.querySelectorAll('.song-modal-song-row').forEach(row => {
                            const songTitle = (row.getAttribute('data-song-title') || '').toLowerCase();
                            const trackType = (row.getAttribute('data-track-type') || '').toLowerCase();
                            const match = !q || releaseTitle.includes(q) || releaseNumber.includes(q) || songTitle.includes(q) || trackType.includes(q);
                            row.style.display = match ? '' : 'none';
                            if (match) hasVisible = true;
                        });
                        group.style.display = hasVisible ? '' : 'none';
                    });
                });
                songModalReleaseList.addEventListener('click', (e) => {
                    const toggle = e.target.closest('.song-modal-release-toggle');
                    if (toggle) {
                        const parent = toggle.closest('.song-modal-release');
                        const body = parent ? parent.querySelector('.song-modal-release-songs') : null;
                        if (body) {
                            body.classList.toggle('hidden');
                            if (songModalHint) songModalHint.classList.add('hidden');
                        }
                        return;
                    }
                    const linkBtn = e.target.closest('.song-modal-link-btn');
                    if (linkBtn) {
                        const songId = parseInt(linkBtn.getAttribute('data-song-id') || '0', 10);
                        if (!songId || !state.selectedMetaId) return;
                        (async () => {
                            try {
                                const res = await fetch('/hinata/api/save_media_song_link.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ meta_id: state.selectedMetaId, song_id: songId })
                                });
                                const json = await res.json();
                                if (json.status !== 'success') { alert('楽曲リンク保存エラー: ' + (json.message || '')); return; }
                                closeSongSelectModal();
                                await member_loadLinkedSongForInfo();
                                await song_loadLinkedSong();
                                await member_fetchAndApplySongMembers();
                            } catch (err) {
                                alert('楽曲リンク保存中にエラーが発生しました: ' + err.message);
                            }
                        })();
                    }
                });
            }

            // init
            setActiveTab(state.activeTab);
            loadVideos(true);
            return { playSelectedVideo };
        })();
        window.MediaAdmin = MediaAdmin;
    </script>
</body>
</html>

