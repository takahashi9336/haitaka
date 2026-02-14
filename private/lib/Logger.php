<?php

namespace Core;

class Logger {
    public static function log(string $level, string $message): void {
        try {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            if (!is_dir($logDir) || !is_writable($logDir)) {
                return;
            }

            $date = date('Y-m-d');
            $time = date('H:i:s');
            $userId = (isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'guest');
            $logFile = $logDir . '/app_' . $date . '.log';

            $entry = "[$time][$userId][$level] $message" . PHP_EOL;
            @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ログ失敗でアプリを止めない
        }
    }

    public static function error(string $msg): void { self::log('ERROR', $msg); }
    public static function info(string $msg): void { self::log('INFO', $msg); }
}