<?php
/**
 * セッションの apps ツリーから app_key に一致するアプリのテーマを取得する
 * 使用例: $appKey = 'hinata'; include __DIR__ . '/theme_from_session.php';
 * 設定される変数: $themePrimary, $themeLight, $isThemeHex, $themeTailwind,
 *   $bodyBgClass, $bodyStyle, $headerIconBg, $headerBorder, $headerShadow,
 *   $cardIconBg, $cardIconText, $cardIconHover, $cardDeco, $cardBorder
 *   (hex の場合は _Style 版も: $headerIconStyle, $cardIconStyle 等)
 */

if (!defined('THEME_ALLOWED_TW')) {
    define('THEME_ALLOWED_TW', [
        'indigo' => 1, 'sky' => 1, 'slate' => 1,
        'amber' => 1, 'orange' => 1, 'violet' => 1, 'emerald' => 1,
    ]);
    define('THEME_TW_TO_HEX', [
        'indigo' => '#6366f1', 'sky' => '#0ea5e9', 'slate' => '#64748b',
        'amber' => '#f59e0b', 'orange' => '#ea580c', 'violet' => '#8b5cf6',
        'emerald' => '#10b981',
    ]);
}

/**
 * セッション apps ツリーから app_key に一致するアプリを再帰検索する
 */
function findAppInSessionTree(string $appKey): ?array {
    $apps = $_SESSION['user']['apps'] ?? [];
    $search = function ($list) use ($appKey, &$search) {
        foreach ($list as $a) {
            if (isset($a['app_key']) && $a['app_key'] === $appKey) {
                return $a;
            }
            if (!empty($a['children'])) {
                $r = $search($a['children']);
                if ($r) return $r;
            }
        }
        return null;
    };
    return $search($apps);
}

/**
 * Tailwind カラー名を解決する（hex の場合は null を返す）
 */
function resolveThemeTailwind(string $primary): ?string {
    if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $primary)) {
        return null;
    }
    if (preg_match('/^([a-z]+)/', $primary, $m) && isset(THEME_ALLOWED_TW[$m[1]])) {
        return $m[1];
    }
    return 'indigo';
}

/**
 * 指定 app_key のテーマ変数を配列で返す
 *
 * @return array 全テーマ変数を含む連想配列
 */
function getThemeVarsForApp(string $appKey): array {
    $app = findAppInSessionTree($appKey);
    $themePrimary = $app ? ($app['theme_primary'] ?? 'indigo') : 'indigo';
    $themeLight = $app ? ($app['theme_light'] ?? null) : null;
    $isThemeHex = (bool)preg_match('/^#[0-9A-Fa-f]{3,8}$/', $themePrimary);
    $themeTailwind = resolveThemeTailwind($themePrimary) ?? 'indigo';
    $themePrimaryHex = $isThemeHex ? $themePrimary : (THEME_TW_TO_HEX[$themeTailwind] ?? '#6366f1');

    if ($isThemeHex) {
        $bodyBgClass = '';
        $bodyStyle = 'background-color: ' . (function () use ($themeLight, $themePrimary) {
            if ($themeLight && preg_match('/^#[0-9A-Fa-f]{6}$/i', $themeLight)) {
                return $themeLight;
            }
            if (preg_match('/^#([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})$/i', $themePrimary, $hex)) {
                $r = hexdec($hex[1]); $g = hexdec($hex[2]); $b = hexdec($hex[3]);
                return "rgba($r,$g,$b,0.12)";
            }
            return '#f8fafc';
        })() . ';';
        $headerIconBg = '';
        $headerIconStyle = 'background-color: ' . $themePrimary . '; color: #fff;';
        $headerBorder = 'border-slate-100';
        $headerShadow = 'shadow-slate-200';
        $cardIconBg = $cardIconText = $cardIconHover = $cardDeco = '';
        $cardBorder = 'border-slate-100';
        $cardIconStyle = 'background-color: ' . ($themeLight ?: 'rgba(100,116,139,0.15)') . '; color: ' . $themePrimary . ';';
        $cardIconHoverStyle = 'background-color: ' . $themePrimary . '; color: #fff;';
        $cardDecoStyle = 'color: ' . $themePrimary . ';';
        $btnBgClass = '';
        $btnBgStyle = 'background-color: ' . $themePrimary . ';';
        $tabActiveClass = '';
        $tabActiveStyle = 'color: ' . $themePrimary . '; border-bottom-color: ' . $themePrimary . ';';
    } else {
        $bodyBgClass = "bg-{$themeTailwind}-50";
        $bodyStyle = '';
        $headerIconBg = "bg-{$themeTailwind}-500";
        $headerIconStyle = '';
        $headerBorder = "border-{$themeTailwind}-100";
        $headerShadow = "shadow-{$themeTailwind}-200";
        $cardIconBg = "bg-{$themeTailwind}-50";
        $cardIconText = "text-{$themeTailwind}-500";
        $cardIconHover = "group-hover:bg-{$themeTailwind}-500 group-hover:text-white";
        $cardDeco = "text-{$themeTailwind}-500";
        $cardBorder = "border-{$themeTailwind}-100";
        $cardIconStyle = $cardIconHoverStyle = $cardDecoStyle = '';
        $btnBgClass = "bg-{$themeTailwind}-500 hover:bg-{$themeTailwind}-600";
        $btnBgStyle = '';
        $tabActiveClass = "text-{$themeTailwind}-600 border-{$themeTailwind}-500";
        $tabActiveStyle = '';
    }

    return compact(
        'themePrimary', 'themeLight', 'isThemeHex', 'themeTailwind', 'themePrimaryHex',
        'bodyBgClass', 'bodyStyle',
        'headerIconBg', 'headerIconStyle', 'headerBorder', 'headerShadow',
        'cardIconBg', 'cardIconText', 'cardIconHover', 'cardIconStyle',
        'cardIconHoverStyle', 'cardDeco', 'cardDecoStyle', 'cardBorder',
        'btnBgClass', 'btnBgStyle',
        'tabActiveClass', 'tabActiveStyle'
    );
}

extract(getThemeVarsForApp($appKey ?? 'indigo'));
