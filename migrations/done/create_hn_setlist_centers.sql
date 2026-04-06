-- LIVE拡張: セットリスト行のセンターメンバー（複数対応）
-- hn_setlists.center_member_id は残しつつ、新規テーブルを正として扱う

CREATE TABLE IF NOT EXISTS `hn_setlist_centers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setlist_id` bigint unsigned NOT NULL COMMENT 'hn_setlists.id',
  `member_id` int NOT NULL COMMENT 'hn_members.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setlist_member` (`setlist_id`, `member_id`),
  KEY `idx_setlist_id` (`setlist_id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

