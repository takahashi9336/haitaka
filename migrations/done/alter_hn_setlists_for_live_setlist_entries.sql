-- LIVEセットリスト拡張: 非楽曲行 + ライブ時センター対応
-- - song_id を NULL 許可（song行のみ必須はアプリ側で担保）
-- - entry_type / label / block_kind / center_member_id を追加
--
-- 既存データは entry_type='song' として扱う

ALTER TABLE `hn_setlists`
  MODIFY COLUMN `song_id` bigint unsigned NULL,
  ADD COLUMN `entry_type` varchar(20) NOT NULL DEFAULT 'song' AFTER `song_id`,
  ADD COLUMN `label` varchar(255) DEFAULT NULL AFTER `encore`,
  ADD COLUMN `block_kind` varchar(50) DEFAULT NULL AFTER `label`,
  ADD COLUMN `center_member_id` int DEFAULT NULL AFTER `block_kind`;

-- 参照整合性は既存方針（FKなし）に合わせ、ここではFKを追加しない
-- 必要なら後から hn_events.id の符号統一後にFKを追加する

