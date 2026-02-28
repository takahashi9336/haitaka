-- ミーグリレポ（チャット形式）管理テーブル
-- 1つのスロット(部)に対して複数レポ(やり取り)を登録可能

CREATE TABLE IF NOT EXISTS `hn_meetgreet_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL COMMENT 'hn_meetgreet_slots.id',
  `ticket_used` int(11) NOT NULL DEFAULT 1 COMMENT 'このレポで使用した枚数',
  `my_nickname` varchar(50) DEFAULT NULL COMMENT 'レポ内での自分の表示名',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'スロット内でのレポ並び順',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slot_order` (`slot_id`, `sort_order`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_mg_reports_slot` FOREIGN KEY (`slot_id`) REFERENCES `hn_meetgreet_slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ミーグリレポ（1回のやり取り）';

CREATE TABLE IF NOT EXISTS `hn_meetgreet_report_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `report_id` bigint(20) unsigned NOT NULL COMMENT 'hn_meetgreet_reports.id',
  `sender_type` enum('member','self','narration') NOT NULL DEFAULT 'self' COMMENT 'メッセージ送信者タイプ',
  `content` text NOT NULL COMMENT 'メッセージ本文',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'メッセージ順序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_order` (`report_id`, `sort_order`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_mg_report_messages_report` FOREIGN KEY (`report_id`) REFERENCES `hn_meetgreet_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ミーグリレポ チャットメッセージ';
