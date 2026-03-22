-- com_media_assets に media_type カラムを追加
-- 動画・ショート・ライブなどの分類を管理する
ALTER TABLE `com_media_assets`
  ADD COLUMN `media_type` varchar(20) DEFAULT NULL COMMENT '動画種別: video, short, live' AFTER `sub_key`;
