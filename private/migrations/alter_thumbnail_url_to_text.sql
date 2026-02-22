-- com_media_assets.thumbnail_url を TEXT に拡張
-- TikTok等のサムネイルURLが長大なため VARCHAR では不足
ALTER TABLE `com_media_assets`
  MODIFY COLUMN `thumbnail_url` TEXT DEFAULT NULL COMMENT 'サムネイル画像URL';
