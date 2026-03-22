-- hn_event_movies.movie_id の FK 参照先を旧テーブル com_youtube_embed_data から
-- 新テーブル com_media_assets に変更する（メディアリファクタリング対応漏れ）

-- 1) 現在の FK 制約名を確認する（結果を見て手順2のDROP文に反映する）
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME = 'hn_event_movies'
   AND COLUMN_NAME = 'movie_id'
   AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 2) 既存の FK を削除（↑で確認した CONSTRAINT_NAME に置き換える。既に削除済みなら省略）
-- ALTER TABLE `hn_event_movies` DROP FOREIGN KEY `ここに実際の制約名`;

-- 3) movie_id の型を com_media_assets.id に合わせる (int → bigint UNSIGNED)
ALTER TABLE `hn_event_movies`
  MODIFY COLUMN `movie_id` bigint(20) UNSIGNED NOT NULL;

-- 4) 新しい FK を追加
ALTER TABLE `hn_event_movies`
  ADD CONSTRAINT `fk_event_movies_asset`
  FOREIGN KEY (`movie_id`) REFERENCES `com_media_assets` (`id`)
  ON DELETE CASCADE;
