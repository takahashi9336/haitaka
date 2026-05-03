<?php
/**
 * 日向坂イベント管理 View (カテゴリ欄復活版)
 * 物理パス: haitaka/private/apps/Hinata/Views/event_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
use App\Hinata\Helper\MemberGroupHelper;

$hinataMiniCalCategories = [
    ['c' => 1, 'label' => 'LIVE', 'color' => '#FF4D4F'],
    ['c' => 2, 'label' => 'ミーグリ', 'color' => '#5C67F2'],
    ['c' => 3, 'label' => 'リアルミーグリ', 'color' => '#4F6BED'],
    ['c' => 4, 'label' => 'リリース', 'color' => '#00C08B'],
    ['c' => 5, 'label' => 'メディア', 'color' => '#8B5CF6'],
    ['c' => 6, 'label' => 'スペイベ', 'color' => '#F59E0B'],
    ['c' => 99, 'label' => 'その他', 'color' => '#94A3B8'],
];
$hinataMiniCalPayload = [
    'events' => array_map(static function ($row) {
        return ['d' => $row['event_date'], 'c' => (int)($row['category'] ?? 99)];
    }, $miniCalEvents ?? []),
    'categories' => $hinataMiniCalCategories,
];
$hinataMiniCalJson = json_encode($hinataMiniCalPayload, JSON_UNESCAPED_UNICODE);

/**
 * 最近の編集リスト用（タブ filter 値・バッジ文言・日付ブロックのグラデーション）
 *
 * @return array{filter:string,pill:string,pill_class:string,grad:string}
 */
function hinata_event_admin_recent_row_meta(int $c): array {
    return match ($c) {
        1 => ['filter' => 'live', 'pill' => 'LIVE', 'pill_class' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200/80', 'grad' => 'linear-gradient(135deg,#ffe4e6 0%,#ffedd5 100%)'],
        2 => ['filter' => 'mg', 'pill' => 'ミーグリ', 'pill_class' => 'bg-indigo-100 text-indigo-700 ring-1 ring-inset ring-indigo-200/80', 'grad' => 'linear-gradient(135deg,#e0e7ff 0%,#ede9fe 100%)'],
        3 => ['filter' => 'mg', 'pill' => 'リアルミ', 'pill_class' => 'bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-200/80', 'grad' => 'linear-gradient(135deg,#ede9fe 0%,#f3e8ff 100%)'],
        4 => ['filter' => 'other', 'pill' => 'リリース', 'pill_class' => 'bg-teal-100 text-teal-800 ring-1 ring-inset ring-teal-200/80', 'grad' => 'linear-gradient(135deg,#ccfbf1 0%,#d1fae5 100%)'],
        5 => ['filter' => 'other', 'pill' => 'メディア', 'pill_class' => 'bg-fuchsia-100 text-fuchsia-800 ring-1 ring-inset ring-fuchsia-200/80', 'grad' => 'linear-gradient(135deg,#fae8ff 0%,#fce7f3 100%)'],
        6 => ['filter' => 'other', 'pill' => 'スペイベ', 'pill_class' => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-200/80', 'grad' => 'linear-gradient(135deg,#d1fae5 0%,#ecfccb 100%)'],
        default => ['filter' => 'other', 'pill' => 'その他', 'pill_class' => 'bg-slate-200 text-slate-700 ring-1 ring-inset ring-slate-300/70', 'grad' => 'linear-gradient(135deg,#f1f5f9 0%,#e8eef5 100%)'],
    };
}

function hinata_event_admin_render_recent_row(array $ev): void {
    $c = (int)($ev['category'] ?? 99);
    $meta = hinata_event_admin_recent_row_meta($c);
    $ts = strtotime($ev['event_date'] ?? 'now');
    $monEn = strtoupper(date('M', $ts));
    $day = (string)(int)date('j', $ts);
    $place = trim((string)($ev['event_place'] ?? ''));
    $title = htmlspecialchars((string)($ev['event_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $dataEv = htmlspecialchars(json_encode($ev, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    ?>
    <button type="button" class="hinata-recent-row group w-full flex items-center gap-3 px-3 py-2 text-left bg-white hover:bg-slate-50/90 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-200 border-0 cursor-pointer"
        data-filter="<?= htmlspecialchars($meta['filter'], ENT_QUOTES, 'UTF-8') ?>"
        data-ev="<?= $dataEv ?>">
        <div class="w-11 shrink-0 rounded-lg flex flex-col items-center justify-center py-1.5 text-slate-700 shadow-sm ring-1 ring-slate-200/60" style="background:<?= htmlspecialchars($meta['grad'], ENT_QUOTES, 'UTF-8') ?>">
            <span class="text-[8px] font-bold text-slate-500 tracking-wide leading-none"><?= htmlspecialchars($monEn, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="text-base font-black text-slate-800 leading-none mt-0.5 tabular-nums"><?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="flex-1 min-w-0 py-0.5 flex flex-col gap-0.5 justify-center">
            <p class="text-sm font-black text-slate-800 leading-tight line-clamp-1"><?= $title ?></p>
            <div class="flex items-center gap-2 min-w-0">
                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[9px] font-black leading-none <?= htmlspecialchars($meta['pill_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($meta['pill'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($place !== ''): ?>
                <span class="flex min-w-0 flex-1 items-center gap-0.5 text-[11px] text-slate-500 leading-tight">
                    <i class="fa-solid fa-location-dot shrink-0 text-[10px] opacity-70" aria-hidden="true"></i>
                    <span class="truncate"><?= htmlspecialchars($place, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="shrink-0 flex items-center pr-0.5">
            <i class="fa-solid fa-chevron-right text-slate-300 text-xs group-hover:text-slate-400 transition-colors" aria-hidden="true"></i>
        </div>
    </button>
    <?php
}

$recentUpcoming = $recentUpcoming ?? [];
$recentPast = $recentPast ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Admin - Hinata Portal</title>
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
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
    <style>
        .hinata-mc-card { border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); }
        .hinata-mc-today {
            background: #1A1F2B;
            border-color: #1A1F2B !important;
        }
        .hinata-mc-today .hinata-mc-today-num { color: #fff !important; }
        .hinata-mc-today:hover { background: #252b3a; }
        #hinataMcGrid {
            gap: 1px 0;
            row-gap: 1px;
        }
        .hinata-recent-card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 14px rgba(0,0,0,0.04);
        }
        /* タイトル行と登録カードは同一カラム幅。左端を視覚上揃える */
        .event-reg-card {
            margin-left: 0;
            margin-right: 0;
            box-sizing: border-box;
        }
        #eventForm .hinata-admin-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #333c4e;
            line-height: 1.4;
            letter-spacing: 0;
        }
        #eventForm .hinata-admin-label-hint {
            font-weight: 500;
            color: #64748b;
            font-size: 0.6875rem;
            margin-left: 0.25rem;
        }
        #eventForm .hinata-form-control {
            display: block;
            width: 100%;
            min-height: 3rem;
            padding: 0.625rem 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            background: #fff;
            font-size: 0.875rem;
            color: #1e293b;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        }
        #eventForm .hinata-form-control:focus-visible {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.22);
        }
        #eventForm .hinata-form-control::placeholder {
            color: #94a3b8;
        }
        #eventForm .hinata-form-control:hover {
            border-color: #94a3b8;
        }
        #eventForm textarea.hinata-form-control {
            min-height: 5.75rem;
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
        }
        #eventForm .hinata-input-wrap.relative .hinata-form-control:not(textarea) {
            padding-left: 2.5rem;
        }
        #eventForm .hinata-form-field-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.875rem;
        }
        #eventForm .hinata-textarea-wrap .hinata-form-icon-tl {
            position: absolute;
            left: 0.75rem;
            top: 0.75rem;
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.875rem;
        }
        #eventForm .hinata-textarea-wrap textarea.hinata-form-control {
            padding-left: 2.375rem;
            padding-top: 0.875rem;
        }
        #eventForm input[type="date"].hinata-form-control {
            padding-right: 2.75rem;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-xl"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-calendar-plus text-sm"></i></div>
                <h1 class="font-black text-slate-700 tracking-tight text-lg">イベント管理</h1>
            </div>
            <a href="/hinata/" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>ポータル</a>
        </header>

        <div class="p-[12px] max-w-[1320px] mx-auto w-full">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <div class="lg:col-span-2 space-y-6">
                    <section class="event-reg-card bg-white p-6 md:p-8 rounded-xl border <?= $cardBorder ?> shadow-sm">
                        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                                <h2 class="m-0 flex items-center gap-2 text-xl font-bold leading-tight <?= $cardDeco ?>"><i class="fa-solid fa-calendar-plus shrink-0 <?= $cardIconText ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>></i><span>イベントを登録</span></h2>
                                <button type="button" id="btnCancel" class="hidden text-xs font-bold text-red-400 shrink-0">新規に戻る</button>
                            </div>
                            <button type="submit" form="eventForm" id="btnSubmit" class="h-11 w-full shrink-0 rounded-lg bg-sky-500 px-6 text-sm font-black text-white shadow-md shadow-sky-200/80 transition-colors hover:bg-sky-600 sm:ml-auto sm:w-auto">💾保存</button>
                        </div>

                        <form id="eventForm" class="space-y-6">
                            <input type="hidden" name="id" id="event_id">
                            <input type="hidden" name="category" id="f_category" value="1">

                            <div>
                                <span class="hinata-admin-label">カテゴリ</span>
                                <div class="flex w-full max-w-full flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 p-1">
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg bg-white px-3 py-2 text-xs font-black text-slate-800 shadow-sm whitespace-nowrap transition-colors hover:bg-white" data-cat="1" aria-pressed="true">🎤 LIVE</button>
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg px-3 py-2 text-xs font-black text-slate-600 whitespace-nowrap transition-colors hover:bg-white" data-cat="2" aria-pressed="false">📸 ミーグリ</button>
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg px-3 py-2 text-xs font-black text-slate-600 whitespace-nowrap transition-colors hover:bg-white" data-cat="3" aria-pressed="false">🤝 リアルミ</button>
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg px-3 py-2 text-xs font-black text-slate-600 whitespace-nowrap transition-colors hover:bg-white" data-cat="4" aria-pressed="false">💿 リリース</button>
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg px-3 py-2 text-xs font-black text-slate-600 whitespace-nowrap transition-colors hover:bg-white" data-cat="5" aria-pressed="false">📺 メディア</button>
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg px-3 py-2 text-xs font-black text-slate-600 whitespace-nowrap transition-colors hover:bg-white" data-cat="6" aria-pressed="false">✨ スペイベ</button>
                                    <button type="button" class="hinata-event-cat-toggle rounded-lg px-3 py-2 text-xs font-black text-slate-600 whitespace-nowrap transition-colors hover:bg-white" data-cat="99" aria-pressed="false">📌 その他</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="hinata-admin-label" for="f_name">イベント名<span class="text-red-500 font-bold ml-0.5" aria-hidden="true">*</span></label>
                                    <div class="relative hinata-input-wrap">
                                        <i class="fa-solid fa-pen hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="text" name="event_name" id="f_name" required class="hinata-form-control">
                                    </div>
                                </div>
                                <div>
                                    <label class="hinata-admin-label" for="f_date">日付<span class="text-red-500 font-bold ml-0.5" aria-hidden="true">*</span></label>
                                    <div class="relative hinata-input-wrap">
                                        <i class="fa-regular fa-calendar hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="date" name="event_date" id="f_date" required class="hinata-form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                                <div id="mgRoundsWrap" class="hidden w-full shrink-0 md:w-44">
                                    <label class="hinata-admin-label" for="f_mg_rounds">部数</label>
                                    <div class="relative hinata-input-wrap">
                                        <i class="fa-solid fa-list-ol hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="number" name="mg_rounds" id="f_mg_rounds" min="1" max="30" value="6" class="hinata-form-control" placeholder="例: 6">
                                    </div>
                                </div>
                                <div class="min-w-0 w-full flex-1">
                                    <label class="hinata-admin-label" for="f_place">会場・場所</label>
                                    <div class="relative hinata-input-wrap">
                                        <i class="fa-solid fa-building hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="text" name="event_place" id="f_place" class="hinata-form-control" placeholder="例: 横浜アリーナ">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="hinata-admin-label" for="f_place_address">会場住所（Maps用）</label>
                                <div class="flex gap-2 items-center flex-wrap md:flex-nowrap">
                                    <div class="relative hinata-input-wrap min-w-0 flex-1 w-full md:w-auto md:flex-1">
                                        <i class="fa-solid fa-location-dot hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="text" name="event_place_address" id="f_place_address" class="hinata-form-control" placeholder="例: 神奈川県横浜市西区みなとみらい6-2-14">
                                    </div>
                                    <button type="button" id="btnGeocodePlace" class="shrink-0 min-h-[3rem] rounded-[0.625rem] border border-slate-300 bg-white px-4 text-sm font-black text-slate-700 shadow-sm transition-colors hover:border-slate-400 hover:bg-slate-50 disabled:opacity-50" title="住所から緯度経度を取得">
                                        📍座標取得
                                    </button>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500">LiveTrip の「エリア」表示に使います。都道府県まで含めて入力してください。</p>
                                <input type="hidden" name="latitude" id="f_latitude">
                                <input type="hidden" name="longitude" id="f_longitude">
                                <input type="hidden" name="place_id" id="f_place_id">
                            </div>

                            <div>
                                <label class="hinata-admin-label" for="f_info">詳細メモ</label>
                                <div class="relative hinata-textarea-wrap">
                                    <i class="fa-solid fa-align-left hinata-form-icon-tl" aria-hidden="true"></i>
                                    <textarea name="event_info" id="f_info" rows="3" class="hinata-form-control resize-y" placeholder="詳細メモ"></textarea>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="hinata-admin-label" for="f_url">特設サイト URL</label>
                                    <div class="relative hinata-input-wrap">
                                        <i class="fa-solid fa-link hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="url" name="event_url" id="f_url" class="hinata-form-control" placeholder="https://...">
                                    </div>
                                </div>
                                <div>
                                    <label class="hinata-admin-label" for="f_youtube">YouTube URL</label>
                                    <div class="relative hinata-input-wrap">
                                        <i class="fa-brands fa-youtube hinata-form-field-icon" aria-hidden="true"></i>
                                        <input type="url" name="youtube_url" id="f_youtube" class="hinata-form-control" placeholder="https://youtu.be/...">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="hinata-admin-label" for="f_hashtag">キャンペーンハッシュタグ<span class="hinata-admin-label-hint">（#不要・付けて入力してもOK）</span></label>
                                <div class="relative hinata-input-wrap">
                                    <i class="fa-solid fa-hashtag hinata-form-field-icon" aria-hidden="true"></i>
                                    <input type="text" name="event_hashtag" id="f_hashtag" class="hinata-form-control" placeholder="例: 七回目のひな誕祭">
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500">初参戦ガイドで「#○○ の動画」として表示されます。タイトルまたは説明にこのハッシュタグが含まれる動画が自動で横スクロール表示されます。</p>
                            </div>

                            <div>
                                <label class="hinata-admin-label">コラボ企画ページ URL</label>
                                <div id="collabUrlsContainer" class="space-y-2"></div>
                                <button type="button" id="btnAddCollabUrl" class="mt-2 text-xs font-bold text-sky-600 hover:text-sky-700"><i class="fa-solid fa-plus mr-1"></i>URLを追加</button>
                            </div>

                            <div class="pt-2">
                                <label class="hinata-admin-label mb-3">出演メンバー</label>
                                <div class="flex gap-6 mb-3">
                                    <label class="flex items-center gap-1 cursor-pointer font-bold text-sm"><input type="radio" name="cast_type" value="group" checked onchange="toggleMemberSelect()"> 全員</label>
                                    <label class="flex items-center gap-1 cursor-pointer font-bold text-sm"><input type="radio" name="cast_type" value="individual" onchange="toggleMemberSelect()"> 個別</label>
                                </div>
                                <div id="memberSelectArea" class="hidden max-h-48 overflow-y-auto rounded-[0.625rem] border border-slate-300 bg-white p-4 shadow-sm space-y-3">
                                    <?php $eventGrouped = MemberGroupHelper::group($members); ?>
                                    <?php foreach ($eventGrouped['order'] as $g): ?>
                                    <?php if (empty($eventGrouped['active'][$g])) continue; ?>
                                    <div>
                                        <p class="mb-2 text-xs font-bold tracking-wide text-[#333c4e]"><?= htmlspecialchars(MemberGroupHelper::getGenLabel($g)) ?></p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <?php foreach ($eventGrouped['active'][$g] as $m): ?>
                                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600"><input type="checkbox" name="member_ids[]" value="<?= $m['id'] ?>" class="w-4 h-4 rounded <?= $cardIconText ?>"<?= $isThemeHex ? ' style="accent-color: var(--hinata-theme)"' : '' ?>> <?= htmlspecialchars($m['name']) ?></label>
                                        <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (!empty($eventGrouped['graduates'])): ?>
                                    <div>
                                        <p class="mb-2 border-t border-slate-200 pt-3 mt-2 text-xs font-bold tracking-wide text-[#333c4e]">卒業生</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <?php foreach ($eventGrouped['graduates'] as $m): ?>
                                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600"><input type="checkbox" name="member_ids[]" value="<?= $m['id'] ?>" class="w-4 h-4 rounded <?= $cardIconText ?>"<?= $isThemeHex ? ' style="accent-color: var(--hinata-theme)"' : '' ?>> <?= htmlspecialchars($m['name']) ?></label>
                                        <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex gap-3 justify-end">
                                <button type="button" id="btnDelete" class="hidden w-14 h-14 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="hinata-mc-card bg-white border <?= $cardBorder ?> p-3 md:p-3.5" aria-labelledby="hinata-mc-heading">
                        <div class="flex items-center justify-between gap-1.5 pb-2 mb-1.5 border-b border-slate-100">
                            <h3 id="hinata-mc-heading" class="flex items-center gap-1.5 text-xs font-black text-[#1A1F2B] tracking-tight leading-tight">
                                <i class="fa-regular fa-calendar text-slate-500 text-[11px]" aria-hidden="true"></i>
                                ミニカレンダー
                            </h3>
                            <div class="flex items-center gap-0 shrink-0">
                                <button type="button" id="hinataMcPrev" class="p-1 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" title="前月" aria-label="前月">
                                    <i class="fa-solid fa-chevron-left text-[9px]"></i>
                                </button>
                                <span id="hinataMcBadge" class="text-[10px] font-bold text-slate-600 bg-[#F0F2F5] px-2 py-0.5 rounded-full whitespace-nowrap tabular-nums leading-tight"></span>
                                <button type="button" id="hinataMcNext" class="p-1 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" title="次月" aria-label="次月">
                                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-0 text-center text-[9px] font-bold text-[#B0B0B0] mb-0.5 leading-none" role="row">
                            <span class="py-0.5">日</span><span class="py-0.5">月</span><span class="py-0.5">火</span><span class="py-0.5">水</span>
                            <span class="py-0.5">木</span><span class="py-0.5">金</span><span class="py-0.5">土</span>
                        </div>
                        <div id="hinataMcGrid" class="grid grid-cols-7"></div>
                        <div id="hinataMcLegend" class="flex flex-wrap justify-center gap-x-3 gap-y-1 mt-2 pt-2 border-t border-slate-100 text-[9px] font-bold text-slate-500 leading-tight"></div>
                    </section>

                    <section class="hinata-recent-card bg-white rounded-2xl border <?= $cardBorder ?> overflow-hidden" aria-labelledby="hinata-recent-heading">
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <h3 id="hinata-recent-heading" class="flex items-center gap-2 text-sm font-black text-slate-800 tracking-tight">
                                    <i class="fa-regular fa-clock text-slate-400 text-[15px]" aria-hidden="true"></i>
                                    最近の編集
                                </h3>
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full whitespace-nowrap shrink-0">過去 7 日</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-3" role="tablist" aria-label="イベント種別">
                                <button type="button" class="hinata-recent-tab rounded-full px-3 py-1.5 text-[11px] transition-all bg-white shadow-sm text-slate-800 font-black" data-tab="all">すべて</button>
                                <button type="button" class="hinata-recent-tab rounded-full px-3 py-1.5 text-[11px] transition-all bg-slate-100/80 text-slate-500 font-bold hover:bg-slate-100" data-tab="live">LIVE</button>
                                <button type="button" class="hinata-recent-tab rounded-full px-3 py-1.5 text-[11px] transition-all bg-slate-100/80 text-slate-500 font-bold hover:bg-slate-100" data-tab="mg">ミーグリ</button>
                                <button type="button" class="hinata-recent-tab rounded-full px-3 py-1.5 text-[11px] transition-all bg-slate-100/80 text-slate-500 font-bold hover:bg-slate-100" data-tab="other">その他</button>
                            </div>

                            <div id="hinataRecentUpcoming" class="rounded-xl border border-slate-100 overflow-hidden divide-y divide-slate-100 bg-slate-50/30">
                                <?php if (empty($recentUpcoming)): ?>
                                    <p class="text-xs text-slate-400 py-8 text-center font-medium">本日以降のイベントはありません</p>
                                <?php else: ?>
                                    <?php foreach ($recentUpcoming as $ev): ?>
                                        <?php hinata_event_admin_render_recent_row($ev); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($recentPast)): ?>
                            <div id="hinataRecentPastWrap" class="hidden mt-3">
                                <p class="text-[10px] font-black text-slate-400 mb-2 tracking-wider">過去のイベント</p>
                                <div id="hinataRecentPast" class="rounded-xl border border-slate-100 overflow-hidden divide-y divide-slate-100 bg-slate-50/30">
                                    <?php foreach ($recentPast as $ev): ?>
                                        <?php hinata_event_admin_render_recent_row($ev); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="button" id="hinataRecentExpandBtn" class="w-full mt-4 flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white py-3 text-xs font-black text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-colors" aria-expanded="false">
                                <i class="fa-solid fa-table-list text-slate-400 text-sm" aria-hidden="true"></i>
                                <span class="hinata-recent-expand-label">一覧をすべて表示</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
    <script type="application/json" id="hinata-admin-mini-cal-json"><?= $hinataMiniCalJson ?></script>
    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        function toggleMemberSelect() {
            const isInd = document.querySelector('input[name="cast_type"]:checked').value === 'individual';
            document.getElementById('memberSelectArea').classList.toggle('hidden', !isInd);
        }
        function toggleMgRounds() {
            const cat = parseInt(document.getElementById('f_category').value, 10);
            document.getElementById('mgRoundsWrap').classList.toggle('hidden', cat !== 2 && cat !== 3);
        }
        function syncHinataEventCatToggle() {
            const cur = String(document.getElementById('f_category').value || '1');
            document.querySelectorAll('.hinata-event-cat-toggle').forEach(btn => {
                const on = btn.getAttribute('data-cat') === cur;
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                btn.classList.toggle('bg-white', on);
                btn.classList.toggle('shadow-sm', on);
                btn.classList.toggle('text-slate-800', on);
                btn.classList.toggle('text-slate-600', !on);
            });
        }
        document.querySelectorAll('.hinata-event-cat-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('f_category').value = btn.getAttribute('data-cat');
                toggleMgRounds();
                syncHinataEventCatToggle();
            });
        });
        toggleMgRounds();
        syncHinataEventCatToggle();

        function addCollabUrlInput(val) {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2';
            div.innerHTML = '<div class="relative hinata-input-wrap min-w-0 flex-1"><i class="fa-solid fa-link hinata-form-field-icon" aria-hidden="true"></i><input type="url" name="collaboration_urls[]" class="hinata-form-control" placeholder="https://..." value="' + (val || '').replace(/"/g, '&quot;') + '"></div><button type="button" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[0.625rem] border border-slate-300 bg-white text-slate-400 shadow-sm transition-colors hover:border-slate-400 hover:bg-slate-50 hover:text-red-500" onclick="this.parentElement.remove()" aria-label="このURL欄を削除"><i class="fa-solid fa-times"></i></button>';
            document.getElementById('collabUrlsContainer').appendChild(div);
        }
        document.getElementById('btnAddCollabUrl').onclick = () => addCollabUrlInput('');

        function editEvent(ev) {
            document.getElementById('event_id').value = ev.id;
            document.getElementById('f_name').value = ev.event_name;
            document.getElementById('f_date').value = ev.event_date;
            document.getElementById('f_category').value = String(ev.category != null ? ev.category : '1');
            document.getElementById('f_mg_rounds').value = ev.mg_rounds || 6;
            toggleMgRounds();
            syncHinataEventCatToggle();
            document.getElementById('f_place').value = ev.event_place || '';
            document.getElementById('f_place_address').value = ev.event_place_address || '';
            document.getElementById('f_latitude').value = ev.latitude || '';
            document.getElementById('f_longitude').value = ev.longitude || '';
            document.getElementById('f_place_id').value = ev.place_id || '';
            document.getElementById('f_info').value = ev.event_info || '';
            document.getElementById('f_url').value = ev.event_url || '';
            document.getElementById('f_hashtag').value = ev.event_hashtag || '';
            document.getElementById('f_youtube').value = '';
            document.getElementById('collabUrlsContainer').innerHTML = '';
            try {
                const urls = ev.collaboration_urls ? (typeof ev.collaboration_urls === 'string' ? JSON.parse(ev.collaboration_urls) : ev.collaboration_urls) : [];
                if (Array.isArray(urls) && urls.length) { urls.forEach(u => addCollabUrlInput(u)); } else { addCollabUrlInput(''); }
            } catch (_) { addCollabUrlInput(''); }
            document.getElementById('btnDelete').classList.remove('hidden');
            document.getElementById('btnCancel').classList.remove('hidden');
            document.getElementById('btnSubmit').innerText = '💾変更を保存';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        document.getElementById('btnCancel').onclick = () => location.reload();

        async function geocodePlaceAndSave() {
            const id = document.getElementById('event_id').value;
            const addr = document.getElementById('f_place_address').value || '';
            if (!id) {
                if (window.App?.toast) {
                    App.toast('先にイベントを保存してから実行してください（ID未設定）', 3500);
                } else {
                    alert('先にイベントを保存してから実行してください（ID未設定）');
                }
                return;
            }
            if (!addr.trim()) {
                if (window.App?.toast) {
                    App.toast('会場住所（Maps用）を入力してください', 3500);
                } else {
                    alert('会場住所（Maps用）を入力してください');
                }
                return;
            }
            const btn = document.getElementById('btnGeocodePlace');
            btn.disabled = true;
            const orig = btn.innerText;
            btn.innerText = '取得中...';
            try {
                const res = await App.post('api/geocode_event_place.php', { id: id, event_place_address: addr });
                if (res.status === 'success') {
                    const geo = res.geo || {};
                    document.getElementById('f_latitude').value = geo.latitude || '';
                    document.getElementById('f_longitude').value = geo.longitude || '';
                    document.getElementById('f_place_id').value = geo.place_id || '';
                    if (window.App?.toast) App.toast('座標を保存しました。', 2500);
                } else {
                    const msg = (res.message || '取得に失敗しました');
                    if (window.App?.toast) {
                        App.toast('座標取得に失敗しました: ' + msg, 4500);
                    } else {
                        alert('エラー: ' + msg);
                    }
                }
            } catch (e) {
                const msg = (e && e.message) ? e.message : '通信エラー';
                if (window.App?.toast) {
                    App.toast('座標取得に失敗しました: ' + msg, 4500);
                } else {
                    alert('エラー: ' + msg);
                }
            } finally {
                btn.disabled = false;
                btn.innerText = orig;
            }
        }

        document.getElementById('btnGeocodePlace')?.addEventListener('click', geocodePlaceAndSave);

        document.getElementById('eventForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.member_ids = Array.from(formData.getAll('member_ids[]'));
            data.collaboration_urls = Array.from(formData.getAll('collaboration_urls[]')).filter(u => String(u).trim());
            const cat = parseInt(data.category);
            if (cat !== 2 && cat !== 3) {
                data.mg_rounds = '';
            }
            const res = await App.post('api/save_event.php', data);
            if (res.status === 'success') location.reload(); else alert('エラー: ' + res.message);
        };
        document.getElementById('btnDelete').onclick = async () => {
            if (!confirm('削除しますか？')) return;
            const res = await App.post('api/delete_event.php', { id: document.getElementById('event_id').value });
            if (res.status === 'success') location.reload(); else alert('エラー: ' + res.message);
        }

        (function hinataRecentCardInit() {
            const listUp = document.getElementById('hinataRecentUpcoming');
            const wrapPast = document.getElementById('hinataRecentPastWrap');
            const btnExpand = document.getElementById('hinataRecentExpandBtn');
            const tabs = document.querySelectorAll('.hinata-recent-tab');
            if (!listUp) return;

            function bindList(el) {
                if (!el) return;
                el.addEventListener('click', function (e) {
                    const row = e.target.closest('.hinata-recent-row');
                    if (!row || !row.dataset.ev) return;
                    try {
                        editEvent(JSON.parse(row.dataset.ev));
                    } catch (err) {}
                });
            }
            bindList(listUp);
            bindList(document.getElementById('hinataRecentPast'));

            let activeTab = 'all';
            function applyFilter() {
                document.querySelectorAll('.hinata-recent-row').forEach(function (row) {
                    const f = row.getAttribute('data-filter') || 'other';
                    const show = activeTab === 'all' || f === activeTab;
                    row.classList.toggle('hidden', !show);
                });
            }
            function setTabActive(tabEl) {
                tabs.forEach(function (t) {
                    const on = t === tabEl;
                    if (on) {
                        t.classList.add('bg-white', 'shadow-sm', 'text-slate-800', 'font-black');
                        t.classList.remove('bg-slate-100/80', 'text-slate-500', 'font-bold');
                    } else {
                        t.classList.remove('bg-white', 'shadow-sm', 'text-slate-800', 'font-black');
                        t.classList.add('bg-slate-100/80', 'text-slate-500', 'font-bold');
                    }
                });
            }
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activeTab = tab.getAttribute('data-tab') || 'all';
                    setTabActive(tab);
                    applyFilter();
                });
            });
            applyFilter();

            if (btnExpand && wrapPast) {
                btnExpand.addEventListener('click', function () {
                    wrapPast.classList.toggle('hidden');
                    const expanded = !wrapPast.classList.contains('hidden');
                    btnExpand.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    const span = btnExpand.querySelector('.hinata-recent-expand-label');
                    if (span) span.textContent = expanded ? '過去を折りたたむ' : '一覧をすべて表示';
                });
            }
        })();

        (function initHinataMiniCalendar() {
            const elJson = document.getElementById('hinata-admin-mini-cal-json');
            const grid = document.getElementById('hinataMcGrid');
            const badge = document.getElementById('hinataMcBadge');
            const leg = document.getElementById('hinataMcLegend');
            if (!elJson || !grid || !badge || !leg) return;

            let data;
            try {
                data = JSON.parse(elJson.textContent);
            } catch (e) {
                return;
            }

            const events = data.events || [];
            const categories = data.categories || [];
            const catColor = {};
            categories.forEach(function (x) { catColor[x.c] = x.color; });

            const byDate = new Map();
            events.forEach(function (ev) {
                if (!ev.d) return;
                if (!byDate.has(ev.d)) byDate.set(ev.d, new Set());
                byDate.get(ev.d).add(ev.c);
            });

            const now = new Date();
            let viewY = now.getFullYear();
            let viewM = now.getMonth() + 1;

            function pad2(n) { return n < 10 ? '0' + n : String(n); }
            function iso(yr, mo, day) { return yr + '-' + pad2(mo) + '-' + pad2(day); }

            function buildCells(y, m) {
                const cells = [];
                const startDow = new Date(y, m - 1, 1).getDay();
                const dim = new Date(y, m, 0).getDate();
                const prevDim = new Date(y, m - 1, 0).getDate();

                for (let i = 0; i < startDow; i++) {
                    const day = prevDim - startDow + i + 1;
                    const mo = m === 1 ? 12 : m - 1;
                    const yr = m === 1 ? y - 1 : y;
                    cells.push({ yr: yr, mo: mo, day: day, inMonth: false });
                }
                for (let d = 1; d <= dim; d++) {
                    cells.push({ yr: y, mo: m, day: d, inMonth: true });
                }
                let ny = m === 12 ? y + 1 : y;
                let nm = m === 12 ? 1 : m + 1;
                let nd = 1;
                while (cells.length < 42) {
                    cells.push({ yr: ny, mo: nm, day: nd, inMonth: false });
                    nd++;
                    const maxN = new Date(ny, nm, 0).getDate();
                    if (nd > maxN) {
                        nd = 1;
                        nm++;
                        if (nm > 12) {
                            nm = 1;
                            ny++;
                        }
                    }
                }
                return cells;
            }

            function isToday(yr, mo, day) {
                return yr === now.getFullYear() && mo === now.getMonth() + 1 && day === now.getDate();
            }

            categories.forEach(function (x) {
                const row = document.createElement('span');
                row.className = 'inline-flex items-center gap-1.5';
                const dot = document.createElement('span');
                dot.className = 'rounded-full w-1 h-1 shrink-0';
                dot.style.background = x.color;
                dot.setAttribute('aria-hidden', 'true');
                const lbl = document.createElement('span');
                lbl.textContent = x.label;
                row.appendChild(dot);
                row.appendChild(lbl);
                leg.appendChild(row);
            });

            function render() {
                badge.textContent = viewY + '年' + viewM + '月';
                grid.innerHTML = '';
                const cells = buildCells(viewY, viewM);

                cells.forEach(function (cell) {
                    const ds = iso(cell.yr, cell.mo, cell.day);
                    const catSet = byDate.get(ds);
                    const cats = catSet ? Array.from(catSet).sort(function (a, b) { return a - b; }) : [];

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'hinata-mc-cell flex flex-col items-center justify-start rounded-md py-0.5 min-h-[30px] w-full border border-transparent hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 transition-colors leading-none';

                    const num = document.createElement('span');
                    num.className = 'text-[10px] font-bold leading-none tabular-nums ' +
                        (cell.inMonth ? 'text-[#4A4A4A]' : 'text-[#B0B0B0]/50');

                    if (cell.inMonth && isToday(cell.yr, cell.mo, cell.day)) {
                        btn.classList.add('hinata-mc-today');
                        num.className = 'hinata-mc-today-num text-[10px] font-black leading-none tabular-nums';
                    }

                    num.textContent = String(cell.day);

                    const dots = document.createElement('div');
                    dots.className = 'hinata-mc-dots mt-0.5 flex flex-wrap justify-center gap-px max-w-[26px] min-h-[5px]';
                    cats.forEach(function (c) {
                        const dot = document.createElement('span');
                        dot.className = 'rounded-full w-1 h-1 shrink-0';
                        dot.style.background = catColor[c] || '#94A3B8';
                        dot.setAttribute('aria-hidden', 'true');
                        dots.appendChild(dot);
                    });

                    btn.appendChild(num);
                    btn.appendChild(dots);
                    btn.addEventListener('click', function () {
                        var fDate = document.getElementById('f_date');
                        if (fDate) {
                            fDate.value = ds;
                            fDate.dispatchEvent(new Event('input', { bubbles: true }));
                            fDate.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        var form = document.getElementById('eventForm');
                        if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                    grid.appendChild(btn);
                });
            }

            document.getElementById('hinataMcPrev').addEventListener('click', function () {
                viewM--;
                if (viewM < 1) {
                    viewM = 12;
                    viewY--;
                }
                render();
            });
            document.getElementById('hinataMcNext').addEventListener('click', function () {
                viewM++;
                if (viewM > 12) {
                    viewM = 1;
                    viewY++;
                }
                render();
            });

            render();
        })();
    </script>
</body>
</html>