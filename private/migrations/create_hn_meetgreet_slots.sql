-- ミーグリ予定・レポ管理テーブル
CREATE TABLE IF NOT EXISTS `hn_meetgreet_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_date` date NOT NULL COMMENT 'ミーグリ日付',
  `slot_name` varchar(50) NOT NULL COMMENT '部名（第1部, 第2部等）',
  `start_time` time DEFAULT NULL COMMENT '開始時刻',
  `end_time` time DEFAULT NULL COMMENT '終了時刻',
  `member_id` int(11) DEFAULT NULL COMMENT 'メンバーID',
  `member_name_raw` varchar(100) DEFAULT NULL COMMENT 'パース元のメンバー名（マッチ失敗時の保持用）',
  `ticket_count` int(11) DEFAULT 0 COMMENT '保有数',
  `report` text DEFAULT NULL COMMENT 'レポ/メモ',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_date` (`user_id`, `event_date`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ミーグリ予定・レポ';

-- sys_apps にミーグリ予定を子アプリとして登録（親: hinata）
INSERT INTO `sys_apps` (`app_key`, `name`, `route_prefix`, `icon_class`, `theme_primary`, `parent_id`, `sort_order`, `admin_only`)
SELECT 'hinata_meetgreet', 'ミーグリ予定', '/hinata/meetgreet.php', 'fa-solid fa-ticket', NULL, 4, 15, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `sys_apps` WHERE `app_key` = 'hinata_meetgreet');

-- 全ロールにアクセス権を付与
INSERT IGNORE INTO `sys_role_apps` (`role_id`, `app_id`)
SELECT r.id, a.id
FROM `sys_roles` r, `sys_apps` a
WHERE a.app_key = 'hinata_meetgreet'
  AND r.role_key IN ('admin', 'user', 'hinata');
