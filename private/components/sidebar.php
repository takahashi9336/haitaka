<?php
/**
 * 共通サイドバー (日本語版)
 * セッションの apps ツリー（DB 由来）からメニューを動的に描画する
 */
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, '?') !== false) {
    $uri = strstr($uri, '?', true);
}
$uri = rtrim($uri, '/') ?: '/';

$user = $_SESSION['user'] ?? ['id_name' => 'ゲスト', 'role' => ''];
$initial = mb_substr($user['id_name'] ?? '?', 0, 1);
$logoText = $user['logo_text'] ?? 'MyPlatform';
$sessionApps = $user['apps'] ?? null;
// 新しい形式（ツリー: 各要素に app_key / name がある）のみセッションメニューを使用。古い形式（app_key=>権限配列）はフォールバック
$useSessionMenu = false;
if (is_array($sessionApps) && count($sessionApps) > 0) {
    $first = reset($sessionApps);
    $useSessionMenu = is_array($first) && isset($first['app_key']) && array_key_exists('name', $first);
}

// 現在URIが指定アプリのリンクと一致するか（プレフィックス一致で判定）
$isAppActive = function (array $app) use ($uri) {
    // ダッシュボード（/ または /index.php）の特別扱い
    $appKey = $app['app_key'] ?? '';
    $defaultRoute = $app['default_route'] ?? null;
    if ($appKey === 'dashboard' || $defaultRoute === '/index.php') {
        if ($uri === '/' || $uri === '/index.php') {
            return true;
        }
    }
    $prefix = rtrim($app['route_prefix'] ?? '', '/');
    if ($prefix === '') {
        return false;
    }
    if (!empty($app['path'])) {
        $link = $prefix . '/' . ltrim($app['path'], '/');
    } else {
        $link = $app['default_route'] ?? $prefix . '/';
    }
    $link = rtrim($link, '/') ?: '/';
    return ($uri === $link || strpos($uri . '/', $link . '/') === 0);
};

// テーマ色: hex の場合は style、それ以外は Tailwind クラスでアクティブ表示（子は親の色を継承）
$themeActiveAttrs = function (array $app, ?array $parentApp = null) {
    $primary = $app['theme_primary'] ?? $parentApp['theme_primary'] ?? 'indigo';
    $light   = $app['theme_light']   ?? $parentApp['theme_light']   ?? null;
    // hex の場合はインラインスタイル
    if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $primary)) {
        $bg = $light && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $light)
            ? $light
            : (function ($hex) {
                $hex = ltrim($hex, '#');
                if (strlen($hex) === 3) {
                    $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                }
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                return sprintf('rgba(%d,%d,%d,0.15)', $r, $g, $b);
            })($primary);
        return ['class' => 'font-bold shadow-sm', 'style' => 'background-color: ' . $bg . '; color: ' . $primary . ';'];
    }
    $t = preg_match('/^([a-z]+)/', $primary, $m) && isset(THEME_ALLOWED_TW[$m[1]]) ? $m[1] : 'indigo';
    $classes = [
        'indigo'  => 'bg-indigo-50 text-indigo-700 font-bold shadow-sm shadow-indigo-100/50',
        'sky'     => 'bg-sky-50 text-sky-600 font-bold shadow-sm shadow-sky-100/50',
        'slate'   => 'bg-slate-100 text-slate-800 font-bold shadow-sm shadow-slate-100/50',
        'amber'   => 'bg-amber-50 text-amber-700 font-bold shadow-sm shadow-amber-100/50',
        'orange'  => 'bg-orange-50 text-orange-600 font-bold shadow-sm shadow-orange-100/50',
        'violet'  => 'bg-violet-50 text-violet-700 font-bold shadow-sm shadow-violet-100/50',
        'emerald' => 'bg-emerald-50 text-emerald-700 font-bold shadow-sm shadow-emerald-100/50',
    ];
    return ['class' => $classes[$t], 'style' => ''];
};

$inactiveClass = "text-slate-500 hover:bg-slate-50 transition";
?>
<aside id="sidebar" class="sidebar bg-white border-r border-slate-200 flex flex-col shrink-0 z-50 transition-all duration-300">
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
        <a href="<?= htmlspecialchars($user['default_route'] ?? '/index.php') ?>" class="flex items-center gap-2 overflow-hidden hover:opacity-80 transition cursor-pointer">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shrink-0 shadow-indigo-200 shadow-lg">
                <i class="fa-solid fa-layer-group text-sm"></i>
            </div>
            <span class="font-black text-lg text-slate-800 tracking-tighter logo-text truncate"><?= htmlspecialchars($logoText) ?></span>
        </a>
        <button id="sidebarToggle" class="hidden md:block text-slate-400 hover:text-indigo-600 p-1 transition-colors">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <button id="sidebarClose" class="md:hidden text-slate-400 p-2">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="flex-1 flex flex-col px-3 py-6 space-y-1 overflow-y-auto">
        <?php if ($useSessionMenu): ?>
            <?php foreach ($sessionApps as $app):
                $href = !empty($app['path']) ? rtrim($app['route_prefix'] ?? '', '/') . '/' . ltrim($app['path'], '/') : ($app['default_route'] ?? rtrim($app['route_prefix'] ?? '', '/') . '/');
                $active = $isAppActive($app);
                $attrs = $active ? $themeActiveAttrs($app, null) : ['class' => $inactiveClass, 'style' => ''];
                $icon = $app['icon_class'] ?? 'fa-circle';
            ?>
            <div class="space-y-1">
                <a href="<?= htmlspecialchars($href) ?>" class="nav-item flex items-center px-3 py-3 rounded-xl <?= $attrs['class'] ?>"<?= $attrs['style'] ? ' style="' . htmlspecialchars($attrs['style']) . '"' : '' ?>>
                    <i class="fa-solid <?= htmlspecialchars($icon) ?> w-6 text-center text-lg"></i>
                    <span class="nav-text ml-2 text-sm"><?= htmlspecialchars($app['name'] ?? '') ?></span>
                </a>
                <?php if (!empty($app['children'])): ?>
                <div class="pl-10 md:pl-8 space-y-1 nav-text">
                    <?php
                    foreach ($app['children'] as $child):
                        $cPath = $child['path'] ?? '';
                        $cHref = !empty($cPath)
                            ? rtrim($child['route_prefix'] ?? '', '/') . '/' . ltrim($cPath, '/')
                            : ($child['default_route'] ?? rtrim($child['route_prefix'] ?? '', '/') . '/');
                        $cActive = $isAppActive($child);
                        $cPrimary = $child['theme_primary'] ?? $app['theme_primary'] ?? 'sky';
                        if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $cPrimary)) {
                            $cStyle = $cActive ? ' color: ' . $cPrimary . ';' : '';
                            $cClass = $cActive ? 'font-bold' : 'text-slate-400';
                        } else {
                            $t = preg_match('/^([a-z]+)/', $cPrimary, $m) && isset(THEME_ALLOWED_TW[$m[1]]) ? $m[1] : 'sky';
                            $cStyle = '';
                            $cClass = $cActive ? "text-{$t}-500 font-bold" : 'text-slate-400';
                        }
                    ?>
                    <a href="<?= htmlspecialchars($cHref) ?>" class="block py-1.5 text-[11px] <?= $cClass ?> hover:opacity-80 transition"<?= $cStyle ? ' style="' . $cStyle . '"' : '' ?>><?= htmlspecialchars($child['name'] ?? '') ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-slate-100 bg-slate-50/50 shrink-0">
        <div class="flex items-center gap-3 px-1 overflow-hidden">
            <div class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold shrink-0 shadow-sm">
                <?= htmlspecialchars($initial) ?>
            </div>
            <div class="user-info text-sm truncate">
                <p class="font-bold text-slate-900 leading-none"><?= htmlspecialchars($user['id_name']) ?></p>
                <p class="text-[10px] text-slate-400 mt-1 font-bold tracking-wider">
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        管理者
                    <?php elseif (($user['role'] ?? '') === 'hinata_admin'): ?>
                        日向坂管理者
                    <?php else: ?>
                        一般ユーザー
                    <?php endif; ?>
                </p>
            </div>
            <div class="ml-auto flex items-center">
                <a href="/users_settings/" class="text-slate-400 hover:text-indigo-600 p-2" title="設定"><i class="fa-solid fa-gear"></i></a>
                <a href="/logout.php" class="text-slate-400 hover:text-red-500 p-2" title="ログアウト"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </div>
</aside>
<div id="app-toast" aria-live="polite" style="position:fixed;top:1.5rem;left:50%;transform:translateX(-50%);z-index:99999;padding:0.75rem 1.25rem;border-radius:0.75rem;background:#fff;color:#334155;font-size:0.875rem;font-weight:500;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);border:1px solid #f1f5f9;pointer-events:none;transition:opacity 0.3s;opacity:0;"></div>
<script>
(function() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    let startX = 0;
    let currentX = 0;
    let isSwiping = false;
    const swipeThreshold = 100; // スワイプと認識する最小距離（ピクセル）
    
    function getEdgeThreshold() {
        return window.innerWidth * 0.25;
    }
    
    function openSidebar() {
        sidebar.classList.add('mobile-open');
    }
    
    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
    }
    
    document.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        const isSidebarOpen = sidebar.classList.contains('mobile-open');
        if (startX <= getEdgeThreshold() || isSidebarOpen) {
            isSwiping = true;
        }
    }, { passive: true });
    
    document.addEventListener('touchmove', (e) => {
        if (!isSwiping) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });
    
    document.addEventListener('touchend', (e) => {
        if (!isSwiping) {
            startX = 0;
            currentX = 0;
            return;
        }
        const deltaX = currentX - startX;
        const isSidebarOpen = sidebar.classList.contains('mobile-open');
        if (!isSidebarOpen && startX <= getEdgeThreshold() && deltaX > swipeThreshold) {
            openSidebar();
        } else if (isSidebarOpen && deltaX < -swipeThreshold) {
            closeSidebar();
        }
        isSwiping = false;
        startX = 0;
        currentX = 0;
    }, { passive: true });
    
    const closeBtn = document.getElementById('sidebarClose');
    if (closeBtn) closeBtn.onclick = closeSidebar;
    
    document.addEventListener('click', (e) => {
        if (sidebar.classList.contains('mobile-open') && !sidebar.contains(e.target) && !e.target.closest('#mobileMenuBtn')) {
            closeSidebar();
        }
    });
})();
</script>
