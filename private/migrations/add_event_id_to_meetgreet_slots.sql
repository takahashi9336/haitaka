-- hn_meetgreet_slots にイベント紐付けカラムを追加
ALTER TABLE `hn_meetgreet_slots`
  ADD COLUMN `event_id` int(11) DEFAULT NULL COMMENT '紐付けイベントID' AFTER `user_id`,
  ADD KEY `event_id` (`event_id`);
