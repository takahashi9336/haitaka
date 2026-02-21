<?php
$appKey = 'movie';
require_once __DIR__ . '/../../../components/theme_from_session.php';

$genres = [];
if (!empty($movie['genres'])) {
    $decoded = json_decode($movie['genres'], true);
    if (is_array($decoded)) $genres = $decoded;
}
$isPlaceholder = empty($movie['tmdb_id']);
$posterUrl = !empty($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : '';
$backdropUrl = !empty($movie['backdrop_path']) ? 'https://image.tmdb.org/t/p/w1280' . $movie['backdrop_path'] : '';

$director = '';
$cast = [];
if (!empty($movie['credits'])) {
    if (!empty($movie['credits']['crew'])) {
        foreach ($movie['credits']['crew'] as $crew) {
            if ($crew['job'] === 'Director') {
                $director = $crew['name'];
                break;
            }
        }
    }
    if (!empty($movie['credits']['cast'])) {
        $cast = array_slice($movie['credits']['cast'], 0, 10);
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - 映画リスト</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --mv-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
        .mv-theme-btn { background-color: var(--mv-theme); }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        .mv-theme-text { color: var(--mv-theme); }
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
            .sidebar.mobile-open .nav-text, .sidebar.mobile-open .logo-text, .sidebar.mobile-open .user-info { display: inline !important; }
        }
        .star-rating .star { cursor: pointer; transition: color 0.15s; }
        .star-rating .star:hover, .star-rating .star.filled { color: #f59e0b; }
        .backdrop-gradient {
            background: linear-gradient(to bottom, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.85) 100%);
        }
        .hero-title {
            text-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 0 12px rgba(0,0,0,0.15);
        }
        .cast-scroll { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .cast-scroll::-webkit-scrollbar { height: 6px; }
        .cast-scroll::-webkit-scrollbar-track { background: transparent; }
        .cast-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../../private/components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <!-- ヘッダー -->
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a id="backLink" href="/movie/list.php" class="flex items-center gap-2 text-slate-500 hover:text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span id="backLinkText" class="text-sm font-bold">映画リスト</span>
                </a>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($movie['status'] === 'watchlist'): ?>
                <button onclick="MovieDetail.markWatched()" class="px-4 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-check mr-1"></i>見た
                </button>
                <?php else: ?>
                <button onclick="MovieDetail.moveToWatchlist()" class="px-4 py-2 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-bookmark mr-1"></i>見たいに戻す
                </button>
                <?php endif; ?>
                <button onclick="MovieDetail.remove()" class="p-2 text-slate-400 hover:text-red-500 transition" title="リストから削除">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto">
            <!-- バックドロップ -->
            <?php if ($backdropUrl): ?>
            <div class="relative h-56 md:h-72 lg:h-80 overflow-hidden">
                <img src="<?= htmlspecialchars($backdropUrl) ?>" class="w-full h-full object-cover object-top">
                <div class="absolute inset-0 backdrop-gradient"></div>
            </div>
            <?php endif; ?>

            <div class="max-w-4xl mx-auto px-6 md:px-12 <?= $backdropUrl ? '-mt-24 md:-mt-28 relative z-10' : 'pt-6' ?>">
                <div class="flex flex-col md:flex-row gap-6 mb-8">
                    <!-- ポスター -->
                    <div class="shrink-0">
                        <?php if ($posterUrl): ?>
                        <img src="<?= htmlspecialchars($posterUrl) ?>"
                             alt="<?= htmlspecialchars($movie['title']) ?>"
                             class="w-40 md:w-52 rounded-xl shadow-2xl border-2 border-white/20">
                        <?php else: ?>
                        <div class="w-40 md:w-52 aspect-[2/3] bg-slate-200 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-film text-5xl text-slate-400"></i>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 基本情報 -->
                    <div class="flex-1 pt-2">
                        <div class="<?= $backdropUrl ? 'bg-white/85 backdrop-blur-sm rounded-xl p-4 -mx-2 shadow-sm' : '' ?>">
                        <h1 class="text-2xl md:text-3xl font-black text-slate-800 mb-1 <?= $backdropUrl ? 'hero-title' : '' ?>"><?= htmlspecialchars($movie['title']) ?></h1>
                        <?php if ($movie['original_title'] && $movie['original_title'] !== $movie['title']): ?>
                        <p class="text-sm text-slate-400 mb-3"><?= htmlspecialchars($movie['original_title']) ?></p>
                        <?php endif; ?>

                        <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 mb-4">
                            <?php if (!empty($movie['release_date'])): ?>
                            <span><i class="fa-solid fa-calendar mr-1"></i><?= date('Y年m月d日', strtotime($movie['release_date'])) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($movie['runtime'])): ?>
                            <span><i class="fa-solid fa-clock mr-1"></i><?= $movie['runtime'] ?>分</span>
                            <?php endif; ?>
                            <?php if ($movie['vote_average'] && $movie['vote_average'] > 0): ?>
                            <span class="text-amber-500 font-bold">
                                <i class="fa-solid fa-star mr-0.5"></i><?= number_format($movie['vote_average'], 1) ?>
                                <span class="text-slate-400 font-normal text-xs">(<?= number_format($movie['vote_count']) ?>件)</span>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($genres)): ?>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php foreach ($genres as $genre): ?>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600"><?= htmlspecialchars(is_string($genre) ? $genre : '') ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($director): ?>
                        <p class="text-sm text-slate-600 mb-2"><span class="font-bold text-slate-500">監督:</span> <?= htmlspecialchars($director) ?></p>
                        <?php endif; ?>

                        <!-- ステータスバッジ -->
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <?php if ($movie['status'] === 'watched'): ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 text-green-600 rounded-lg text-sm font-bold">
                                <i class="fa-solid fa-check-circle"></i> 視聴済み
                                <?php if ($movie['watched_date']): ?>
                                <span class="text-green-400 text-xs"><?= date('Y/m/d', strtotime($movie['watched_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-bold">
                                <i class="fa-solid fa-bookmark"></i> 見たいリスト
                            </div>
                            <?php endif; ?>

                            <?php if ($isPlaceholder): ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 text-amber-600 rounded-lg text-sm font-bold">
                                <i class="fa-solid fa-clock"></i> 仮登録
                            </div>
                            <?php endif; ?>

                            <a href="https://www.google.com/search?q=<?= urlencode($movie['title'] . ' 映画') ?>"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 rounded-lg text-sm font-bold transition"
                               title="Googleで検索">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>検索
                            </a>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 仮登録: TMDB紐付けセクション -->
                <?php if ($isPlaceholder): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6">
                    <h2 class="text-sm font-bold text-amber-700 mb-3">
                        <i class="fa-solid fa-link mr-1.5"></i>TMDBから映画情報を取得して紐付け
                    </h2>
                    <p class="text-xs text-amber-600 mb-4">この映画はまだTMDB情報が紐付けられていません。検索して正しい映画を選択してください。</p>
                    <div class="flex items-center gap-2">
                        <input type="text" id="tmdbSearchInput" value="<?= htmlspecialchars($movie['title']) ?>"
                               placeholder="映画タイトルで検索..."
                               class="flex-1 px-3 py-2 bg-white border border-amber-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                               onkeydown="if(event.key==='Enter') TmdbLink.search()">
                        <button onclick="TmdbLink.search()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg transition">
                            <i class="fa-solid fa-magnifying-glass mr-1"></i>検索
                        </button>
                    </div>
                    <div id="tmdbLinkResults" class="mt-4 space-y-2 hidden"></div>
                </div>
                <?php endif; ?>

                <!-- あらすじ -->
                <?php if (!empty($movie['overview'])): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-500 mb-3"><i class="fa-solid fa-align-left mr-1.5"></i>あらすじ</h2>
                    <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($movie['overview'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- キャスト -->
                <?php if (!empty($cast)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm mb-6 overflow-hidden">
                    <h2 class="text-sm font-bold text-slate-500 px-6 pt-6 pb-4"><i class="fa-solid fa-users mr-1.5"></i>キャスト</h2>
                    <div class="overflow-x-auto px-6 pb-6 cast-scroll">
                        <table><tr>
                            <?php foreach ($cast as $person): ?>
                            <td class="text-center align-top pr-4 last:pr-0" style="min-width:80px;">
                                <?php if (!empty($person['profile_path'])): ?>
                                <img src="https://image.tmdb.org/t/p/w185<?= htmlspecialchars($person['profile_path']) ?>"
                                     class="w-16 h-16 rounded-full object-cover mx-auto mb-1.5 border-2 border-slate-100" loading="lazy">
                                <?php else: ?>
                                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-1.5">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                </div>
                                <?php endif; ?>
                                <p class="text-[11px] font-bold text-slate-700 line-clamp-1 whitespace-nowrap"><?= htmlspecialchars($person['name'] ?? '') ?></p>
                                <p class="text-[10px] text-slate-400 line-clamp-1 whitespace-nowrap"><?= htmlspecialchars($person['character'] ?? '') ?></p>
                            </td>
                            <?php endforeach; ?>
                        </tr></table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 配信サービス -->
                <?php
                    $wpData = null;
                    $wpUpdated = null;
                    if (!empty($movie['watch_providers'])) {
                        $wpData = json_decode($movie['watch_providers'], true);
                        $wpUpdated = $movie['watch_providers_updated_at'] ?? null;
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
                                <img src="https://image.tmdb.org/t/p/w45<?= htmlspecialchars($p['logo_path']) ?>" class="w-5 h-5 rounded" loading="lazy">
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
                                <img src="https://image.tmdb.org/t/p/w45<?= htmlspecialchars($p['logo_path']) ?>" class="w-5 h-5 rounded" loading="lazy">
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
                                <img src="https://image.tmdb.org/t/p/w45<?= htmlspecialchars($p['logo_path']) ?>" class="w-5 h-5 rounded" loading="lazy">
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

                <!-- 個人レビュー -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-500 mb-4"><i class="fa-solid fa-pen mr-1.5"></i>マイレビュー</h2>

                    <?php if ($movie['status'] === 'watched'): ?>
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-400 mb-1 block">視聴日</label>
                            <input type="date" id="reviewDate" value="<?= htmlspecialchars($movie['watched_date'] ?? '') ?>"
                                   class="w-full max-w-xs px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)]">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 mb-2 block">評価</label>
                            <div class="star-rating flex gap-1" id="reviewRating">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                <span class="star text-xl <?= ($movie['rating'] && $i <= $movie['rating']) ? 'filled' : 'text-slate-300' ?>"
                                      data-value="<?= $i ?>" onclick="MovieDetail.setRating(<?= $i ?>)">
                                    <i class="fa-solid fa-star"></i>
                                </span>
                                <?php endfor; ?>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1" id="ratingLabel"><?= $movie['rating'] ? $movie['rating'] . ' / 10' : '未評価' ?></p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 mb-1 block">メモ・感想</label>
                            <textarea id="reviewMemo" rows="4" placeholder="映画の感想を書こう..."
                                      class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] resize-none"><?= htmlspecialchars($movie['memo'] ?? '') ?></textarea>
                        </div>
                        <button onclick="MovieDetail.saveReview()" class="px-6 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-save mr-1"></i>保存
                        </button>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-slate-400">「見た」に変更するとレビューを書けるようになります</p>
                    <?php endif; ?>
                </div>

                <!-- タグ -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
                    <h2 class="text-sm font-bold text-slate-500 mb-3"><i class="fa-solid fa-tags mr-1.5"></i>タグ</h2>
                    <div class="flex flex-wrap gap-1.5 mb-3" id="tagContainer">
                        <?php
                        $movieTags = [];
                        if (!empty($movie['tags'])) {
                            $decoded = json_decode($movie['tags'], true);
                            if (is_array($decoded)) $movieTags = $decoded;
                        }
                        ?>
                        <?php foreach ($movieTags as $tag): ?>
                        <span class="inline-flex items-center gap-1 text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-tag text-[9px]"></i><?= htmlspecialchars($tag) ?>
                            <button onclick="TagEditor.remove('<?= htmlspecialchars(addslashes($tag)) ?>')" class="text-amber-400 hover:text-red-500 transition ml-0.5">&times;</button>
                        </span>
                        <?php endforeach; ?>
                        <?php if (empty($movieTags)): ?>
                        <span class="text-xs text-slate-400" id="tagEmpty">タグなし</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <input type="text" id="tagInput" placeholder="タグを入力してEnter..."
                                   class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] focus:border-transparent"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault(); TagEditor.add();}" autocomplete="off">
                        </div>
                        <button onclick="TagEditor.add()" class="px-3 py-2 mv-theme-btn text-white text-sm font-bold rounded-lg transition">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- メタ情報 -->
                <div class="text-xs text-slate-400 pb-4">
                    <p>リスト追加: <?= $movie['created_at'] ?> / 更新: <?= $movie['updated_at'] ?></p>
                    <p class="mt-0.5">TMDB ID: <?= $movie['tmdb_id'] ?></p>
                </div>

                <?php require_once __DIR__ . '/_tmdb_attribution.php'; ?>
            </div>
        </div>
    </main>

    <!-- 「見た」に変更モーダル -->
    <div id="watchedModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity" onclick="WatchedModal.close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-check-circle mv-theme-text mr-2"></i>見た映画に変更</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">視聴日 <span class="text-slate-400 font-normal">（任意）</span></label>
                    <input type="date" id="watchedDate" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-2 block">評価</label>
                    <div class="star-rating flex gap-1" id="watchedRatingStars">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <span class="star text-slate-300 text-xl" data-value="<?= $i ?>" onclick="WatchedModal.setRating(<?= $i ?>)">
                            <i class="fa-solid fa-star"></i>
                        </span>
                        <?php endfor; ?>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 mb-1 block">メモ・感想</label>
                    <textarea id="watchedMemo" rows="3" placeholder="感想をメモ..."
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--mv-theme)] resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button onclick="WatchedModal.close()" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-lg hover:bg-slate-50 transition">キャンセル</button>
                <button onclick="WatchedModal.save()" class="flex-1 px-4 py-2.5 mv-theme-btn text-white text-sm font-bold rounded-lg transition">登録</button>
            </div>
        </div>
    </div>

    <!-- 画像プレビューモーダル -->
    <div id="posterPreview" class="fixed inset-0 bg-black/80 z-[70] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200" onclick="event.stopPropagation(); PosterPreview.close()">
        <button class="absolute top-4 right-4 text-white/70 hover:text-white transition text-2xl" onclick="PosterPreview.close()">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <img id="posterPreviewImg" src="" alt=""
             class="max-w-[90vw] max-h-[85vh] rounded-xl shadow-2xl object-contain transition-transform duration-200 scale-95"
             onclick="event.stopPropagation()">
    </div>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const movieEntryId = <?= (int)$movie['id'] ?>;
        const movieInternalId = <?= (int)$movie['movie_id'] ?>;
        const isPlaceholder = <?= $isPlaceholder ? 'true' : 'false' ?>;
        let currentRating = <?= (int)($movie['rating'] ?? 0) ?>;

        const MovieDetail = {
            setRating(val) {
                currentRating = val;
                document.querySelectorAll('#reviewRating .star').forEach(s => {
                    const v = parseInt(s.dataset.value);
                    s.classList.toggle('filled', v <= val);
                    s.classList.toggle('text-slate-300', v > val);
                });
                document.getElementById('ratingLabel').textContent = val + ' / 10';
            },

            markWatched() {
                WatchedModal.open();
            },

            async moveToWatchlist() {
                if (!confirm('見たいリストに戻しますか？（評価・メモは保持されます）')) return;
                const result = await App.post('/movie/api/update.php', {
                    id: movieEntryId,
                    status: 'watchlist',
                });
                if (result.status === 'success') {
                    App.toast('見たいリストに移動しました');
                    location.reload();
                } else {
                    App.toast(result.message || '更新に失敗しました');
                }
            },

            async saveReview() {
                const dateVal = document.getElementById('reviewDate').value;
                const data = {
                    id: movieEntryId,
                    rating: currentRating || null,
                    memo: document.getElementById('reviewMemo').value.trim() || null,
                    watched_date: dateVal || '',
                };
                const result = await App.post('/movie/api/update.php', data);
                if (result.status === 'success') {
                    App.toast('レビューを保存しました');
                } else {
                    App.toast(result.message || '保存に失敗しました');
                }
            },

            async remove() {
                if (!confirm('この映画をリストから削除しますか？')) return;
                const result = await App.post('/movie/api/remove.php', { id: movieEntryId });
                if (result.status === 'success') {
                    App.toast('削除しました');
                    const backTo = sessionStorage.getItem('mv_back_to');
                    location.href = backTo === 'dashboard' ? '/movie/' : '/movie/list.php';
                } else {
                    App.toast(result.message || '削除に失敗しました');
                }
            }
        };

        const WatchedModal = {
            rating: 0,
            open() {
                document.getElementById('watchedDate').value = '';
                document.getElementById('watchedMemo').value = '';
                this.setRating(0);
                const modal = document.getElementById('watchedModal');
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.classList.add('opacity-100');
            },
            close() {
                const modal = document.getElementById('watchedModal');
                modal.classList.add('opacity-0', 'pointer-events-none');
                modal.classList.remove('opacity-100');
            },
            setRating(val) {
                this.rating = val;
                document.querySelectorAll('#watchedRatingStars .star').forEach(s => {
                    const v = parseInt(s.dataset.value);
                    s.classList.toggle('filled', v <= val);
                    s.classList.toggle('text-slate-300', v > val);
                });
            },
            async save() {
                const data = {
                    id: movieEntryId,
                    status: 'watched',
                    watched_date: document.getElementById('watchedDate').value || null,
                    rating: this.rating || null,
                    memo: document.getElementById('watchedMemo').value.trim() || null,
                };
                const result = await App.post('/movie/api/update.php', data);
                if (result.status === 'success') {
                    App.toast('見たリストに移動しました');
                    this.close();
                    location.reload();
                } else {
                    App.toast(result.message || '更新に失敗しました');
                }
            }
        };

        const TmdbLink = {
            async search() {
                const query = document.getElementById('tmdbSearchInput').value.trim();
                if (!query) return;

                const container = document.getElementById('tmdbLinkResults');
                container.classList.remove('hidden');
                container.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-amber-400"></i></div>';

                try {
                    const res = await fetch(`/movie/api/search.php?q=${encodeURIComponent(query)}`);
                    const json = await res.json();

                    if (json.status !== 'success' || !json.data.results || json.data.results.length === 0) {
                        container.innerHTML = '<p class="text-sm text-slate-400 text-center py-4">見つかりませんでした</p>';
                        return;
                    }

                    container.innerHTML = json.data.results.slice(0, 8).map(m => {
                        const poster = m.poster_path
                            ? `<img src="https://image.tmdb.org/t/p/w92${m.poster_path}" class="w-12 h-[72px] object-cover rounded-lg shrink-0 cursor-pointer hover:brightness-90 transition" loading="lazy" onclick="event.stopPropagation(); PosterPreview.open('${m.poster_path}')">`
                            : `<div class="w-12 h-[72px] bg-slate-200 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-film text-slate-400 text-sm"></i></div>`;
                        const year = m.release_date ? m.release_date.substring(0, 4) : '';
                        const rating = m.vote_average ? `<i class="fa-solid fa-star text-amber-400 text-[9px]"></i> ${m.vote_average.toFixed(1)}` : '';
                        return `
                        <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100 cursor-pointer hover:border-amber-300 hover:shadow-sm transition"
                             onclick="TmdbLink.link(${m.id})">
                            ${poster}
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-sm text-slate-800 line-clamp-1">${TmdbLink.esc(m.title)}</div>
                                <div class="text-[11px] text-slate-400">${year} ${rating}</div>
                                ${m.original_title && m.original_title !== m.title ? `<div class="text-[10px] text-slate-400 line-clamp-1">${TmdbLink.esc(m.original_title)}</div>` : ''}
                            </div>
                            <i class="fa-solid fa-link text-amber-400 shrink-0"></i>
                        </div>`;
                    }).join('');
                } catch (e) {
                    container.innerHTML = '<p class="text-sm text-red-400 text-center py-4">検索中にエラーが発生しました</p>';
                }
            },

            async link(tmdbId) {
                if (!confirm('この映画情報を紐付けますか？')) return;

                const result = await App.post('/movie/api/link_tmdb.php', {
                    movie_id: movieInternalId,
                    tmdb_id: tmdbId,
                });

                if (result.status === 'success') {
                    App.toast('TMDB情報を紐付けました');
                    location.reload();
                } else {
                    App.toast(result.message || '紐付けに失敗しました');
                }
            },

            esc(str) {
                if (!str) return '';
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        };

        const TagEditor = {
            tags: <?= json_encode($movieTags, JSON_UNESCAPED_UNICODE) ?>,

            renderTags() {
                const container = document.getElementById('tagContainer');
                if (this.tags.length === 0) {
                    container.innerHTML = '<span class="text-xs text-slate-400" id="tagEmpty">タグなし</span>';
                    return;
                }
                container.innerHTML = this.tags.map(tag => {
                    const escaped = this.esc(tag);
                    const escapedJs = tag.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                    return `<span class="inline-flex items-center gap-1 text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                        <i class="fa-solid fa-tag text-[9px]"></i>${escaped}
                        <button onclick="TagEditor.remove('${escapedJs}')" class="text-amber-400 hover:text-red-500 transition ml-0.5">&times;</button>
                    </span>`;
                }).join('');
            },

            async add() {
                const input = document.getElementById('tagInput');
                const tag = input.value.trim();
                if (!tag) return;
                if (this.tags.includes(tag)) {
                    App.toast('このタグは既に追加されています');
                    return;
                }
                this.tags.push(tag);
                input.value = '';
                this.renderTags();
                await this.save();
            },

            async remove(tag) {
                this.tags = this.tags.filter(t => t !== tag);
                this.renderTags();
                await this.save();
            },

            async save() {
                try {
                    const result = await App.post('/movie/api/update_tags.php', {
                        id: movieEntryId,
                        tags: this.tags,
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

        const PosterPreview = {
            open(posterPath) {
                const img = document.getElementById('posterPreviewImg');
                img.src = 'https://image.tmdb.org/t/p/w500' + posterPath;
                const el = document.getElementById('posterPreview');
                el.classList.remove('pointer-events-none', 'opacity-0');
                el.classList.add('pointer-events-auto', 'opacity-100');
                img.classList.remove('scale-95');
                img.classList.add('scale-100');
            },
            close() {
                const el = document.getElementById('posterPreview');
                const img = document.getElementById('posterPreviewImg');
                el.classList.add('opacity-0');
                el.classList.remove('opacity-100');
                img.classList.add('scale-95');
                img.classList.remove('scale-100');
                setTimeout(() => {
                    el.classList.add('pointer-events-none');
                    el.classList.remove('pointer-events-auto');
                    img.src = '';
                }, 200);
            }
        };

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { PosterPreview.close(); WatchedModal.close(); }
        });

        (() => {
            const backTo = sessionStorage.getItem('mv_back_to');
            const link = document.getElementById('backLink');
            const text = document.getElementById('backLinkText');
            if (backTo === 'dashboard') {
                if (link) link.href = '/movie/';
                if (text) text.textContent = '映画';
            } else {
                const saved = sessionStorage.getItem('app:lastListParams:/movie/list.php');
                if (saved && link) link.href = '/movie/list.php' + saved;
            }
        })();
    </script>
</body>
</html>
