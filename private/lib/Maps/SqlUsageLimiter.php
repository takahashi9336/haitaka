<?php

namespace Core\Maps;

/**
 * com_maps_api_usage に月次でカウントする利用量リミッタ
 * - 無料枠の90%制限は private/config/maps_api_limits.php に従う
 */
class SqlUsageLimiter implements UsageLimiterInterface {
    private function getLimits(): array {
        $path = dirname(__DIR__, 2) . '/config/maps_api_limits.php';
        if (!is_file($path)) {
            return [
                'geocoding' => 9000,
                'autocomplete' => 9000,
                'static_maps' => 9000,
                'distance_matrix' => 9000,
                'directions' => 9000,
            ];
        }
        $cfg = require $path;
        return $cfg['limits'] ?? [];
    }

    private function getCurrentCount(\PDO $pdo, string $sku, string $ym): int {
        $stmt = $pdo->prepare("SELECT `count` FROM com_maps_api_usage WHERE sku = :sku AND `year_month` = :ym");
        $stmt->execute(['sku' => $sku, 'ym' => $ym]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['count'] : 0;
    }

    private function increment(\PDO $pdo, string $sku, string $ym, int $amount): bool {
        $sql = "INSERT INTO com_maps_api_usage (sku, `year_month`, `count`)
                VALUES (:sku, :ym, :amt)
                ON DUPLICATE KEY UPDATE `count` = `count` + :amt2";
        return $pdo->prepare($sql)->execute([
            'sku' => $sku,
            'ym' => $ym,
            'amt' => $amount,
            'amt2' => $amount,
        ]);
    }

    public function incrementAndCheck(string $sku, int $amount = 1): bool {
        $sku = trim($sku);
        if ($sku === '') return false;
        $amount = max(1, (int)$amount);

        try {
            $pdo = \Core\Database::connect();
            $ym = date('Y-m');
            $limits = $this->getLimits();
            $limit = (int)($limits[$sku] ?? 0);
            if ($limit > 0) {
                $current = $this->getCurrentCount($pdo, $sku, $ym);
                if (($current + $amount) > $limit) {
                    return false;
                }
            }
            return $this->increment($pdo, $sku, $ym, $amount);
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Maps usage limiter failed', $e);
            return false;
        }
    }
}

