-- ============================================
-- Health: ルーティン表
-- hl_routines / hl_routine_phases / hl_routine_items
-- + sys_apps 子アプリ + role_apps
-- ============================================

-- 1. ルーティン定義
CREATE TABLE IF NOT EXISTS `hl_routines` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'BaseModelでの隔離に必須',
  `name` varchar(255) NOT NULL COMMENT 'ルーティン名',
  `type` varchar(20) NOT NULL DEFAULT '平日' COMMENT '平日/休日',
  `theme` varchar(20) NOT NULL DEFAULT 'morning' COMMENT 'morning/night',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_sort` (`user_id`, `sort_order`),
  CONSTRAINT `fk_hl_routines_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Health: ルーティン定義';

-- 2. フェーズ（時刻区分）
CREATE TABLE IF NOT EXISTS `hl_routine_phases` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `routine_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL COMMENT 'フェーズ名（例: 7:50, 職場フェーズ）',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_routine_sort` (`routine_id`, `sort_order`),
  CONSTRAINT `fk_hl_routine_phases_routine` FOREIGN KEY (`routine_id`) REFERENCES `hl_routines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hl_routine_phases_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Health: ルーティン フェーズ';

-- 3. アイテム（個別行動）
CREATE TABLE IF NOT EXISTS `hl_routine_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `phase_id` bigint(20) UNSIGNED NOT NULL,
  `content` varchar(500) NOT NULL COMMENT '行動テキスト',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phase_sort` (`phase_id`, `sort_order`),
  CONSTRAINT `fk_hl_routine_items_phase` FOREIGN KEY (`phase_id`) REFERENCES `hl_routine_phases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hl_routine_items_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Health: ルーティン アイテム';

-- 4. sys_apps に子アプリ登録
INSERT INTO sys_apps (app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
SELECT
    'health_routine',
    'ルーティン表',
    p.id,
    '/health',
    'routine.php',
    'fa-clock',
    p.theme_primary,
    p.theme_light,
    '/health/routine.php',
    '日々のルーティンを管理・印刷',
    0,
    3,
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

-- 5. role_apps
INSERT IGNORE INTO sys_role_apps (role_id, app_id)
SELECT r.id, a.id
FROM sys_roles r, sys_apps a
WHERE a.app_key = 'health_routine'
  AND r.role_key IN ('admin', 'user', 'hinata', 'hinata_admin', 'hinata_movie', 'movie');
