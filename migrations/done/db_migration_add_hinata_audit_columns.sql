-- 日向坂ポータル関連テーブルに監査用カラム（updated_at, update_user）を追加するマイグレーション
-- 想定バージョン: MySQL 5.7+ / 8.x

-- 1) メンバー
ALTER TABLE hn_members
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER member_info,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 2) イベント
ALTER TABLE hn_events
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER event_url,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 3) リリース
ALTER TABLE hn_releases
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 4) 楽曲
ALTER TABLE hn_songs
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 5) 楽曲参加メンバー
ALTER TABLE hn_song_members
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER part_description,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 6) リリース別メンバーアーティスト写真
ALTER TABLE hn_release_member_images
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 7) 楽曲-動画紐付け
ALTER TABLE hn_song_media_links
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 8) メンバー画像
ALTER TABLE hn_member_images
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER sort_order,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 9) リリース版別（ジャケット）
ALTER TABLE hn_release_editions
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

-- 10) 動画-メンバー紐付け
ALTER TABLE hn_media_members
  ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER member_id,
  ADD COLUMN update_user varchar(50) NOT NULL DEFAULT '' AFTER updated_at;

