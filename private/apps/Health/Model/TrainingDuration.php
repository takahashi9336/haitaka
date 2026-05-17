<?php

namespace App\Health\Model;

/**
 * トレーニング時間（秒）のパース・バリデーション
 */
final class TrainingDuration {
    public const MAX_SEC = 86400;
    public const DEFAULT_SEC = 60;

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, sec?: int, message?: string}
     */
    public static function parseFromInput(array $input, bool $required = false, ?int $default = self::DEFAULT_SEC): array {
        $hasDurationSec = array_key_exists('duration_sec', $input);
        $hasParts = array_key_exists('duration_min', $input) || array_key_exists('duration_sec_part', $input);

        if (!$hasDurationSec && !$hasParts) {
            if ($required) {
                return ['ok' => false, 'message' => '時間を指定してください'];
            }
            if ($default === null) {
                return ['ok' => true, 'sec' => null];
            }
            return ['ok' => true, 'sec' => $default];
        }

        if ($hasDurationSec && !$hasParts) {
            $sec = (int) $input['duration_sec'];
        } else {
            $min = (int) ($input['duration_min'] ?? 0);
            $part = (int) ($input['duration_sec_part'] ?? 0);
            if ($part < 0 || $part > 59) {
                return ['ok' => false, 'message' => '秒は0〜59を指定してください'];
            }
            if ($min < 0) {
                return ['ok' => false, 'message' => '分は0以上を指定してください'];
            }
            $sec = $min * 60 + $part;
        }

        if ($sec < 0 || $sec > self::MAX_SEC) {
            return ['ok' => false, 'message' => '時間は0秒〜24時間の範囲で指定してください'];
        }

        return ['ok' => true, 'sec' => $sec];
    }

    public static function formatLabel(int $sec): string {
        if ($sec <= 0) {
            return '0秒';
        }
        $m = intdiv($sec, 60);
        $s = $sec % 60;
        if ($m > 0 && $s > 0) {
            return $m . '分' . $s . '秒';
        }
        if ($m > 0) {
            return $m . '分';
        }
        return $s . '秒';
    }
}
