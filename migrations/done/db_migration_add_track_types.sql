-- ============================================
-- 日向坂ポータル：楽曲種別の拡張
-- DB Migration Script
-- 実行日: 2026-02-13
-- ============================================

-- hn_songs.track_type に期別曲・ユニット曲・ソロ曲を追加
ALTER TABLE `hn_songs` 
MODIFY COLUMN `track_type` enum(
    'title',         -- 表題曲
    'coupling',      -- カップリング
    'album_only',    -- アルバム収録曲
    'bonus',         -- ボーナストラック
    'kisei',         -- 期別曲（1期生曲、2期生曲等）
    'unit',          -- ユニット曲
    'solo',          -- ソロ曲
    'other'          -- その他
) DEFAULT 'other' COMMENT '楽曲種別';

-- ============================================
-- 確認用クエリ
-- ============================================

-- テーブル構造確認
-- DESCRIBE hn_songs;

-- ============================================
-- サンプルデータ例
-- ============================================

/*
-- 期別曲の例
INSERT INTO hn_songs (release_id, title, track_type, track_number) VALUES
(1, '1期生の曲', 'kisei', 2);

-- ユニット曲の例
INSERT INTO hn_songs (release_id, title, track_type, track_number) VALUES
(1, 'おすしのテーマ', 'unit', 3);

-- ソロ曲の例
INSERT INTO hn_songs (release_id, title, track_type, track_number) VALUES
(2, '加藤史帆ソロ', 'solo', 4);
*/
