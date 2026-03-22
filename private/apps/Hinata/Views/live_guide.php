<?php
/**
 * 初参戦ライブガイド View
 * イベントで出る可能性のある曲と紐づく動画の一覧
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';
$trackTypesDisplay = \App\Hinata\Model\SongModel::TRACK_TYPES_DISPLAY;

$likelihoodLabels = [
    'certain' => 'ほぼ確実に出る',
    'high' => '高確率で出る',
    'possible' => '出る可能性がある',
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>初参戦ライブガイド - 日向坂ポータル</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .dash-scroll { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 8px; scroll-behavior: smooth; scrollbar-width: thin; }
        .dash-scroll::-webkit-scrollbar { height: 6px; }
        .video-thumb { position: relative; flex: 0 0 100px; width: 100px; aspect-ratio: 16/9; border-radius: 6px; overflow: hidden; background: #e2e8f0; cursor: pointer; }
        .video-thumb.wide { aspect-ratio: 16/9; flex: 0 0 140px; width: 140px; }
        .video-thumb.tall { aspect-ratio: 9/16; flex: 0 0 56px; width: 56px; }
        .video-thumb.tiktok { aspect-ratio: 9/16; flex: 0 0 100px; width: 100px; }
        /* #○○の動画セクション用：大きめのサムネイル */
        .hashtag-scroll .video-thumb { flex: 0 0 180px; width: 180px; }
        .hashtag-scroll .video-thumb.tiktok { flex: 0 0 110px; width: 110px; aspect-ratio: 9/16; }
    </style>
    <style>:root { --hinata-theme: <?= htmlspecialchars($themePrimaryHex) ?>; }</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-16 bg-white border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/index.php" class="text-slate-400 p-2"><i class="fa-solid fa-chevron-left text-lg"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?> <?= $headerShadow ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-music text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">初参戦ライブガイド</h1>
            </div>
            <?php if (in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true)): ?>
            <a href="/hinata/live_guide_admin.php" class="text-xs font-bold <?= $cardIconText ?> <?= $cardIconBg ?> px-4 py-2 rounded-full hover:opacity-90 transition"<?= $cardIconStyle ? ' style="' . htmlspecialchars($cardIconStyle) . '"' : '' ?>>
                <i class="fa-solid fa-gear mr-1"></i>楽曲管理
            </a>
            <?php endif; ?>
        </header>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full space-y-6">
            <section class="bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                <label class="block text-[10px] font-black text-slate-400 mb-2 tracking-wider">ライブイベントを選択</label>
                <select id="eventSelect" class="w-full h-12 border border-slate-200 rounded-lg px-4 text-sm bg-white outline-none focus:ring-2 <?= $isThemeHex ? 'focus:ring-[var(--hinata-theme)]' : 'focus:ring-' . $themeTailwind . '-200' ?>">
                    <option value="">-- イベントを選択 --</option>
                    <?php foreach ($events as $ev): ?>
                    <option value="<?= (int)$ev['id'] ?>"<?= (isset($_GET['event_id']) && (int)$_GET['event_id'] === (int)$ev['id']) ? ' selected' : '' ?>>
                        <?= htmlspecialchars($ev['event_date']) ?> <?= htmlspecialchars($ev['event_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </section>

            <div id="guideContent" class="hidden space-y-6">
                <div id="eventHeader" class="bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                    <h2 id="eventTitle" class="text-lg font-black text-slate-800"></h2>
                    <p id="eventDatePlace" class="text-sm text-slate-500 mt-1"></p>
                    <a id="eventUrlLink" href="#" target="_blank" class="hidden mt-2 text-xs font-bold text-sky-600 hover:text-sky-700">特設サイト <i class="fa-solid fa-external-link text-[10px]"></i></a>
                </div>

                <div id="songMiniPlayer" class="hidden bg-white rounded-xl border <?= $cardBorder ?> shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2 border-b <?= $cardBorder ?> bg-slate-50/80">
                        <p id="songMiniTitle" class="text-[11px] font-bold text-slate-700 flex-1 truncate"></p>
                        <button type="button" onclick="LiveGuideSongPlayer.close()" class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 hover:bg-slate-300 flex items-center justify-center transition text-[10px]"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div id="songMiniEmbed" class="px-4 pb-3"></div>
                </div>

                <div id="songsSection" class="space-y-6"></div>

                <section id="collabSection" class="hidden bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                    <h3 class="text-sm font-black text-slate-600 mb-4">コラボ企画</h3>
                    <div id="collabUrls" class="space-y-2"></div>
                </section>

                <section id="hashtagSection" class="hidden bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                    <h3 id="hashtagSectionTitle" class="text-sm font-black text-slate-600 mb-4"></h3>
                    <div id="hashtagMediaScroll" class="dash-scroll hashtag-scroll"></div>
                </section>

                <section id="hashtagTiktokSection" class="hidden bg-white p-6 rounded-xl border <?= $cardBorder ?> shadow-sm">
                    <h3 id="hashtagTiktokSectionTitle" class="text-sm font-black text-slate-600 mb-4 flex items-center gap-2"><i class="fa-brands fa-tiktok text-slate-700"></i><span></span></h3>
                    <div id="hashtagTiktokScroll" class="dash-scroll hashtag-scroll"></div>
                </section>
            </div>

            <?php if (empty($events)): ?>
            <div class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-calendar-xmark text-4xl mb-4"></i>
                <p>直近のライブイベントがありません</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../../../components/video_modal.php'; ?>

    <script src="/assets/js/core.js?v=2"></script>
    <script>
        const likelihoodLabels = <?= json_encode($likelihoodLabels) ?>;
        const trackTypesDisplay = <?= json_encode($trackTypesDisplay) ?>;
        const eventSelect = document.getElementById('eventSelect');
        const guideContent = document.getElementById('guideContent');

        var LiveGuideSongPlayer = {
            currentUrl: null,
            playByIndex: function(ev, songIdx, type) {
                ev && ev.stopPropagation && ev.stopPropagation();
                var s = window._liveGuideSongs && window._liveGuideSongs[songIdx];
                if (!s) return;
                var url = type === 'apple' ? (s.apple_music_url || '') : (s.spotify_url || '');
                if (url) this.play(type, url, s.song_title || '');
            },
            play: function(type, url, title) {
                if (!url) return;
                var player = document.getElementById('songMiniPlayer');
                var embed = document.getElementById('songMiniEmbed');
                var titleEl = document.getElementById('songMiniTitle');
                if (!player || !embed) return;
                if (this.currentUrl === url) { this.close(); return; }
                this.currentUrl = url;
                titleEl.textContent = title || '';
                var embedUrl = type === 'apple'
                    ? (url + '').replace('music.apple.com', 'embed.music.apple.com')
                    : (url + '').replace('open.spotify.com/', 'open.spotify.com/embed/');
                var iframe = '<iframe src="' + embedUrl.replace(/"/g, '&quot;') + '" height="' + (type === 'apple' ? 175 : 152) + '" frameborder="0" allow="autoplay; encrypted-media; fullscreen" sandbox="allow-forms allow-popups allow-same-origin allow-scripts" style="width:100%;border-radius:12px;"></iframe>';
                embed.innerHTML = iframe;
                player.classList.remove('hidden');
                player.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            },
            close: function() {
                var player = document.getElementById('songMiniPlayer');
                var embed = document.getElementById('songMiniEmbed');
                if (player) player.classList.add('hidden');
                if (embed) embed.innerHTML = '';
                this.currentUrl = null;
            }
        };

        function getThumbUrl(v) {
            if (v.thumbnail_url) return v.thumbnail_url;
            if (v.platform === 'youtube' && v.media_key) return 'https://img.youtube.com/vi/' + v.media_key + '/mqdefault.jpg';
            return '';
        }

        function openVideo(idx, ev) {
            ev && ev.stopPropagation && ev.stopPropagation();
            var v = window._liveGuideVideos && window._liveGuideVideos[idx];
            if (!v) return;
            if (typeof openVideoModalWithData === 'function') {
                openVideoModalWithData(v, ev);
            } else if (v.platform === 'youtube' && v.media_key) {
                window.open('https://www.youtube.com/watch?v=' + v.media_key, '_blank');
            } else if (v.platform === 'tiktok' && v.media_key) {
                window.open('https://www.tiktok.com/' + (v.sub_key || '@user') + '/video/' + v.media_key, '_blank');
            } else if (v.platform === 'instagram' && v.media_key) {
                window.open('https://www.instagram.com/reel/' + v.media_key + '/', '_blank');
            }
        }

        async function loadGuide() {
            const eventId = parseInt(eventSelect.value, 10) || 0;
            guideContent.classList.toggle('hidden', !eventId);
            if (!eventId) return;
            try {
                const res = await fetch('/hinata/api/get_live_guide.php?event_id=' + eventId);
                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || '取得失敗');
                renderGuide(json.data);
            } catch (e) {
                alert('読み込みエラー: ' + e.message);
            }
        }

        function renderGuide(data) {
            window._liveGuideVideos = [];
            window._liveGuideSongs = [];
            const event = data.event || {};
            document.getElementById('eventTitle').textContent = event.event_name || '';
            document.getElementById('eventDatePlace').textContent = [event.event_date, event.event_place].filter(Boolean).join(' ') || '';
            const urlLink = document.getElementById('eventUrlLink');
            if (event.event_url) {
                urlLink.href = event.event_url;
                urlLink.classList.remove('hidden');
            } else {
                urlLink.classList.add('hidden');
            }

            const songsSection = document.getElementById('songsSection');
            const cardBorder = document.body.classList.contains('dark') ? 'border-slate-600' : 'border-slate-100';
            const eventId = (event && event.id) ? event.id : 0;
            let songIndex = 0;
            let html = '';
            ['certain', 'high', 'possible'].forEach(function(lik) {
                const songs = (data.songs_by_likelihood && data.songs_by_likelihood[lik]) || [];
                if (songs.length === 0) return;
                html += '<section class="bg-white rounded-2xl border ' + cardBorder + ' shadow-sm overflow-hidden">';
                html += '<div class="px-5 py-3 border-b ' + cardBorder + '">';
                html += '<h3 class="text-[10px] font-black text-slate-400 tracking-wider">' + (likelihoodLabels[lik] || lik) + '（' + songs.length + '曲）</h3>';
                html += '</div>';
                html += '<ul class="divide-y ' + cardBorder + '">';
                songs.forEach(function(s, i) {
                    var songIdx = songIndex++;
                    window._liveGuideSongs.push(s);
                    const songId = parseInt(s.song_id || s.id || 0, 10);
                    const songUrl = songId ? '/hinata/song.php?id=' + songId + '&from=live_guide&event_id=' + eventId : '#';
                    const trackLabel = (s.track_type && trackTypesDisplay[s.track_type]) ? trackTypesDisplay[s.track_type] : (s.track_type || '');
                    const hasApple = !!(s.apple_music_url && String(s.apple_music_url).trim());
                    const hasSpotify = !!(s.spotify_url && String(s.spotify_url).trim());
                    const videos = s.videos || [];
                    html += '<li class="group hover:bg-slate-50 transition">';
                    html += '<div class="px-5 py-3">';
                    html += '<div class="flex items-center gap-3">';
                    html += '<span class="text-slate-400 text-xs w-6 font-mono shrink-0">' + (i + 1) + '</span>';
                    html += '<a href="' + escapeHtml(songUrl) + '" class="flex-1 min-w-0">';
                    html += '<p class="font-bold text-slate-800 break-words">' + escapeHtml(s.song_title || '') + '</p>';
                    if (trackLabel) html += '<p class="text-[10px] text-slate-500">' + escapeHtml(trackLabel) + '</p>';
                    html += '</a>';
                    html += '<div class="flex items-center gap-1 shrink-0">';
                    if (hasApple) html += '<button type="button" onclick="LiveGuideSongPlayer.playByIndex(event,' + songIdx + ',\'apple\')" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-pink-50 flex items-center justify-center transition text-slate-400 hover:text-pink-500" title="Apple Musicで再生"><i class="fa-brands fa-apple text-xs"></i></button>';
                    if (hasSpotify) html += '<button type="button" onclick="LiveGuideSongPlayer.playByIndex(event,' + songIdx + ',\'spotify\')" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-emerald-50 flex items-center justify-center transition text-slate-400 hover:text-emerald-500" title="Spotifyで再生"><i class="fa-brands fa-spotify text-xs"></i></button>';
                    html += '<a href="' + escapeHtml(songUrl) + '" class="w-8 h-8 flex items-center justify-center text-slate-300 group-hover:text-slate-500 transition shrink-0"><i class="fa-solid fa-chevron-right text-xs"></i></a>';
                    html += '</div></div>';
                    if (videos.length) {
                        html += '<div class="mt-2 ml-9 pl-0" onclick="event.stopPropagation()">';
                        html += '<div class="dash-scroll">';
                        videos.forEach(function(v) {
                            const isShort = (v.media_type === 'short' || v.platform === 'tiktok' || v.platform === 'instagram');
                            const thumbCls = isShort ? 'video-thumb tall' : 'video-thumb wide';
                            const cat = (v.category || '').toString();
                            var idx = _videoRef(v);
                            html += '<div class="' + thumbCls + ' shrink-0" onclick="openVideo(' + idx + ', event)" title="' + escapeHtml(v.title || '') + '">';
                            html += '<img src="' + escapeHtml(getThumbUrl(v)) + '" alt="" class="w-full h-full object-cover" onerror="this.style.display=\'none\'">';
                            html += '<div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-1"><span class="text-[8px] text-white font-bold">' + escapeHtml(cat) + '</span></div></div>';
                        });
                        html += '</div></div>';
                    }
                    html += '</div></li>';
                });
                html += '</ul></section>';
            });
            songsSection.innerHTML = html || '<p class="text-slate-400 text-center py-8 bg-white rounded-xl border ' + cardBorder + '">候補曲が登録されていません</p>';

            const collab = data.collaboration_urls || [];
            const collabSection = document.getElementById('collabSection');
            const collabUrls = document.getElementById('collabUrls');
            if (collab.length) {
                collabSection.classList.remove('hidden');
                collabUrls.innerHTML = collab.map(function(u) {
                    return '<a href="' + escapeHtml(u) + '" target="_blank" class="block text-sm font-bold text-sky-600 hover:text-sky-700 truncate">' + escapeHtml(u) + ' <i class="fa-solid fa-external-link text-[10px]"></i></a>';
                }).join('');
            } else {
                collabSection.classList.add('hidden');
            }

            const hashtagMedia = data.hashtag_media || [];
            const hashtagSection = document.getElementById('hashtagSection');
            const hashtagTitle = document.getElementById('hashtagSectionTitle');
            const hashtagScroll = document.getElementById('hashtagMediaScroll');
            const nonTiktokMedia = hashtagMedia.filter(function(v) { return (v.platform || '').toLowerCase() !== 'tiktok'; });
            if (event.event_hashtag) {
                hashtagSection.classList.remove('hidden');
                hashtagTitle.textContent = '#' + event.event_hashtag + ' の動画';
                if (nonTiktokMedia.length) {
                    hashtagScroll.innerHTML = nonTiktokMedia.map(function(v) {
                        const isShort = (v.media_type === 'short' || v.platform === 'instagram');
                        const thumbCls = isShort ? 'video-thumb' : 'video-thumb wide';
                        var idx = _videoRef(v);
                        return '<div class="' + thumbCls + '" onclick="openVideo(' + idx + ', event)" title="' + escapeHtml(v.title || '') + '">' +
                            '<img src="' + escapeHtml(getThumbUrl(v)) + '" alt="" class="w-full h-full object-cover" onerror="this.style.display=\'none\'">' +
                            '</div>';
                    }).join('');
                } else {
                    hashtagScroll.innerHTML = '<p class="text-sm text-slate-400 py-4">タイトルまたは説明に「' + escapeHtml(event.event_hashtag) + '」が含まれる動画がここに表示されます。</p>';
                }
            } else {
                hashtagSection.classList.add('hidden');
            }

            const hashtagTiktokMedia = hashtagMedia.filter(function(v) { return (v.platform || '').toLowerCase() === 'tiktok'; });
            const hashtagTiktokSection = document.getElementById('hashtagTiktokSection');
            const hashtagTiktokTitle = document.getElementById('hashtagTiktokSectionTitle');
            const hashtagTiktokScroll = document.getElementById('hashtagTiktokScroll');
            if (event.event_hashtag) {
                hashtagTiktokSection.classList.remove('hidden');
                var titleSpan = hashtagTiktokTitle.querySelector('span');
                if (titleSpan) titleSpan.textContent = '#' + event.event_hashtag + ' のTikTok';
                if (hashtagTiktokMedia.length) {
                    hashtagTiktokScroll.innerHTML = hashtagTiktokMedia.map(function(v) {
                        var idx = _videoRef(v);
                        return '<div class="video-thumb tiktok shrink-0" onclick="openVideo(' + idx + ', event)" title="' + escapeHtml(v.title || '') + '">' +
                            '<img src="' + escapeHtml(getThumbUrl(v)) + '" alt="" class="w-full h-full object-cover" onerror="this.style.display=\'none\'">' +
                            '</div>';
                    }).join('');
                } else {
                    hashtagTiktokScroll.innerHTML = '<p class="text-sm text-slate-400 py-4">タイトルまたは説明に「' + escapeHtml(event.event_hashtag) + '」が含まれるTikTok動画がここに表示されます。</p>';
                }
            } else {
                hashtagTiktokSection.classList.add('hidden');
            }
        }

        window._liveGuideVideos = [];
        function _videoRef(v) {
            var i = window._liveGuideVideos.length;
            window._liveGuideVideos.push(v);
            return i;
        }
        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }

        eventSelect.onchange = loadGuide;

        document.getElementById('mobileMenuBtn').onclick = () => document.getElementById('sidebar').classList.add('mobile-open');

        var urlEventId = new URLSearchParams(window.location.search).get('event_id');
        if (urlEventId && eventSelect.querySelector('option[value="' + urlEventId + '"]')) {
            eventSelect.value = urlEventId;
        }
        if (eventSelect.value) loadGuide();
    </script>
</body>
</html>
