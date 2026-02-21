-- mv_user_moviesにタグカラムを追加
ALTER TABLE mv_user_movies
    ADD COLUMN tags JSON DEFAULT NULL COMMENT 'ユーザー定義タグ（JSON配列）';
