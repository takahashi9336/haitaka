-- Focus Note アプリ（ヤバい集中力ノート Web化）
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

-- ============================================
-- 1. テーブル作成
-- ============================================

-- マンスリーページ（月単位・報酬感覚プランニング）
-- year_month は予約語と競合するため ym に変更
CREATE TABLE IF NOT EXISTS fn_monthly_pages (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    ym              DATE NOT NULL COMMENT '月の初日 例: 2026-02-01',
    target          TEXT NULL COMMENT '1. ターゲット',
    importance_check TEXT NULL COMMENT '2. 重要度チェック',
    concrete_imaging TEXT NULL COMMENT '3. 具象イメージング',
    reverse_planning TEXT NULL COMMENT '4. リバースプランニング',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_ym (user_id, ym),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- デイリータスク（マンスリーの5番で登録する複数タスク）
CREATE TABLE IF NOT EXISTS fn_daily_tasks (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    monthly_page_id BIGINT UNSIGNED NOT NULL,
    content         TEXT NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_monthly (monthly_page_id),
    KEY idx_user (user_id),
    FOREIGN KEY (monthly_page_id) REFERENCES fn_monthly_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ウィークリーページ（週単位、月曜起点）
CREATE TABLE IF NOT EXISTS fn_weekly_pages (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    week_start        DATE NOT NULL COMMENT '週の月曜日 例: 2026-02-17',
    obstacle_contrast TEXT NULL COMMENT '2. 障害コントラスト',
    obstacle_fix      TEXT NULL COMMENT '3. 障害フィックス',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_week (user_id, week_start),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ウィークリーで選んだタスク（デイリータスクの参照）
CREATE TABLE IF NOT EXISTS fn_weekly_task_picks (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    weekly_page_id  BIGINT UNSIGNED NOT NULL,
    daily_task_id   BIGINT UNSIGNED NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_weekly (weekly_page_id),
    KEY idx_user (user_id),
    FOREIGN KEY (weekly_page_id) REFERENCES fn_weekly_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (daily_task_id) REFERENCES fn_daily_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 質問型アクション（タスク＋時間＋場所＋完了・所要時間）
CREATE TABLE IF NOT EXISTS fn_question_actions (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    weekly_task_pick_id BIGINT UNSIGNED NOT NULL,
    scheduled_time      VARCHAR(50) NULL COMMENT '例: 9:00',
    place               VARCHAR(100) NULL COMMENT '例: 自宅デスク',
    question_text       TEXT NULL COMMENT '[名前]は、[時間]に[場所]で[タスク]をするか？',
    done                TINYINT(1) NOT NULL DEFAULT 0,
    actual_duration_min INT UNSIGNED NULL COMMENT '所要分数',
    done_at             DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_weekly_pick (weekly_task_pick_id),
    KEY idx_user (user_id),
    KEY idx_done (done),
    FOREIGN KEY (weekly_task_pick_id) REFERENCES fn_weekly_task_picks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 2. sys_apps に focus_note を登録
-- ============================================
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'focus_note',
    'Focus Note',
    NULL,
    '/focus_note/',
    NULL,
    'fa-bolt',
    'emerald',
    'emerald-50',
    '/focus_note/',
    '集中力ノート（マンスリー・ウィークリー・報酬感覚プランニング）',
    0,
    25,
    1,
    0,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_apps WHERE app_key = 'focus_note');
