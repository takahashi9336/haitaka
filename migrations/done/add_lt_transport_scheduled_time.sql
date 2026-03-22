-- 移動区間に時刻指定を追加（タイムライン統合用）
ALTER TABLE lt_transport_legs
ADD COLUMN scheduled_time VARCHAR(50) NULL COMMENT '例: 08:00, 09:30' AFTER duration_min;
