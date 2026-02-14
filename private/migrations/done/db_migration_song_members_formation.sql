-- ============================================
-- hn_song_members 構成変更（データ0件前提）
-- ① role / is_featured 削除 → is_center フラグに統一
-- ② position: 列内で左端=1、右にカウントアップ（ダブルセンター時は 2,3 がセンター）
-- ============================================

ALTER TABLE `hn_song_members`
ADD COLUMN `is_center` tinyint(1) NOT NULL DEFAULT 0
COMMENT 'センターフラグ（1=センター、ダブルセンターの場合は該当する複数行が1）'
AFTER `member_id`;

ALTER TABLE `hn_song_members`
MODIFY COLUMN `position` int(11) DEFAULT NULL
COMMENT '列内の位置（左端=1、右にカウントアップ。ダブルセンター例: 1列目で 2,3 がセンター）';

-- member_featured インデックスは member_id の FK で参照されているため、FK を削除してからインデックス削除
-- （制約名は環境により異なる場合あり。不明な場合は SHOW CREATE TABLE hn_song_members; で確認）
ALTER TABLE `hn_song_members` DROP FOREIGN KEY `hn_song_members_ibfk_2`;

ALTER TABLE `hn_song_members` DROP INDEX `member_featured`;
ALTER TABLE `hn_song_members` DROP COLUMN `is_featured`;
ALTER TABLE `hn_song_members` DROP COLUMN `role`;

ALTER TABLE `hn_song_members`
ADD CONSTRAINT `hn_song_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `hn_members`(`id`) ON DELETE CASCADE;
