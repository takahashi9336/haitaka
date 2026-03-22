-- ============================================
-- 日向坂ポータル：フォーメーション管理機能
-- DB Migration Script (Update)
-- 実行日: 2026-02-13
-- ============================================

-- 1. hn_songs テーブルの修正
--    center_member_id を削除（センターは hn_song_members で管理）
ALTER TABLE `hn_songs` 
DROP FOREIGN KEY `hn_songs_ibfk_3`;

ALTER TABLE `hn_songs` 
DROP COLUMN `center_member_id`;

-- 2. hn_song_members テーブルの修正
--    role の enum を変更（福神削除、member追加）
ALTER TABLE `hn_song_members` 
MODIFY COLUMN `role` enum('center','member','under','other') DEFAULT 'member' 
COMMENT '役割（center:センター, member:通常参加, under:アンダー, other:その他）';

--    row_number カラムを追加（フォーメーション列番号）
ALTER TABLE `hn_song_members` 
ADD COLUMN `row_number` tinyint DEFAULT NULL 
COMMENT 'フォーメーション列番号（1:フロント最前列, 2:2列目, 3:3列目後列, NULL:アンダー等）' 
AFTER `role`;

--    position カラムのコメントを更新
ALTER TABLE `hn_song_members` 
MODIFY COLUMN `position` int(11) DEFAULT NULL 
COMMENT '列内の位置（中央を0、左側は負の数、右側は正の数）';

-- ============================================
-- 確認用クエリ
-- ============================================

-- テーブル構造確認
-- DESCRIBE hn_songs;
-- DESCRIBE hn_song_members;

-- サンプルデータ登録例（コメントアウト）
/*
-- 楽曲登録（例: 1stシングル「キュン」）
INSERT INTO hn_songs (release_id, title, track_type, track_number) VALUES
(1, 'キュン', 'title', 1);

-- フォーメーション登録（ダブルセンター例）
INSERT INTO hn_song_members (song_id, member_id, role, row_number, position, is_featured) VALUES
-- フロント（1列目）: 4人
(1, 2, 'member', 1, -2, 0),   -- 左端
(1, 5, 'center', 1, -1, 1),   -- センター左
(1, 10, 'center', 1, 1, 1),   -- センター右
(1, 3, 'member', 1, 2, 0),    -- 右端

-- 2列目: 5人
(1, 4, 'member', 2, -2, 0),
(1, 6, 'member', 2, -1, 0),
(1, 7, 'member', 2, 0, 0),
(1, 8, 'member', 2, 1, 0),
(1, 9, 'member', 2, 2, 0);
*/
