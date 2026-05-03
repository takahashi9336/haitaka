<?php
/**
 * 日向坂イベント管理 View (カテゴリ欄復活版)
 * 物理パス: haitaka/private/apps/Hinata/Views/event_admin.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
use App\Hinata\Helper\MemberGroupHelper;
use App\Hinata\Model\MediaAssetModel;
use App\Hinata\Service\EventRelatedLinkService;

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
    static $hinata_editor_media_asset = null;
    if ($hinata_editor_media_asset === null) {
        $hinata_editor_media_asset = new MediaAssetModel();
    }
    $c = (int)($ev['category'] ?? 99);
    $meta = hinata_event_admin_recent_row_meta($c);
    $ts = strtotime($ev['event_date'] ?? 'now');
    $monEn = strtoupper(date('M', $ts));
    $day = (string)(int)date('j', $ts);
    $place = trim((string)($ev['event_place'] ?? ''));
    $title = htmlspecialchars((string)($ev['event_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $payload = $ev;
    $payload['_editor_related_links'] = EventRelatedLinkService::buildLegacyLinksForEditor($ev, $hinata_editor_media_asset);
    /** @var list<int> $memberIds */
    $memberIds = [];
    $csvRaw = isset($ev['member_ids_csv']) ? trim((string)$ev['member_ids_csv']) : '';
    if ($csvRaw !== '') {
        foreach (explode(',', $csvRaw) as $sid) {
            $mid = (int)trim((string)$sid);
            if ($mid > 0 && $mid !== MemberGroupHelper::POKA_MEMBER_ID) {
                $memberIds[] = $mid;
            }
        }
    }
    $payload['member_ids'] = $memberIds;
    unset($payload['member_ids_csv']);
    $dataEv = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
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
$eventSeriesList = $eventSeriesList ?? [];
$hinataSeriesListJson = json_encode($eventSeriesList, JSON_UNESCAPED_UNICODE);
$hinataTokusetsuDomainsJson = json_encode(EventRelatedLinkService::parseTokusetsuDomainsFromEnv(), JSON_UNESCAPED_UNICODE);
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
        /* 会場住所 + 座標取得（1枠内） */
        #eventForm .hinata-place-address-combo {
            position: relative;
            display: block;
            width: 100%;
            min-height: 3rem;
            border-radius: 0.625rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        #eventForm .hinata-place-address-combo:hover {
            border-color: #94a3b8;
        }
        #eventForm .hinata-place-address-combo:focus-within {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.22);
        }
        #eventForm .hinata-place-address-combo .hinata-form-field-icon {
            z-index: 1;
        }
        #eventForm .hinata-place-address-input {
            display: block;
            width: 100%;
            min-height: 3rem;
            padding: 0.625rem 8.5rem 0.625rem 2.5rem;
            border: none;
            border-radius: 0.625rem;
            background: #fff;
            font-size: 0.875rem;
            color: #1e293b;
            outline: none;
        }
        #eventForm .hinata-place-address-input::placeholder {
            color: #94a3b8;
        }
        /* Chrome / Edge: 住所オートフィルの既定背景（水色っぽい色）を抑止 */
        #eventForm .hinata-place-address-input:-webkit-autofill,
        #eventForm .hinata-place-address-input:-webkit-autofill:hover,
        #eventForm .hinata-place-address-input:-webkit-autofill:focus,
        #eventForm .hinata-place-address-input:-webkit-autofill:active {
            -webkit-text-fill-color: #1e293b;
            caret-color: #1e293b;
            box-shadow: 0 0 0 1000px #fff inset;
            transition: background-color 99999s ease-out 0s;
        }
        #eventForm .hinata-place-geocode-btn {
            position: absolute;
            right: 0.375rem;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.65rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.6875rem;
            font-weight: 900;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            box-shadow: 0 1px 4px rgba(14, 165, 233, 0.35);
            transition: filter 0.15s, opacity 0.15s;
            white-space: nowrap;
        }
        #eventForm .hinata-place-geocode-btn:hover:not(:disabled) {
            filter: brightness(1.05);
        }
        #eventForm .hinata-place-geocode-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @media (max-width: 380px) {
            #eventForm .hinata-place-address-input {
                padding-right: 2.5rem;
                font-size: 0.8125rem;
            }
            #eventForm .hinata-place-geocode-btn span.hinata-place-geocode-label {
                display: none;
            }
            #eventForm .hinata-place-geocode-btn {
                padding: 0.5rem;
            }
        }
        /* 出演メンバー（カードグリッド） */
        #eventForm .hinata-member-card {
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        #eventForm .hinata-member-card:has(.hinata-member-cb:checked) {
            border-color: rgb(56 189 248);
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.22);
        }
        #eventForm .hinata-member-cb {
            accent-color: #0ea5e9;
        }
        #eventForm .hinata-mfilter-btn.hinata-mfilter-btn-active {
            background: #fff;
            border-color: rgb(125 211 252);
            color: rgb(3 105 161);
            font-weight: 900;
            box-shadow: 0 1px 2px rgba(15,23,42,0.06);
        }
        /* 関連リンク（チップ入力） */
        #hinataRelatedLinksShell.hinata-rel-shell {
            gap: 0.375rem 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        }
        #hinataRelatedLinkDraft.hinata-rel-draft-invalid {
            color: #b91c1c;
        }
        #hinataRelatedLinkDraft:focus {
            outline: none;
        }
        .hinata-rel-chip-wrap {
            position: relative;
            max-width: 100%;
        }
        /* ピル全体（× を内包） */
        .hinata-rel-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            max-width: 100%;
            padding: 0.25rem 0.35rem 0.25rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            border: none;
            cursor: default;
            transition: filter 0.12s;
        }
        .hinata-rel-chip:hover {
            filter: brightness(0.96);
        }
        /* 種別変更（ラベル部分のみクリック） */
        .hinata-rel-chip-main {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            min-width: 0;
            flex: 1 1 auto;
            padding: 0;
            margin: 0;
            border: none;
            background: transparent;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            font-size: inherit;
            color: inherit;
            text-align: left;
        }
        .hinata-rel-chip-main:focus-visible {
            outline: 2px solid rgba(14, 165, 233, 0.45);
            outline-offset: 2px;
            border-radius: 9999px;
        }
        .hinata-rel-chip-kind {
            opacity: 0.85;
            max-width: 12rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .hinata-rel-chip-x {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.35rem;
            height: 1.35rem;
            margin: 0;
            border-radius: 9999px;
            background: rgba(255,255,255,0.4);
            color: inherit;
            border: none;
            cursor: pointer;
            font-size: 0.65rem;
            line-height: 1;
            padding: 0;
            flex-shrink: 0;
        }
        .hinata-rel-chip-x:hover {
            background: rgba(255,255,255,0.55);
        }
        .hinata-rel-chip-x:focus-visible {
            outline: 2px solid rgba(14, 165, 233, 0.45);
            outline-offset: 1px;
        }
        .hinata-rel-kind-popover {
            position: absolute;
            z-index: 40;
            top: 100%;
            left: 0;
            margin-top: 4px;
            padding: 0.5rem;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            box-shadow: 0 6px 20px rgba(15,23,42,0.12);
            min-width: 10rem;
        }
        .hinata-rel-shell.hinata-rel-shell-invalid {
            border-color: #f87171;
            box-shadow: 0 0 0 2px rgba(248,113,113,0.25);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main id="hinataEventAdminScrollRoot" class="flex-1 flex flex-col min-w-0 overflow-y-auto">
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
                            <div class="flex w-full shrink-0 flex-col gap-2 sm:ml-auto sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-2">
                                <button type="button" id="btnDuplicate" class="hidden h-11 w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-xs font-black text-slate-600 shadow-sm hover:bg-slate-50 transition-colors sm:w-auto" title="このイベントを複製">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                    <span>このイベントを複製</span>
                                </button>
                                <button type="submit" form="eventForm" id="btnSubmit" class="h-11 w-full shrink-0 rounded-lg bg-sky-500 px-6 text-sm font-black text-white shadow-md shadow-sky-200/80 transition-colors hover:bg-sky-600 sm:w-auto">💾保存</button>
                            </div>
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

                            <div>
                                <label class="hinata-admin-label" for="f_series_id">系列（上位のくくり）</label>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2">
                                    <div class="min-w-0 flex-1">
                                        <input type="hidden" name="series_id" id="f_series_id" value="">
                                        <input type="text" id="f_series_name" list="hinataSeriesDatalist"
                                            class="hinata-form-control w-full"
                                            placeholder="（指定なし）/ 既存の系列名を検索"
                                            autocomplete="off">
                                    </div>
                                    <button type="button" id="hinata_series_clear" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-black text-slate-600 shadow-sm hover:bg-slate-50 whitespace-nowrap" title="系列を解除">解除</button>
                                    <button type="button" id="hinata_series_delete" class="shrink-0 rounded-lg border border-red-200 bg-white px-3 py-2 text-[11px] font-black text-red-600 shadow-sm hover:bg-red-50 whitespace-nowrap" title="系列を削除（参照0件のみ）">削除</button>
                                </div>
                                <datalist id="hinataSeriesDatalist">
                                    <?php foreach ($eventSeriesList as $ser): ?>
                                        <option value="<?= htmlspecialchars((string)($ser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
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
                                <div class="hinata-place-address-combo">
                                    <i class="fa-solid fa-location-dot hinata-form-field-icon" aria-hidden="true"></i>
                                    <input type="text" name="event_place_address" id="f_place_address" class="hinata-place-address-input" placeholder="例: 神奈川県横浜市西区みなとみらい6-2-14" autocomplete="street-address">
                                    <button type="button" id="btnGeocodePlace" class="hinata-place-geocode-btn" title="住所から緯度経度を取得">
                                        <i class="fa-solid fa-crosshairs text-[11px]" aria-hidden="true"></i>
                                        <span class="hinata-place-geocode-label">座標取得</span>
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

                            <div>
                                <label class="hinata-admin-label" for="hinataRelatedLinkDraft">関連リンク</label>
                                <p class="-mt-0.5 mb-2 text-xs leading-relaxed text-slate-500">URLを追加すると種別が自動判定されます（最大20件）。タグをクリックして種別を上書きできます。</p>
                                <div id="hinataRelatedLinksShell" class="hinata-rel-shell px-3 py-2 min-h-[3rem] w-full bg-white hover:border-[#94a3b8] transition-colors cursor-text flex flex-wrap items-center">
                                    <div id="hinataRelatedChips" class="flex flex-wrap gap-1.5 items-center max-w-full"></div>
                                    <input type="text" id="hinataRelatedLinkDraft" autocomplete="off" class="hinata-rel-draft flex-1 min-w-[min(100%,11rem)] min-h-[2.25rem] border-0 bg-transparent px-1 text-sm text-[#1e293b] placeholder:text-[#94a3b8] outline-none" placeholder="URLを貼り付け（種別は自動判定）">
                                </div>
                                <input type="hidden" name="related_links" id="related_links_hidden" value="[]">
                            </div>

                            <div>
                                <label class="hinata-admin-label" for="f_hashtag">キャンペーンハッシュタグ<span class="hinata-admin-label-hint">（#不要・付けて入力してもOK）</span></label>
                                <div class="relative hinata-input-wrap">
                                    <i class="fa-solid fa-hashtag hinata-form-field-icon" aria-hidden="true"></i>
                                    <input type="text" name="event_hashtag" id="f_hashtag" class="hinata-form-control" placeholder="例: 七回目のひな誕祭">
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500">初参戦ガイドで「#○○ の動画」として表示されます。タイトルまたは説明にこのハッシュタグが含まれる動画が自動で横スクロール表示されます。</p>
                            </div>

                            <div id="hinataCastMemberRoot" class="pt-2">
                                <span class="hinata-admin-label">出演メンバー</span>
                                <p class="mt-1 mb-2 text-xs leading-relaxed text-slate-500">カードまたはチェックボックスで選択してください。ポカは対象外です。誰も選ばない場合は従来どおり「全員」出演として保存されます。</p>

                                <?php
                                $eventGrouped = MemberGroupHelper::group($members);
                                $pokaId = MemberGroupHelper::POKA_MEMBER_ID;
                                $filterGenKeys = array_values(array_filter($eventGrouped['order'], static fn($x) => $x !== 'poka'));
                                ?>

                                <div class="mb-2 flex flex-wrap items-center gap-1.5" role="tablist" aria-label="メンバー表示フィルタ">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide mr-1">表示</span>
                                    <button type="button" class="hinata-mfilter-btn hinata-mfilter-btn-active rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-100 transition-colors" data-hinata-mfilter="active">現役</button>
                                    <?php if (!empty($eventGrouped['graduates'])): ?>
                                    <button type="button" class="hinata-mfilter-btn rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-100 transition-colors" data-hinata-mfilter="graduate">卒業生</button>
                                    <button type="button" class="hinata-mfilter-btn rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-100 transition-colors" data-hinata-mfilter="all">すべて</button>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide shrink-0">一括</span>
                                    <button type="button" id="hinataMemberBulkActiveAll" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-black text-slate-700 shadow-sm hover:bg-slate-50">現役メンバー全員</button>
                                    <?php foreach ($filterGenKeys as $g):
                                        if ($g === 'poka') continue;
                                        if (empty($eventGrouped['active'][$g])) continue;
                                        ?>
                                    <button type="button" class="hinata-mbulk-gen rounded-lg border border-slate-300 bg-white px-2 py-1 text-[10px] font-bold text-slate-600 shadow-sm hover:bg-slate-50" data-hinata-mbulk-gen="<?= is_numeric($g) ? (int)$g : 0 ?>"><?= htmlspecialchars(MemberGroupHelper::getGenLabel($g), ENT_QUOTES, 'UTF-8') ?>を選択</button>
                                    <?php endforeach; ?>
                                    <button type="button" id="hinataMemberBulkClear" class="rounded-lg border border-red-100 bg-red-50/90 px-2.5 py-1 text-[11px] font-black text-red-600 hover:bg-red-100">全解除</button>
                                </div>

                                <div id="hinataMemberScroll" class="max-h-[min(28rem,55vh)] overflow-y-auto rounded-[0.625rem] border border-slate-300 bg-white p-3 shadow-sm">
                                    <section class="hinata-member-gen-block" data-gen-block="active">
                                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
                                            <?php foreach ($eventGrouped['order'] as $g): ?>
                                                <?php if ($g === 'poka' || empty($eventGrouped['active'][$g])) continue; ?>
                                                <?php foreach ($eventGrouped['active'][$g] as $m): ?>
                                                    <?php if ((int)($m['id'] ?? 0) === $pokaId) continue; ?>
                                                    <?php
                                                    $mid = (int)$m['id'];
                                                    $mname = htmlspecialchars((string)($m['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                                                    $imgFile = isset($m['image_url']) ? trim((string)$m['image_url']) : '';
                                                    $imgSrc = $imgFile !== '' ? '/assets/img/members/' . htmlspecialchars($imgFile, ENT_QUOTES, 'UTF-8') : '';
                                                    $genNum = is_numeric($g) ? (int)$g : 0;
                                                    $accentStyle = $isThemeHex ? ' style="accent-color: var(--hinata-theme)"' : '';
                                                    $initial = function_exists('mb_substr')
                                                        ? htmlspecialchars(mb_substr((string)($m['name'] ?? '?'), 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8')
                                                        : htmlspecialchars(substr((string)($m['name'] ?? '?'), 0, 3), ENT_QUOTES, 'UTF-8');
                                                    ?>
                                            <label class="hinata-member-card relative flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-slate-100 bg-white px-2 pb-2 pt-6 text-center"
                                                data-cohort="active"
                                                data-generation="<?= $genNum ?>">
                                                <input type="checkbox" name="member_ids[]" value="<?= $mid ?>" id="hinata_mc_<?= $mid ?>" class="hinata-member-cb absolute right-2 top-2 z-10 h-4 w-4 rounded <?= $cardIconText ?>"<?= $accentStyle ?>>
                                                <?php if ($imgSrc !== ''): ?>
                                                <img src="<?= $imgSrc ?>" alt="" width="64" height="64" class="pointer-events-none h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-slate-100" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                <span class="pointer-events-none flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-200 text-lg font-black text-slate-500 ring-2 ring-slate-100"><?= $initial ?></span>
                                                <?php endif; ?>
                                                <span class="pointer-events-none w-full truncate text-[10px] font-bold leading-snug text-slate-700" title="<?= $mname ?>"><?= $mname ?></span>
                                            </label>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>

                                    <?php if (!empty($eventGrouped['graduates'])): ?>
                                    <section class="hinata-member-gen-block mt-4 hidden border-t border-slate-100 pt-4" data-gen-block="graduate">
                                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
                                            <?php foreach ($eventGrouped['graduates'] as $m): ?>
                                                <?php if ((int)($m['id'] ?? 0) === $pokaId) continue; ?>
                                                <?php
                                                $mid = (int)$m['id'];
                                                $mname = htmlspecialchars((string)($m['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                                                $imgFile = isset($m['image_url']) ? trim((string)$m['image_url']) : '';
                                                $imgSrc = $imgFile !== '' ? '/assets/img/members/' . htmlspecialchars($imgFile, ENT_QUOTES, 'UTF-8') : '';
                                                $accentStyle = $isThemeHex ? ' style="accent-color: var(--hinata-theme)"' : '';
                                                $initial = function_exists('mb_substr')
                                                    ? htmlspecialchars(mb_substr((string)($m['name'] ?? '?'), 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8')
                                                    : htmlspecialchars(substr((string)($m['name'] ?? '?'), 0, 3), ENT_QUOTES, 'UTF-8');
                                                ?>
                                            <label class="hinata-member-card relative flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-slate-100 bg-white px-2 pb-2 pt-6 text-center"
                                                data-cohort="graduate"
                                                data-generation="">
                                                <input type="checkbox" name="member_ids[]" value="<?= $mid ?>" id="hinata_mc_<?= $mid ?>" class="hinata-member-cb absolute right-2 top-2 z-10 h-4 w-4 rounded <?= $cardIconText ?>"<?= $accentStyle ?>>
                                                <?php if ($imgSrc !== ''): ?>
                                                <img src="<?= $imgSrc ?>" alt="" width="64" height="64" class="pointer-events-none h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-slate-100 opacity-95" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                <span class="pointer-events-none flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-100 text-lg font-black text-slate-400 ring-2 ring-slate-100"><?= $initial ?></span>
                                                <?php endif; ?>
                                                <span class="pointer-events-none w-full truncate text-[10px] font-bold leading-snug text-slate-600" title="<?= $mname ?>"><?= $mname ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                    <?php endif; ?>
                                </div>
                                <p id="hinataMemberPickCount" class="mt-2 text-xs font-black text-[#334155]" aria-live="polite">選択: 0 名</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 justify-end">
                                <button type="button" id="btnDelete" class="hidden w-14 h-14 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors" title="削除"><i class="fa-solid fa-trash-can"></i></button>
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
    <script type="application/json" id="hinata-event-tokusetsu-domains-json"><?= htmlspecialchars($hinataTokusetsuDomainsJson, ENT_NOQUOTES, 'UTF-8') ?></script>
    <script type="application/json" id="hinata-series-list-json"><?= htmlspecialchars($hinataSeriesListJson ?? '[]', ENT_NOQUOTES, 'UTF-8') ?></script>
    <script src="/assets/js/core.js?v=2"></script>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
        (function hinataCastMemberUiInit() {
            const root = document.getElementById('hinataCastMemberRoot');
            if (!root) return;

            function applyMemberFilter(mode) {
                root.querySelectorAll('.hinata-member-gen-block').forEach(function (block) {
                    const key = block.getAttribute('data-gen-block') || '';
                    if (mode === 'all') {
                        block.classList.remove('hidden');
                    } else if (mode === 'active') {
                        block.classList.toggle('hidden', key !== 'active');
                    } else if (mode === 'graduate') {
                        block.classList.toggle('hidden', key !== 'graduate');
                    }
                });
                root.querySelectorAll('.hinata-mfilter-btn').forEach(function (b) {
                    const on = (b.getAttribute('data-hinata-mfilter') || '') === String(mode);
                    b.classList.toggle('hinata-mfilter-btn-active', on);
                });
            }

            root.addEventListener('click', function (e) {
                const fb = e.target.closest('.hinata-mfilter-btn');
                if (fb && root.contains(fb)) {
                    applyMemberFilter(fb.getAttribute('data-hinata-mfilter') || 'active');
                }
            });

            function updatePickCount() {
                const el = document.getElementById('hinataMemberPickCount');
                if (!el) return;
                const n = document.querySelectorAll('#hinataCastMemberRoot .hinata-member-cb:checked').length;
                el.textContent = '選択: ' + n + ' 名';
            }

            window.hinataRestoreMemberCheckboxes = function (ids) {
                const raw = Array.isArray(ids) ? ids : [];
                const set = new Set();
                raw.forEach(function (x) {
                    const id = parseInt(String(x), 10);
                    if (id > 0) set.add(String(id));
                });
                document.querySelectorAll('#hinataCastMemberRoot .hinata-member-cb').forEach(function (cb) {
                    cb.checked = set.has(String(cb.value));
                });
                updatePickCount();
            };

            root.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('hinata-member-cb')) updatePickCount();
            });

            document.getElementById('hinataMemberBulkActiveAll')?.addEventListener('click', function () {
                document.querySelectorAll('#hinataCastMemberRoot .hinata-member-card[data-cohort="active"] .hinata-member-cb').forEach(function (cb) {
                    cb.checked = true;
                });
                updatePickCount();
            });

            root.querySelectorAll('.hinata-mbulk-gen').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const g = btn.getAttribute('data-hinata-mbulk-gen');
                    if (g == null || g === '') return;
                    document.querySelectorAll('#hinataCastMemberRoot .hinata-member-card[data-cohort="active"]').forEach(function (card) {
                        if (String(card.getAttribute('data-generation') || '') !== String(g)) return;
                        const cb = card.querySelector('.hinata-member-cb');
                        if (cb) cb.checked = true;
                    });
                    updatePickCount();
                });
            });

            document.getElementById('hinataMemberBulkClear')?.addEventListener('click', function () {
                document.querySelectorAll('#hinataCastMemberRoot .hinata-member-cb').forEach(function (cb) {
                    cb.checked = false;
                });
                updatePickCount();
            });

            window.hinataUpdateMemberPickCount = updatePickCount;
            applyMemberFilter('active');
            updatePickCount();
        })();
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

        (function hinataRelatedLinkInit() {
            const HINATA_REL_MAX = 20;
            const YOUTUBE_RE = /(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|shorts\/|.*[?&]v=)|youtu\.be\/)([^"&?/ ]{11})/i;
            function tokDomainsParsed() {
                const el = document.getElementById('hinata-event-tokusetsu-domains-json');
                try { return JSON.parse(el && el.textContent ? el.textContent : '[]'); } catch (_) { return []; }
            }
            const hinataTokusetsuDomains = Array.isArray(tokDomainsParsed()) ? tokDomainsParsed() : [];
            /** @typedef {{ url: string, kind: string, manual_override: boolean }} HinataRel */

            /** @type {{ links: HinataRel[] }} */
            const HinataRelatedLinkState = { links: [] };
            /** @type {HTMLElement|null} */
            let hinataRelOpenPopoverWrap = null;

            function hinataEscapeHtml(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            }

            function hinataNormalizeUrl(raw) {
                let s = String(raw ?? '').trim();
                if (!s) return null;
                if (!/^https?:\/\//i.test(s)) s = 'https://' + s;
                let u;
                try { u = new URL(s); } catch (_) { return null; }
                if (!u.hostname) return null;
                const host = u.hostname.toLowerCase();
                let path = u.pathname || '';
                if (path === '/' || path === '') path = '';
                else path = '/' + path.split('/').filter(Boolean).join('/');
                const sp = new URLSearchParams(u.search);
                for (const k of [...sp.keys()]) {
                    if (/^utm_/i.test(k)) sp.delete(k);
                }
                const qs = sp.toString();
                let out = 'https://' + host + path;
                if (qs) out += '?' + qs;
                if (u.hash) out += u.hash;
                return out;
            }

            function hinataYoutubeVideoMatch(url) {
                return YOUTUBE_RE.test(url);
            }

            function hinataYoutubeHost(host) {
                if (host === 'youtu.be') return true;
                return /\.youtube\.com$/.test(host) || host === 'youtube.com'
                    || /\.youtube-nocookie\.com$/.test(host) || host === 'youtube-nocookie.com';
            }

            function hinataTokusetsuHost(host) {
                if (host === 'hinatazaka46.com' || host.endsWith('.hinatazaka46.com')) return true;
                return hinataTokusetsuDomains.some(function (d) {
                    var x = String(d).toLowerCase().trim();
                    if (!x) return false;
                    return host === x || host.endsWith('.' + x);
                });
            }

            function hinataClassifyKind(norm) {
                if (!norm) return 'other';
                if (hinataYoutubeVideoMatch(norm)) return 'youtube';
                try {
                    const pu = new URL(norm);
                    const host = pu.hostname.toLowerCase();
                    const path = (pu.pathname || '').toLowerCase();
                    if (hinataTokusetsuHost(host)) return 'tokusetsu';
                    const tokens = ['/collab', '/campaign', '/cp/', '/special'];
                    for (let i = 0; i < tokens.length; i++) {
                        if (path.includes(tokens[i])) return 'collab';
                    }
                    if (!hinataYoutubeHost(host) && !hinataTokusetsuHost(host)) return 'collab';
                    return 'other';
                } catch (_) {
                    return 'other';
                }
            }

            function hinataRelKindUi(kind) {
                const map = {
                    youtube: { cls: 'hinata-rel-chip shadow-sm shadow-red-900/10', icon: 'fa-brands fa-youtube', lab: 'YouTube', styleAttr: 'color:#b91c1c;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);' },
                    tokusetsu: { cls: 'hinata-rel-chip shadow-sm', icon: 'fa-solid fa-globe', lab: '特設', styleAttr: 'color:#0369a1;background:rgba(14,165,233,0.12);border:1px solid rgba(14,165,233,0.35);' },
                    collab: { cls: 'hinata-rel-chip shadow-sm', icon: 'fa-solid fa-handshake', lab: 'コラボ', styleAttr: 'color:#4338ca;background:rgba(99,102,241,0.14);border:1px solid rgba(99,102,241,0.4);' },
                    other: { cls: 'hinata-rel-chip shadow-sm', icon: 'fa-solid fa-link', lab: 'リンク', styleAttr: 'color:#475569;background:rgba(107,119,148,0.12);border:1px solid rgba(107,119,148,0.35);' }
                };
                return map[kind] || map.other;
            }

            function shortUrlLabel(norm) {
                try {
                    const u = new URL(norm);
                    const p = !u.pathname || u.pathname === '/' ? '' : u.pathname;
                    const s = u.hostname + p;
                    return s.length > 44 ? s.slice(0, 41) + '…' : s;
                } catch (_) {
                    return norm.slice(0, 44);
                }
            }

            function hinataRelatedLinkClosePopover(force) {
                if (!hinataRelOpenPopoverWrap) return;
                if (force !== true && document.activeElement && hinataRelOpenPopoverWrap.contains(document.activeElement)) return;
                const pop = hinataRelOpenPopoverWrap.querySelector('.hinata-rel-kind-popover');
                if (pop) pop.classList.add('hidden');
                hinataRelOpenPopoverWrap = null;
            }

            function syncRelatedHiddenJson() {
                const h = document.getElementById('related_links_hidden');
                if (h) h.value = JSON.stringify(HinataRelatedLinkState.links);
            }

            window.hinataRelatedLinkGetPayload = function () {
                return HinataRelatedLinkState.links.map(function (L) {
                    return { url: L.url, kind: L.kind, manual_override: !!L.manual_override };
                });
            };

            function hinataRelatedLinkRender() {
                const chips = document.getElementById('hinataRelatedChips');
                if (!chips) return;
                chips.innerHTML = '';
                HinataRelatedLinkState.links.forEach(function (L, idx) {
                    const meta = hinataRelKindUi(L.kind);
                    const wrap = document.createElement('div');
                    wrap.className = 'hinata-rel-chip-wrap max-w-full';

                    const pop = document.createElement('div');
                    pop.className = 'hinata-rel-kind-popover hidden';
                    const sel = document.createElement('select');
                    sel.className = 'w-full text-xs font-bold rounded border border-slate-200 px-2 py-1';
                    [['youtube', 'YouTube'], ['tokusetsu', '特設'], ['collab', 'コラボ'], ['other', 'その他']].forEach(function (opt) {
                        const o = document.createElement('option');
                        o.value = opt[0];
                        o.textContent = opt[1];
                        if (L.kind === opt[0]) o.selected = true;
                        sel.appendChild(o);
                    });
                    sel.addEventListener('click', function (ev) { ev.stopPropagation(); });
                    sel.addEventListener('change', function () {
                        HinataRelatedLinkState.links[idx].kind = sel.value;
                        HinataRelatedLinkState.links[idx].manual_override = true;
                        hinataRelatedLinkRender();
                        hinataRelatedLinkClosePopover(true);
                    });
                    pop.appendChild(sel);

                    const pill = document.createElement('div');
                    pill.className = meta.cls;
                    if (meta.styleAttr) pill.setAttribute('style', meta.styleAttr);
                    pill.setAttribute('title', L.url);

                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'hinata-rel-chip-main';
                    row.setAttribute('title', L.url);
                    row.setAttribute('aria-label', '種別を変更');
                    row.innerHTML = '<i class="' + hinataEscapeHtml(meta.icon) + '" aria-hidden="true"></i><span class="hinata-rel-chip-kind">' + hinataEscapeHtml(meta.lab + ': ' + shortUrlLabel(L.url)) + '</span>';
                    row.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (hinataRelOpenPopoverWrap === wrap) {
                            hinataRelatedLinkClosePopover(true);
                            return;
                        }
                        hinataRelatedLinkClosePopover(true);
                        hinataRelOpenPopoverWrap = wrap;
                        pop.classList.remove('hidden');
                    });

                    const xb = document.createElement('button');
                    xb.type = 'button';
                    xb.className = 'hinata-rel-chip-x shrink-0';
                    xb.setAttribute('aria-label', 'このリンクを削除');
                    xb.innerHTML = '<i class="fa-solid fa-times" aria-hidden="true"></i>';
                    xb.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        HinataRelatedLinkState.links.splice(idx, 1);
                        hinataRelatedLinkClosePopover(true);
                        hinataRelatedLinkRender();
                    });

                    pill.appendChild(row);
                    pill.appendChild(xb);
                    wrap.appendChild(pill);
                    wrap.appendChild(pop);
                    chips.appendChild(wrap);
                });
                syncRelatedHiddenJson();
            }

            window.hinataRelatedLinkSetAll = function (arr) {
                HinataRelatedLinkState.links = Array.isArray(arr) ? arr.filter(function (x) { return x && String(x.url || '').trim() !== ''; }).slice(0, HINATA_REL_MAX).map(function (x) {
                    const k = ['youtube','tokusetsu','collab','other'].indexOf(x.kind) !== -1 ? x.kind : 'other';
                    return { url: String(x.url), kind: k, manual_override: !!x.manual_override };
                }) : [];
                hinataRelatedLinkRender();
            };

            function showDraftValidationError(shell, draftEl) {
                shell.classList.add('hinata-rel-shell-invalid');
                draftEl.classList.add('hinata-rel-draft-invalid');
                setTimeout(function () {
                    shell.classList.remove('hinata-rel-shell-invalid');
                    draftEl.classList.remove('hinata-rel-draft-invalid');
                }, 2200);
            }

            function hinataLegacyEditorLinks(ev) {
                /** @type {HinataRel[]} */
                const out = [];
                const eu = typeof ev.event_url === 'string' ? ev.event_url.trim() : '';
                if (eu) {
                    const n = hinataNormalizeUrl(eu);
                    if (n) out.push({ url: n, kind: 'tokusetsu', manual_override: false });
                }
                var vk = ev.video_key ? String(ev.video_key).trim() : '';
                if (vk) {
                    const ytUrl = hinataNormalizeUrl('https://www.youtube.com/watch?v=' + vk) || ('https://www.youtube.com/watch?v=' + vk);
                    out.push({ url: ytUrl, kind: 'youtube', manual_override: false });
                }
                var rawC = ev.collaboration_urls;
                var urls = [];
                if (Array.isArray(rawC)) urls = rawC;
                else if (typeof rawC === 'string' && rawC.trim()) {
                    try { var d = JSON.parse(rawC); if (Array.isArray(d)) urls = d; } catch (_) {}
                }
                urls.forEach(function (u0) {
                    if (typeof u0 !== 'string' || !u0.trim()) return;
                    var n = hinataNormalizeUrl(u0.trim());
                    if (!n) return;
                    out.push({ url: n, kind: hinataClassifyKind(n), manual_override: false });
                });
                return out.slice(0, HINATA_REL_MAX);
            }

            window.hinataRelatedLinkCommitOneRaw = function (raw) {
                const draft = document.getElementById('hinataRelatedLinkDraft');
                const shell = document.getElementById('hinataRelatedLinksShell');
                if (!draft || !shell) return false;
                const norm = hinataNormalizeUrl(raw);
                if (!norm) {
                    showDraftValidationError(shell, draft);
                    return false;
                }
                if (HinataRelatedLinkState.links.some(function (L) { return L.url === norm; })) {
                    if (window.App && App.toast) App.toast('同じURLが既に追加されています', 3200); else alert('同じURLが既に追加されています');
                    return false;
                }
                if (HinataRelatedLinkState.links.length >= HINATA_REL_MAX) {
                    if (window.App && App.toast) App.toast('関連リンクは最大' + HINATA_REL_MAX + '件までです', 3400); else alert('関連リンクは最大' + HINATA_REL_MAX + '件までです');
                    return false;
                }
                HinataRelatedLinkState.links.push({ url: norm, kind: hinataClassifyKind(norm), manual_override: false });
                hinataRelatedLinkRender();
                return true;
            };

            window.hinataRelatedLinkFlushDraftField = function () {
                var draft = document.getElementById('hinataRelatedLinkDraft');
                if (!draft) return;
                let raw = String(draft.value || '').trim();
                if (!raw) return false;
                const parts = raw.split(/[\s,]+/).map(function (x) { return x.trim(); }).filter(Boolean);
                let ok = false;
                parts.forEach(function (p) { if (window.hinataRelatedLinkCommitOneRaw(p)) ok = true; });
                draft.value = '';
                return ok;
            };

            document.addEventListener('click', function (e) {
                var t = e.target;
                if (t instanceof Element && !t.closest('.hinata-rel-chip-wrap')) hinataRelatedLinkClosePopover(true);
            });

            const shell = document.getElementById('hinataRelatedLinksShell');
            const draftEl = document.getElementById('hinataRelatedLinkDraft');
            if (shell && draftEl) {
                shell.addEventListener('click', function (e) {
                    if (!e.target.closest('.hinata-rel-chip-wrap')) draftEl.focus();
                });
                draftEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        window.hinataRelatedLinkFlushDraftField();
                    } else if (e.key === ',') {
                        e.preventDefault();
                        window.hinataRelatedLinkFlushDraftField();
                    }
                });
                draftEl.addEventListener('blur', function () {
                    setTimeout(function () {
                        if (document.activeElement === draftEl) return;
                        if (shell.contains(document.activeElement)) return;
                        window.hinataRelatedLinkFlushDraftField();
                    }, 140);
                });
            }

            window.hinataRelatedLinkRenderFromEvent = function (ev) {
                /** @type {HinataRel[]} */
                var src = [];
                if (Object.prototype.hasOwnProperty.call(ev, '_editor_related_links') && Array.isArray(ev._editor_related_links)) {
                    src = ev._editor_related_links.map(function (x) {
                        var ku = ['youtube','tokusetsu','collab','other'].indexOf(String(x.kind)) !== -1 ? String(x.kind) : 'other';
                        return { url: String(x.url || '').trim(), kind: ku, manual_override: !!x.manual_override };
                    }).filter(function (x) { return x.url; });
                } else src = hinataLegacyEditorLinks(ev);
                window.hinataRelatedLinkSetAll(src);
                var d = document.getElementById('hinataRelatedLinkDraft');
                if (d) d.value = '';
            };

            hinataRelatedLinkSetAll([]);
        })();

        /** イベント管理レイアウトではスクロールは body ではなく main 側（overflow-y-auto） */
        function hinataScrollEventAdminToTop() {
            const root = document.getElementById('hinataEventAdminScrollRoot');
            if (root && typeof root.scrollTo === 'function') {
                root.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        /** @param {boolean} isEdit 既存IDの編集モード（false=新規登録相当） */
        function setEventFormMode(isEdit) {
            document.getElementById('btnDelete').classList.toggle('hidden', !isEdit);
            document.getElementById('btnDuplicate').classList.toggle('hidden', !isEdit);
            document.getElementById('btnCancel').classList.toggle('hidden', !isEdit);
            document.getElementById('btnSubmit').innerText = isEdit ? '💾変更を保存' : '💾保存';
        }

        function editEvent(ev) {
            document.getElementById('event_id').value = ev.id;
            document.getElementById('f_name').value = ev.event_name;
            document.getElementById('f_date').value = ev.event_date;
            document.getElementById('f_category').value = String(ev.category != null ? ev.category : '1');
            const fSeries = document.getElementById('f_series_id');
            if (fSeries) {
                const sid = ev.series_id;
                fSeries.value = (sid != null && String(sid) !== '' && parseInt(String(sid), 10) > 0) ? String(parseInt(String(sid), 10)) : '';
                fSeries.dataset.hinataSeriesPrev = fSeries.value || '';
            }
            const fSeriesName = document.getElementById('f_series_name');
            if (fSeriesName) {
                fSeriesName.value = (typeof ev.series_name === 'string' && ev.series_name.trim() !== '') ? ev.series_name.trim() : '';
            }
            document.getElementById('f_mg_rounds').value = ev.mg_rounds || 6;
            toggleMgRounds();
            syncHinataEventCatToggle();
            document.getElementById('f_place').value = ev.event_place || '';
            document.getElementById('f_place_address').value = ev.event_place_address || '';
            document.getElementById('f_latitude').value = ev.latitude || '';
            document.getElementById('f_longitude').value = ev.longitude || '';
            document.getElementById('f_place_id').value = ev.place_id || '';
            document.getElementById('f_info').value = ev.event_info || '';
            window.hinataRelatedLinkRenderFromEvent(ev);
            if (typeof hinataRestoreMemberCheckboxes === 'function') hinataRestoreMemberCheckboxes(ev.member_ids || []);
            document.getElementById('f_hashtag').value = ev.event_hashtag || '';
            setEventFormMode(true);
            if (typeof window.hinataUpdateMemberPickCount === 'function') window.hinataUpdateMemberPickCount();
            hinataScrollEventAdminToTop();
        }

        function duplicateEventFromForm() {
            const idEl = document.getElementById('event_id');
            if (!idEl || !String(idEl.value || '').trim()) return;
            if (!confirm('表示中のイベントを複製して新規登録モードに切り替えます。\n入力内容・日付・出演メンバー・関連リンクはそのままです（保存するまで画面だけの状態です）。よろしいですか？')) {
                return;
            }
            idEl.value = '';
            setEventFormMode(false);
            document.getElementById('btnCancel').classList.remove('hidden');
            if (typeof window.hinataUpdateMemberPickCount === 'function') window.hinataUpdateMemberPickCount();
            hinataScrollEventAdminToTop();
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
            const geoLabel = btn ? btn.querySelector('.hinata-place-geocode-label') : null;
            btn.disabled = true;
            const origGeoLabel = geoLabel ? geoLabel.textContent.trim() : (btn.innerText || '');
            if (geoLabel) geoLabel.textContent = '取得中…';
            else btn.textContent = '取得中…';
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
                if (geoLabel) geoLabel.textContent = origGeoLabel || '座標取得';
                else btn.textContent = origGeoLabel;
            }
        }

        document.getElementById('btnGeocodePlace')?.addEventListener('click', geocodePlaceAndSave);

        document.getElementById('btnDuplicate')?.addEventListener('click', duplicateEventFromForm);

        (function hinataSeriesPickerUxInit() {
            const idEl = document.getElementById('f_series_id');
            const nameEl = document.getElementById('f_series_name');
            const clearBtn = document.getElementById('hinata_series_clear');
            const delBtn = document.getElementById('hinata_series_delete');
            if (!idEl || !nameEl) return;

            /** @type {Array<{id:number|string,name:string}>} */
            const list = (() => {
                try {
                    const el = document.getElementById('hinata-series-list-json');
                    if (!el) return [];
                    const raw = JSON.parse(el.textContent || '[]');
                    return Array.isArray(raw) ? raw : [];
                } catch (_) { return []; }
            })();
            const byName = new Map();
            const byId = new Map();
            list.forEach(function (x) {
                const n = String(x.name || '').trim();
                const id = parseInt(String(x.id || ''), 10);
                if (n && id > 0) {
                    byName.set(n, String(id));
                    byId.set(String(id), n);
                }
            });

            function syncFromName() {
                const n = String(nameEl.value || '').trim();
                if (!n) {
                    idEl.value = '';
                    return;
                }
                const sid = byName.get(n);
                idEl.value = sid ? sid : '';
            }

            nameEl.addEventListener('change', syncFromName);
            nameEl.addEventListener('input', function () {
                const n = String(nameEl.value || '').trim();
                if (!n) idEl.value = '';
            });
            clearBtn?.addEventListener('click', function () {
                nameEl.value = '';
                idEl.value = '';
                if (window.App?.toast) App.toast('系列を解除しました', 2000);
            });

            delBtn?.addEventListener('click', async function () {
                const n = String(nameEl.value || '').trim();
                if (!n) {
                    if (window.App?.toast) App.toast('削除する系列名を入力してください', 2600);
                    else alert('削除する系列名を入力してください');
                    return;
                }
                const sid = byName.get(n);
                if (!sid) {
                    alert('登録済みの系列名と一致しません（候補から選んでください）');
                    return;
                }
                if (!confirm('系列「' + n + '」を削除します。\n※この系列を参照しているイベントが1件でもある場合は削除できません。\nよろしいですか？')) return;
                const res = await App.post('api/delete_event_series.php', { id: parseInt(sid, 10) });
                if (res.status !== 'success') {
                    alert(res.message || '削除に失敗しました');
                    return;
                }
                // datalist/options とローカルMapから削除
                const dl = document.getElementById('hinataSeriesDatalist');
                if (dl) {
                    Array.from(dl.querySelectorAll('option')).forEach(function (o) {
                        if (String(o.value || '') === n) o.remove();
                    });
                }
                byName.delete(n);
                byId.delete(String(sid));
                nameEl.value = '';
                idEl.value = '';
                if (window.App?.toast) App.toast('系列を削除しました', 2200);
            });
        })();

        document.getElementById('eventForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.member_ids = Array.from(formData.getAll('member_ids[]'));
            data.related_links = typeof hinataRelatedLinkGetPayload === 'function' ? hinataRelatedLinkGetPayload() : [];
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