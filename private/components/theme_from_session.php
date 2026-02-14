<?php
/**
 * セッションの apps ツリーから app_key に一致するアプリのテーマを取得する
 * 使用例: $appKey = 'hinata'; include __DIR__ . '/theme_from_session.php';
 * 設定される変数: $themePrimary, $themeLight, $isThemeHex, $themeTailwind,
 *   $bodyBgClass, $bodyStyle, $headerIconBg, $headerBorder, $headerShadow,
 *   $cardIconBg, $cardIconText, $cardIconHover, $cardDeco, $cardBorder
 *   (hex の場合は _Style 版も: $headerIconStyle, $cardIconStyle 等)
 */
if (!isset($appKey) || $appKey === '') {
    $appKey = 'indigo';
}

$themePrimary = 'indigo';
$themeLight = null;
$isThemeHex = false;
$themeTailwind = 'indigo';

$apps = $_SESSION['user']['apps'] ?? [];
$findApp = function ($list) use ($appKey, &$findApp) {
    foreach ($list as $a) {
        if (isset($a['app_key']) && $a['app_key'] === $appKey) {
            return $a;
        }
        if (!empty($a['children'])) {
            $r = $findApp($a['children']);
            if ($r) {
                return $r;
            }
        }
    }
    return null;
};
$app = $findApp($apps);
if ($app) {
    $themePrimary = $app['theme_primary'] ?? 'indigo';
    $themeLight = $app['theme_light'] ?? null;
}
$isThemeHex = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $themePrimary);
$allowedTw = ['indigo' => 1, 'sky' => 1, 'slate' => 1, 'amber' => 1, 'orange' => 1, 'violet' => 1, 'emerald' => 1];
if (!$isThemeHex && preg_match('/^([a-z]+)/', $themePrimary, $m)) {
    $themeTailwind = isset($allowedTw[$m[1]]) ? $m[1] : 'indigo';
}

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
// タブ・JS連携用: 常に hex を用意（Tailwind はマッピング）
$themePrimaryHex = $themePrimary;
if (!$isThemeHex) {
    $twToHex = ['indigo' => '#6366f1', 'sky' => '#0ea5e9', 'slate' => '#64748b', 'amber' => '#f59e0b', 'orange' => '#ea580c', 'violet' => '#8b5cf6', 'emerald' => '#10b981'];
    $themePrimaryHex = $twToHex[$themeTailwind] ?? '#6366f1';
}
