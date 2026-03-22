-- dr_series にアニメ/ドラマ種別カラムを追加
-- 物理パス: haitaka/private/migrations/next/add_category_to_dr_series.sql

ALTER TABLE `dr_series`
  ADD COLUMN `category` varchar(20) NOT NULL DEFAULT 'drama' COMMENT 'anime=アニメ / drama=ドラマ' AFTER `tmdb_id`,
  ADD INDEX `idx_dr_series_category` (`category`);

