<?php
/**
 * 共通 Bootstrap
 * - Composer autoload 読み込み
 * - グローバルエラーハンドラ・例外ハンドラ・シャットダウンハンドラ登録
 */
require_once __DIR__ . '/vendor/autoload.php';

\Core\Bootstrap::registerErrorHandlers();
