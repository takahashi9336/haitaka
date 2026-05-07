<?php
namespace Core;

/**
 * ユーザ×日付のガチャ状態（ファイル永続）
 * 端末依存（localStorage）を避け、スマホ/PCで同一状態にするための簡易ストア。
 */
class GachaState {
    private static function dir(): string {
        $dir = __DIR__ . '/../cache/gacha';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    public static function path(string $appKey, int $userId, ?string $date = null): string {
        $date = $date ?: date('Y-m-d');
        $safeApp = preg_replace('/[^a-z0-9_]+/i', '', $appKey) ?: 'app';
        return self::dir() . "/{$safeApp}_{$userId}_{$date}.json";
    }

    public static function read(string $appKey, int $userId, ?string $date = null): array {
        $path = self::path($appKey, $userId, $date);
        if (!file_exists($path)) return [];
        $raw = @file_get_contents($path);
        if ($raw === false) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function write(string $appKey, int $userId, array $state, ?string $date = null): void {
        $path = self::path($appKey, $userId, $date);
        @file_put_contents($path, json_encode($state, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}

