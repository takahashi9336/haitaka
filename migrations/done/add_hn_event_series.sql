-- イベント「系列」（上位のくくり）マスタと hn_events への参照
-- 実行前にバックアップを取得してください

CREATE TABLE IF NOT EXISTS hn_event_series (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL COMMENT '系列表示名',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_hn_event_series_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日向坂イベント系列';

ALTER TABLE hn_events
  ADD COLUMN series_id BIGINT UNSIGNED NULL COMMENT 'hn_event_series.id' AFTER category,
  ADD INDEX idx_hn_events_series_id (series_id);
