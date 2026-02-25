-- ============================================
-- 映画のみ・日向坂＋映画 ロール追加
-- 設計書: docs/設計_アプリ・ロール管理テーブル.md に準拠
-- 前提: sys_apps に movie, movie_list, movie_import, hinata 等が存在すること
--       create_mv_movies_tables.sql, add_movie_child_pages.sql が実行済みであること
-- ============================================

-- ----------------------------------------
-- 1. sys_roles に2ロール追加
-- ----------------------------------------
INSERT INTO sys_roles (role_key, name, description, default_route, logo_text, sidebar_mode, created_at, updated_at)
VALUES
  ('movie', '映画のみ', '映画アプリのみ利用可', '/movie/', '映画', 'restricted', NOW(), NOW()),
  ('hinata_movie', '日向坂＋映画', '日向坂と映画の両方', '/hinata/', NULL, 'restricted', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  default_route = VALUES(default_route),
  logo_text = VALUES(logo_text),
  sidebar_mode = VALUES(sidebar_mode),
  updated_at = NOW();

-- ----------------------------------------
-- 2. sys_role_apps: movie ロールに映画関連を紐付け
-- ----------------------------------------
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 0
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'movie' AND a.app_key = 'movie';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 1
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'movie' AND a.app_key = 'movie_list';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 2
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'movie' AND a.app_key = 'movie_import';

-- ----------------------------------------
-- 3. sys_role_apps: hinata_movie ロールに日向坂＋映画を紐付け
-- ----------------------------------------
-- 日向坂 (app_key=hinata) とその子 (admin_only=0 のもの)
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 0
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'hinata';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 1
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'hinata_members';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 2
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'hinata_events';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 3
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'hinata_talk';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 4
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'hinata_media_list';

-- 映画
INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 5
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'movie';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 6
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'movie_list';

INSERT IGNORE INTO sys_role_apps (role_id, app_id, sort_order)
SELECT r.id, a.id, 7
FROM sys_roles r
CROSS JOIN sys_apps a
WHERE r.role_key = 'hinata_movie' AND a.app_key = 'movie_import';

-- ============================================
-- 実行後確認例
-- ============================================
-- SELECT * FROM sys_roles WHERE role_key IN ('movie','hinata_movie');
-- SELECT ra.*, a.app_key FROM sys_role_apps ra JOIN sys_apps a ON ra.app_id = a.id WHERE ra.role_id IN (SELECT id FROM sys_roles WHERE role_key IN ('movie','hinata_movie')) ORDER BY ra.role_id, ra.sort_order;
