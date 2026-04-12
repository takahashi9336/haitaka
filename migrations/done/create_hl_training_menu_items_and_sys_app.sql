-- ============================================
-- Health: トレーニングメニュー
-- hl_training_menu_items + sys_apps 子アプリ
-- ============================================

CREATE TABLE IF NOT EXISTS `hl_training_menu_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'BaseModelでの隔離に必須',
  `name` varchar(255) NOT NULL COMMENT 'メニュー名',
  `reps` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_created_at` (`user_id`, `created_at`),
  CONSTRAINT `fk_hl_training_menu_items_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Health: トレーニングメニュー';

-- 子画面: トレーニングメニュー（親 health の id を参照）
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'health_training_menu',
    'トレーニングメニュー',
    p.id,
    '/health',
    'training_menu.php',
    'fa-dumbbell',
    p.theme_primary,
    p.theme_light,
    '/health/training_menu.php',
    'トレーニングメニューと参照動画',
    0,
    1,
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

INSERT IGNORE INTO sys_role_apps (role_id, app_id)
SELECT r.id, a.id
FROM sys_roles r, sys_apps a
WHERE a.app_key = 'health_training_menu'
  AND r.role_key IN ('admin', 'user', 'hinata', 'hinata_admin', 'hinata_movie', 'movie');
