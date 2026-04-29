-- com_user_places: ユーザー別の場所（自宅など）を共通テーブルへ移行
-- 方針:
--  1) com_user_places を新規作成
--  2) lt_user_places からデータコピー（既存暗号化データはそのままコピー）
--
-- 注意:
--  - 既存の lt_user_places は残します（ロールバック/比較用）
--  - 以後アプリは com_user_places を参照する前提

CREATE TABLE IF NOT EXISTS com_user_places (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    place_key       VARCHAR(32) NOT NULL COMMENT 'home など',
    label           VARCHAR(255) NULL COMMENT '表示用（例: 自宅）',
    address         VARCHAR(500) NULL COMMENT '検索/表示用住所（暗号化される場合あり）',
    place_id        VARCHAR(128) NULL COMMENT 'Google Place ID（暗号化される場合あり）',
    latitude        DECIMAL(10, 7) NULL,
    longitude       DECIMAL(10, 7) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_place (user_id, place_key),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='ユーザー別の場所（共通）';

-- データ移行（重複があれば上書き）
INSERT INTO com_user_places (
    id, user_id, place_key, label, address, place_id, latitude, longitude, created_at, updated_at
)
SELECT
    id, user_id, place_key, label, address, place_id, latitude, longitude, created_at, updated_at
FROM lt_user_places
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    address = VALUES(address),
    place_id = VALUES(place_id),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    updated_at = VALUES(updated_at);

