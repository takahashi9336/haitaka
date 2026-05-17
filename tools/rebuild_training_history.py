# ASCII-only source; writes UTF-8 Japanese to training_history.php
from pathlib import Path

OUT = Path(__file__).resolve().parents[1] / 'private/apps/Health/Views/training_history.php'
T = {
    'comment': '\u30c8\u30ec\u30fc\u30cb\u30f3\u30b0\u5b9f\u65bd\u5c65\u6b74',
    'title': '\u30c8\u30ec\u30fc\u30cb\u30f3\u30b0\u5b9f\u65bd\u5c65\u6b74',
    'h1': '\u30c8\u30ec\u30fc\u30cb\u30f3\u30b0\u5b9f\u65bd\u5c65\u6b74',
    'h2_add': 'HIIT\u5b9f\u65bd\u3092\u5f8c\u304b\u3089\u8ffd\u52a0',
    'label_date': '\u5b9f\u65bd\u65e5',
    'btn_add': 'HIIT\u3092\u8ffd\u52a0',
    'hint': '\u76f4\u8fd13\u30f6\u6708\u5206\u3092\u8868\u793a\u3002\u8ffd\u52a0\u6642\u306f\u73fe\u5728\u306e\u767b\u9332\u30e1\u30cb\u30e5\u30fc\u304c\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3068\u3057\u3066\u4fdd\u5b58\u3055\u308c\u307e\u3059\u3002',
    'h2_list': '\u8a18\u9332\u4e00\u89a7',
    'th_date': '\u5b9f\u65bd\u65e5',
    'th_content': '\u5185\u5bb9',
    'th_count': '\u7a2e\u76ee\u6570',
    'th_duration': '\u5408\u8a08\u6642\u9593',
    'th_action': '\u64cd\u4f5c',
    'empty': '\u5c65\u6b74\u304c\u3042\u308a\u307e\u305b\u3093',
}
P = '___TAG___'

html = f'''<?php
/**
 * Health: {T["comment"]} View
 */
$appKey = 'health_training_history';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{T["title"]} - Health</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body {{ font-family: 'Inter', 'Noto Sans JP', sans-serif; }}
        .sidebar {{ transition: width 0.3s; width: 240px; }}
        @media (max-width: 768px) {{
            .sidebar {{ position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }}
            .sidebar.mobile-open {{ transform: translateX(0); }}
        }}
        input:focus, select:focus {{ outline: none; }}
        details.hiit-log summary {{ cursor: pointer; list-style: none; }}
        details.hiit-log summary::-webkit-details-marker {{ display: none; }}
    </style>
    <?php if ($isThemeHex): ?>
    <style>
        input[type="number"], input[type="date"], select {{ accent-color: var(--health-theme); }}
    </style>
    <?php endif; ?>
    <style>:root {{ --health-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }}</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10 gap-3">
            <{P} class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/health/training_menu.php" class="text-slate-400 hover:text-slate-600 transition shrink-0"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <{P} class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg shrink-0 <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-clipboard-check text-sm"></i>
                </{P}>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter truncate">{T["h1"]}</h1>
            </{P}>
        </header>

        <{P} class="flex-1 overflow-y-auto p-4 md:p-10">
            <{P} class="max-w-4xl mx-auto space-y-8">
                <{P} class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm p-5">
                    <h2 class="text-sm font-black text-slate-600 mb-4">{T["h2_add"]}</h2>
                    <{P} class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <{P} class="flex flex-col md:col-span-2">
                            <label class="text-xs font-black text-slate-500 mb-1 ml-1">{T["label_date"]}</label>
                            <input type="date" id="logDate" class="border p-2 rounded-md border-slate-300 h-[42px] w-full">
                        </{P}>
                        <{P} class="flex flex-col justify-end">
                            <button id="addLogBtn" class="<?= $btnBgClass ?> text-white p-2 rounded-md transition h-[42px] font-bold w-full"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>>
                                {T["btn_add"]}
                            </button>
                        </{P}>
                    </{P}>
                    <p class="text-[11px] text-slate-400 mt-3">{T["hint"]}</p>
                </{P}>

                <{P} class="bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden overflow-x-auto">
                    <h2 class="text-sm font-black text-slate-600 p-4 pb-0">{T["h2_list"]}</h2>
                    <table class="w-full text-left border-collapse mt-2 min-w-[560px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="p-4 text-sm font-black text-slate-600 w-32">{T["th_date"]}</th>
                                <th class="p-4 text-sm font-black text-slate-600">{T["th_content"]}</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-20">{T["th_count"]}</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-28">{T["th_duration"]}</th>
                                <th class="p-4 text-sm font-black text-slate-600 w-16 text-center">{T["th_action"]}</th>
                            </tr>
                        </thead>
                        <tbody id="logList" class="divide-y divide-slate-200"></tbody>
                    </table>
                    <{P} id="logEmptyMsg" class="p-8 text-center text-slate-400 hidden">{T["empty"]}</{P}>
                </{P}>
            </{P}>
        </{P}>
    </main>

    '''

html = html.replace('<' + P, '<div').replace('</' + P + '>', '</motion>').replace('</motion>', '</div>')

existing = OUT.read_text(encoding='utf-8', errors='replace')
i = existing.find('<script src="/assets/js/core.js')
if i < 0:
    raise SystemExit('script not found')
OUT.write_text(html + existing[i:], encoding='utf-8', newline='\n')
assert T['h1'] in OUT.read_text(encoding='utf-8')
print('ok')
