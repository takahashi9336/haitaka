-- 移動区間に費用(amount)を追加
ALTER TABLE lt_transport_legs
ADD COLUMN amount INT UNSIGNED NULL COMMENT '交通費(円)' AFTER scheduled_time;
