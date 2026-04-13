-- LiveTrip: タイムライン項目に場所（place_id/緯度経度）を追加
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

ALTER TABLE lt_timeline_items
ADD COLUMN place_id VARCHAR(128) NULL COMMENT 'Google Place ID' AFTER memo,
ADD COLUMN latitude DECIMAL(10,7) NULL COMMENT '緯度' AFTER place_id,
ADD COLUMN longitude DECIMAL(10,7) NULL COMMENT '経度' AFTER latitude,
ADD COLUMN location_label VARCHAR(255) NULL COMMENT '表示用の場所名' AFTER longitude,
ADD COLUMN location_address VARCHAR(255) NULL COMMENT '表示用の住所/検索文字列' AFTER location_label;

