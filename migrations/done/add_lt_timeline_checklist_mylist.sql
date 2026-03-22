-- LiveTrip: タイムライン・チェックリスト・持ち物マイリスト
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

-- ============================================
-- 1. タイムライン（当日スケジュール）
-- ============================================
CREATE TABLE IF NOT EXISTS lt_timeline_items (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id    BIGINT UNSIGNED NOT NULL,
    label           VARCHAR(100) NOT NULL COMMENT '開場、開演、チェックアウトなど',
    scheduled_time  VARCHAR(50) NULL COMMENT '例: 18:00, 18:30',
    memo            VARCHAR(255) NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='当日タイムライン';

-- ============================================
-- 2. チェックリスト（遠征ごと）
-- ============================================
CREATE TABLE IF NOT EXISTS lt_checklist_items (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id    BIGINT UNSIGNED NOT NULL,
    item_name       VARCHAR(255) NOT NULL COMMENT 'チケット確認、予約確認、財布など',
    checked         TINYINT(1) NOT NULL DEFAULT 0,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='チェックリスト';

-- ============================================
-- 3. 持ち物マイリスト（テンプレート・ユーザー単位）
-- ============================================
CREATE TABLE IF NOT EXISTS lt_my_lists (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    list_name       VARCHAR(255) NOT NULL COMMENT '例: 遠征基本セット',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='持ち物テンプレート';

-- ============================================
-- 4. マイリストの項目
-- ============================================
CREATE TABLE IF NOT EXISTS lt_my_list_items (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    my_list_id      BIGINT UNSIGNED NOT NULL,
    item_name       VARCHAR(255) NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_list (my_list_id),
    FOREIGN KEY (my_list_id) REFERENCES lt_my_lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='マイリスト項目';
