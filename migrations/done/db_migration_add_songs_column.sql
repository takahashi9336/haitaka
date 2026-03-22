-- ============================================
-- ① 中間テーブルへ退避（現行 hn_songs の構造・データをコピー）
-- ============================================
CREATE TABLE hn_songs_tmp AS SELECT * FROM hn_songs;

-- ============================================
-- ② hn_songs のデータを削除
-- （hn_song_members の FK で CASCADE 削除されないよう、一時的に FK チェックを無効化）
-- ============================================
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM hn_songs;

-- ============================================
-- ③ カラム追加・変更
-- ============================================
ALTER TABLE `hn_songs` 
MODIFY COLUMN `track_type` enum(
    'title',   -- 表題曲
    'read',    -- アルバムリード曲
    'sub',     -- 共通
    'type_a',  -- TYPE-A
    'type_b',  -- TYPE-B
    'type_c',  -- TYPE-C
    'type_d',  -- TYPE-D
    'normal',  -- 通常版
    'other'    -- その他（既存データ・未分類）
) DEFAULT 'other' COMMENT 'トラックタイプ';

ALTER TABLE `hn_songs` 
ADD `formation_type` enum(
    'all',       -- 全員参加
    'kibetsu',   -- 期別曲
    'senbatsu',  -- 選抜
    'solo',      -- ソロ
    'under',     -- ひなた坂
    'unit',      -- ユニット
    'other'      -- その他（未分類）
) DEFAULT 'other' COMMENT 'フォーメーションタイプ'
AFTER track_number;

ALTER TABLE hn_songs ADD generation tinyint DEFAULT NULL COMMENT 'hn_members.generation（期別曲の場合の期）' AFTER formation_type ;
ALTER TABLE hn_songs ADD arranger varchar(50) DEFAULT NULL COMMENT 'アレンジャー' AFTER composer ;
ALTER TABLE hn_songs ADD mv_director varchar(50) DEFAULT NULL COMMENT 'MV監督' AFTER arranger ;
ALTER TABLE hn_songs ADD choreographer varchar(50) DEFAULT NULL COMMENT '振付師' AFTER mv_director ;

-- ============================================
-- ④ 中間テーブルから hn_songs へデータ移行
-- （track_type を旧enum→新enumにマッピング、新規カラムは NULL / 既定値）
-- ============================================
INSERT INTO hn_songs (
  id, release_id, media_meta_id, title, title_kana, track_type,
  track_number,  lyricist, composer,
  arranger, mv_director, choreographer,
  duration, memo, created_at, formation_type, generation
)
SELECT
  id, release_id, media_meta_id, title, title_kana,
  CASE tmp.track_type
    WHEN 'title'       THEN 'title'
    WHEN 'coupling'    THEN 'normal'
    WHEN 'album_only'  THEN 'sub'
    WHEN 'bonus'       THEN 'normal'
    WHEN 'kisei'       THEN 'other'
    WHEN 'unit'        THEN 'other'
    WHEN 'solo'        THEN 'other'
    ELSE 'other'
  END,
  track_number,  lyricist, composer,
  NULL, NULL, NULL,
  duration, memo, created_at,
  'other', NULL
FROM hn_songs_tmp tmp;

SET FOREIGN_KEY_CHECKS = 1;

-- 中間テーブル削除（不要になった場合）
-- DROP TABLE hn_songs_tmp;