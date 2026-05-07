-- 映画出演者（上位キャスト/監督/脚本）保存テーブル
-- 物理パス: haitaka/private/migrations/todo/create_mv_movie_credits.sql

CREATE TABLE IF NOT EXISTS `mv_movie_credits` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `movie_id` bigint(20) UNSIGNED NOT NULL COMMENT 'mv_movies.id',
  `tmdb_movie_id` int(11) UNSIGNED NOT NULL COMMENT 'mv_movies.tmdb_id（NULLは対象外）',
  `role_kind` varchar(20) NOT NULL COMMENT 'cast/director/writer',
  `rank_no` tinyint(3) UNSIGNED NOT NULL COMMENT '作品内の順序（castはorder基準、crewは出現順ベース）',
  `person_tmdb_id` int(11) UNSIGNED NOT NULL COMMENT 'TMDB person id',
  `person_name` varchar(255) NOT NULL COMMENT '表示名',
  `character_name` varchar(255) DEFAULT NULL COMMENT 'castの役名',
  `job_name` varchar(255) DEFAULT NULL COMMENT 'crewのjob（Director/Writer/Screenplay等）',
  `department` varchar(255) DEFAULT NULL COMMENT 'crewのdepartment',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mv_movie_credits_movie_role_person` (`movie_id`, `role_kind`, `person_tmdb_id`),
  KEY `idx_mv_movie_credits_movie_role_rank` (`movie_id`, `role_kind`, `rank_no`),
  KEY `idx_mv_movie_credits_person` (`person_tmdb_id`),
  KEY `idx_mv_movie_credits_role` (`role_kind`),
  CONSTRAINT `fk_mv_movie_credits_movie` FOREIGN KEY (`movie_id`) REFERENCES `mv_movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

