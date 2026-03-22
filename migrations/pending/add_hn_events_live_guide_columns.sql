-- ============================================
-- 初参戦ライブガイド機能：hn_events 拡張
-- event_hashtag: キャンペーンハッシュタグ（例: 七回目のひな誕祭）
-- collaboration_urls: コラボ企画ページURLの配列 JSON
-- ============================================

ALTER TABLE `hn_events`
  ADD COLUMN `event_hashtag` varchar(100) DEFAULT NULL COMMENT 'キャンペーンハッシュタグ（#なしで保存）' AFTER `event_url`,
  ADD COLUMN `collaboration_urls` json DEFAULT NULL COMMENT 'コラボ企画ページURLの配列' AFTER `event_hashtag`;
