-- 対応管理・改善事項（管理者向け）
-- sys_improvement_items: 改善事項マスタ
-- sys_apps: admin_improvement_list を管理画面配下に追加
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

CREATE TABLE IF NOT EXISTS `sys_improvement_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `screen_name` varchar(200) NOT NULL COMMENT '画面名（どの画面からの登録か）',
  `content` text NOT NULL COMMENT '改善事項の内容',
  `status` enum('pending','done','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'pending:未対応 / done:対応済 / cancelled:見送り',
  `priority` tinyint(4) DEFAULT NULL COMMENT '優先度 1-5',
  `source_url` varchar(500) DEFAULT NULL COMMENT '登録時のURL',
  `created_by` int(11) NOT NULL COMMENT '登録者 user_id',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL COMMENT '対応日（対応済み時）',
  `memo` text DEFAULT NULL COMMENT '管理者メモ',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_screen_name` (`screen_name`(100)),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='対応管理・改善事項';

-- sys_apps に対応管理を追加（管理画面 parent_id=5）
INSERT INTO sys_apps (id, app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
VALUES
  (18, 'admin_improvement_list', '対応管理', 5, '/admin/', 'improvement_list.php', NULL, 'slate', 'slate-100', NULL, 'システムの対応・改善事項の管理', 0, 4, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  parent_id = VALUES(parent_id),
  route_prefix = VALUES(route_prefix),
  path = VALUES(path),
  description = VALUES(description),
  sort_order = VALUES(sort_order),
  is_visible = VALUES(is_visible),
  admin_only = VALUES(admin_only),
  updated_at = NOW();
