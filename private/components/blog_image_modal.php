<?php
/**
 * ブログ画像選択モーダル
 * ダウンロードアイコン押下で開き、ブログ本文中の全画像をグリッド表示。
 * ユーザーが選択した画像をダウンロード可能。
 *
 * 使用: BlogImageModal.open(articleId, title)
 */
?>
<style>
    #blogImageModal { opacity: 0; }
    #blogImageModal.active { display: flex !important; align-items: center; justify-content: center; opacity: 1; }
    @keyframes bim-backdropFadeIn { 0% { opacity: 0; } 100% { opacity: 1; } }
    @keyframes bim-backdropFadeOut { 0% { opacity: 1; } 100% { opacity: 0; } }
    #blogImageModal.modal-opening { animation: bim-backdropFadeIn 0.25s ease forwards; }
    #blogImageModal.modal-closing { animation: bim-backdropFadeOut 0.2s ease forwards; }
    .blog-image-item { cursor: pointer; transition: box-shadow 0.15s, transform 0.15s; }
    .blog-image-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); transform: scale(1.02); }
    .blog-image-item.selected { ring: 2px solid; ring-color: rgb(14 165 233); }
    .blog-image-check-icon { opacity: 0; transition: opacity 0.2s; }
    .blog-image-item.selected .blog-image-check-icon { opacity: 1; }
    .blog-image-item-mobile { cursor: pointer; }
    #blogImageZoomOverlay { opacity: 0; transition: opacity 0.2s; }
    #blogImageZoomOverlay.active { opacity: 1; }
</style>

<div id="blogImageModal" class="fixed inset-0 z-[100] hidden bg-slate-900/80 backdrop-blur-sm transition-all">
    <div class="w-full max-w-3xl md:max-w-4xl lg:max-w-5xl xl:max-w-6xl mx-4 max-h-[90vh] bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 shrink-0">
            <h3 id="blogImageModalTitle" class="text-sm font-bold text-slate-700 line-clamp-1 flex-1 mr-2"></h3>
            <button type="button" id="blogImageModalClose" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200 transition shrink-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="blogImageModalBody" class="flex-1 overflow-y-auto p-4 min-h-[200px]">
            <div id="blogImageModalLoading" class="flex items-center justify-center py-12 text-slate-400">
                <i class="fa-solid fa-spinner fa-spin text-2xl mr-2"></i>
                <span>読み込み中...</span>
            </div>
            <div id="blogImageModalEmpty" class="hidden py-12 text-center text-slate-400 text-sm">
                画像がありません
            </div>
            <div id="blogImageModalGrid" class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-2 gap-4"></div>
        </div>
        <div id="blogImageModalFooter" class="flex items-center justify-between gap-2 px-4 py-3 border-t border-slate-200 bg-slate-50 shrink-0 flex-wrap">
            <div class="flex gap-2">
                <button type="button" id="blogImageModalSelectAll" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-sky-100 text-sky-700 hover:bg-sky-200 transition hidden">全選択</button>
                <button type="button" id="blogImageModalSelectNone" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-200 text-slate-600 hover:bg-slate-300 transition hidden">選択解除</button>
            </div>
            <button type="button" id="blogImageModalDownload" class="text-xs font-bold px-4 py-2 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition disabled:opacity-50 disabled:cursor-not-allowed hidden">
                <i class="fa-solid fa-download mr-1"></i><span id="blogImageModalDownloadText">選択した画像をダウンロード</span>
            </button>
        </div>
        <p id="blogImageModalMobileHint" class="hidden px-4 py-2 text-xs text-slate-500 border-t border-slate-100 shrink-0">タップで拡大 / 長押しで保存</p>
    </div>
</div>
<div id="blogImageZoomOverlay" class="fixed inset-0 z-[110] hidden bg-black/90 flex items-center justify-center p-4" onclick="BlogImageZoom.close()">
    <img id="blogImageZoomImg" src="" alt="" class="max-w-full max-h-full object-contain" onclick="event.stopPropagation()">
    <button type="button" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/30" onclick="event.stopPropagation(); BlogImageZoom.close();">
        <i class="fa-solid fa-xmark text-xl"></i>
    </button>
</div>

<script>
(function() {
    var modal = document.getElementById('blogImageModal');
    var titleEl = document.getElementById('blogImageModalTitle');
    var body = document.getElementById('blogImageModalBody');
    var loading = document.getElementById('blogImageModalLoading');
    var empty = document.getElementById('blogImageModalEmpty');
    var grid = document.getElementById('blogImageModalGrid');
    var selectAllBtn = document.getElementById('blogImageModalSelectAll');
    var selectNoneBtn = document.getElementById('blogImageModalSelectNone');
    var downloadBtn = document.getElementById('blogImageModalDownload');
    var closeBtn = document.getElementById('blogImageModalClose');
    var footer = document.getElementById('blogImageModalFooter');
    var mobileHint = document.getElementById('blogImageModalMobileHint');

    var currentArticleId = 0;
    var currentImages = [];
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function closeModal() {
        if (!modal) return;
        BlogImageZoom.close();
        modal.classList.add('modal-closing');
        setTimeout(function() {
            modal.classList.remove('active', 'modal-closing');
            modal.classList.add('hidden');
        }, 200);
    }

    function showState(state) {
        loading.classList.add('hidden');
        empty.classList.add('hidden');
        grid.classList.add('hidden');
        selectAllBtn.classList.add('hidden');
        selectNoneBtn.classList.add('hidden');
        downloadBtn.classList.add('hidden');
        if (footer) footer.classList.add('hidden');
        if (mobileHint) mobileHint.classList.add('hidden');

        if (state === 'loading') {
            loading.classList.remove('hidden');
        } else if (state === 'empty') {
            empty.classList.remove('hidden');
        } else if (state === 'grid') {
            grid.classList.remove('hidden');
            if (currentImages.length > 0) {
                if (isMobile) {
                    if (mobileHint) mobileHint.classList.remove('hidden');
                } else {
                    if (footer) footer.classList.remove('hidden');
                    selectAllBtn.classList.remove('hidden');
                    selectNoneBtn.classList.remove('hidden');
                    downloadBtn.classList.remove('hidden');
                }
            }
        }
    }

    function updateDownloadBtn() {
        if (!grid || isMobile) return;
        var count = grid.querySelectorAll('.blog-image-check:checked').length;
        downloadBtn.disabled = count === 0;
        var textEl = document.getElementById('blogImageModalDownloadText');
        if (textEl) textEl.textContent = count > 0 ? '選択した画像をダウンロード (' + count + '枚)' : '選択した画像をダウンロード';
    }

    var zoomOverlay = document.getElementById('blogImageZoomOverlay');
    var zoomImg = document.getElementById('blogImageZoomImg');

    window.BlogImageZoom = {
        open: function(url) {
            if (!zoomOverlay || !zoomImg) return;
            zoomImg.src = url || '';
            zoomOverlay.classList.remove('hidden');
            zoomOverlay.offsetHeight;
            zoomOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        },
        close: function() {
            if (!zoomOverlay) return;
            zoomOverlay.classList.remove('active');
            setTimeout(function() {
                zoomOverlay.classList.add('hidden');
                zoomImg.src = '';
                document.body.style.overflow = '';
        }, 200);
    }
};

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') BlogImageZoom.close();
    });

    function triggerSingleDownload(url, idx) {
        var filename = 'blog_' + new Date().toISOString().slice(0, 19).replace(/[-:]/g, '').replace('T', '_') + '_' + (idx + 1) + '.jpg';
        var a = document.createElement('a');
        a.href = '/hinata/api/download_blog_image.php?url=' + encodeURIComponent(url);
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function renderGrid(images) {
        grid.innerHTML = '';
        images.forEach(function(url, idx) {
            if (isMobile) {
                var wrap = document.createElement('div');
                wrap.className = 'blog-image-item blog-image-item-mobile block rounded-lg overflow-hidden aspect-square bg-slate-100 relative';
                wrap.dataset.url = url;
                var img = document.createElement('img');
                img.src = url;
                img.alt = '';
                img.className = 'w-full h-full object-cover';
                img.loading = 'lazy';
                wrap.appendChild(img);
                wrap.addEventListener('click', function() {
                    BlogImageZoom.open(url);
                });
                grid.appendChild(wrap);
            } else {
                var wrap = document.createElement('label');
                wrap.className = 'blog-image-item block rounded-lg overflow-hidden border-2 border-transparent aspect-square bg-slate-100 cursor-pointer relative';
                wrap.dataset.url = url;
                wrap.dataset.idx = String(idx);
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'blog-image-check sr-only';
                cb.dataset.url = url;
                var img = document.createElement('img');
                img.src = url;
                img.alt = '';
                img.className = 'w-full h-full object-cover';
                img.loading = 'lazy';
                var checkIcon = document.createElement('span');
                checkIcon.className = 'blog-image-check-icon absolute top-1 right-1 w-6 h-6 rounded-full bg-sky-500 text-white flex items-center justify-center';
                checkIcon.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                var expandBtn = document.createElement('button');
                expandBtn.type = 'button';
                expandBtn.className = 'absolute bottom-1 left-1 w-7 h-7 rounded-full bg-black/50 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10';
                expandBtn.innerHTML = '<i class="fa-solid fa-expand text-xs"></i>';
                expandBtn.title = '拡大';
                expandBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    BlogImageZoom.open(url);
                });
                wrap.classList.add('group');
                wrap.appendChild(cb);
                wrap.appendChild(img);
                wrap.appendChild(checkIcon);
                wrap.appendChild(expandBtn);

                wrap.addEventListener('click', function(e) {
                    if (e.target === cb) return;
                    e.preventDefault();
                    cb.checked = !cb.checked;
                    wrap.classList.toggle('selected', cb.checked);
                    wrap.classList.toggle('ring-2', cb.checked);
                    wrap.classList.toggle('ring-sky-500', cb.checked);
                    updateDownloadBtn();
                });

                cb.addEventListener('change', function() {
                    wrap.classList.toggle('selected', cb.checked);
                    wrap.classList.toggle('ring-2', cb.checked);
                    wrap.classList.toggle('ring-sky-500', cb.checked);
                    updateDownloadBtn();
                });

                grid.appendChild(wrap);
            }
        });
        updateDownloadBtn();
    }

    window.BlogImageModal = {
        open: function(articleId, title, postId) {
            if (!modal) return;
            var param = (articleId && articleId > 0) ? ('article_id=' + encodeURIComponent(articleId)) : ('id=' + encodeURIComponent(postId || 0));
            if (!articleId && !postId) return;
            currentArticleId = articleId || postId;
            currentImages = [];
            titleEl.textContent = title || '(無題)';
            showState('loading');
            modal.classList.remove('hidden');
            modal.classList.add('active');

            fetch('/hinata/api/get_blog_images.php?' + param)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.status === 'success' && res.images && res.images.length > 0) {
                        currentImages = res.images;
                        renderGrid(res.images);
                        showState('grid');
                    } else {
                        showState('empty');
                    }
                })
                .catch(function() {
                    showState('empty');
                });
        }
    };

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            grid.querySelectorAll('.blog-image-check').forEach(function(cb) {
                cb.checked = true;
            });
            grid.querySelectorAll('.blog-image-item').forEach(function(el) {
                el.classList.add('selected', 'ring-2', 'ring-sky-500');
            });
            updateDownloadBtn();
        });
    }
    if (selectNoneBtn) {
        selectNoneBtn.addEventListener('click', function() {
            grid.querySelectorAll('.blog-image-check').forEach(function(cb) {
                cb.checked = false;
            });
            grid.querySelectorAll('.blog-image-item').forEach(function(el) {
                el.classList.remove('selected', 'ring-2', 'ring-sky-500');
            });
            updateDownloadBtn();
        });
    }
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            if (downloadBtn.disabled || isMobile) return;
            var selected = [];
            grid.querySelectorAll('.blog-image-check:checked').forEach(function(cb) {
                selected.push(cb.dataset.url);
            });
            if (selected.length === 0) return;

            closeModal();
            selected.forEach(function(url, i) {
                setTimeout(function() {
                    triggerSingleDownload(url, i);
                }, i * 400);
            });
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.blog-download-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        var articleId = parseInt(btn.getAttribute('data-article-id') || '0', 10);
        var postId = parseInt(btn.getAttribute('data-post-id') || '0', 10);
        var title = btn.getAttribute('data-title') || '(無題)';
        if (typeof BlogImageModal !== 'undefined') BlogImageModal.open(articleId, title, postId);
    });
})();
</script>
