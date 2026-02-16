<?php
/**
 * Favicon link tags - include in <head>
 * 日向坂46公式ロゴ風: 「ひ」マーク＝水色・紫の三角形がずらして重なる（45.95°・46に由来）
 * 公式カラー: #5BBDE3（水色） #7E1281（紫） #FFFFFF（白の隙間）
 * Data URI使用でパス・ルーティング問題を回避
 */
// 紫が下敷き、水色を右にずらして重ねた公式ロゴ「ひ」マーク
$faviconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M2 28 L16 2 L30 28 Z" fill="#7E1281"/><path d="M8 26 L18 8 L28 26 Z" fill="#5BBDE3"/></svg>';
$faviconDataUri = 'data:image/svg+xml;base64,' . base64_encode($faviconSvg);
?>
<link rel="icon" href="<?= $faviconDataUri ?>" type="image/svg+xml">
