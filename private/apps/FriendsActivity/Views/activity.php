<?php
/**
 * 友人の視聴一覧 View
 */
$cardIconText = $cardIconText ?? '';
$themeTailwind = $themeTailwind ?? 'indigo';
$typeLabels = ['anime' => 'アニメ', 'movie' => '映画', 'drama' => 'ドラマ'];
$currentFilter = $_GET['filter'] ?? '';
$currentUserId = (int)($_GET['user_id'] ?? 0);
$viewableUsers = $viewableUsers ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>友人の視聴 - MyPlatform</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --activity-theme: <?= htmlspecialchars($themePrimaryHex) ?>;
            --mv-theme: var(--activity-theme);
        }
        .mv-theme-btn { background-color: var(--mv-theme); color: #fff; }
        .mv-theme-btn:hover { filter: brightness(1.08); }
        <?php if ($isThemeHex): ?>
        .activity-tab.active { color: var(--activity-theme); border-bottom-color: var(--activity-theme); }
        <?php else: ?>
        .activity-tab.active { <?= $tabActiveClass ?>; border-bottom-width: 2px; }
        <?php endif; ?>
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left text-sm"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-user-group text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">友人の視聴</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            <div class="max-w-5xl mx-auto">
                <!-- 人で絞り込み（帯） -->
                <?php if ($hasViewable && !empty($viewableUsers)): ?>
                <div class="mb-6">
                    <p class="text-xs font-bold text-slate-400 tracking-wider mb-2">表示する人</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="<?= $currentFilter ? "?filter={$currentFilter}" : '/friends_activity.php' ?>" class="px-4 py-2 rounded-full text-sm font-bold transition <?= $currentUserId === 0 ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">全て</a>
                        <?php foreach ($viewableUsers as $vu): ?>
                        <a href="<?= $currentFilter ? "?filter={$currentFilter}&user_id={$vu['user_id']}" : "?user_id={$vu['user_id']}" ?>" class="px-4 py-2 rounded-full text-sm font-bold transition <?= $currentUserId === (int)$vu['user_id'] ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"><?= htmlspecialchars($vu['id_name'] ?? '') ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 種別タブ -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <a href="<?= $currentUserId ? "?user_id={$currentUserId}" : '/friends_activity.php' ?>" class="activity-tab px-4 py-2 rounded-t-lg text-sm font-bold transition <?= $currentFilter === '' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>">全て</a>
                    <a href="/friends_activity.php?filter=anime<?= $currentUserId ? "&user_id={$currentUserId}" : '' ?>" class="activity-tab px-4 py-2 rounded-t-lg text-sm font-bold transition <?= $currentFilter === 'anime' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>">アニメ</a>
                    <a href="/friends_activity.php?filter=movie<?= $currentUserId ? "&user_id={$currentUserId}" : '' ?>" class="activity-tab px-4 py-2 rounded-t-lg text-sm font-bold transition <?= $currentFilter === 'movie' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>">映画</a>
                    <a href="/friends_activity.php?filter=drama<?= $currentUserId ? "&user_id={$currentUserId}" : '' ?>" class="activity-tab px-4 py-2 rounded-t-lg text-sm font-bold transition <?= $currentFilter === 'drama' ? 'active' : 'text-slate-400 hover:text-slate-600' ?>">ドラマ</a>
                </div>

                <?php if (!$hasViewable): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center">
                    <p class="text-slate-500">友達やグループに参加すると、ここに友人の視聴履歴が表示されます。</p>
                    <p class="text-sm text-slate-400 mt-2">管理者に友達登録またはグループへの追加を依頼してください。</p>
                </div>
                <?php elseif (empty($items)): ?>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center">
                    <p class="text-slate-500">まだ視聴履歴はありません</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($items as $item): ?>
                    <?php
                    $canModal = false;
                    $modalData = '';
                    if ($item['type'] === 'anime') {
                        $canModal = true;
                        $modalData = json_encode(['id' => $item['item_id'], 'title' => $item['title'], 'images' => ['recommended_url' => $item['image_url'] ?? '']]);
                    } elseif ($item['type'] === 'movie' && !empty($item['tmdb_id'])) {
                        $canModal = true;
                        $modalData = json_encode([
                            'id' => $item['tmdb_id'], 'title' => $item['title'],
                            'original_title' => $item['original_title'] ?? null,
                            'poster_path' => $item['poster_path'] ?? null,
                            'release_date' => $item['release_date'] ?? null,
                            'overview' => $item['overview'] ?? null,
                            'user_status' => $item['user_status'] ?? null,
                            'user_movie_id' => $item['user_movie_id'] ?? null,
                        ]);
                    } elseif ($item['type'] === 'drama' && !empty($item['tmdb_id'])) {
                        $canModal = true;
                        $modalData = json_encode([
                            'id' => $item['tmdb_id'], 'name' => $item['title'], 'title' => $item['title'],
                            'original_name' => $item['original_title'] ?? null,
                            'poster_path' => $item['poster_path'] ?? null,
                            'first_air_date' => $item['first_air_date'] ?? null,
                            'number_of_seasons' => $item['number_of_seasons'] ?? null,
                            'number_of_episodes' => $item['number_of_episodes'] ?? null,
                            'overview' => $item['overview'] ?? null,
                            'user_status' => $item['user_status'] ?? null,
                            'user_series_id' => $item['user_series_id'] ?? null,
                        ]);
                    }
                    ?>
                    <?php if ($canModal): ?>
                    <div class="group block bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-all cursor-pointer" role="button" tabindex="0" onclick="FriendsActivity.openItem('<?= $item['type'] ?>', <?= htmlspecialchars($modalData) ?>)">
                    <?php else: ?>
                    <a href="<?= htmlspecialchars($item['detail_url']) ?>" class="group block bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-all">
                    <?php endif; ?>
                        <div class="aspect-[2/3] bg-slate-100 relative overflow-hidden">
                            <?php if (!empty($item['image_url'])): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-300">
                                <i class="fa-solid fa-<?= $item['type'] === 'anime' ? 'tv' : ($item['type'] === 'drama' ? 'clapperboard' : 'film') ?> text-4xl"></i>
                            </div>
                            <?php endif; ?>
                            <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-bold bg-black/60 text-white"><?= htmlspecialchars($typeLabels[$item['type']] ?? $item['type']) ?></span>
                            <?php $itemReg = !empty($item['_registered']); ?>
                            <?php if ($itemReg): ?>
                            <div class="absolute top-2 right-2 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>
                            <?php elseif ($item['type'] === 'anime'): ?>
                            <div class="absolute top-2 right-2 flex flex-col gap-1" onclick="event.stopPropagation()">
                                <button onclick="FriendsActivity.addAnime(<?= (int)$item['item_id'] ?>, 'wanna_watch', this)" class="w-6 h-6 bg-white/90 hover:bg-amber-400 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たい"><i class="fa-solid fa-bookmark"></i></button>
                                <button onclick="FriendsActivity.addAnime(<?= (int)$item['item_id'] ?>, 'watching', this)" class="w-6 h-6 bg-white/90 hover:bg-sky-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見てる"><i class="fa-solid fa-play"></i></button>
                                <button onclick="FriendsActivity.addAnime(<?= (int)$item['item_id'] ?>, 'watched', this)" class="w-6 h-6 bg-white/90 hover:bg-green-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見た"><i class="fa-solid fa-check"></i></button>
                            </div>
                            <?php elseif ($item['type'] === 'movie' && !empty($item['tmdb_id'])): ?>
                            <div class="absolute top-2 right-2 flex flex-col gap-1" onclick="event.stopPropagation()">
                                <button onclick="FriendsActivity.addMovie(<?= (int)$item['tmdb_id'] ?>, 'watchlist', this)" class="w-6 h-6 bg-white/90 hover:bg-amber-400 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たい"><i class="fa-solid fa-bookmark"></i></button>
                                <button onclick="FriendsActivity.addMovie(<?= (int)$item['tmdb_id'] ?>, 'watched', this)" class="w-6 h-6 bg-white/90 hover:bg-green-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見た"><i class="fa-solid fa-eye"></i></button>
                            </div>
                            <?php elseif ($item['type'] === 'drama' && !empty($item['tmdb_id'])): ?>
                            <div class="absolute top-2 right-2 flex flex-col gap-1" onclick="event.stopPropagation()">
                                <button onclick="FriendsActivity.addDrama(<?= (int)$item['tmdb_id'] ?>, 'wanna_watch', this)" class="w-6 h-6 bg-white/90 hover:bg-amber-400 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見たい"><i class="fa-solid fa-bookmark"></i></button>
                                <button onclick="FriendsActivity.addDrama(<?= (int)$item['tmdb_id'] ?>, 'watching', this)" class="w-6 h-6 bg-white/90 hover:bg-sky-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見てる"><i class="fa-solid fa-play"></i></button>
                                <button onclick="FriendsActivity.addDrama(<?= (int)$item['tmdb_id'] ?>, 'watched', this)" class="w-6 h-6 bg-white/90 hover:bg-green-500 hover:text-white text-slate-500 rounded-full flex items-center justify-center text-[10px] shadow transition backdrop-blur-sm" title="見た"><i class="fa-solid fa-check"></i></button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-bold text-slate-800 line-clamp-2"><?= htmlspecialchars($item['title']) ?></p>
                            <p class="text-[10px] text-slate-500 mt-1"><?= htmlspecialchars($item['id_name']) ?> <?= !empty($item['watched_date']) ? '・' . htmlspecialchars($item['watched_date']) : '' ?></p>
                        </div>
                    <?php echo $canModal ? '</div>' : '</a>'; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=2"></script>
    <?php if ($hasViewable && !empty($items)): ?>
    <?php require_once __DIR__ . '/../../Anime/Views/_anime_search_shared.php'; ?>
    <?php require_once __DIR__ . '/../../Movie/Views/_movie_search_shared.php'; ?>
    <?php require_once __DIR__ . '/../../Drama/Views/_drama_search_shared.php'; ?>
    <script>
        const FriendsActivity = {
            openItem(type, data) {
                if (type === 'anime' && typeof AnimePreview !== 'undefined') AnimePreview.open(data);
                else if (type === 'movie' && typeof MoviePreview !== 'undefined') MoviePreview.open(data);
                else if (type === 'drama' && typeof DramaPreview !== 'undefined') DramaPreview.open(data);
            },
            async addAnime(workId, kind, btnEl) {
                btnEl.disabled = true;
                const orig = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                try {
                    const r = await App.post('/anime/api/set_status.php', { work_id: workId, kind: kind });
                    if (r.status === 'success') {
                        const wrap = btnEl.closest('.absolute');
                        if (wrap) wrap.outerHTML = '<div class="absolute top-2 right-2 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                        App.toast({ wanna_watch: '見たい', watching: '見てる', watched: '見た' }[kind] + 'に追加しました');
                    } else { App.toast(r.message || '失敗'); btnEl.disabled = false; btnEl.innerHTML = orig; }
                } catch (e) { App.toast('エラー'); btnEl.disabled = false; btnEl.innerHTML = orig; }
            },
            async addMovie(tmdbId, status, btnEl) {
                btnEl.disabled = true;
                const orig = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                try {
                    const r = await App.post('/movie/api/add.php', { tmdb_id: tmdbId, status: status });
                    if (r.status === 'success') {
                        const wrap = btnEl.closest('.absolute');
                        if (wrap) wrap.outerHTML = '<div class="absolute top-2 right-2 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                        App.toast(status === 'watched' ? '見たリストに追加しました' : '見たいリストに追加しました');
                    } else { App.toast(r.message || '失敗'); btnEl.disabled = false; btnEl.innerHTML = orig; }
                } catch (e) { App.toast('エラー'); btnEl.disabled = false; btnEl.innerHTML = orig; }
            },
            async addDrama(tmdbId, status, btnEl) {
                btnEl.disabled = true;
                const orig = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                try {
                    const r = await App.post('/drama/api/add.php', { tmdb_id: tmdbId, status: status });
                    if (r.status === 'success') {
                        const wrap = btnEl.closest('.absolute');
                        if (wrap) wrap.outerHTML = '<div class="absolute top-2 right-2 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] shadow"><i class="fa-solid fa-check"></i></div>';
                        App.toast({ wanna_watch: '見たい', watching: '見てる', watched: '見た' }[status] + 'に追加しました');
                    } else { App.toast(r.message || '失敗'); btnEl.disabled = false; btnEl.innerHTML = orig; }
                } catch (e) { App.toast('エラー'); btnEl.disabled = false; btnEl.innerHTML = orig; }
            }
        };
    </script>
    <?php endif; ?>
    <script>
        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');
    </script>
</body>
</html>
