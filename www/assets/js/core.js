/**
 * Global Core Utility
 * 物理パス: haitaka/www/assets/js/core.js
 */
const App = {
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
        clearTimeout(App._toastTimer);
        App._toastTimer = setTimeout(() => {
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
            if (!response.ok) throw new Error('Network error');
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { status: 'error', message: error.message };
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

    initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle'); // PC用最小化ボタン
        const closeBtn = document.getElementById('sidebarClose');   // モバイル用閉じるボタン
        
        // 全画面共通：三本線ボタン（IDが異なる場合に対応）
        const mobileMenuBtns = document.querySelectorAll('#mobileMenuBtn, .mobile-menu-trigger');

        if (!sidebar) return;

        // PCでの最小化状態復元 (PC環境のみ適用)
        if (window.innerWidth > 768) {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        }

        // 1. PC用：最小化トグル
        if (toggleBtn) {
            toggleBtn.onclick = (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            };
        }

        // 2. ①タスク管理画面等、全画面でのモバイル展開対応
        mobileMenuBtns.forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                sidebar.classList.add('mobile-open');
                // モバイル時は最小化状態を解除して文字が見えるようにする
                sidebar.classList.remove('collapsed');
            };
        });

        // 3. モバイル用：閉じるボタン
        if (closeBtn) {
            closeBtn.onclick = () => {
                sidebar.classList.remove('mobile-open');
                // PC用の最小化状態に戻す
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    sidebar.classList.add('collapsed');
                }
            };
        }
        
        // 4. 背景(main)をクリックした時に閉じる
        const main = document.querySelector('main');
        if (main) {
            main.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.initSidebar();
});