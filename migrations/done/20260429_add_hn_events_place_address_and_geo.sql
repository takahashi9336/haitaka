-- hn_events: 会場住所・座標（Maps連携）を追加
-- 実行前にバックアップを取得してください

ALTER TABLE hn_events
  ADD COLUMN event_place_address VARCHAR(500) NULL COMMENT 'Maps連携用住所（都道府県を含める）' AFTER event_place,
  ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER event_place_address,
  ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude,
  ADD COLUMN place_id VARCHAR(255) NULL COMMENT 'Google Places ID' AFTER longitude;

