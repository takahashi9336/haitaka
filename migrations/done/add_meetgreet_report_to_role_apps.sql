-- ミーグリレポ登録を全ロールの sys_role_apps に追加
INSERT IGNORE INTO `sys_role_apps` (`role_id`, `app_id`)
SELECT r.id, a.id
FROM `sys_roles` r, `sys_apps` a
WHERE a.app_key = 'meetgreet_report'
  AND r.role_key IN ('admin', 'user', 'hinata');
