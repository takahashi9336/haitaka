<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * Maps API 利用量管理モデル
 * 90% 制限を超えないよう自前でカウント
 */
class MapsApiUsageModel extends BaseModel {
    protected string $table = 'lt_maps_api_usage';
    protected array $fields = ['id', 'sku', 'year_month', 'count', 'updated_at'];
    protected bool $isUserIsolated = false;

    /**
     * 現在月の利用量を取得
     */
    public function getCurrentCount(string $sku): int {
        $ym = date('Y-m');
        $sql = "SELECT `count` FROM {$this->table} WHERE sku = :sku AND `year_month` = :ym";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sku' => $sku, 'ym' => $ym]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int) $row['count'] : 0;
    }

    /**
     * 利用量を加算（行がなければ INSERT、あれば UPDATE）
     * @return bool 加算に成功したか
     */
    public function increment(string $sku, int $amount = 1): bool {
        $ym = date('Y-m');
        $sql = "INSERT INTO {$this->table} (sku, `year_month`, `count`)
                VALUES (:sku, :ym, :amt)
                ON DUPLICATE KEY UPDATE `count` = `count` + :amt2";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'sku' => $sku,
            'ym' => $ym,
            'amt' => $amount,
            'amt2' => $amount,
        ]);
    }

    /**
     * 制限内なら加算して true、超過なら false
     * 将来的に API 呼び出し前に使う想定
     */
    public function canIncrement(string $sku, int $amount = 1): bool {
        $limits = $this->getLimits();
        $limit = $limits[$sku] ?? 0;
        if ($limit <= 0) return true;
        $current = $this->getCurrentCount($sku);
        return ($current + $amount) <= $limit;
    }

    /**
     * 制限内なら加算して true（API 実行可）、超過なら false
     * Geocoding / Autocomplete / Static Maps / Distance Matrix 呼び出し前に使用
     */
    public function incrementAndCheck(string $sku, int $amount = 1): bool {
        return $this->incrementIfUnderLimit($sku, $amount);
    }

    /**
     * 制限内なら加算して true、超過なら false を返す
     */
    public function incrementIfUnderLimit(string $sku, int $amount = 1): bool {
        if (!$this->canIncrement($sku, $amount)) {
            return false;
        }
        return $this->increment($sku, $amount);
    }

    private function getLimits(): array {
        $path = dirname(__DIR__, 3) . '/config/maps_api_limits.php';
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

    /**
     * 指定 SKU の制限値を返す（監視・表示用）
     */
    public function getLimit(string $sku): int {
        $limits = $this->getLimits();
        return $limits[$sku] ?? 0;
    }
}
