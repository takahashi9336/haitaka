-- ミーグリイベントの部数カラムを追加
ALTER TABLE `hn_events`
  ADD COLUMN `mg_rounds` tinyint unsigned DEFAULT NULL COMMENT 'ミーグリ部数' AFTER `category`;
