-- ============================================
-- Healthアプリを sys_apps / sys_role_apps に追加
-- - トップ: health
-- - 子画面: health_kitchen_stock（食材ストック）
-- ============================================

-- 1) トップレベル Health
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
VALUES (
    'health',
    'Health',
    NULL,
    '/health/',
    NULL,
    'fa-heart-pulse',
    'emerald',
    'emerald-50',
    '/health/',
    '健康系ユーティリティ（食材ストック等）',
    1,
    45,
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

-- 2) 子画面: 食材ストック（親 health の id を参照して追加）
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'health_kitchen_stock',
    '食材ストック',
    p.id,
    '/health',
    'kitchen_stock.php',
    'fa-basket-shopping',
    p.theme_primary,
    p.theme_light,
    '/health/kitchen_stock.php',
    '冷蔵庫/冷凍庫の在庫管理',
    0,
    0,
    1,
    0,
    NOW(),
    NOW()
FROM sys_apps p
WHERE p.app_key = 'health' AND p.parent_id IS NULL
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

-- 3) restricted ロール向け（将来の restricted 運用でも見えるように付与）
INSERT IGNORE INTO sys_role_apps (role_id, app_id)
SELECT r.id, a.id
FROM sys_roles r, sys_apps a
WHERE a.app_key IN ('health', 'health_kitchen_stock')
  AND r.role_key IN ('admin', 'user', 'hinata', 'hinata_admin', 'hinata_movie', 'movie');

