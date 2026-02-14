<?php

namespace Core\Utils;

/**
 * 文字列ユーティリティ（apps 横断で利用）
 */
class StringUtil {
    /**
     * 英数字とアンダースコアのみ残す（DB識別子・テーブル名のサニタイズ用）
     */
    public static function sanitizeIdentifier(string $value): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
    }

    /**
     * 文字列を指定長で切り詰め（マルチバイト対応）
     */
    public static function truncate(string $value, int $length = 50, string $suffix = ''): string {
        return mb_strimwidth($value, 0, $length, $suffix, 'UTF-8');
    }
}
