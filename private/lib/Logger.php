<?php

namespace Core;

class Logger {
    public static function log(string $level, string $message): void {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $userId = $_SESSION['user']['id'] ?? 'guest';
        $logFile = "$logDir/app_$date.log";
        
        $entry = "[$time][$userId][$level] $message" . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND);
    }

    public static function error(string $msg) { self::log('ERROR', $msg); }
    public static function info(string $msg) { self::log('INFO', $msg); }
}