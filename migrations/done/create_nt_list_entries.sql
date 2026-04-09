-- メモ拡張: 複数リスト種別エントリ（JSON payload）
-- 物理パス: haitaka/migrations/done/create_nt_list_entries.sql

CREATE TABLE IF NOT EXISTS `nt_list_entries` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'BaseModelでの隔離に必須',
  `list_kind` varchar(32) NOT NULL COMMENT 'todo, question, first_time, fun, book, generic_list',
  `title` varchar(255) DEFAULT NULL COMMENT '一覧表示用のタイトル（空ならpayloadから要約して保存してもよい）',
  `payload` json NOT NULL COMMENT 'list_kindごとのデータ本体',
  `bg_color` varchar(20) DEFAULT '#ffffff' COMMENT 'カードの背景色（Keep風）',
  `is_pinned` tinyint(1) DEFAULT 0 COMMENT 'トップに固定フラグ',
  `status` varchar(20) DEFAULT 'active' COMMENT 'active, archived',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_kind_status` (`user_id`, `list_kind`, `status`),
  KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

