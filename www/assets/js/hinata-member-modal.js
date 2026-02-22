/**
 * メンバー詳細モーダル共通ロジック（メンバー帳・楽曲フォーメーション等で利用）
 * 使用: HinataMemberModal.init({ detailApiUrl, imgCacheBust, isAdmin }); のあと HinataMemberModal.open(memberId, event)
 *
 * レベル定義: 0=なし, 1=気になる, 7=3推し, 8=2推し, 9=最推し
 */
(function () {
    'use strict';

    var LEVEL = { NONE: 0, KINNINARU: 1, OSHI_3: 7, OSHI_2: 8, OSHI_TOP: 9 };

    var LEVEL_BTN_MAP = {};
    LEVEL_BTN_MAP[LEVEL.OSHI_TOP] = { btnId: 'favTopBtn', activeClass: 'fav-btn-active-top' };
    LEVEL_BTN_MAP[LEVEL.OSHI_2]   = { btnId: 'fav2ndBtn', activeClass: 'fav-btn-active-2nd' };
    LEVEL_BTN_MAP[LEVEL.OSHI_3]   = { btnId: 'fav3rdBtn', activeClass: 'fav-btn-active-3rd' };
    LEVEL_BTN_MAP[LEVEL.KINNINARU] = { btnId: 'favStarBtn', activeClass: 'fav-btn-active-star' };

    var config = {
        detailApiUrl: '/hinata/members.php',
        imgCacheBust: '',
        isAdmin: false
    };
    var currentMemberId = null;
    var currentFavoriteLevel = 0;
    var closeTimer = null;

    function getTextColor(hex) {
        if (!hex) return '#111827';
        var h = hex.replace('#', '');
        var r = parseInt(h.substring(0, 2), 16);
        var g = parseInt(h.substring(2, 4), 16);
        var b = parseInt(h.substring(4, 6), 16);
        return (0.299 * r + 0.587 * g + 0.114 * b) > 186 ? '#111827' : '#ffffff';
    }

    function updateFavoriteUI(level) {
        currentFavoriteLevel = level || 0;
        var allBtnIds = ['favTopBtn', 'fav2ndBtn', 'fav3rdBtn', 'favStarBtn'];
        var allActiveClasses = ['fav-btn-active-top', 'fav-btn-active-2nd', 'fav-btn-active-3rd', 'fav-btn-active-star'];

        allBtnIds.forEach(function (id, i) {
            var btn = document.getElementById(id);
            if (!btn) return;
            btn.classList.remove(allActiveClasses[i]);
            btn.classList.remove('fav-btn-inactive');
            btn.classList.add('fav-btn-inactive');
        });

        var mapping = LEVEL_BTN_MAP[currentFavoriteLevel];
        if (mapping) {
            var btn = document.getElementById(mapping.btnId);
            if (btn) {
                btn.classList.remove('fav-btn-inactive');
                btn.classList.add(mapping.activeClass);
            }
        }
    }

    function setFavoriteLevel(level) {
        if (!currentMemberId) return;
        var actualLevel = (currentFavoriteLevel === level) ? 0 : level;

        fetch('/hinata/api/toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ member_id: currentMemberId, level: actualLevel })
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (res.status === 'success') {
                var newLevel = res.level !== undefined ? res.level : actualLevel;
                updateFavoriteUI(newLevel);

                var mapping = LEVEL_BTN_MAP[newLevel];
                if (mapping) {
                    var targetBtn = document.getElementById(mapping.btnId);
                    if (targetBtn) {
                        targetBtn.classList.remove('favorite-pop');
                        void targetBtn.offsetWidth;
                        targetBtn.classList.add('favorite-pop');
                    }
                }

                if (res.swapped_member_name) {
                    showToast(res.swapped_member_name + ' の推しランクが解除されました');
                }
            } else {
                alert('お気に入りの更新に失敗しました: ' + (res.message || ''));
            }
        });
    }

    function showToast(msg) {
        var toast = document.getElementById('app-toast');
        if (toast && toast.dataset && typeof window.AppToast !== 'undefined') {
            window.AppToast.show(msg);
            return;
        }
        var el = document.createElement('div');
        el.textContent = msg;
        el.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:8px 20px;border-radius:8px;font-size:12px;font-weight:700;z-index:9999;opacity:0;transition:opacity 0.3s;';
        document.body.appendChild(el);
        requestAnimationFrame(function () { el.style.opacity = '1'; });
        setTimeout(function () {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 300);
        }, 2500);
    }

    function setLink(id, url) {
        var el = document.getElementById(id);
        if (!el) return;
        if (url) {
            el.href = url;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    function fillMemberModal(d) {
        currentMemberId = d.id;
        document.getElementById('modalName').innerText = d.name || '--';
        document.getElementById('modalGen').innerText = d.generation ? d.generation + '期生' : '--';
        document.getElementById('modalBlood').innerText = d.blood_type ? d.blood_type + '型' : '--';
        document.getElementById('modalHeight').innerHTML = d.height ? d.height + ' <span class="text-[10px] text-slate-400 font-bold">cm</span>' : '--';
        document.getElementById('modalBirth').innerText = d.birth_date ? d.birth_date.replace(/-/g, '/') : '--';
        document.getElementById('modalPlace').innerText = d.birth_place || '--';

        var mImg = document.getElementById('modalImg');
        var mThumbsWrap = document.getElementById('modalImgThumbsWrap');
        var mThumbs = document.getElementById('modalImgThumbs');
        var imgs = (d.images && d.images.length) ? d.images : (d.image_url ? [d.image_url] : []);
        var imgBase = '/assets/img/members/';
        var cache = config.imgCacheBust ? '?' + config.imgCacheBust : '';

        if (imgs.length > 0) {
            mImg.src = imgBase + imgs[0] + cache;
            mImg.classList.remove('hidden');
            var selectedIdx = 0;
            var setMainImg = function (idx) {
                mImg.src = imgBase + imgs[idx] + cache;
            };
            var updateThumbBorders = function () {
                mThumbs.querySelectorAll('button').forEach(function (b, i) {
                    var sel = i === selectedIdx;
                    b.classList.toggle('border-sky-500', sel);
                    b.classList.toggle('ring-2', sel);
                    b.classList.toggle('ring-sky-200', sel);
                    b.classList.toggle('border-slate-200', !sel);
                });
            };
            if (imgs.length >= 2 && imgs.length <= 5) {
                mThumbs.innerHTML = imgs.map(function (url, i) {
                    return '<button type="button" class="w-14 h-14 rounded-xl overflow-hidden border-2 border-slate-200 shrink-0 hover:border-sky-400 transition-all duration-300" data-idx="' + i + '"><img src="' + imgBase + url + cache + '" class="w-full h-full object-cover" alt=""></button>';
                }).join('');
                mThumbsWrap.classList.remove('hidden');
                updateThumbBorders();
                mThumbs.querySelectorAll('button').forEach(function (btn, i) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        selectedIdx = i;
                        setMainImg(selectedIdx);
                        updateThumbBorders();
                    });
                });
            } else {
                mThumbsWrap.classList.add('hidden');
                mThumbs.innerHTML = '';
            }
        } else {
            mImg.classList.add('hidden');
            mThumbsWrap.classList.add('hidden');
            mThumbs.innerHTML = '';
        }

        var modalInfoArea = document.getElementById('modalInfoArea');
        if (d.member_info) {
            document.getElementById('modalInfo').innerText = d.member_info;
            modalInfoArea.classList.remove('hidden');
        } else {
            modalInfoArea.classList.add('hidden');
        }

        var penlightSection = document.getElementById('penlightSection');
        var c1 = d.color1 || null;
        var c2 = d.color2 || null;
        var n1 = d.color1_name || '';
        var n2 = d.color2_name || '';
        if (c1 || c2) {
            penlightSection.classList.remove('hidden');
            var p1 = document.getElementById('penlight1');
            var p2 = document.getElementById('penlight2');
            if (c1) {
                p1.style.backgroundColor = c1;
                p1.style.color = getTextColor(c1);
                p1.textContent = n1 || c1;
            } else {
                p1.style.backgroundColor = '#e5e7eb';
                p1.style.color = '#111827';
                p1.textContent = '未設定';
            }
            if (c2) {
                p2.style.backgroundColor = c2;
                p2.style.color = getTextColor(c2);
                p2.textContent = n2 || c2;
            } else {
                p2.style.backgroundColor = '#e5e7eb';
                p2.style.color = '#111827';
                p2.textContent = '未設定';
            }
        } else {
            penlightSection.classList.add('hidden');
        }

        var serverLevelRaw = (d.favorite_level !== undefined && d.favorite_level !== null) ? parseInt(d.favorite_level, 10) : 0;
        var serverLevel = isNaN(serverLevelRaw) ? 0 : serverLevelRaw;
        updateFavoriteUI(serverLevel);

        var adminBtn = document.getElementById('adminEditBtn');
        if (adminBtn) {
            if (config.isAdmin && d.id) {
                adminBtn.href = '/hinata/member_admin.php?member_id=' + encodeURIComponent(d.id);
                adminBtn.classList.remove('hidden');
            } else {
                adminBtn.classList.add('hidden');
            }
        }

        setLink('blogBtn', d.blog_url);
        setLink('instaBtn', d.insta_url);

        var detailPageBtn = document.getElementById('memberDetailPageBtn');
        if (detailPageBtn) {
            var memberUrl = d.id ? '/hinata/member.php?id=' + encodeURIComponent(d.id) : '';
            detailPageBtn.setAttribute('data-url', memberUrl);
            detailPageBtn.href = memberUrl || '#';
        }

        var video = document.getElementById('modalVideo');
        var videoArea = document.getElementById('videoArea');
        var noVideoMsg = document.getElementById('noVideoMsg');
        if (d.pv_video_key) {
            video.src = 'https://www.youtube.com/embed/' + d.pv_video_key + '?rel=0';
            videoArea.classList.remove('hidden');
            noVideoMsg.classList.add('hidden');
        } else {
            video.src = '';
            videoArea.classList.add('hidden');
            noVideoMsg.classList.remove('hidden');
        }
    }

    function closeMemberModal() {
        var modal = document.getElementById('memberModal');
        if (!modal) return;
        modal.classList.remove('modal-opening');
        modal.classList.add('modal-closing');
        closeTimer = setTimeout(function () {
            closeTimer = null;
            modal.classList.remove('active', 'modal-closing');
            var v = document.getElementById('modalVideo');
            if (v) v.src = '';
            document.body.style.overflow = '';
        }, 300);
    }

    function openMemberModal(memberId, sourceEvent) {
        var url = config.detailApiUrl + (config.detailApiUrl.indexOf('?') >= 0 ? '&' : '?') + 'action=detail&id=' + encodeURIComponent(memberId);
        fetch(url).then(function (r) { return r.json(); }).then(function (res) {
            if (res.status !== 'success') return;
            var d = res.data;
            var modal = document.getElementById('memberModal');
            if (!modal) return;

            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }

            if (sourceEvent && sourceEvent.currentTarget) {
                var rect = sourceEvent.currentTarget.getBoundingClientRect();
                var clickX = rect.left + rect.width / 2;
                var clickY = rect.top + rect.height / 2;
                var viewportCenterX = window.innerWidth / 2;
                var viewportCenterY = window.innerHeight / 2;
                modal.style.setProperty('--modal-translate-x', (clickX - viewportCenterX) + 'px');
                modal.style.setProperty('--modal-translate-y', (clickY - viewportCenterY) + 'px');
            } else {
                modal.style.setProperty('--modal-translate-x', '0');
                modal.style.setProperty('--modal-translate-y', '0');
            }

            modal.classList.remove('modal-closing');
            modal.classList.add('active', 'modal-opening');
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
            setTimeout(function () { modal.classList.remove('modal-opening'); }, 650);

            fillMemberModal(d);
        });
    }

    function init(cfg) {
        if (cfg) {
            if (cfg.detailApiUrl !== undefined) config.detailApiUrl = cfg.detailApiUrl;
            if (cfg.imgCacheBust !== undefined) config.imgCacheBust = cfg.imgCacheBust;
            if (cfg.isAdmin !== undefined) config.isAdmin = !!cfg.isAdmin;
        }
        var modal = document.getElementById('memberModal');
        if (!modal) return;
        modal.addEventListener('click', function (e) {
            if (e.target.id === 'memberModal') closeMemberModal();
        });
        var closeBtn = document.getElementById('memberModalCloseBtn');
        if (closeBtn) closeBtn.addEventListener('click', closeMemberModal);

        document.addEventListener('click', function (e) {
            var detailBtn = e.target.closest('#memberDetailPageBtn');
            if (detailBtn) {
                e.preventDefault();
                var url = detailBtn.getAttribute('data-url');
                if (url) {
                    window.location.href = url;
                }
                return;
            }

            var favBtn = e.target.closest('[data-level]');
            if (favBtn && favBtn.closest('#favButtonBar')) {
                e.stopPropagation();
                setFavoriteLevel(parseInt(favBtn.dataset.level, 10));
            }
        });

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                var m = document.getElementById('memberModal');
                if (m && m.classList.contains('active')) {
                    m.classList.remove('active', 'modal-opening', 'modal-closing');
                    var v = document.getElementById('modalVideo');
                    if (v) v.src = '';
                    document.body.style.overflow = '';
                }
            }
        });
    }

    window.HinataMemberModal = {
        init: init,
        open: openMemberModal,
        close: closeMemberModal
    };
})();
