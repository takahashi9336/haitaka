-- Apple Music / Spotify 埋め込みURL を楽曲テーブルに追加
ALTER TABLE `hn_songs`
  ADD COLUMN `apple_music_url` VARCHAR(500) DEFAULT NULL AFTER `memo`,
  ADD COLUMN `spotify_url` VARCHAR(500) DEFAULT NULL AFTER `apple_music_url`;
