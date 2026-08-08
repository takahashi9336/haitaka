<?php
/**
 * Health: ルーティン表 View
 * 一覧(ヒーロー) / 閲覧(タイムライン) / 編集 / 印刷(@media print)
 */
$appKey = 'health_routine';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ルーティン表 - Health</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700;900&family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Noto Sans JP', 'Inter', sans-serif; }
        .fz { font-family: 'Zen Kaku Gothic New', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }

        .hero-card { transition: all 0.25s; }
        .hero-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(0,0,0,.08); }

        .tl-item { transition: background 0.15s; }
        .tl-item:hover { background: #fafbfc; }

        .edit-item { transition: background 0.15s; }
        .edit-item:hover { background: #f7f8f9; }

        .drag-over { border-top: 2px solid #1FA85A !important; }
        .drag-ghost { opacity: 0.4; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 200; display: flex; align-items: center; justify-content: center; }
        .modal-box { background: #fff; border-radius: 16px; padding: 28px; width: 440px; max-width: 90vw; box-shadow: 0 20px 60px rgba(0,0,0,.15); }

        #printArea { position: absolute; left: -9999px; top: 0; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800" style="background:#E9F4EE">

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header id="appHeader" class="h-[60px] bg-white border-b border-[#eef0f2] flex items-center justify-between px-6 shrink-0 sticky top-0 z-10 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2 shrink-0"><i class="fa-solid fa-bars text-lg"></i></button>
                <a id="headerBack" href="#" class="text-slate-400 hover:text-slate-600 transition shrink-0 hidden"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div id="headerIcon" class="w-[30px] h-[30px] rounded-lg flex items-center justify-center text-white shadow-lg shrink-0" style="background:#1FA85A">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </div>
                <h1 id="headerTitle" class="fz font-bold text-[19px] text-slate-800 truncate">ルーティン表</h1>
                <span id="headerSub" class="text-xs text-[#98a2b3] shrink-0 hidden"></span>
            </div>
            <div id="headerActions" class="flex gap-2.5 shrink-0"></div>
        </header>

        <div class="flex-1 overflow-y-auto" id="mainScroll">
            <div id="screenList" class="p-5 md:p-7"></div>
            <div id="screenView" class="hidden"></div>
            <div id="screenEdit" class="hidden p-5 md:p-7"></div>
        </div>
    </main>

    <div id="printArea" style="display:none"></div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
    (function() {
    'use strict';

    /* ── constants ── */
    const THEME = {
        morning: {
            strong: '#E8912C', dark: '#C9761A', light: '#FBEFDD', line: '#F0D6AE', lineLight: '#F3DEC0',
            grad: 'linear-gradient(135deg,#F6B85A,#E8912C)',
            numColor: '#d3b98f',
            sunSvg(s) {
                return `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.4" fill="currentColor" stroke="none"/><line x1="12" y1="2.5" x2="12" y2="4.6"/><line x1="12" y1="19.4" x2="12" y2="21.5"/><line x1="2.5" y1="12" x2="4.6" y2="12"/><line x1="19.4" y1="12" x2="21.5" y2="12"/><line x1="5.2" y1="5.2" x2="6.7" y2="6.7"/><line x1="17.3" y1="17.3" x2="18.8" y2="18.8"/><line x1="18.8" y1="5.2" x2="17.3" y2="6.7"/><line x1="6.7" y1="17.3" x2="5.2" y2="18.8"/></svg>`;
            }
        },
        night: {
            strong: '#3F6EA8', dark: '#2F5687', light: '#E9EFF7', line: '#D8E4F2', lineLight: '#D8E4F2',
            grad: 'linear-gradient(135deg,#5C84B8,#3F6EA8)',
            numColor: '#8FB0D6',
            moonSvg(s) {
                return `<svg width="${s}" height="${s}" viewBox="0 0 24 24"><path d="M20 13.6A8 8 0 1 1 10.4 4 6.3 6.3 0 0 0 20 13.6Z" fill="currentColor"/><path d="M17.5 4.2l.5 1.4 1.4.5-1.4.5-.5 1.4-.5-1.4-1.4-.5 1.4-.5z" fill="currentColor"/></svg>`;
            }
        }
    };
    function themeIcon(theme, size) {
        return theme === 'night' ? THEME.night.moonSvg(size) : THEME.morning.sunSvg(size);
    }
    function T(theme) { return THEME[theme] || THEME.morning; }
    function esc(s) { return (s||'').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

    function countSteps(routine) {
        let n = 0;
        (routine.phases || []).forEach(p => { n += (p.items || []).length; });
        return n;
    }
    function phaseRange(routine) {
        const phases = routine.phases || [];
        if (phases.length === 0) return '';
        const first = phases[0].label || '';
        const last = phases[phases.length - 1].label || '';
        if (/^\d/.test(first) && /^\d/.test(last) && first !== last) return first + ' 〜 ' + last;
        return '';
    }
    function phaseSummary(routine) {
        const phases = routine.phases || [];
        const range = phaseRange(routine);
        const isTime = phases.length > 0 && /^\d/.test(phases[0].label || '');
        if (range) return routine.type + ' ・ ' + range;
        if (isTime) return routine.type + ' ・ 時刻区分 ' + phases.length;
        return routine.type;
    }

    /* ── state ── */
    let routines = [];
    let currentScreen = 'list';
    let currentId = null;
    let editData = null;

    /* ── DOM refs ── */
    const $list = document.getElementById('screenList');
    const $view = document.getElementById('screenView');
    const $edit = document.getElementById('screenEdit');
    const $print = document.getElementById('printArea');
    const $headerBack = document.getElementById('headerBack');
    const $headerIcon = document.getElementById('headerIcon');
    const $headerTitle = document.getElementById('headerTitle');
    const $headerSub = document.getElementById('headerSub');
    const $headerActions = document.getElementById('headerActions');
    const $mainScroll = document.getElementById('mainScroll');

    /* ── API ── */
    async function loadRoutines() {
        const res = await App.post('/health/api/routine_list.php', {});
        if (res && res.status === 'success') {
            routines = res.routines || [];
        } else {
            App.toast(res?.message || '読み込み失敗');
        }
    }

    /* ── navigation ── */
    function navigate(screen, id) {
        if (screen === 'list') location.hash = '';
        else location.hash = screen + '/' + id;
    }

    function applyHash() {
        const h = location.hash.replace('#', '');
        if (!h) { showList(); return; }
        const [s, idStr] = h.split('/');
        const id = parseInt(idStr, 10);
        if (s === 'view' && id) showView(id);
        else if (s === 'edit' && id) showEdit(id);
        else showList();
    }

    function show(el) { el.classList.remove('hidden'); el.style.display = ''; }
    function hide(el) { el.classList.add('hidden'); el.style.display = 'none'; }

    function setHeader(opts) {
        $headerBack.classList.toggle('hidden', !opts.back);
        if (opts.back) $headerBack.onclick = (e) => { e.preventDefault(); navigate(opts.back.screen, opts.back.id); };
        $headerIcon.style.background = opts.iconBg || '#1FA85A';
        $headerIcon.innerHTML = opts.iconSvg || '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
        $headerTitle.textContent = opts.title || 'ルーティン表';
        if (opts.sub) { $headerSub.textContent = opts.sub; $headerSub.classList.remove('hidden'); }
        else { $headerSub.classList.add('hidden'); }
        $headerActions.innerHTML = opts.actions || '';
    }

    /* ── LIST screen ── */
    function showList() {
        currentScreen = 'list'; currentId = null;
        hide($view); hide($edit); show($list);
        $mainScroll.scrollTop = 0;
        setHeader({
            title: 'ルーティン表',
            actions: `<button onclick="RT.doPrint()" class="text-[13px] text-[#475467] bg-white border border-[#e6e8eb] rounded-[9px] px-3.5 py-2 flex items-center gap-1.5 no-print"><i class="fa-solid fa-print text-xs"></i> 印刷</button>
                      <button onclick="RT.showCreateModal()" class="text-[13px] text-white font-bold rounded-[9px] px-4 py-2 flex items-center gap-1.5 no-print" style="background:#1FA85A"><i class="fa-solid fa-plus text-xs"></i> ルーティンを追加</button>`
        });
        renderList();
    }

    function renderList() {
        if (routines.length === 0) {
            $list.innerHTML = `<div class="max-w-4xl mx-auto"><div class="text-center py-20 text-slate-400"><i class="fa-solid fa-clock text-4xl mb-4 block opacity-40"></i><p class="text-lg font-bold mb-2">ルーティンがありません</p><p class="text-sm">「ルーティンを追加」から作成してください。</p></div></div>`;
            return;
        }
        let html = '<div class="max-w-4xl mx-auto"><div class="grid grid-cols-1 md:grid-cols-2 gap-5">';
        routines.forEach(r => {
            const t = T(r.theme);
            const steps = countSteps(r);
            const phases = r.phases || [];
            const isTime = phases.length > 0 && /^\d/.test(phases[0]?.label || '');

            let previewHtml = '';
            if (r.theme === 'night' && phases.length > 0) {
                previewHtml = `<div class="text-xs text-[#98a2b3] mb-2.5">フェーズ</div><div class="flex gap-2 flex-wrap">`;
                phases.forEach(p => { previewHtml += `<span class="text-[12.5px] font-medium rounded-[7px] px-3 py-1.5" style="color:${t.dark};background:${t.light}">${esc(p.label)}</span>`; });
                previewHtml += `</div>`;
            } else {
                previewHtml = `<div class="text-xs text-[#98a2b3] mb-2.5">最初のステップ</div><div class="flex flex-col gap-2 text-[13.5px] text-[#344054]">`;
                let shown = 0;
                for (const p of phases) {
                    for (const it of (p.items || [])) {
                        if (shown >= 3) break;
                        previewHtml += `<div class="flex gap-2.5"><span style="color:${t.strong}">●</span>${esc(it.content)}</div>`;
                        shown++;
                    }
                    if (shown >= 3) break;
                }
                previewHtml += `</div>`;
            }

            const chipLabel = isTime ? '時刻区分 ' + phases.length : 'フェーズ ' + phases.length;

            html += `
            <div class="hero-card bg-white border border-[#e6e8eb] rounded-[18px] overflow-hidden cursor-pointer" style="box-shadow:0 1px 2px rgba(16,24,40,.04)" onclick="RT.navigate('view',${r.id})">
                <div class="flex items-center gap-3.5 px-6 py-5" style="background:${t.grad};color:#fff">
                    <span style="color:#fff">${themeIcon(r.theme, 36)}</span>
                    <div class="min-w-0"><div class="fz text-[20px] font-bold truncate">${esc(r.name)}</div><div class="text-[12.5px] opacity-[.92]">${esc(phaseSummary(r))}</div></div>
                    <div class="ml-auto text-right shrink-0"><div class="fz text-[28px] font-black leading-none">${steps}</div><div class="text-[11px] opacity-[.92]">ステップ</div></div>
                </div>
                <div class="px-6 py-4 pb-5">
                    ${previewHtml}
                    <div class="mt-4 flex items-center gap-2.5">
                        <span class="text-[13px] font-bold text-white rounded-[9px] px-4 py-2" style="background:${t.strong}">開く →</span>
                        <span class="text-[11px] font-bold rounded-[6px] px-2.5 py-1" style="color:${t.dark};background:${t.light}">${esc(chipLabel)}</span>
                    </div>
                </div>
            </div>`;
        });
        html += '</div>';
        html += `<div onclick="RT.showCreateModal()" class="mt-5 border-[1.5px] border-dashed rounded-[16px] px-5 py-5 text-center text-[13.5px] font-medium cursor-pointer hover:opacity-80 transition" style="border-color:#cfe3d6;color:#5b9a74;background:rgba(255,255,255,.4)">＋ 新しいルーティンを追加（休日・出張など）</div>`;
        html += '</div>';
        $list.innerHTML = html;
    }

    /* ── VIEW screen (timeline) ── */
    function showView(id) {
        const r = routines.find(x => x.id == id);
        if (!r) { navigate('list'); return; }
        currentScreen = 'view'; currentId = id;
        hide($list); hide($edit); show($view);
        $mainScroll.scrollTop = 0;
        const t = T(r.theme);
        setHeader({
            back: { screen: 'list' },
            iconBg: t.light,
            iconSvg: `<span style="color:${t.strong}">${themeIcon(r.theme, 24)}</span>`,
            title: r.name,
            sub: r.type + ' ・ 全' + countSteps(r) + 'ステップ',
            actions: `<button onclick="RT.doPrint(${r.id})" class="text-xs text-[#475467] bg-white border border-[#e6e8eb] rounded-lg px-3 py-[7px] no-print"><i class="fa-solid fa-print mr-1"></i>印刷</button>
                      <button onclick="RT.navigate('edit',${r.id})" class="text-xs text-[#475467] bg-white border border-[#e6e8eb] rounded-lg px-3 py-[7px] no-print"><i class="fa-solid fa-pen mr-1"></i>編集</button>`
        });
        renderView(r);
    }

    function renderView(r) {
        const t = T(r.theme);
        const phases = r.phases || [];
        let stepNo = 0;
        let html = '<div class="max-w-2xl mx-auto px-5 md:px-7 py-6">';
        phases.forEach((phase, pi) => {
            const items = phase.items || [];
            const isEmpty = items.length === 0;
            html += `<div class="flex gap-4 mb-2">
                <div class="flex flex-col items-center" style="flex:none;width:60px">
                    <span class="fz text-[15px] font-black text-white rounded-lg py-1.5 w-[58px] text-center" style="background:${t.strong};box-shadow:0 2px 5px ${t.strong}4d">${esc(phase.label)}</span>
                    ${pi < phases.length - 1 ? `<span class="flex-1 mt-1.5" style="width:2px;background:${t.lineLight}"></span>` : ''}
                </div>
                <div class="flex-1 pb-4 min-w-0">`;
            if (isEmpty) {
                html += `<div class="text-sm text-[#b6bcc4] py-2 italic">自由時間（予定なし）</div>`;
            } else {
                items.forEach(it => {
                    stepNo++;
                    const no = String(stepNo).padStart(2, '0');
                    html += `<div class="tl-item flex gap-3 items-baseline py-2 border-b border-[#f4f5f6]">
                        <span class="flex-none w-5 font-mono text-[11px] text-right" style="color:${t.numColor}">${no}</span>
                        <span class="text-[14px] leading-relaxed text-[#344054]">${esc(it.content)}</span>
                    </div>`;
                });
            }
            html += '</div></div>';
        });
        html += '</div>';
        $view.innerHTML = html;
    }

    /* ── EDIT screen ── */
    function showEdit(id) {
        const r = routines.find(x => x.id == id);
        if (!r) { navigate('list'); return; }
        currentScreen = 'edit'; currentId = id;
        editData = JSON.parse(JSON.stringify(r));
        hide($list); hide($view); show($edit);
        $mainScroll.scrollTop = 0;
        setHeader({
            back: { screen: 'view', id },
            title: 'ルーティンを編集',
            actions: `<button onclick="RT.cancelEdit()" class="text-[13px] text-[#475467] bg-white border border-[#e6e8eb] rounded-[9px] px-4 py-2 no-print">キャンセル</button>
                      <button onclick="RT.saveEdit()" class="text-[13px] text-white font-bold rounded-[9px] px-4 py-2 no-print" style="background:#1FA85A">保存</button>
                      <button onclick="RT.confirmDelete()" class="text-[13px] text-red-500 bg-white border border-red-200 rounded-[9px] px-3 py-2 no-print"><i class="fa-solid fa-trash-can"></i></button>`
        });
        renderEdit();
    }

    function renderEdit() {
        const r = editData;
        const t = T(r.theme);
        const phases = r.phases || [];

        let html = '<div class="max-w-3xl mx-auto">';
        html += `<div class="bg-white border border-[#e6e8eb] rounded-[16px] overflow-hidden">`;
        html += `<div class="p-5 border-b border-[#f0f1f3]">
            <div class="flex gap-4 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-[#667085] mb-1.5 font-medium">ルーティン名</label>
                    <input type="text" id="editName" value="${esc(r.name)}" class="w-full border border-[#d7dbe0] rounded-[9px] px-3 py-2.5 text-[14px] focus:outline-none focus:border-slate-400">
                </div>
                <div class="w-[170px]">
                    <label class="block text-xs text-[#667085] mb-1.5 font-medium">種別</label>
                    <div class="flex gap-0.5 bg-[#f2f4f6] rounded-[9px] p-[3px]">
                        <span onclick="RT.setEditType('平日')" id="typeWeekday" class="flex-1 text-center text-[13px] font-bold rounded-[7px] py-[7px] cursor-pointer transition ${r.type==='平日'?'bg-white shadow-sm text-[#2F5687]':'text-[#98a2b3]'}">平日</span>
                        <span onclick="RT.setEditType('休日')" id="typeHoliday" class="flex-1 text-center text-[13px] font-bold rounded-[7px] py-[7px] cursor-pointer transition ${r.type==='休日'?'bg-white shadow-sm text-[#2F5687]':'text-[#98a2b3]'}">休日</span>
                    </div>
                </div>
                <div class="w-[170px]">
                    <label class="block text-xs text-[#667085] mb-1.5 font-medium">テーマ</label>
                    <div class="flex gap-0.5 bg-[#f2f4f6] rounded-[9px] p-[3px]">
                        <span onclick="RT.setEditTheme('morning')" id="themeMorning" class="flex-1 text-center text-[13px] font-bold rounded-[7px] py-[7px] cursor-pointer transition ${r.theme==='morning'?'bg-white shadow-sm text-[#C9761A]':'text-[#98a2b3]'}">朝</span>
                        <span onclick="RT.setEditTheme('night')" id="themeNight" class="flex-1 text-center text-[13px] font-bold rounded-[7px] py-[7px] cursor-pointer transition ${r.theme==='night'?'bg-white shadow-sm text-[#2F5687]':'text-[#98a2b3]'}">夜</span>
                    </div>
                </div>
            </div>
        </div>`;

        html += `<div class="p-5"><div class="text-xs font-bold text-[#344054] mb-3 tracking-wide">フェーズ</div>`;

        phases.forEach((phase, pi) => {
            const items = phase.items || [];
            html += `<div class="border border-[#e6e8eb] rounded-xl mb-3.5 overflow-hidden" data-phase-idx="${pi}">
                <div class="flex items-center gap-2.5 px-3.5 py-2.5 border-b border-[#e6e8eb]" style="background:${t.light}">
                    <span class="text-[15px] cursor-grab select-none drag-handle-phase" style="color:${t.numColor}" draggable="true" data-phase-idx="${pi}">⠿</span>
                    <input type="text" value="${esc(phase.label)}" onchange="RT.updatePhaseLabel(${pi},this.value)" class="fz text-[14px] font-bold bg-transparent border-none focus:outline-none flex-1 min-w-0" style="color:${t.dark}">
                    <span class="text-[11px]" style="color:${t.numColor}">フェーズ名</span>
                    <button onclick="RT.removePhase(${pi})" class="ml-auto text-[13px] text-[#c4cad2] hover:text-red-400 transition"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                <div class="px-3 py-2" id="phaseItems_${pi}">`;

            items.forEach((it, ii) => {
                html += `<div class="edit-item flex items-center gap-2.5 py-[7px] px-2 rounded-lg" data-item-idx="${ii}">
                    <span class="text-[14px] cursor-grab select-none drag-handle-item" style="color:#cdd2d8" draggable="true" data-phase-idx="${pi}" data-item-idx="${ii}">⠿</span>
                    <input type="text" value="${esc(it.content)}" onchange="RT.updateItemContent(${pi},${ii},this.value)" data-rt-item data-pi="${pi}" data-ii="${ii}" class="flex-1 text-[13.5px] text-[#344054] border border-[#edeff1] rounded-[7px] px-2.5 py-[7px] bg-white focus:outline-none focus:border-slate-300 min-w-0">
                    <button onclick="RT.removeItem(${pi},${ii})" class="text-[13px] text-[#d0d5db] hover:text-red-400 transition shrink-0"><i class="fa-solid fa-xmark"></i></button>
                </div>`;
            });

            html += `<div class="px-2 py-2 pt-1"><button onclick="RT.addItem(${pi})" class="text-[13px] font-medium rounded-lg px-3.5 py-[7px] border border-dashed transition" style="color:${t.dark};border-color:${t.line}"><i class="fa-solid fa-plus text-[10px] mr-1"></i> アイテムを追加</button></div>`;
            html += `</div></div>`;
        });

        html += `<button onclick="RT.addPhase()" class="w-full text-[13px] text-[#475467] font-medium border border-dashed border-[#cdd2d8] rounded-[9px] px-4 py-2.5 text-center mt-1 hover:bg-slate-50 transition"><i class="fa-solid fa-plus text-[10px] mr-1"></i> フェーズを追加</button>`;
        html += `</div></div></div>`;
        $edit.innerHTML = html;

        initEditDrag();
        initItemEnterKey();
    }

    /* ── edit helpers ── */
    window.RT = window.RT || {};
    RT.setEditType = function(type) {
        editData.type = type;
        renderEdit();
    };
    RT.setEditTheme = function(theme) {
        editData.theme = theme;
        renderEdit();
    };
    RT.updatePhaseLabel = function(pi, val) {
        editData.phases[pi].label = val;
    };
    RT.updateItemContent = function(pi, ii, val) {
        editData.phases[pi].items[ii].content = val;
    };
    RT.addPhase = function() {
        editData.phases.push({ label: '', items: [] });
        renderEdit();
        const inputs = $edit.querySelectorAll('[data-phase-idx="' + (editData.phases.length - 1) + '"] input[type="text"]');
        if (inputs[0]) inputs[0].focus();
    };
    RT.removePhase = function(pi) {
        if (!confirm('このフェーズを削除しますか？')) return;
        editData.phases.splice(pi, 1);
        renderEdit();
    };
    RT.addItem = function(pi) {
        editData.phases[pi].items.push({ content: '' });
        renderEdit();
        const container = document.getElementById('phaseItems_' + pi);
        if (container) {
            const inputs = container.querySelectorAll('input[type="text"]');
            if (inputs.length) inputs[inputs.length - 1].focus();
        }
    };
    RT.removeItem = function(pi, ii) {
        editData.phases[pi].items.splice(ii, 1);
        renderEdit();
    };
    RT.cancelEdit = function() {
        editData = null;
        navigate('view', currentId);
    };
    RT.saveEdit = async function() {
        const nameInput = document.getElementById('editName');
        const name = nameInput ? nameInput.value.trim() : editData.name;
        if (!name) { App.toast('ルーティン名を入力してください'); return; }

        const phases = (editData.phases || []).map(p => ({
            label: p.label,
            items: (p.items || []).filter(it => (it.content || '').trim() !== '').map(it => ({ content: it.content.trim() }))
        })).filter(p => p.label.trim() !== '');

        const res = await App.post('/health/api/routine_save.php', {
            routine_id: editData.id,
            name: name,
            type: editData.type,
            theme: editData.theme,
            phases: phases
        });
        if (res && res.status === 'success') {
            App.toast('保存しました');
            await loadRoutines();
            editData = null;
            navigate('view', currentId);
        } else {
            App.toast(res?.message || '保存に失敗しました');
        }
    };
    RT.confirmDelete = function() {
        if (!confirm('このルーティンを削除しますか？この操作は元に戻せません。')) return;
        doDelete(currentId);
    };

    async function doDelete(id) {
        const res = await App.post('/health/api/routine_delete.php', { id });
        if (res && res.status === 'success') {
            App.toast('削除しました');
            await loadRoutines();
            navigate('list');
        } else {
            App.toast(res?.message || '削除に失敗しました');
        }
    }

    /* ── Enter key navigation (edit) ── */
    function initItemEnterKey() {
        $edit.querySelectorAll('input[data-rt-item]').forEach(el => {
            el.addEventListener('keydown', e => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const pi = parseInt(el.dataset.pi, 10);
                const ii = parseInt(el.dataset.ii, 10);
                el.dispatchEvent(new Event('change'));

                const items = editData.phases[pi].items;
                if (ii < items.length - 1) {
                    const next = $edit.querySelector(`input[data-rt-item][data-pi="${pi}"][data-ii="${ii + 1}"]`);
                    if (next) { next.focus(); next.select(); }
                } else {
                    editData.phases[pi].items.push({ content: '' });
                    renderEdit();
                    const added = $edit.querySelector(`input[data-rt-item][data-pi="${pi}"][data-ii="${items.length - 1}"]`);
                    if (added) added.focus();
                }
            });
        });
    }

    /* ── drag & drop (edit) ── */
    function initEditDrag() {
        let dragType = null;
        let dragFrom = null;

        $edit.querySelectorAll('.drag-handle-phase').forEach(handle => {
            handle.addEventListener('dragstart', e => {
                dragType = 'phase';
                dragFrom = parseInt(handle.dataset.phaseIdx, 10);
                handle.closest('[data-phase-idx]').classList.add('drag-ghost');
                e.dataTransfer.effectAllowed = 'move';
            });
        });
        $edit.querySelectorAll('[data-phase-idx]').forEach(el => {
            if (el.classList.contains('drag-handle-phase')) return;
            el.addEventListener('dragover', e => {
                if (dragType !== 'phase') return;
                e.preventDefault();
                el.classList.add('drag-over');
            });
            el.addEventListener('dragleave', () => el.classList.remove('drag-over'));
            el.addEventListener('drop', e => {
                e.preventDefault();
                el.classList.remove('drag-over');
                if (dragType !== 'phase') return;
                const to = parseInt(el.dataset.phaseIdx, 10);
                if (isNaN(to) || dragFrom === to) return;
                const moved = editData.phases.splice(dragFrom, 1)[0];
                editData.phases.splice(to, 0, moved);
                renderEdit();
            });
        });

        $edit.querySelectorAll('.drag-handle-item').forEach(handle => {
            handle.addEventListener('dragstart', e => {
                dragType = 'item';
                dragFrom = { pi: parseInt(handle.dataset.phaseIdx, 10), ii: parseInt(handle.dataset.itemIdx, 10) };
                handle.closest('.edit-item').classList.add('drag-ghost');
                e.dataTransfer.effectAllowed = 'move';
            });
        });
        $edit.querySelectorAll('.edit-item').forEach(el => {
            el.addEventListener('dragover', e => {
                if (dragType !== 'item') return;
                e.preventDefault();
                el.classList.add('drag-over');
            });
            el.addEventListener('dragleave', () => el.classList.remove('drag-over'));
            el.addEventListener('drop', e => {
                e.preventDefault();
                el.classList.remove('drag-over');
                if (dragType !== 'item' || !dragFrom) return;
                const toPi = parseInt(el.closest('[data-phase-idx]')?.dataset?.phaseIdx ?? el.dataset.phaseIdx, 10);
                const toIi = parseInt(el.dataset.itemIdx, 10);
                if (isNaN(toPi) || isNaN(toIi)) return;
                const item = editData.phases[dragFrom.pi].items.splice(dragFrom.ii, 1)[0];
                editData.phases[toPi].items.splice(toIi, 0, item);
                renderEdit();
            });
        });

        document.addEventListener('dragend', () => {
            dragType = null; dragFrom = null;
            $edit.querySelectorAll('.drag-ghost,.drag-over').forEach(el => el.classList.remove('drag-ghost', 'drag-over'));
        }, { once: false });
    }

    /* ── create modal ── */
    RT.showCreateModal = function() {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };
        overlay.innerHTML = `
            <div class="modal-box" onclick="event.stopPropagation()">
                <h3 class="fz text-lg font-bold text-slate-800 mb-5">ルーティンを追加</h3>
                <div class="mb-4">
                    <label class="block text-xs text-[#667085] mb-1.5 font-medium">ルーティン名</label>
                    <input type="text" id="createName" placeholder="例: モーニングルーティン" class="w-full border border-[#d7dbe0] rounded-[9px] px-3 py-2.5 text-[14px] focus:outline-none focus:border-slate-400">
                </div>
                <div class="flex gap-4 mb-5">
                    <div class="flex-1">
                        <label class="block text-xs text-[#667085] mb-1.5 font-medium">種別</label>
                        <select id="createType" class="w-full border border-[#d7dbe0] rounded-[9px] px-3 py-2.5 text-[14px] focus:outline-none">
                            <option value="平日">平日</option><option value="休日">休日</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-[#667085] mb-1.5 font-medium">テーマ</label>
                        <select id="createTheme" class="w-full border border-[#d7dbe0] rounded-[9px] px-3 py-2.5 text-[14px] focus:outline-none">
                            <option value="morning">朝（オレンジ）</option><option value="night">夜（ブルー）</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button onclick="this.closest('.modal-overlay').remove()" class="text-[13px] text-[#475467] bg-white border border-[#e6e8eb] rounded-[9px] px-4 py-2">キャンセル</button>
                    <button onclick="RT.doCreate()" class="text-[13px] text-white font-bold rounded-[9px] px-5 py-2" style="background:#1FA85A">作成</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        setTimeout(() => document.getElementById('createName')?.focus(), 50);
    };

    RT.doCreate = async function() {
        const name = document.getElementById('createName')?.value?.trim();
        const type = document.getElementById('createType')?.value || '平日';
        const theme = document.getElementById('createTheme')?.value || 'morning';
        if (!name) { App.toast('ルーティン名を入力してください'); return; }
        const res = await App.post('/health/api/routine_create.php', { name, type, theme });
        if (res && res.status === 'success') {
            document.querySelector('.modal-overlay')?.remove();
            App.toast('作成しました');
            await loadRoutines();
            navigate('edit', res.id);
        } else {
            App.toast(res?.message || '作成に失敗しました');
        }
    };

    /* ── print (html2canvas + jsPDF) ── */
    const A4_W_MM = 210, A4_H_MM = 297;
    const MARGIN_MM = 12;
    const CONTENT_W_MM = A4_W_MM - MARGIN_MM * 2;
    const CONTENT_H_MM = A4_H_MM - MARGIN_MM * 2;

    function buildPageHtml(r) {
        const t = T(r.theme);
        const steps = countSteps(r);
        const now = new Date();
        const ym = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0');
        let stepNo = 0;

        let h = `<div style="width:680px;padding:0;box-sizing:border-box;font-family:'Noto Sans JP',sans-serif;background:#fff;color:#1F2A37">`;
        h += `<div style="display:flex;align-items:center;gap:12px;border-bottom:3px solid ${t.strong};padding-bottom:14px;margin-bottom:8px">
            <span style="color:${t.strong}">${themeIcon(r.theme, 28)}</span>
            <span class="fz" style="font-size:24px;font-weight:900;color:#1F2A37">${esc(r.name)}</span>
            <span style="margin-left:auto;font-size:14px;color:#98a2b3">${esc(r.type)} ・ 全${steps}ステップ</span>
        </div>`;

        h += `<div style="margin-top:12px">`;
        (r.phases || []).forEach(phase => {
            h += `<div style="margin-bottom:4px;margin-top:6px">
                <div class="fz" style="font-size:16px;font-weight:900;border-bottom:2px solid ${t.line};padding-bottom:3px;margin-bottom:4px;color:${t.dark}">${esc(phase.label)}</div>
            </div>`;
            (phase.items || []).forEach(it => {
                stepNo++;
                h += `<div style="display:flex;gap:10px;align-items:baseline;padding:3px 0">
                    <span class="fz" style="flex:none;width:22px;font-size:13px;font-weight:700;text-align:right;color:${t.dark}">${String(stepNo).padStart(2,'0')}</span>
                    <span style="font-size:14.5px;line-height:1.5;color:#2b3441">${esc(it.content)}</span>
                </div>`;
            });
            if ((phase.items || []).length === 0) {
                h += `<div style="font-size:14px;color:#b6bcc4;padding:3px 0;font-style:italic">自由時間（予定なし）</div>`;
            }
        });
        h += `</div>`;

        h += `<div style="margin-top:28px;padding-top:10px;border-top:1px solid #ddd;font-size:11px;color:#b6bcc4;display:flex;justify-content:space-between">
            <span>MyPlatform / ルーティン表</span><span>更新：${ym}</span>
        </div>`;
        h += `</div>`;
        return h;
    }

    RT.doPrint = async function(id) {
        const targets = id ? routines.filter(r => r.id == id) : routines;
        if (targets.length === 0) { App.toast('印刷するルーティンがありません'); return; }

        App.toast('PDF生成中…', 8000);

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        for (let i = 0; i < targets.length; i++) {
            if (i > 0) pdf.addPage();

            $print.style.cssText = 'position:absolute;left:-9999px;top:0;display:block;';
            $print.innerHTML = buildPageHtml(targets[i]);

            await document.fonts.ready;
            await new Promise(r => setTimeout(r, 150));

            const el = $print.firstElementChild;
            const canvas = await html2canvas(el, {
                scale: 3,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false,
            });

            $print.style.display = 'none';

            const imgData = canvas.toDataURL('image/jpeg', 0.92);
            const imgW = CONTENT_W_MM;
            const imgH = (canvas.height / canvas.width) * imgW;
            pdf.addImage(imgData, 'JPEG', MARGIN_MM, MARGIN_MM, imgW, Math.min(imgH, CONTENT_H_MM));
        }

        const fileName = targets.length === 1
            ? targets[0].name.replace(/\s+/g, '_') + '.pdf'
            : 'ルーティン表.pdf';
        pdf.save(fileName);
        App.toast('PDFをダウンロードしました');
    };

    /* ── expose to global ── */
    RT.navigate = navigate;
    RT.loadRoutines = loadRoutines;

    /* ── init ── */
    document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    window.addEventListener('hashchange', applyHash);
    window.addEventListener('DOMContentLoaded', async () => {
        await loadRoutines();
        applyHash();
    });

    })();
    </script>
</body>
</html>
