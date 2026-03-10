-- LiveTrip 移動: 往路/復路区分を廃止
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

ALTER TABLE lt_transport_legs
DROP COLUMN direction;
