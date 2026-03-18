-- Sense Lab アプリ（センス強化スクラップ）
-- 1. 本番スクラップテーブル作成

CREATE TABLE IF NOT EXISTS sl_sense_entries (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(255) NOT NULL COMMENT '直感的なタイトル',
    image_path  VARCHAR(500) NOT NULL COMMENT '画像の相対パス',
    category    VARCHAR(50) NOT NULL COMMENT 'food|design|daily|other など',
    reason_1    TEXT NULL COMMENT 'なぜ良いと思ったか 1つ目',
    reason_2    TEXT NULL COMMENT 'なぜ良いと思ったか 2つ目',
    reason_3    TEXT NULL COMMENT 'なぜ良いと思ったか 3つ目',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_category (category),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='センス強化スクラップ（Sense Lab）';

-- 2. クイックスクラップテーブル作成（どこからでもメモを保存）
CREATE TABLE IF NOT EXISTS sl_sense_quick_entries (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    app_key         VARCHAR(50) NULL COMMENT '起点アプリの app_key 例: task_manager, hinata',
    page_title      VARCHAR(255) NULL COMMENT 'ページタイトル（MyPlatform除去前）',
    source_url      VARCHAR(500) NULL COMMENT 'パス＋クエリ /task_manager/?tab=... など',
    category_hint   VARCHAR(50) NULL COMMENT 'design|daily|other などのラベル候補',
    note            TEXT NOT NULL COMMENT '1〜3行程度の「なぜ良いと思ったか」のラフメモ',
    linked_entry_id BIGINT UNSIGNED NULL COMMENT '本番 sl_sense_entries.id に紐づく場合にセット',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_created (user_id, created_at),
    KEY idx_app (app_key),
    KEY idx_linked (linked_entry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Sense Lab 用クイックスクラップ（テキストのみ）';

-- 3. sys_apps に Sense Lab を登録（一般アプリだが admin_only=1 で自分専用）
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'sense_lab',
    'Sense Lab',
    NULL,
    '/sense_lab/',
    NULL,
    'fa-wand-magic-sparkles',
    'violet',
    'violet-50',
    '/sense_lab/',
    'センス強化スクラップ＆自己分析',
    0,
    40,
    1,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_apps WHERE app_key = 'sense_lab');

