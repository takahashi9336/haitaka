-- ミーグリレポ用アバター画像テーブル
-- ユーザーごと・メンバーごとに1枚のアバターを保持

CREATE TABLE IF NOT EXISTS `hn_meetgreet_report_avatars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL COMMENT 'hn_members.id',
  `image_path` varchar(500) NOT NULL COMMENT '画像の相対パス',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_member` (`user_id`, `member_id`),
  KEY `idx_member` (`member_id`),
  CONSTRAINT `fk_mg_avatars_member` FOREIGN KEY (`member_id`) REFERENCES `hn_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ミーグリレポ用アバター画像';
