-- ============================================
-- Health: HIITセッション記録用カラム
-- ============================================

ALTER TABLE `hl_training_logs`
  ADD COLUMN `log_kind` varchar(20) NOT NULL DEFAULT 'exercise' COMMENT 'exercise|hiit' AFTER `user_id`,
  ADD COLUMN `menu_snapshot` json DEFAULT NULL COMMENT 'HIIT記録時の種目一覧' AFTER `duration_sec`;
