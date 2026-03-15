-- エンタメ親アプリとドラマ・アニメの sys_apps 登録

-- 1. エンタメ親アプリをトップレベルに追加
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
VALUES (
    'entame',
    'エンタメ',
    NULL,
    '/entame/',
    NULL,
    'fa-masks-theater',
    'violet',
    'violet-50',
    '/entame/',
    '映画・ドラマ・アニメの統合ダッシュボード',
    1,
    40,
    1,
    0,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    parent_id = VALUES(parent_id),
    route_prefix = VALUES(route_prefix),
    path = VALUES(path),
    icon_class = VALUES(icon_class),
    theme_primary = VALUES(theme_primary),
    theme_light = VALUES(theme_light),
    default_route = VALUES(default_route),
    description = VALUES(description),
    is_system = VALUES(is_system),
    sort_order = VALUES(sort_order),
    is_visible = VALUES(is_visible),
    admin_only = VALUES(admin_only),
    updated_at = NOW();

-- 2. 既存 movie アプリをエンタメ配下にぶら下げる
UPDATE sys_apps m
JOIN sys_apps p ON p.app_key = 'entame'
SET m.parent_id = p.id
WHERE m.app_key = 'movie';

-- 3. アニメ・ドラマアプリをエンタメ配下に追加
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'anime',
    'アニメ',
    p.id,
    '/anime',
    'index.php',
    'fa-tv',
    'sky',
    'sky-50',
    '/anime/index.php',
    'Annict 連携アニメ管理',
    1,
    1,
    1,
    0,
    NOW(),
    NOW()
FROM sys_apps p
WHERE p.app_key = 'entame'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    parent_id = VALUES(parent_id),
    route_prefix = VALUES(route_prefix),
    path = VALUES(path),
    icon_class = VALUES(icon_class),
    theme_primary = VALUES(theme_primary),
    theme_light = VALUES(theme_light),
    default_route = VALUES(default_route),
    description = VALUES(description),
    is_system = VALUES(is_system),
    sort_order = VALUES(sort_order),
    is_visible = VALUES(is_visible),
    admin_only = VALUES(admin_only),
    updated_at = NOW();

INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'drama',
    'ドラマ',
    p.id,
    '/drama',
    'index.php',
    'fa-clapperboard',
    'violet',
    'violet-50',
    '/drama/index.php',
    'TMDB 連携ドラマ管理',
    1,
    2,
    1,
    0,
    NOW(),
    NOW()
FROM sys_apps p
WHERE p.app_key = 'entame'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    parent_id = VALUES(parent_id),
    route_prefix = VALUES(route_prefix),
    path = VALUES(path),
    icon_class = VALUES(icon_class),
    theme_primary = VALUES(theme_primary),
    theme_light = VALUES(theme_light),
    default_route = VALUES(default_route),
    description = VALUES(description),
    is_system = VALUES(is_system),
    sort_order = VALUES(sort_order),
    is_visible = VALUES(is_visible),
    admin_only = VALUES(admin_only),
    updated_at = NOW();

-- 4. movie / hinata_movie ロールにエンタメ・アニメ・ドラマを紐付け

-- entame 親
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 0
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key IN ('movie', 'hinata_movie')
  AND a.app_key = 'entame';

-- anime 子
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 1
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key IN ('movie', 'hinata_movie')
  AND a.app_key = 'anime';

-- drama 子
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 2
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key IN ('movie', 'hinata_movie')
  AND a.app_key = 'drama';

