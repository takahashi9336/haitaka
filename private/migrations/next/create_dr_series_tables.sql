-- ドラマ（TVシリーズ）管理アプリ テーブル作成
-- 物理パス: haitaka/private/migrations/next/create_dr_series_tables.sql

-- TMDB TVシリーズキャッシュテーブル
CREATE TABLE IF NOT EXISTS `dr_series` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tmdb_id` int(11) UNSIGNED NOT NULL COMMENT 'TMDB上のTVシリーズID',
  `title` varchar(500) NOT NULL COMMENT '日本語タイトル',
  `original_title` varchar(500) DEFAULT NULL COMMENT '原題',
  `overview` text DEFAULT NULL COMMENT 'あらすじ',
  `poster_path` varchar(255) DEFAULT NULL COMMENT 'TMDBポスター画像パス',
  `backdrop_path` varchar(255) DEFAULT NULL COMMENT 'TMDB背景画像パス',
  `first_air_date` date DEFAULT NULL COMMENT '初回放送日',
  `last_air_date` date DEFAULT NULL COMMENT '最終放送日',
  `number_of_seasons` int(11) DEFAULT NULL COMMENT 'シーズン数',
  `number_of_episodes` int(11) DEFAULT NULL COMMENT 'エピソード数',
  `vote_average` decimal(3,1) DEFAULT NULL COMMENT 'TMDB平均評価',
  `vote_count` int(11) DEFAULT NULL COMMENT 'TMDB評価数',
  `genres` varchar(500) DEFAULT NULL COMMENT 'ジャンル（JSON配列）',
  `runtime_avg` int(11) DEFAULT NULL COMMENT '平均話数分数',
  `watch_providers` text DEFAULT NULL COMMENT '配信サービス情報（JSON）',
  `watch_providers_updated_at` datetime DEFAULT NULL COMMENT '配信情報更新日時',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dr_tmdb_id` (`tmdb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ユーザードラマ（TVシリーズ）リストテーブル
CREATE TABLE IF NOT EXISTS `dr_user_series` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'sys_users.id',
  `series_id` bigint(20) UNSIGNED NOT NULL COMMENT 'dr_series.id',
  `status` varchar(20) NOT NULL DEFAULT 'wanna_watch' COMMENT 'wanna_watch=見たい / watching=見てる / watched=見た',
  `rating` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '個人評価（1-10）',
  `memo` text DEFAULT NULL COMMENT '個人メモ・感想',
  `watched_date` date DEFAULT NULL COMMENT '視聴完了日（最終話）',
  `current_season` int(11) DEFAULT NULL COMMENT '現在視聴中シーズン',
  `current_episode` int(11) DEFAULT NULL COMMENT '現在視聴中エピソード',
  `tags` varchar(500) DEFAULT NULL COMMENT 'タグ（JSON配列）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dr_user_series` (`user_id`, `series_id`),
  KEY `idx_dr_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_dr_user_series_series` FOREIGN KEY (`series_id`) REFERENCES `dr_series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

