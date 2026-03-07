-- 遠征の複数日対応: タイムラインに日付、移動に出発日を追加
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

ALTER TABLE lt_timeline_items
ADD COLUMN scheduled_date DATE NULL COMMENT '対象日。NULL=当日(event_date)' AFTER trip_plan_id;

ALTER TABLE lt_transport_legs
ADD COLUMN departure_date DATE NULL COMMENT '出発日。NULL=往路なら前日/復路なら翌日で補完' AFTER direction;
