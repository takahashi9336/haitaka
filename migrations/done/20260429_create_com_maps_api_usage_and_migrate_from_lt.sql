-- com_maps_api_usage: Google Maps API 利用量（共通）
-- 方針:
--  1) com_maps_api_usage を新規作成
--  2) lt_maps_api_usage からデータコピー
-- 注意:
--  - 既存 lt_maps_api_usage は残します（検証は別SQLでリネーム）

CREATE TABLE IF NOT EXISTS com_maps_api_usage (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sku             VARCHAR(50) NOT NULL COMMENT 'geocoding, autocomplete, static_maps, distance_matrix, directions 等',
    `year_month`    CHAR(7) NOT NULL COMMENT 'YYYY-MM',
    `count`         INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sku_month (sku, `year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Maps API利用量（月次・SKU別、共通）';

INSERT INTO com_maps_api_usage (id, sku, `year_month`, `count`, updated_at)
SELECT id, sku, `year_month`, `count`, updated_at
FROM lt_maps_api_usage
ON DUPLICATE KEY UPDATE
  `count` = VALUES(`count`),
  updated_at = VALUES(updated_at);

