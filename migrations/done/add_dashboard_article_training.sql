-- ダッシュボード記事トレーニング（ほめポイント／ツッコミポイント）
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

CREATE TABLE IF NOT EXISTS dashboard_article_training (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL COMMENT 'sys_users.id',
    article_url    VARCHAR(500) NOT NULL COMMENT '対象記事のURL',
    article_title  VARCHAR(500) NOT NULL COMMENT '対象記事のタイトル',
    praise_1       TEXT NULL COMMENT 'ほめポイント1',
    praise_2       TEXT NULL COMMENT 'ほめポイント2',
    praise_3       TEXT NULL COMMENT 'ほめポイント3',
    tsukkomi_1     TEXT NULL COMMENT 'ツッコミポイント1',
    tsukkomi_2     TEXT NULL COMMENT 'ツッコミポイント2',
    tsukkomi_3     TEXT NULL COMMENT 'ツッコミポイント3',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_article (user_id, article_url),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

