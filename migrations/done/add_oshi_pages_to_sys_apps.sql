-- 推し設定ページをサイドバーに追加
-- parent_id は hinata ポータルの sys_apps.id（環境依存のため手動調整が必要な場合あり）
-- 下記は hinata のapp_key='hinata' の id を取得して挿入するパターン

INSERT INTO sys_apps (parent_id, app_key, name, icon, route_prefix, path, default_route, admin_only, sort_order, is_visible)
SELECT id, 'hinata_oshi_settings', '推し設定', 'fa-solid fa-heart', '/hinata', 'oshi_settings.php', '/hinata/oshi_settings.php', 0, 60, 1
FROM sys_apps WHERE app_key = 'hinata' AND parent_id IS NULL
ON DUPLICATE KEY UPDATE name = VALUES(name);
