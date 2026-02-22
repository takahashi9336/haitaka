<!-- 動画再生モーダル（共通コンポーネント） -->
<style>
    #videoModal { opacity: 0; }
    #videoModal.active {
        display: flex !important;
        align-items: center;
        justify-content: center;
        opacity: 1;
    }
    @keyframes vm-backdropFadeIn { 0% { opacity: 0; } 100% { opacity: 1; } }
    @keyframes vm-backdropFadeOut { 0% { opacity: 1; } 100% { opacity: 0; } }
    #videoModal.modal-opening { animation: vm-backdropFadeIn 0.65s cubic-bezier(0.25,0.46,0.45,0.94) forwards; }
    #videoModal.modal-opening .video-modal-content { animation: vm-modalExpand 0.65s cubic-bezier(0.25,0.46,0.45,0.94) forwards; }
    #videoModal.modal-closing { animation: vm-backdropFadeOut 0.3s cubic-bezier(0.55,0.09,0.68,0.53) forwards; }
    #videoModal.modal-closing .video-modal-content { animation: vm-modalShrink 0.3s cubic-bezier(0.55,0.09,0.68,0.53) forwards; }
    @keyframes vm-modalExpand {
        0% { transform: translate(var(--modal-translate-x,0),var(--modal-translate-y,0)) scale(0.3); opacity: 0; }
        100% { transform: translate(0,0) scale(1); opacity: 1; }
    }
    @keyframes vm-modalShrink {
        0% { transform: translate(0,0) scale(1); opacity: 1; }
        100% { transform: translate(var(--modal-translate-x,0),var(--modal-translate-y,0)) scale(0.3); opacity: 0; }
    }
    #videoModalEmbed { transition: transform 0.4s ease, width 0.4s ease; transform-origin: center center; }
</style>

<div id="videoModal" class="fixed inset-0 z-[100] hidden bg-slate-900/80 backdrop-blur-xl transition-all">
    <div class="w-full max-w-6xl mx-auto p-4 max-h-[100dvh] flex items-center">
        <div class="video-modal-content bg-white w-full rounded-[2rem] md:rounded-[3rem] shadow-2xl overflow-hidden relative max-h-[94dvh]">
            <button onclick="closeVideoModal()" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-10 hover:bg-white shadow-lg transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <button id="btnRotateVideo" onclick="toggleVideoRotation()" class="absolute top-4 right-20 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-10 hover:bg-white shadow-lg transition hidden" title="動画を回転">
                <i class="fa-solid fa-rotate-right text-lg"></i>
            </button>
            <div id="videoModalBody" class="p-6 md:p-8 pt-16 overflow-y-auto max-h-[94dvh]">
                <div id="videoModalEmbed" class="aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-xl">
                    <iframe id="videoModalIframe" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
                </div>
                <div id="videoModalExternal" class="hidden w-full rounded-xl bg-slate-100 flex flex-col items-center justify-center gap-4 p-8">
                    <img id="videoModalExternalThumb" src="" alt="" class="w-full max-w-md rounded-xl shadow-md hidden">
                    <i id="videoModalExternalIcon" class="fa-solid fa-external-link-alt text-4xl text-slate-400"></i>
                    <p id="videoModalExternalMsg" class="text-sm text-slate-600 text-center">この動画はモーダル内で再生できません</p>
                    <a id="videoModalExternalLink" href="#" target="_blank" class="px-6 py-3 bg-sky-500 text-white rounded-full font-bold text-sm hover:bg-sky-600 transition">
                        新しいタブで開く
                    </a>
                </div>
                <div id="videoModalInfo" class="mt-4">
                    <span id="videoModalCategory" class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-700"></span>
                    <h2 id="videoModalTitle" class="text-lg font-bold text-slate-800 mt-2"></h2>
                    <p id="videoModalDate" class="text-xs text-slate-400 mt-1"></p>
                    <p id="videoModalDescription" class="mt-3 text-xs text-slate-600 whitespace-pre-wrap max-h-40 overflow-y-auto hidden"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let _vmRotation = 0;

    window._vmGetVideoUrl = function(video) {
        if (video.platform === 'youtube') return 'https://www.youtube.com/watch?v=' + video.media_key;
        if (video.platform === 'tiktok') return 'https://www.tiktok.com/' + (video.sub_key || '@user') + '/video/' + video.media_key;
        if (video.platform === 'instagram') return 'https://www.instagram.com/reel/' + video.media_key + '/';
        return '#';
    };

    window._vmGetEmbedUrl = function(video) {
        if (video.platform === 'youtube') return 'https://www.youtube.com/embed/' + video.media_key + '?rel=0';
        if (video.platform === 'tiktok') return 'https://www.tiktok.com/player/v1/' + video.media_key + '?music_info=1&description=1&rel=0';
        if (video.platform === 'instagram') return 'https://www.instagram.com/p/' + video.media_key + '/embed/captioned/';
        return null;
    };

    window._vmGetThumbnailUrl = function(video) {
        if (video.thumbnail_url) return video.thumbnail_url;
        if (video.platform === 'youtube' && video.media_key) return 'https://img.youtube.com/vi/' + video.media_key + '/mqdefault.jpg';
        return '';
    };

    function resetLayout() {
        var body = document.getElementById('videoModalBody');
        var embedArea = document.getElementById('videoModalEmbed');
        var info = document.getElementById('videoModalInfo');
        body.className = 'p-6 md:p-8 pt-16 overflow-y-auto max-h-[94dvh]';
        embedArea.className = 'aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-xl';
        embedArea.style.cssText = '';
        info.className = 'mt-4';
        info.style.cssText = '';
    }

    window.openVideoModalWithData = function(video, ev) {
        var modal = document.getElementById('videoModal');
        var iframe = document.getElementById('videoModalIframe');
        var embedArea = document.getElementById('videoModalEmbed');
        var externalArea = document.getElementById('videoModalExternal');
        var externalLink = document.getElementById('videoModalExternalLink');
        var btnRotate = document.getElementById('btnRotateVideo');
        var body = document.getElementById('videoModalBody');
        var info = document.getElementById('videoModalInfo');

        if (ev) {
            var rect = (ev.currentTarget || ev.target).getBoundingClientRect();
            modal.style.setProperty('--modal-translate-x', (rect.left + rect.width/2 - window.innerWidth/2) + 'px');
            modal.style.setProperty('--modal-translate-y', (rect.top + rect.height/2 - window.innerHeight/2) + 'px');
        } else {
            modal.style.setProperty('--modal-translate-x', '0');
            modal.style.setProperty('--modal-translate-y', '0');
        }

        resetLayout();
        _vmRotation = 0;

        var embedUrl = _vmGetEmbedUrl(video);
        if (embedUrl) {
            embedArea.classList.remove('hidden');
            externalArea.classList.add('hidden');
            if (video.platform === 'tiktok') {
                embedArea.className = 'w-full max-w-sm mx-auto rounded-2xl overflow-hidden bg-black shadow-xl';
                embedArea.style.aspectRatio = '9 / 16';
                btnRotate.classList.remove('hidden');
            } else if (video.platform === 'instagram') {
                body.className = 'p-5 md:p-6 pt-14 overflow-hidden';
                embedArea.className = 'w-full max-w-lg mx-auto rounded-2xl overflow-hidden bg-white shadow-xl';
                embedArea.style.cssText = 'height:min(80vh, 780px)';
                info.classList.add('hidden');
                btnRotate.classList.add('hidden');
            } else {
                btnRotate.classList.add('hidden');
            }
            iframe.src = embedUrl;
        } else {
            embedArea.classList.add('hidden');
            externalArea.classList.remove('hidden');
            btnRotate.classList.add('hidden');
            externalLink.href = _vmGetVideoUrl(video);
            iframe.src = '';

            var extThumb = document.getElementById('videoModalExternalThumb');
            var extIcon = document.getElementById('videoModalExternalIcon');
            var extMsg = document.getElementById('videoModalExternalMsg');
            var thumbUrl = _vmGetThumbnailUrl(video);

            if (thumbUrl && !thumbUrl.includes('no-image')) { extThumb.src = thumbUrl; extThumb.classList.remove('hidden'); }
            else { extThumb.classList.add('hidden'); }

            if (video.platform === 'instagram') {
                extIcon.className = 'fa-brands fa-instagram text-4xl text-pink-500';
                extMsg.textContent = 'Instagramアプリで再生されます';
                externalLink.textContent = 'Instagramで開く';
                externalLink.className = 'px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full font-bold text-sm hover:opacity-90 transition';
            } else if (video.platform === 'tiktok') {
                extIcon.className = 'fa-brands fa-tiktok text-4xl text-slate-800';
                extMsg.textContent = 'TikTokアプリで再生されます';
                externalLink.textContent = 'TikTokで開く';
                externalLink.className = 'px-6 py-3 bg-slate-800 text-white rounded-full font-bold text-sm hover:bg-slate-900 transition';
            } else {
                extIcon.className = 'fa-solid fa-external-link-alt text-4xl text-slate-400';
                extMsg.textContent = 'この動画はモーダル内で再生できません';
                externalLink.textContent = '新しいタブで開く';
                externalLink.className = 'px-6 py-3 bg-sky-500 text-white rounded-full font-bold text-sm hover:bg-sky-600 transition';
            }
        }

        var catColors = {'CM':'bg-slate-100 text-slate-700','Hinareha':'bg-amber-100 text-amber-700','Live':'bg-purple-100 text-purple-700','MV':'bg-pink-100 text-pink-700','SelfIntro':'bg-cyan-100 text-cyan-700','SoloPV':'bg-blue-100 text-blue-700','Special':'bg-orange-100 text-orange-700','Teaser':'bg-emerald-100 text-emerald-700','Trailer':'bg-rose-100 text-rose-700','Variety':'bg-yellow-100 text-yellow-700'};
        var catEl = document.getElementById('videoModalCategory');
        catEl.textContent = video.category || '';
        catEl.className = 'inline-block px-2 py-0.5 rounded text-xs font-bold ' + (catColors[video.category] || 'bg-slate-100 text-slate-600');
        document.getElementById('videoModalTitle').textContent = video.title || '';
        var pd = video.upload_date || video.release_date || video.created_at || '';
        document.getElementById('videoModalDate').textContent = pd ? new Date(pd).toLocaleDateString('ja-JP') : '';
        var descEl = document.getElementById('videoModalDescription');
        var desc = (video.description || '').trim();
        if (desc && video.platform !== 'instagram') {
            descEl.textContent = desc;
            descEl.classList.remove('hidden');
            descEl.className = 'mt-3 text-xs text-slate-600 whitespace-pre-wrap max-h-40 overflow-y-auto';
        } else {
            descEl.textContent = '';
            descEl.classList.add('hidden');
        }

        modal.classList.remove('modal-closing');
        modal.classList.add('active', 'modal-opening');
        document.body.style.overflow = 'hidden';
        setTimeout(function() { modal.classList.remove('modal-opening'); }, 650);
    };

    window.closeVideoModal = function() {
        var modal = document.getElementById('videoModal');
        var iframe = document.getElementById('videoModalIframe');
        modal.classList.remove('modal-opening');
        modal.classList.add('modal-closing');
        setTimeout(function() {
            modal.classList.remove('active', 'modal-closing');
            iframe.src = '';
            resetLayout();
            _vmRotation = 0;
            document.getElementById('btnRotateVideo').classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    };

    window.toggleVideoRotation = function() {
        var embedArea = document.getElementById('videoModalEmbed');
        var containerW = embedArea.parentElement.clientWidth;
        _vmRotation = (_vmRotation + 90) % 360;
        if (_vmRotation === 90 || _vmRotation === 270) {
            var w = containerW * 9 / 16;
            embedArea.style.width = w + 'px';
            embedArea.style.maxWidth = 'none';
            embedArea.style.aspectRatio = '9 / 16';
            embedArea.style.transform = 'rotate(' + _vmRotation + 'deg)';
        } else {
            embedArea.style.transform = _vmRotation === 180 ? 'rotate(180deg)' : '';
            embedArea.style.width = '';
            embedArea.style.maxWidth = '';
            embedArea.style.aspectRatio = '9 / 16';
        }
    };

    document.getElementById('videoModal').onclick = function(e) {
        if (e.target.id === 'videoModal') closeVideoModal();
    };
})();
</script>
