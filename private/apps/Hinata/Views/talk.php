<?php
/**
 * ミーグリ（推し活）ネタ帳 View
 * 物理パス: haitaka/private/apps/Hinata/Views/talk.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

function _talkMemberImgSrc(?string $imageUrl): string {
    if (!$imageUrl) return '';
    return str_starts_with($imageUrl, '/') ? htmlspecialchars($imageUrl) : '/assets/img/members/' . htmlspecialchars($imageUrl);
}

$defaultMemberId = null;
if (!empty($members)) {
    $best = null;
    foreach ($members as $m) {
        $mid = (int)($m['id'] ?? 0);
        if (!$mid) continue;
        $fav = (int)($m['favorite_level'] ?? 0);
        if ($best === null || $fav > $best['fav']) {
            $best = ['id' => $mid, 'fav' => $fav];
        }
    }
    $defaultMemberId = $best['id'] ?? (int)($members[0]['id'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ネタ帳 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .color-strip { width: 8px; flex-shrink: 0; }
        .neta-item.done { opacity: 0.45; }
        
        .neta-card { 
            transition: box-shadow 0.2s, transform 0.2s; 
            box-shadow: 0 2px 8px -2px rgba(0,0,0,0.1), 0 4px 12px -4px rgba(0,0,0,0.08);
        }
        .neta-card:hover, .neta-card:focus-within { 
            box-shadow: 0 8px 24px -6px rgba(0,0,0,0.18), 0 12px 28px -8px rgba(0,0,0,0.12); 
        }
        .neta-card .card-actions { opacity: 0; transition: opacity 0.2s; }
        .neta-card:hover .card-actions, .neta-card:focus-within .card-actions { opacity: 1; }
        .fav-badge { font-size: 10px; font-weight: 700; }
        .neta-cards-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
        @media (max-width: 640px) { .neta-cards-columns { grid-template-columns: 1fr; } }

        /* action buttons (mock寄せ) */
        .neta-card .acts { gap: 8px; }
        .neta-card .acts .iconbtn {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.35); /* slate-400 */
            box-shadow: 0 10px 18px -16px rgba(15, 23, 42, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        }
        .neta-card .acts .iconbtn svg {
            width: 16px;
            height: 16px;
            stroke: #3f4a5a;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .neta-card .acts .iconbtn:hover {
            transform: translateY(-1px);
            background: #f8fafc; /* slate-50 */
            border-color: rgba(148, 163, 184, 0.55);
            box-shadow: 0 14px 26px -18px rgba(15, 23, 42, 0.45);
        }
        .neta-card .acts .iconbtn:active { transform: translateY(0px) scale(0.98); }
        .neta-card .acts .iconbtn[data-state="on"] svg[data-icon="star"] { stroke: #f5b301; fill: #f5b301; }
        .neta-card .acts .iconbtn[data-state="done"] svg[data-icon="check"] { stroke: #16a34a; } /* green-600 */
        
        /* サイドバー共通スタイル */
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            #netaFormContainer.form-hidden { display: none; }
        }
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <!-- 共通サイドバー -->
    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-xl"></i></button>
                <a href="/hinata/" class="text-slate-400 p-2 shrink-0 transition <?= $isThemeHex ? 'hover:opacity-80' : 'hover:text-' . $themeTailwind . '-500' ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-lightbulb text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-base md:text-lg tracking-tight truncate">推し活ネタ帳</h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button id="toggleFormBtn" class="md:hidden text-xs font-bold px-3 py-1.5 rounded-full <?= $cardIconText ?> <?= $cardIconBg ?> hover:opacity-90"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>+ 追加</button>
            </div>
        </header>

        <div id="scrollContainer" class="flex-1 overflow-y-auto p-4 space-y-4 pb-24">
            <div class="max-w-6xl mx-auto w-full space-y-4">
                <!-- メンバー一覧（横スクロール） + 右上フィルタボタン -->
                <section class="bg-white border <?= $cardBorder ?> rounded-xl shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b <?= $cardBorder ?>">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[10px] font-black text-slate-400 tracking-wider">メンバー</span>
                        </div>
                        <div id="memberFilterBar" class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="member-filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-sky-500 text-white hover:bg-sky-600 transition" data-filter="all">すべて</button>
                            <button type="button" class="member-filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="oshi"><i class="fa-solid fa-crown text-[9px] mr-1 text-amber-500"></i>推し</button>
                            <button type="button" class="member-filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="fav"><i class="fa-solid fa-star text-[9px] mr-1 text-yellow-500"></i>気になる</button>
                            <button type="button" class="member-filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="has"><i class="fa-solid fa-lightbulb text-[9px] mr-1 text-sky-500"></i>ネタあり</button>
                        </div>
                    </div>
                    <div class="px-3 py-3">
                        <div id="memberStrip" class="flex items-start gap-3 overflow-x-auto p-1 pb-6 scroll-smooth" style="scrollbar-width: thin;">
                            <?php foreach (($members ?? []) as $m):
                                $mid = (int)($m['id'] ?? 0);
                                $name = (string)($m['name'] ?? '');
                                $imgSrc = _talkMemberImgSrc($m['image_url'] ?? null);
                                $hasNeta = !empty($groupedNeta) && array_key_exists($mid, $groupedNeta);
                            ?>
                                <button type="button"
                                    class="member-chip shrink-0 w-[136px] group outline-none focus:ring-2 focus:ring-sky-200 rounded-xl p-1.5 hover:bg-slate-50 transition"
                                    data-member-id="<?= $mid ?>"
                                    data-favorite-level="<?= (int)($m['favorite_level'] ?? 0) ?>"
                                    data-has-neta="<?= $hasNeta ? '1' : '0' ?>"
                                    title="<?= htmlspecialchars($name) ?>">
                                    <div class="w-28 h-28 mx-auto rounded-full overflow-hidden bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center">
                                        <?php if ($imgSrc): ?>
                                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($name) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user text-slate-300 text-xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-1.5 text-center">
                                        <div class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($name) ?></div>
                                        <?php
                                            $favLevel = (int)($m['favorite_level'] ?? 0);
                                            $badge = $favLevel >= 2 ? '推し' : ($favLevel === 1 ? '気になる' : '');
                                        ?>
                                        <?php if ($badge): ?>
                                            <div class="text-[9px] font-bold text-slate-400"><?= $badge ?></div>
                                        <?php else: ?>
                                            <div class="text-[9px] font-bold text-transparent">.</div>
                                        <?php endif; ?>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- 登録・編集フォーム -->
                <section id="netaFormContainer" class="bg-white border <?= $cardBorder ?> rounded-xl p-4 shadow-sm form-hidden md:block -mt-2">
                    <form id="netaForm" class="space-y-3">
                        <input type="hidden" name="id" id="neta_id">
                        <div id="cancelEditRow" class="hidden flex justify-end items-center">
                            <button type="button" id="cancelEdit" class="hidden text-[10px] text-red-400 font-bold bg-red-50 px-2 py-0.5 rounded">キャンセル</button>
                        </div>
                        <!-- member_idは上部メンバー欄で選択 -->
                        <input type="hidden" name="member_id" id="form_member_id">
                        <input type="hidden" name="neta_type" id="form_neta_type" value="">
                        <input type="hidden" name="tags" id="form_tags" value="[]">
                        <div class="flex gap-5 items-stretch">
                            <div class="shrink-0 w-24 flex flex-col h-full">
                                <div class="flex-1 min-h-0 flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center">
                                        <img id="formMemberImg" src="" alt="" class="w-full h-full object-cover hidden">
                                        <i id="formMemberFallbackIcon" class="fa-solid fa-user text-slate-300 text-xl"></i>
                                    </div>
                                    <div id="formMemberName" class="mt-3 text-sm font-bold text-slate-700 text-center truncate w-24">メンバー未選択</div>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0 flex flex-col">
                                <textarea name="content" id="form_content" required placeholder="何を話す？" class="w-full flex-1 border border-slate-100 rounded-lg p-4 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-sky-100 min-h-[120px] transition-all resize-y"></textarea>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                    <!-- 種類トグル -->
                                    <div id="typeToggle" class="flex bg-slate-100 rounded-full p-1 text-[10px] font-bold">
                                        <button type="button" class="type-btn px-3 py-1 rounded-full bg-sky-500 text-white shadow hover:bg-sky-600 transition" data-type="none">未登録</button>
                                        <button type="button" class="type-btn px-3 py-1 rounded-full text-slate-600 hover:bg-slate-200 transition" data-type="question">質問</button>
                                        <button type="button" class="type-btn px-3 py-1 rounded-full text-slate-600 hover:bg-slate-200 transition" data-type="impression">感想</button>
                                        <button type="button" class="type-btn px-3 py-1 rounded-full text-slate-600 hover:bg-slate-200 transition" data-type="joke">ネタ</button>
                                    </div>

                                    <!-- タグ入力 -->
                                    <div class="relative flex-1 min-w-[220px]">
                                        <div id="tagBadges" class="flex flex-wrap gap-1.5 mb-2"></div>
                                        <input id="tagInput" type="text" placeholder="#タグ（Enterで追加）" list="tagSuggestions"
                                               class="w-full h-10 border border-slate-100 rounded-lg px-3 text-sm bg-slate-50 outline-none focus:ring-2 focus:ring-sky-100">
                                        <datalist id="tagSuggestions">
                                            <?php foreach (($tagSuggestions ?? []) as $t): ?>
                                                <option value="#<?= htmlspecialchars($t) ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <!-- 追加ボタン（タグ入力と同じ行の右側） -->
                                    <button type="submit" id="submitBtn" class="shrink-0 bg-sky-500 hover:bg-sky-600 text-white h-10 px-4 rounded-lg font-bold shadow-sm active:scale-95 transition">
                                        +追加
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- カード表示エリア（デフォルト） -->
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- 左：ネタエリア（約70%） -->
                    <div class="lg:basis-[70%] lg:min-w-0">
                        <!-- メモエリア フィルタ（提案順） -->
                        <div id="memoQuickFilterBar" class="flex flex-wrap items-center gap-2 mb-3">
                            <button type="button" class="memo-quick-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-sky-500 text-white hover:bg-sky-600 shadow-sm shadow-sky-200/60 ring-1 ring-black/5 active:scale-[0.98] transition" data-mode="unused">未使用</button>
                            <button type="button" class="memo-quick-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-white text-slate-600 hover:bg-slate-50 shadow-sm ring-1 ring-black/5 active:scale-[0.98] transition" data-mode="question"><i class="fa-solid fa-circle-question text-[9px] mr-1 text-blue-500"></i>質問</button>
                            <button type="button" class="memo-quick-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-white text-slate-600 hover:bg-slate-50 shadow-sm ring-1 ring-black/5 active:scale-[0.98] transition" data-mode="impression"><i class="fa-solid fa-comment-dots text-[9px] mr-1 text-emerald-500"></i>感想</button>
                            <button type="button" class="memo-quick-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-white text-slate-600 hover:bg-slate-50 shadow-sm ring-1 ring-black/5 active:scale-[0.98] transition" data-mode="joke"><i class="fa-solid fa-face-laugh-squint text-[9px] mr-1 text-amber-500"></i>ネタ</button>
                            <button type="button" class="memo-quick-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-white text-slate-600 hover:bg-slate-50 shadow-sm ring-1 ring-black/5 active:scale-[0.98] transition" data-mode="favorite"><i class="fa-solid fa-star text-[9px] mr-1 text-amber-500"></i>お気に入り</button>
                            <button type="button" class="memo-quick-btn px-3 py-1.5 rounded-full text-[10px] font-bold bg-white text-slate-600 hover:bg-slate-50 shadow-sm ring-1 ring-black/5 active:scale-[0.98] transition" data-mode="all">すべて</button>
                        </div>
                        <div id="cardView" class="space-y-8">
                            <div id="selectedMemberEmpty" class="hidden bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-400 text-sm">
                                選択中メンバーのネタがありません
                            </div>
                            <?php if(!empty($groupedNeta)): foreach ($groupedNeta as $mid => $group): 
                                $favLevel = (int)($group['favorite_level'] ?? 0);
                                $favLabel = $favLevel >= 2 ? '推し' : ($favLevel === 1 ? '気になる' : '');
                                $color1 = htmlspecialchars($group['color1'] ?? '#7cc7e8');
                                $color2 = htmlspecialchars($group['color2'] ?? $color1);
                            ?>
                                <div id="neta-member-<?= (int)$mid ?>" data-member-id="<?= (int)$mid ?>">
                                    <!-- カード群（カラムレイアウトで詰めて表示） -->
                                    <div class="neta-cards-columns">
                                        <?php foreach ($group['items'] as $item): ?>
                                                <?php
                                                $type = $item['neta_type'] ?? null;
                                                $typeLabel = $type === 'question' ? '質問' : ($type === 'impression' ? '感想' : ($type === 'joke' ? 'ネタ' : null));
                                                $typeClass = $type === 'question' ? 'bg-blue-100 text-blue-700' : ($type === 'impression' ? 'bg-emerald-100 text-emerald-700' : ($type === 'joke' ? 'bg-amber-100 text-amber-700' : ''));
                                                $isFav = !empty($item['is_favorite']);
                                            ?>
                                            <div class="neta-card neta-item relative bg-white border-l-4 rounded-xl p-4 outline-none focus:outline-none focus:ring-2 focus:ring-sky-200 w-full <?= $item['status'] === 'done' ? 'done' : '' ?>"
                                                 style="border-left-color: <?= $color1 ?>;"
                                                 data-neta-type="<?= htmlspecialchars($type ?? '') ?>"
                                                 tabindex="0">
                                                <?php if ($typeLabel): ?>
                                                    <span class="absolute top-2 left-2 text-[9px] font-black px-2 py-0.5 rounded-full <?= $typeClass ?>"><?= $typeLabel ?></span>
                                                <?php endif; ?>
                                                <span class="used-badge absolute top-2 <?= $typeLabel ? 'left-[64px]' : 'left-2' ?> text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 <?= $item['status'] === 'done' ? '' : 'hidden' ?>">使用済</span>
                                                <div class="text-sm text-slate-700 leading-relaxed neta-content pr-10 pt-4">
                                                    <?= nl2br(htmlspecialchars($item['content'])) ?>
                                                </div>
                                                <div class="foot mt-3 flex items-center justify-between gap-3" onclick="event.stopPropagation()">
                                                    <div class="ttag flex flex-wrap gap-1.5 min-w-0">
                                                        <?php if (!empty($item['tags']) && is_array($item['tags'])): ?>
                                                            <?php foreach ($item['tags'] as $tg): ?>
                                                                <?php if ($tg !== ''): ?>
                                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">#<?= htmlspecialchars($tg) ?></span>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="acts card-actions flex items-center shrink-0">
                                                        <!-- お気に入り -->
                                                        <button type="button"
                                                                class="iconbtn"
                                                                data-role="fav-btn"
                                                                data-state="<?= $isFav ? 'on' : 'off' ?>"
                                                                title="お気に入り" aria-label="お気に入り"
                                                                onclick="toggleNetaFavorite(this, <?= (int)$item['id'] ?>, <?= $isFav ? 'true' : 'false' ?>)">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-icon="star">
                                                                <path d="M12 2.7l2.95 6.1 6.73.98-4.87 4.74 1.15 6.7L12 18.8 6.04 21.22l1.15-6.7L2.32 9.78l6.73-.98L12 2.7z"></path>
                                                            </svg>
                                                        </button>
                                                        <!-- 編集 -->
                                                        <button type="button"
                                                                class="iconbtn"
                                                                title="編集" aria-label="編集"
                                                                onclick="editNeta(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-icon="pencil">
                                                                <path d="M12 20h9"></path>
                                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                                                            </svg>
                                                        </button>
                                                        <!-- 使用済み -->
                                                        <button type="button"
                                                                class="iconbtn"
                                                                data-role="done-btn"
                                                                data-state="<?= $item['status'] === 'done' ? 'done' : 'off' ?>"
                                                                title="使用済み" aria-label="使用済み"
                                                                onclick="toggleStatus(this, <?= (int)$item['id'] ?>, <?= $item['status'] === 'done' ? 'false' : 'true' ?>)">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-icon="check">
                                                                <path d="M20 6L9 17l-5-5"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <p class="text-center text-slate-400 text-xs py-10 tracking-wider">データがありません</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 右：サイド（次のミーグリ + 登録済みメンバー） -->
                    <aside class="lg:basis-[30%] lg:min-w-0 space-y-4">
                        <?php
                            $next = $nextMgEvent ?? null;
                            $daysLeft = $next ? (int)($next['days_left'] ?? 0) : null;
                            $nextDate = $next['event_date'] ?? null;
                            $nextName = $next['event_name'] ?? null;
                            $nextCat = $next ? (int)($next['category'] ?? 0) : 0;
                            $nextTypeLabel = $nextCat === 3 ? 'リアルミーグリ' : ($nextCat === 2 ? 'ミーグリ' : 'イベント');
                            $nextMd = $nextDate ? date('n/j', strtotime($nextDate)) : null;
                        ?>
                        <section class="relative overflow-hidden rounded-2xl p-5 shadow-sm border border-white/25 text-white">
                            <!-- glass background -->
                            <div class="absolute inset-0 bg-gradient-to-br from-sky-500 via-sky-500 to-indigo-500"></div>
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/20 rounded-full blur-2xl"></div>
                            <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
                            <div class="absolute inset-0 backdrop-blur-[2px]"></div>

                            <div class="relative">
                                <!-- 1) icon + "次のイベント・あと◯日" -->
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="fa-solid fa-calendar-days text-white/90"></i>
                                        <span class="text-[11px] font-black tracking-tight truncate">次のイベント ・ あと<?= $daysLeft !== null ? max(0, $daysLeft) : '—' ?>日</span>
                                    </div>
                                </div>

                                <!-- 2) (日付) (イベント種類) -->
                                <div class="mt-3 flex items-end gap-2">
                                    <div class="text-2xl font-black leading-none"><?= htmlspecialchars($nextMd ?: '—') ?></div>
                                    <div class="text-xl font-black leading-none"><?= htmlspecialchars($nextTypeLabel) ?></div>
                                </div>

                                <!-- 3) (名前) ○部(複数は/) -->
                                <?php if (!empty($nextMgParticipantsText)): ?>
                                    <div class="mt-2 text-xs font-bold text-white/90 leading-relaxed">
                                        <?= htmlspecialchars($nextMgParticipantsText) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2 text-xs font-bold text-white/70">参加予定が未登録です</div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="bg-white border <?= $cardBorder ?> rounded-2xl p-5 shadow-sm">
                            <?php
                                $rmList = $registeredMembers ?? [];
                                $rmTotalMembers = count($rmList);
                                $rmTotalNeta = (int)($totalNetaCount ?? 0);
                                $rmTotalUsed = (int)($totalUsedCount ?? 0);
                                $rmGroups = ['oshi' => [], 'kininaru' => [], 'other' => []];
                                foreach ($rmList as $rm) {
                                    $k = $rm['fav_type'] ?? 'other';
                                    if (!isset($rmGroups[$k])) $k = 'other';
                                    $rmGroups[$k][] = $rm;
                                }
                                $rmGroupLabels = ['oshi' => '⭐ 推し', 'kininaru' => '❤️ 気になる', 'other' => 'その他'];
                            ?>

                            <div class="flex items-center gap-2">
                                <h5 class="text-sm font-black text-slate-800">👥 登録メンバー</h5>
                                <span class="text-[11px] text-slate-400 font-bold">
                                    <?= $rmTotalMembers ?>名 ・ ネタ<?= $rmTotalNeta ?>件 ・ 使用済<?= $rmTotalUsed ?>
                                </span>
                            </div>

                            <div class="mt-4 space-y-4 max-h-[520px] overflow-y-auto pr-1">
                                <?php foreach (['oshi','kininaru','other'] as $gKey): ?>
                                    <?php $g = $rmGroups[$gKey] ?? []; if (empty($g)) continue; ?>
                                    <div>
                                        <div class="flex items-center gap-2 text-[11px] font-black text-slate-500">
                                            <span><?= $rmGroupLabels[$gKey] ?></span>
                                            <span class="flex-1 h-px bg-slate-200"></span>
                                            <span><?= count($g) ?>名</span>
                                        </div>
                                        <div class="mt-2 space-y-2">
                                            <?php foreach ($g as $rm):
                                                $rmImg = _talkMemberImgSrc($rm['image_url'] ?? null);
                                                $total = (int)($rm['count'] ?? 0);
                                                $used  = (int)($rm['used_count'] ?? 0);
                                                $pct = $total > 0 ? (int)round(($used / $total) * 100) : 0;
                                                $gen = (int)($rm['generation'] ?? 0);
                                                $sub = ($gen > 0 ? "{$gen}期生" : "—") . " ・ 使用済 {$used}/{$total}";
                                                $accent = $rm['color1'] ?? '#94a3b8';
                                            ?>
                                                <button type="button"
                                                    class="registered-member-item w-full text-left flex items-center gap-3 rounded-xl border border-slate-100 border-l-2 border-l-transparent px-3 py-2 transition bg-white hover:bg-slate-50"
                                                    data-accent="<?= htmlspecialchars($accent) ?>"
                                                    data-member-id="<?= (int)($rm['member_id'] ?? 0) ?>">

                                                    <!-- 丸画像（既存形状維持） -->
                                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 shrink-0 flex items-center justify-center">
                                                        <?php if ($rmImg): ?>
                                                            <img src="<?= $rmImg ?>" alt="<?= htmlspecialchars($rm['name'] ?? '') ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-user text-slate-300"></i>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- 中央：氏名 + サブ + バー -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($rm['name'] ?? '') ?></div>
                                                        <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($sub) ?></div>
                                                        <div class="mt-1 h-1 rounded bg-slate-100 overflow-hidden">
                                                            <div class="h-1 rounded bg-emerald-400" style="width: <?= max(0, min(100, $pct)) ?>%"></div>
                                                        </div>
                                                    </div>

                                                    <!-- 右：ネタ数（縦） -->
                                                    <div class="text-right shrink-0 w-10">
                                                        <div class="text-base font-black text-slate-800 leading-none"><?= $total ?></div>
                                                        <div class="text-[10px] text-slate-400 font-bold mt-0.5">ネタ</div>
                                                    </div>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (empty($rmList)): ?>
                                    <div class="text-xs text-slate-400 text-center py-8">登録済みメンバーがいません</div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        window.onload = () => {
            restoreScroll();     // スクロール位置の復元
        };

        const DEFAULT_MEMBER_ID = <?= (int)($defaultMemberId ?? 0) ?>;
        const MEMBER_META = <?= json_encode(array_reduce(($members ?? []), function($acc, $m) {
            $mid = (int)($m['id'] ?? 0);
            if (!$mid) return $acc;
            $acc[$mid] = [
                'name' => (string)($m['name'] ?? ''),
                'img'  => _talkMemberImgSrc($m['image_url'] ?? null),
            ];
            return $acc;
        }, []), JSON_UNESCAPED_UNICODE) ?>;

        // メンバー横スクロール選択（フォームのメンバー選択に反映）
        const memberStrip = document.getElementById('memberStrip');
        const formMemberSelect = document.getElementById('form_member_id');
        const formMemberImg = document.getElementById('formMemberImg');
        const formMemberFallbackIcon = document.getElementById('formMemberFallbackIcon');
        const formMemberName = document.getElementById('formMemberName');

        const selectedMemberEmpty = document.getElementById('selectedMemberEmpty');

        function filterNetaBySelectedMember(memberId) {
            const cardView = document.getElementById('cardView');
            if (!cardView) return;
            const blocks = cardView.querySelectorAll('[data-member-id]');
            let visible = 0;
            blocks.forEach(b => {
                const ok = String(b.dataset.memberId) === String(memberId);
                b.classList.toggle('hidden', !ok);
                if (ok) visible++;
            });
            if (selectedMemberEmpty) {
                selectedMemberEmpty.classList.toggle('hidden', visible > 0);
            }
        }

        let memoQuickMode = 'unused'; // unused/question/impression/joke/favorite/all

        function applyMemoFilters() {
            const cardView = document.getElementById('cardView');
            if (!cardView) return;
            cardView.querySelectorAll('.neta-item').forEach(item => {
                const isDone = item.classList.contains('done');
                const t = (item.dataset.netaType || '').trim();
                const isFav = (item.dataset.isFavorite || '0') === '1';

                let show = true;
                if (memoQuickMode === 'all') {
                    show = true; // 未使用/使用済み両方、種類不問
                } else if (memoQuickMode === 'unused') {
                    show = !isDone; // 未使用、かつ全種類
                } else if (memoQuickMode === 'favorite') {
                    show = !isDone && isFav; // お気に入り、かつ未使用（種類不問）
                } else if (memoQuickMode === 'question' || memoQuickMode === 'impression' || memoQuickMode === 'joke') {
                    show = !isDone && (t === memoQuickMode); // 未使用、かつ種類一致
                }

                item.classList.toggle('hidden', !show);
            });
        }

        function updateFormMemberDisplay(memberId) {
            const meta = memberId ? MEMBER_META[String(memberId)] || MEMBER_META[Number(memberId)] : null;
            if (!formMemberName) return;
            if (!meta) {
                formMemberName.textContent = 'メンバー未選択';
                if (formMemberImg) {
                    formMemberImg.classList.add('hidden');
                    formMemberImg.src = '';
                    formMemberImg.alt = '';
                }
                if (formMemberFallbackIcon) formMemberFallbackIcon.classList.remove('hidden');
                return;
            }
            formMemberName.textContent = meta.name || '';
            if (meta.img) {
                if (formMemberImg) {
                    formMemberImg.src = meta.img;
                    formMemberImg.alt = meta.name || '';
                    formMemberImg.classList.remove('hidden');
                }
                if (formMemberFallbackIcon) formMemberFallbackIcon.classList.add('hidden');
            } else {
                if (formMemberImg) {
                    formMemberImg.classList.add('hidden');
                    formMemberImg.src = '';
                    formMemberImg.alt = '';
                }
                if (formMemberFallbackIcon) formMemberFallbackIcon.classList.remove('hidden');
            }
        }

        // 登録フォーム：種類トグル
        const formNetaType = document.getElementById('form_neta_type');
        const typeToggle = document.getElementById('typeToggle');
        function setFormNetaType(type) {
            const t = (type === null || type === undefined || type === '' || type === 'none') ? '' : String(type);
            if (formNetaType) formNetaType.value = t;
            if (!typeToggle) return;
            typeToggle.querySelectorAll('.type-btn').forEach(btn => {
                const active = (btn.dataset.type || 'none') === (t || 'none');
                btn.classList.toggle('bg-sky-500', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('shadow', active);
                btn.classList.toggle('hover:bg-sky-600', active);
                btn.classList.toggle('text-slate-600', !active);
                btn.classList.toggle('hover:bg-slate-200', !active);
            });
        }
        if (typeToggle) {
            typeToggle.addEventListener('click', (e) => {
                const btn = e.target.closest('.type-btn');
                if (!btn) return;
                setFormNetaType(btn.dataset.type || 'none');
            });
        }

        // 登録フォーム：タグ（簡易SNS風）
        const formTags = document.getElementById('form_tags');
        const tagInput = document.getElementById('tagInput');
        const tagBadges = document.getElementById('tagBadges');
        let currentTags = [];

        function normalizeTag(raw) {
            let t = String(raw || '').trim();
            if (t.startsWith('#')) t = t.slice(1);
            t = t.trim();
            // 空白や#を除去（最低限）
            t = t.replace(/[#\s]+/g, '');
            return t;
        }
        function renderTags() {
            if (formTags) formTags.value = JSON.stringify(currentTags);
            if (!tagBadges) return;
            tagBadges.innerHTML = '';
            currentTags.forEach((t, idx) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold hover:bg-slate-200 transition';
                b.textContent = '#' + t + ' ×';
                b.onclick = () => {
                    currentTags.splice(idx, 1);
                    renderTags();
                };
                tagBadges.appendChild(b);
            });
        }
        function addTag(raw) {
            const t = normalizeTag(raw);
            if (!t) return;
            if (!currentTags.includes(t)) currentTags.push(t);
            renderTags();
        }
        if (tagInput) {
            tagInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
                    e.preventDefault();
                    addTag(tagInput.value);
                    tagInput.value = '';
                } else if (e.key === 'Backspace' && tagInput.value === '' && currentTags.length) {
                    currentTags.pop();
                    renderTags();
                }
            });
            tagInput.addEventListener('blur', () => {
                if (tagInput.value.trim()) {
                    addTag(tagInput.value);
                    tagInput.value = '';
                }
            });
        }

        function syncSelectedMember(memberId) {
            if (!memberId) return;
            setSelectedMemberChip(memberId);
            if (formMemberSelect) formMemberSelect.value = String(memberId);
            updateFormMemberDisplay(memberId);
            filterNetaBySelectedMember(memberId);
            applyMemoFilters();
            highlightRegisteredMember(memberId);
        }

        function highlightRegisteredMember(memberId) {
            document.querySelectorAll('.registered-member-item').forEach(btn => {
                const isActive = String(btn.dataset.memberId) === String(memberId);
                btn.classList.toggle('bg-white', !isActive);
                btn.classList.toggle('hover:bg-slate-50', !isActive);
                btn.classList.toggle('border-l-transparent', !isActive);

                if (isActive) {
                    // カードと同様のメンバーカラーをアクセントに（背景は薄く）
                    const rawAccent = btn.getAttribute('data-accent') || '#38bdf8';
                    const accent = pickAccentColor(rawAccent);
                    btn.style.borderLeftColor = accent;
                    btn.style.background = `color-mix(in srgb, ${accent} 10%, white)`;
                } else {
                    btn.style.borderLeftColor = '';
                    btn.style.background = '';
                }
            });
        }

        function pickAccentColor(hex) {
            const c = (hex || '').trim();
            if (!/^#?[0-9a-fA-F]{6}$/.test(c)) return '#94a3b8';
            const h = c.startsWith('#') ? c.slice(1) : c;
            const r = parseInt(h.slice(0, 2), 16);
            const g = parseInt(h.slice(2, 4), 16);
            const b = parseInt(h.slice(4, 6), 16);
            // 相対輝度（簡易）。白系は視認性が低いのでフォールバック。
            const luminance = 0.299 * r + 0.587 * g + 0.114 * b;
            if (luminance >= 235) return '#94a3b8'; // slate-400相当
            return '#' + h.toLowerCase();
        }

        function setSelectedMemberChip(memberId) {
            if (!memberStrip) return;
            memberStrip.querySelectorAll('.member-chip').forEach(btn => {
                const active = String(btn.dataset.memberId) === String(memberId);
                btn.classList.toggle('bg-sky-50', active);
                btn.classList.toggle('ring-2', active);
                btn.classList.toggle('ring-sky-200', active);
            });
        }
        if (memberStrip) {
            memberStrip.addEventListener('click', (e) => {
                const btn = e.target.closest('.member-chip');
                if (!btn) return;
                const memberId = btn.dataset.memberId;
                syncSelectedMember(memberId);
            });
        }

        // 右上フィルタ（メンバー一覧のみ）
        const memberFilterBar = document.getElementById('memberFilterBar');
        function applyMemberFilter(mode) {
            if (!memberStrip) return;
            const chips = memberStrip.querySelectorAll('.member-chip');
            chips.forEach(chip => {
                const fav = parseInt(chip.dataset.favoriteLevel || '0', 10);
                const has = chip.dataset.hasNeta === '1';
                let ok = true;
                if (mode === 'oshi') ok = fav >= 2;
                else if (mode === 'fav') ok = fav === 1;
                else if (mode === 'has') ok = has;
                chip.classList.toggle('hidden', !ok);
            });
        }
        function setActiveFilterBtn(activeBtn) {
            if (!memberFilterBar) return;
            memberFilterBar.querySelectorAll('.member-filter-btn').forEach(btn => {
                const isActive = btn === activeBtn;
                btn.classList.toggle('bg-sky-500', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('hover:bg-sky-600', isActive);
                btn.classList.toggle('bg-slate-100', !isActive);
                btn.classList.toggle('text-slate-600', !isActive);
                btn.classList.toggle('hover:bg-slate-200', !isActive);
            });
        }
        if (memberFilterBar) {
            memberFilterBar.addEventListener('click', (e) => {
                const btn = e.target.closest('.member-filter-btn');
                if (!btn) return;
                const mode = btn.dataset.filter || 'all';
                setActiveFilterBtn(btn);
                applyMemberFilter(mode);
            });
        }

        // メモエリア クイックフィルタ（提案順）
        const memoQuickFilterBar = document.getElementById('memoQuickFilterBar');
        function setActiveMemoQuickBtn(activeBtn) {
            if (!memoQuickFilterBar) return;
            memoQuickFilterBar.querySelectorAll('.memo-quick-btn').forEach(btn => {
                const isActive = btn === activeBtn;
                btn.classList.toggle('bg-sky-500', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('hover:bg-sky-600', isActive);
                btn.classList.toggle('bg-white', !isActive);
                btn.classList.toggle('text-slate-600', !isActive);
                btn.classList.toggle('hover:bg-slate-50', !isActive);
            });
        }
        if (memoQuickFilterBar) {
            memoQuickFilterBar.addEventListener('click', (e) => {
                const btn = e.target.closest('.memo-quick-btn');
                if (!btn) return;
                memoQuickMode = btn.dataset.mode || 'unused';
                setActiveMemoQuickBtn(btn);
                applyMemoFilters();
            });
        }

        // 右サイド：登録済みメンバークリックで上部選択と連動
        document.querySelectorAll('.registered-member-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const mid = btn.dataset.memberId;
                if (mid) syncSelectedMember(mid);
            });
        });

        // スマホ用フォームトグル（表示時は「-追加」）
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        function updateToggleFormBtnLabel() {
            if (!toggleFormBtn) return;
            const isHidden = document.getElementById('netaFormContainer')?.classList.contains('form-hidden');
            toggleFormBtn.textContent = (isHidden ? '+ 追加' : '- 追加');
        }
        if (toggleFormBtn) {
            toggleFormBtn.onclick = function() {
                document.getElementById('netaFormContainer').classList.toggle('form-hidden');
                updateToggleFormBtnLabel();
            };
            updateToggleFormBtnLabel();
        }

        /**
         * スクロール位置の保存（リロード直前）
         */
        function saveScroll() {
            const container = document.getElementById('scrollContainer');
            if (container) {
                sessionStorage.setItem('hinata_scroll_pos', container.scrollTop);
            }
        }

        /**
         * スクロール位置の復元
         */
        function restoreScroll() {
            const pos = sessionStorage.getItem('hinata_scroll_pos');
            const container = document.getElementById('scrollContainer');
            if (pos && container) {
                container.scrollTop = pos;
            }
        }

        /**
         * 編集モード切り替え
         */
        function editNeta(item) {
            document.getElementById('netaFormContainer').classList.remove('form-hidden');
            document.getElementById('neta_id').value = item.id;
            document.getElementById('form_member_id').value = item.member_id;
            document.getElementById('form_content').value = item.content;
            setFormNetaType(item.neta_type || 'none');
            currentTags = Array.isArray(item.tags) ? item.tags.map(normalizeTag).filter(Boolean) : [];
            renderTags();
            document.getElementById('submitBtn').innerText = '変更を保存';
            document.getElementById('cancelEditRow')?.classList.remove('hidden');
            document.getElementById('cancelEdit').classList.remove('hidden');
            syncSelectedMember(item.member_id);
            
            // フォームへスクロール
            const container = document.getElementById('scrollContainer');
            if (container) container.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /**
         * フォームリセット
         */
        document.getElementById('cancelEdit').onclick = function() {
            document.getElementById('netaForm').reset();
            document.getElementById('neta_id').value = '';
            document.getElementById('submitBtn').innerText = '+追加';
            this.classList.add('hidden');
            document.getElementById('cancelEditRow')?.classList.add('hidden');
            setFormNetaType('none');
            currentTags = [];
            renderTags();
            applyMemoFilters();
        };

        // 初期表示：最推し（favorite_level最大）を選択状態にする
        document.addEventListener('DOMContentLoaded', () => {
            if (DEFAULT_MEMBER_ID) {
                syncSelectedMember(DEFAULT_MEMBER_ID);
            }
            setFormNetaType('none');
            renderTags();
            // default: 未使用（全種類）
            memoQuickMode = 'unused';
            applyMemoFilters();
        });

        /**
         * 保存処理
         */
        document.getElementById('netaForm').onsubmit = async (e) => {
            e.preventDefault();
            const memberId = (formMemberSelect?.value || '').trim();
            if (!memberId) {
                alert('上のメンバー欄からメンバーを選択してください');
                return;
            }
            saveScroll(); 
            const res = await App.post('api/save_neta.php', Object.fromEntries(new FormData(e.target)));
            if (res.status === 'success') {
                location.reload();
            } else {
                alert('エラー: ' + res.message);
            }
        };

        /**
         * 完了ステータスのトグル
         */
        function updateCardUiAfterStatus(cardEl, isDone) {
            cardEl.classList.toggle('done', isDone);
            const badge = cardEl.querySelector('.used-badge');
            if (badge) badge.classList.toggle('hidden', !isDone);
            const doneBtn = cardEl.querySelector('[data-role="done-btn"]');
            if (doneBtn) doneBtn.dataset.state = isDone ? 'done' : 'off';
            // フィルタ条件に合わせて表示更新
            applyMemoFilters();
        }

        function updateCardUiAfterFavorite(cardEl, isFav) {
            cardEl.dataset.isFavorite = isFav ? '1' : '0';
            const favBtn = cardEl.querySelector('[data-role="fav-btn"]');
            if (favBtn) favBtn.dataset.state = isFav ? 'on' : 'off';
        }

        async function toggleStatus(btnEl, id, checked) {
            saveScroll();
            const card = btnEl?.closest?.('.neta-item');
            const res = await App.post('api/update_neta_status.php', {
                id: id,
                status: checked ? 'done' : 'stock'
            });
            if (res.status === 'success') {
                if (card) updateCardUiAfterStatus(card, !!checked);
            } else {
                alert('更新に失敗しました');
            }
        }

        async function toggleNetaFavorite(btnEl, id, current) {
            saveScroll();
            const card = btnEl?.closest?.('.neta-item');
            const next = !current;
            const res = await App.post('api/update_neta_favorite.php', {
                id: id,
                is_favorite: next ? 1 : 0
            });
            if (res.status === 'success') {
                if (card) updateCardUiAfterFavorite(card, next);
                // 次回クリック用に current を更新
                if (btnEl) btnEl.setAttribute('onclick', `toggleNetaFavorite(this, ${id}, ${next ? 'true' : 'false'})`);
            } else {
                alert('更新に失敗しました');
            }
        }

        /**
         * ネタの削除
         */
        async function deleteNeta(id) {
            if (!confirm('このネタを削除してもよろしいですか？')) return;
            saveScroll();
            const res = await App.post('api/delete_neta.php', { id });
            if (res.status === 'success') {
                location.reload();
            } else {
                alert('削除に失敗しました');
            }
        }
    </script>
</body>
</html>
