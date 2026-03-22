-- 遠征に複数イベントを紐づけ可能に
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

-- ============================================
-- 1. hn_user_events_status に座席・感想を追加
-- ============================================
ALTER TABLE hn_user_events_status
  ADD COLUMN seat_info VARCHAR(255) NULL COMMENT '座席情報' AFTER status,
  ADD COLUMN impression TEXT NULL COMMENT '参加後の感想' AFTER seat_info;

-- ============================================
-- 2. lt_trip_plan_events 新規作成
-- ============================================
CREATE TABLE IF NOT EXISTS lt_trip_plan_events (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id    BIGINT UNSIGNED NOT NULL,
    event_type      VARCHAR(20) NOT NULL COMMENT 'hinata|generic',
    hn_event_id     BIGINT UNSIGNED NULL COMMENT 'hn_events.id',
    lt_event_id     BIGINT UNSIGNED NULL COMMENT 'lt_events.id',
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    seat_info       VARCHAR(255) NULL COMMENT '汎用イベント用座席',
    impression      TEXT NULL COMMENT '汎用イベント用感想',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_trip (trip_plan_id),
    KEY idx_hn_event (hn_event_id),
    KEY idx_lt_event (lt_event_id),
    FOREIGN KEY (trip_plan_id) REFERENCES lt_trip_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='遠征とイベントの紐づけ（1対多）';

-- ============================================
-- 3. 既存データ移行
-- ============================================
INSERT INTO lt_trip_plan_events (trip_plan_id, event_type, hn_event_id, lt_event_id, sort_order, seat_info, impression)
SELECT tp.id, tp.event_type, tp.hn_event_id, tp.lt_event_id, 0,
       CASE WHEN tp.event_type = 'generic' THEN tp.seat_info ELSE NULL END,
       CASE WHEN tp.event_type = 'generic' THEN tp.impression ELSE NULL END
FROM lt_trip_plans tp
WHERE (tp.hn_event_id IS NOT NULL OR tp.lt_event_id IS NOT NULL);

-- 日向坂イベントの座席・感想を hn_user_events_status に反映
INSERT INTO hn_user_events_status (user_id, event_id, status, seat_info, impression)
SELECT m.user_id, tp.hn_event_id, 1, tp.seat_info, tp.impression
FROM lt_trip_plans tp
JOIN lt_trip_members m ON m.trip_plan_id = tp.id
WHERE tp.event_type = 'hinata' AND tp.hn_event_id IS NOT NULL
  AND (tp.seat_info IS NOT NULL OR tp.impression IS NOT NULL)
ON DUPLICATE KEY UPDATE
  seat_info = COALESCE(VALUES(seat_info), hn_user_events_status.seat_info),
  impression = COALESCE(VALUES(impression), hn_user_events_status.impression);

-- ============================================
-- 4. lt_trip_plans からイベント関連列を削除
-- ============================================
ALTER TABLE lt_trip_plans
  DROP COLUMN event_type,
  DROP COLUMN hn_event_id,
  DROP COLUMN lt_event_id,
  DROP COLUMN seat_info;
