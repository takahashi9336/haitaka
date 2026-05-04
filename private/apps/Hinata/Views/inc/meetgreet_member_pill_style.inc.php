<?php

declare(strict_types=1);

/**
 * ミーグリ画面専用：メンバーカラーの表示用ユーティリティ。
 * color_code が "10,サクラピンク,#ffdbed" のような形式でも HEX を抽出して扱う。
 */
if (!function_exists('hinata_meetgreet_member_text_color')) {
    /**
     * 白背景で読める程度に、メンバーカラーを暗くして返す（背景なし・文字色用）。
     * @return string #RRGGBB
     */
    function hinata_meetgreet_member_text_color(?string $hex, string $fallback = '#334155'): string {
        $rgb = hinata_mg_pill_parse_hex($hex);
        if ($rgb === null) {
            return $fallback;
        }
        [$r, $g, $b] = $rgb;

        // 極端に淡い（ほぼ白）はフォールバックへ
        if ($r >= 252 && $g >= 252 && $b >= 252) {
            return $fallback;
        }

        // 文字として見やすい程度の暗さまで落とす（最初期の挙動に寄せる）
        $targetL = 0.28;
        if (hinata_mg_pill_relative_luminance($r, $g, $b) <= $targetL) {
            return hinata_mg_pill_to_hex($r, $g, $b);
        }

        $nr = $r;
        $ng = $g;
        $nb = $b;
        for ($i = 0; $i < 18; $i++) {
            $nr = max(0, min(255, (int)round($nr * 0.88)));
            $ng = max(0, min(255, (int)round($ng * 0.88)));
            $nb = max(0, min(255, (int)round($nb * 0.88)));
            if (hinata_mg_pill_relative_luminance($nr, $ng, $nb) <= $targetL) {
                break;
            }
        }

        // まだ薄い場合はフォールバック
        if ($nr >= 245 && $ng >= 245 && $nb >= 245) {
            return $fallback;
        }

        return hinata_mg_pill_to_hex($nr, $ng, $nb);
    }
}

/** @return array{0:int,1:int,2:int}|null */
if (!function_exists('hinata_mg_pill_parse_hex')) {
    function hinata_mg_pill_parse_hex(?string $hex): ?array {
        if ($hex === null || $hex === '') {
            return null;
        }
        // 例: "10,サクラピンク,#ffdbed" のような形式にも対応（最後の #RRGGBB を拾う）
        $raw = trim($hex);
        if (str_contains($raw, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn($v) => $v !== ''));
            for ($i = count($parts) - 1; $i >= 0; $i--) {
                $p = $parts[$i];
                if (preg_match('/#?[0-9a-fA-F]{6}$/', $p) || preg_match('/#?[0-9a-fA-F]{3}$/', $p)) {
                    $raw = $p;
                    break;
                }
            }
        }

        $h = $raw;
        if ($h[0] === '#') {
            $h = substr($h, 1);
        }
        if (strlen($h) === 3 && preg_match('/^[0-9a-fA-F]{3}$/', $h)) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $h)) {
            return null;
        }

        return [
            hexdec(substr($h, 0, 2)),
            hexdec(substr($h, 2, 2)),
            hexdec(substr($h, 4, 2)),
        ];
    }
}

if (!function_exists('hinata_mg_pill_linearize_channel')) {
    function hinata_mg_pill_linearize_channel(int $c): float {
        $s = $c / 255.0;
        if ($s <= 0.04045) {
            return $s / 12.92;
        }

        return (($s + 0.055) / 1.055) ** 2.4;
    }
}

if (!function_exists('hinata_mg_pill_relative_luminance')) {
    function hinata_mg_pill_relative_luminance(int $r, int $g, int $b): float {
        $R = hinata_mg_pill_linearize_channel($r);
        $G = hinata_mg_pill_linearize_channel($g);
        $B = hinata_mg_pill_linearize_channel($b);

        return 0.2126 * $R + 0.7152 * $G + 0.0722 * $B;
    }
}

if (!function_exists('hinata_mg_pill_to_hex')) {
    function hinata_mg_pill_to_hex(int $r, int $g, int $b): string {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }
}
