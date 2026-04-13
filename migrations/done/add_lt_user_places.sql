-- LiveTrip: ユーザー別の場所（自宅など）を保存
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

CREATE TABLE IF NOT EXISTS lt_user_places (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    place_key       VARCHAR(32) NOT NULL COMMENT 'home など',
    label           VARCHAR(255) NULL COMMENT '表示用（例: 自宅）',
    address         VARCHAR(500) NULL COMMENT '検索/表示用住所',
    place_id        VARCHAR(128) NULL COMMENT 'Google Place ID',
    latitude        DECIMAL(10, 7) NULL,
    longitude       DECIMAL(10, 7) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_place (user_id, place_key),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='ユーザー別の場所（LiveTrip）';

