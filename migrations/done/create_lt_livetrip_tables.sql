-- LiveTrip: ライブ・フェス 費用・宿泊・移動管理
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

-- ============================================
-- 1. テーブル作成
-- ============================================

-- 汎用イベントマスタ（ユーザー登録のライブ・フェス）
CREATE TABLE IF NOT EXISTS lt_events (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    event_name          VARCHAR(255) NOT NULL,
    event_date          DATE NOT NULL,
    event_place         VARCHAR(255) NULL,
    event_info          TEXT NULL,
    event_place_address VARCHAR(500) NULL COMMENT 'Maps連携用住所',
    latitude            DECIMAL(10, 8) NULL,
    longitude           DECIMAL(11, 8) NULL,
    place_id            VARCHAR(255) NULL COMMENT 'Google Places ID',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='汎用イベント（ライブ・フェス）';

-- 遠征（trip 主体。1回の参加計画）
CREATE TABLE IF NOT EXISTS lt_trip_plans (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_type      VARCHAR(20) NOT NULL COMMENT 'hinata|generic',
    hn_event_id     BIGINT UNSIGNED NULL COMMENT 'hn_events.id（日向坂イベント）',
    lt_event_id     BIGINT UNSIGNED NULL COMMENT 'lt_events.id（汎用イベント）',
    seat_info       VARCHAR(255) NULL COMMENT '座席情報（アリーナ○列、天空席など）',
    impression      TEXT NULL COMMENT '感想',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_event_type (event_type),
    KEY idx_hn_event (hn_event_id),
    KEY idx_lt_event (lt_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='遠征（参加計画）';

-- trip にユーザーを紐づけ（将来の共有に備え）
CREATE TABLE IF NOT EXISTS lt_trip_members (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id    BIGINT UNSIGNED NOT NULL,
    user_id         INT NOT NULL,
    role            VARCHAR(20) NOT NULL DEFAULT 'owner' COMMENT 'owner|editor|viewer',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_trip_user (trip_plan_id, user_id),
    KEY idx_user (user_id),
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='trip参加メンバー';

-- 費用
CREATE TABLE IF NOT EXISTS lt_expenses (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id    BIGINT UNSIGNED NOT NULL,
    category        VARCHAR(30) NOT NULL COMMENT 'transport|hotel|ticket|food|goods|other',
    amount          INT NOT NULL DEFAULT 0,
    memo            TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='費用';

-- 宿泊
CREATE TABLE IF NOT EXISTS lt_hotel_stays (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id            BIGINT UNSIGNED NOT NULL,
    hotel_name              VARCHAR(255) NOT NULL,
    address                 VARCHAR(500) NULL,
    distance_from_home      VARCHAR(100) NULL COMMENT '自宅からの距離・所要時間',
    time_from_home          VARCHAR(100) NULL,
    distance_from_venue     VARCHAR(100) NULL COMMENT '会場からの距離・所要時間',
    time_from_venue         VARCHAR(100) NULL,
    check_in                DATE NULL,
    check_out               DATE NULL,
    reservation_no          VARCHAR(100) NULL,
    price                   INT NULL,
    num_guests              TINYINT UNSIGNED NULL COMMENT '何人で泊まったか',
    memo                    TEXT NULL,
    latitude                DECIMAL(10, 8) NULL,
    longitude               DECIMAL(11, 8) NULL,
    place_id                VARCHAR(255) NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='宿泊情報';

-- 移動区間
CREATE TABLE IF NOT EXISTS lt_transport_legs (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id    BIGINT UNSIGNED NOT NULL,
    direction       VARCHAR(20) NOT NULL COMMENT 'outbound|return',
    transport_type  VARCHAR(50) NULL COMMENT '新幹線、在来線、高速バスなど',
    route_memo      TEXT NULL COMMENT '東京→名古屋 のぞみ、名古屋→会場 在来線など',
    departure       VARCHAR(100) NULL COMMENT '発着駅・空港',
    arrival         VARCHAR(100) NULL,
    duration_min    INT UNSIGNED NULL COMMENT '所要時間（分）',
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='移動区間';

-- ============================================
-- 2. sys_apps に live_trip を登録
-- ============================================
-- sys_apps 登録（live_trip: Admin限定試験運用）
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'live_trip',
    '遠征管理',
    NULL,
    '/live_trip/',
    'index.php',
    'fa-plane',
    'emerald',
    'emerald-50',
    '/live_trip/',
    'ライブ・フェス 費用・宿泊・移動管理',
    0,
    35,
    1,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM sys_apps WHERE app_key = 'live_trip');
