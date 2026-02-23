<?php

namespace Core;

class Logger {
    /** INFO 用（操作ログ）: app_YYYY-MM-DD.log */
    public static function log(string $level, string $message): void {
        self::writeLog($level, $message, false);
    }

    /** ERROR 用（エラーログ）: app_error_YYYY-MM-DD.log */
    public static function error(string $msg): void {
        self::writeLog('ERROR', $msg, true);
    }

    /** メッセージ + 例外のスタックトレース付き（app_error_*.log に出力） */
    public static function errorWithContext(string $msg, ?\Throwable $e = null): void {
        $full = $msg;
        if ($e !== null) {
            $full .= ' | Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            $trace = $e->getTraceAsString();
            if ($trace !== '') {
                $full .= PHP_EOL . 'Stack: ' . str_replace("\n", "\n  ", $trace);
            }
        }
        self::writeLog('ERROR', $full, true);
    }

    public static function info(string $msg): void {
        self::log('INFO', $msg);
    }

    private static function writeLog(string $level, string $message, bool $isErrorLog): void {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $userId = (isset($_SESSION['user']['id']) ? (string)$_SESSION['user']['id'] : (php_sapi_name() === 'cli' ? 'cli' : 'guest'));
        $prefix = $isErrorLog ? 'app_error_' : 'app_';
        $entry = "[$time][$userId][$level] $message" . PHP_EOL;

        $written = false;
        try {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            if (is_dir($logDir) && is_writable($logDir)) {
                $logFile = $logDir . '/' . $prefix . $date . '.log';
                $written = @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX) !== false;
            }
        } catch (\Throwable $e) {
            // ファイル書き込み失敗時は error_log にフォールバック
        }
        if (!$written && $isErrorLog) {
            error_log('[MyPlatform] ' . $entry);
        }
    }
}