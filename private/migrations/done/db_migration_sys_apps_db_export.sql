-- 管理画面の子画面「DB一括抽出」を sys_apps に追加
-- 全CREATE文・スキーマ概要（Markdown）・JSON の一括ダウンロードを提供する画面

INSERT INTO sys_apps (id, app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
VALUES
  (16, 'admin_db_export', 'DB一括抽出', 5, '/admin/', 'db_export.php', NULL, 'slate', 'slate-100', NULL, '全CREATE文・スキーマ概要・JSONの一括ダウンロード', 0, 4, 1, 1, NOW(), NOW())
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
