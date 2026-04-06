-- LIVE拡張: 影ナレ（イベントに1つ）管理テーブル
-- 影ナレはセットリスト行ではなく、LIVEイベント（hn_events.category=1）に1件だけ紐づく想定

CREATE TABLE IF NOT EXISTS `hn_event_shadow_narrations` (
  `event_id` bigint unsigned NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `update_user` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `hn_event_shadow_narration_members` (
  `event_id` bigint unsigned NOT NULL,
  `member_id` int NOT NULL,
  PRIMARY KEY (`event_id`, `member_id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- event_id の符号が hn_events と完全一致していない既存事情があるため、ここではFKを張らない
-- member_id は hn_members 参照だが、既存方針に合わせFKは張らない（必要なら後で追加）

