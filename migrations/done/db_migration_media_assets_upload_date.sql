-- ============================================
-- 日向坂ポータル：メディアアセットにアップロード日時カラムを追加
-- 対象テーブル: com_media_assets
-- 目的:
--   - 各プラットフォーム上の動画アップロード日時を管理するため、
--     upload_date カラムを追加する
--   - これにより、hn_media_metadata.release_date から役割を切り離し、
--     将来的な release_date 廃止に備える
-- 実行前提:
--   - テーブル com_media_assets が既に存在していること
-- ============================================

ALTER TABLE `com_media_assets`
  ADD COLUMN `upload_date` datetime DEFAULT NULL COMMENT 'プラットフォームへのアップロード日時' AFTER `thumbnail_url`;

-- 確認用クエリ例:
--   DESC com_media_assets;
--   SELECT id, platform, media_key, upload_date FROM com_media_assets ORDER BY id DESC LIMIT 20;

