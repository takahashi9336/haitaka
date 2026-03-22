-- 遠征の目的地（コラボ店・観光・その他）
CREATE TABLE IF NOT EXISTS lt_destinations (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id        BIGINT UNSIGNED NOT NULL,
    name                VARCHAR(255) NOT NULL COMMENT '目的地名',
    destination_type    VARCHAR(30) NOT NULL COMMENT 'collab|sightseeing|other',
    address             VARCHAR(500) NULL,
    visit_date          DATE NULL,
    visit_time          VARCHAR(20) NULL COMMENT '目安時刻',
    memo                TEXT NULL,
    latitude            DECIMAL(10, 8) NULL,
    longitude           DECIMAL(11, 8) NULL,
    place_id            VARCHAR(255) NULL,
    sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='遠征の目的地（コラボ店・観光など）';
