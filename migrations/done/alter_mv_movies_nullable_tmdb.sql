-- mv_movies.tmdb_id を NULL許可に変更（仮登録対応）
-- TMDB検索で見つからない映画を仮ページとして登録できるようにする
-- NULLの場合は仮登録（プレースホルダー）扱い
-- 物理パス: haitaka/private/migrations/done/alter_mv_movies_nullable_tmdb.sql

ALTER TABLE `mv_movies`
  MODIFY `tmdb_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'TMDB上の映画ID（NULLは仮登録）';

-- UNIQUE制約はMySQLでNULL複数行を許容するため、そのまま保持
