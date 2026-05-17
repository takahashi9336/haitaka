-- ============================================
-- Health: トレーニングメニューに時間（秒）を追加
-- ============================================

ALTER TABLE `hl_training_menu_items`
  ADD COLUMN `duration_sec` int(10) UNSIGNED NOT NULL DEFAULT 60 COMMENT '1セットあたりの時間（秒）' AFTER `reps`;

UPDATE `hl_training_menu_items` SET `duration_sec` = 60 WHERE `duration_sec` = 0;
