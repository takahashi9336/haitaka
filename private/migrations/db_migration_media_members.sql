-- ============================================
-- 動画・メンバー紐付けテーブル (hn_media_members)
-- DB Migration Script
-- ※既存の media_refactoring で作成済みの場合はスキップ
-- ============================================

CREATE TABLE IF NOT EXISTS `hn_media_members` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_meta_id` bigint(20) UNSIGNED NOT NULL COMMENT 'hn_media_metadata.id',
  `member_id` int(11) NOT NULL COMMENT 'hn_members.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_member` (`media_meta_id`, `member_id`),
  KEY `member_id` (`member_id`),
  FOREIGN KEY (`media_meta_id`) REFERENCES `hn_media_metadata`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `hn_members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='動画・メンバー紐付け';
