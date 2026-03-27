-- ============================================
-- 初参戦ライブガイド：イベント×楽曲×出る確度
-- hn_event_guide_songs
-- 当日未確定の曲を手動登録し、確度付きで管理
-- ============================================

-- hn_events.id の型は環境により int(11) / bigint 等の差があるため、event_id への FK は未設定（JOINで運用）
CREATE TABLE IF NOT EXISTS `hn_event_guide_songs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL COMMENT 'hn_events.id',
  `song_id` bigint(20) UNSIGNED NOT NULL COMMENT 'hn_songs.id',
  `likelihood` enum('certain','high','possible') NOT NULL DEFAULT 'possible' COMMENT 'certain=ほぼ確実, high=高確率, possible=出る可能性あり',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_song` (`event_id`, `song_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_song_id` (`song_id`),
  KEY `idx_likelihood` (`likelihood`),
  CONSTRAINT `fk_event_guide_songs_song` FOREIGN KEY (`song_id`) REFERENCES `hn_songs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='初参戦ライブガイド用 イベント候補曲（出る確度付き）';
