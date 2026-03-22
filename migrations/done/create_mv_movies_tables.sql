-- 映画管理アプリ テーブル作成
-- 物理パス: haitaka/private/migrations/done/create_mv_movies_tables.sql

-- TMDB映画キャッシュテーブル
CREATE TABLE IF NOT EXISTS `mv_movies` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tmdb_id` int(11) UNSIGNED NOT NULL COMMENT 'TMDB上の映画ID',
  `title` varchar(500) NOT NULL COMMENT '日本語タイトル',
  `original_title` varchar(500) DEFAULT NULL COMMENT '原題',
  `overview` text DEFAULT NULL COMMENT 'あらすじ',
  `poster_path` varchar(255) DEFAULT NULL COMMENT 'TMDBポスター画像パス',
  `backdrop_path` varchar(255) DEFAULT NULL COMMENT 'TMDB背景画像パス',
  `release_date` date DEFAULT NULL COMMENT '公開日',
  `vote_average` decimal(3,1) DEFAULT NULL COMMENT 'TMDB平均評価',
  `vote_count` int(11) DEFAULT NULL COMMENT 'TMDB評価数',
  `genres` varchar(500) DEFAULT NULL COMMENT 'ジャンル（JSON配列）',
  `runtime` int(11) DEFAULT NULL COMMENT '上映時間（分）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tmdb_id` (`tmdb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ユーザー映画リストテーブル
CREATE TABLE IF NOT EXISTS `mv_user_movies` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'sys_users.id',
  `movie_id` bigint(20) UNSIGNED NOT NULL COMMENT 'mv_movies.id',
  `status` varchar(20) NOT NULL DEFAULT 'watchlist' COMMENT 'watchlist=見たい / watched=見た',
  `rating` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '個人評価（1-10）',
  `memo` text DEFAULT NULL COMMENT '個人メモ・感想',
  `watched_date` date DEFAULT NULL COMMENT '視聴日',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_movie` (`user_id`, `movie_id`),
  KEY `idx_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_mv_user_movies_movie` FOREIGN KEY (`movie_id`) REFERENCES `mv_movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- sys_apps にアプリ登録
INSERT INTO `sys_apps` (`app_key`, `name`, `icon_class`, `route_prefix`, `default_route`, `theme_primary`, `theme_light`, `is_visible`, `admin_only`, `sort_order`)
VALUES ('movie', '映画リスト', 'fa-film', '/movie', '/movie/index.php', 'violet', NULL, 1, 0, 50)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 全ロールにアプリを紐付け（既存ロールに合わせて調整してください）
-- INSERT INTO `sys_role_apps` (`role_id`, `app_id`) SELECT r.id, a.id FROM sys_roles r, sys_apps a WHERE a.app_key = 'movie';
