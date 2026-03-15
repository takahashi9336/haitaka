-- 移動区間に Google Maps ルート共有URL を保存するカラムを追加
ALTER TABLE lt_transport_legs
ADD COLUMN maps_link VARCHAR(2048) NULL COMMENT 'Google Maps ルート共有URL' AFTER amount;
