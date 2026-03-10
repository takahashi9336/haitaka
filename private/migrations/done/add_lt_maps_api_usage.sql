-- Google Maps API 利用量管理（90%制限用）
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

CREATE TABLE IF NOT EXISTS lt_maps_api_usage (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sku             VARCHAR(50) NOT NULL COMMENT 'geocoding, autocomplete, static_maps, distance_matrix 等',
    `year_month`    CHAR(7) NOT NULL COMMENT 'YYYY-MM',
    `count`         INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sku_month (sku, `year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Maps API利用量（月次・SKU別）';
