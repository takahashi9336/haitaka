-- ============================================
-- 日向坂ポータル：リリース・楽曲管理機能
-- DB Migration Script
-- 実行日: 2026-02-13
-- ============================================

-- 1. リリースマスタ（シングル・アルバム）
CREATE TABLE IF NOT EXISTS `hn_releases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `release_type` enum('single','album','digital','ep','best') NOT NULL DEFAULT 'single' COMMENT 'リリース種別',
  `release_number` varchar(20) DEFAULT NULL COMMENT 'リリース番号（1st, 2nd, ベスト等）',
  `title` varchar(255) NOT NULL COMMENT 'リリースタイトル',
  `title_kana` varchar(255) DEFAULT NULL COMMENT 'よみがな',
  `release_date` date DEFAULT NULL COMMENT '発売日',
  `jacket_image_url` varchar(255) DEFAULT NULL COMMENT 'ジャケット画像URL',
  `description` text DEFAULT NULL COMMENT '説明・備考',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `release_date` (`release_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='リリースマスタ（シングル・アルバム）';

-- 2. 楽曲マスタ
CREATE TABLE IF NOT EXISTS `hn_songs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `release_id` int(11) NOT NULL COMMENT 'リリースID',
  `media_meta_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'メディアメタデータID（MVがある場合）',
  `title` varchar(255) NOT NULL COMMENT '楽曲タイトル',
  `title_kana` varchar(255) DEFAULT NULL COMMENT 'よみがな',
  `track_type` enum('title','coupling','album_only','bonus','kisei','unit','solo','other') DEFAULT 'other' COMMENT '楽曲種別',
  `track_number` int(11) DEFAULT NULL COMMENT 'トラック番号',
  `lyricist` varchar(255) DEFAULT NULL COMMENT '作詞',
  `composer` varchar(255) DEFAULT NULL COMMENT '作曲',
  `duration` int(11) DEFAULT NULL COMMENT '再生時間（秒）',
  `memo` text DEFAULT NULL COMMENT '備考',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `release_id` (`release_id`),
  UNIQUE KEY `media_meta_id` (`media_meta_id`),
  FOREIGN KEY (`release_id`) REFERENCES `hn_releases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`media_meta_id`) REFERENCES `hn_media_metadata`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='楽曲マスタ';

-- 3. 楽曲参加メンバー中間テーブル
CREATE TABLE IF NOT EXISTS `hn_song_members` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `song_id` bigint(20) UNSIGNED NOT NULL COMMENT '楽曲ID',
  `member_id` int(11) NOT NULL COMMENT 'メンバーID',
  `role` enum('center','member','under','other') DEFAULT 'member' COMMENT '役割（center:センター, member:通常参加, under:アンダー, other:その他）',
  `row_number` tinyint DEFAULT NULL COMMENT 'フォーメーション列番号（1:フロント最前列, 2:2列目, 3:3列目後列, NULL:アンダー等）',
  `position` int(11) DEFAULT NULL COMMENT '列内の位置（中央を0、左側は負の数、右側は正の数）',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT '主役級フラグ（センター・ソロパート多め）',
  `part_description` varchar(255) DEFAULT NULL COMMENT 'パート説明',
  PRIMARY KEY (`id`),
  UNIQUE KEY `song_member` (`song_id`, `member_id`),
  KEY `member_featured` (`member_id`, `is_featured`),
  FOREIGN KEY (`song_id`) REFERENCES `hn_songs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `hn_members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='楽曲参加メンバー';

-- 4. hn_media_metadata に UNIQUE KEY を追加（asset_id で一意性保証）
-- ※既存データがある場合、重複をチェックしてから実行
ALTER TABLE `hn_media_metadata` 
ADD UNIQUE KEY `asset_id` (`asset_id`);

-- ============================================
-- サンプルデータ（テスト用）
-- ============================================

-- リリースサンプル
INSERT INTO `hn_releases` (`release_type`, `release_number`, `title`, `title_kana`, `release_date`) VALUES
('single', '1st', 'キュン', 'きゅん', '2019-03-27'),
('single', '2nd', 'ドレミソラシド', 'どれみそらしど', '2019-07-17'),
('single', '3rd', 'こんなに好きになっちゃっていいの？', 'こんなにすきになっちゃっていいの', '2019-10-02');

-- ============================================
-- マイグレーション用SQL（既存データ更新）
-- ============================================

-- 既存の 'member_kojin_pv' を 'SoloPV' に変更（未実行の場合）
UPDATE `hn_media_metadata` 
SET `category` = 'SoloPV' 
WHERE `category` = 'member_kojin_pv';

-- ============================================
-- 確認用クエリ
-- ============================================

-- リリース一覧
-- SELECT * FROM hn_releases ORDER BY release_date ASC;

-- 楽曲とMVの紐付け確認
-- SELECT s.title, r.title as release_title, ma.media_key
-- FROM hn_songs s
-- JOIN hn_releases r ON s.release_id = r.id
-- LEFT JOIN hn_media_metadata hmeta ON s.media_meta_id = hmeta.id
-- LEFT JOIN com_media_assets ma ON hmeta.asset_id = ma.id;
