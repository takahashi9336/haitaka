<?php
/**
 * メディア一括登録 View
 * 物理パス: haitaka/private/apps/Hinata/Views/media_import.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>動画一括登録 - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-New { background: #dbeafe; color: #1e40af; }
        .status-Linked { background: #dcfce7; color: #15803d; }
        .status-Registered { background: #f1f5f9; color: #64748b; }
        .status-Error { background: #fee2e2; color: #991b1b; }

        .preview-table { overflow-x: auto; }
        .preview-table table { min-width: 800px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">動画一括登録</h1>
            </div>
            <a href="/hinata/index.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                ポータルへ戻る
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-6xl mx-auto">
                
                <!-- 入力セクション -->
                <section class="bg-white rounded-3xl border <?= $cardBorder ?> shadow-sm p-6 md:p-8 mb-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-black text-slate-800 mb-2">データ入力</h2>
                        <p class="text-xs text-slate-500 mb-2">YouTube動画を一括でインポートします。タブ区切り、またはカンマ区切りで入力してください。</p>
                        
                        <div class="<?= $cardIconBg ?> border <?= $cardBorder ?> rounded-lg p-3 mb-3"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                            <p class="text-xs font-bold mb-2 <?= $cardIconText ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>📝 入力形式</p>
                            <code class="text-xs bg-white px-2 py-1 rounded block font-mono <?= $cardIconText ?>"<?= $cardDecoStyle ? ' style="' . htmlspecialchars($cardDecoStyle) . '"' : '' ?>>
                                URL [TAB] タイトル [TAB] シングル番号 [TAB] 公開日 [TAB] カテゴリ
                            </code>
                            <div class="mt-2 space-y-1 text-xs text-slate-600">
                                <div><strong>URL</strong>: YouTube動画のURL（必須）</div>
                                <div><strong>タイトル</strong>: 楽曲タイトル（必須）</div>
                                <div><strong>シングル番号</strong>: 1, 2, 3... または 1st, 2nd, 3rd...（任意、MVの場合に使用）</div>
                                <div><strong>公開日</strong>: YYYY-MM-DD形式（任意）</div>
                                <div><strong>カテゴリ</strong>: MV, Live, Variety等（任意、未指定時はデフォルトカテゴリを使用）</div>
                            </div>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-xs font-bold text-green-700 mb-2">✨ 新機能：楽曲情報の自動登録</p>
                            <p class="text-xs text-slate-600">
                                カテゴリが <strong class="text-green-700">MV</strong> の動画は、プレビュー画面でリリース（シングル・アルバム）情報を選択することで、
                                楽曲情報（収録曲）も同時に登録できます。
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">デフォルトカテゴリ</label>
                        <select id="defaultCategory" class="w-full md:w-64 h-11 border <?= $cardBorder ?> rounded-xl px-4 text-sm outline-none bg-slate-50 <?= $isThemeHex ? 'focus:border-[var(--hinata-theme)]' : 'focus:border-' . $themeTailwind . '-300' ?> transition">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $key === 'MV' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">※ CSV入力でカテゴリ未指定時に使用されます</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">一括入力データ</label>
                        <textarea 
                            id="bulkTextarea" 
                            rows="10" 
                            placeholder="例:&#10;https://www.youtube.com/watch?v=xxxxx&#9;キュン&#9;1&#9;2019-03-27&#9;MV&#10;https://www.youtube.com/watch?v=yyyyy&#9;ドレミソラシド&#9;2&#9;2019-07-17&#9;MV&#10;https://www.youtube.com/watch?v=zzzzz&#9;こんなに好きになっちゃっていいの？&#9;3&#9;2019-10-02&#9;MV"
                            class="w-full border <?= $cardBorder ?> rounded-xl px-4 py-3 text-sm outline-none bg-slate-50 <?= $isThemeHex ? 'focus:border-[var(--hinata-theme)]' : 'focus:border-' . $themeTailwind . '-300' ?> transition font-mono"
                        ></textarea>
                        <p class="text-xs text-slate-400 mt-1">
                            <i class="fa-solid fa-info-circle"></i> 
                            Excel等から直接コピー＆ペーストできます。1行につき1動画を入力してください。
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            id="btnPreview" 
                            class="h-11 px-6 <?= $btnBgClass ?> text-white font-bold text-sm rounded-full transition shadow-lg flex items-center gap-2"<?= $btnBgStyle ? ' style="' . htmlspecialchars($btnBgStyle) . '"' : '' ?>
                        >
                            <i class="fa-solid fa-magnifying-glass"></i>
                            プレビュー
                        </button>
                        <button 
                            id="btnClear" 
                            class="h-11 px-6 bg-slate-100 text-slate-600 font-bold text-sm rounded-full hover:bg-slate-200 transition"
                        >
                            クリア
                        </button>
                    </div>
                </section>

                <!-- プレビューセクション -->
                <section id="previewSection" class="bg-white rounded-3xl border <?= $cardBorder ?> shadow-sm p-6 md:p-8 hidden">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h2 class="text-lg font-black text-slate-800 mb-1">プレビュー結果</h2>
                                <p class="text-xs text-slate-500">登録内容を確認し、必要に応じて楽曲情報を入力してください。</p>
                            </div>
                            <button 
                                id="btnExecute" 
                                class="h-11 px-6 bg-green-500 text-white font-bold text-sm rounded-full hover:bg-green-600 transition shadow-lg shadow-green-200 flex items-center gap-2"
                            >
                                <i class="fa-solid fa-check"></i>
                                一括登録実行
                            </button>
                        </div>
                        <div class="flex gap-4 text-xs mb-2">
                            <span class="text-blue-600 font-bold">🆕 新規: <span id="countNew">0</span>件</span>
                            <span class="text-green-600 font-bold">🔗 リンク: <span id="countLinked">0</span>件</span>
                            <span class="text-slate-400 font-bold">✓ 登録済: <span id="countRegistered">0</span>件</span>
                            <span class="text-red-600 font-bold">⚠ エラー: <span id="countError">0</span>件</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded p-2">
                            <p class="text-xs text-slate-500">
                                <strong class="text-blue-600">新規</strong>: 完全新規登録 / 
                                <strong class="text-green-600">リンク</strong>: 動画素材は登録済み / 
                                <strong class="text-slate-500">登録済</strong>: スキップ / 
                                <strong class="text-red-600">エラー</strong>: URL不正
                            </p>
                        </div>
                    </div>

                    <div class="preview-table">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">サムネイル</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">ステータス</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">タイトル</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">カテゴリ</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">公開日</th>
                                    <th class="text-left text-xs font-bold text-slate-600 p-3">操作</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- JavaScript で動的生成 -->
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <script>
        const btnPreview = document.getElementById('btnPreview');
        const btnClear = document.getElementById('btnClear');
        const btnExecute = document.getElementById('btnExecute');
        const bulkTextarea = document.getElementById('bulkTextarea');
        const defaultCategory = document.getElementById('defaultCategory');
        const previewSection = document.getElementById('previewSection');
        const previewTableBody = document.getElementById('previewTableBody');

        let previewData = [];
        let releases = [];
        let trackTypes = {};

        // プレビューボタン
        btnPreview.addEventListener('click', async () => {
            const rawInput = bulkTextarea.value.trim();
            if (!rawInput) {
                alert('データを入力してください');
                return;
            }

            btnPreview.disabled = true;
            btnPreview.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 解析中...';

            try {
                const response = await fetch('/hinata/api/preview_media.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        raw_input: rawInput,
                        default_category: defaultCategory.value,
                    }),
                });

                const result = await response.json();
                if (result.status === 'success') {
                    previewData = result.data;
                    releases = result.releases || [];
                    trackTypes = result.track_types || {};
                    renderPreview(previewData);
                } else {
                    alert('エラー: ' + (result.message || '不明なエラー'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                btnPreview.disabled = false;
                btnPreview.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> プレビュー';
            }
        });

        // クリアボタン
        btnClear.addEventListener('click', () => {
            bulkTextarea.value = '';
            previewSection.classList.add('hidden');
            previewData = [];
            releases = [];
            trackTypes = {};
        });

        // プレビュー描画
        function renderPreview(data) {
            if (data.length === 0) {
                previewSection.classList.add('hidden');
                return;
            }

            previewSection.classList.remove('hidden');

            // カウント集計
            const counts = { New: 0, Linked: 0, Registered: 0, Error: 0 };
            data.forEach(item => counts[item.status]++);
            document.getElementById('countNew').textContent = counts.New;
            document.getElementById('countLinked').textContent = counts.Linked;
            document.getElementById('countRegistered').textContent = counts.Registered;
            document.getElementById('countError').textContent = counts.Error;

            // トラック種別プルダウンのオプション生成
            const trackTypeOptions = Object.entries(trackTypes).map(([key, label]) => 
                `<option value="${key}">${label}</option>`
            ).join('');

            // テーブル生成
            previewTableBody.innerHTML = data.map((item, index) => {
                const isMV = item.category === 'MV';
                
                // リリースプルダウン（item.release_idが指定されている場合は事前選択）
                const releaseOptionsHtml = releases.map(r => 
                    `<option value="${r.id}" ${r.id == item.release_id ? 'selected' : ''}>${r.release_number || ''} ${r.title}</option>`
                ).join('');
                
                return `
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition" data-index="${index}">
                    <td class="p-3" rowspan="${isMV ? 2 : 1}">
                        ${item.thumbnail 
                            ? `<img src="${item.thumbnail}" alt="サムネイル" class="w-20 h-12 rounded object-cover border border-slate-200">`
                            : '<div class="w-20 h-12 bg-slate-200 rounded flex items-center justify-center text-slate-400 text-xs">N/A</div>'
                        }
                    </td>
                    <td class="p-3" rowspan="${isMV ? 2 : 1}">
                        <span class="status-badge status-${item.status}">
                            ${item.status === 'New' ? '<i class="fa-solid fa-plus"></i>' : ''}
                            ${item.status === 'Linked' ? '<i class="fa-solid fa-link"></i>' : ''}
                            ${item.status === 'Registered' ? '<i class="fa-solid fa-check"></i>' : ''}
                            ${item.status === 'Error' ? '<i class="fa-solid fa-exclamation"></i>' : ''}
                            ${item.message || item.status}
                        </span>
                    </td>
                    <td class="p-3 text-sm text-slate-800 font-medium">
                        ${item.title || '<span class="text-slate-400">未設定</span>'}
                        ${item.single_number ? `<span class="ml-2 text-xs text-sky-600 font-bold">[${item.single_number}]</span>` : ''}
                    </td>
                    <td class="p-3 text-sm text-slate-600">${item.category}</td>
                    <td class="p-3 text-sm text-slate-600">${item.release_date || '-'}</td>
                    <td class="p-3" rowspan="${isMV ? 2 : 1}">
                        <button class="btn-remove text-red-500 hover:text-red-700 text-xs font-bold" data-index="${index}">
                            <i class="fa-solid fa-trash"></i> 除外
                        </button>
                    </td>
                </tr>
                ${isMV ? `
                <tr class="bg-sky-50/30 border-b border-slate-100" data-index="${index}">
                    <td colspan="3" class="p-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="font-bold text-sky-600">🎵 楽曲情報:</span>
                            <select class="song-release border border-sky-200 rounded px-2 py-1 text-xs" data-index="${index}">
                                <option value="">リリース選択（任意）</option>
                                ${releaseOptionsHtml}
                            </select>
                            ${item.release_id ? '<span class="text-green-600 text-xs">✓ CSV指定</span>' : ''}
                            <input type="text" class="song-title border border-sky-200 rounded px-2 py-1 text-xs w-40" 
                                   placeholder="楽曲タイトル" value="${item.title || ''}" data-index="${index}">
                            <select class="song-track-type border border-sky-200 rounded px-2 py-1 text-xs" data-index="${index}">
                                ${trackTypeOptions}
                            </select>
                            <input type="number" class="song-track-number border border-sky-200 rounded px-2 py-1 text-xs w-16" 
                                   placeholder="No" min="1" data-index="${index}">
                        </div>
                    </td>
                </tr>
                ` : ''}
            `;
            }).join('');

            // 除外ボタン
            document.querySelectorAll('.btn-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    previewData.splice(index, 1);
                    renderPreview(previewData);
                });
            });
        }

        // 一括登録実行
        btnExecute.addEventListener('click', async () => {
            if (previewData.length === 0) {
                alert('登録するデータがありません');
                return;
            }

            if (!confirm('一括登録を実行しますか？')) {
                return;
            }

            // 楽曲情報を各アイテムに追加
            const itemsWithSongInfo = previewData.map((item, index) => {
                const newItem = { ...item };
                
                // カテゴリが MV の場合のみ楽曲情報を追加
                if (item.category === 'MV') {
                    const releaseSelect = document.querySelector(`.song-release[data-index="${index}"]`);
                    const titleInput = document.querySelector(`.song-title[data-index="${index}"]`);
                    const trackTypeSelect = document.querySelector(`.song-track-type[data-index="${index}"]`);
                    const trackNumberInput = document.querySelector(`.song-track-number[data-index="${index}"]`);

                    // UI選択またはCSV指定のrelease_idを使用
                    const releaseId = releaseSelect?.value || item.release_id;
                    
                    if (releaseId) {
                        // release_idを直接設定（CSV指定とUI選択を統合）
                        newItem.release_id = releaseId;
                        
                        // song_infoも設定（追加情報用）
                        newItem.song_info = {
                            release_id: releaseId,
                            title: titleInput?.value || item.title,
                            track_type: trackTypeSelect?.value || 'title',  // MVはデフォルトで表題曲
                            track_number: trackNumberInput?.value ? parseInt(trackNumberInput.value) : null,
                        };
                    }
                }
                
                return newItem;
            });

            btnExecute.disabled = true;
            btnExecute.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 登録中...';

            try {
                const response = await fetch('/hinata/api/save_media_bulk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: itemsWithSongInfo }),
                });

                const result = await response.json();
                if (result.status === 'success') {
                    alert(result.message || '登録が完了しました');
                    bulkTextarea.value = '';
                    previewSection.classList.add('hidden');
                    previewData = [];
                    releases = [];
                    trackTypes = {};
                } else {
                    alert('エラー: ' + (result.message || '不明なエラー'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                btnExecute.disabled = false;
                btnExecute.innerHTML = '<i class="fa-solid fa-check"></i> 一括登録実行';
            }
        });

        // モバイルメニュー
        document.getElementById('mobileMenuBtn').onclick = () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        };
    </script>
</body>
</html>
