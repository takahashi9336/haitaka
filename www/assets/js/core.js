/**
 * Global Core Utility
 * 物理パス: haitaka/www/assets/js/core.js
 * 冪等: 複数回読み込まれてもエラーにならない
 */
(function() {
    if (window.App) return;
    window.App = {
    /**
     * サイト全体で利用可能なトースト通知
     * 使用例: App.toast('コピーしました。');
     * @param {string} message - 表示するメッセージ
     * @param {number} [duration=2500] - 表示時間（ミリ秒）
     */
    toast(message, duration) {
        let el = document.getElementById('app-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'app-toast';
            el.setAttribute('aria-live', 'polite');
            Object.assign(el.style, {
                position: 'fixed',
                top: '1.5rem',
                left: '50%',
                transform: 'translateX(-50%)',
                zIndex: '99999',
                padding: '0.75rem 1.25rem',
                borderRadius: '0.75rem',
                background: '#fff',
                color: '#334155',
                fontSize: '0.875rem',
                fontWeight: '500',
                boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)',
                border: '1px solid #f1f5f9',
                pointerEvents: 'none',
                transition: 'opacity 0.3s',
                opacity: '0'
            });
            document.body.appendChild(el);
        }
        el.textContent = message;
        el.style.opacity = '1';
        clearTimeout(window.App._toastTimer);
        window.App._toastTimer = setTimeout(() => {
            el.style.opacity = '0';
        }, duration ?? 2500);
    },

    async post(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const text = await response.text();
            let json = null;
            try {
                json = text ? JSON.parse(text) : null;
            } catch (_) {
                json = null;
            }
            if (!response.ok) {
                const msg =
                    (json && typeof json.message === 'string' && json.message !== '')
                        ? json.message
                        : (json && typeof json.error === 'string' && json.error !== '')
                            ? json.error
                            : `HTTP ${response.status}`;
                return {
                    status: 'error',
                    message: msg,
                    http_status: response.status,
                };
            }
            if (json && typeof json === 'object') {
                return json;
            }
            return { status: 'error', message: 'サーバの応答が不正です' };
        } catch (error) {
            console.error('API Error:', error);
            const name = error && error.name;
            const m = error && error.message ? String(error.message) : '';
            const msg =
                name === 'TypeError' && (m.includes('fetch') || m.includes('Load failed') || m.includes('NetworkError'))
                    ? '接続できませんでした（ネットワークまたはサーバ障害）'
                    : m || '通信エラー';
            return { status: 'error', message: msg };
        }
    },

    calculateRemaining(dueDateStr) {
        if (!dueDateStr) return null;
        const due = new Date(dueDateStr);
        const today = new Date();
        today.setHours(0,0,0,0);
        const diff = due - today;
        return Math.ceil(diff / (1000 * 60 * 60 * 24));
    },

    /**
     * スクロール位置の保存・復元（サイト全体共通）
     *
     * 使い方: スクロール対象の要素に data-scroll-persist="キー名" を付与するだけ。
     *   <div class="flex-1 overflow-y-auto" data-scroll-persist="movie-list">
     */
    initScrollPersist() {
        const containers = document.querySelectorAll('[data-scroll-persist]');
        if (containers.length === 0) return;

        const pageKey = location.pathname + location.search;

        containers.forEach(el => {
            const key = `scroll:${el.dataset.scrollPersist}:${pageKey}`;
            const saved = sessionStorage.getItem(key);
            if (saved !== null) {
                requestAnimationFrame(() => {
                    el.scrollTop = parseInt(saved, 10);
                });
            }

            el.addEventListener('scroll', () => {
                clearTimeout(el._scrollSaveTimer);
                el._scrollSaveTimer = setTimeout(() => {
                    sessionStorage.setItem(key, el.scrollTop);
                }, 150);
            }, { passive: true });
        });
    },

    /**
     * 現在のURL検索パラメータを取得（他ページへのリンクに付与用）
     * 使い方: App.buildBackUrl('/movie/', ['tab', 'sort', 'order', 'view'])
     */
    buildBackUrl(basePath, paramKeys) {
        const current = new URLSearchParams(sessionStorage.getItem('app:lastListParams:' + basePath) || '');
        const params = new URLSearchParams();
        paramKeys.forEach(k => {
            const v = current.get(k);
            if (v) params.set(k, v);
        });
        const qs = params.toString();
        return qs ? basePath + '?' + qs : basePath;
    },

    /**
     * 現在ページのURLパラメータを保存（一覧→詳細遷移前に呼ぶ）
     */
    saveListParams(basePath) {
        sessionStorage.setItem('app:lastListParams:' + basePath, location.search);
    },

    goBack(fallback) {
        try {
            if (document.referrer && new URL(document.referrer).origin === location.origin) {
                history.back();
                return;
            }
        } catch (e) {}
        location.href = fallback || '/hinata/';
    },

    initSidebar() {
        // サイドバーの開閉/ブレークポイント制御は `private/components/sidebar.php` に集約。
        // ここでイベントを重ねると二重バインドになるため、core.js 側では何もしない。
        return;
    }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.App.initSidebar();
        window.App.initScrollPersist();
    });
})();