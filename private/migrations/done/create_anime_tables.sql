-- アニメ管理アプリ テーブル作成（Annict OAuth 対応）
-- 物理パス: haitaka/private/migrations/done/create_anime_tables.sql

-- Annict OAuth トークン（ユーザーごとに1件）
CREATE TABLE IF NOT EXISTS `an_annict_tokens` (
  `user_id` int(11) NOT NULL COMMENT 'sys_users.id',
  `access_token` varchar(255) NOT NULL COMMENT 'Annict OAuth access_token',
  `scope` varchar(100) DEFAULT 'read write' COMMENT 'OAuth scope',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_an_annict_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Annict 作品キャッシュ（検索・補完用）
CREATE TABLE IF NOT EXISTS `an_works` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `annict_id` int(11) UNSIGNED NOT NULL COMMENT 'Annict 作品ID',
  `title` varchar(500) NOT NULL,
  `title_kana` varchar(500) DEFAULT NULL,
  `media` varchar(20) DEFAULT NULL COMMENT 'tv/ova/movie/web/other',
  `season_name` varchar(20) DEFAULT NULL COMMENT '2016-spring',
  `released_on` date DEFAULT NULL,
  `episodes_count` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `official_site_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_annict_id` (`annict_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ユーザー作品リスト（Annict がマスタ、ローカルキャッシュは任意。統計・一覧表示のため保持）
CREATE TABLE IF NOT EXISTS `an_user_works` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'sys_users.id',
  `work_id` bigint(20) UNSIGNED NOT NULL COMMENT 'an_works.id',
  `annict_work_id` int(11) UNSIGNED NOT NULL COMMENT 'Annict 作品ID（API連携用）',
  `status` varchar(30) NOT NULL DEFAULT 'wanna_watch' COMMENT 'wanna_watch/watching/watched/on_hold/stop_watching',
  `rating` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '1-5 or 1-10',
  `memo` text DEFAULT NULL,
  `watched_date` date DEFAULT NULL,
  `watched_episodes` int(11) DEFAULT NULL COMMENT '何話まで視聴済み',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_annict_work` (`user_id`, `annict_work_id`),
  KEY `idx_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_an_user_works_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_an_user_works_work` FOREIGN KEY (`work_id`) REFERENCES `an_works` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
