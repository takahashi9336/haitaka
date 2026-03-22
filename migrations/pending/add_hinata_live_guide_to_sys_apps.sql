-- 初参戦ライブガイドを日向坂ポータルにメニュー追加
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'hinata_live_guide',
    '初参戦ガイド',
    h.id,
    '/hinata',
    'live_guide.php',
    'fa-solid fa-music',
    h.theme_primary,
    h.theme_light,
    '/hinata/live_guide.php',
    'ライブ初参加者向けの曲と動画一覧',
    0,
    14,
    1,
    0,
    NOW(),
    NOW()
FROM sys_apps h
WHERE h.app_key = 'hinata' AND h.parent_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM sys_apps WHERE app_key = 'hinata_live_guide')
LIMIT 1;

-- ロールにアクセス権付与（既存の日向坂系ロール）
INSERT IGNORE INTO sys_role_apps (role_id, app_id)
SELECT r.id, a.id
FROM sys_roles r, sys_apps a
WHERE a.app_key = 'hinata_live_guide'
  AND r.role_key IN ('admin', 'user', 'hinata', 'hinata_admin', 'hinata_movie');
