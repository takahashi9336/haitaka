-- 映画アプリに子画面を追加
-- 前提: sys_apps に movie の親レコードが存在すること

-- 見たい / 見た リスト
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, sort_order, is_visible, is_system, admin_only)
SELECT 'movie_list', '見たい / 見た', id, '/movie', 'list.php', 'fa-list', theme_primary, theme_light, '/movie/list.php', 1, 1, 0, 0
FROM sys_apps WHERE app_key = 'movie' LIMIT 1;

-- 一括登録
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, sort_order, is_visible, is_system, admin_only)
SELECT 'movie_import', '一括登録', id, '/movie', 'import.php', 'fa-file-import', theme_primary, theme_light, '/movie/import.php', 2, 1, 0, 0
FROM sys_apps WHERE app_key = 'movie' LIMIT 1;

-- ロール権限: 親(movie)を持つロールに子画面も追加（restricted モード対応）
-- movie_list
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT ra.role_id, new_app.id, ra.sort_order + 1
FROM sys_role_apps ra
JOIN sys_apps parent ON ra.app_id = parent.id AND parent.app_key = 'movie'
JOIN sys_apps new_app ON new_app.app_key = 'movie_list';

-- movie_import
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT ra.role_id, new_app.id, ra.sort_order + 2
FROM sys_role_apps ra
JOIN sys_apps parent ON ra.app_id = parent.id AND parent.app_key = 'movie'
JOIN sys_apps new_app ON new_app.app_key = 'movie_import';
