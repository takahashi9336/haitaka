-- ============================================
-- アプリ・ロール管理テーブル 初期データ
-- 設計書: docs/設計_アプリ・ロール管理テーブル.md
-- 実行前提: sys_apps, sys_roles, sys_role_apps テーブルが作成済みであること
--
-- 想定: テーブル作成直後の初回投入。既にレコードがある場合は
--       ON DUPLICATE KEY UPDATE で同一 id / role_key / app_key を上書きする。
--       sys_roles の id を変更している環境では、sys_role_apps の role_id を
--       実際の id に合わせて修正するか、本ファイルの sys_roles の id を外して
--       AUTO_INCREMENT に任せ、sys_role_apps は role_key で紐付ける等の対応が必要。
-- ============================================

-- ----------------------------------------
-- 1. sys_roles（ロールマスタ）
-- ----------------------------------------
-- 既存データがある場合はスキップする想定。必要に応じて DELETE または TRUNCATE の後に実行。
INSERT INTO sys_roles (id, role_key, name, description, default_route, logo_text, sidebar_mode, created_at, updated_at)
VALUES
  (1, 'admin',  '管理者',     '全アプリ＋管理画面',           '/index.php', NULL,              'full',        NOW(), NOW()),
  (2, 'user',   '一般ユーザー', '管理画面以外のアプリ',         '/index.php', NULL,              'full',        NOW(), NOW()),
  (3, 'hinata', '日向坂のみ',   '日向坂ポータルとその子のみ', '/hinata/',   '日向坂ポータル', 'restricted',  NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  default_route = VALUES(default_route),
  logo_text = VALUES(logo_text),
  sidebar_mode = VALUES(sidebar_mode),
  updated_at = NOW();

-- ----------------------------------------
-- 2. sys_apps（アプリ・画面マスタ）
-- ----------------------------------------
-- トップレベル: parent_id = NULL
-- 子画面: parent_id = 親アプリの id（日向坂=4, 管理画面=5）
-- ID を固定して登録し、sys_role_apps から参照する。
INSERT INTO sys_apps (id, app_key, name, parent_id, route_prefix, path, icon_class, theme_primary, theme_light, default_route, description, is_system, sort_order, is_visible, admin_only, created_at, updated_at)
VALUES
  -- トップレベル
  (1, 'dashboard',     'ダッシュボード', NULL, '/',              NULL, 'fa-house',         'indigo', 'indigo-50', '/index.php', 'トップのダッシュボード', 1, 0, 1, 0, NOW(), NOW()),
  (2, 'task_manager',  'タスク管理',     NULL, '/task_manager/',  NULL, 'fa-list-check',    'indigo', 'indigo-50', '/task_manager/', 'タスク管理アプリ', 1, 10, 1, 0, NOW(), NOW()),
  (3, 'note',         'メモ',          NULL, '/note/',          NULL, 'fa-lightbulb',     'indigo', 'indigo-50', '/note/', 'メモ・ノートアプリ', 1, 20, 1, 0, NOW(), NOW()),
  (4, 'hinata',       '日向坂ポータル', NULL, '/hinata/',       NULL, 'fa-star',          'sky',    'sky-50',    '/hinata/', '日向坂ポータル', 1, 30, 1, 0, NOW(), NOW()),
  (5, 'admin',        '管理画面',      NULL, '/admin/',         NULL, 'fa-shield-halved', 'slate',  'slate-100', '/admin/', 'システム管理', 1, 100, 1, 1, NOW(), NOW()),
  -- 日向坂ポータル配下（parent_id = 4）
  (6, 'hinata_members',           'メンバー帳',    4, '/hinata/', 'members.php',  NULL, 'sky', 'sky-50', NULL, 'メンバー一覧', 0, 0, 1, 0, NOW(), NOW()),
  (7, 'hinata_events',            'イベント',     4, '/hinata/', 'events.php',   NULL, 'sky', 'sky-50', NULL, 'イベント一覧', 0, 1, 1, 0, NOW(), NOW()),
  (8, 'hinata_talk',               'ミーグリネタ帳', 4, '/hinata/', 'talk.php',    NULL, 'sky', 'sky-50', NULL, 'ネタ帳', 0, 2, 1, 0, NOW(), NOW()),
  (9, 'hinata_media_list',         '動画一覧',     4, '/hinata/', 'media_list.php', NULL, 'sky', 'sky-50', NULL, '動画一覧', 0, 3, 1, 0, NOW(), NOW()),
  (10, 'hinata_media_import',      '動画一括登録', 4, '/hinata/', 'media_import.php', NULL, 'sky', 'sky-50', NULL, '管理者用・動画一括登録', 0, 4, 1, 1, NOW(), NOW()),
  (11, 'hinata_media_member_admin','動画・メンバー紐付け', 4, '/hinata/', 'media_member_admin.php', NULL, 'sky', 'sky-50', NULL, '管理者用・紐付け', 0, 5, 1, 1, NOW(), NOW()),
  -- 管理画面配下（parent_id = 5）
  (12, 'admin_users',   'ユーザー管理',  5, '/admin/', 'users.php',   NULL, 'slate', 'slate-100', NULL, 'ユーザー追加・パスワードリセット', 0, 0, 1, 1, NOW(), NOW()),
  (13, 'admin_apps',    'アプリ管理',   5, '/admin/', 'apps.php',    NULL, 'slate', 'slate-100', NULL, 'sys_apps の登録・編集', 0, 1, 1, 1, NOW(), NOW()),
  (14, 'admin_roles',   'ロール管理',   5, '/admin/', 'roles.php',   NULL, 'slate', 'slate-100', NULL, 'sys_roles の登録・編集', 0, 2, 1, 1, NOW(), NOW()),
  (15, 'admin_db_viewer','DBビューワ',  5, '/db_viewer/', NULL,       NULL, 'slate', 'slate-100', NULL, 'データベース参照', 0, 3, 1, 1, NOW(), NOW())
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

-- ----------------------------------------
-- 3. sys_role_apps（ロール別アプリ許可）
-- ----------------------------------------
-- hinata ロール（role_id=3）は sidebar_mode=restricted のため、
-- 日向坂ポータル(id=4) とその子のうち admin_only=0 のみ許可。
-- （動画一括登録・動画・メンバー紐付けは admin_only のため hinata には出さない）
INSERT INTO sys_role_apps (role_id, app_id, sort_order, created_at)
VALUES
  (3, 4, 0, NOW()),  -- 日向坂ポータル
  (3, 6, 1, NOW()),  -- メンバー帳
  (3, 7, 2, NOW()),  -- イベント
  (3, 8, 3, NOW()),  -- ミーグリネタ帳
  (3, 9, 4, NOW())   -- 動画一覧
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

-- admin / user は sidebar_mode=full のため sys_role_apps に登録不要（全アプリ表示）。

-- ============================================
-- 実行後確認例
-- ============================================
-- SELECT * FROM sys_roles ORDER BY id;
-- SELECT id, app_key, name, parent_id, route_prefix, path, admin_only FROM sys_apps ORDER BY sort_order, id;
-- SELECT * FROM sys_role_apps WHERE role_id = 3 ORDER BY sort_order;
