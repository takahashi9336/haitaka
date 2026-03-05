-- 目標・行動目標設定（MAC/WOOP/If-Then）
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

-- ============================================
-- 1. テーブル作成
-- ============================================

-- 目標（WOOP: Wish / Outcome / Obstacle / Plan / Being）
CREATE TABLE IF NOT EXISTS fn_goals (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    wish            TEXT NULL COMMENT 'WOOP: 願望',
    outcome         TEXT NULL COMMENT 'WOOP: 成果イメージ',
    obstacle        TEXT NULL COMMENT 'WOOP: 障害',
    plan            TEXT NULL COMMENT 'WOOP: 計画（If-Then等）',
    being           TEXT NULL COMMENT '抽象目的（ありたい姿）',
    is_active       TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=現在の目標',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 行動目標（MAC: content, measurement, is_process_goal）
CREATE TABLE IF NOT EXISTS fn_action_goals (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    goal_id         BIGINT UNSIGNED NOT NULL,
    user_id         INT NOT NULL,
    content         TEXT NOT NULL COMMENT '行動内容（Actionable）',
    measurement     VARCHAR(255) NULL COMMENT '測定方法（Measurable）',
    is_process_goal TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=プロセス目標',
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_goal (goal_id),
    KEY idx_user (user_id),
    FOREIGN KEY (goal_id) REFERENCES fn_goals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- If-Then ルール
CREATE TABLE IF NOT EXISTS fn_if_then_rules (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    goal_id         BIGINT UNSIGNED NOT NULL,
    user_id         INT NOT NULL,
    if_condition    TEXT NOT NULL COMMENT 'If: 条件',
    then_action     TEXT NOT NULL COMMENT 'Then: 行動',
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_goal (goal_id),
    KEY idx_user (user_id),
    FOREIGN KEY (goal_id) REFERENCES fn_goals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
