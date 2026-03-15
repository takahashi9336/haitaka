<?php
$appKey = 'drama';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$genres = [];
if (!empty($series['genres'])) {
    $decoded = json_decode($series['genres'], true);
    if (is_array($decoded)) $genres = $decoded;
}
$posterUrl = !empty($series['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $series['poster_path'] : '';
$backdropUrl = !empty($series['backdrop_path']) ? 'https://image.tmdb.org/t/p/w1280' . $series['backdrop_path'] : '';

$statusLabels = ['wanna_watch' => '見たい', 'watching' => '見てる', 'watched' => '見た'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($series['title']) ?> - ドラマリスト</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --dr-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .dr-theme-btn { background-color: var(--dr-theme); }
        .dr-theme-btn:hover { filter: brightness(1.08); }
        .dr-theme-text { color: var(--dr-theme); }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease; width: 240px; }
        .sidebar.collapsed { width: 64px; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <!-- ヘッダー -->
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a id="drBackLink" href="/drama/list.php" class="flex items-center gap-2 text-slate-500 hover:text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span id="drBackLinkText" class="text-sm font-bold">ドラマリスト</span>
                </a>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($series['status'] === 'wanna_watch'): ?>
                <button onclick="DrDetail.setStatus('watching')" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-play mr-1"></i>見てるに変更
                </button>
                <button onclick="DrDetail.openWatchedModal()" class="px-4 py-2 dr-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-check mr-1"></i>見たに変更
                </button>
                <?php elseif ($series['status'] === 'watching'): ?>
                <button onclick="DrDetail.setStatus('wanna_watch')" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-bookmark mr-1"></i>見たいに戻す
                </button>
                <button onclick="DrDetail.openWatchedModal()" class="px-4 py-2 dr-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-check mr-1"></i>見たに変更
                </button>
                <?php else: ?>
                <button onclick="DrDetail.setStatus('wanna_watch')" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-bookmark mr-1"></i>見たいに戻す
                </button>
                <?php endif; ?>
                <button onclick="DrDetail.remove()" class="p-2 text-slate-400 hover:text-red-500 transition" title="リストから削除">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto">
            <?php if ($backdropUrl): ?>
            <div class="relative h-48 md:h-64 lg:h-72 overflow-hidden">
                <img src="<?= htmlspecialchars($backdropUrl) ?>" class="w-full h-full object-cover object-top" alt="">
                <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(15,23,42,0.1),rgba(15,23,42,0.7));"></div>
            </div>
            <?php endif; ?>

            <div class="max-w-4xl mx-auto px-6 md:px-12 <?= $backdropUrl ? '-mt-20 md:-mt-24 relative z-10' : 'pt-6' ?>">
                <div class="flex flex-col md:flex-row gap-6 mb-8">
                    <div class="shrink-0">
                        <?php if ($posterUrl): ?>
                        <img src="<?= htmlspecialchars($posterUrl) ?>"
                             alt="<?= htmlspecialchars($series['title']) ?>"
                             class="w-40 md:w-52 rounded-xl shadow-2xl border-2 border-white/20 aspect-[2/3] object-cover">
                        <?php else: ?>
                        <div class="w-40 md:w-52 aspect-[2/3] bg-slate-200 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-clapperboard text-5xl text-slate-400"></i>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 pt-2">
                        <div class="<?= $backdropUrl ? 'bg-white/90 backdrop-blur-sm rounded-xl p-4 -mx-2 shadow-sm' : '' ?>">
                            <h1 class="text-2xl md:text-3xl font-black text-slate-800 mb-1"><?= htmlspecialchars($series['title']) ?></h1>
                            <?php if (!empty($series['original_title']) && $series['original_title'] !== $series['title']): ?>
                            <p class="text-sm text-slate-400 mb-2"><?= htmlspecialchars($series['original_title']) ?></p>
                            <?php endif; ?>

                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 mb-4">
                                <?php if (!empty($series['first_air_date'])): ?>
                                <span><i class="fa-solid fa-calendar mr-1"></i><?= date('Y年m月d日', strtotime($series['first_air_date'])) ?> 配信開始</span>
                                <?php endif; ?>
                                <?php if (!empty($series['number_of_seasons'])): ?>
                                <span><i class="fa-solid fa-layer-group mr-1"></i><?= (int)$series['number_of_seasons'] ?>シーズン</span>
                                <?php endif; ?>
                                <?php if (!empty($series['number_of_episodes'])): ?>
                                <span><i class="fa-solid fa-list-ol mr-1"></i><?= (int)$series['number_of_episodes'] ?>話</span>
                                <?php endif; ?>
                                <?php if (!empty($series['runtime_avg'])): ?>
                                <span><i class="fa-solid fa-clock mr-1"></i>平均 <?= (int)$series['runtime_avg'] ?>分/話</span>
                                <?php endif; ?>
                                <?php if (!empty($series['vote_average']) && $series['vote_average'] > 0): ?>
                                <span class="text-amber-500 font-bold">
                                    <i class="fa-solid fa-star mr-0.5"></i><?= number_format($series['vote_average'], 1) ?>
                                    <?php if (!empty($series['vote_count'])): ?>
                                    <span class="text-slate-400 font-normal text-xs">(<?= number_format($series['vote_count']) ?>件)</span>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($genres)): ?>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php foreach ($genres as $genre): ?>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600"><?= htmlspecialchars(is_string($genre) ? $genre : '') ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold">
                                    <i class="fa-solid fa-list-check"></i>
                                    <?= $statusLabels[$series['status']] ?? '不明' ?>
                                    <?php if (!empty($series['watched_date']) && $series['status'] === 'watched'): ?>
                                    <span class="text-slate-400 text-[11px]"><?= date('Y/m/d', strtotime($series['watched_date'])) ?> 完走</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($series['tmdb_id'])): ?>
                                <a href="https://www.google.com/search?q=<?= urlencode(($series['title'] ?? '') . ' ドラマ') ?>"
                                   target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 rounded-lg text-xs font-bold transition">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>検索
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- あらすじ -->
                <?php if (!empty($series['overview'])): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-500 mb-3"><i class="fa-solid fa-align-left mr-1.5"></i>あらすじ</h2>
                    <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($series['overview'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- 配信サービス -->
                <?php
                $wpData = null;
                $wpUpdated = null;
                if (!empty($series['watch_providers'])) {
                    $wpData = json_decode($series['watch_providers'], true);
                    $wpUpdated = $series['watch_providers_updated_at'] ?? null;
                }
                ?>
                <?php if ($wpData && (!empty($wpData['flatrate']) || !empty($wpData['rent']) || !empty($wpData['buy']))): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-bold text-slate-500"><i class="fa-solid fa-tv mr-1.5"></i>配信サービス</h2>
                        <?php if ($wpUpdated): ?>
                        <span class="text-[10px] text-slate-400"><?= date('Y年n月j日', strtotime($wpUpdated)) ?> 時点</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($wpData['flatrate'])): ?>
                    <div class="mb-3">
                        <p class="text-[11px] font-bold text-slate-400 mb-2">見放題</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($wpData['flatrate'] as $p): ?>
                            <div class="flex items-center gap-1.5 bg-slate-50 rounded-lg px-2.5 py-1.5" title="<?= htmlspecialchars($p['provider_name']) ?>">
                                <?php if (!empty($p['logo_path'])): ?>
                                <img src="https://image.tmdb.org/t/p/w45<?= htmlspecialchars($p['logo_path']) ?>" class="w-5 h-5 rounded" loading="lazy" alt="">
                                <?php endif; ?>
                                <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($p['provider_name']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($wpData['rent'])): ?>
                    <div class="mb-3">
                        <p class="text-[11px] font-bold text-slate-400 mb-2">レンタル</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($wpData['rent'] as $p): ?>
                            <div class="flex items-center gap-1.5 bg-slate-50 rounded-lg px-2.5 py-1.5" title="<?= htmlspecialchars($p['provider_name']) ?>">
                                <?php if (!empty($p['logo_path'])): ?>
                                <img src="https://image.tmdb.org/t/p/w45<?= htmlspecialchars($p['logo_path']) ?>" class="w-5 h-5 rounded" loading="lazy" alt="">
                                <?php endif; ?>
                                <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($p['provider_name']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($wpData['buy'])): ?>
                    <div class="mb-3">
                        <p class="text-[11px] font-bold text-slate-400 mb-2">購入</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($wpData['buy'] as $p): ?>
                            <div class="flex items-center gap-1.5 bg-slate-50 rounded-lg px-2.5 py-1.5" title="<?= htmlspecialchars($p['provider_name']) ?>">
                                <?php if (!empty($p['logo_path'])): ?>
                                <img src="https://image.tmdb.org/t/p/w45<?= htmlspecialchars($p['logo_path']) ?>" class="w-5 h-5 rounded" loading="lazy" alt="">
                                <?php endif; ?>
                                <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($p['provider_name']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($wpData['link'])): ?>
                    <a href="<?= htmlspecialchars($wpData['link']) ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1 text-[11px] text-slate-400 hover:text-slate-600 transition mt-1">
                        <i class="fa-solid fa-up-right-from-square"></i> TMDBで配信情報を見る
                    </a>
                    <?php endif; ?>

                    <p class="text-[10px] text-slate-400 mt-2">配信情報提供: <a href="https://www.justwatch.com/" target="_blank" rel="noopener noreferrer" class="underline hover:text-slate-600">JustWatch</a></p>
                </div>
                <?php endif; ?>

                <!-- レビュー -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-500 mb-4"><i class="fa-solid fa-pen mr-1.5"></i>マイレビュー</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-400 mb-1 block">視聴完了日</label>
                            <input type="date" id="drReviewDate" value="<?= htmlspecialchars($series['watched_date'] ?? '') ?>"
                                   class="w-full max-w-xs px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)]">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 mb-2 block">評価 (1-10)</label>
                            <div class="flex items-center gap-3">
                                <input type="number" id="drReviewRating" min="1" max="10" step="1"
                                       value="<?= $series['rating'] !== null ? (int)$series['rating'] : '' ?>"
                                       class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)]">
                                <span class="text-xs text-slate-400">数字で入力（空欄で未評価）</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 mb-1 block">メモ・感想</label>
                            <textarea id="drReviewMemo" rows="4" placeholder="ドラマの感想を書こう..."
                                      class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)] resize-none"><?= htmlspecialchars($series['memo'] ?? '') ?></textarea>
                        </div>
                        <button onclick="DrDetail.saveReview()" class="px-6 py-2 dr-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-save mr-1"></i>保存
                        </button>
                    </div>
                </div>

                <!-- タグ -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-500 mb-3"><i class="fa-solid fa-tags mr-1.5"></i>タグ</h2>
                    <div class="flex flex-wrap gap-1.5 mb-3" id="drTagContainer">
                        <?php
                        $drTags = [];
                        if (!empty($series['tags'])) {
                            $decoded = json_decode($series['tags'], true);
                            if (is_array($decoded)) $drTags = $decoded;
                        }
                        ?>
                        <?php foreach ($drTags as $tag): ?>
                        <span class="inline-flex items-center gap-1 text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-tag text-[9px]"></i><?= htmlspecialchars($tag) ?>
                            <button onclick="DrTagEditor.remove('<?= htmlspecialchars(addslashes($tag)) ?>')" class="text-amber-400 hover:text-red-500 transition ml-0.5">&times;</button>
                        </span>
                        <?php endforeach; ?>
                        <?php if (empty($drTags)): ?>
                        <span class="text-xs text-slate-400" id="drTagEmpty">タグなし</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <input type="text" id="drTagInput" placeholder="タグを入力してEnter..."
                                   class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)] focus:border-transparent"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault(); DrTagEditor.add();}" autocomplete="off">
                        </div>
                        <button onclick="DrTagEditor.add()" class="px-3 py-2 dr-theme-btn text-white text-sm font-bold rounded-lg transition">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- メタ情報 -->
                <div class="text-xs text-slate-400 pb-4">
                    <p>リスト追加: <?= htmlspecialchars($series['created_at'] ?? '') ?> / 更新: <?= htmlspecialchars($series['updated_at'] ?? '') ?></p>
                    <p class="mt-0.5">TMDB ID: <?= htmlspecialchars($series['tmdb_id'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </main>

    <!-- 「見た」に変更モーダル -->
    <div id="drWatchedModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity" onclick="DrWatchedModal.close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-check-circle dr-theme-text mr-2"></i>見たドラマに変更</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">視聴完了日 <span class="text-slate-400 font-normal">（任意）</span></label>
                    <input type="date" id="drWatchedDate" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">評価 (1-10)</label>
                    <input type="number" id="drWatchedRating" min="1" max="10" step="1"
                           class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">メモ・感想</label>
                    <textarea id="drWatchedMemo" rows="3" placeholder="感想をメモ..."
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--dr-theme)] resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button onclick="DrWatchedModal.close()" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">キャンセル</button>
                <button onclick="DrWatchedModal.save()" class="flex-1 px-4 py-2.5 dr-theme-btn text-white text-sm font-bold rounded-lg transition">登録</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const drUserSeriesId = <?= (int)$series['id'] ?>;

        const DrDetail = {
            async setStatus(status) {
                try {
                    const payload = { id: drUserSeriesId, status };
                    const result = await App.post('/drama/api/update.php', payload);
                    if (result.status === 'success') {
                        App.toast('ステータスを更新しました');
                        location.reload();
                    } else {
                        App.toast(result.message || '更新に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            },
            openWatchedModal() {
                document.getElementById('drWatchedDate').value = '';
                document.getElementById('drWatchedRating').value = '';
                document.getElementById('drWatchedMemo').value = '';
                const modal = document.getElementById('drWatchedModal');
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.classList.add('opacity-100');
            },
            async saveReview() {
                const dateVal = document.getElementById('drReviewDate').value;
                const ratingVal = document.getElementById('drReviewRating').value;
                const memoVal = document.getElementById('drReviewMemo').value.trim();
                const clearDate = dateVal === '';
                const payload = {
                    id: drUserSeriesId,
                    rating: ratingVal ? parseInt(ratingVal, 10) : null,
                    memo: memoVal || null,
                    watched_date: dateVal || null
                };
                if (clearDate) payload.watched_date = '';
                try {
                    const result = await App.post('/drama/api/update.php', payload);
                    if (result.status === 'success') {
                        App.toast('レビューを保存しました');
                    } else {
                        App.toast(result.message || '保存に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            },
            async remove() {
                if (!confirm('このドラマをリストから削除しますか？')) return;
                try {
                    const result = await App.post('/drama/api/remove.php', { id: drUserSeriesId });
                    if (result.status === 'success') {
                        App.toast('削除しました');
                        const saved = sessionStorage.getItem('app:lastListParams:/drama/list.php');
                        location.href = '/drama/list.php' + (saved || '');
                    } else {
                        App.toast(result.message || '削除に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            }
        };

        const DrWatchedModal = {
            close() {
                const modal = document.getElementById('drWatchedModal');
                modal.classList.add('opacity-0', 'pointer-events-none');
                modal.classList.remove('opacity-100');
            },
            async save() {
                const dateVal = document.getElementById('drWatchedDate').value;
                const ratingVal = document.getElementById('drWatchedRating').value;
                const memoVal = document.getElementById('drWatchedMemo').value.trim();
                const payload = {
                    id: drUserSeriesId,
                    status: 'watched',
                    watched_date: dateVal || null,
                    rating: ratingVal ? parseInt(ratingVal, 10) : null,
                    memo: memoVal || null
                };
                try {
                    const result = await App.post('/drama/api/update.php', payload);
                    if (result.status === 'success') {
                        App.toast('見たに変更しました');
                        this.close();
                        location.reload();
                    } else {
                        App.toast(result.message || '更新に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('エラーが発生しました');
                }
            }
        };

        const DrTagEditor = {
            tags: <?= json_encode($drTags, JSON_UNESCAPED_UNICODE) ?>,
            render() {
                const container = document.getElementById('drTagContainer');
                if (!this.tags.length) {
                    container.innerHTML = '<span class="text-xs text-slate-400" id="drTagEmpty">タグなし</span>';
                    return;
                }
                container.innerHTML = this.tags.map(tag => {
                    const escaped = this.esc(tag);
                    const escapedJs = tag.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                    return `<span class="inline-flex items-center gap-1 text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                        <i class="fa-solid fa-tag text-[9px]"></i>${escaped}
                        <button onclick="DrTagEditor.remove('${escapedJs}')" class="text-amber-400 hover:text-red-500 transition ml-0.5">&times;</button>
                    </span>`;
                }).join('');
            },
            async add() {
                const input = document.getElementById('drTagInput');
                const tag = input.value.trim();
                if (!tag) return;
                if (this.tags.includes(tag)) {
                    App.toast('このタグは既に追加されています');
                    return;
                }
                this.tags.push(tag);
                input.value = '';
                this.render();
                await this.save();
            },
            async remove(tag) {
                this.tags = this.tags.filter(t => t !== tag);
                this.render();
                await this.save();
            },
            async save() {
                try {
                    const result = await App.post('/drama/api/update_tags.php', {
                        id: drUserSeriesId,
                        tags: this.tags
                    });
                    if (result.status !== 'success') {
                        App.toast(result.message || 'タグの保存に失敗しました');
                    }
                } catch (e) {
                    console.error(e);
                    App.toast('タグの保存に失敗しました');
                }
            },
            esc(str) {
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        };

        DrTagEditor.render();

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                DrWatchedModal.close();
            }
        });

        (function() {
            const saved = sessionStorage.getItem('app:lastListParams:/drama/list.php');
            const link = document.getElementById('drBackLink');
            const text = document.getElementById('drBackLinkText');
            if (saved && link && text) {
                link.href = '/drama/list.php' + saved;
                text.textContent = 'ドラマリスト';
            }
        })();
    </script>
</body>
</html>

