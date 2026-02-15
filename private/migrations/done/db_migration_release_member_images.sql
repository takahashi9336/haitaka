-- ============================================
-- リリース単位のメンバーアーティスト写真 (hn_release_member_images)
-- 楽曲フォーメーション表示で参照する、リリースごとのメンバー写真
-- ============================================

CREATE TABLE IF NOT EXISTS `hn_release_member_images` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `release_id` int(11) NOT NULL COMMENT 'hn_releases.id',
  `member_id` int(11) NOT NULL COMMENT 'hn_members.id',
  `image_url` varchar(255) NOT NULL COMMENT 'アーティスト写真URL',
  `sort_order` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `release_member` (`release_id`, `member_id`),
  KEY `release_id` (`release_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='リリース別メンバーアーティスト写真（フォーメーション表示用）';
