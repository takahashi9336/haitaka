-- LiveTrip: タイムライン項目に所要時間(分)を追加
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

ALTER TABLE lt_timeline_items
ADD COLUMN duration_min SMALLINT UNSIGNED NULL COMMENT '所要時間(分)。未指定は表示側で30分扱い' AFTER scheduled_time;

