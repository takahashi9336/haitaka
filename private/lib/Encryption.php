<?php

namespace Core;

/**
 * AES-256-GCM によるフィールドレベル暗号化ユーティリティ
 */
class Encryption {
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private static ?string $key = null;

    private static function getKey(): string {
        if (self::$key === null) {
            $b64 = $_ENV['ENCRYPTION_KEY'] ?? '';
            if ($b64 === '') {
                throw new \RuntimeException('ENCRYPTION_KEY is not set in .env');
            }
            $decoded = base64_decode($b64, true);
            if ($decoded === false || strlen($decoded) !== 32) {
                throw new \RuntimeException('ENCRYPTION_KEY must be 32 bytes (base64-encoded)');
            }
            self::$key = $decoded;
        }
        return self::$key;
    }

    /**
     * 平文を暗号化し、base64(iv || tag || ciphertext) を返す
     */
    public static function encrypt(?string $plaintext): ?string {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        $key = self::getKey();
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * base64(iv || tag || ciphertext) を復号して平文を返す
     * 復号失敗時（平文データ等）はそのまま返す（移行期の互換性）
     */
    public static function decrypt(?string $encrypted): ?string {
        if ($encrypted === null || $encrypted === '') {
            return $encrypted;
        }

        $raw = base64_decode($encrypted, true);
        if ($raw === false) {
            return $encrypted;
        }

        $minLength = self::IV_LENGTH + self::TAG_LENGTH + 1;
        if (strlen($raw) < $minLength) {
            return $encrypted;
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $key = self::getKey();

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            return $encrypted;
        }

        return $plaintext;
    }

    /**
     * 値が暗号化済みかどうかを判定する（移行スクリプト用）
     */
    public static function isEncrypted(?string $value): bool {
        if ($value === null || $value === '') {
            return false;
        }

        $raw = base64_decode($value, true);
        if ($raw === false) {
            return false;
        }

        $minLength = self::IV_LENGTH + self::TAG_LENGTH + 1;
        if (strlen($raw) < $minLength) {
            return false;
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $key = self::getKey();

        return openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        ) !== false;
    }
}
