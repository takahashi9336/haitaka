<?php
/**
 * リリース詳細 View（収録曲一覧）
 * 物理パス: haitaka/private/apps/Hinata/Views/release_show.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$groupNames = $groupNames ?? [
    'hinatazaka46' => '日向坂46',
    'hiragana_keyaki' => 'けやき坂46',
];
$mainJacket = null;
foreach ($release['editions'] ?? [] as $ed) {
    if (($ed['edition'] ?? '') === 'type_a' && !empty($ed['jacket_image_url'])) {
        $mainJacket = $ed['jacket_image_url'];
        break;
    }
}
if (!$mainJacket && !empty($release['editions'])) {
    $mainJacket = $release['editions'][0]['jacket_image_url'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($release['title']) ?> - リリース - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-4 shrink-0 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-2">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/songs.php" onclick="event.preventDefault();App.goBack('/hinata/songs.php');" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>><i class="fa-solid fa-compact-disc text-sm"></i></div>
                <h1 class="font-black text-slate-700 text-lg tracking-tight truncate max-w-[200px]"><?= htmlspecialchars($release['title']) ?></h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8">
            <div class="max-w-2xl mx-auto space-y-6">
                <section class="flex gap-5 items-start bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm p-5">
                    <div class="w-16 h-16 shrink-0 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center">
                        <?php if ($mainJacket): ?>
                        <img src="<?= htmlspecialchars($mainJacket) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                        <i class="fa-solid fa-compact-disc text-2xl text-slate-300"></i>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-black text-slate-400 tracking-wider">
                            <?= htmlspecialchars($releaseTypes[$release['release_type']] ?? $release['release_type']) ?>
                            <?php
                            $rg = $release['group_name'] ?? 'hinatazaka46';
                            $rgL = $groupNames[$rg] ?? $rg;
                            ?>
                            <span class="inline-block ml-1 px-2 py-0.5 rounded-full text-[9px] bg-orange-50 text-orange-800"><?= htmlspecialchars($rgL) ?></span>
                        </p>
                        <h2 class="text-xl font-black text-slate-800 mt-1"><?= htmlspecialchars($release['title']) ?></h2>
                        <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($release['release_number'] ?? '') ?>　<?= !empty($release['release_date']) ? \Core\Utils\DateUtil::format($release['release_date'], 'Y年n月d日') : '' ?></p>
                        <?php if (!empty($release['description'])): ?>
                        <p class="text-sm text-slate-600 mt-3 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($release['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b <?= $cardBorder ?> flex items-center justify-between flex-wrap gap-2">
                        <h3 class="text-[10px] font-black text-slate-400 tracking-wider">収録曲（<?= count($release['songs'] ?? []) ?> 曲）</h3>
                        <div class="flex items-center gap-2">
                            <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
                            <a href="/hinata/release_artist_photos.php?release_id=<?= (int)$release['id'] ?>" class="text-[10px] font-bold <?= $cardIconText ?> hover:opacity-80 transition">アーティスト写真</a>
                            <?php endif; ?>
                            <?php
                            $songsListQS = 'tab=songs&release_id=' . (int)$release['id'];
                            $rg = $release['group_name'] ?? 'hinatazaka46';
                            if ($rg === 'hiragana_keyaki') {
                                $songsListQS .= '&group=hiragana_keyaki';
                            }
                            ?>
                            <a href="/hinata/songs.php?<?= htmlspecialchars($songsListQS) ?>" class="text-[10px] font-bold <?= $cardIconText ?>"<?= isset($cardIconStyle) && $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>全曲一覧で見る</a>
                        </div>
                    </div>
                    <?php if (empty($release['songs'])): ?>
                    <p class="p-5 text-slate-400 text-sm">収録曲はありません</p>
                    <?php else: ?>
                    <ul class="divide-y <?= $cardBorder ?>">
                        <?php foreach ($release['songs'] as $s): ?>
                        <li>
                            <a href="/hinata/song.php?id=<?= (int)$s['id'] ?>&from=release&release_id=<?= (int)$release['id'] ?>" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition">
                                <span class="text-slate-400 text-xs w-6 font-mono"><?= (int)($s['track_number'] ?? 0) ?></span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($s['title']) ?></p>
                                    <p class="text-[10px] text-slate-500"><?= htmlspecialchars($trackTypesDisplay[$s['track_type'] ?? ''] ?? $s['track_type'] ?? '') ?></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <?php if (!empty($s['apple_music_url'])): ?>
                                    <i class="fa-brands fa-apple text-slate-400 text-xs" title="Apple Music"></i>
                                    <?php endif; ?>
                                    <?php if (!empty($s['spotify_url'])): ?>
                                    <i class="fa-brands fa-spotify text-green-400 text-xs" title="Spotify"></i>
                                    <?php endif; ?>
                                </div>
                                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>

                <?php
                $isAdmin = in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true);
                if ($isAdmin && !empty($release['songs'])):
                ?>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b <?= $cardBorder ?> flex items-center justify-between">
                        <h3 class="text-[10px] font-black text-slate-400 tracking-wider"><i class="fa-solid fa-sort-numeric-down text-sky-400 mr-1"></i>楽曲順序編集</h3>
                        <button onclick="SongOrder.toggle()" id="songOrderToggle" class="text-[10px] font-bold text-sky-500 hover:text-sky-700 transition">
                            <i class="fa-solid fa-pen-to-square mr-0.5"></i>開く
                        </button>
                    </div>
                    <div id="songOrderBody" class="hidden">
                        <div class="px-5 py-3 space-y-3">
                            <p class="text-[10px] text-slate-500">各楽曲のトラック番号（順序）を指定してください。数値が小さいほど前に表示されます。</p>
                            <?php foreach ($release['songs'] as $s): ?>
                            <div class="flex items-center gap-3 border border-slate-100 rounded-xl p-3 bg-slate-50/50" data-song-id="<?= (int)$s['id'] ?>">
                                <label class="text-[10px] font-bold text-slate-500 shrink-0 w-16">番号</label>
                                <input type="number" min="1" class="song-order-input w-16 h-9 border <?= $cardBorder ?> rounded-lg px-2 text-sm text-center font-mono" value="<?= (int)($s['track_number'] ?? 0) ?: '' ?>" placeholder="—" data-song-id="<?= (int)$s['id'] ?>">
                                <p class="text-sm font-bold text-slate-700 flex-1 truncate"><?= htmlspecialchars($s['title']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="px-5 py-3 border-t <?= $cardBorder ?> flex items-center justify-between bg-slate-50/50">
                            <p id="songOrderStatus" class="text-[10px] text-slate-400"></p>
                            <button onclick="SongOrder.save()" class="px-4 py-2 bg-sky-500 text-white text-xs font-bold rounded-lg hover:bg-sky-600 transition shadow-sm"><i class="fa-solid fa-floppy-disk mr-1"></i>保存</button>
                        </div>
                    </div>
                </section>
                <section class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b <?= $cardBorder ?> flex items-center justify-between">
                        <h3 class="text-[10px] font-black text-slate-400 tracking-wider"><i class="fa-solid fa-headphones text-violet-400 mr-1"></i>ストリーミングURL 一括編集</h3>
                        <button onclick="StreamingBulk.toggle()" id="streamingBulkToggle" class="text-[10px] font-bold text-violet-500 hover:text-violet-700 transition">
                            <i class="fa-solid fa-pen-to-square mr-0.5"></i>開く
                        </button>
                    </div>
                    <div id="streamingBulkBody" class="hidden">
                        <div class="px-5 py-3 space-y-4">
                            <?php foreach ($release['songs'] as $i => $s): ?>
                            <div class="border border-slate-100 rounded-xl p-3 space-y-2" data-song-id="<?= (int)$s['id'] ?>">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400 text-[10px] font-mono w-5"><?= (int)($s['track_number'] ?? 0) ?></span>
                                    <p class="text-sm font-bold text-slate-700 flex-1 truncate"><?= htmlspecialchars($s['title']) ?></p>
                                    <?php if (!empty($s['apple_music_url']) && !empty($s['spotify_url'])): ?>
                                    <span class="text-[8px] font-bold text-emerald-500 bg-emerald-50 px-1.5 py-0.5 rounded-full">登録済</span>
                                    <?php elseif (!empty($s['apple_music_url']) || !empty($s['spotify_url'])): ?>
                                    <span class="text-[8px] font-bold text-amber-500 bg-amber-50 px-1.5 py-0.5 rounded-full">一部</span>
                                    <?php else: ?>
                                    <span class="text-[8px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-full">未登録</span>
                                    <?php endif; ?>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 flex items-center gap-1 mb-0.5"><i class="fa-brands fa-apple text-pink-400"></i>Apple Music</label>
                                        <input type="url" class="streaming-input apple w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] focus:outline-none focus:ring-2 focus:ring-violet-200 transition" placeholder="https://music.apple.com/jp/album/..." value="<?= htmlspecialchars($s['apple_music_url'] ?? '') ?>" data-song-id="<?= (int)$s['id'] ?>" data-type="apple">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 flex items-center gap-1 mb-0.5"><i class="fa-brands fa-spotify text-emerald-400"></i>Spotify</label>
                                        <input type="url" class="streaming-input spotify w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] focus:outline-none focus:ring-2 focus:ring-violet-200 transition" placeholder="https://open.spotify.com/track/..." value="<?= htmlspecialchars($s['spotify_url'] ?? '') ?>" data-song-id="<?= (int)$s['id'] ?>" data-type="spotify">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="px-5 py-3 border-t <?= $cardBorder ?> flex items-center justify-between bg-slate-50/50">
                            <p id="streamingBulkStatus" class="text-[10px] text-slate-400"></p>
                            <button onclick="StreamingBulk.save()" class="px-4 py-2 bg-violet-500 text-white text-xs font-bold rounded-lg hover:bg-violet-600 transition shadow-sm"><i class="fa-solid fa-floppy-disk mr-1"></i>一括保存</button>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <p class="text-center"><a href="/hinata/songs.php" class="text-sm font-bold <?= $cardIconText ?>"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>><i class="fa-solid fa-arrow-left mr-1"></i>リリース一覧へ戻る</a></p>
            </div>
        </div>
    </main>

    <script src="/assets/js/core.js?v=3"></script>
    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('mobile-open');
        });

        var SongOrder = {
            open: false,
            toggle: function() {
                this.open = !this.open;
                var body = document.getElementById('songOrderBody');
                var btn = document.getElementById('songOrderToggle');
                if (this.open) {
                    body.classList.remove('hidden');
                    btn.innerHTML = '<i class="fa-solid fa-chevron-up mr-0.5"></i>閉じる';
                } else {
                    body.classList.add('hidden');
                    btn.innerHTML = '<i class="fa-solid fa-pen-to-square mr-0.5"></i>開く';
                }
            },
            save: function() {
                var songs = [];
                document.querySelectorAll('.song-order-input').forEach(function(input) {
                    var songId = parseInt(input.getAttribute('data-song-id'));
                    var val = input.value.trim();
                    var trackNumber = val !== '' ? parseInt(val, 10) : null;
                    if (songId) {
                        songs.push({ song_id: songId, track_number: trackNumber > 0 ? trackNumber : null });
                    }
                });
                if (songs.length === 0) return;
                var statusEl = document.getElementById('songOrderStatus');
                statusEl.textContent = '保存中...';
                statusEl.className = 'text-[10px] text-sky-500';
                App.post('/hinata/api/save_release_song_order.php', {
                    release_id: <?= (int)$release['id'] ?>,
                    songs: songs
                }).then(function(res) {
                    if (res.status === 'success') {
                        statusEl.textContent = res.message;
                        statusEl.className = 'text-[10px] text-emerald-500 font-bold';
                        location.reload();
                    } else {
                        statusEl.textContent = res.message || 'エラーが発生しました';
                        statusEl.className = 'text-[10px] text-red-500 font-bold';
                    }
                });
            }
        };

        var StreamingBulk = {
            open: false,
            toggle: function() {
                this.open = !this.open;
                var body = document.getElementById('streamingBulkBody');
                var btn = document.getElementById('streamingBulkToggle');
                if (this.open) {
                    body.classList.remove('hidden');
                    btn.innerHTML = '<i class="fa-solid fa-chevron-up mr-0.5"></i>閉じる';
                } else {
                    body.classList.add('hidden');
                    btn.innerHTML = '<i class="fa-solid fa-pen-to-square mr-0.5"></i>開く';
                }
            },
            save: function() {
                var songs = [];
                var cards = document.querySelectorAll('#streamingBulkBody [data-song-id]');
                var seen = {};
                cards.forEach(function(card) {
                    if (card.tagName === 'INPUT') return;
                    var songId = parseInt(card.getAttribute('data-song-id'));
                    if (seen[songId]) return;
                    seen[songId] = true;
                    var appleInput = card.querySelector('input[data-type="apple"]');
                    var spotifyInput = card.querySelector('input[data-type="spotify"]');
                    songs.push({
                        song_id: songId,
                        apple_music_url: appleInput ? appleInput.value.trim() : '',
                        spotify_url: spotifyInput ? spotifyInput.value.trim() : ''
                    });
                });

                if (songs.length === 0) return;
                var statusEl = document.getElementById('streamingBulkStatus');
                statusEl.textContent = '保存中...';
                statusEl.className = 'text-[10px] text-violet-500';

                App.post('/hinata/api/bulk_save_streaming.php', { songs: songs }).then(function(res) {
                    if (res.status === 'success') {
                        statusEl.textContent = res.message;
                        statusEl.className = 'text-[10px] text-emerald-500 font-bold';
                        cards.forEach(function(card) {
                            if (card.tagName === 'INPUT') return;
                            var appleVal = (card.querySelector('input[data-type="apple"]') || {}).value || '';
                            var spotifyVal = (card.querySelector('input[data-type="spotify"]') || {}).value || '';
                            var badge = card.querySelector('span[class*="rounded-full"]');
                            if (badge) {
                                if (appleVal && spotifyVal) {
                                    badge.className = 'text-[8px] font-bold text-emerald-500 bg-emerald-50 px-1.5 py-0.5 rounded-full';
                                    badge.textContent = '登録済';
                                } else if (appleVal || spotifyVal) {
                                    badge.className = 'text-[8px] font-bold text-amber-500 bg-amber-50 px-1.5 py-0.5 rounded-full';
                                    badge.textContent = '一部';
                                } else {
                                    badge.className = 'text-[8px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-full';
                                    badge.textContent = '未登録';
                                }
                            }
                        });
                    } else {
                        statusEl.textContent = res.message || 'エラーが発生しました';
                        statusEl.className = 'text-[10px] text-red-500 font-bold';
                    }
                });
            }
        };
    </script>
</body>
</html>
