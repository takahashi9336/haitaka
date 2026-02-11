/**
 * Global Core Utility
 * 物理パス: haitaka/www/assets/js/core.js
 */
const App = {
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