-- live_trip: 遠征タイトルを追加し、イベント未紐付でも管理可能にする
-- 実行前にバックアップを取得してください

ALTER TABLE lt_trip_plans
  ADD COLUMN title VARCHAR(255) NULL AFTER id;

-- 既存データ補完: 先頭イベント名をタイトルへ
UPDATE lt_trip_plans tp
LEFT JOIN (
    SELECT
        tpe.trip_plan_id,
        COALESCE(he.event_name, le.event_name) AS event_name,
        ROW_NUMBER() OVER (
            PARTITION BY tpe.trip_plan_id
            ORDER BY COALESCE(he.event_date, le.event_date) ASC, tpe.id ASC
        ) AS rn
    FROM lt_trip_plan_events tpe
    LEFT JOIN hn_events he ON tpe.event_type = 'hinata' AND tpe.hn_event_id = he.id
    LEFT JOIN lt_events le ON tpe.event_type = 'generic' AND tpe.lt_event_id = le.id
) ev ON ev.trip_plan_id = tp.id AND ev.rn = 1
SET tp.title = COALESCE(NULLIF(TRIM(ev.event_name), ''), CONCAT('遠征 #', tp.id))
WHERE tp.title IS NULL OR TRIM(tp.title) = '';

ALTER TABLE lt_trip_plans
  MODIFY COLUMN title VARCHAR(255) NOT NULL;
