-- 共通ガイドシステム（ブロック編集で手順を作成・管理）
-- sys_guides: ガイドマスタ
-- sys_apps: admin_guides を管理画面配下に追加

CREATE TABLE IF NOT EXISTS `sys_guides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guide_key` varchar(100) NOT NULL COMMENT '画面識別子（例: meetgreet_import）',
  `title` varchar(200) NOT NULL COMMENT 'ガイドタイトル',
  `blocks` json NOT NULL COMMENT 'ブロック配列 [{type:text|image, content|src, alt?}]',
  `app_key` varchar(50) DEFAULT NULL COMMENT '紐づけアプリ（任意）',
  `show_on_first_visit` tinyint(1) NOT NULL DEFAULT 0 COMMENT '初回表示するか',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guide_key` (`guide_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='共通ガイド・手順マスタ';

-- sys_apps にガイド管理を追加（管理画面 parent_id=5）
INSERT INTO sys_apps (id, app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
VALUES
  (17, 'admin_guides', 'ガイド管理', 5, '/admin/', 'guides.php', NULL, 'slate', 'slate-100', NULL, '手順ガイドの作成・編集', 0, 5, 1, 1, NOW(), NOW())
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
