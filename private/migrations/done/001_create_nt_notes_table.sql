-- クイックメモシステム (Google Keep風)
-- 物理パス: haitaka/private/migrations/001_create_nt_notes_table.sql

CREATE TABLE IF NOT EXISTS `nt_notes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'BaseModelでの隔離に必須',
  `title` varchar(255) DEFAULT NULL COMMENT '省略可能（雑なメモ用）',
  `content` text NOT NULL COMMENT 'メモ本文（Markdown対応を想定）',
  `bg_color` varchar(20) DEFAULT '#ffffff' COMMENT 'カードの背景色（Keep風）',
  `is_pinned` tinyint(1) DEFAULT 0 COMMENT 'トップに固定フラグ',
  `status` varchar(20) DEFAULT 'active' COMMENT 'active, archived, trash',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
