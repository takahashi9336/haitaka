-- ============================================
-- 楽曲と動画の紐付け (n:1) 対応用テーブル
-- hn_song_media_links: song_id <-> media_meta_id 中間テーブル
-- ============================================

CREATE TABLE IF NOT EXISTS `hn_song_media_links` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `song_id` bigint(20) UNSIGNED NOT NULL COMMENT 'hn_songs.id',
  `media_meta_id` bigint(20) UNSIGNED NOT NULL COMMENT 'hn_media_metadata.id',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_media_meta` (`media_meta_id`),
  KEY `song_id` (`song_id`),
  CONSTRAINT `fk_song_media_links_song` FOREIGN KEY (`song_id`) REFERENCES `hn_songs`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_song_media_links_media` FOREIGN KEY (`media_meta_id`) REFERENCES `hn_media_metadata`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='楽曲と動画の紐付け (n:1: 1曲-複数動画／1動画-1曲)';

-- 既存の hn_songs.media_meta_id からリンクを移行
INSERT INTO `hn_song_media_links` (`song_id`, `media_meta_id`)
SELECT `id` AS song_id, `media_meta_id`
FROM `hn_songs`
WHERE `media_meta_id` IS NOT NULL
ON DUPLICATE KEY UPDATE `song_id` = VALUES(`song_id`);

