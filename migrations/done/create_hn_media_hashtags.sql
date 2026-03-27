-- ============================================
-- 初参戦ライブガイド：メディアのハッシュタグ
-- hn_media_hashtags
-- 1動画に複数ハッシュタグを付与可能（多対多）
-- ============================================

CREATE TABLE IF NOT EXISTS `hn_media_hashtags` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_meta_id` bigint(20) UNSIGNED NOT NULL COMMENT 'hn_media_metadata.id',
  `hashtag` varchar(100) NOT NULL COMMENT 'ハッシュタグ（#なしで保存）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_media_hashtag` (`media_meta_id`, `hashtag`),
  KEY `idx_hashtag` (`hashtag`),
  CONSTRAINT `fk_media_hashtags_meta` FOREIGN KEY (`media_meta_id`) REFERENCES `hn_media_metadata` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='メディアに付与するハッシュタグ';
